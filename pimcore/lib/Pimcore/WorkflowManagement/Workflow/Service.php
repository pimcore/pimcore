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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\WorkflowManagement\Workflow;

use Pimcore\Model\Element;
use Pimcore\Model\User;
use Pimcore\Logger;

class Service
{


    /**
     * @param $fc - The field configuration from the Workflow
     * @param $value - The value
     * @return array
     */
    public static function createNoteData($fc, $value)
    {
        $data = [];

        //supported types for notes are text, date, document, asset, object, bool
        if ($fc['fieldType'] === 'checkbox') {
            $data['type'] = 'bool';
            $data['value'] = (bool) $value;
        } elseif (in_array($fc['fieldType'], ['date', 'datetime'])) {
            $data['type'] = 'date';
            if (empty($fc['timeformat']) || $fc['timeformat'] === 'milliseconds') {
                $data['value'] = new \Pimcore\Date($value / 1000);
            } else {
                $data['value'] = new \Pimcore\Date($value);
            }
        } elseif (false) { //TODO

            $data['type'] = 'document';
            $data['value'] = $value;
        } elseif (false) { //TODO

            $data['type'] = 'asset';
            $data['value'] = $value;
        } elseif (false) { //TODO

            $data['type'] = 'object';
            $data['value'] = $value;
        } else {
            $data['type'] = 'text';
            $data['value'] = $value;
        }

        $data['key'] = $fc['name'];

        return $data;
    }

    /**
     * @param $data
     * @param $pimcoreTagName
     * @return mixed|null
     */
    public static function getDataFromEditmode($data, $pimcoreTagName)
    {
        $tagClass = '\\Pimcore\\Model\\Object\\ClassDefinition\\Data\\' . ucfirst($pimcoreTagName);
        if (\Pimcore\Tool::classExists($tagClass)) {
            /**
             * @var \Pimcore\Model\Object\ClassDefinition\Data $tag
             */
            $tag = new $tagClass();

            return $tag->getDataFromEditmode($data);
        }

        //purposely return null if there is no valid class, log a warning
        Logger::warning("No valid pimcore tag found for fieldType ({$pimcoreTagName}), check 'fieldType' exists, and 'type' is not being used in config");

        return null;
    }


    /**
     * Creates a note for an action with a transition
     * @param Element\AbstractElement $element
     * @param string $type
     * @param string $title
     * @param string $description
     * @param array $noteData
     * @return Element\Note $note
     */
    public static function createActionNote($element, $type, $title, $description, $noteData, $user=null)
    {
        //prepare some vars for creating the note
        if (!$user) {
            $user = \Pimcore\Tool\Admin::getCurrentUser();
        }

        $note = new Element\Note();
        $note->setElement($element);
        $note->setDate(time());
        $note->setType($type);
        $note->setTitle($title);
        $note->setDescription($description);
        $note->setUser($user->getId());

        if (is_array($noteData)) {
            foreach ($noteData as $row) {
                if ($row['key'] === 'noteDate' && $row['type'] === 'date') {
                    /**
                     * @var \Pimcore\Date $date
                     */
                    $date = $row['value'];
                    $note->setDate($date->getTimestamp());
                } else {
                    $note->addData($row['key'], $row['type'], $row['value']);
                }
            }
        }

        $note->save();

        return $note;
    }


    /**
     * Sends an email
     * @param array $users
     * @param Element\Note $note
     */
    public static function sendEmailNotification($users, $note)
    {
        //try {

            $recipients = self::getNotificationUsers($users);
        if (!count($recipients)) {
            return;
        }

        $mail = new \Pimcore\Mail();
        foreach ($recipients as $user) {
            /**
                 * @var $user User
                 */
                $mail->addTo($user->getEmail(), $user->getName());
        }

        $element = Element\Service::getElementById($note->getCtype(), $note->getCid());

        $mail->setSubject("[pimcore] {$note->getTitle()}, {$element->getType()} [{$element->getId()}]");

            //TODO decide some body text/html

            $mail->setBodyText($note->getDescription());

        $mail->send();

        //} catch(\Exception $e) {
        //    //todo application log
       // }
    }


    /**
     * Returns a list of users given an array of ID's
     * if an ID is a role, all users associated with that role
     * will also be returned.
     * @param $userIds
     */
    private static function getNotificationUsers($userIds)
    {
        $notifyUsers = [];

        //get roles
        $roleList = new User\Role\Listing();
        $roleList->setCondition('id in (?)', [implode(',', $userIds)]);

        foreach ($roleList->load() as $role) {
            $userList = new User\Listing();
            $userList->setCondition('FIND_IN_SET(?, roles) > 0', [$role->getId()]);

            foreach ($userList->load() as $user) {
                if ($user->getEmail()) {
                    $notifyUsers[] = $user;
                }
            }
        }
        unset($roleList, $user, $role);

        //get users
        $roleList = new User\Listing();
        $roleList->setCondition('id in (?)', [implode(',', $userIds)]);

        foreach ($roleList->load() as $user) {
            /**
             * @var User $user
             */
            if ($user->getEmail()) {
                $notifyUsers[] = $user;
            }
        }

        return $notifyUsers;
    }
}
