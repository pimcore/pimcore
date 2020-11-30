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

namespace Pimcore\Workflow\Notification;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Workflow\Workflow;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationEmailService extends AbstractNotificationService
{
    const MAIL_PATH_LANGUAGE_PLACEHOLDER = '%_locale%';

    /**
     * @var EngineInterface $templatingEngine
     */
    private $templatingEngine;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param EngineInterface $templatingEngine
     * @param RouterInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(EngineInterface $templatingEngine, RouterInterface $router, TranslatorInterface $translator)
    {
        $this->templatingEngine = $templatingEngine;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * Sends an Mail
     *
     * @param array $users
     * @param array $roles
     * @param Workflow $workflow
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param string $action
     * @param string $mailType
     * @param string $mailPath
     */
    public function sendWorkflowEmailNotification(array $users, array $roles, Workflow $workflow, string $subjectType, AbstractElement $subject, string $action, string $mailType, string $mailPath)
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
        } catch (\Exception $e) {
            \Pimcore\Logger::error('Error sending Workflow change notification email.');
        }
    }

    /**
     * @param User[] $recipients
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param Workflow $workflow
     * @param string $action
     * @param string $language
     * @param string $mailPath
     * @param string $deeplink
     */
    protected function sendPimcoreDocumentMail(array $recipients, string $subjectType, AbstractElement $subject, Workflow $workflow, string $action, string $language, string $mailPath, string $deeplink)
    {
        $mail = new \Pimcore\Mail(['document' => $mailPath, 'params' => $this->getNotificationEmailParameters($subjectType, $subject, $workflow, $action, $deeplink, $language)]);

        foreach ($recipients as $user) {
            $mail->addTo($user->getEmail(), $user->getName());
        }

        $mail->send();
    }

    /**
     * @param User[] $recipients
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param Workflow $workflow
     * @param string $action
     * @param string $language
     * @param string $mailPath
     * @param string $deeplink
     */
    protected function sendTemplateMail(array $recipients, string $subjectType, AbstractElement $subject, Workflow $workflow, string $action, string $language, string $mailPath, string $deeplink)
    {
        $mail = new \Pimcore\Mail();

        foreach ($recipients as $user) {
            $mail->addTo($user->getEmail(), $user->getName());
        }

        $mail->setSubject(
            $this->translator->trans('workflow_change_email_notification_subject', [$subjectType . ' ' . $subject->getFullPath(), $workflow->getName()], 'admin', $language)
        );

        $mail->setBodyHtml($this->getHtmlBody($subjectType, $subject, $workflow, $action, $language, $mailPath, $deeplink));

        $mail->send();
    }

    /**
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param Workflow $workflow
     * @param string $action
     * @param string $language
     * @param string $mailPath
     * @param string $deeplink
     *
     * @return string
     */
    protected function getHtmlBody(string $subjectType, AbstractElement $subject, Workflow $workflow, string $action, string $language, string $mailPath, string $deeplink): string
    {
        // allow retrieval of inherited values
        $inheritanceBackup = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);

        $translatorLocaleBackup = $this->translator->getLocale();
        $this->translator->setLocale($language);

        $emailTemplate = $this->templatingEngine->render(
            $mailPath, $this->getNotificationEmailParameters($subjectType, $subject, $workflow, $action, $deeplink, $language)
        );

        //reset inheritance
        AbstractObject::setGetInheritedValues($inheritanceBackup);

        //reset translation locale
        $this->translator->setLocale($translatorLocaleBackup);

        return $emailTemplate;
    }

    /**
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param Workflow $workflow
     * @param string $action
     * @param string $deeplink
     * @param string $language
     *
     * @return array
     */
    protected function getNotificationEmailParameters(string $subjectType, AbstractElement $subject, Workflow $workflow, string $action, string $deeplink, string $language): array
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
