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
        $loader->load('cart_manager.yml');
        $loader->load('order_manager.yml');
        $loader->load('voucher_service.yml');
        $loader->load('tracking_manager.yml');

        $this->registerEnvironmentConfiguration($config['environment'], $container);
        $this->registerCartManagerConfiguration($config['cart_manager'], $container);
        $this->registerOrderManagerConfiguration($config['order_manager'], $container);
        $this->registerVoucherServiceConfig($config['voucher_service'], $container);
        $this->registerTrackingManagerConfiguration($config['tracking_manager'], $container);
    }

    private function registerEnvironmentConfiguration(array $config, ContainerBuilder $container)
    {
        $environment = new ChildDefinition($config['environment_id']);
        $environment->setPublic(true);

        $container->setDefinition('pimcore_ecommerce.environment', $environment);
        $container->setParameter('pimcore_ecommerce.environment.options', $config['options']);
    }

    private function registerCartManagerConfiguration(array $config, ContainerBuilder $container)
    {
        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $cartManager = new ChildDefinition($tenantConfig['cart_manager_id']);
            $cartManager->setPublic(true);

            $cartFactory = new ChildDefinition($tenantConfig['cart']['factory_id']);
            $cartFactory->setArgument('$options', $tenantConfig['cart']['factory_options']);

            $priceCalculatorFactory = new ChildDefinition($tenantConfig['price_calculator']['factory_id']);
            $priceCalculatorFactory->setArgument(
                '$modificatorConfig',
                $tenantConfig['price_calculator']['modificators']
            );
            $priceCalculatorFactory->setArgument(
                '$options',
                $tenantConfig['price_calculator']['factory_options']
            );

            $cartManager->setArgument('$cartFactory', $cartFactory);
            $cartManager->setArgument('$cartPriceCalculatorFactory', $priceCalculatorFactory);

            // order manager tenant defaults to the same tenant as the cart tenant but can be
            // configured on demand
            $orderManagerTenant = $tenantConfig['order_manager_tenant'] ?? $tenant;

            $cartManager->setArgument('$orderManager', new Reference('pimcore_ecommerce.order_manager.' . $orderManagerTenant));

            $container->setDefinition('pimcore_ecommerce.cart_manager.' . $tenant, $cartManager);
        }
    }

    private function registerOrderManagerConfiguration(array $config, ContainerBuilder $container)
    {
        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $orderManager = new ChildDefinition($tenantConfig['order_manager_id']);
            $orderManager->setPublic(true);

            $orderAgentFactory = new ChildDefinition($tenantConfig['order_agent']['factory_id']);
            $orderAgentFactory->setArgument('$options', $tenantConfig['order_agent']['factory_options']);

            $orderManager->setArgument('$orderAgentFactory', $orderAgentFactory);
            $orderManager->setArgument('$options', $tenantConfig['options']);

            $container->setDefinition('pimcore_ecommerce.order_manager.' . $tenant, $orderManager);
        }
    }

    private function registerVoucherServiceConfig(array $config, ContainerBuilder $container)
    {
        $voucherService = new ChildDefinition($config['voucher_service_id']);
        $voucherService->setPublic(true);
        $voucherService->setArgument('$options', $config['voucher_service_options']);

        $container->setDefinition('pimcore_ecommerce.voucher_service', $voucherService);

        $container->setParameter(
            'pimcore_ecommerce.voucher_service.token_manager.mapping',
            $config['token_managers']['mapping']
        );

        $container->setAlias(
            'pimcore_ecommerce.voucher_service.token_manager_factory',
            $config['token_managers']['factory_id']
        );
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
