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

namespace Pimcore\Bundle\AdminBundle\Security\User;

use Pimcore\Security\User\User as PimcoreUser;

trigger_deprecation(
    'pimcore/pimcore',
    '10.6',
    'The "%s" class is deprecated and will be removed in Pimcore 11. Use %s instead.',
    [User::class, PimcoreUser::class]
);

/**
 * @deprecated and will be removed in Pimcore 11. Use \Pimcore\Security\User\User instead.
 */
class User extends \Pimcore\Security\User\User
{
}
