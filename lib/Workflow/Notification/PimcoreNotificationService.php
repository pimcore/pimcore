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

namespace Pimcore\Workflow\Notification;

use Exception;
use Pimcore\Logger;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification\Service\NotificationService;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PimcoreNotificationService extends AbstractNotificationService
{
    protected NotificationService $notificationService;

    protected TranslatorInterface $translator;

    /**
     * PimcoreNotificationService constructor.
     *
     */
    public function __construct(NotificationService $notificationService, TranslatorInterface $translator)
    {
        $this->notificationService = $notificationService;
        $this->translator = $translator;
    }

    public function sendPimcoreNotification(
        array $users,
        array $roles,
        WorkflowInterface $workflow,
        string $subjectType,
        ElementInterface $subject,
        Transition $transition
    ): void {
        try {
            $recipients = $this->getNotificationUsersByName($users, $roles, true);
            if (!count($recipients)) {
                return;
            }

            foreach ($recipients as $language => $recipientsPerLanguage) {
                $title = $this->translator->trans(
                    'workflow_change_email_notification_subject',
                    [$subjectType . ' ' . $subject->getFullPath(), $workflow->getName()],
                    'admin',
                    $language
                );
                $message = $this->translator->trans(
                    'workflow_change_email_notification_text',
                    [
                        $subjectType . ' ' . $subject->getFullPath(),
                        $subject->getId(),
                        $this->translator->trans($transition->getLabel(), [], 'admin', $language),
                        $this->translator->trans($workflow->getName(), [], 'admin', $language),
                    ],
                    'admin',
                    $language
                );

                $noteInfo = $this->getNoteInfo($subject->getId());
                if ($noteInfo) {
                    $message .= "\n\n";
                    $message .= $this->translator->trans('workflow_change_email_notification_note', [], 'admin') . "\n";
                    $message .= $noteInfo;
                }

                foreach ($recipientsPerLanguage as $recipient) {
                    $this->notificationService->sendToUser($recipient->getId(), 0, $title, $message, $subject);
                }
            }
        } catch (Exception) {
            Logger::error('Error sending Workflow change notification.');
        }
    }
}
