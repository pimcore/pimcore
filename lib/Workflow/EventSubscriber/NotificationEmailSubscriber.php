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
use Pimcore\Workflow\Transition;
use Pimcore\Workflow\NotificationEmail\NotificationEmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Workflow\Event\Event;


class NotificationEmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $enabled = true;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Event $event
     * @throws ValidationException
     */
    public function onWorkflowCompleted(Event $event)
    {
        if(!$this->checkEvent($event)) {
            return;
        }

        /**
         * @var AbstractElement $subject
         * @var Transition $transition
         */
        $subject = $event->getSubject();
        $transition = $event->getTransition();

        $this->handleNotifyPostWorkflow($transition, $subject);
    }

    /**
     * @param Workflow\NotificationEmail\NotificationEmailInterface $notifyEmail
     * @param AbstractElement $subject
     * @throws ValidationException
     */
    private function handleNotifyPostWorkflow(Workflow\NotificationEmail\NotificationEmailInterface $notifyEmail, AbstractElement $subject)
    {
        //notify users
        $parameters = [
            'product' => (Service::getType($subject) == 'object' ? $subject->getClassName() : Service::getType($subject)),
            'subject' => $subject,
            'action' => $notifyEmail->getLabel(),
            'note_description' => ''
        ];

        $mailService = \Pimcore::getContainer()->get(NotificationEmailService::class);
        $mailService->sendWorkflowEmailNotification($notifyEmail->getNotifyUsers(), $notifyEmail->getNotifyRoles(), $parameters);
    }

    /**
     * check's if the event subscriber should be executed
     *
     * @param Event $event
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
            'workflow.completed' => array('onWorkflowCompleted', 0)
        ];
    }
}
