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

namespace Pimcore\Tests\Model\Maintenance;

use Pimcore\Maintenance\Tasks\ScheduledTasksTask;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;

/**
 * Regression tests for GHSA-mfr4-5hr5-jqvg: a publish-version task must only ever apply a
 * version that actually belongs to the task's own element.
 */
class ScheduledTasksTaskTest extends ModelTestCase
{
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->createAdminUser();
    }

    public function testPublishVersionTaskIgnoresAVersionBelongingToAnotherElement(): void
    {
        $elementA = TestHelper::createEmptyDocumentPage('element-a-', true, true);
        $elementA->setTitle('element A original title');
        $elementA->save();

        $elementB = TestHelper::createEmptyDocumentPage('element-b-', true, true);
        $elementB->setTitle('element B original title');
        $elementB->save();

        $elementB->setTitle('element B modified title');
        $versionOfB = $elementB->saveVersion();
        $this->assertNotNull($versionOfB, 'expected a version to be created on element B');

        $elementB->setTitle('element B original title');
        $elementB->save();

        // task targets element A, but the referenced version belongs to element B
        $this->createPublishVersionTask($elementA->getId(), 'document', $versionOfB->getId());

        (new ScheduledTasksTask(new NullLogger()))->execute();

        $reloadedA = Page::getById($elementA->getId(), ['force' => true]);
        $reloadedB = Page::getById($elementB->getId(), ['force' => true]);

        $this->assertSame(
            'element A original title',
            $reloadedA->getTitle(),
            'a task must not apply a version that belongs to a different element'
        );
        $this->assertSame(
            'element B original title',
            $reloadedB->getTitle(),
            'the element the version actually belongs to must remain untouched'
        );
    }

    public function testPublishVersionTaskStillPublishesItsOwnVersion(): void
    {
        $document = TestHelper::createEmptyDocumentPage('own-', true, true);
        $document->setTitle('v1');
        $document->save();

        $document->setPublished(false);
        $document->setTitle('v2');
        $ownVersion = $document->saveVersion();
        $this->assertNotNull($ownVersion, 'expected a version to be created');

        $this->createPublishVersionTask($document->getId(), 'document', $ownVersion->getId());

        (new ScheduledTasksTask(new NullLogger()))->execute();

        $reloaded = Page::getById($document->getId(), ['force' => true]);

        $this->assertSame(
            'v2',
            $reloaded->getTitle(),
            'a legitimate publish-version task on its own element must still apply the version data'
        );
        $this->assertTrue(
            $reloaded->isPublished(),
            'a legitimate publish-version task must still publish the document'
        );
    }

    private function createPublishVersionTask(int $cid, string $ctype, int $versionId): Task
    {
        $task = new Task([
            'cid' => $cid,
            'ctype' => $ctype,
            'action' => 'publish-version',
            'version' => $versionId,
            'date' => time() - 10,
            'active' => true,
            'userId' => $this->adminUser->getId(),
        ]);
        $task->save();

        return $task;
    }

    private function createAdminUser(): User
    {
        if (!$user = User::getByName('scheduled-tasks-test-admin')) {
            $user = new User();
            $user->setName('scheduled-tasks-test-admin');
            $user->setAdmin(true);
            $user->save();
        }

        return $user;
    }
}
