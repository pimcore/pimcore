<?php

namespace Pimcore\Bundle\StaticRoutesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_static_routes');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->arrayNode('definitions')
                ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('pattern')->end()
                            ->scalarNode('reverse')->end()
                            ->scalarNode('controller')->end()
                            ->scalarNode('variables')->end()
                            ->scalarNode('defaults')->end()
                            ->arrayNode('siteId')
                                ->integerPrototype()->end()
                            ->end()
                            ->arrayNode('methods')
                                ->scalarPrototype()->end()
                            ->end()
                            ->integerNode('priority')->end()
                            ->integerNode('creationDate')->end()
                            ->integerNode('modificationDate')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
