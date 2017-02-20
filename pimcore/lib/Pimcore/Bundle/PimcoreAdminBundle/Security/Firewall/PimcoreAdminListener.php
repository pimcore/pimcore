<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Firewall;

use Pimcore\Bundle\PimcoreAdminBundle\Security\Authentication\Token\PimcoreAdminToken;
use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Tool\Authentication;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\HttpUtils;

class PimcoreAdminListener implements ListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var HttpUtils
     */
    protected $httpUtils;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     * @param HttpUtils $httpUtils
     */
    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router, HttpUtils $httpUtils)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router       = $router;
        $this->httpUtils    = $httpUtils;
    }

    /**
     * @inheritDoc
     */
    public function handle(GetResponseEvent $event)
    {
        $pimcoreUser = Authentication::authenticateSession();

        // TODO is this needed or is handle only called when not authenticated?
        if (null !== $token = $this->tokenStorage->getToken()) {
            if ($token instanceof PimcoreAdminToken && $token->isAuthenticated()) {
                $pimcoreUser = $token->getUser()->getUser();
                $this->logger->debug(sprintf('Loaded user %s from storage', $pimcoreUser->getName()));

                return;
            }
        }

        if ($pimcoreUser) {
            $this->logger->debug(sprintf('Loaded user %s from session', $pimcoreUser->getName()));

            $user  = new User($pimcoreUser);
            $token = new PimcoreAdminToken($user);

            $this->tokenStorage->setToken($token);
            return;
        }

        $this->logger->debug('Clearing storage token as there is no user in the session');
        $this->tokenStorage->setToken(null);

        if (!$this->isAllowed($event->getRequest())) {
            $this->logger->debug('Redirecting to login page');

            // redirect to login page
            $event->setResponse(new RedirectResponse($this->router->generate('admin_login')));
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isAllowed(Request $request)
    {
        $paths = [
            '/admin/login',
            '/admin/login/', // just needed until we remove the old login page
            '/admin/login/login' // just needed until we remove the old login page
        ];

        foreach ($paths as $path) {
            if ($this->httpUtils->checkRequestPath($request, $path)) {
                return true;
            }
        }

        return false;
    }
}
