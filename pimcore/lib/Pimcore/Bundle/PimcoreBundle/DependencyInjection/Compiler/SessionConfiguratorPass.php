<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SessionConfiguratorPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // configure the core session through our configurator service (mainly to register custom attribute bags)
        $session = $container->getDefinition('session');

        // just to make sure nobody else (symfony core, other bundle) sets a configurator and we overwrite it here
        if ($session->getConfigurator()) {
            throw new InvalidConfigurationException('The session service already defines a configurator.');
        }

        $session->setConfigurator([new Reference('pimcore.session.configurator'), 'configure']);
    }
}
