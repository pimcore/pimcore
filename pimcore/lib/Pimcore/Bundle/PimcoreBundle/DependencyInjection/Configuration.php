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

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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

        $this->addCacheNode($rootNode);
        $this->addContextNode($rootNode);
        $this->addAdminNode($rootNode);
        $this->addDocumentsNode($rootNode);
        $this->addObjectsNode($rootNode);

        return $treeBuilder;
    }

    /**
     * Add document specific config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addDocumentsNode(ArrayNodeDefinition $rootNode)
    {
        $documentsNode = $rootNode
            ->children()
                ->arrayNode('documents')
                    ->addDefaultsIfNotSet();

        $this->addImplementationLoaderNode($documentsNode, 'tags');

        $documentsNode
            ->children()
                ->arrayNode('areas')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('autoload')
                            ->defaultTrue();
    }

    /**
     * Add object specific config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addObjectsNode(ArrayNodeDefinition $rootNode)
    {
        $objectsNode = $rootNode
            ->children()
                ->arrayNode('objects');

        $classDefinitionsNode = $objectsNode
            ->children()
                ->arrayNode('class_definitions');

        $this->addImplementationLoaderNode($classDefinitionsNode, 'data');
        $this->addImplementationLoaderNode($classDefinitionsNode, 'layout');
    }

    /**
     * Add implementation node config (map, prefixes)
     *
     * @param ArrayNodeDefinition $node
     * @param string $name
     */
    protected function addImplementationLoaderNode(ArrayNodeDefinition $node, $name)
    {
        $children = $node
            ->children()
            ->arrayNode($name)
                ->children();

        $children->arrayNode('map')
            ->useAttributeAsKey('name')
            ->prototype('scalar');

        $children
            ->arrayNode('prefixes')
            ->prototype('scalar');
    }

    /**
     * Add context config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addContextNode(ArrayNodeDefinition $rootNode)
    {
        $contextNode = $rootNode->children()
            ->arrayNode('context');

        /** @var ArrayNodeDefinition|NodeDefinition $prototype */
        $prototype = $contextNode->prototype('array');

        // define routes child on each context entry
        $this->addRoutesChild($prototype, 'routes');
    }

    /**
     * Add admin config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addAdminNode(ArrayNodeDefinition $rootNode)
    {
        $adminNode = $rootNode->children()
            ->arrayNode('admin')
            ->addDefaultsIfNotSet();

        // unauthenticated routes won't be double checked for authentication in AdminControllerListener
        $this->addRoutesChild($adminNode, 'unauthenticated_routes');

        $adminNode->children()
            ->arrayNode("translations")
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode("path")->defaultNull()->end()
            ->end();
    }

    /**
     * Add a route prototype child
     *
     * @param ArrayNodeDefinition $parent
     * @param $name
     */
    protected function addRoutesChild(ArrayNodeDefinition $parent, $name)
    {
        $node = $parent->children()->arrayNode($name);

        /** @var ArrayNodeDefinition|NodeDefinition $prototype */
        $prototype = $node->prototype('array');
        $prototype
            ->children()
                ->scalarNode('path')->defaultFalse()->end()
                ->scalarNode('route')->defaultFalse()->end()
                ->scalarNode('host')->defaultFalse()->end()
                ->arrayNode('methods')
                    ->prototype('scalar')->end()
                ->end()
            ->end();
    }

    /**
     * Add cache config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addCacheNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()
            ->arrayNode('cache')
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('pool_service_id')
                        ->defaultValue(null)
                    ->end()
                    ->integerNode('default_lifetime')
                        ->defaultValue(2419200) // 28 days
                    ->end()
                    ->arrayNode('pools')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('doctrine')
                                ->canBeDisabled()
                                ->children()
                                    ->scalarNode('connection')
                                        ->defaultValue('default')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('redis')
                                ->canBeEnabled()
                                ->children()
                                    // TODO define available config values and defaults
                                    ->variableNode('connection')
                                        ->defaultValue([])
                                    ->end()

                                    // TODO define available config values and defaults
                                    ->variableNode('options')
                                        ->defaultValue([])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
    }
}
