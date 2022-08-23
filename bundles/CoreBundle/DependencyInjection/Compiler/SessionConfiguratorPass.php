<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Session\SessionConfigurator;
use Pimcore\Session\SessionConfiguratorInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @TODO remove in Pimcore 11
 *
 * @internal
 */
final class SessionConfiguratorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // @phpstan-ignore-next-line
        if (!$container->has('session')) {
            return;
        }

        // @phpstan-ignore-next-line
        if (!$container->has(SessionConfigurator::class)) {
            return;
        }

        // configure the core session through our configurator service (mainly to register custom attribute bags)
        $session = $container->findDefinition('session');

        // just to make sure nobody else (symfony core, other bundle) sets a configurator and we overwrite it here
        if ($session->getConfigurator()) {
            throw new InvalidConfigurationException('The session service already defines a configurator.');
        }

        $session->setConfigurator([new Reference(SessionConfigurator::class), 'configure']);

        $this->addTaggedConfigurators($container);
    }

    /**
     * Finds all configurators tagged as pimcore.session.configurator and adds them to the configurator collection
     *
     * @param ContainerBuilder $container
     */
    protected function addTaggedConfigurators(ContainerBuilder $container)
    {
        $configurator = $container->getDefinition(SessionConfigurator::class);
        $taggedServices = $container->findTaggedServiceIds('pimcore.session.configurator');

        foreach ($taggedServices as $id => $tags) {
            if (($tags[0]['type'] ?? null) !== 'internal') {
                trigger_deprecation('pimcore/pimcore', '10.5',
                    sprintf('Implementation of %s is deprecated since version 10.5 and will be removed in Pimcore 11.' .
                        'Implement the Event Listener instead.', SessionConfiguratorInterface::class));
            }

            $configurator->addMethodCall('addConfigurator', [new Reference($id)]);
        }
    }
}
