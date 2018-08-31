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

namespace Pimcore\Workflow\NotificationEmail;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Tool;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Pimcore\Model\User;
use Pimcore\Model\Element;

class NotificationEmailService
{
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
     * Sends an email
     *
     * @param array $users
     * @param array $roles
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param string $action
     */
    public function sendWorkflowEmailNotification($users, $roles, $subjectType, $subject, $action)
    {
        try {
            $recipients = self::getNotificationUsersByName($users, $roles);
            if (!count($recipients)) {
                return;
            }

            foreach ($recipients as $lang => $recipientsLanguage) {

                $mail = new \Pimcore\Mail();

                foreach ($recipientsLanguage as $user) {
                    /**
                     * @var $user User
                     */
                    $mail->addTo($user->getEmail(), $user->getName());
                }

                $mail->setSubject("Workflow Update");

                $mail->setBodyHtml($this->getHtmlBody($subjectType, $subject, $action, $lang));

                $mail->send();
            }

        } catch(\Exception $e) {
            \Pimcore\Logger::error("Error sending Workflow change notification email.");
        }
    }

    /**
     * Returns a list of distinct users given an user- and role array containing their respective names
     *
     * @param $users
     * @param $roles
     *
     * @return User[]
     */
    private function getNotificationUsersByName($users, $roles)
    {
        $notifyUsers = [];

        //get roles
        $roleList = new User\Role\Listing();
        $roleList->setCondition('name in (?) and email is not null', [implode(',', $roles)]);

        foreach ($roleList->load() as $role) {
            $userList = new User\Listing();
            $userList->setCondition('FIND_IN_SET(?, roles) > 0', [$role->getId()]);

            foreach ($userList->load() as $user) {
                if ($user->getEmail()) {
                    if ($user->getLanguage() === 'de') {
                        $notifyUsers['de'][$user->getId()] = $user;
                    }
                    else {
                        $notifyUsers['en'][$user->getId()] = $user;
                    }
                }
            }
        }

        //get users
        $userList = new User\Listing();
        $userList->setCondition('FIND_IN_SET(name, ?) and email is not null', [implode(',', $users)]);

        foreach ($userList->load() as $user) {
            /**
             * @var User $user
             */
            if ($user->getEmail()) {
                if ($user->getLanguage() === 'de') {
                    $notifyUsers['de'][$user->getId()] = $user;
                }
                else {
                    $notifyUsers['en'][$user->getId()] = $user;
                }
            }
        }

        if (!empty($notifyUsers['de'])) {
            $notifyUsers['de'] = array_values($notifyUsers['de']);
        }

        if (!empty($notifyUsers['en'])) {
            $notifyUsers['en'] = array_values($notifyUsers['en']);
        }

        return $notifyUsers;
    }

    /**
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param string $action
     * @param string $lang
     *
     * @return string
     */
    protected function getHtmlBody($subjectType, $subject, $action, $lang): string {
        // allow retrieval of inherited values
        $inheritanceBackup = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);

        $deeplink = '';
        $hostUrl = Tool::getHostUrl();
        if ($hostUrl !== '') {
            $deeplink = $hostUrl . '/' . $this->router->generate('pimcore_admin_login') . '/deeplink?object_' . $subject->getId() . '_object';
        }

        $emailTemplate = $this->templatingEngine->render(
            '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig', $this->getNotificationEmailParameters($subjectType, $subject, $action, $deeplink, $lang)
        );

        //reset inheritance
        AbstractObject::setGetInheritedValues($inheritanceBackup);

        return $emailTemplate;
    }

    /**
     * @param string $subjectType
     * @param AbstractElement $subject
     * @param string $action
     * @param string $deeplink
     * @param string $lang
     *
     * @return array
     */
    protected function getNotificationEmailParameters($subjectType, $subject, $action, $deeplink, $lang) {
        $noteDescription = $this->getNoteInfo($subject->getId());

        return [
            'subjectType' => $subjectType,
            'subject' => $subject,
            'action' => $action,
            'deeplink' => $deeplink,
            'note_description' => $noteDescription,
            'translator' => $this->translator,
            'lang' => $lang
        ];
    }

    protected function getNoteInfo($id): string {
        $noteList = new Element\Note\Listing();
        $noteList->addConditionParam("(cid = ?)", [$id]);
        $noteList->setOrderKey("date");
        $noteList->setOrder("desc");
        $noteList->setLimit(1);

        $notes = $noteList->load();

        if (count($notes) == 1) {
            // found matching note
            return $notes[0]->getDescription();
        }

        return '';
    }
}
