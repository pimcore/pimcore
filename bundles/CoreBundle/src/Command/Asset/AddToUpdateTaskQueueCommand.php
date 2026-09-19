<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use Pimcore;
use Pimcore\Config;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db\Helper;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function implode;
use function sprintf;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:add-to-update-task-queue',
    description: 'Adds assets to update task queue for re-processing (previews, meta-data, ...)',
)]
class AddToUpdateTaskQueueCommand extends AbstractCommand
{
    /**
     * Custom settings which are written by the asset update task, grouped by asset type.
     * If one of them is not set at all, the asset was never processed successfully by the update task
     * and therefore is missing (meta-)data.
     */
    private const METADATA_CUSTOM_SETTINGS = [
        'image' => ['embeddedMetaDataExtracted', 'imageDimensionsCalculated'],
        'video' => ['embeddedMetaDataExtracted', 'duration', 'videoWidth', 'videoHeight'],
        'document' => ['document_page_count'],
    ];

    private const ASSETS_PER_LOOP = 10;

    protected array $types = ['image', 'video', 'document'];

    protected function configure(): void
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'only add assets with this IDs (comma-separated) to the update task queue'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only add assets in this folder (ID) to the update task queue'
            )
            ->addOption(
                'path-pattern',
                null,
                InputOption::VALUE_OPTIONAL,
                'only add assets matching the given regex pattern (path + filename), example:  ^/Sample.*urban.jpg$'
            )
            ->addOption(
                'retry-failed',
                'f',
                InputOption::VALUE_NONE,
                'retry assets that previously failed to be processed'
            )
            ->addOption(
                'missing-metadata',
                'm',
                InputOption::VALUE_NONE,
                'only add assets that are missing meta-data (embedded meta-data, image dimensions, video duration, ' .
                'page count), e.g. to backfill assets that were uploaded before the update task queue was ' .
                'processed. Assets that previously failed to be processed are only included if --retry-failed is ' .
                'used as well'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            [$conditions, $conditionVariables] = $this->buildConditions($input);
        } catch (InvalidOptionException $e) {
            $this->writeError($e->getMessage());

            return 1;
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $list->setOrderKey('id');
        $list->setOrder('ASC');
        $list->setLimit(self::ASSETS_PER_LOOP);

        $this->output->writeln(sprintf('Found %d asset(s) to add to the update task queue ...', $list->getTotalCount()));

        // the update task writes the very custom settings that are filtered on here, so the result set can shrink
        // while the queue is being consumed - paginate by ID instead of by offset, which would skip assets
        $lastId = 0;
        $count = 0;

        do {
            $list->setCondition(
                implode(' AND ', [...$conditions, 'id > ?']),
                [...$conditionVariables, $lastId]
            );

            $assets = $list->load();
            foreach ($assets as $asset) {
                $lastId = (int) $asset->getId();
                $this->output->writeln(
                    sprintf('Adding asset %s (%s) to the update task queue', $asset->getId(), $asset->getRealFullPath()),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $asset->triggerUpdateTask();
                $count++;
            }

            Pimcore::collectGarbage();
        } while (count($assets) === self::ASSETS_PER_LOOP);

        $this->output->writeln(sprintf('Added %d asset(s) to the update task queue', $count));

        return 0;
    }

    /**
     * @return array{0: string[], 1: array<int, mixed>}
     */
    protected function buildConditions(InputInterface $input): array
    {
        $conditionVariables = [];

        $conditions = [
            "`type` IN ('" . implode("','", $this->types) . "')",
        ];

        if ($input->getOption('parent')) {
            $parent = Asset::getById((int) $input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . Helper::escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                throw new InvalidOptionException($input->getOption('parent') . ' is not a valid asset folder ID!');
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        if ($regex = $input->getOption('path-pattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }

        $retryFailed = (bool) $input->getOption('retry-failed');
        $missingMetadata = (bool) $input->getOption('missing-metadata');

        if ($retryFailed && $missingMetadata) {
            // everything that still needs processing: never processed before, or failed while doing so
            $conditions[] = sprintf(
                '(%s OR %s)',
                $this->buildMissingMetadataCondition(),
                $this->buildProcessingFailedCondition(true)
            );
        } elseif ($retryFailed) {
            $conditions[] = $this->buildProcessingFailedCondition(true);
        } elseif ($missingMetadata) {
            $conditions[] = $this->buildMissingMetadataCondition();
            // assets that are flagged as failed are not missing their meta-data because they were never
            // processed, but because processing them did not work - they'd just fail again on every run
            $conditions[] = $this->buildProcessingFailedCondition(false);
        }

        return [$conditions, $conditionVariables];
    }

    private function buildMissingMetadataCondition(): string
    {
        $typeConditions = [];

        foreach (self::METADATA_CUSTOM_SETTINGS as $type => $customSettings) {
            if ($type === 'document' && !$this->isPageCountProcessingEnabled()) {
                // without page count processing there's no meta-data written for documents at all
                continue;
            }

            $missingCustomSettings = [];
            foreach ($customSettings as $customSetting) {
                $missingCustomSettings[] = $this->buildMissingCustomSettingCondition($customSetting);
            }

            $typeConditions[] = sprintf(
                "(`type` = '%s' AND (%s))",
                $type,
                implode(' OR ', $missingCustomSettings)
            );
        }

        return '(' . implode(' OR ', $typeConditions) . ')';
    }

    private function buildMissingCustomSettingCondition(string $customSetting): string
    {
        return sprintf('JSON_EXTRACT(customSettings, \'$."%s"\') IS NULL', $customSetting);
    }

    /**
     * The custom setting is only ever set to `true` or removed again, so a missing value means "not failed".
     * `JSON_UNQUOTE()` is used instead of comparing to a JSON boolean, as MySQL renders JSON columns with a
     * space after the colon (`{"key": true}`) while MariaDB stores them verbatim, which makes a plain LIKE
     * on the serialized column unreliable.
     */
    private function buildProcessingFailedCondition(bool $failed): string
    {
        return sprintf(
            'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(customSettings, \'$."%s"\')), \'false\') %s \'true\'',
            Asset::CUSTOM_SETTING_PROCESSING_FAILED,
            $failed ? '=' : '<>'
        );
    }

    protected function isPageCountProcessingEnabled(): bool
    {
        return (bool) (Config::getSystemConfiguration('assets')['document']['process_page_count'] ?? false);
    }
}
