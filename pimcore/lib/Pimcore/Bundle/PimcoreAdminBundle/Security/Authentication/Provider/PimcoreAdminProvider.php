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
        // this is only here as double check - actually the token is already authenticated as soon as it's set
        // in firewall listener
        if ($token instanceof PimcoreAdminToken) {
            $pimcoreUser = $token->getUser()->getUser();
            if (Authentication::isValidUser($pimcoreUser)) {
                $token->setAuthenticated(true);

                return $token;
            }
        }

        throw new AuthenticationException('Failed to authenticate pimcore admin token');
    }

    /**
     * @inheritDoc
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PimcoreAdminToken;
    }
}
