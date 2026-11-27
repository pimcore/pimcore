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

namespace Pimcore\Tests\Unit\CoreBundle\Command;

use Pimcore\Bundle\CoreBundle\Command\Asset\AddToUpdateTaskQueueCommand;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;

class AddToUpdateTaskQueueCommandTest extends TestCase
{
    private const IMAGE_CONDITION = '(`type` = \'image\' AND ('
        . 'JSON_EXTRACT(customSettings, \'$."embeddedMetaDataExtracted"\') IS NULL'
        . ' OR JSON_EXTRACT(customSettings, \'$."imageDimensionsCalculated"\') IS NULL))';

    private const VIDEO_CONDITION = '(`type` = \'video\' AND ('
        . 'JSON_EXTRACT(customSettings, \'$."embeddedMetaDataExtracted"\') IS NULL'
        . ' OR JSON_EXTRACT(customSettings, \'$."duration"\') IS NULL'
        . ' OR JSON_EXTRACT(customSettings, \'$."videoWidth"\') IS NULL'
        . ' OR JSON_EXTRACT(customSettings, \'$."videoHeight"\') IS NULL))';

    private const DOCUMENT_CONDITION = '(`type` = \'document\' AND ('
        . 'JSON_EXTRACT(customSettings, \'$."document_page_count"\') IS NULL))';

    private const FAILED_CONDITION =
        'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(customSettings, \'$."pimcore-asset-processing-failed"\')), \'false\')'
        . ' = \'true\'';

    private const NOT_FAILED_CONDITION =
        'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(customSettings, \'$."pimcore-asset-processing-failed"\')), \'false\')'
        . ' <> \'true\'';

    /**
     * @return array{0: string[], 1: array<int, mixed>}
     */
    private function buildConditions(array $input, bool $pageCountProcessingEnabled = true): array
    {
        $command = new class($pageCountProcessingEnabled) extends AddToUpdateTaskQueueCommand {
            public function __construct(private readonly bool $pageCountProcessingEnabled)
            {
                parent::__construct();
            }

            public function buildConditions(InputInterface $input): array
            {
                return parent::buildConditions($input);
            }

            protected function isPageCountProcessingEnabled(): bool
            {
                return $this->pageCountProcessingEnabled;
            }
        };

        return $command->buildConditions(new ArrayInput($input, $command->getDefinition()));
    }

    public function testWithoutOptionsOnlyTypesAreFiltered(): void
    {
        [$conditions, $variables] = $this->buildConditions([]);

        $this->assertSame(["`type` IN ('image','video','document')"], $conditions);
        $this->assertSame([], $variables);
    }

    public function testMissingMetadataOptionAddsConditionForEveryType(): void
    {
        [$conditions, $variables] = $this->buildConditions(['--missing-metadata' => true]);

        $this->assertSame(
            [
                "`type` IN ('image','video','document')",
                '(' . self::IMAGE_CONDITION . ' OR ' . self::VIDEO_CONDITION . ' OR ' . self::DOCUMENT_CONDITION . ')',
                self::NOT_FAILED_CONDITION,
            ],
            $conditions
        );
        $this->assertSame([], $variables);
    }

    public function testMissingMetadataOptionSkipsDocumentsIfPageCountProcessingIsDisabled(): void
    {
        [$conditions] = $this->buildConditions(['--missing-metadata' => true], false);

        $this->assertSame(
            [
                "`type` IN ('image','video','document')",
                '(' . self::IMAGE_CONDITION . ' OR ' . self::VIDEO_CONDITION . ')',
                self::NOT_FAILED_CONDITION,
            ],
            $conditions
        );
    }

    public function testMissingMetadataOptionCanBeCombinedWithOtherFilters(): void
    {
        [$conditions, $variables] = $this->buildConditions([
            '--missing-metadata' => true,
            '--id' => ['3', '5'],
            '--path-pattern' => '^/Sample.*urban.jpg$',
        ]);

        $this->assertSame(
            [
                "`type` IN ('image','video','document')",
                'id in (3,5)',
                'CONCAT(`path`, filename) REGEXP ?',
                '(' . self::IMAGE_CONDITION . ' OR ' . self::VIDEO_CONDITION . ' OR ' . self::DOCUMENT_CONDITION . ')',
                self::NOT_FAILED_CONDITION,
            ],
            $conditions
        );
        $this->assertSame(['^/Sample.*urban.jpg$'], $variables);
    }

    public function testMissingMetadataOptionCombinedWithRetryFailedAlsoQueuesFailedAssets(): void
    {
        [$conditions] = $this->buildConditions([
            '--missing-metadata' => true,
            '--retry-failed' => true,
        ]);

        // the two are OR-ed: a failed asset is queued even if it does not look like it is missing meta-data,
        // which is the case for documents (`document_page_count` is set to `failed`) and images
        $this->assertSame(
            [
                "`type` IN ('image','video','document')",
                '((' . self::IMAGE_CONDITION . ' OR ' . self::VIDEO_CONDITION . ' OR ' . self::DOCUMENT_CONDITION . ')'
                . ' OR ' . self::FAILED_CONDITION . ')',
            ],
            $conditions
        );
    }

    public function testRetryFailedOptionMatchesTheFlagIndependentlyOfTheJsonSerialization(): void
    {
        [$conditions] = $this->buildConditions(['--retry-failed' => true]);

        $this->assertSame(
            [
                "`type` IN ('image','video','document')",
                self::FAILED_CONDITION,
            ],
            $conditions
        );
    }
}
