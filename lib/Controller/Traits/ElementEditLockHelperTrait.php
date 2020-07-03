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

namespace Pimcore\Controller\Traits;

use Pimcore\Model\Element\Editlock;
use Pimcore\Model\User;

trait ElementEditLockHelperTrait
{
    protected function getEditLockResponse(string $id, string $type)
    {
        $editLock = Editlock::getByElement($id, $type);
        $user = User::getById($editLock->getUserId());

        $editLock = object2array($editLock);
        unset($editLock['sessionId']);

        if ($user) {
            $editLock['user'] = [
                'name' => $user->getName(),
            ];
        }

        return $this->adminJson([
            'editlock' => $editLock,
        ]);
    }
}
