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

namespace Pimcore\Tests\Model\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Maintenance\Tasks\ScheduledTasksTask;
use Pimcore\Model\Document;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;

/**
 * @group model.maintenance.scheduledtaskstask
 */
class ScheduledTasksTaskTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testTaskIsRemovedAndSkippedOnceItsUserIsDeleted(): void
    {
        $user = $this->createUser();
        $document = TestHelper::createEmptyDocumentPage('scheduled-tasks-', true, true);
        $this->assertTrue($document->isPublished(), 'precondition: the document starts out published');

        $task = $this->scheduleUnpublish($document, $user->getId());
        $user->delete();

        $this->executeScheduledTasks();

        $reloaded = Document::getById($document->getId(), ['force' => true]);
        $this->assertTrue($reloaded->isPublished(), 'a task whose user was deleted must not execute');
        $this->assertFalse(
            (bool) Db::get()->fetchOne('SELECT id FROM schedule_tasks WHERE id = ?', [$task->getId()]),
            'deleting the user must remove its scheduled tasks so they cannot linger as dangling references'
        );

        $reloaded->delete();
    }

    public function testTaskWithNonExistentUserIdIsSkippedWithoutCleanup(): void
    {
        // Covers a task that became dangling by some other means (e.g. it predates the
        // Dao cleanup and was never removed) to prove the maintenance job itself refuses
        // to run it, rather than relying solely on cleanup at user-deletion time.
        $document = TestHelper::createEmptyDocumentPage('scheduled-tasks-', true, true);
        $this->assertTrue($document->isPublished(), 'precondition: the document starts out published');

        $task = $this->scheduleUnpublish($document, $this->findNonExistentUserId());

        $this->executeScheduledTasks();

        $reloaded = Document::getById($document->getId(), ['force' => true]);
        $this->assertTrue($reloaded->isPublished(), 'a task referencing a non-existent user id must not execute');
        $this->assertFalse(
            (bool) Db::get()->fetchOne('SELECT active FROM schedule_tasks WHERE id = ?', [$task->getId()]),
            'the dangling task must be deactivated so it is not retried indefinitely'
        );

        $reloaded->delete();
    }

    public function testTaskForExistingUserStillExecutes(): void
    {
        $user = $this->createUser();
        $document = TestHelper::createEmptyDocumentPage('scheduled-tasks-', true, true);
        $this->assertTrue($document->isPublished(), 'precondition: the document starts out published');

        $this->scheduleUnpublish($document, $user->getId());

        $this->executeScheduledTasks();

        $reloaded = Document::getById($document->getId(), ['force' => true]);
        $this->assertFalse(
            $reloaded->isPublished(),
            'a task whose user still exists and is permitted must still execute'
        );

        $reloaded->delete();
        $user->delete();
    }

    private function scheduleUnpublish(Document $document, int $userId): Task
    {
        $task = new Task();
        $task->setCid($document->getId());
        $task->setCtype('document');
        $task->setDate(time() - 3600);
        $task->setAction('unpublish');
        $task->setActive(true);
        $task->setUserId($userId);
        $task->save();

        return $task;
    }

    private function executeScheduledTasks(): void
    {
        (new ScheduledTasksTask(new NullLogger()))->execute();
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setAdmin(true);
        $user->setName('scheduled-tasks-test-' . uniqid());
        $user->save();

        return $user;
    }

    private function findNonExistentUserId(): int
    {
        $maxId = (int) Db::get()->fetchOne('SELECT MAX(id) FROM users');

        return $maxId + 100000;
    }
}
