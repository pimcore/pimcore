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

use Pimcore\Bundle\CoreBundle\DependencyInjection\Config\Processor\PlaceholderProcessor;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Storage\Redis\ConnectionFactory;
use Pimcore\Targeting\Storage\CookieStorage;
use Pimcore\Targeting\Storage\TargetingStorageInterface;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Pimcore\Workflow\Notification\NotificationEmailService;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var PlaceholderProcessor
     */
    private $placeholderProcessor;

    private $placeholders = [];

    public function __construct()
    {
        $this->placeholderProcessor = new PlaceholderProcessor();
        $this->placeholders = [];
    }

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
        $rootNode->ignoreExtraKeys();

        $rootNode
            ->children()
                ->arrayNode('error_handling')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('render_error_document')
                            ->info('Render error document in case of an error instead of showing Symfony\'s error page')
                            ->defaultTrue()
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return (bool)$v;
                                })
                            ->end()
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
                    ->info('Generic map for feature flags')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('case_insensitive')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return (bool)$v;
                                })
                            ->end()
                            ->info('Force Pimcore translations to NOT be case sensitive. This only applies to translations set via Pimcore\'s translator (e.g. website translations)')
                            ->defaultFalse()
                        ->end()

                        ->arrayNode('admin_translation_mapping')
                            ->useAttributeAsKey('locale')
                            ->prototype('scalar')->end()
                        ->end()

                        ->arrayNode('debugging')
                            ->info('If debugging is enabled, the translator will return the plain translation key instead of the translated message.')
                            ->addDefaultsIfNotSet()
                            ->canBeDisabled()
                            ->children()
                                ->scalarNode('parameter')
                                    ->defaultValue('pimcore_debug_translations')
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('data_object')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('translation_extractor')
                                    ->children()
                                        ->arrayNode('attributes')
                                            ->info('Can be used to restrict the extracted localized fields (e.g. used by XLIFF exporter in the Pimcore backend)')
                                            ->prototype('array')
                                                ->prototype('scalar')->end()
                                            ->end()
                                            ->example(
                                                [
                                                    'Product' => ['name', 'description'],
                                                    'Brand' => ['name'],
                                                ]
                                            )
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('maps')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('tile_layer_url_template')
                            ->defaultValue('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png')
                        ->end()
                        ->scalarNode('geocoding_url_template')
                            ->defaultValue('https://nominatim.openstreetmap.org/search?q={q}&addressdetails=1&format=json&limit=1')
                        ->end()
                        ->scalarNode('reverse_geocoding_url_template')
                            ->defaultValue('https://nominatim.openstreetmap.org/reverse?format=json&lat={lat}&lon={lon}&addressdetails=1')
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addGeneralNode($rootNode);
        $this->addMaintenanceNode($rootNode);
        $this->addServicesNode($rootNode);
        $this->addObjectsNode($rootNode);
        $this->addAssetNode($rootNode);
        $this->addDocumentsNode($rootNode);
        $this->addEncryptionNode($rootNode);
        $this->addModelsNode($rootNode);
        $this->addRoutingNode($rootNode);
        $this->addCacheNode($rootNode);
        $this->addContextNode($rootNode);
        $this->addAdminNode($rootNode);
        $this->addWebProfilerNode($rootNode);
        $this->addSecurityNode($rootNode);
        $this->addEmailNode($rootNode);
        $this->addNewsletterNode($rootNode);
        $this->addCustomReportsNode($rootNode);
        $this->addMigrationsNode($rootNode);
        $this->addTargetingNode($rootNode);
        $this->addSitemapsNode($rootNode);
        $this->addMimeNode($rootNode);
        $this->addWorkflowNode($rootNode);
        $this->addHttpClientNode($rootNode);
        $this->addApplicationLogNode($rootNode);

        return $treeBuilder;
    }

    /**
     * Add maintenance config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addMaintenanceNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
            ->arrayNode('maintenance')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('housekeeping')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('cleanup_tmp_files_atime_older_than')
                        ->defaultValue(7776000) // 90 days
                    ->end()
                    ->integerNode('cleanup_profiler_files_atime_older_than')
                        ->defaultValue(1800)
                    ->end()
        ;
    }

    /**
     * Add general config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addGeneralNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
            ->arrayNode('general')
            ->ignoreExtraKeys()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('timezone')
                    ->defaultValue('Europe/Berlin')
                ->end()
                ->scalarNode('path_variable')
                    ->defaultNull()
                ->end()
                ->scalarNode('domain')
                    ->defaultNull()
                ->end()
                ->booleanNode('redirect_to_maindomain')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return (bool)$v;
                        })
                    ->end()
                    ->defaultFalse()
                ->end()
                ->scalarNode('language')
                    ->defaultValue('en')
                ->end()
                ->scalarNode('valid_languages')
                    ->defaultValue('en')
                ->end()
                ->arrayNode('fallback_languages')
                    ->performNoDeepMerging()
                    ->beforeNormalization()
                    ->ifArray()
                        ->then(function ($v) {
                            return $v;
                        })
                    ->end()
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->scalarNode('default_language')
                    ->defaultValue('en')
                ->end()
                ->booleanNode('disable_usage_statistics')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return (bool)$v;
                        })
                    ->end()
                    ->defaultFalse()
                ->end()
                ->booleanNode('debug_admin_translations')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return (bool)$v;
                        })
                    ->end()
                    ->defaultFalse()
                ->end()
                ->scalarNode('instance_identifier')
                    ->defaultNull()->end()
                ->booleanNode('show_cookie_notice')
                    ->setDeprecated('The cookie bar will be removed in Pimcore 7')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return (bool)$v;
                        })
                    ->end()
                    ->defaultFalse()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addServicesNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
            ->arrayNode('services')
                ->children()
                    ->arrayNode('google')
                    ->children()
                        ->scalarNode('client_id')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('email')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('simple_api_key')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('browser_api_key')
                            ->defaultNull()
                        ->end()
                    ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('webservice')
                ->canBeEnabled()
            ->end();
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
                            ->useAttributeAsKey('name')
                            ->prototype('scalar');
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addHttpClientNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('httpclient')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('adapter')
                            ->defaultValue('Socket')
                        ->end()
                        ->scalarNode('proxy_host')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('proxy_port')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('proxy_user')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('proxy_pass')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addApplicationLogNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('applicationlog')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mail_notification')
                            ->children()
                                ->booleanNode('send_log_summary')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function ($v) {
                                            return (bool)$v;
                                        })
                                    ->end()
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('filter_priority')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('mail_receiver')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('archive_treshold')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('archive_alternative_database')
                            ->defaultValue('')
                        ->end()
                    ->end()
            ->end();
    }

    /**
     * Add asset specific extension config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addAssetNode(ArrayNodeDefinition $rootNode)
    {
        $assetsNode = $rootNode
            ->children()
                ->arrayNode('assets')
                ->ignoreExtraKeys()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('preview_image_thumbnail')
                        ->defaultNull()
                        ->end()
                    ->scalarNode('default_upload_path')
                        ->defaultValue('_default_upload_bucket')
                        ->end()
                    ->integerNode('tree_paging_limit')
                        ->defaultValue(100)
                        ->end()
                    ->arrayNode('image')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('low_quality_image_preview')
                                ->addDefaultsIfNotSet()
                                ->canBeDisabled()
                                ->children()
                                    ->scalarNode('generator')
                                    ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('focal_point_detection')
                                ->addDefaultsIfNotSet()
                                ->canBeDisabled()
                            ->end()
                            ->arrayNode('thumbnails')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('webp_auto_support')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) {
                                                return (bool)$v;
                                            })
                                        ->end()
                                        ->defaultTrue()
                                    ->end()
                                    ->booleanNode('clip_auto_support')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) {
                                                return (bool)$v;
                                            })
                                        ->end()
                                        ->defaultTrue()
                                    ->end()
                                    ->booleanNode('auto_clear_temp_files')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) {
                                                return (bool)$v;
                                            })
                                        ->end()
                                        ->defaultTrue()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('video')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('thumbnails')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('auto_clear_temp_files')
                                    ->defaultTrue()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('versions')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('days')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('steps')
                                ->defaultNull()
                            ->end()
                            ->booleanNode('use_hardlinks')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) {
                                        return (bool)$v;
                                    })
                                ->end()
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('disable_stack_trace')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) {
                                        return (bool)$v;
                                    })
                                ->end()
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('icc_rgb_profile')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('icc_cmyk_profile')
                        ->defaultNull()
                    ->end()
                    ->booleanNode('hide_edit_image')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('disable_tree_preview')
                        ->defaultTrue()
                    ->end()
                ->end();

        $assetsNode
            ->children()
                ->arrayNode('metadata')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('class_definitions')
                            ->children()
                                ->arrayNode('data')
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

                            ->end()
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
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('tree_paging_limit')
                            ->defaultValue(30)
                        ->end()
                        ->arrayNode('versions')
                            ->children()
                                ->scalarNode('days')->defaultNull()->end()
                                ->scalarNode('steps')->defaultNull()->end()
                                ->booleanNode('disable_stack_trace')
                                    ->beforeNormalization()
                                    ->ifString()
                                        ->then(function ($v) {
                                            return (bool)$v;
                                        })
                                    ->end()
                                    ->defaultFalse()
                                ->end()
                            ->end()
                        ->end()
                    ->end();
        $classDefinitionsNode = $objectsNode
            ->children()
                ->arrayNode('class_definitions')
                    ->addDefaultsIfNotSet();

        $this->addImplementationLoaderNode($classDefinitionsNode, 'data');
        $this->addImplementationLoaderNode($classDefinitionsNode, 'layout');
    }

    /**
     * Add encryption specific extension config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addEncryptionNode(ArrayNodeDefinition $rootNode)
    {
        $encryptionNode = $rootNode
            ->children()
            ->arrayNode('encryption')->addDefaultsIfNotSet();

        $encryptionNode
            ->children()
            ->scalarNode('secret')->defaultNull();
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
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet();

        $documentsNode
            ->children()
                ->arrayNode('tags')
                    ->setDeprecated('The "%node%" option is deprecated. Use "editables" instead.')
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
                ->arrayNode('versions')
                    ->children()
                        ->scalarNode('days')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('steps')
                            ->defaultNull()
                        ->end()
                        ->booleanNode('disable_stack_trace')
                            ->beforeNormalization()
                            ->ifString()
                                ->then(function ($v) {
                                    return (bool)$v;
                                })
                            ->end()
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('error_pages')
                    ->children()
                        ->scalarNode('default')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('create_redirect_when_moved')
                    ->setDeprecated('The "%node%" option is deprecated and not used anymore, it is just there for compatibility.')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return (bool)$v;
                        })
                    ->end()
                    ->defaultFalse()
                ->end()
                ->scalarNode('allow_trailing_slash')
                    ->defaultValue('no')
                ->end()
                ->booleanNode('generate_preview')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return (bool)$v;
                        })
                    ->end()
                    ->defaultFalse()
                ->end()
                ->integerNode('tree_paging_limit')
                    ->defaultValue(50)
                ->end()
                ->arrayNode('editables')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('map')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('prefixes')
                            ->prototype('scalar')->end()
                        ->end()
                        ->enumNode('naming_strategy')
                            ->info('Sets naming strategy used to build editable names')
                            ->values(['legacy', 'nested'])
                            ->defaultValue('nested')
                            ->setDeprecated('The "%node%" option is deprecated. Migrate to the new editable naming scheme!')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('areas')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('autoload')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return (bool)$v;
                                })
                            ->end()
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('newsletter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('defaultUrlPrefix')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('web_to_print')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('pdf_creation_php_memory_limit')
                            ->defaultValue('2048M')
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

    private function addRoutingNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('routing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('defaults')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('bundle')
                                    ->defaultValue('AppBundle')
                                ->end()
                                ->scalarNode('controller')
                                    ->defaultValue('Default')
                                ->end()
                                ->scalarNode('action')
                                    ->defaultValue('default')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('static')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('locale_params')
                                    ->info('Route params from this list will be mapped to _locale if _locale is not set explicitely')
                                    ->prototype('scalar')
                                    ->defaultValue([])
                                ->end()
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
            ->ignoreExtraKeys()
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
                            'storage_key' => '_' . $name,
                        ];
                    }

                    if (is_string($value)) {
                        $value = [
                            'storage_key' => $value,
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
                        'storage_key' => '_' . $name,
                    ];
                }

                return $result;
            },
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
                                        'storage_key' => '_foo',
                                    ],
                                    'bar' => [
                                        'storage_key' => '_bar',
                                    ],
                                ],
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
                                'AppBundle\Model\DataObject\User1' => [
                                    'id' => 'website_demo.security.encoder_factory2',
                                ],
                                'AppBundle\Model\DataObject\User2' => 'website_demo.security.encoder_factory2',
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
                            ['path' => '^/test/path'],
                        ],
                    ],
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
     * @param string $name
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
            ->arrayNode('full_page_cache')
                ->ignoreExtraKeys()
                ->canBeDisabled()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('lifetime')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('exclude_patterns')->end()
                    ->scalarNode('exclude_cookie')->end()
                ->end()
            ->end()
            ->arrayNode('cache')
                ->ignoreExtraKeys()
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
                                            ->scalarNode('database')
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

    /**
     * Adds configuration for email source adapters
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addEmailNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('email')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('sender')
                            ->children()
                                ->scalarNode('name')->end()
                                ->scalarNode('email')->end()
                            ->end()
                        ->end()
                        ->arrayNode('return')
                            ->children()
                                ->scalarNode('name')->end()
                                ->scalarNode('email')->end()
                            ->end()
                        ->end()
                        ->scalarNode('method')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('debug')
                            ->children()
                                ->scalarNode('email_addresses')
                                    ->defaultValue('')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('usespecific')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds configuration tree for newsletter source adapters
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addNewsletterNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('newsletter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('sender')
                            ->children()
                                ->scalarNode('name')->end()
                                ->scalarNode('email')->end()
                            ->end()
                        ->end()
                        ->arrayNode('return')
                            ->children()
                                ->scalarNode('name')->end()
                                ->scalarNode('email')->end()
                            ->end()
                        ->end()
                        ->scalarNode('method')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('debug')
                            ->children()
                                ->scalarNode('email_addresses')
                                    ->defaultValue('')
                                ->end()
                            ->end()
                        ->end()
                        ->booleanNode('use_specific')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return (bool)$v;
                                })
                            ->end()
                        ->end()
                        ->arrayNode('source_adapters')
                            ->useAttributeAsKey('name')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds configuration tree for custom report adapters
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addCustomReportsNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('custom_report')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('adapters')
                            ->useAttributeAsKey('name')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds configuration tree node for migrations
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addMigrationsNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('migrations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('sets')
                            ->useAttributeAsKey('identifier')
                            ->defaultValue([])
                            ->info('Migration sets which can be used apart from bundle migrations. Use the -s option in migration commands to select a specific set.')
                            ->example([
                                [
                                    'custom_set' => [
                                        'name' => 'Custom Migrations',
                                        'namespace' => 'App\\Migrations\\Custom',
                                        'directory' => 'src/App/Migrations/Custom',
                                    ],
                                    'custom_set_2' => [
                                        'name' => 'Custom Migrations 2',
                                        'namespace' => 'App\\Migrations\\Custom2',
                                        'directory' => 'src/App/Migrations/Custom2',
                                        'connection' => 'custom_connection',
                                    ],
                                ],
                            ])
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('identifier')->end()
                                    ->scalarNode('name')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('namespace')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('directory')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('connection')
                                        ->info('If defined, the DBAL connection defined here will be used')
                                        ->defaultNull()
                                        ->beforeNormalization()
                                            ->ifTrue(function ($v) {
                                                return empty(trim($v));
                                            })
                                            ->then(function () {
                                                return null;
                                            })
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addTargetingNode(ArrayNodeDefinition $rootNode)
    {
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
    }

    private function addSitemapsNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('sitemaps')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('generators')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) {
                                        return [
                                            'enabled' => true,
                                            'generator_id' => $v,
                                            'priority' => 0,
                                        ];
                                    })
                                ->end()
                                ->addDefaultsIfNotSet()
                                ->canBeDisabled()
                                ->children()
                                    ->scalarNode('generator_id')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->integerNode('priority')
                                        ->defaultValue(0)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addMimeNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('mime')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('extensions')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addWorkflowNode(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                 ->arrayNode('workflows')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->arrayNode('placeholders')
                                    ->info('Placeholder values in this workflow configuration (locale: "%%locale%%") will be replaced by the given placeholder value (eg. "de_AT")')
                                    ->example([
                                        'placeholders' => [
                                            '%%locale%%' => 'de_AT',
                                        ],
                                    ])
                                    ->defaultValue([])
                                    ->beforeNormalization()
                                        ->castToArray()
                                        ->always()
                                        ->then(function ($placeholders) {
                                            $this->placeholders = $placeholders;

                                            return $placeholders;
                                        })
                                    ->end()
                                    ->prototype('scalar')->end()
                                ->end()
                                ->booleanNode('enabled')
                                    ->defaultTrue()
                                    ->info('Can be used to enable or disable the workflow.')
                                ->end()
                                ->integerNode('priority')
                                    ->defaultValue(0)
                                    ->info('When multiple custom view or permission settings from different places in different workflows are valid, the workflow with the highest priority will be used.')
                                ->end()
                                ->scalarNode('label')
                                    ->info('Will be used in the backend interface as nice name for the workflow. If not set the technical workflow name will be used as label too.')
                                ->end()
                                ->arrayNode('audit_trail')
                                    ->canBeEnabled()
                                    ->info('Enable default audit trail feature provided by Symfony. Take a look at the Symfony docs for more details.')
                                ->end()
                                ->enumNode('type')
                                    ->values(['workflow', 'state_machine'])
                                    ->info('A workflow with type "workflow" can handle multiple places at one time whereas a state_machine provides a finite state_machine (only one place at one time). Take a look at the Symfony docs for more details.')
                                ->end()
                                ->arrayNode('marking_store')
                                    ->fixXmlConfig('argument')
                                    ->children()
                                        ->enumNode('type')
                                            ->values(['multiple_state', 'single_state', 'state_table', 'data_object_multiple_state', 'data_object_splitted_state'])
                                        ->end()
                                        ->arrayNode('arguments')
                                            ->beforeNormalization()
                                                ->always()
                                                ->then(function ($arguments) {
                                                    if (is_string($arguments)) {
                                                        $arguments = [$arguments];
                                                    }
                                                    if (!empty($this->placeholders)) {
                                                        $arguments = $this->placeholderProcessor->mergePlaceholders($arguments, $this->placeholders);
                                                    }

                                                    return $arguments;
                                                })
                                            ->end()
                                            ->requiresAtLeastOneElement()
                                            ->prototype('variable')
                                            ->end()
                                        ->end()
                                        ->scalarNode('service')
                                            ->cannotBeEmpty()
                                        ->end()
                                    ->end()
                                    ->info('Handles the way how the state/place is stored. If not defined "state_table" will be used as default. Take a look at @TODO for a description of the different types.')
                                    ->validate()
                                        ->ifTrue(function ($v) {
                                            return isset($v['type']) && isset($v['service']);
                                        })
                                        ->thenInvalid('"type" and "service" cannot be used together.')
                                    ->end()
                                    ->validate()
                                        ->ifTrue(function ($v) {
                                            return !empty($v['arguments']) && isset($v['service']);
                                        })
                                        ->thenInvalid('"arguments" and "service" cannot be used together.')
                                    ->end()
                                ->end()
                                ->arrayNode('supports')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function ($v) {
                                            return [$v];
                                        })
                                    ->end()
                                    ->prototype('scalar')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->info('List of supported entity classes. Take a look at the Symfony docs for more details.')
                                    ->example(['\Pimcore\Model\DataObject\Product'])
                                ->end()
                                ->arrayNode('support_strategy')
                                    ->fixXmlConfig('argument')
                                    ->children()
                                        ->enumNode('type')
                                            ->values(['expression'])
                                            ->info('Type "expression": a symfony expression to define a criteria.')
                                        ->end()
                                        ->arrayNode('arguments')
                                            ->beforeNormalization()
                                                ->ifString()
                                                ->then(function ($v) {
                                                    return [$v];
                                                })
                                            ->end()
                                            ->requiresAtLeastOneElement()
                                            ->prototype('variable')
                                            ->end()
                                        ->end()
                                        ->scalarNode('service')
                                            ->cannotBeEmpty()
                                            ->info('Define a custom service to handle the logic. Take a look at the Symfony docs for more details.')
                                        ->end()
                                    ->end()
                                    ->validate()
                                        ->ifTrue(function ($v) {
                                            return isset($v['type']) && isset($v['service']);
                                        })
                                        ->thenInvalid('"type" and "service" cannot be used together.')
                                    ->end()
                                    ->validate()
                                        ->ifTrue(function ($v) {
                                            return !empty($v['arguments']) && isset($v['service']);
                                        })
                                        ->thenInvalid('"arguments" and "service" cannot be used together.')
                                    ->end()
                                    ->info('Can be used to implement a special logic which subjects are supported by the workflow. For example only products matching certain criteria.')
                                    ->example([
                                        'type' => 'expression',
                                        'arguments' => [
                                            '\Pimcore\Model\DataObject\Product',
                                            'subject.getProductType() == "article" and is_fully_authenticated() and "ROLE_PIMCORE_ADMIN" in roles',
                                        ],
                                    ])
                                ->end()
                                ->scalarNode('initial_place')
                                    ->defaultNull()
                                    ->setDeprecated('The "%node%" option is deprecated. Use "initial_markings" instead.')
                                    ->info('Will be applied when the current place is empty.')
                                ->end()
                                ->arrayNode('initial_markings')
                                    ->info('Can be used to set the initial places (markings) for a workflow. Note that this option is Symfony 4.3+ only')
                                    ->beforeNormalization()
                                        ->ifString()
                                            ->then(function ($v) {
                                                return [$v];
                                            })
                                        ->end()
                                        ->requiresAtLeastOneElement()
                                        ->prototype('scalar')
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                                ->arrayNode('places')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('label')->info('Nice name which will be used in the Pimcore backend.')->end()
                                            ->scalarNode('title')->info('Title/tooltip for this place when it is displayed in the header of the Pimcore element detail view in the backend.')->defaultValue('')->end()
                                            ->scalarNode('color')->info('Color of the place which will be used in the Pimcore backend.')->defaultValue('#bfdadc')->end()
                                            ->booleanNode('colorInverted')->info('If set to true the color will be used as border and font color otherwise as background color.')->defaultFalse()->end()
                                            ->booleanNode('visibleInHeader')->info('If set to false, the place will be hidden in the header of the Pimcore element detail view in the backend.')->defaultTrue()->end()

                                            ->arrayNode('permissions')
                                                ->prototype('array')
                                                    ->children()
                                                        ->scalarNode('condition')->info('A symfony expression can be configured here. The first set of permissions which are matching the condition will be used.')->end()
                                                        ->booleanNode('save')->info('save permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('publish')->info('publish permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('unpublish')->info('unpublish permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('delete')->info('delete permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('rename')->info('rename permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('view')->info('view permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('settings')->info('settings permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('versions')->info('versions permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('properties')->info('properties permission as it can be configured in Pimcore workplaces')->end()
                                                        ->booleanNode('modify')->info('a short hand for save, publish, unpublish, delete + rename')->end()
                                                        ->scalarNode('objectLayout')->info('if set, the user will see the configured custom data object layout')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->beforeNormalization()
                                        ->always()
                                        ->then(function ($places) {
                                            if (!empty($this->placeholders)) {
                                                foreach ($places as $name => $place) {
                                                    $places[$name] = $this->placeholderProcessor->mergePlaceholders($place, $this->placeholders);
                                                }
                                            }

                                            return $places;
                                        })
                                    ->end()

                                    ->example([
                                        'places' => [
                                            'closed' => [
                                                'label' => 'close product',
                                                'permissions' => [
                                                    [
                                                        'condition' => "is_fully_authenticated() and 'ROLE_PIMCORE_ADMIN' in roles",
                                                        'modify' => false,
                                                    ],
                                                    [
                                                        'modify' => false,
                                                        'objectLayout' => 2,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ])
                                ->end()
                                ->arrayNode('transitions')
                                    ->beforeNormalization()
                                        ->always()
                                        ->then(function ($transitions) {
                                            // It's an indexed array, we let the validation occurs
                                            if (isset($transitions[0])) {
                                                return $transitions;
                                            }

                                            foreach ($transitions as $name => $transition) {
                                                if (array_key_exists('name', (array) $transition)) {
                                                    continue;
                                                }
                                                $transition['name'] = $name;
                                                $transitions[$name] = $transition;
                                            }

                                            return $transitions;
                                        })
                                    ->end()
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                            ->scalarNode('guard')
                                                ->cannotBeEmpty()
                                                ->info('An expression to block the transition')
                                                ->example('is_fully_authenticated() and has_role(\'ROLE_JOURNALIST\') and subject.getTitle() == \'My first article\'')
                                            ->end()
                                            ->arrayNode('from')
                                                ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(function ($v) {
                                                        return [$v];
                                                    })
                                                ->end()
                                                ->requiresAtLeastOneElement()
                                                ->prototype('scalar')
                                                    ->cannotBeEmpty()
                                                ->end()
                                            ->end()
                                            ->arrayNode('to')
                                                ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(function ($v) {
                                                        return [$v];
                                                    })
                                                ->end()
                                                ->requiresAtLeastOneElement()
                                                ->prototype('scalar')
                                                    ->cannotBeEmpty()
                                                ->end()
                                            ->end()
                                            ->arrayNode('options')
                                                ->children()
                                                    ->scalarNode('label')->info('Nice name for the Pimcore backend.')->end()
                                                    ->arrayNode('notes')
                                                        ->children()
                                                            ->booleanNode('commentEnabled')->defaultFalse()->info('If enabled a detail window will open when the user executes the transition. In this detail view the user be asked to enter a "comment". This comment then will be used as comment for the notes/events feature.')->end()
                                                            ->booleanNode('commentRequired')->defaultFalse()->info('Set this to true if the comment should be a required field.')->end()
                                                            ->scalarNode('commentSetterFn')->info('Can be used for data objects. The comment will be saved to the data object additionally to the notes/events through this setter function.')->end()
                                                            ->scalarNode('commentGetterFn')->info('Can be used for data objects to prefill the comment field with data from the data object.')->end()
                                                            ->scalarNode('type')->defaultValue('Status update')->info('Set\'s the type string in the saved note.')->end()
                                                            ->scalarNode('title')->info('An optional alternative "title" for the note, if blank the actions transition result is used.')->end()
                                                            ->arrayNode('additionalFields')
                                                                ->prototype('array')
                                                                    ->children()
                                                                        ->scalarNode('name')->isRequired()->info('The technical name used in the input form.')->end()
                                                                        ->enumNode('fieldType')
                                                                            ->isRequired()
                                                                            ->values(['input', 'textarea', 'select', 'datetime', 'date', 'user', 'checkbox'])
                                                                            ->info('The data component name/field type.')
                                                                        ->end()
                                                                        ->scalarNode('title')->info('The label used by the field')->end()
                                                                        ->booleanNode('required')->defaultFalse()->info('Whether or not the field is required.')->end()
                                                                        ->scalarNode('setterFn')->info('Optional setter function (available in the element, for example in the updated object), if not specified, data will be added to notes. The Workflow manager will call the function with the whole field data.')->end()
                                                                        ->arrayNode('fieldTypeSettings')
                                                                             ->prototype('variable')->end()
                                                                             ->info('Will be passed to the underlying Pimcore data object field type. Can be used to configure the options of a select box for example.')
                                                                        ->end()
                                                                    ->end()
                                                                ->end()
                                                                ->info('Add additional field to the transition detail window.')
                                                            ->end()
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('iconClass')->info('Css class to define the icon which will be used in the actions button in the backend.')->end()
                                                    ->scalarNode('objectLayout')->defaultValue(false)->info('Forces an object layout after the transition was performed. This objectLayout setting overrules all objectLayout settings within the places configs.')->end()

                                                    ->arrayNode('notificationSettings')
                                                        ->prototype('array')
                                                            ->children()
                                                                ->scalarNode('condition')->info('A symfony expression can be configured here. All sets of notification which are matching the condition will be used.')->end()
                                                                ->arrayNode('notifyUsers')
                                                                    ->prototype('scalar')
                                                                        ->cannotBeEmpty()
                                                                    ->end()
                                                                    ->info('Send an email notification to a list of users (user names) when the transition get\'s applied')
                                                                ->end()
                                                                ->arrayNode('notifyRoles')
                                                                    ->prototype('scalar')
                                                                        ->cannotBeEmpty()
                                                                    ->end()
                                                                    ->info('Send an email notification to a list of user roles (role names) when the transition get\'s applied')
                                                                ->end()
                                                                ->arrayNode('channelType')
                                                                    ->requiresAtLeastOneElement()
                                                                    ->enumPrototype()
                                                                        ->values([NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL, NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION])
                                                                        ->cannotBeEmpty()
                                                                        ->defaultValue(NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL)
                                                                    ->end()
                                                                    ->info('Define which channel notification should be sent to, possible values "' . NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL . '" and "' . NotificationSubscriber::NOTIFICATION_CHANNEL_PIMCORE_NOTIFICATION . '", default value is "' . NotificationSubscriber::NOTIFICATION_CHANNEL_MAIL . '".')
                                                                    ->addDefaultChildrenIfNoneSet()
                                                                ->end()
                                                                ->enumNode('mailType')
                                                                    ->values([NotificationSubscriber::MAIL_TYPE_TEMPLATE, NotificationSubscriber::MAIL_TYPE_DOCUMENT])
                                                                    ->defaultValue(NotificationSubscriber::MAIL_TYPE_TEMPLATE)
                                                                    ->info('Type of mail source.')
                                                                ->end()
                                                                ->scalarNode('mailPath')
                                                                    ->defaultValue(NotificationSubscriber::DEFAULT_MAIL_TEMPLATE_PATH)
                                                                    ->info('Path to mail source - either Symfony path to template or fullpath to Pimcore document. Optional use ' . NotificationEmailService::MAIL_PATH_LANGUAGE_PLACEHOLDER . ' as placeholder for language.')
                                                                ->end()
                                                            ->end()
                                                        ->end()
                                                    ->end()

                                                    ->enumNode('changePublishedState')
                                                        ->values([ChangePublishedStateSubscriber::NO_CHANGE, ChangePublishedStateSubscriber::FORCE_UNPUBLISHED, ChangePublishedStateSubscriber::FORCE_PUBLISHED, ChangePublishedStateSubscriber::SAVE_VERSION])
                                                        ->defaultValue(ChangePublishedStateSubscriber::NO_CHANGE)
                                                        ->info('Change published state of element while transition (only available for documents and data objects).')
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()

                                    ->example([
                                        'close_product' => [
                                            'from' => 'open',
                                            'to' => 'closed',
                                            'options' => [
                                                'label' => 'close product',
                                                'notes' => [
                                                    'commentEnabled' => true,
                                                    'commentRequired' => true,
                                                    'additionalFields' => [
                                                        [
                                                            'name' => 'accept',
                                                            'title' => 'accept terms',
                                                            'required' => true,
                                                            'fieldType' => 'checkbox',
                                                        ],
                                                        [
                                                            'name' => 'select',
                                                            'title' => 'please select a type',
                                                            'setterFn' => 'setSpecialWorkflowType',
                                                            'fieldType' => 'select',
                                                            'fieldTypeSettings' => [
                                                                'options' => [
                                                                    ['key' => 'Option A', 'value' => 'a'],
                                                                    ['key' => 'Option B', 'value' => 'b'],
                                                                    ['key' => 'Option C', 'value' => 'c'],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ])
                                ->end()
                                ->arrayNode('globalActions')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('label')->info('Nice name for the Pimcore backend.')->end()
                                            ->scalarNode('iconClass')->info('Css class to define the icon which will be used in the actions button in the backend.')->end()
                                            ->scalarNode('objectLayout')->defaultValue(false)->info('Forces an object layout after the global action was performed. This objectLayout setting overrules all objectLayout settings within the places configs.')->end()
                                            ->scalarNode('guard')
                                                ->cannotBeEmpty()
                                                ->info('An expression to block the action')
                                                ->example('is_fully_authenticated() and has_role(\'ROLE_JOURNALIST\') and subject.getTitle() == \'My first article\'')
                                            ->end()
                                            ->arrayNode('to')
                                                ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(function ($v) {
                                                        return [$v];
                                                    })
                                                ->end()
                                                ->requiresAtLeastOneElement()
                                                ->prototype('scalar')
                                                    ->cannotBeEmpty()
                                                ->end()
                                                ->info('Optionally set the current place of the workflow. Can be used for example to reset the workflow to the initial place.')
                                            ->end()
                                            ->arrayNode('notes')
                                                ->children()
                                                    ->booleanNode('commentEnabled')->defaultFalse()->end()
                                                    ->booleanNode('commentRequired')->defaultFalse()->end()
                                                    ->scalarNode('commentSetterFn')->end()
                                                    ->scalarNode('commentGetterFn')->end()
                                                    ->scalarNode('type')->defaultValue('Status update')->end()
                                                    ->scalarNode('title')->end()
                                                    ->arrayNode('additionalFields')
                                                        ->prototype('array')
                                                            ->children()
                                                                ->scalarNode('name')->isRequired()->end()
                                                                ->enumNode('fieldType')
                                                                    ->isRequired()
                                                                    ->values(['input', 'textarea', 'select', 'datetime', 'date', 'user', 'checkbox'])
                                                                ->end()
                                                                ->scalarNode('title')->end()
                                                                ->booleanNode('required')->defaultFalse()->end()
                                                                ->scalarNode('setterFn')->end()
                                                                ->arrayNode('fieldTypeSettings')
                                                                     ->prototype('variable')->end()
                                                                ->end()
                                                            ->end()

                                                        ->end()
                                                    ->end()
                                                ->end()
                                                ->info('See notes section of transitions. It works exactly the same way.')
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->info('Actions which will be added to actions button independently of the current workflow place.')
                                ->end()
                            ->end()
                            ->validate()
                                ->ifTrue(function ($v) {
                                    return $v['supports'] && isset($v['support_strategy']);
                                })
                                ->thenInvalid('"supports" and "support_strategy" cannot be used together.')
                            ->end()
                            ->validate()
                                ->ifTrue(function ($v) {
                                    return !$v['supports'] && !isset($v['support_strategy']);
                                })
                                ->thenInvalid('"supports" or "support_strategy" should be configured.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->addDefaultsIfNotSet()
            ->end();
    }
}
