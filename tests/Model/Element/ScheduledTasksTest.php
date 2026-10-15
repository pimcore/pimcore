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

namespace Pimcore\Tests\Model\Element;

use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Dao as AssetDao;
use Pimcore\Model\Schedule\Task;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group model.element.scheduledtasks
 */
class ScheduledTasksTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function testSaveWithoutScheduledTasksDoesNotDeleteFromScheduleTasks(): void
    {
        $asset = $this->loadFreshAsset(TestHelper::createImageAsset()->getId());
        $dao = $this->attachDeleteCountingDao($asset);

        $asset->save();

        $this->assertSame(0, $dao->deleteAllTasksCalls);
        $this->assertSame(0, $this->countTasks($asset));
    }

    public function testScheduledTasksAreStoredOnSave(): void
    {
        $asset = $this->loadFreshAsset(TestHelper::createImageAsset()->getId());
        $asset->setScheduledTasks([$this->createTask()]);
        $asset->save();

        $this->assertSame(1, $this->countTasks($asset));

        $fresh = $this->loadFreshAsset($asset->getId());
        $tasks = $fresh->getScheduledTasks();
        $this->assertCount(1, $tasks);
        $this->assertSame($asset->getId(), $tasks[0]->getCid());
        $this->assertSame('asset', $tasks[0]->getCtype());
        $this->assertSame('publish', $tasks[0]->getAction());
    }

    public function testExistingScheduledTasksArePreservedOnSaveWithoutChanges(): void
    {
        $asset = $this->loadFreshAsset(TestHelper::createImageAsset()->getId());
        $asset->setScheduledTasks([$this->createTask()]);
        $asset->save();
        $taskId = $asset->getScheduledTasks()[0]->getId();

        $fresh = $this->loadFreshAsset($asset->getId());
        $dao = $this->attachDeleteCountingDao($fresh);
        $fresh->save();

        $this->assertSame(1, $dao->deleteAllTasksCalls);
        $this->assertSame(1, $this->countTasks($asset));
        $this->assertSame($taskId, $this->loadFreshAsset($asset->getId())->getScheduledTasks()[0]->getId());
    }

    public function testExplicitlyEmptyScheduledTasksRemoveExistingTasks(): void
    {
        $asset = $this->loadFreshAsset(TestHelper::createImageAsset()->getId());
        $asset->setScheduledTasks([$this->createTask()]);
        $asset->save();
        $this->assertSame(1, $this->countTasks($asset));

        $fresh = $this->loadFreshAsset($asset->getId());
        $dao = $this->attachDeleteCountingDao($fresh);
        $fresh->setScheduledTasks([]);
        $fresh->save();

        $this->assertSame(1, $dao->deleteAllTasksCalls);
        $this->assertSame(0, $this->countTasks($asset));
    }

    public function testScheduleTasksTableHasCompositeCidCtypeIndex(): void
    {
        $indexes = Db::get()->fetchAllAssociative('SHOW INDEX FROM schedule_tasks');

        $columnsByIndex = [];
        foreach ($indexes as $index) {
            $columnsByIndex[$index['Key_name']][(int) $index['Seq_in_index']] = $index['Column_name'];
        }

        $this->assertArrayHasKey('cid_ctype', $columnsByIndex);
        $this->assertSame([1 => 'cid', 2 => 'ctype'], $columnsByIndex['cid_ctype']);
        $this->assertArrayNotHasKey('cid', $columnsByIndex);
    }

    private function loadFreshAsset(int $id): Asset
    {
        $asset = Asset::getById($id, ['force' => true]);
        $this->assertInstanceOf(Asset::class, $asset);

        return $asset;
    }

    private function createTask(): Task
    {
        return Task::create([
            'date' => time() + 3600,
            'action' => 'publish',
            'active' => true,
        ]);
    }

    private function countTasks(Asset $asset): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM schedule_tasks WHERE cid = ? AND ctype = ?',
            [$asset->getId(), 'asset']
        );
    }

    /**
     * Replaces the asset's DAO with one that counts the calls to deleteAllTasks()
     */
    private function attachDeleteCountingDao(Asset $asset): AssetDao
    {
        $dao = new class () extends AssetDao {
            public int $deleteAllTasksCalls = 0;

            public function deleteAllTasks(array $ignoreIds = []): void
            {
                $this->deleteAllTasksCalls++;
                parent::deleteAllTasks($ignoreIds);
            }
        };
        $dao->setModel($asset);
        $dao->configure();
        $asset->setDao($dao);

        return $dao;
    }
}
