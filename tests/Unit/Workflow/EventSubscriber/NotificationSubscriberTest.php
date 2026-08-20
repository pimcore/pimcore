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

namespace Pimcore\Tests\Unit\Workflow\EventSubscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pimcore\Event\Workflow\GlobalActionEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Notification\NotificationEmailService;
use Pimcore\Workflow\Notification\PimcoreNotificationService;
use stdClass;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NotificationSubscriberTest extends TestCase
{
    private NotificationEmailService&MockObject $mailService;

    private PimcoreNotificationService&MockObject $pimcoreNotificationService;

    private NotificationSubscriber $subscriber;

    public function testItSubscribesToThePostGlobalActionEvent(): void
    {
        $this->assertArrayHasKey(
            WorkflowEvents::POST_GLOBAL_ACTION,
            NotificationSubscriber::getSubscribedEvents()
        );
    }

    public function testGlobalActionSendsNotificationsToTheConfiguredChannels(): void
    {
        $this->createSubscriber();

        $this->mailService
            ->expects($this->once())
            ->method('sendWorkflowEmailNotification')
            ->with(
                ['admin'],
                ['projectmanagers'],
                $this->anything(),
                'Product',
                $this->anything(),
                $this->isInstanceOf(GlobalAction::class),
                NotificationSubscriber::MAIL_TYPE_TEMPLATE,
                'some/path.html.twig'
            );

        $this->pimcoreNotificationService
            ->expects($this->once())
            ->method('sendPimcoreNotification')
            ->with(
                ['admin'],
                ['projectmanagers'],
                $this->anything(),
                'Product',
                $this->anything(),
                $this->isInstanceOf(GlobalAction::class)
            );

        $this->subscriber->onPostGlobalAction($this->createEvent([
            [
                'notifyUsers' => ['admin'],
                'notifyRoles' => ['projectmanagers'],
                'channelType' => [
                    NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL,
                    NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION,
                ],
                'mailType' => NotificationSubscriber::MAIL_TYPE_TEMPLATE,
                'mailPath' => 'some/path.html.twig',
            ],
        ]));
    }

    public function testGlobalActionWithoutNotificationSettingsDoesNotNotify(): void
    {
        $this->createSubscriber();
        $this->expectNoNotifications();

        $this->subscriber->onPostGlobalAction($this->createEvent([]));
    }

    public function testGlobalActionDoesNotNotifyWhenTheSubscriberIsDisabled(): void
    {
        $this->createSubscriber();
        $this->expectNoNotifications();

        $this->subscriber->setEnabled(false);

        $this->subscriber->onPostGlobalAction($this->createEvent([$this->mailNotificationSetting()]));
    }

    public function testGlobalActionDoesNotNotifyForNonElementSubjects(): void
    {
        $this->createSubscriber();
        $this->expectNoNotifications();

        $this->subscriber->onPostGlobalAction(
            $this->createEvent([$this->mailNotificationSetting()], new stdClass())
        );
    }

    private function createSubscriber(): void
    {
        $this->mailService = $this->createMock(NotificationEmailService::class);
        $this->pimcoreNotificationService = $this->createMock(PimcoreNotificationService::class);

        $this->subscriber = new NotificationSubscriber(
            $this->mailService,
            $this->pimcoreNotificationService,
            $this->createStub(TranslatorInterface::class),
            $this->createStub(ExpressionService::class),
            $this->createStub(Manager::class)
        );
    }

    private function expectNoNotifications(): void
    {
        $this->mailService->expects($this->never())->method('sendWorkflowEmailNotification');
        $this->pimcoreNotificationService->expects($this->never())->method('sendPimcoreNotification');
    }

    /**
     * @return array<string, mixed>
     */
    private function mailNotificationSetting(): array
    {
        return [
            'notifyUsers' => ['admin'],
            'channelType' => [NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL],
            'mailType' => NotificationSubscriber::MAIL_TYPE_TEMPLATE,
            'mailPath' => 'some/path.html.twig',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $notificationSettings
     */
    private function createEvent(array $notificationSettings, ?object $subject = null): GlobalActionEvent
    {
        if ($subject === null) {
            $subject = new Concrete();
            $subject->setClassName('Product');
        }

        $globalAction = new GlobalAction(
            'myGlobalAction',
            ['label' => 'My global action', 'notificationSettings' => $notificationSettings],
            $this->createStub(ExpressionService::class),
            'myWorkflow'
        );

        return new GlobalActionEvent($this->createStub(WorkflowInterface::class), $subject, $globalAction);
    }
}
