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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use Pimcore\Cache\Pool\Redis;
use Pimcore\Cache\Pool\Redis\ConnectionFactory;
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

        $rootNode = $treeBuilder->root('pimcore');
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->arrayNode('error_handling')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('render_error_document')
                            ->info('Render error document in case of an error instead of showing Symfony\'s error page')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('bundles')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('search_paths')
                            ->prototype('scalar')->end()
                        ->end()
                        ->booleanNode('handle_composer')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addObjectsNode($rootNode);
        $this->addDocumentsNode($rootNode);
        $this->addModelsNode($rootNode);

        $this->addCacheNode($rootNode);
        $this->addContextNode($rootNode);
        $this->addAdminNode($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $extensionsNode
     */
    protected function addModelsNode(ArrayNodeDefinition $extensionsNode)
    {
        $extensionsNode
            ->children()
            ->arrayNode('models')
            ->addDefaultsIfNotSet()
                ->children()
                ->arrayNode('class_overrides')
                ->prototype('scalar')
                ->end();
    }

    /**
     * Add object specific extension config
     *
     * @param ArrayNodeDefinition $extensionsNode
     */
    protected function addObjectsNode(ArrayNodeDefinition $extensionsNode)
    {
        $objectsNode = $extensionsNode
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet();

        $classDefinitionsNode = $objectsNode
            ->children()
                ->arrayNode('class_definitions')
                    ->addDefaultsIfNotSet();

        $this->addImplementationLoaderNode($classDefinitionsNode, 'data');
        $this->addImplementationLoaderNode($classDefinitionsNode, 'layout');
    }

    /**
     * Add document specific extension config
     *
     * @param ArrayNodeDefinition $extensionsNode
     */
    protected function addDocumentsNode(ArrayNodeDefinition $extensionsNode)
    {
        $documentsNode = $extensionsNode
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
                                ->defaultTrue()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Add implementation node config (map, prefixes)
     *
     * @param ArrayNodeDefinition $node
     * @param string $name
     */
    protected function addImplementationLoaderNode(ArrayNodeDefinition $node, $name)
    {
        $node
            ->children()
                ->arrayNode($name)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('map')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('prefixes')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
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
        $defaultOptions = ConnectionFactory::getDefaultOptions();

        $rootNode->children()
            ->arrayNode('cache')
            ->canBeDisabled()
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
                                    ->arrayNode('connection')
                                        ->info('Redis connection options. See ' . ConnectionFactory::class)
                                        ->children()
                                            ->scalarNode('server')->end()
                                            ->integerNode('port')
                                                ->defaultValue($defaultOptions['port'])
                                            ->end()
                                            ->integerNode('database')
                                                ->defaultValue($defaultOptions['database'])
                                            ->end()
                                            ->scalarNode('password')
                                                ->defaultValue($defaultOptions['password'])
                                            ->end()
                                            ->scalarNode('persistent')
                                                ->defaultValue($defaultOptions['persistent'])
                                            ->end()
                                            ->booleanNode('force_standalone')
                                                ->defaultValue($defaultOptions['force_standalone'])
                                            ->end()
                                            ->integerNode('connect_retries')
                                                ->defaultValue($defaultOptions['connect_retries'])
                                            ->end()
                                            ->floatNode('timeout')
                                                ->defaultValue($defaultOptions['timeout'])
                                            ->end()
                                            ->floatNode('read_timeout')
                                                ->defaultValue($defaultOptions['read_timeout'])
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('options')
                                        ->info('Redis cache pool options. See ' . Redis::class)
                                        ->children()
                                            ->booleanNode('notMatchingTags')->end()
                                            ->integerNode('compress_tags')->end()
                                            ->integerNode('compress_data')->end()
                                            ->integerNode('compress_threshold')->end()
                                            ->scalarNode('compression_lib')->end()
                                            ->booleanNode('use_lua')->end()
                                            ->integerNode('lua_max_c_stack')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
    }
}
