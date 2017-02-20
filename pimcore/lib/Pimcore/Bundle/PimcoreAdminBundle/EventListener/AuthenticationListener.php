<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Model\User;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class AuthenticationListener implements EventSubscriberInterface, LoggerAwareInterface
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
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     */
    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router       = $router;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!preg_match('/^\/admin/', $request->getPathInfo())) {
            $this->logger->warning('Not handling authentication as no admin route');

            return;
        }

        if ($this->isAllowed($request)) {
            $this->logger->warning(sprintf('Not handling authentication as request to %s is allowed', $request->getPathInfo()));

            return;
        }

        if (null !== $token = $this->tokenStorage->getToken()) {
            $pimcoreUser = $token->getUser();

            $this->logger->warning(sprintf('Loaded user %s from storage', $pimcoreUser->getId()));

            if ($pimcoreUser instanceof User && \Pimcore\Tool\Authentication::isValidUser($pimcoreUser)) {
                return;
            }
        }

        if (null !== $pimcoreUser = \Pimcore\Tool\Authentication::authenticateSession()) {
            $this->logger->warning(sprintf('Loaded user %s from session', $pimcoreUser->getId()));

            $user  = new \Pimcore\Bundle\PimcoreAdminBundle\Security\User\User($pimcoreUser);
            $token = new PostAuthenticationGuardToken($user, 'admin', $user->getRoles());

            $this->tokenStorage->setToken($token);

            return;
        }

        $this->logger->warning(sprintf('No user found - redirecting to login'));

        $event->setResponse(new RedirectResponse($this->router->generate('admin_login')));
    }

    protected function isAllowed(Request $request)
    {
        return preg_match('/\/admin\/login\/?/', $request->getPathInfo());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 64]
        ];
    }
}
