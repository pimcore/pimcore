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

declare(strict_types=1);

namespace Pimcore\Model\Notification\Service;

use Pimcore\Model\User;

class UserService
{
    /**
     * @param User $loggedIn
     *
     * @return array
     */
    public function findAll(User $loggedIn): array
    {

        // condition for users with groups having notifications permission
        $condition = [];
        $rolesList = new \Pimcore\Model\User\Role\Listing();
        $rolesList->addConditionParam("CONCAT(',', permissions, ',') LIKE ?", '%,notifications,%');
        $rolesList->load();
        $roles = $rolesList->getRoles();

        foreach ($roles as $role) {
            $condition[] = "CONCAT(',', roles, ',') LIKE '%," . $role->getId() . ",%'";
        }

        // get available users having notifications permission or having a group with notifications permission
        $userListing = new User\Listing();
        $userListing->setOrderKey('name');
        $userListing->setOrder('ASC');

        $condition[] = 'admin = 1';
        $userListing->addConditionParam("((CONCAT(',', permissions, ',') LIKE ? ) OR " . implode(' OR ', $condition) . ')', '%,notifications,%');
        $userListing->addConditionParam('id != ?', $loggedIn->getId());
        $userListing->addConditionParam('active = ?', '1');
        $userListing->load();
        $users = $userListing->getUsers();

        return array_merge($users, $roles);
    }

    /**
     * @param array $users
     *
     * @return array
     */
    public function filterUsersWithPermission(array $users): array
    {
        $usersList = [];

        /** @var User $user */
        foreach ($users as $user) {
            if ($user->isAllowed('notifications')) {
                $usersList[] = $user;
            }
        }

        return $usersList;
    }
}
