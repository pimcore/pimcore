<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Firewall;

use Pimcore\Bundle\PimcoreAdminBundle\Security\Authentication\Token\PimcoreAdminToken;
use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class PimcoreAdminListener implements ListenerInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritDoc
     */
    public function handle(GetResponseEvent $event)
    {
        $pimcoreUser = Authentication::authenticateSession();
        if (!$pimcoreUser) {
            return;
        }

        $user  = new User($pimcoreUser);
        $token = new PimcoreAdminToken($user);

        $this->tokenStorage->setToken($token);

        /*
        $token = $this->tokenStorage->getToken();
        if ($token instanceof PimcoreAdminToken) {
             $this->tokenStorage->setToken(null);
        }
        */
    }
}
