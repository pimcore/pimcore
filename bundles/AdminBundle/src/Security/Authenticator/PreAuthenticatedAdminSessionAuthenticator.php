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

namespace Pimcore\Bundle\AdminBundle\Security\Authenticator;

use Pimcore\Tool\Authentication;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
class PreAuthenticatedAdminSessionAuthenticator implements InteractiveAuthenticatorInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private UserProviderInterface $userProvider,
        private TokenStorageInterface $tokenStorage,
        private string $firewallName
    ) {
    }

    public function isInteractive(): bool
    {
        return true;
    }

    public function supports(Request $request): ?bool
    {
        $username = null;

        try {
            $user = Authentication::authenticateSession($request);
            if ($user) {
                $username = $user->getUsername();
            }
        } catch (\Exception $e) {
            if ($e instanceof AuthenticationException) {
                $this->clearToken($e);
            }

            if (null !== $this->logger) {
                $this->logger->debug('Skipping pre-authenticated authenticator as a BadCredentialsException is thrown.', ['exception' => $e, 'authenticator' => static::class]);
            }

            return false;
        }

        if (null === $username) {
            if (null !== $this->logger) {
                $this->logger->debug('Skipping pre-authenticated authenticator no username could be extracted.', ['authenticator' => static::class]);
            }

            return false;
        }

        $request->attributes->set('_pre_authenticated_username', $username);

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge($request->attributes->get('_pre_authenticated_username'), $this->userProvider->loadUserByIdentifier(...)),
            [new PreAuthenticatedUserBadge()]
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new PreAuthenticatedToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // let the original request continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->clearToken($exception);

        return null;
    }

    private function clearToken(AuthenticationException $exception): void
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof PreAuthenticatedToken && $this->firewallName === $token->getFirewallName()) {
            $this->tokenStorage->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info('Cleared pre-authenticated token due to an exception.', ['exception' => $exception]);
            }
        }
    }
}
