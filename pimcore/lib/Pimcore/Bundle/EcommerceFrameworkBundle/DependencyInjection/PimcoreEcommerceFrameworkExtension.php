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

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilitySystemLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerFactoryLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManagerFactoryLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessorLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\IndexService\AttributeFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterServiceLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\IFilterServiceLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceSystemLocator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcoreEcommerceFrameworkExtension extends ConfigurableExtension
{
    const SERVICE_ID_FACTORY = 'pimcore_ecommerce.factory';
    const SERVICE_ID_ENVIRONMENT = 'pimcore_ecommerce.environment';
    const SERVICE_ID_PRICING_MANAGER = 'pimcore_ecommerce.pricing_manager';
    const SERVICE_ID_PAYMENT_MANAGER = 'pimcore_ecommerce.payment_manager';
    const SERVICE_ID_INDEX_SERVICE = 'pimcore_ecommerce.index_service';
    const SERVICE_ID_VOUCHER_SERVICE = 'pimcore_ecommerce.voucher_service';
    const SERVICE_ID_TOKEN_MANAGER_FACTORY = 'pimcore_ecommerce.voucher_service.token_manager_factory';
    const SERVICE_ID_OFFER_TOOL = 'pimcore_ecommerce.offer_tool';
    const SERVICE_ID_TRACKING_MANAGER = 'pimcore_ecommerce.tracking.tracking_manager';

    /**
     * The services below are defined as public as the Factory loads services via get() on
     * demand.
     *
     * @inheritDoc
     */
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $container->setParameter('pimcore_ecommerce.pimcore.config', $config['pimcore']);
        $container->setParameter('pimcore_ecommerce.use_legacy_class_mapping', $config['use_legacy_class_mapping']);
        $container->setParameter('pimcore_ecommerce.decimal_scale', $config['decimal_scale']);

        $loader->load('services.yml');
        $loader->load('event_listeners.yml');
        $loader->load('factory.yml');
        $loader->load('environment.yml');
        $loader->load('cart_manager.yml');
        $loader->load('order_manager.yml');
        $loader->load('pricing_manager.yml');
        $loader->load('price_systems.yml');
        $loader->load('availability_systems.yml');
        $loader->load('checkout_manager.yml');
        $loader->load('payment_manager.yml');
        $loader->load('index_service.yml');
        $loader->load('filter_service.yml');
        $loader->load('voucher_service.yml');
        $loader->load('offer_tool.yml');
        $loader->load('tracking_manager.yml');

        $this->registerFactoryConfiguration($container, $config['factory']);
        $this->registerEnvironmentConfiguration($container, $config['environment']);
        $this->registerCartManagerConfiguration($container, $config['cart_manager']);
        $this->registerOrderManagerConfiguration($container, $config['order_manager']);
        $this->registerPricingManagerConfiguration($container, $config['pricing_manager']);
        $this->registerPriceSystemsConfiguration($container, $config['price_systems']);
        $this->registerAvailabilitySystemsConfiguration($container, $config['availability_systems']);
        $this->registerCheckoutManagerConfiguration($container, $config['checkout_manager']);
        $this->registerPaymentManagerConfiguration($container, $config['payment_manager']);
        $this->registerIndexServiceConfig($container, $config['index_service']);
        $this->registerFilterServiceConfig($container, $config['filter_service']);
        $this->registerVoucherServiceConfig($container, $config['voucher_service']);
        $this->registerOfferToolConfig($container, $config['offer_tool']);
        $this->registerTrackingManagerConfiguration($container, $config['tracking_manager']);
    }

    private function registerFactoryConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias(
            self::SERVICE_ID_FACTORY,
            $config['factory_id']
        );

        $container->setParameter(
            'pimcore_ecommerce.factory.strict_tenants',
            $config['strict_tenants']
        );
    }

    private function registerEnvironmentConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias(
            self::SERVICE_ID_ENVIRONMENT,
            $config['environment_id']
        );

        $container->setParameter('pimcore_ecommerce.environment.options', $config['options']);
    }

    private function registerCartManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $mapping = [];

        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $cartManager = new ChildDefinition($tenantConfig['cart_manager_id']);
            $cartManager->setPublic(true);

            $cartFactory = new ChildDefinition($tenantConfig['cart']['factory_id']);

            if (!empty($tenantConfig['cart']['factory_options'])) {
                $cartFactory->setArgument('$options', $tenantConfig['cart']['factory_options']);
            }

            $priceCalculatorFactory = new ChildDefinition($tenantConfig['price_calculator']['factory_id']);
            $priceCalculatorFactory->setArgument(
                '$modificatorConfig',
                $tenantConfig['price_calculator']['modificators']
            );

            if (!empty($tenantConfig['price_calculator']['factory_options'])) {
                $priceCalculatorFactory->setArgument(
                    '$options',
                    $tenantConfig['price_calculator']['factory_options']
                );
            }

            $cartManager->setArgument('$cartFactory', $cartFactory);
            $cartManager->setArgument('$cartPriceCalculatorFactory', $priceCalculatorFactory);

            $aliasName = sprintf('pimcore_ecommerce.cart_manager.%s', $tenant);
            $container->setDefinition($aliasName, $cartManager);

            $mapping[$tenant] = $aliasName;
        }

        $this->setupTenantAwareComponentLocator(
            $container,
            ICartManagerLocator::class,
            $mapping,
            CartManagerLocator::class,
            [
                'pimcore_ecommerce.locator.cart_manager',
            ]
        );
    }

    private function registerOrderManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $mapping = [];

        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $orderManager = new ChildDefinition($tenantConfig['order_manager_id']);
            $orderManager->setPublic(true);

            $orderAgentFactory = new ChildDefinition($tenantConfig['order_agent']['factory_id']);

            if (!empty($tenantConfig['order_agent']['factory_options'])) {
                $orderAgentFactory->setArgument('$options', $tenantConfig['order_agent']['factory_options']);
            }

            $orderManager->setArgument('$orderAgentFactory', $orderAgentFactory);

            if (!empty($tenantConfig['options'])) {
                $orderManager->setArgument('$options', $tenantConfig['options']);
            }

            $aliasName = sprintf('pimcore_ecommerce.order_manager.%s', $tenant);
            $container->setDefinition($aliasName, $orderManager);

            $mapping[$tenant] = $aliasName;
        }

        $this->setupTenantAwareComponentLocator(
            $container,
            IOrderManagerLocator::class,
            $mapping,
            OrderManagerLocator::class,
            [
                'pimcore_ecommerce.locator.order_manager'
            ]
        );
    }

    private function registerPricingManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias(
            self::SERVICE_ID_PRICING_MANAGER,
            $config['pricing_manager_id']
        );

        $container->setParameter('pimcore_ecommerce.pricing_manager.enabled', $config['enabled']);
        $container->setParameter('pimcore_ecommerce.pricing_manager.condition_mapping', $config['conditions']);
        $container->setParameter('pimcore_ecommerce.pricing_manager.action_mapping', $config['actions']);
        $container->setParameter('pimcore_ecommerce.pricing_manager.options', $config['pricing_manager_options']);
    }

    private function registerPriceSystemsConfiguration(ContainerBuilder $container, array $config)
    {
        $mapping = [];

        foreach ($config as $name => $cfg) {
            $aliasName = sprintf('pimcore_ecommerce.price_system.%s', $name);

            $container->setAlias($aliasName, $cfg['id']);
            $mapping[$name] = $aliasName;
        }

        $this->setupNameServiceComponentLocator(
            $container,
            'price_system',
            $mapping,
            PriceSystemLocator::class
        );
    }

    private function registerAvailabilitySystemsConfiguration(ContainerBuilder $container, array $config)
    {
        $mapping = [];

        foreach ($config as $name => $cfg) {
            $aliasName = sprintf('pimcore_ecommerce.availability_system.%s', $name);

            $container->setAlias($aliasName, $cfg['id']);
            $mapping[$name] = $aliasName;
        }

        $this->setupNameServiceComponentLocator(
            $container,
            'availability_system',
            $mapping,
            AvailabilitySystemLocator::class
        );
    }

    private function registerCheckoutManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $commitOrderProcessorMapping   = [];
        $checkoutManagerFactoryMapping = [];

        foreach ($config['tenants'] as $tenant => $tenantConfig) {
            $commitOrderProcessor = new ChildDefinition($tenantConfig['commit_order_processor']['id']);

            if (!empty($tenantConfig['commit_order_processor']['options'])) {
                $commitOrderProcessor->setArgument('$options', $tenantConfig['commit_order_processor']['options']);
            }

            $checkoutManagerFactory = new ChildDefinition($tenantConfig['factory_id']);
            $checkoutManagerFactory->setArguments([
                '$checkoutStepDefinitions' => $tenantConfig['steps'],
            ]);

            if (!empty($tenantConfig['factory_options'])) {
                $checkoutManagerFactory->setArgument('$options', $tenantConfig['factory_options']);
            }

            if (null !== $tenantConfig['payment']['provider']) {
                $checkoutManagerFactory->setArgument('$paymentProvider', new Reference(sprintf(
                    'pimcore_ecommerce.payment_manager.provider.%s',
                    $tenantConfig['payment']['provider']
                )));
            }

            $commitOrderProcessorAliasName = sprintf(
                'pimcore_ecommerce.checkout_manager.%s.commit_order_processor',
                $tenant
            );

            $checkoutManagerFactoryAliasName = sprintf(
                'pimcore_ecommerce.checkout_manager.%s.factory',
                $tenant
            );

            $container->setDefinition($commitOrderProcessorAliasName, $commitOrderProcessor);
            $container->setDefinition($checkoutManagerFactoryAliasName, $checkoutManagerFactory);

            $commitOrderProcessorMapping[$tenant]   = $commitOrderProcessorAliasName;
            $checkoutManagerFactoryMapping[$tenant] = $checkoutManagerFactoryAliasName;
        }

        $this->setupTenantAwareComponentLocator(
            $container,
            ICommitOrderProcessorLocator::class,
            $commitOrderProcessorMapping,
            CommitOrderProcessorLocator::class,
            [
                'pimcore_ecommerce.locator.checkout_manager.commit_order_processor',
            ]
        );

        $this->setupTenantAwareComponentLocator(
            $container,
            ICheckoutManagerFactoryLocator::class,
            $checkoutManagerFactoryMapping,
            CheckoutManagerFactoryLocator::class,
            [
                'pimcore_ecommerce.locator.checkout_manager.factory',
            ]
        );
    }

    private function registerPaymentManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias(self::SERVICE_ID_PAYMENT_MANAGER, $config['payment_manager_id']);

        $mapping = [];

        foreach ($config['providers'] as $name => $providerConfig) {
            if (!isset($providerConfig['profiles'][$providerConfig['profile']])) {
                throw new InvalidConfigurationException(sprintf(
                    'Payment provider "%s" is configured to use profile "%s", but profile is not defined',
                    $name,
                    $providerConfig['profile']
                ));
            }

            $profileConfig = $providerConfig['profiles'][$providerConfig['profile']];

            $provider = new ChildDefinition($providerConfig['provider_id']);
            if (!empty($profileConfig)) {
                $provider->setArgument('$options', $profileConfig);
            }

            $serviceId = sprintf('pimcore_ecommerce.payment_manager.provider.%s', $name);
            $container->setDefinition($serviceId, $provider);

            $mapping[$name] = $serviceId;
        }

        $this->setupServiceLocator($container, 'payment_manager.provider', $mapping);
    }

    private function registerIndexServiceConfig(ContainerBuilder $container, array $config)
    {
        $container->setAlias(
            self::SERVICE_ID_INDEX_SERVICE,
            $config['index_service_id']
        );

        $container->setParameter('pimcore_ecommerce.index_service.default_tenant', $config['default_tenant']);

        $attributeFactory = new AttributeFactory();

        foreach ($config['tenants'] ?? [] as $tenant => $tenantConfig) {
            if (!$tenantConfig['enabled']) {
                continue;
            }

            $configId = sprintf('pimcore_ecommerce.index_service.%s.config', $tenant);
            $workerId = sprintf('pimcore_ecommerce.index_service.%s.worker', $tenant);

            $config = new ChildDefinition($tenantConfig['config_id']);
            $config->setArguments([
                '$tenantName'       => $tenant,
                '$attributes'       => $attributeFactory->createAttributes($tenantConfig['attributes']),
                '$searchAttributes' => $tenantConfig['search_attributes'],
                '$filterTypes'      => []
            ]);

            if (!empty($tenantConfig['config_options'])) {
                $config->setArgument('$options', $tenantConfig['config_options']);
            }

            $worker = new ChildDefinition($tenantConfig['worker_id']);
            $worker->setArgument('$tenantConfig', new Reference($configId));
            $worker->addTag('pimcore_ecommerce.index_service.worker', ['tenant' => $tenant]);

            $container->setDefinition($configId, $config);
            $container->setDefinition($workerId, $worker);
        }
    }

    private function registerFilterServiceConfig(ContainerBuilder $container, array $config)
    {
        $mapping = [];

        foreach ($config['tenants'] ?? [] as $tenant => $tenantConfig) {
            if (!$tenantConfig['enabled']) {
                continue;
            }

            $filterTypes = [];
            foreach ($tenantConfig['filter_types'] ?? [] as $filterTypeName => $filterTypeConfig) {
                $filterType = new ChildDefinition($filterTypeConfig['filter_type_id']);
                $filterType->setArgument('$template', $filterTypeConfig['template']);

                if (!empty($filterTypeConfig['options'])) {
                    $filterType->setArgument('$options', $filterTypeConfig['options']);
                }

                $filterTypes[$filterTypeName] = $filterType;
            }

            $filterService = new ChildDefinition($tenantConfig['service_id']);
            $filterService->setArgument('$filterTypes', $filterTypes);

            $serviceId = sprintf('pimcore_ecommerce.filter_service.%s', $tenant);
            $container->setDefinition($serviceId, $filterService);

            $mapping[$tenant] = $serviceId;
        }

        $this->setupTenantAwareComponentLocator(
            $container,
            IFilterServiceLocator::class,
            $mapping,
            FilterServiceLocator::class,
            [
                'pimcore_ecommerce.locator.filter_service'
            ]
        );
    }

    private function registerVoucherServiceConfig(ContainerBuilder $container, array $config)
    {
        // voucher service options are referenced in service definition
        $container->setParameter(
            'pimcore_ecommerce.voucher_service.options',
            $config['voucher_service_options']
        );

        $container->setAlias(
            self::SERVICE_ID_VOUCHER_SERVICE,
            $config['voucher_service_id']
        );

        $container->setParameter(
            'pimcore_ecommerce.voucher_service.token_manager.mapping',
            $config['token_managers']['mapping']
        );

        $container->setAlias(
            self::SERVICE_ID_TOKEN_MANAGER_FACTORY,
            $config['token_managers']['factory_id']
        );
    }

    private function registerOfferToolConfig(ContainerBuilder $container, array $config)
    {
        $container->setAlias(
            self::SERVICE_ID_OFFER_TOOL,
            $config['service_id']
        );

        $container->setParameter(
            'pimcore_ecommerce.offer_tool.order_storage.offer_class',
            $config['order_storage']['offer_class']
        );

        $container->setParameter(
            'pimcore_ecommerce.offer_tool.order_storage.offer_item_class',
            $config['order_storage']['offer_item_class']
        );

        $container->setParameter(
            'pimcore_ecommerce.offer_tool.order_storage.parent_folder_path',
            $config['order_storage']['parent_folder_path']
        );
    }

    private function registerTrackingManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias(
            self::SERVICE_ID_TRACKING_MANAGER,
            $config['tracking_manager_id']
        );

        foreach ($config['trackers'] as $name => $trackerConfig) {
            if (!$trackerConfig['enabled']) {
                continue;
            }

            $tracker = new ChildDefinition($trackerConfig['id']);

            if (null !== $trackerConfig['item_builder_id']) {
                $tracker->setArgument('$trackingItemBuilder', new Reference($trackerConfig['item_builder_id']));
            }

            if (!empty($trackerConfig['options'])) {
                $tracker->setArgument('$options', $trackerConfig['options']);
            }

            $tracker->addTag('pimcore_ecommerce.tracking.tracker', ['name' => $name]);

            $container->setDefinition(sprintf('pimcore_ecommerce.tracking.tracker.%s', $name), $tracker);
        }
    }

    private function setupTenantAwareComponentLocator(ContainerBuilder $container, string $id, array $mapping, string $class, array $aliases)
    {
        $serviceLocator = $this->setupServiceLocator($container, $id, $mapping, false);

        $container->setDefinition($id, new Definition($class, [
            $serviceLocator,
            new Reference('pimcore_ecommerce.environment'),
            $container->getParameter('pimcore_ecommerce.factory.strict_tenants')
        ]));

        foreach ($aliases as $alias) {
            $container->setAlias($alias, $id);
        }
    }

    private function setupNameServiceComponentLocator(ContainerBuilder $container, string $id, array $mapping, string $class)
    {
        $serviceLocator = $this->setupServiceLocator($container, $id, $mapping, false);

        $locator = new Definition($class, [
            $serviceLocator
        ]);

        $container->setDefinition(sprintf('pimcore_ecommerce.locator.%s', $id), $locator);
    }

    private function setupServiceLocator(ContainerBuilder $container, string $id, array $mapping, bool $register = true)
    {
        foreach ($mapping as $name => $reference) {
            $mapping[$name] = new Reference($reference);
        }

        $serviceLocator = new Definition(ServiceLocator::class, [$mapping]);
        $serviceLocator->setPublic(false);
        $serviceLocator->addTag('container.service_locator');

        if ($register) {
            $container->setDefinition(sprintf('pimcore_ecommerce.locator.%s', $id), $serviceLocator);
        }

        return $serviceLocator;
    }
}
