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

namespace Pimcore\Security\Hasher;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
interface UserAwarePasswordHasherInterface extends PasswordHasherInterface
{
    /**
     * Set the user
     *
     *
     * @throws RuntimeException
     *      if the user is already set to prevent overwriting the scoped user object
     */
    public function setUser(UserInterface $user): void;

    /**
     * Get the user object
     *
     *
     * @throws RuntimeException
     *      if no user was set
     */
    public function getUser(): UserInterface;
}
