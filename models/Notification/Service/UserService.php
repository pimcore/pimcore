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
use Pimcore\Model\User\Role;

class UserService
{
    /**
     * @param User $loggedIn
     *
     * @return array
     */
    public function findAll(User $loggedIn): array
    {
        $filter = [
            'id > ?' => 0,
            'id != ?' => $loggedIn->getId(),
            'name != ?' => 'system',
            'active = ?' => 1,
        ];

        $userFilter = array_merge($filter, [
            'admin = ?' => '1',
        ]);

        $roleFilter = array_merge($filter, [
            'type = ?' => 'role'
        ]);

        $condition = implode(' AND ', array_keys($userFilter));
        $conditionVariables = array_values($userFilter);

        $listing = new User\Listing();
        $listing->setCondition($condition, $conditionVariables);
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');
        $listing->load();

        $users = $listing->getUsers();
        $users = $this->filterUsersWithPermission($users);

        $condition = implode(' AND ', array_keys($roleFilter));
        $conditionVariables = array_values($roleFilter);

        $listing = new Role\Listing();
        $listing->setCondition($condition, $conditionVariables);
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');
        $listing->load();

        $roles = $listing->getRoles();

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
