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
use Pimcore\Event\Workflow\WorkflowNotificationEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Notification\NotificationEmailService;
use Pimcore\Workflow\Notification\PimcoreNotificationService;
use Pimcore\Workflow\Transition as PimcoreTransition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Covers the WorkflowEvents::PRE_NOTIFICATION_SENDING event dispatched by the
 * NotificationSubscriber before the notifications configured on a transition are sent.
 */
class NotificationSubscriberTest extends TestCase
{
    private const WORKFLOW_NAME = 'test_wf';

    private const SUBJECT_TYPE = 'Product';

    private const CONFIGURED_USERS = ['configured_user'];

    private const CONFIGURED_ROLES = ['configured_role'];

    private NotificationEmailService&MockObject $mailService;

    private PimcoreNotificationService&MockObject $pimcoreNotificationService;

    private ExpressionService&MockObject $expressionService;

    private WorkflowInterface $workflow;

    private Concrete&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailService = $this->createMock(NotificationEmailService::class);
        $this->pimcoreNotificationService = $this->createMock(PimcoreNotificationService::class);
        $this->expressionService = $this->createMock(ExpressionService::class);

        $workflow = $this->createStub(WorkflowInterface::class);
        $workflow->method('getName')->willReturn(self::WORKFLOW_NAME);
        $this->workflow = $workflow;

        $this->subject = $this->createMock(Concrete::class);
        $this->subject->method('getClassName')->willReturn(self::SUBJECT_TYPE);
    }

    /**
     * Regression: with no listener registered, both channels must still receive exactly the
     * users and roles configured on the transition.
     */
    public function testRecipientsAreUnchangedWhenNoListenerIsRegistered(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting([
                NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL,
                NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION,
            ]),
        ]);

        $this->expectMail($transition, self::CONFIGURED_USERS, self::CONFIGURED_ROLES);
        $this->expectPimcoreNotification($transition, self::CONFIGURED_USERS, self::CONFIGURED_ROLES);

        $subscriber = $this->createSubscriber(new EventDispatcher());
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));
    }

    /**
     * A listener rewriting the recipients must affect the mail *and* the Pimcore notification
     * channel - the event is not mail-specific.
     */
    public function testListenerCanRewriteRecipientsForBothChannels(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting([
                NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL,
                NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION,
            ]),
        ]);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowEvents::PRE_NOTIFICATION_SENDING,
            static function (WorkflowNotificationEvent $event): void {
                $event->setUsers([...$event->getUsers(), 'owner']);
                $event->setRoles(['reviewer']);
            }
        );

        $this->expectMail($transition, ['configured_user', 'owner'], ['reviewer']);
        $this->expectPimcoreNotification($transition, ['configured_user', 'owner'], ['reviewer']);

        $subscriber = $this->createSubscriber($eventDispatcher);
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));
    }

    public function testEventCarriesTransitionWorkflowSubjectRecipientsAndMailSettings(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting(
                [NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION],
                NotificationSubscriber::MAIL_TYPE_DOCUMENT,
                '/emails/%_locale%/workflow'
            ),
        ]);

        /** @var WorkflowNotificationEvent[] $dispatched */
        $dispatched = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowEvents::PRE_NOTIFICATION_SENDING,
            static function (WorkflowNotificationEvent $event) use (&$dispatched): void {
                $dispatched[] = $event;
            }
        );

        $subscriber = $this->createSubscriber($eventDispatcher);
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));

        $this->assertCount(1, $dispatched);
        $event = $dispatched[0];
        $this->assertSame($transition, $event->getTransition());
        $this->assertSame($this->workflow, $event->getWorkflow());
        $this->assertSame($this->subject, $event->getSubject());
        $this->assertSame(self::CONFIGURED_USERS, $event->getUsers());
        $this->assertSame(self::CONFIGURED_ROLES, $event->getRoles());
        $this->assertSame(NotificationSubscriber::MAIL_TYPE_DOCUMENT, $event->getMailType());
        $this->assertSame(
            '/emails/%_locale%/workflow',
            $event->getMailPath(),
            'The mail settings are carried for context even when the mail channel is not configured.'
        );
    }

    /**
     * One event per notification setting, so a listener can decide per setting - and the
     * rewritten recipients of one setting must not leak into another.
     */
    public function testEventIsDispatchedOncePerNotificationSetting(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting(
                [NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL],
                NotificationSubscriber::MAIL_TYPE_TEMPLATE,
                '/mail/first'
            ),
            $this->createNotificationSetting(
                [NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION],
                NotificationSubscriber::MAIL_TYPE_TEMPLATE,
                '/mail/second'
            ),
        ]);

        $seenMailPaths = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowEvents::PRE_NOTIFICATION_SENDING,
            static function (WorkflowNotificationEvent $event) use (&$seenMailPaths): void {
                $seenMailPaths[] = $event->getMailPath();

                if ($event->getMailPath() === '/mail/first') {
                    $event->setUsers(['mail_only_user']);
                }
            }
        );

        $this->expectMail($transition, ['mail_only_user'], self::CONFIGURED_ROLES, '/mail/first');
        $this->expectPimcoreNotification($transition, self::CONFIGURED_USERS, self::CONFIGURED_ROLES);

        $subscriber = $this->createSubscriber($eventDispatcher);
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));

        $this->assertSame(['/mail/first', '/mail/second'], $seenMailPaths);
    }

    public function testEventIsNotDispatchedWhenConditionIsNotMet(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting(
                [
                    NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL,
                    NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION,
                ],
                condition: 'subject.getPublished()'
            ),
        ]);

        $this->expressionService
            ->expects($this->once())
            ->method('evaluateExpression')
            ->with($this->workflow, $this->subject, 'subject.getPublished()')
            ->willReturn(false);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowEvents::PRE_NOTIFICATION_SENDING,
            static function (): void {
                self::fail('The event must not be dispatched when the notification condition is not met.');
            }
        );

        $this->mailService->expects($this->never())->method('sendWorkflowEmailNotification');
        $this->pimcoreNotificationService->expects($this->never())->method('sendPimcoreNotification');

        $subscriber = $this->createSubscriber($eventDispatcher);
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));
    }

    public function testEventIsDispatchedWhenConditionIsMet(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting(
                [NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL],
                condition: 'subject.getPublished()'
            ),
        ]);

        $this->expressionService
            ->expects($this->once())
            ->method('evaluateExpression')
            ->with($this->workflow, $this->subject, 'subject.getPublished()')
            ->willReturn(true);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowEvents::PRE_NOTIFICATION_SENDING,
            static function (WorkflowNotificationEvent $event): void {
                $event->setRoles([]);
            }
        );

        $this->expectMail($transition, self::CONFIGURED_USERS, []);
        $this->pimcoreNotificationService->expects($this->never())->method('sendPimcoreNotification');

        $subscriber = $this->createSubscriber($eventDispatcher);
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));
    }

    public function testEventIsNotDispatchedWhenSubscriberIsDisabled(): void
    {
        $transition = $this->createTransition([
            $this->createNotificationSetting([NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL]),
        ]);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            WorkflowEvents::PRE_NOTIFICATION_SENDING,
            static function (): void {
                self::fail('The event must not be dispatched when the subscriber is disabled.');
            }
        );

        $this->mailService->expects($this->never())->method('sendWorkflowEmailNotification');

        $subscriber = $this->createSubscriber($eventDispatcher);
        $subscriber->setEnabled(false);
        $subscriber->onWorkflowCompleted($this->createCompletedEvent($transition));
    }

    private function createSubscriber(EventDispatcher $eventDispatcher): NotificationSubscriber
    {
        $workflowManager = $this->createMock(Manager::class);
        $workflowManager
            ->method('getWorkflowByName')
            ->with(self::WORKFLOW_NAME)
            ->willReturn($this->workflow);

        return new NotificationSubscriber(
            $this->mailService,
            $this->pimcoreNotificationService,
            $this->createStub(TranslatorInterface::class),
            $this->expressionService,
            $workflowManager,
            $eventDispatcher
        );
    }

    /**
     * @param string[] $channelType
     */
    private function createNotificationSetting(
        array $channelType,
        string $mailType = NotificationSubscriber::MAIL_TYPE_TEMPLATE,
        string $mailPath = NotificationSubscriber::DEFAULT_MAIL_TEMPLATE_PATH,
        ?string $condition = null
    ): array {
        return [
            'condition' => $condition,
            'notifyUsers' => self::CONFIGURED_USERS,
            'notifyRoles' => self::CONFIGURED_ROLES,
            'channelType' => $channelType,
            'mailType' => $mailType,
            'mailPath' => $mailPath,
        ];
    }

    private function createTransition(array $notificationSettings): PimcoreTransition
    {
        return new PimcoreTransition('go', 'start', 'end', [
            'notificationSettings' => $notificationSettings,
        ]);
    }

    private function createCompletedEvent(PimcoreTransition $transition): CompletedEvent
    {
        return new CompletedEvent($this->subject, new Marking(['start' => 1]), $transition, $this->workflow);
    }

    private function expectMail(
        PimcoreTransition $transition,
        array $users,
        array $roles,
        string $mailPath = NotificationSubscriber::DEFAULT_MAIL_TEMPLATE_PATH
    ): void {
        $this->mailService
            ->expects($this->once())
            ->method('sendWorkflowEmailNotification')
            ->with(
                $users,
                $roles,
                $this->workflow,
                self::SUBJECT_TYPE,
                $this->subject,
                $transition,
                NotificationSubscriber::MAIL_TYPE_TEMPLATE,
                $mailPath
            );
    }

    private function expectPimcoreNotification(PimcoreTransition $transition, array $users, array $roles): void
    {
        $this->pimcoreNotificationService
            ->expects($this->once())
            ->method('sendPimcoreNotification')
            ->with($users, $roles, $this->workflow, self::SUBJECT_TYPE, $this->subject, $transition);
    }
}
