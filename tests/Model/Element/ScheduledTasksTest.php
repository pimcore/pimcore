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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Concrete\Dao as ConcreteDao;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Page\Dao as PageDao;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Schedule\Task;
use Pimcore\Tests\Support\Helper\Element\DeleteAllTasksCountingDaoInterface;
use Pimcore\Tests\Support\Helper\Element\DeleteAllTasksCountingDaoTrait;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Scheduled tasks are handled by the same traits for assets, documents and data objects, so every
 * behaviour is verified for all three element types.
 *
 * @group model.element.scheduledtasks
 */
class ScheduledTasksTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public static function elementTypeProvider(): array
    {
        return [
            'asset' => ['asset'],
            'document' => ['document'],
            'object' => ['object'],
        ];
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testSaveWithoutScheduledTasksDoesNotDeleteFromScheduleTasks(string $type): void
    {
        $element = $this->createFreshElement($type);
        $dao = $this->attachDeleteCountingDao($element);

        $element->save();

        $this->assertSame(0, $dao->getDeleteAllTasksCalls());
        $this->assertSame(0, $this->countTasks($element));
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testScheduledTasksAreStoredOnSave(string $type): void
    {
        $element = $this->createFreshElement($type);
        $element->setScheduledTasks([$this->createTask()]);
        $element->save();

        $this->assertSame(1, $this->countTasks($element));

        $tasks = $this->reload($element)->getScheduledTasks();
        $this->assertCount(1, $tasks);
        $this->assertSame($element->getId(), $tasks[0]->getCid());
        $this->assertSame($type, $tasks[0]->getCtype());
        $this->assertSame('publish', $tasks[0]->getAction());
        $this->assertTrue($tasks[0]->getActive());
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testExistingScheduledTasksArePreservedOnSaveWithoutChanges(string $type): void
    {
        $element = $this->createFreshElement($type);
        $element->setScheduledTasks([$this->createTask()]);
        $element->save();
        $taskId = $element->getScheduledTasks()[0]->getId();

        $fresh = $this->reload($element);
        $dao = $this->attachDeleteCountingDao($fresh);
        $fresh->save();

        $this->assertSame(1, $dao->getDeleteAllTasksCalls());
        $this->assertSame(1, $this->countTasks($element));
        $this->assertSame($taskId, $this->reload($element)->getScheduledTasks()[0]->getId());
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testExplicitlyEmptyScheduledTasksRemoveExistingTasks(string $type): void
    {
        $element = $this->createFreshElement($type);
        $element->setScheduledTasks([$this->createTask()]);
        $element->save();
        $this->assertSame(1, $this->countTasks($element));

        $fresh = $this->reload($element);
        $dao = $this->attachDeleteCountingDao($fresh);
        $fresh->setScheduledTasks([]);
        $fresh->save();

        $this->assertSame(1, $dao->getDeleteAllTasksCalls());
        $this->assertSame(0, $this->countTasks($element));
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testReplacingScheduledTasksKeepsOnlyTheNewOnes(string $type): void
    {
        $element = $this->createFreshElement($type);
        $element->setScheduledTasks([$this->createTask(), $this->createTask('unpublish')]);
        $element->save();
        $this->assertSame(2, $this->countTasks($element));

        $fresh = $this->reload($element);
        $fresh->setScheduledTasks([$this->createTask('delete')]);
        $fresh->save();

        $tasks = $this->reload($element)->getScheduledTasks();
        $this->assertCount(1, $tasks);
        $this->assertSame('delete', $tasks[0]->getAction());
        $this->assertSame(1, $this->countTasks($element));
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testRemovingScheduledTasksDoesNotTouchOtherElementsOfTheSameType(string $type): void
    {
        $first = $this->createFreshElement($type);
        $first->setScheduledTasks([$this->createTask()]);
        $first->save();

        $second = $this->createFreshElement($type);
        $second->setScheduledTasks([$this->createTask()]);
        $second->save();

        $fresh = $this->reload($second);
        $fresh->setScheduledTasks([]);
        $fresh->save();

        $this->assertSame(1, $this->countTasks($first));
        $this->assertSame(0, $this->countTasks($second));
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testDeletingAnElementRemovesOnlyItsScheduledTasks(string $type): void
    {
        $first = $this->createFreshElement($type);
        $first->setScheduledTasks([$this->createTask()]);
        $first->save();

        $second = $this->createFreshElement($type);
        $second->setScheduledTasks([$this->createTask()]);
        $second->save();

        $second->delete();

        $this->assertSame(1, $this->countTasks($first));
        $this->assertSame(0, $this->countTasks($second));
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

    private function createFreshElement(string $type): Asset|Page|Concrete
    {
        $element = match ($type) {
            'asset' => TestHelper::createImageAsset(),
            'document' => TestHelper::createEmptyDocumentPage(),
            'object' => TestHelper::createEmptyObject(),
        };

        return $this->reload($element);
    }

    /**
     * Loads the element again from the database, bypassing the runtime cache
     */
    private function reload(Asset|Page|Concrete $element): Asset|Page|Concrete
    {
        $fresh = match (true) {
            $element instanceof Asset => Asset::getById($element->getId(), ['force' => true]),
            $element instanceof Page => Document::getById($element->getId(), ['force' => true]),
            $element instanceof Concrete => DataObject::getById($element->getId(), ['force' => true]),
        };
        $this->assertNotNull($fresh);

        return $fresh;
    }

    private function createTask(string $action = 'publish'): Task
    {
        return Task::create([
            'date' => time() + 3600,
            'action' => $action,
            'active' => true,
        ]);
    }

    private function countTasks(Asset|Page|Concrete $element): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM schedule_tasks WHERE cid = ? AND ctype = ?',
            [$element->getId(), Service::getElementType($element)]
        );
    }

    /**
     * Replaces the element's DAO with one that counts the calls to deleteAllTasks()
     */
    private function attachDeleteCountingDao(Asset|Page|Concrete $element): DeleteAllTasksCountingDaoInterface
    {
        $dao = match (true) {
            $element instanceof Asset => new class () extends AssetDao implements DeleteAllTasksCountingDaoInterface {
                use DeleteAllTasksCountingDaoTrait;
            },
            $element instanceof Page => new class () extends PageDao implements DeleteAllTasksCountingDaoInterface {
                use DeleteAllTasksCountingDaoTrait;
            },
            $element instanceof Concrete => new class () extends ConcreteDao implements
                DeleteAllTasksCountingDaoInterface {
                use DeleteAllTasksCountingDaoTrait;
            },
        };
        $dao->setModel($element);
        $dao->configure();
        if (method_exists($dao, 'init')) {
            $dao->init();
        }
        $element->setDao($dao);

        return $dao;
    }
}
