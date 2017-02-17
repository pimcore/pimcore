<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Session;

use Pimcore\Bundle\PimcoreBundle\Session\SessionConfiguratorInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminSessionBagConfigurator implements SessionConfiguratorInterface
{
    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        $this->registerBag($session, 'pimcore_admin');
        $this->registerBag($session, 'pimcore_documents');
    }

    /**
     * Create and register an attribute bag
     *
     * @param SessionInterface $session
     * @param string $name
     */
    protected function registerBag(SessionInterface $session, $name)
    {
        $bag = new AttributeBag('_' . $name);
        $bag->setName($name);

        $session->registerBag($bag);
    }
}
