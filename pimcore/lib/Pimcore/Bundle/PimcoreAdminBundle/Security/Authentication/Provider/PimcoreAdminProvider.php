<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Authentication\Provider;

use Pimcore\Bundle\PimcoreAdminBundle\Security\Authentication\Token\PimcoreAdminToken;
use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class PimcoreAdminProvider implements AuthenticationProviderInterface
{
    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        $pimcoreUser = Authentication::authenticateSession();
        if ($pimcoreUser && $user->getId() === $pimcoreUser->getId()) {
            $authenticatedToken = new PimcoreAdminToken(new User($pimcoreUser));
            $authenticatedToken->setAuthenticated(true);

            return $authenticatedToken;
        }

        throw new AuthenticationException('Failed to load pimcore user from session');
    }

    /**
     * @inheritDoc
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PimcoreAdminToken;
    }
}
