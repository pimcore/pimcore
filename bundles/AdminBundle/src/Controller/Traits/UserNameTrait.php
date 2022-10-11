<?php

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

namespace Pimcore\Bundle\AdminBundle\Controller\Traits;

use Pimcore\Model\User;

/**
 * @internal
 */
trait UserNameTrait
{
    /**
     * @param int $userId The User ID.
     *
     * @return array{userName: string, fullName: string}
     */
    protected function getUserName(int $userId): array
    {
        $user = User::getById($userId);

        if (empty($user)) {
            $data = [
                'userName' => '',
                'fullName' => $this->trans('user_unknown'),
            ];
        } else {
            $data = [
                'userName' => $user->getName(),
                'fullName' => (empty($user->getFullName()) ? $user->getName() : $user->getFullName()),
            ];
        }

        return $data;
    }
}
