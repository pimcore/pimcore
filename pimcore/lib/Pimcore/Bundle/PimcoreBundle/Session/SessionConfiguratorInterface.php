<?php

namespace Pimcore\Bundle\PimcoreBundle\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface SessionConfiguratorInterface
{
    /**
     * Configure the session (e.g. register a bag)
     *
     * @param SessionInterface $session
     */
    public function configure(SessionInterface $session);
}
