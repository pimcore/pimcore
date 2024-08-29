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
