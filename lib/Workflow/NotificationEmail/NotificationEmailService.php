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
    protected $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

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
     * @param array $parameters
     */
    public function sendWorkflowEmailNotification($users, $roles, $parameters)
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

                $mail->setBodyHtml($this->getHtmlBody($parameters, $lang));

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
        $roleList->setCondition('name in (?)', [implode(',', $roles)]);

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
        $userList->setCondition('FIND_IN_SET(name, ?)', [implode(',', $users)]);

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


    private function getHtmlBody($parameters, $lang): string {
        $hostUrl = Tool::getHostUrl();
        $deeplink = '';

        // allow retrieval of inherited values
        $inheritanceBackup = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);

        if ($hostUrl !== '') {
            $deeplink = $hostUrl . '/' . $this->router->generate('pimcore_admin_login') . '/deeplink?object_' . $parameters['subject']->getId() . '_object';
        }

        $noteDescription = $this->getNoteInfo($parameters['subject']->getId());

        $emailTemplate = $this->templatingEngine->render(
            '@PimcoreCore/Workflow/NotificationEmail/notificationEmail.html.twig',
            [
                'product' => $parameters['product'],
                'subject' => $parameters['subject'],
                'action' => $parameters['action'],
                'deeplink' => $deeplink,
                'note_description' => $noteDescription,
                'translator' => $this->translator,
                'lang' => $lang
            ]
        );

        //reset inheritance
        AbstractObject::setGetInheritedValues($inheritanceBackup);

        return $emailTemplate;
    }

    private function getNoteInfo($id): string {
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
