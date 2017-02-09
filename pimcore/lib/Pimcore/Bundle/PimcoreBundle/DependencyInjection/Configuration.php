<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('pimcore');

        $rootNode
            ->children()
                ->arrayNode('documents')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('areas')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('autoload')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end() // areas
                    ->end()
                ->end() // document
            ->end();

        return $treeBuilder;
    }
}
