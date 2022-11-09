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

namespace Pimcore\Bundle\AdminBundle\Security\Firewall;

use Pimcore\Bundle\AdminBundle\Security\User\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * @deprecated will be removed in Pimcore 11
 *
 * Checks if there's an existing admin session and stores its token on the security token storage.
 *
 * @package Pimcore\Bundle\AdminBundle\Security\Firewall
 */
class PreAuthenticatedAdminSessionListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AuthenticationManagerInterface $authenticationManager,
        private string $providerKey
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $pimcoreUser = Authentication::authenticateSession($request);
        if (null !== $pimcoreUser) {
            $user = new User($pimcoreUser);

            $token = new PreAuthenticatedToken($user, $this->providerKey);
            $token->setUser($user);

            try {
                $authenticatedToken = $this->authenticationManager->authenticate($token);
                $this->tokenStorage->setToken($authenticatedToken);
            } catch (AuthenticationException $e) {
                // clear token on auth failure
                $storedToken = $this->tokenStorage->getToken();
                if ($storedToken instanceof PreAuthenticatedToken && $storedToken->getFirewallName() === $this->providerKey) {
                    $this->tokenStorage->setToken(null);
                }
            }
        }
    }
}
