<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Workflow\EventSubscriber;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Workflow;
use Pimcore\Workflow\Notification\NotificationEmailService;
use Pimcore\Workflow\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class NotificationSubscriber implements EventSubscriberInterface
{
    const MAIL_TYPE_TEMPLATE = 'template';

    const MAIL_TYPE_DOCUMENT = 'pimcore_document';

    const NOTIFICATION_CHANNEL_MAIL = 'mail';

    const NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION = 'pimcore_notification';

    const DEFAULT_MAIL_TEMPLATE_PATH = '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig';

    protected NotificationEmailService $mailService;

    protected Workflow\Notification\PimcoreNotificationService $pimcoreNotificationService;

    protected TranslatorInterface $translator;

    protected bool $enabled = true;

    protected Workflow\ExpressionService $expressionService;

    protected Workflow\Manager $workflowManager;

    public function __construct(NotificationEmailService $mailService, Workflow\Notification\PimcoreNotificationService $pimcoreNotificationService, TranslatorInterface $translator, Workflow\ExpressionService $expressionService, Workflow\Manager $workflowManager)
    {
        $this->mailService = $mailService;
        $this->pimcoreNotificationService = $pimcoreNotificationService;
        $this->translator = $translator;
        $this->expressionService = $expressionService;
        $this->workflowManager = $workflowManager;
    }

    public function onWorkflowCompleted(Event $event): void
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var ElementInterface $subject */
        $subject = $event->getSubject();
        /** @var Transition $transition */
        $transition = $event->getTransition();
        $workflow = $this->workflowManager->getWorkflowByName($event->getWorkflowName());

        $notificationSettings = $transition->getNotificationSettings();
        foreach ($notificationSettings as $notificationSetting) {
            $condition = $notificationSetting['condition'] ?? null;

            if (empty($condition) || $this->expressionService->evaluateExpression($workflow, $subject, $condition)) {
                $notifyUsers = $notificationSetting['notifyUsers'] ?? [];
                $notifyRoles = $notificationSetting['notifyRoles'] ?? [];

                if (in_array(self::NOTIFICATION_CHANNEL_MAIL, $notificationSetting['channelType'])) {
                    $this->handleNotifyPostWorkflowEmail($transition, $workflow, $subject, $notificationSetting['mailType'], $notificationSetting['mailPath'], $notifyUsers, $notifyRoles);
                }

                if (in_array(self::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION, $notificationSetting['channelType'])) {
                    $this->handleNotifyPostWorkflowPimcoreNotification($transition, $workflow, $subject, $notifyUsers, $notifyRoles);
                }
            }
        }
    }

    private function handleNotifyPostWorkflowEmail(Transition $transition, \Symfony\Component\Workflow\Workflow $workflow, ElementInterface $subject, string $mailType, string $mailPath, array $notifyUsers, array $notifyRoles): void
    {
        //notify users
        $subjectType = ($subject instanceof Concrete ? $subject->getClassName() : Service::getElementType($subject));

        $this->mailService->sendWorkflowEmailNotification(
            $notifyUsers,
            $notifyRoles,
            $workflow,
            $subjectType,
            $subject,
            $transition->getLabel(),
            $mailType,
            $mailPath
        );
    }

    private function handleNotifyPostWorkflowPimcoreNotification(Transition $transition, \Symfony\Component\Workflow\Workflow $workflow, ElementInterface $subject, array $notifyUsers, array $notifyRoles): void
    {
        $subjectType = ($subject instanceof Concrete ? $subject->getClassName() : Service::getElementType($subject));
        $this->pimcoreNotificationService->sendPimcoreNotification(
            $notifyUsers,
            $notifyRoles,
            $workflow,
            $subjectType,
            $subject,
            $transition->getLabel()
        );
    }

    /**
     * check's if the event subscriber should be executed
     */
    private function checkEvent(Event $event): bool
    {
        return $this->isEnabled()
            && $event->getTransition() instanceof Transition
            && $event->getSubject() instanceof ElementInterface;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.completed' => ['onWorkflowCompleted', 0],
        ];
    }
}
