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

namespace Pimcore\Workflow\Notification;

use Exception;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification\Service\NotificationService;
use Symfony\Component\Workflow\Workflow;
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

    public function sendPimcoreNotification(array $users, array $roles, Workflow $workflow, string $subjectType, ElementInterface $subject, string $action): void
    {
        try {
            $recipients = $this->getNotificationUsersByName($users, $roles, true);
            if (!count($recipients)) {
                return;
            }

            foreach ($recipients as $language => $recipientsPerLanguage) {
                $title = $this->translator->trans('workflow_change_email_notification_subject', [$subjectType . ' ' . $subject->getFullPath(), $workflow->getName()], 'admin', $language);
                $message = $this->translator->trans(
                    'workflow_change_email_notification_text',
                    [
                        $subjectType . ' ' . $subject->getFullPath(),
                        $subject->getId(),
                        $this->translator->trans($action, [], 'admin', $language),
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
        } catch (Exception $e) {
            \Pimcore\Logger::error('Error sending Workflow change notification.');
        }
    }
}
