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

use Pimcore\Bundle\AdminBundle\Security\User\User;
use Pimcore\Event\Admin\Login\LoginFailedEvent;
use Pimcore\Event\Admin\Login\LoginRedirectEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @internal
 */
class AdminLoginAuthenticator extends AdminAbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === self::PIMCORE_ADMIN_LOGIN_CHECK
            && $request->getMethod() === 'POST' && $request->get('password');
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response('Session expired or unauthorized request. Please reload and try again!');
            $response->setStatusCode(Response::HTTP_FORBIDDEN);

            return $response;
        }

        $event = new LoginRedirectEvent(self::PIMCORE_ADMIN_LOGIN, ['perspective' => strip_tags($request->get('perspective'))]);
        $this->dispatcher->dispatch($event, AdminEvents::LOGIN_REDIRECT);

        $url = $this->router->generate($event->getRouteName(), $event->getRouteParams());

        return new RedirectResponse($url);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request): Passport
    {
        if (!$username = $request->get('username')) {
            throw new AuthenticationException('Missing username or password');
        }

        $passport = new Passport(
            new UserBadge($username),
            new CustomCredentials(function ($credentials) {
                $pimcoreUser = Authentication::authenticatePlaintext($credentials['username'], $credentials['password']);

                if ($pimcoreUser) {
                    $user = new User($pimcoreUser);
                    $this->saveUserToSession($user);
                } else {
                    // trigger LOGIN_FAILED event if user could not be authenticated via username/password
                    $event = new LoginFailedEvent($credentials);
                    $this->dispatcher->dispatch($event, AdminEvents::LOGIN_FAILED);

                    if ($event->hasUser()) {
                        $user = new User($event->getUser());
                        $this->saveUserToSession($user);
                    } else {
                        return false;
                    }
                }

                return true;
            }, ['username' => $username, 'password' => $request->get('password')])
        );

        if ($csrfToken = $request->get('csrf_token')) {
            $passport->addBadge(new CsrfTokenBadge('pimcore_admin_authenticate', $csrfToken));
        }

        return $passport;
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive(): bool
    {
        return true;
    }
}
