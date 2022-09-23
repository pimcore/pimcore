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
     * @param int $user_id The User ID.
     *
     * @return array{username: string, fullname: string}
     */
    protected function getUserName(int $user_id): array
    {
        $user = User::getById($userId);

        if (empty($user)) {
            $data = [
                'username' => '',
                'fullname' => $this->trans('user_unknown'),
            ];
        } else {
            $data = [
                'username' => $user->getName(),
                'fullname' => $user->getFullName(),
            ];
        }

        return $data;
    }
}
