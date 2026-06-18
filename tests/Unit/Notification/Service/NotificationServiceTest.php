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

namespace Pimcore\Tests\Unit\Notification\Service;

use Pimcore;
use Pimcore\Db;
use Pimcore\Model\Notification\Service\NotificationService;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use UnexpectedValueException;

class NotificationServiceTest extends TestCase
{
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = Pimcore::getContainer()->get(NotificationService::class);
    }

    public function _after(): void
    {
        $user = User::getByName('notification-user');
        $group = User\Role::getByName('notification-group');

        if ($user instanceof User) {
            $this->notificationService->deleteAll($user->getId());
            $user->delete();
        }

        if ($group instanceof User\Role) {
            $group->delete();
        }
    }

    public function testSendToNoExistUser(): void
    {
        $user = 100;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf('No user found with the ID %d', $user));

        $this->notificationService->sendToUser(
            $user,
            100,
            'Test title',
            'Test message'
        );
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testSendToNoExistUserLeavesNoActiveTransaction(): void
    {
        try {
            $this->notificationService->sendToUser(
                100,
                100,
                'Test title',
                'Test message'
            );
            $this->fail('Expected UnexpectedValueException was not thrown');
        } catch (UnexpectedValueException) {
            // expected
        }

        $this->assertFalse(Db::getConnection()->isTransactionActive());
    }

    public function testFindAndMarkAsReadWithInvalidIdLeavesNoActiveTransaction(): void
    {
        try {
            $this->notificationService->findAndMarkAsRead(999999);
            $this->fail('Expected UnexpectedValueException was not thrown');
        } catch (UnexpectedValueException) {
            // expected
        }

        $this->assertFalse(Db::getConnection()->isTransactionActive());
    }

    public function testFindAndMarkAsReadWithWrongRecipientLeavesNoActiveTransaction(): void
    {
        $user = new User();
        $user->setName('notification-user')->save();

        $this->notificationService->sendToUser(
            $user->getId(),
            0,
            'Test title',
            'Test message'
        );

        $notifications = $this->notificationService->findAll(['recipient' => $user->getId()]);
        $notificationId = $notifications['data'][0]->getId();

        try {
            $this->notificationService->findAndMarkAsRead($notificationId, 999999);
            $this->fail('Expected AccessDeniedHttpException was not thrown');
        } catch (AccessDeniedHttpException) {
            // expected
        }

        $this->assertFalse(Db::getConnection()->isTransactionActive());
    }

    public function testDeleteWithInvalidIdLeavesNoActiveTransaction(): void
    {
        try {
            $this->notificationService->delete(999999);
            $this->fail('Expected UnexpectedValueException was not thrown');
        } catch (UnexpectedValueException) {
            // expected
        }

        $this->assertFalse(Db::getConnection()->isTransactionActive());
    }

    public function testSendToNoExistGroup(): void
    {
        $group = 100;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf('No group found with the ID %d', $group));

        $this->notificationService->sendToGroup(
            $group,
            100,
            'Test title',
            'Test message'
        );
    }

    public function testSendToUser(): void
    {
        $count = 2;
        $user = new User();

        $user
            ->setName('notification-user')
            ->save();

        for ($i = 0; $i < $count; $i++) {
            $this->notificationService->sendToUser(
                $user->getId(),
                0,
                'Test title',
                'Test message'
            );
        }

        $notifications = $this->notificationService->findAll(['recipient' => $user->getId()]);

        $this->equalTo($count, $notifications['total']);
    }

    public function testSendToUserWithElement(): void
    {
        $count = 2;
        $user = new User();

        $user
            ->setName('notification-user')
            ->save();

        $element = TestHelper::createEmptyObject();

        for ($i = 0; $i < $count; $i++) {
            $this->notificationService->sendToUser(
                $user->getId(),
                0,
                'Test title',
                'Test message',
                $element
            );
        }

        $notifications = $this->notificationService->findAll(['recipient' => $user->getId()]);

        $this->equalTo($count, $notifications['total']);
    }

    public function testSendToGroup(): void
    {
        $count = 3;
        $group = new User\Role();

        $group
            ->setName('notification-group')
            ->save();

        $user = new User();

        $user
            ->setName('notification-user')
            ->setRoles([$group->getId()])
            ->save();

        $this->notificationService->sendToGroup(
            $group->getId(),
            0,
            'Test title',
            'Test message'
        );

        $notifications = $this->notificationService->findAll(['recipient' => $user->getId()]);

        $this->equalTo($count, $notifications['total']);
    }
}
