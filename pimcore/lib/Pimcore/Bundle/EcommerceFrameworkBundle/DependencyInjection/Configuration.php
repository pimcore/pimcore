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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\MultiCartManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\SessionEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingManager;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\VarDumper\VarDumper;

class Configuration implements ConfigurationInterface
{
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
            ->append($this->buildTrackingManagerNode())
        ;

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
            ->children()
                ->scalarNode('cart_manager_id')
                    ->defaultValue(MultiCartManager::class)
                ->end()
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
                                'options' => [
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
                            return $v;
                        }

                        return $this->mergeTenantConfig($v);
                    })
                    ->end()
                    ->prototype('array')
                        ->canBeDisabled()
                        ->children()
                            ->arrayNode('cart')
                                ->isRequired()
                                ->children()
                                    ->scalarNode('factory_id')->isRequired()->end()
                                    ->append($this->buildOptionsNode())
                                ->end()
                            ->end()
                            ->arrayNode('price_calculator')
                                ->isRequired()
                                ->children()
                                    ->scalarNode('factory_id')->isRequired()->end()
                                    ->append($this->buildOptionsNode())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $cartManager;
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

    private function buildOptionsNode(string $name = 'options'): NodeDefinition
    {
        $node = new VariableNodeDefinition($name);
        $node
            ->defaultValue([])
            ->treatNullLike([])
            ->beforeNormalization()
               ->castToArray()
            ->end();

        return $node;
    }

    /**
     * Merges tenant configs with an optional _defaults key which is applied
     * to every tenant and removed.
     *
     * @param array $config
     *
     * @return array
     */
    private function mergeTenantConfig(array $config): array
    {
        // check if a _defaults tenant is set and merge its config into all defined
        // tenants
        $defaults = [];
        if (isset($config['_defaults'])) {
            $defaults = $config['_defaults'];
            unset($config['_defaults']);
        }

        foreach ($config as $tenant => $tenantConfig) {
            // tenants starting with _defaults are not included in the final config
            // but can be used for yaml inheritance
            if (preg_match('/^_defaults/i', $tenant)) {
                unset($config[$tenant]);
                continue;
            }

            $config[$tenant] = $this->mergeDefaults($defaults, $tenantConfig);
        }

        // if no default tenant is set, use the _defaults as default tenant
        if (!isset($config['default']) && !empty($defaults)) {
            $config['default'] = $defaults;
        }

        return $config;
    }

    /**
     * Merges defaults with values but does not transform scalars into arrays as array_merge_recursive does
     *
     * @param array $defaults
     * @param array $values
     *
     * @return array
     */
    private function mergeDefaults(array $defaults, array $values): array
    {
        foreach ($defaults as $k => $v) {
            if (!isset($values[$k]) || (is_array($values[$k]) && empty($values[$k]))) {
                $values[$k] = $v;
            } else {
                if (!is_array($v)) {
                    // only merging arrays
                    continue;
                }

                if (!is_array($values[$k])) {
                    throw new InvalidConfigurationException(sprintf(
                        'Can\'t merge defaults key %s as defaults is an array while the value to merge is a %s',
                        $k, gettype($values[$k])
                    ));
                }

                if ($this->isArrayAssociative($v)) {
                    $values[$k] = $this->mergeDefaults($defaults[$k], $values[$k]);
                } else {
                    $values[$k] = array_merge($defaults[$k], $values[$k]);
                }
            }
        }

        return $values;
    }

    /**
     * Checks if array is associative or sequential
     *
     * @see https://stackoverflow.com/a/173479/9131
     *
     * @param array $array
     *
     * @return bool
     */
    private function isArrayAssociative(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
