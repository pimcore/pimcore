<?php

namespace Pimcore\Bundle\PimcoreBundle\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Handles a collection of session configurators.
 */
class SessionConfigurator implements SessionConfiguratorInterface
{
    /**
     * @var SessionConfiguratorInterface[]
     */
    protected $configurators = [];

    /**
     * @param SessionConfiguratorInterface $configurator
     */
    public function addConfigurator(SessionConfiguratorInterface $configurator)
    {
        $this->configurators[] = $configurator;
    }

    /**
     * @param SessionConfiguratorInterface[] $configurators
     */
    public function setConfigurators(array $configurators)
    {
        $this->configurators = [];

        foreach ($configurators as $configurator) {
            $this->addConfigurator($configurator);
        }
    }

    /**
     * @inheritDoc
     */
    public function configure(SessionInterface $session)
    {
        foreach ($this->configurators as $configurator) {
            $configurator->configure($session);
        }
    }
}
