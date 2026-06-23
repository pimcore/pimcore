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

namespace Pimcore\Model\User;

use Pimcore\Model\User;

/**
 * @internal
 */
class Service
{
    /**
     * Mapping between database types and pimcore class names
     */
    public static function getClassNameForType(string $type): ?string
    {
        return match ($type) {
            'user' => User::class,
            'userfolder' => User\Folder::class,
            'role' => User\Role::class,
            'rolefolder' => User\Role\Folder::class,
            default => null,
        };
    }
}
