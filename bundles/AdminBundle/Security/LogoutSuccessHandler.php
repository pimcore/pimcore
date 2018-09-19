<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Security;

use Pimcore\Event\Admin\Login\LogoutEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\Element\Editlock;
use Pimcore\Model\User;
use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Handle logout. This was originally implemented as LogoutHandler, but wasn't triggered as the token was empty at call
 * time in LogoutListener::handle was called. As the logout success handler is always triggered it is now implemented as
 * success handler.
 *
 * TODO: investigate why the token is empty and change to LogoutHandler
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param TokenStorage $tokenStorage
     * @param RouterInterface $router
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        TokenStorage $tokenStorage,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    public function onLogoutSuccess(Request $request)
    {
        $this->logger->debug('Logging out');

        $this->tokenStorage->setToken(null);

        // clear open edit locks for this session
        Editlock::clearSession(Session::getSessionId());

        /** @var LogoutEvent $event */
        $event = Session::useSession(function (AttributeBagInterface $adminSession) use ($request) {
            $event = null;

            $user = $adminSession->get('user');
            if ($user && $user instanceof User) {
                $event = new LogoutEvent($request, $user);
                $this->eventDispatcher->dispatch(AdminEvents::LOGIN_LOGOUT, $event);

                $adminSession->remove('user');
            }

            Session::invalidate();

            return $event;
        });

        $response = null;
        if ($event && $event->hasResponse()) {
            $response = $event->getResponse();
        } else {
            $response = new RedirectResponse($this->router->generate('pimcore_admin_index'));
        }

        // cleanup pimcore-cookies => 315554400 => strtotime('1980-01-01')
        $response->headers->setCookie(new Cookie('pimcore_opentabs', false, 315554400, '/'));
        $response->headers->clearCookie('pimcore_admin_sid', '/', null, false, true);

        $this->logger->debug('Logout succeeded, redirecting to ' . $response->getTargetUrl());

        return $response;
    }
}
