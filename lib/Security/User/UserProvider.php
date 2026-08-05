<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Security\User;

use Pimcore\Model\User as PimcoreUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $pimcoreUser = PimcoreUser::getByName($identifier);

        if ($pimcoreUser) {
            return $this->buildUser($pimcoreUser);
        }

        throw new UserNotFoundException(sprintf('User %s was not found', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            // user is not supported - we only support pimcore users
            throw new UnsupportedUserException();
        }

        /** @var PimcoreUser $refreshedPimcoreUser */
        $refreshedPimcoreUser = PimcoreUser::getById($user->getId());

        if ($user->getLastPasswordReset() !== $refreshedPimcoreUser->getLastPasswordReset()) {
            // password was changed since the session was created, so we invalidate the session
            throw new UnsupportedUserException('User is valid but password was changed');
        }

        return $this->buildUser($refreshedPimcoreUser);
    }

    protected function buildUser(PimcoreUser $pimcoreUser): User
    {
        return new User($pimcoreUser);
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
