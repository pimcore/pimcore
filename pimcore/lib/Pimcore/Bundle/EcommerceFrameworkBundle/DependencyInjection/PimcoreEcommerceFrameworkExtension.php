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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcoreEcommerceFrameworkExtension extends ConfigurableExtension
{
    /**
     * @inheritDoc
     */
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('environment.yml');
        $loader->load('tracking_manager.yml');

        $this->registerEnvironmentConfiguration($config['environment'], $container);
        $this->registerTrackingManagerConfiguration($config['tracking_manager'], $container);
    }

    private function registerEnvironmentConfiguration(array $config, ContainerBuilder $container)
    {
        $environment = new ChildDefinition($config['environment_id']);
        $environment->setPublic(true);

        $container->setDefinition('pimcore_ecommerce.environment', $environment);
        $container->setParameter('pimcore_ecommerce.environment.options', $config['options']);
    }

    private function registerTrackingManagerConfiguration(array $config, ContainerBuilder $container)
    {
        // the public flag is only needed as the factory still implements a getTrackingManager method which directly
        // accesses the tracking manager service
        $trackingManager = new ChildDefinition($config['tracking_manager_id']);
        $trackingManager->setPublic(true);

        $container->setDefinition('pimcore_ecommerce.tracking.tracking_manager', $trackingManager);

        foreach ($config['trackers'] as $name => $trackerConfig) {
            if (!$trackerConfig['enabled']) {
                continue;
            }

            $tracker = new ChildDefinition($trackerConfig['id']);

            if (null !== $trackerConfig['item_builder_id']) {
                $tracker->setArgument('$trackingItemBuilder', new Reference($trackerConfig['item_builder_id']));
            }

            if (null !== $trackerConfig['options']) {
                $tracker->setArgument('$options', $trackerConfig['options']);
            }

            $trackingManager->addMethodCall('registerTracker', [$tracker]);
        }
    }
}
