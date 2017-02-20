<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security;

use Pimcore\Model\Element\Editlock;
use Pimcore\Model\User;
use Pimcore\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param TokenStorage $tokenStorage
     * @param RouterInterface $router
     */
    public function __construct(TokenStorage $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router       = $router;
    }

    /**
     * @inheritDoc
     */
    public function onLogoutSuccess(Request $request)
    {
        $this->logger->debug('Logging out');

        $this->tokenStorage->setToken(null);

        // clear open edit locks for this session
        Editlock::clearSession(session_id());

        // TODO trigger admin.login.logout event
        Session::useSession(function ($adminSession) {
            if ($adminSession->user instanceof User) {
                $adminSession->user = null;
            }

            \Zend_Session::destroy();
        });

        $response = new RedirectResponse($this->router->generate('admin_index'));

        // cleanup pimcore-cookies => 315554400 => strtotime('1980-01-01')
        $response->headers->setCookie(new Cookie('pimcore_opentabs', false, 315554400, '/'));

        $this->logger->debug('Logout succeeded, redirecting to ' . $response->getTargetUrl());

        return $response;
    }
}
