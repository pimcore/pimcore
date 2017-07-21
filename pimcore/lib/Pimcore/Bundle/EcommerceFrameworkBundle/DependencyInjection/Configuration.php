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
use Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool\DefaultService as DefaultOfferToolService;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\AgentFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Environment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule;
use Pimcore\Bundle\EcommerceFrameworkBundle\SessionEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\Processor\PlaceholderProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\Processor\TenantProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\DefaultService as DefaultVoucherService;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\TokenManagerFactory;
use Pimcore\Model\Object\OfferToolOffer;
use Pimcore\Model\Object\OfferToolOfferItem;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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

    public function __construct()
    {
        $this->tenantProcessor      = new TenantProcessor();
        $this->placeholderProcessor = new PlaceholderProcessor();
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
            ->append($this->buildEnvironmentNode())
            ->append($this->buildCartManagerNode())
            ->append($this->buildOrderManagerNode())
            ->append($this->buildPricingManagerNode())
            ->append($this->buildPriceSystemsNode())
            ->append($this->buildAvailabilitySystemsNode())
            ->append($this->buildProductIndexNode())
            ->append($this->buildVoucherServiceNode())
            ->append($this->buildOfferToolNode())
            ->append($this->buildTrackingManagerNode());

        return $treeBuilder;
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
                        ->canBeDisabled()
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
                        ->canBeDisabled()
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

    private function buildProductIndexNode(): NodeDefinition
    {
        $builder = new TreeBuilder();

        $productIndex = $builder->root('product_index');
        $productIndex->addDefaultsIfNotSet();

        $productIndex
            ->children()
                ->booleanNode('disable_default_tenant')
                    ->defaultFalse()
                ->end()
                ->arrayNode('tenants')
                    ->info('Configuration per tenant. If a _defaults key is set, it will be merged into every tenant.')
                    ->useAttributeAsKey('name')
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
                        }

                        return $config;
                    })
                    ->end()
                    ->prototype('array')
                        ->canBeDisabled()
                        ->children()
                            ->scalarNode('config_id')
                                ->isRequired()
                            ->end()
                            ->arrayNode('placeholders')
                                ->defaultValue([])
                                ->beforeNormalization()
                                    ->castToArray()
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('search_attributes')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function ($v) {
                                            return ['name' => $v];
                                        })
                                    ->end()
                                    ->children()
                                        ->scalarNode('name')->isRequired()->end()
                                    ->end()
                                ->end()
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
                                    ->children()
                                        ->scalarNode('name')->isRequired()->end()
                                        ->scalarNode('fieldname')->defaultNull()->end()
                                        ->scalarNode('type')->defaultNull()->end()
                                        ->scalarNode('interpreter')->defaultNull()->end()
                                        ->scalarNode('getter')->defaultNull()->end()
                                        ->scalarNode('filtergroup')->defaultNull()->end()
                                        ->scalarNode('locale')->defaultNull()->end()
                                        ->variableNode('options')->defaultValue([])->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $productIndex;
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
                                ->defaultNull()
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
}
