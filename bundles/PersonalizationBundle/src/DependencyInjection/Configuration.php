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

namespace Pimcore\Bundle\PersonalizationBundle\DependencyInjection;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\CookieStorage;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\TargetingStorageInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_personalization');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('targeting')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('storage_id')
                            ->info('Service ID of the targeting storage which should be used. This ID will be aliased to ' . TargetingStorageInterface::class)
                            ->defaultValue(CookieStorage::class)
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('session')
                            ->info('Enables HTTP session support by configuring session bags and the full page cache')
                            ->canBeEnabled()
                        ->end()
                        ->arrayNode('data_providers')
                            ->useAttributeAsKey('key')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                        ->arrayNode('conditions')
                            ->useAttributeAsKey('key')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                        ->arrayNode('action_handlers')
                            ->useAttributeAsKey('name')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
