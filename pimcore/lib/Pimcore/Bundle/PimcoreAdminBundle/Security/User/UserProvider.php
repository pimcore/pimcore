<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\User;

use Pimcore\Model\User as PimcoreUser;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        /** @var PimcoreUser $pimcoreUser */
        $pimcoreUser = PimcoreUser::getByName($username);

        if ($pimcoreUser) {
            $user = new User($pimcoreUser);

            return $user;
        }

        throw new UsernameNotFoundException(sprintf('User %s was not found', $username));
    }

    /**
     * @inheritDoc
     *
     * @param User $user
     */
    public function refreshUser(UserInterface $user)
    {
        /** @var PimcoreUser $refreshedPimcoreUser */
        $refreshedPimcoreUser = PimcoreUser::getById($user->getId());

        return $this->buildUser($refreshedPimcoreUser);
    }

    /**
     * @param PimcoreUser $pimcoreUser
     * @return User
     */
    protected function buildUser(PimcoreUser $pimcoreUser)
    {
        return new User($pimcoreUser);
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return $class === User::class;
    }
}
