<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Unit\Notification\Service;

use Pimcore\Model\Notification\Service\NotificationService;
use Pimcore\Model\User;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Util\TestHelper;

class NotificationServiceTest extends TestCase
{
    /** @var NotificationService $notificationService */
    protected $notificationService;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->notificationService = \Pimcore::getContainer()->get(NotificationService::class);
    }

    /**
     * @inheritDoc
     */
    public function _after()
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

    public function testSendToNoExistUser()
    {
        $user = 100;

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf('No user found with the ID %d', $user));

        $this->notificationService->sendToUser(
            $user,
            100,
            'Test title',
            'Test message'
        );
    }

    public function testSendToNoExistGroup()
    {
        $group = 100;

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf('No group found with the ID %d', $group));

        $this->notificationService->sendToGroup(
            $group,
            100,
            'Test title',
            'Test message'
        );
    }

    public function testSendToUser()
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

    public function testSendToUserWithElement()
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

    public function testSendToGroup()
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
