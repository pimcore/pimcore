<?php
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

namespace Pimcore\Workflow\EventSubscriber;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow;
use Pimcore\Workflow\NotificationEmail\NotificationEmailService;
use Pimcore\Workflow\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Workflow\Event\Event;

class NotificationEmailSubscriber implements EventSubscriberInterface
{
    const MAIL_TYPE_TEMPLATE = 'template';
    const MAIL_TYPE_DOCUMENT = 'pimcore_document';

    const DEFAULT_MAIL_TEMPLATE_PATH = '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig';

    /**
     * @var TranslatorInterface
     */
    private $mailService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var Workflow\ExpressionService
     */
    private $expressionService;

    /**
     * @var Workflow\Manager
     */
    private $workflowManager;

    /**
     * NotificationEmailSubscriber constructor.
     *
     * @param NotificationEmailService $mailService
     * @param TranslatorInterface $translator
     * @param Workflow\ExpressionService $expressionService
     * @param Workflow\Manager $manager
     */
    public function __construct(NotificationEmailService $mailService, TranslatorInterface $translator, Workflow\ExpressionService $expressionService, Workflow\Manager $manager)
    {
        $this->mailService = $mailService;
        $this->translator = $translator;
        $this->expressionService = $expressionService;
        $this->workflowManager = $manager;
    }

    /**
     * @param Event $event
     *
     * @throws ValidationException
     */
    public function onWorkflowCompleted(Event $event)
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /**
         * @var AbstractElement $subject
         * @var Transition $transition
         */
        $subject = $event->getSubject();
        $transition = $event->getTransition();
        $workflow = $this->workflowManager->getWorkflowByName($event->getWorkflowName());

        $notificationSettings = $transition->getNotificationSettings();
        foreach ($notificationSettings as $notificationSetting) {
            $condition = $notificationSetting['condition'];

            if (empty($condition) || $this->expressionService->evaluateExpression($workflow, $subject, $condition)) {
                $notifyUsers = $notificationSetting['notifyUsers'] ?? [];
                $notifyRoles = $notificationSetting['notifyRoles'] ?? [];

                $this->handleNotifyPostWorkflow($transition, $workflow, $subject, $notificationSetting['mailType'], $notificationSetting['mailPath'], $notifyUsers, $notifyRoles);
            }
        }
    }

    /**
     * @param Transition $notifyEmail
     * @param \Pimcore\Model\Workflow $workflow
     * @param AbstractElement $subject
     * @param string $mailType
     * @param string $mailPath
     */
    private function handleNotifyPostWorkflow(Transition $transition, \Symfony\Component\Workflow\Workflow $workflow, AbstractElement $subject, string $mailType, string $mailPath, array $notifyUsers, array $notifyRoles)
    {
        //notify users
        $subjectType = (Service::getType($subject) == 'object' ? $subject->getClassName() : Service::getType($subject));

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

    /**
     * check's if the event subscriber should be executed
     *
     * @param Event $event
     *
     * @return bool
     */
    private function checkEvent(Event $event): bool
    {
        return $this->isEnabled()
            && $event->getTransition() instanceof Transition
            && $event->getSubject() instanceof AbstractElement;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.completed' => ['onWorkflowCompleted', 0]
        ];
    }
}
