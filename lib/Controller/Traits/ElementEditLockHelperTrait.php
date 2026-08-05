<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Controller\Traits;

use Pimcore\Model\Element\Editlock;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 */
trait ElementEditLockHelperTrait
{
    protected function getEditLockResponse(int $id, string $type): JsonResponse
    {
        $editLock = Editlock::getByElement($id, $type);
        $user = User::getById($editLock->getUserId());

        $editLock = $editLock->getObjectVars();
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
