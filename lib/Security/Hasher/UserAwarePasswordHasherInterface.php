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
