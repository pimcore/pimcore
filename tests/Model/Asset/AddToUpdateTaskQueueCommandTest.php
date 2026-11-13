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

use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\Asset\AddToUpdateTaskQueueCommand;
use Pimcore\Db;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

/**
 * `pimcore:assets:add-to-update-task-queue --retry-failed` must select the assets flagged as failed, independent of
 * how the database renders the JSON `customSettings` column (MySQL 8 normalizes it to `"key": true`).
 *
 * @group model.asset.asset
 */
final class AddToUpdateTaskQueueCommandTest extends ModelTestCase
{
    private const QUEUE_NAME = 'pimcore_asset_update';

    public function testRetryFailedQueuesOnlyFailedAssets(): void
    {
        $failedAsset = TestHelper::createImageAsset();
        $failedAsset->setCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED, true);
        $failedAsset->getDao()->updateCustomSettings();

        $okAsset = TestHelper::createImageAsset();

        $this->clearQueue();

        $tester = new CommandTester(new AddToUpdateTaskQueueCommand());
        $tester->execute(['--retry-failed' => true]);

        $tester->assertCommandIsSuccessful();
        $queuedIds = $this->fetchQueuedAssetIds();
        $this->assertContains($failedAsset->getId(), $queuedIds);
        $this->assertNotContains($okAsset->getId(), $queuedIds);
    }

    /**
     * The command loads the matching assets in pages of 10, continuing after the last ID it saw. With more matching
     * assets than that, every one of them must be dispatched exactly once, and the reported count must only include
     * the assets that were actually dispatched.
     */
    public function testQueuesEveryAssetOnceAcrossPagesAndCountsOnlyDispatches(): void
    {
        $ids = [];
        for ($i = 0; $i < 21; $i++) {
            $ids[] = TestHelper::createImageAsset()->getId();
        }

        // the asset's queue lock is held, as while another process adds it - the command must not dispatch or count it
        $lockedId = $ids[12];
        $lock = Pimcore::getContainer()->get(LockFactory::class)
            ->createLock(Asset::getById($lockedId)->getUpdateQueueLockId());
        $this->assertTrue($lock->acquire());

        $this->clearQueue();

        $tester = new CommandTester(new AddToUpdateTaskQueueCommand());
        $tester->execute(['--id' => $ids]);
        $lock->release();

        $tester->assertCommandIsSuccessful();
        $queuedIds = $this->fetchQueuedAssetIds();
        $expectedIds = array_values(array_diff($ids, [$lockedId]));
        sort($queuedIds);
        $this->assertSame($expectedIds, $queuedIds);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Found 21 asset(s)', $display);
        $this->assertStringContainsString('Added 20 asset(s)', $display);
        $this->assertStringContainsString('Skipped 1 asset(s)', $display);
    }

    private function clearQueue(): void
    {
        $db = Db::get();
        if ($db->createSchemaManager()->tablesExist(['messenger_messages'])) {
            $db->delete('messenger_messages', ['queue_name' => self::QUEUE_NAME]);
        }
    }

    /**
     * @return int[]
     */
    private function fetchQueuedAssetIds(): array
    {
        $bodies = Db::get()->fetchFirstColumn(
            'SELECT body FROM messenger_messages WHERE queue_name = ?',
            [self::QUEUE_NAME]
        );

        $serializer = new PhpSerializer();
        $ids = [];
        foreach ($bodies as $body) {
            $message = $serializer->decode(['body' => $body])->getMessage();
            if ($message instanceof AssetUpdateTasksMessage) {
                $ids[] = $message->getId();
            }
        }

        return $ids;
    }
}
