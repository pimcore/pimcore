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

use Pimcore\Bundle\AdminBundle\Security\Authentication\Token\TwoFactorRequiredToken;
use Pimcore\Bundle\AdminBundle\Security\User\User;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\User as UserModel;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
abstract class AdminAbstractAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface, LoggerAwareInterface
{
    public const PIMCORE_ADMIN_LOGIN = 'pimcore_admin_login';

    public const PIMCORE_ADMIN_LOGIN_CHECK = 'pimcore_admin_login_check';

    use LoggerAwareTrait;

    /**
     * @var bool
     */
    protected $twoFactorRequired = false;

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected RouterInterface $router,
        protected TranslatorInterface $translator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            throw new AccessDeniedHttpException(strtr($exception->getMessageKey(), $exception->getMessageData()));
        }

        $url = $this->router->generate(AdminLoginAuthenticator::PIMCORE_ADMIN_LOGIN, [
            'auth_failed' => 'true',
        ]);

        return new RedirectResponse($url);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        /** @var UserModel $user */
        $user = $token->getUser()->getUser();

        // set user language
        $request->setLocale($user->getLanguage());
        $this->translator->setLocale($user->getLanguage());

        // set user on runtime cache for legacy compatibility
        RuntimeCache::set('pimcore_admin_user', $user);

        if ($user->isAdmin()) {
            if (Admin::isMaintenanceModeScheduledForLogin()) {
                Admin::activateMaintenanceMode(Session::getSessionId());
                Admin::unscheduleMaintenanceModeOnLogin();
            }
        }

        // as we authenticate statelessly (short lived sessions) the authentication is called for
        // every request. therefore we only redirect if we're on the login page
        if (!in_array($request->attributes->get('_route'), [
            AdminLoginAuthenticator::PIMCORE_ADMIN_LOGIN,
            AdminLoginAuthenticator::PIMCORE_ADMIN_LOGIN_CHECK,
        ])) {
            return null;
        }

        if ($request->get('deeplink') && $request->get('deeplink') !== 'true') {
            $url = $this->router->generate('pimcore_admin_login_deeplink');
            $url .= '?' . $request->get('deeplink');
        } else {
            $url = $this->router->generate('pimcore_admin_index', [
                '_dc' => time(),
                'perspective' => strip_tags($request->get('perspective')),
            ]);
        }

        if ($url) {
            $response = new RedirectResponse($url);
            $response->headers->setCookie(new Cookie('pimcore_admin_sid', true));

            return $response;
        }

        return null;
    }

    /**
     * @param User $user
     */
    protected function saveUserToSession($user): void
    {
        if ($user && Authentication::isValidUser($user->getUser())) {
            $pimcoreUser = $user->getUser();

            Session::useSession(function (AttributeBagInterface $adminSession) use ($pimcoreUser) {
                Session::regenerateId();
                $adminSession->set('user', $pimcoreUser);

                // this flag gets removed after successful authentication in \Pimcore\Bundle\AdminBundle\EventListener\TwoFactorListener
                if ($pimcoreUser->getTwoFactorAuthentication('required') && $pimcoreUser->getTwoFactorAuthentication('enabled')) {
                    $adminSession->set('2fa_required', true);
                }
            });
        }
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        if ($this->twoFactorRequired) {
            return new TwoFactorRequiredToken(
                $passport->getUser(),
                $firewallName,
                $passport->getUser()->getRoles()
            );
        } else {
            return parent::createToken($passport, $firewallName);
        }
    }

    /**
     * @deprecated
     */
    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        /** @var Passport $passport */
        return $this->createToken($passport, $firewallName);
    }
}
