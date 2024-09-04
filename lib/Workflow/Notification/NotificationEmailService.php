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
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Workflow\Workflow;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationEmailService extends AbstractNotificationService
{
    const MAIL_PATH_LANGUAGE_PLACEHOLDER = '%_locale%';

    private EngineInterface $template;

    private RouterInterface $router;

    protected TranslatorInterface $translator;

    public function __construct(EngineInterface $template, RouterInterface $router, TranslatorInterface $translator)
    {
        $this->template = $template;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * Sends an Mail
     *
     */
    public function sendWorkflowEmailNotification(array $users, array $roles, Workflow $workflow, string $subjectType, ElementInterface $subject, string $action, string $mailType, string $mailPath): void
    {
        try {
            $recipients = $this->getNotificationUsersByName($users, $roles);
            if (!count($recipients)) {
                return;
            }

            $deeplink = '';
            $hostUrl = Tool::getHostUrl();
            if ($hostUrl !== '') {
                // Decide what kind of link to create
                $objectType = $type = 'object';
                if ($subject instanceof \Pimcore\Model\Document) {
                    $objectType = 'document';
                    $type = $subject->getType();
                }
                if ($subject instanceof \Pimcore\Model\Asset) {
                    $objectType = 'asset';
                    $type = $subject->getType();
                }

                $deeplink = $hostUrl . $this->router->generate('pimcore_admin_login_deeplink') . '?'.$objectType.'_' . $subject->getId() . '_'. $type;
            }

            foreach ($recipients as $language => $recipientsPerLanguage) {
                $localizedMailPath = str_replace(self::MAIL_PATH_LANGUAGE_PLACEHOLDER, $language, $mailPath);

                switch ($mailType) {
                    case NotificationSubscriber::MAIL_TYPE_TEMPLATE:

                        $this->sendTemplateMail(
                            $recipientsPerLanguage,
                            $subjectType,
                            $subject,
                            $workflow,
                            $action,
                            $language,
                            $localizedMailPath,
                            $deeplink
                        );

                        break;

                    case NotificationSubscriber::MAIL_TYPE_DOCUMENT:

                        $this->sendPimcoreDocumentMail(
                            $recipientsPerLanguage,
                            $subjectType,
                            $subject,
                            $workflow,
                            $action,
                            $language,
                            $localizedMailPath,
                            $deeplink
                        );

                        break;
                }
            }
        } catch (Exception $e) {
            \Pimcore\Logger::error('Error sending Workflow change notification email.');
        }
    }

    /**
     * @param User[] $recipients
     */
    protected function sendPimcoreDocumentMail(array $recipients, string $subjectType, ElementInterface $subject, Workflow $workflow, string $action, string $language, string $mailPath, string $deeplink): void
    {
        $mail = new \Pimcore\Mail(['document' => $mailPath, 'params' => $this->getNotificationEmailParameters($subjectType, $subject, $workflow, $action, $deeplink, $language)]);

        foreach ($recipients as $user) {
            $mail->addTo($user->getEmail(), $user->getName());
        }

        $mail->send();
    }

    /**
     * @param User[] $recipients
     */
    protected function sendTemplateMail(array $recipients, string $subjectType, ElementInterface $subject, Workflow $workflow, string $action, string $language, string $mailPath, string $deeplink): void
    {
        $mail = new \Pimcore\Mail();

        foreach ($recipients as $user) {
            $mail->addTo($user->getEmail(), $user->getName());
        }

        $mail->subject(
            $this->translator->trans('workflow_change_email_notification_subject', [$subjectType . ' ' . $subject->getFullPath(), $workflow->getName()], 'admin', $language)
        );

        $mail->html($this->getHtmlBody($subjectType, $subject, $workflow, $action, $language, $mailPath, $deeplink));

        $mail->send();
    }

    protected function getHtmlBody(string $subjectType, ElementInterface $subject, Workflow $workflow, string $action, string $language, string $mailPath, string $deeplink): string
    {
        $translatorLocaleBackup = null;
        if ($this->translator instanceof LocaleAwareInterface) {
            $translatorLocaleBackup = $this->translator->getLocale();
            $this->translator->setLocale($language);
        }

        try {
            // allow retrieval of inherited values
            return DataObject\Service::useInheritedValues(true, fn () => $this->template->render(
                $mailPath,
                $this->getNotificationEmailParameters($subjectType, $subject, $workflow, $action, $deeplink, $language),
            ));
        } finally {
            if ($this->translator instanceof LocaleAwareInterface) {
                //reset translation locale
                $this->translator->setLocale($translatorLocaleBackup);
            }
        }
    }

    protected function getNotificationEmailParameters(string $subjectType, ElementInterface $subject, Workflow $workflow, string $action, string $deeplink, string $language): array
    {
        $noteDescription = $this->getNoteInfo($subject->getId());

        return [
            'subjectType' => $subjectType,
            'subject' => $subject,
            'action' => $action,
            'workflow' => $workflow,
            'workflowName' => $workflow->getName(),
            'deeplink' => $deeplink,
            'note_description' => $noteDescription,
            'translator' => $this->translator,
            'lang' => $language,
        ];
    }
}
