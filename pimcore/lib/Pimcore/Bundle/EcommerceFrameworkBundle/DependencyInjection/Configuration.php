<?php

declare(strict_types=1);

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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculatorFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\MultiCartManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManagerFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\Config\Processor\PlaceholderProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\Config\Processor\TenantProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\IndexService\DefaultWorkerConfigMapper;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterService;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysql;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexService;
use Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\DefaultService as DefaultOfferToolService;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\AgentFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Environment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Bundle\EcommerceFrameworkBundle\SessionEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\DefaultService as DefaultVoucherService;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\TokenManagerFactory;
use Pimcore\Model\Object\OfferToolOffer;
use Pimcore\Model\Object\OfferToolOfferItem;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    /**
     * @var TenantProcessor
     */
    private $tenantProcessor;

    /**
     * @var PlaceholderProcessor
     */
    private $placeholderProcessor;

    /**
     * @var DefaultWorkerConfigMapper
     */
    private $indexWorkerConfigMapper;

    public function __construct()
    {
        $this->tenantProcessor      = new TenantProcessor();
        $this->placeholderProcessor = new PlaceholderProcessor();

        $this->indexWorkerConfigMapper = new DefaultWorkerConfigMapper();
    }

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('pimcore_ecommerce_framework');
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->append($this->buildPimcoreNode())
            ->append($this->buildFactoryNode())
            ->append($this->buildEnvironmentNode())
            ->append($this->buildCartManagerNode())
            ->append($this->buildOrderManagerNode())
            ->append($this->buildPricingManagerNode())
            ->append($this->buildPriceSystemsNode())
            ->append($this->buildAvailabilitySystemsNode())
            ->append($this->buildCheckoutManagerNode())
            ->append($this->buildPaymentManagerNode())
            ->append($this->buildIndexServiceNode())
            ->append($this->buildFilterServiceNode())
            ->append($this->buildVoucherServiceNode())
            ->append($this->buildOfferToolNode())
            ->append($this->buildTrackingManagerNode());

        return $treeBuilder;
    }

    private function buildPimcoreNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $pimcore = $builder->root('pimcore');
        $pimcore->addDefaultsIfNotSet();

        $pimcore
            ->children()
                ->arrayNode('menu')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('pricing_rules')
                            ->addDefaultsIfNotSet()
                            ->canBeDisabled()
                        ->end()
                        ->arrayNode('order_list')
                            ->addDefaultsIfNotSet()
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('route')
                                    ->defaultValue('pimcore_ecommerce_backend_admin-order_list')
                                ->end()
                                ->scalarNode('path')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $pimcore;
    }

    private function buildFactoryNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $factory = $builder->root('factory');
        $factory->addDefaultsIfNotSet();

        $factory
            ->children()
                ->scalarNode('factory_id')
                    ->defaultValue(Factory::class)
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('strict_tenants')
                    ->defaultFalse()
                    ->info('If true the factory will not fall back to the default tenant if a tenant is passed but not existing')
                ->end()
            ->end();

        return $factory;
    }

    private function buildEnvironmentNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $environment = $builder->root('environment');
        $environment->addDefaultsIfNotSet();
        $environment
            ->children()
                ->scalarNode('environment_id')
                    ->defaultValue(SessionEnvironment::class)
                ->end()
                ->append($this->buildOptionsNode('options'))
            ->end();

        return $environment;
    }

    private function buildCartManagerNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $cartManager = $builder->root('cart_manager');
        $cartManager->addDefaultsIfNotSet();

        $cartManager
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('tenants')
                    ->info('Configuration per tenant. If a _defaults key is set, it will be merged into every tenant. A tenant named "default" is mandatory.')
                    ->example([
                        '_defaults' => [
                            'cart' => [
                                'factory_id' => 'CartFactory'
                            ]
                        ],
                        'default' => [
                            'cart' => [
                                'factory_options' => [
                                    'foo' => 'bar'
                                ]
                            ]
                        ],
                        'noShipping' => [
                            'price_calculator' => [
                                'factory_id' => 'PriceCalculatorFactory'
                            ]
                        ]
                    ])
                    ->useAttributeAsKey('name')
                    ->validate()
                        ->ifTrue(function (array $v) {
                            return !array_key_exists('default', $v);
                        })
                        ->thenInvalid('Cart manager needs at least a default tenant')
                    ->end()
                    ->beforeNormalization()
                    ->always(function ($v) {
                        if (empty($v) || !is_array($v)) {
                            $v = [];
                        }

                        return $this->tenantProcessor->mergeTenantConfig($v);
                    })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('cart_manager_id')
                                ->defaultValue(MultiCartManager::class)
                            ->end()
                            ->scalarNode('order_manager_tenant')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('cart')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('factory_id')
                                        ->cannotBeEmpty()
                                        ->defaultValue(CartFactory::class)
                                    ->end()
                                    ->append($this->buildOptionsNode('factory_options', [
                                        'cart_class_name'       => Cart::class,
                                        'guest_cart_class_name' => SessionCart::class
                                    ]))
                                ->end()
                            ->end()
                            ->arrayNode('price_calculator')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('factory_id')
                                        ->cannotBeEmpty()
                                        ->defaultValue(CartPriceCalculatorFactory::class)
                                    ->end()
                                    ->append($this->buildOptionsNode('factory_options'))
                                    ->arrayNode('modificators')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('class')->isRequired()->end()
                                                ->append($this->buildOptionsNode())
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $cartManager;
    }

    private function buildOrderManagerNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $orderManager = $builder->root('order_manager');
        $orderManager->addDefaultsIfNotSet();

        $orderManager
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('tenants')
                    ->info('Configuration per tenant. If a _defaults key is set, it will be merged into every tenant. A tenant named "default" is mandatory.')
                    ->useAttributeAsKey('name')
                    ->validate()
                        ->ifTrue(function (array $v) {
                            return !array_key_exists('default', $v);
                        })
                        ->thenInvalid('Order manager needs at least a default tenant')
                    ->end()
                    ->beforeNormalization()
                    ->always(function ($v) {
                        if (empty($v) || !is_array($v)) {
                            $v = [];
                        }

                        return $this->tenantProcessor->mergeTenantConfig($v);
                    })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('order_manager_id')
                                ->defaultValue(OrderManager::class)
                            ->end()
                            ->arrayNode('options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('order_class')
                                        ->defaultValue('\\Pimcore\\Model\\Object\\OnlineShopOrder')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('order_item_class')
                                        ->defaultValue('\\Pimcore\\Model\\Object\\OnlineShopOrderItem')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('list_class')
                                        ->defaultValue(Listing::class)
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('list_item_class')
                                        ->defaultValue(Listing\Item::class)
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('parent_order_folder')
                                        ->defaultValue('/order/%%Y/%%m/%%d')
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('order_agent')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('factory_id')
                                        ->defaultValue(AgentFactory::class)
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->append($this->buildOptionsNode('factory_options'))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $orderManager;
    }

    private function buildPricingManagerNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $pricingManager = $builder->root('pricing_manager');
        $pricingManager->addDefaultsIfNotSet();

        $pricingManager
            ->canBeDisabled()
            ->children()
                ->scalarNode('pricing_manager_id')
                    ->cannotBeEmpty()
                    ->defaultValue(PricingManager::class)
                ->end()
                ->arrayNode('pricing_manager_options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('rule_class')
                            ->cannotBeEmpty()
                            ->defaultValue(Rule::class)
                        ->end()
                        ->scalarNode('price_info_class')
                            ->cannotBeEmpty()
                            ->defaultValue(PriceInfo::class)
                        ->end()
                        ->scalarNode('environment_class')
                            ->cannotBeEmpty()
                            ->defaultValue(Environment::class)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('conditions')
                    ->info('Condition mapping from name to used class')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('actions')
                    ->info('Action mapping from name to used class')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();

        return $pricingManager;
    }

    private function buildPriceSystemsNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $priceSystems = $builder->root('price_systems');
        $priceSystems
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v) {
                        return ['id' => $v];
                    })
                ->end()
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('id')
                        ->isRequired()
                    ->end()
                ->end()
            ->end();

        return $priceSystems;
    }

    private function buildAvailabilitySystemsNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $availabilitySystems = $builder->root('availability_systems');
        $availabilitySystems
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v) {
                        return ['id' => $v];
                    })
                ->end()
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('id')
                        ->isRequired()
                    ->end()
                ->end()
            ->end();

        return $availabilitySystems;
    }

    private function buildCheckoutManagerNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $checkoutManager = $builder->root('checkout_manager');
        $checkoutManager->addDefaultsIfNotSet();

        $checkoutManager
            ->children()
                ->arrayNode('tenants')
                    ->info('Configuration per tenant. If a _defaults key is set, it will be merged into every tenant. A tenant named "default" is mandatory.')
                    ->useAttributeAsKey('name')
                    ->validate()
                        ->ifTrue(function (array $v) {
                            return !array_key_exists('default', $v);
                        })
                        ->thenInvalid('Checkout manager needs at least a default tenant')
                    ->end()
                    ->beforeNormalization()
                        ->always(function ($v) {
                            if (empty($v) || !is_array($v)) {
                                $v = [];
                            }

                            return $this->tenantProcessor->mergeTenantConfig($v);
                        })
                    ->end()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('factory_id')
                                ->defaultValue(CheckoutManagerFactory::class)
                                ->cannotBeEmpty()
                            ->end()
                            ->append($this->buildOptionsNode('factory_options'))
                            ->scalarNode('order_manager_tenant')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('payment')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('provider')
                                        ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('commit_order_processor')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('id')
                                        ->defaultValue(CommitOrderProcessor::class)
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->append($this->buildOptionsNode())
                                ->end()
                            ->end()
                            ->arrayNode('steps')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('class')
                                            ->isRequired()
                                        ->end()
                                        ->append($this->buildOptionsNode())
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $checkoutManager;
    }

    private function buildPaymentManagerNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $paymentManager = $builder->root('payment_manager');
        $paymentManager->addDefaultsIfNotSet();

        $paymentManager
            ->children()
                ->scalarNode('payment_manager_id')
                    ->cannotBeEmpty()
                    ->defaultValue(PaymentManager::class)
                ->end()
                ->arrayNode('providers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('provider_id')
                                ->isRequired()
                            ->end()
                            ->scalarNode('profile')
                                ->isRequired()
                            ->end()
                            ->arrayNode('profiles')
                                ->beforeNormalization()
                                    ->always(function ($v) {
                                        if (empty($v) || !is_array($v)) {
                                            $v = [];
                                        }

                                        return $this->tenantProcessor->mergeTenantConfig($v);
                                    })
                                ->end()
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->useAttributeAsKey('name')
                                    ->prototype('variable')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $paymentManager;
    }

    private function buildIndexServiceNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $indexService = $builder->root('index_service');
        $indexService->addDefaultsIfNotSet();

        $indexService
            ->children()
                ->scalarNode('index_service_id')
                    ->cannotBeEmpty()
                    ->defaultValue(IndexService::class)
                ->end()
                ->scalarNode('default_tenant')
                    ->cannotBeEmpty()
                    ->defaultValue('default')
                ->end()
                ->arrayNode('tenants')
                    ->info('Configuration per tenant. If a _defaults key is set, it will be merged into every tenant.')
                    ->useAttributeAsKey('name', false)
                    ->validate()
                        ->always(function (array $v) {
                            // check if all search attributes are defined as attribute
                            foreach ($v as $tenant => $tenantConfig) {
                                foreach ($tenantConfig['search_attributes'] as $searchAttribute) {
                                    if (!isset($tenantConfig['attributes'][$searchAttribute])) {
                                        throw new InvalidConfigurationException(sprintf(
                                            'The search attribute "%s" in product index tenant "%s" is not defined as attribute.',
                                            $searchAttribute,
                                            $tenant
                                        ));
                                    }
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->always(function ($v) {
                            if (empty($v) || !is_array($v)) {
                                $v = [];
                            }

                            $config = $this->tenantProcessor->mergeTenantConfig($v);

                            foreach ($config as $tenant => $tenantConfig) {
                                if (isset($tenantConfig['placeholders']) && is_array($tenantConfig['placeholders']) && count($tenantConfig['placeholders']) > 0) {
                                    $placeholders = $tenantConfig['placeholders'];

                                    // remove placeholders while replacing as we don't want to replace the placeholders
                                    unset($tenantConfig['placeholders']);

                                    $config[$tenant] = $this->placeholderProcessor->mergePlaceholders($tenantConfig, $placeholders);

                                    // re-add placeholders
                                    $config[$tenant]['placeholders'] = $placeholders;
                                }

                                // if only config or worker is set, try to auto resolve missing config/worker
                                if (!($config[$tenant]['config_id'] && $config[$tenant]['worker_id'])) {
                                    // nothing is set - set default value
                                    if (!$config[$tenant]['config_id'] && !$config[$tenant]['worker_id']) {
                                        $config[$tenant]['config_id'] = DefaultMysql::class;
                                    }

                                    // resolve default matching part
                                    if ($config[$tenant]['config_id']) {
                                        $config[$tenant]['worker_id'] = $this->indexWorkerConfigMapper->getWorkerForConfig($config[$tenant]['config_id']);
                                    } elseif ($config[$tenant]['worker_id']) {
                                        $config[$tenant]['config_id'] = $this->indexWorkerConfigMapper->getConfigForWorker($config[$tenant]['worker_id']);
                                    }
                                }
                            }

                            return $config;
                        })
                    ->end()
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->canBeDisabled()
                        ->children()
                            ->scalarNode('config_id')
                                ->cannotBeEmpty()
                                ->defaultValue(DefaultMysql::class)
                            ->end()
                            ->append($this->buildOptionsNode('config_options'))
                            ->scalarNode('worker_id')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('placeholders')
                                ->defaultValue([])
                                ->beforeNormalization()
                                    ->castToArray()
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('search_attributes')
                                ->defaultValue([])
                                ->beforeNormalization()
                                    ->castToArray()
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('attributes')
                                ->useAttributeAsKey('name', false)
                                ->beforeNormalization()
                                    ->always(function ($v) {
                                        if (empty($v) || !is_array($v)) {
                                            $v = [];
                                        }

                                        // make sure the name property is set
                                        foreach (array_keys($v) as $name) {
                                            if (!isset($v[$name]['name'])) {
                                                $v[$name]['name'] = $name;
                                            }
                                        }

                                        return $v;
                                    })
                                ->end()
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->always(function($v) {
                                            if (empty($v) || !is_array($v)) {
                                                return $v;
                                            }

                                            $v = $this->remapProperties($v,[
                                                'fieldname'               => 'field_name',
                                                'filtergroup'             => 'filter_group',
                                                'getter'                  => 'getter_id',
                                                'interpreter'             => 'interpreter_id',
                                                'config'                  => 'options',
                                                'hideInFieldlistDatatype' => 'hide_in_fieldlist_datatype'
                                            ]);

                                            return $v;
                                        })
                                    ->end()
                                    ->children()
                                        ->scalarNode('name')->isRequired()->end()
                                        ->scalarNode('field_name')->defaultNull()->end()
                                        ->scalarNode('type')->defaultNull()->end()
                                        ->scalarNode('locale')->defaultNull()->end()
                                        ->scalarNode('filter_group')->defaultNull()->end()
                                        ->append($this->buildOptionsNode())
                                        ->scalarNode('getter_id')->defaultNull()->end()
                                        ->append($this->buildOptionsNode('getter_options'))
                                        ->scalarNode('interpreter_id')->defaultNull()->end()
                                        ->append($this->buildOptionsNode('interpreter_options'))
                                        ->booleanNode('hide_in_fieldlist_datatype')->defaultFalse()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $indexService;
    }

    private function buildFilterServiceNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $filterService = $builder->root('filter_service');
        $filterService->addDefaultsIfNotSet();

        $filterService
            ->children()
                ->arrayNode('tenants')
                    ->info('Configuration per tenant. If a _defaults key is set, it will be merged into every tenant.')
                    ->useAttributeAsKey('name', false)
                    ->beforeNormalization()
                        ->always(function ($v) {
                            if (empty($v) || !is_array($v)) {
                                $v = [];
                            }

                            return $this->tenantProcessor->mergeTenantConfig($v);
                        })
                    ->end()
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->canBeDisabled()
                        ->children()
                            ->scalarNode('service_id')
                                ->cannotBeEmpty()
                                ->defaultValue(FilterService::class)
                            ->end()
                            ->arrayNode('filter_types')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->addDefaultsIfNotSet()
                                    ->beforeNormalization()
                                        ->always(function ($v) {
                                            if (empty($v) || !is_array($v)) {
                                                return $v;
                                            }

                                            return $this->remapProperties($v, [
                                                'class'  => 'filter_type_id',
                                                'script' => 'template'
                                            ]);
                                        })
                                    ->end()
                                    ->children()
                                        ->scalarNode('filter_type_id')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('template')
                                            ->isRequired()
                                        ->end()
                                        ->append($this->buildOptionsNode())
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $filterService;
    }

    private function buildVoucherServiceNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $voucherService = $builder->root('voucher_service');
        $voucherService->addDefaultsIfNotSet();
        $voucherService
            ->children()
                ->scalarNode('voucher_service_id')
                    ->cannotBeEmpty()
                    ->defaultValue(DefaultVoucherService::class)
                ->end()
                ->arrayNode('voucher_service_options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('reservation_minutes_threshold')
                            ->info('Reservations older than x MINUTES get removed by maintenance task')
                            ->defaultValue(5)
                            ->min(0)
                        ->end()
                        ->integerNode('statistics_days_threshold')
                            ->info('Statistics older than x DAYS get removed by maintenance task')
                            ->defaultValue(30)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('token_managers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('factory_id')
                            ->cannotBeEmpty()
                            ->defaultValue(TokenManagerFactory::class)
                        ->end()
                        ->arrayNode('mapping')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $voucherService;
    }

    private function buildOfferToolNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $offerTool = $builder->root('offer_tool');
        $offerTool->addDefaultsIfNotSet();

        $offerTool
            ->children()
                ->scalarNode('service_id')
                    ->defaultValue(DefaultOfferToolService::class)
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('order_storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('offer_class')
                            ->cannotBeEmpty()
                            ->defaultValue(OfferToolOffer::class)
                        ->end()
                        ->scalarNode('offer_item_class')
                            ->cannotBeEmpty()
                            ->defaultValue(OfferToolOfferItem::class)
                        ->end()
                        ->scalarNode('parent_folder_path')
                            ->cannotBeEmpty()
                            ->defaultValue('/offertool/offers/%%Y/%%m')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $offerTool;
    }

    private function buildTrackingManagerNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $trackingManager = $builder->root('tracking_manager');
        $trackingManager->addDefaultsIfNotSet();
        $trackingManager
            ->children()
                ->scalarNode('tracking_manager_id')
                    ->defaultValue(TrackingManager::class)
                ->end()
                ->arrayNode('trackers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->canBeDisabled()
                        ->children()
                            ->scalarNode('id')
                                ->isRequired()
                            ->end()
                            ->append($this->buildOptionsNode())
                            ->scalarNode('item_builder_id')
                                ->defaultValue(TrackingItemBuilder::class)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $trackingManager;
    }

    private function buildOptionsNode(string $name = 'options', array $defaultValue = []): NodeDefinition
    {
        $node = new VariableNodeDefinition($name);
        $node
            ->defaultValue($defaultValue)
            ->treatNullLike([])
            ->beforeNormalization()
               ->castToArray()
            ->end();

        return $node;
    }

    /**
     * Normalizes properties from old to new names to easy migration
     *
     * @param array $data
     * @param array $map
     *
     * @return array
     */
    private function remapProperties(array $data, array $map): array
    {
        foreach ($map as $old => $new) {
            if (isset($data[$old]) && !isset($data[$new])) {
                $data[$new] = $data[$old];
                unset($data[$old]);
            }
        }

        return $data;
    }
}
