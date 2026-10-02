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

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Bundle\CoreBundle\Command\Asset\AddToUpdateTaskQueueCommand;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Serialize;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Runs the conditions built by `pimcore:assets:add-to-update-task-queue` against the database, so that the
 * differences between MySQL and MariaDB in storing and rendering `json` columns are actually covered:
 * MariaDB keeps the encoded string verbatim, MySQL normalizes it (`{"key": true}`).
 *
 * @group model.asset.asset
 */
class UpdateTaskQueueConditionTest extends ModelTestCase
{
    /**
     * @param array<string, mixed>|null $customSettings null writes a NULL column, as for a never saved custom setting
     */
    private function writeCustomSettings(Asset $asset, ?array $customSettings): void
    {
        Db::get()->update(
            'assets',
            ['customSettings' => $customSettings === null ? null : Serialize::toJson($customSettings)],
            ['id' => $asset->getId()]
        );
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return int[] the IDs the command would add to the update task queue
     */
    private function matchingIds(array $input): array
    {
        $command = new class extends AddToUpdateTaskQueueCommand {
            public function buildConditions(InputInterface $input): array
            {
                return parent::buildConditions($input);
            }

            protected function isPageCountProcessingEnabled(): bool
            {
                return true;
            }
        };

        [$conditions, $conditionVariables] = $command->buildConditions(
            new ArrayInput($input, $command->getDefinition())
        );

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $list->setOrderKey('id');
        $list->setOrder('ASC');

        return array_map(static fn (Asset $asset): int => (int) $asset->getId(), $list->load());
    }

    public function testRetryFailedMatchesTheProcessingFailedFlag(): void
    {
        $failed = TestHelper::createImageAsset();
        $this->writeCustomSettings($failed, [Asset::CUSTOM_SETTING_PROCESSING_FAILED => true]);

        $processed = TestHelper::createImageAsset();
        $this->writeCustomSettings($processed, [
            'embeddedMetaDataExtracted' => true,
            'imageDimensionsCalculated' => true,
        ]);

        $matching = $this->matchingIds(['--retry-failed' => true]);

        $this->assertContains((int) $failed->getId(), $matching);
        $this->assertNotContains((int) $processed->getId(), $matching);
    }

    public function testMissingMetadataMatchesAssetsThatWereNeverProcessed(): void
    {
        $neverProcessed = TestHelper::createImageAsset();
        $this->writeCustomSettings($neverProcessed, null);

        $withoutDimensions = TestHelper::createImageAsset();
        $this->writeCustomSettings($withoutDimensions, ['embeddedMetaDataExtracted' => true]);

        $processed = TestHelper::createImageAsset();
        $this->writeCustomSettings($processed, [
            'embeddedMetaDataExtracted' => true,
            'imageDimensionsCalculated' => true,
        ]);

        $matching = $this->matchingIds(['--missing-metadata' => true]);

        $this->assertContains((int) $neverProcessed->getId(), $matching);
        $this->assertContains((int) $withoutDimensions->getId(), $matching);
        $this->assertNotContains((int) $processed->getId(), $matching);
    }

    public function testMissingMetadataMatchesPerAssetType(): void
    {
        $video = TestHelper::createVideoAsset();
        $this->writeCustomSettings($video, [
            'embeddedMetaDataExtracted' => true,
            'duration' => 5.0,
            'videoWidth' => 640,
            // videoHeight is missing
        ]);

        $document = TestHelper::createDocumentAsset();
        $this->writeCustomSettings($document, ['document_page_count' => 3]);

        $matching = $this->matchingIds(['--missing-metadata' => true]);

        $this->assertContains((int) $video->getId(), $matching);
        $this->assertNotContains((int) $document->getId(), $matching);
    }

    public function testMissingMetadataSkipsFailedAssetsUnlessTheyAreRetried(): void
    {
        // a failed video has its duration and dimensions removed again, so it looks like it was never processed
        $failedVideo = TestHelper::createVideoAsset();
        $this->writeCustomSettings($failedVideo, [
            'embeddedMetaDataExtracted' => true,
            Asset::CUSTOM_SETTING_PROCESSING_FAILED => true,
        ]);

        // a failed document keeps its page count, so it does not look like it is missing meta-data at all
        $failedDocument = TestHelper::createDocumentAsset();
        $this->writeCustomSettings($failedDocument, [
            'document_page_count' => 'failed',
            Asset::CUSTOM_SETTING_PROCESSING_FAILED => true,
        ]);

        $missingOnly = $this->matchingIds(['--missing-metadata' => true]);
        $this->assertNotContains((int) $failedVideo->getId(), $missingOnly);
        $this->assertNotContains((int) $failedDocument->getId(), $missingOnly);

        $missingAndFailed = $this->matchingIds(['--missing-metadata' => true, '--retry-failed' => true]);
        $this->assertContains((int) $failedVideo->getId(), $missingAndFailed);
        $this->assertContains((int) $failedDocument->getId(), $missingAndFailed);
    }

    public function testConditionsCanBeContinuedAfterAnId(): void
    {
        $first = TestHelper::createImageAsset();
        $this->writeCustomSettings($first, null);

        $second = TestHelper::createImageAsset();
        $this->writeCustomSettings($second, null);

        $list = new Asset\Listing();
        $list->setCondition(
            '`type` = \'image\' AND JSON_EXTRACT(customSettings, \'$."embeddedMetaDataExtracted"\') IS NULL'
            . ' AND id > ?',
            [$first->getId()]
        );

        $matching = array_map(static fn (Asset $asset): int => (int) $asset->getId(), $list->load());

        $this->assertNotContains((int) $first->getId(), $matching);
        $this->assertContains((int) $second->getId(), $matching);
    }
}
