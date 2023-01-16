<?php

namespace Pimcore\Bundle\UuidBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_uuid');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('instance_identifier')
                ->defaultNull()
                ->info('UUID instance identifier. Has to be unique throughout multiple Pimcore instances. UUID generation will be automatically enabled if a Instance identifier is provided (do not change the instance identifier afterwards - this will cause invalid UUIDs)')
                ->end()
            ->end();
        return $treeBuilder;
    }
}
