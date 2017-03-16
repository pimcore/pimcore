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
        if (!$container->hasDefinition('session')) {
            return;
        }

        if (!$container->hasDefinition('pimcore.session.configurator')) {
            return;
        }

        // configure the core session through our configurator service (mainly to register custom attribute bags)
        $session = $container->getDefinition('session');

        // just to make sure nobody else (symfony core, other bundle) sets a configurator and we overwrite it here
        if ($session->getConfigurator()) {
            throw new InvalidConfigurationException('The session service already defines a configurator.');
        }

        $session->setConfigurator([new Reference('pimcore.session.configurator'), 'configure']);

        $this->addTaggedConfigurators($container);
    }

    /**
     * Finds all configurators tagged as pimcore.session.configurator and adds them to the configurator collection
     *
     * @param ContainerBuilder $container
     */
    protected function addTaggedConfigurators(ContainerBuilder $container)
    {
        $configurator   = $container->getDefinition('pimcore.session.configurator');
        $taggedServices = $container->findTaggedServiceIds('pimcore.session.configurator');

        foreach ($taggedServices as $id => $tags) {
            $configurator->addMethodCall('addConfigurator', [new Reference($id)]);
        }
    }
}
