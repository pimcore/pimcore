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
                ->arrayNode('flags')
                    ->info('Generic map for feature flags, such as `zend_date`')
                    ->prototype('scalar')
                    ->end()
            ->end();

        $this->addObjectsNode($rootNode);
        $this->addDocumentsNode($rootNode);
        $this->addModelsNode($rootNode);

        $this->addCacheNode($rootNode);
        $this->addContextNode($rootNode);
        $this->addAdminNode($rootNode);
        $this->addWebProfilerNode($rootNode);

        $this->addSecurityNode($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addModelsNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
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
     * @param ArrayNodeDefinition $rootNode
     */
    private function addObjectsNode(ArrayNodeDefinition $rootNode)
    {
        $objectsNode = $rootNode
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
     * @param ArrayNodeDefinition $rootNode
     */
    private function addDocumentsNode(ArrayNodeDefinition $rootNode)
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
    private function addImplementationLoaderNode(ArrayNodeDefinition $node, $name)
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
    private function addContextNode(ArrayNodeDefinition $rootNode)
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
    private function addAdminNode(ArrayNodeDefinition $rootNode)
    {
        $adminNode = $rootNode->children()
            ->arrayNode('admin')
            ->addDefaultsIfNotSet();

        // add session attribute bag config
        $this->addAdminSessionAttributeBags($adminNode);

        // unauthenticated routes won't be double checked for authentication in AdminControllerListener
        $this->addRoutesChild($adminNode, 'unauthenticated_routes');

        $adminNode
            ->children()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $adminNode
     */
    private function addAdminSessionAttributeBags(ArrayNodeDefinition $adminNode)
    {
        // Normalizes session bag config. Allows the following formats (all formats will be
        // normalized to the third format.
        //
        // attribute_bags:
        //      - foo
        //      - bar
        //
        // attribute_bags:
        //      foo: _foo
        //      bar: _bar
        //
        // attribute_bags:
        //      foo:
        //          storage_key: _foo
        //      bar:
        //          storage_key: _bar
        $normalizers = [
            'assoc' => function (array $array) {
                $result = [];
                foreach ($array as $name => $value) {
                    if (null === $value) {
                        $value = [
                            'storage_key' => '_' . $name
                        ];
                    }

                    if (is_string($value)) {
                        $value = [
                            'storage_key' => $value
                        ];
                    }

                    $result[$name] = $value;
                }

                return $result;
            },

            'sequential' => function (array $array) {
                $result = [];
                foreach ($array as $name) {
                    $result[$name] = [
                        'storage_key' => '_' . $name
                    ];
                }

                return $result;
            }
        ];

        $adminNode
            ->children()
                ->arrayNode('session')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('attribute_bags')
                            ->useAttributeAsKey('name')
                            ->beforeNormalization()
                                ->ifArray()->then(function ($v) use ($normalizers) {
                                    if (isAssocArray($v)) {
                                        return $normalizers['assoc']($v);
                                    } else {
                                        return $normalizers['sequential']($v);
                                    }
                                })
                            ->end()
                            ->example([
                                ['foo', 'bar'],
                                [
                                    'foo' => '_foo',
                                    'bar' => '_bar',
                                ],
                                [
                                    'foo' => [
                                        'storage_key' => '_foo'
                                    ],
                                    'bar' => [
                                        'storage_key' => '_bar'
                                    ]
                                ]
                            ])
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('storage_key')
                                        ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addSecurityNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('encoder_factories')
                            ->info('Encoder factories to use as className => factory service ID mapping')
                            ->example([
                                'AppBundle\Model\Object\User1' => [
                                    'id' => 'website_demo.security.encoder_factory2'
                                ],
                                'AppBundle\Model\Object\User2' => 'website_demo.security.encoder_factory2'
                            ])
                            ->useAttributeAsKey('class')
                            ->prototype('array')
                            ->beforeNormalization()->ifString()->then(function ($v) {
                                return ['id' => $v];
                            })->end()
                            ->children()
                                ->scalarNode('id')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Configure exclude paths for web profiler toolbar
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addWebProfilerNode(ArrayNodeDefinition $rootNode)
    {
        $webProfilerNode = $rootNode->children()
            ->arrayNode('web_profiler')
                ->example([
                    'toolbar' => [
                        'excluded_routes' => [
                            ['path' => '^/test/path']
                        ]
                    ]
                ])
                ->addDefaultsIfNotSet();

        $toolbarNode = $webProfilerNode->children()
            ->arrayNode('toolbar')
                ->addDefaultsIfNotSet();

        $this->addRoutesChild($toolbarNode, 'excluded_routes');
    }

    /**
     * Add a route prototype child
     *
     * @param ArrayNodeDefinition $parent
     * @param $name
     */
    private function addRoutesChild(ArrayNodeDefinition $parent, $name)
    {
        $node = $parent->children()->arrayNode($name);

        /** @var ArrayNodeDefinition|NodeDefinition $prototype */
        $prototype = $node->prototype('array');
        $prototype
            ->beforeNormalization()
                ->ifNull()->then(function () {
                    return [];
                })
            ->end()
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
    private function addCacheNode(ArrayNodeDefinition $rootNode)
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
