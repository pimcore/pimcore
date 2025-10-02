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
use Pimcore\Console\AbstractCommand;
use Pimcore\Db\Helper;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:add-to-update-task-queue',
    description: 'Adds assets to update task queue for re-processing (previews, meta-data, ...)',
)]
class AddToUpdateTaskQueueCommand extends AbstractCommand
{
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
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');

                return 1;
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        if ($regex = $input->getOption('path-pattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }

        if ($input->getOption('retry-failed')) {
            $conditions[] = sprintf(
                "customSettings LIKE '%%%s%%'",
                '"' . Asset::CUSTOM_SETTING_PROCESSING_FAILED . '":true'
            );
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $total = $list->getTotalCount();
        $perLoop = 10;

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);
            $assets = $list->load();
            foreach ($assets as $asset) {
                $asset->triggerUpdateTask();
            }

            Pimcore::collectGarbage();
        }

        return 0;
    }
}
