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

use Pimcore\Db;
use Pimcore\Model\Element\Note;
use Pimcore\Model\User;

class AbstractNotificationService
{
    protected function getNoteInfo(int $id): string
    {
        $noteList = new Note\Listing();
        $noteList->addConditionParam('(cid = ?)', [$id]);
        $noteList->setOrderKey('date');
        $noteList->setOrder('desc');
        $noteList->setLimit(1);

        $notes = $noteList->load();

        if (count($notes) == 1) {
            // found matching note
            return $notes[0]->getDescription();
        }

        return '';
    }

    /**
     * Returns a list of distinct users given an user- and role array containing their respective names
     *
     *
     * @return User[][]
     */
    protected function getNotificationUsersByName(array $users, array $roles, bool $includeAllUsers = false): array
    {
        $notifyUsers = [];

        if ($roles) {
            //get roles
            $roleList = new User\Role\Listing();
            $roleList->setCondition('name IN ('.implode(',', array_map([Db::get(), 'quote'], $roles)).')');

            foreach ($roleList->load() as $role) {
                $userList = new User\Listing();
                $userList->setCondition('FIND_IN_SET(?, roles) > 0 AND active = 1', [$role->getId()]);

                if (!$includeAllUsers) {
                    $userList->addConditionParam('(email IS NOT NULL AND email != "")');
                }

                foreach ($userList->load() as $user) {
                    $notifyUsers[$user->getLanguage()][$user->getId()] = $user;
                }
            }
        }

        if ($users) {
            //get users
            $userList = new User\Listing();
            $userList->setCondition('name IN ('.implode(',', array_map([Db::get(), 'quote'], $users)).') and active = 1');

            if (!$includeAllUsers) {
                $userList->addConditionParam('(email IS NOT NULL AND email != "")');
            }

            foreach ($userList->load() as $user) {
                $notifyUsers[$user->getLanguage()][$user->getId()] = $user;
            }
        }

        foreach ($notifyUsers as $language => $usersPerLanguage) {
            $notifyUsers[$language] = array_values($usersPerLanguage);
        }

        return $notifyUsers;
    }
}
