<?php

namespace Pimcore\Bundle\PersonalizationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\TargetingStorageInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\CookieStorage;

class Configuration implements ConfigurationInterface {
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder {
        $treeBuilder = new TreeBuilder('pimcore_personalization');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode ();

        $rootNode
            ->children()
                ->arrayNode('targeting')
                    ->canBeDisabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('storage_id')
                            ->info('Service ID of the targeting storage which should be used. This ID will be aliased to ' . TargetingStorageInterface::class)
                            ->defaultValue(CookieStorage::class)
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode ('session')
                            ->info ('Enables HTTP session support by configuring session bags and the full page cache')
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
