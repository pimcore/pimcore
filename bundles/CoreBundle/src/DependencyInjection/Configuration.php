<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use const PASSWORD_ARGON2I;
use const PASSWORD_ARGON2ID;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Config\Processor\PlaceholderProcessor;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotificationSubscriber;
use Pimcore\Workflow\Notification\NotificationEmailService;
use Pimcore\Workflow\Transition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    private PlaceholderProcessor $placeholderProcessor;

    private array $placeholders = [];

    public function __construct()
    {
        $this->placeholderProcessor = new PlaceholderProcessor();
        $this->placeholders = [];
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->arrayNode('bundles')
                    ->info('Define parameters for Pimcore Bundle Locator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('search_paths')
                            ->info('Define additional paths from root folder(without leading slash) that need to be scanned for *Bundle.php')
                            ->prototype('scalar')->end()
                        ->end()
                        ->booleanNode('handle_composer')
                            ->info('Define whether it should be scanning bundles through composer /vendor folder or not')
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
                        ->arrayNode('domains')
                            ->info('Valid domains for translations')
                            ->prototype('scalar')->end()
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
        $this->addObjectsNode($rootNode);
        $this->addAssetNode($rootNode);
        $this->addDocumentsNode($rootNode);
        $this->addEncryptionNode($rootNode);
        $this->addModelsNode($rootNode);
        $this->addRoutingNode($rootNode);
        $this->addCacheNode($rootNode);
        $this->addContextNode($rootNode);
        $this->addWebProfilerNode($rootNode);
        $this->addSecurityNode($rootNode);
        $this->addEmailNode($rootNode);
        $this->addWorkflowNode($rootNode);
        $this->addHttpClientNode($rootNode);
        $this->addApplicationLogNode($rootNode);
        $this->addPredefinedPropertiesNode($rootNode);
        $this->addPerspectivesNode($rootNode);
        $this->addCustomViewsNode($rootNode);
        $this->addTemplatingEngineNode($rootNode);
        $this->addGotenbergNode($rootNode);
        $this->addDependencyNode($rootNode);
        $this->addChromiumNode($rootNode);
        $storageNode = ConfigurationHelper::addConfigLocationWithWriteTargetNodes($rootNode, [
            'image_thumbnails' => PIMCORE_CONFIGURATION_DIRECTORY . '/image_thumbnails',
            'video_thumbnails' => PIMCORE_CONFIGURATION_DIRECTORY . '/video_thumbnails',
            'document_types' => PIMCORE_CONFIGURATION_DIRECTORY . '/document_types',
            'predefined_properties' => PIMCORE_CONFIGURATION_DIRECTORY . '/predefined_properties',
            'predefined_asset_metadata' => PIMCORE_CONFIGURATION_DIRECTORY . '/predefined_asset_metadata',
            'perspectives' => PIMCORE_CONFIGURATION_DIRECTORY . '/perspectives',
            'custom_views' => PIMCORE_CONFIGURATION_DIRECTORY . '/custom_views',
            'object_custom_layouts' => PIMCORE_CONFIGURATION_DIRECTORY . '/object_custom_layouts',
        ]);

        ConfigurationHelper::addConfigLocationTargetNode(
            $storageNode,
            'system_settings',
            PIMCORE_CONFIGURATION_DIRECTORY . '/system_settings',
            [LocationAwareConfigRepository::READ_TARGET]
        );

        ConfigurationHelper::addConfigLocationTargetNode(
            $storageNode,
            'select_options',
            PIMCORE_CONFIGURATION_DIRECTORY . '/select_options',
            [LocationAwareConfigRepository::READ_TARGET]
        );

        return $treeBuilder;
    }

    /**
     * Add maintenance config
     */
    private function addMaintenanceNode(ArrayNodeDefinition $rootNode): void
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
                        ->info('Integer value in seconds.')
                        ->defaultValue(7_776_000) // 90 days
                    ->end()
                    ->integerNode('cleanup_profiler_files_atime_older_than')
                        ->info('Integer value in seconds.')
                        ->defaultValue(1800)
                    ->end()
        ;
    }

    /**
     * Add general config
     */
    private function addGeneralNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('general')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('timezone')
                    ->defaultValue('')
                ->end()
                ->scalarNode('path_variable')
                    ->info('Additional $PATH variable (: separated) (/x/y:/foo/bar):')
                    ->defaultNull()
                ->end()
                ->scalarNode('domain')
                    ->defaultValue('')
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
                    ->setDeprecated('pimcore/pimcore', '11.2')
                ->end()
                ->arrayNode('valid_languages')
                    ->info('String or array format are supported.')
                    ->beforeNormalization()
                    ->ifString()
                        ->then(fn ($v) => explode(',', $v))
                    ->end()
                    ->defaultValue(['en', 'de', 'fr'])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('required_languages')
                    ->info('String or array format are supported.')
                    ->beforeNormalization()
                    ->ifString()
                        ->then(fn ($v) => explode(',', $v))
                    ->end()
                    ->prototype('scalar')->end()
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
                    ->info('Debug Admin-Translations (text in UI will be displayed wrapped in +)')
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

    private function addModelsNode(ArrayNodeDefinition $rootNode): void
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

    private function addHttpClientNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('httpclient')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('adapter')
                            ->info('Set to `Proxy` if proxy server should be used')
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

    private function addApplicationLogNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('applicationlog')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mail_notification')
                            ->children()
                                ->booleanNode('send_log_summary')
                                    ->info('Send log summary via email')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function ($v) {
                                            return (bool)$v;
                                        })
                                    ->end()
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('filter_priority')
                                    ->info('Filter threshold for email summary, choose one of: 7 (debug), 6 (info), 5 (notice), 4 (warning), 3 (error), 2 (critical), 1 (alert) ,0 (emerg)')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('mail_receiver')
                                ->info('Log summary receivers. Separate multiple email receivers by using ;')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('archive_treshold')
                            ->info('Archive threshold in days')
                            ->defaultValue(30)
                        ->end()
                        ->scalarNode('archive_alternative_database')
                            ->info('Archive database name (optional). Tables will get archived to a different database, recommended when huge amounts of logs will be generated')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('delete_archive_threshold')
                            ->info('Threshold for deleting application log archive tables (in months)')
                            ->defaultValue('6')
                        ->end()
                    ->end()
            ->end();
    }

    /**
     * Add asset specific extension config
     */
    private function addAssetNode(ArrayNodeDefinition $rootNode): void
    {
        $assetsNode = $rootNode
            ->children()
                ->arrayNode('assets')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('thumbnails')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('allowed_formats')
                                ->defaultValue(
                                    [
                                        'avif',
                                        'eps',
                                        'gif',
                                        'jpeg',
                                        'jpg',
                                        'pjpeg',
                                        'png',
                                        'svg',
                                        'tiff',
                                        'webm',
                                        'webp',
                                        'print',
                                    ]
                                )
                                ->scalarPrototype()->end()
                            ->end()
                            ->floatNode('max_scaling_factor')
                                ->defaultValue(5.0)
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('frontend_prefixes')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('source')
                                ->defaultValue('')
                                ->end()
                            ->scalarNode('thumbnail')
                                ->defaultValue('')
                                ->end()
                            ->scalarNode('thumbnail_deferred')
                                ->defaultValue('')
                                ->end()
                        ->end()
                    ->end()
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
                            ->integerNode('max_pixels')
                                ->info('Maximum number of pixels an image can have when added (width × height).')
                                ->defaultValue(40_000_000)
                            ->end()
                            ->arrayNode('low_quality_image_preview')
                                ->info('Allow a LQIP SVG image to be generated alongside any other thumbnails.')
                                ->addDefaultsIfNotSet()
                                ->canBeDisabled()
                            ->end()
                            ->arrayNode('thumbnails')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('definitions')
                                        ->normalizeKeys(false)
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('id')->end()
                                                ->scalarNode('name')->end()
                                                ->scalarNode('description')->end()
                                                ->scalarNode('group')->end()
                                                ->scalarNode('format')->end()
                                                ->scalarNode('quality')->end()
                                                ->scalarNode('highResolution')->end()
                                                ->booleanNode('preserveColor')->end()
                                                ->booleanNode('preserveMetaData')->end()
                                                ->booleanNode('rasterizeSVG')->end()
                                                ->booleanNode('downloadable')->end()
                                                ->integerNode('modificationDate')->end()
                                                ->integerNode('creationDate')->end()
                                                ->booleanNode('preserveAnimation')->end()
                                                ->arrayNode('items')
                                                    ->prototype('array')
                                                        ->children()
                                                            ->scalarNode('method')->end()
                                                            ->arrayNode('arguments')
                                                                ->prototype('variable')->end()
                                                            ->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('medias')
                                                    ->normalizeKeys(false)
                                                    ->prototype('array')
                                                        ->arrayProtoType()
                                                            ->children()
                                                                ->scalarNode('method')->end()
                                                                ->arrayNode('arguments')
                                                                    ->prototype('variable')->end()
                                                                ->end()
                                                            ->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->booleanNode('clip_auto_support')
                                        ->info('Try to detect and use clipping paths and masks in images when generating thumbnails.')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) {
                                                return (bool)$v;
                                            })
                                        ->end()
                                        ->defaultTrue()
                                    ->end()
                                    ->arrayNode('image_optimizers')
                                        ->addDefaultsIfNotSet()
                                        ->canBeDisabled()
                                    ->end()
                                    ->arrayNode('auto_formats')
                                        ->prototype('array')
                                            ->canBeDisabled()
                                            ->children()
                                                ->scalarNode('quality')->end()
                                            ->end()
                                        ->end()
                                        ->defaultValue([
                                            'avif' => [
                                                'enabled' => true,
                                                'quality' => 50,
                                            ],
                                            'webp' => [
                                                'enabled' => true,
                                                'quality' => null,
                                            ],
                                        ])
                                    ->end()
                                    ->booleanNode('status_cache')
                                        ->info('Store image metadata such as filename and modification date in assets_image_thumbnail_cache, this is helpful when using remote object storage for thumbnails.')
                                        ->defaultTrue()
                                    ->end()
                                    ->booleanNode('auto_clear_temp_files')
                                        ->info('Automatically delete all image thumbnail files any time an image or its metadata is updated.')
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
                                    ->arrayNode('definitions')
                                        ->normalizeKeys(false)
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('id')->end()
                                                ->scalarNode('name')->end()
                                                ->scalarNode('description')->end()
                                                ->scalarNode('group')->end()
                                                ->scalarNode('videoBitrate')->end()
                                                ->scalarNode('audioBitrate')->end()
                                                ->scalarNode('quality')->end()
                                                ->integerNode('modificationDate')->end()
                                                ->integerNode('creationDate')->end()
                                                ->arrayNode('items')
                                                    ->prototype('array')
                                                        ->children()
                                                            ->scalarNode('method')->end()
                                                            ->arrayNode('arguments')
                                                                ->prototype('variable')->end()
                                                            ->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('medias')
                                                    ->normalizeKeys(false)
                                                    ->prototype('array')
                                                        ->arrayProtoType()
                                                            ->children()
                                                                ->scalarNode('method')->end()
                                                                ->arrayNode('arguments')
                                                                    ->prototype('variable')->end()
                                                                ->end()
                                                            ->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()

                                    ->booleanNode('auto_clear_temp_files')
                                        ->info('Automatically delete all video thumbnail files any time an image or its metadata is updated.')
                                        ->defaultTrue()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('document')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('thumbnails')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')
                                        ->defaultTrue()
                                        ->info('Process thumbnails for Asset documents.')
                                    ->end()
                                ->end()
                            ->end()
                            ->booleanNode('process_page_count')
                                ->defaultTrue()
                                ->info('Process & store page count for Asset documents. Internally required for thumbnails & text generation')
                            ->end()
                            ->booleanNode('process_text')
                                ->defaultTrue()
                                ->info('Process text for Asset documents (e.g. used by backend search).')
                            ->end()
                            ->booleanNode('scan_pdf')
                                ->defaultTrue()
                                ->info('Scan PDF documents for unsafe JavaScript.')
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
                        ->info('Absolute path to default ICC RGB profile (if no embedded profile is given)')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('icc_cmyk_profile')
                        ->info('Absolute path to default ICC CMYK profile (if no embedded profile is given)')
                        ->defaultNull()
                    ->end()
                ->end();

        $assetsNode
            ->children()
                ->arrayNode('metadata')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('predefined')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('definitions')
                                ->normalizeKeys(false)
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('description')->end()
                                            ->scalarNode('group')->end()
                                            ->scalarNode('language')->end()
                                            ->scalarNode('type')->end()
                                            ->scalarNode('data')->end()
                                            ->scalarNode('targetSubtype')->end()
                                            ->scalarNode('config')->end()
                                            ->booleanNode('inheritable')
                                                ->beforeNormalization()
                                                ->ifString()
                                                ->then(function ($v) {
                                                    return (bool)$v;
                                                })
                                                ->end()
                                            ->end()
                                            ->integerNode('creationDate')->end()
                                            ->integerNode('modificationDate')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
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
        $this->addImplementationNodeFromArrayDefinition($assetsNode, 'type_definitions');
    }

    /**
     * Add object specific extension config
     */
    private function addObjectsNode(ArrayNodeDefinition $rootNode): void
    {
        $objectsNode = $rootNode
            ->children()
                ->arrayNode('objects')
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('ignore_localized_query_fallback')
                            ->beforeNormalization()
                            ->ifString()
                                ->then(function ($v) {
                                    return (bool)$v;
                                })
                                ->end()
                            ->defaultFalse()
                        ->end()
                        ->integerNode('tree_paging_limit')
                            ->defaultValue(30)
                        ->end()
                        ->integerNode('auto_save_interval')
                            ->defaultValue(60)
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
                        ->arrayNode('custom_layouts')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('definitions')
                                    ->normalizeKeys(false)
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('id')->end()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('description')->defaultNull()->end()
                                            ->integerNode('creationDate')->end()
                                            ->integerNode('modificationDate')->end()
                                            ->integerNode('userOwner')->end()
                                            ->integerNode('userModification')
                                                ->beforeNormalization()
                                                    ->ifNull()->then(function () {
                                                        return 0;
                                                    })->end()
                                                ->end()
                                            ->scalarNode('classId')->end()
                                            ->integerNode('default')->end()
                                            ->variableNode('layoutDefinitions')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('select_options')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('definitions')
                                    ->normalizeKeys(false)
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('id')->end()
                                            ->scalarNode('group')->end()
                                            ->scalarNode('useTraits')->end()
                                            ->scalarNode('implementsInterfaces')->end()
                                            ->arrayNode('selectOptions')
                                                ->prototype('array')
                                                    ->children()
                                                        ->scalarNode('value')->end()
                                                        ->scalarNode('label')->end()
                                                        ->scalarNode('name')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
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
     */
    private function addEncryptionNode(ArrayNodeDefinition $rootNode): void
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
     */
    private function addDocumentsNode(ArrayNodeDefinition $rootNode): void
    {
        $documentsNode = $rootNode
            ->children()
                ->arrayNode('documents')
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet();

        $documentsNode
            ->children()
                 ->arrayNode('doc_types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('definitions')
                        ->normalizeKeys(false)
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('group')->end()
                                    ->scalarNode('module')->end()
                                    ->scalarNode('controller')->end()
                                    ->scalarNode('template')->end()
                                    ->scalarNode('type')->end()
                                    ->integerNode('priority')->end()
                                    ->integerNode('creationDate')->end()
                                    ->integerNode('modificationDate')->end()
                                    ->booleanNode('staticGeneratorEnabled')
                                        ->beforeNormalization()
                                            ->always()
                                            ->then(function ($v) {
                                                return (bool)$v;
                                            })
                                        ->end()
                                        ->defaultFalse()
                                    ->end()
                                ->end()
                            ->end()
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
                ->scalarNode('default_controller')
                    ->defaultValue('App\\Controller\\DefaultController::defaultAction')
                ->end()
                ->arrayNode('error_pages')
                    ->children()
                        ->scalarNode('default')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('localized')
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
                    ->end()
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
                ->scalarNode('preview_url_prefix')
                    ->defaultValue('')
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
                ->integerNode('auto_save_interval')
                    ->defaultValue(60)
                ->end()
                ->arrayNode('static_page_router')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                            ->info('Enable Static Page router for document when using remote storage for generated pages')
                        ->end()
                        ->scalarNode('route_pattern')
                            ->defaultNull()
                            ->info('Optionally define route patterns to lookup static pages. Regular Expressions like: /^\/en\/Magazine/')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('static_page_generator')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('use_main_domain')
                            ->defaultFalse()
                            ->info('Use main domain for static pages folder in tmp/pages')
                        ->end()
                        ->arrayNode('headers')
                            ->normalizeKeys(false)
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->end()
                                        ->scalarNode('value')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addDocumentDefinition($documentsNode, 'type_definitions');
    }

    /**
     * Add implementation node config (map, prefixes)
     */
    private function addImplementationLoaderNode(ArrayNodeDefinition $node, string $name): void
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
     * Add implementation node config with array (map[class,matching], prefixes)
     */
    private function addImplementationNodeFromArrayDefinition(ArrayNodeDefinition $node, string $name): void
    {
        $node
            ->children()
                ->arrayNode($name)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('map')
                            ->arrayPrototype()
                            ->children()
                                ->scalarNode('class')->end()
                                ->arrayNode('matching')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addDocumentDefinition(ArrayNodeDefinition $node, string $name): void
    {
        $node
            ->children()
                ->arrayNode($name)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('map')
                            ->arrayPrototype()
                            ->children()
                                ->scalarNode('class')->end()
                                ->booleanNode('translatable')
                                    ->defaultTrue()
                                ->end()
                                ->scalarNode('valid_table')->defaultNull()->end()
                                ->booleanNode('direct_route')
                                    ->defaultFalse()
                                ->end()
                                ->booleanNode('translatable_inheritance')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('children_supported')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('only_printable_childrens')
                                    ->defaultFalse()
                                ->end()
                                ->booleanNode('predefined_document_types')
                                    ->defaultFalse()
                                ->end()
                             ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addRoutingNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('routing')
                    ->addDefaultsIfNotSet()
                    ->children()
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
     */
    private function addContextNode(ArrayNodeDefinition $rootNode): void
    {
        $contextNode = $rootNode->children()
            ->arrayNode('context')
            ->useAttributeAsKey('name');

        $prototype = $contextNode->prototype('array');

        // define routes child on each context entry
        $this->addRoutesChild($prototype, 'routes');
    }

    private function addSecurityNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('password')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('algorithm')
                                    ->info('The hashing algorithm to use for backend users and objects containing a "password" field.')
                                    ->example('!php/const PASSWORD_BCRYPT')
                                    ->values(array_filter([
                                        PASSWORD_DEFAULT,
                                        PASSWORD_BCRYPT,
                                        defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : null,
                                        defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : null,
                                    ]))
                                    ->defaultValue(PASSWORD_DEFAULT)
                                ->end()
                                ->arrayNode('options')
                                    ->info('See: https://www.php.net/manual/de/password.constants.php')
                                    ->example(['cost' => 13])
                                    ->defaultValue([])
                                    ->normalizeKeys(false)
                                    ->variablePrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->enumNode('factory_type')
                            ->values(['encoder', 'password_hasher'])
                            ->defaultValue('encoder')
                        ->end()
                        ->arrayNode('encoder_factories')
                            ->info('Encoder factories to use as className => factory service ID mapping')
                            ->example([
                                'App\Model\DataObject\User1' => [
                                    'id' => 'website_demo.security.encoder_factory2',
                                ],
                                'App\Model\DataObject\User2' => 'website_demo.security.encoder_factory2',
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
                        ->arrayNode('password_hasher_factories')
                            ->info('Password hasher factories to use as className => factory service ID mapping')
                            ->example([
                                'App\Model\DataObject\User1' => [
                                    'id' => 'website_demo.security.encoder_factory2',
                                ],
                                'App\Model\DataObject\User2' => 'website_demo.security.encoder_factory2',
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
            ->end();
    }

    /**
     * Configure exclude paths for web profiler toolbar
     */
    private function addWebProfilerNode(ArrayNodeDefinition $rootNode): void
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
     */
    private function addRoutesChild(ArrayNodeDefinition $parent, string $name): void
    {
        $node = $parent->children()->arrayNode($name);

        /** @var ArrayNodeDefinition $prototype */
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
     */
    private function addCacheNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('full_page_cache')
                ->ignoreExtraKeys()
                ->canBeDisabled()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('lifetime')
                        ->info('Optional output-cache lifetime (in seconds) after the cache expires, if not defined the cache will be cleaned on every action inside the CMS, otherwise not (for high traffic sites)')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('exclude_patterns')
                        ->info('Regular Expressions like: /^\/dir\/toexclude/')
                    ->end()
                    ->scalarNode('exclude_cookie')
                        ->info('Comma separated list of cookie names, that will automatically disable the full-page cache')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Adds configuration for email source adapters
     */
    private function addEmailNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('email')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('sender')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('email')
                                    ->defaultValue('')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('return')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('email')
                                    ->defaultValue('')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('debug')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('email_addresses')
                                    ->defaultValue('')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('usespecific')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addWorkflowNode(ArrayNodeDefinition $rootNode): void
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
                                ->arrayNode('custom_extensions')->ignoreExtraKeys(false)->info('Use this key to attach additional config information to a workflow, for example via bundles, etc.')->end()
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
                                                ->example('is_fully_authenticated() and is_granted(\'ROLE_JOURNALIST\') and subject.getTitle() == \'My first article\'')
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
                                                                            ->values(['input', 'numeric', 'textarea', 'select', 'datetime', 'date', 'user', 'checkbox'])
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
                                                            ->arrayNode('customHtml')
                                                                ->children()
                                                                    ->enumNode('position')
                                                                        ->values(['top', 'center', 'bottom'])
                                                                        ->defaultValue('top')
                                                                        ->info('Set position of custom HTML inside modal (top, center, bottom).')
                                                                    ->end()
                                                                    ->scalarNode('service')
                                                                        ->cannotBeEmpty()
                                                                        ->info('Define a custom service for rendering custom HTML within the note modal.')
                                                                    ->end()
                                                                ->end()
                                                            ->end()
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('iconClass')->info('CSS class to define the icon which will be used in the actions button in the backend.')->end()
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
                                                    ->enumNode('unsavedChangesBehaviour')
                                                        ->values([Transition::UNSAVED_CHANGES_BEHAVIOUR_SAVE,
                                                                  Transition::UNSAVED_CHANGES_BEHAVIOUR_WARN,
                                                                  Transition::UNSAVED_CHANGES_BEHAVIOUR_IGNORE, ])
                                                        ->defaultValue(Transition::UNSAVED_CHANGES_BEHAVIOUR_WARN)
                                                        ->info('Behaviour when workflow transition gets applied but there are unsaved changes')
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
                                            ->scalarNode('iconClass')->info('CSS class to define the icon which will be used in the actions button in the backend.')->end()
                                            ->scalarNode('objectLayout')->defaultValue(false)->info('Forces an object layout after the global action was performed. This objectLayout setting overrules all objectLayout settings within the places configs.')->end()
                                            ->scalarNode('guard')
                                                ->cannotBeEmpty()
                                                ->info('An expression to block the action')
                                                ->example('is_fully_authenticated() and is_granted(\'ROLE_JOURNALIST\') and subject.getTitle() == \'My first article\'')
                                            ->end()
                                            ->booleanNode('saveSubject')
                                                ->defaultTrue()
                                                ->info('Determines if the global action should perform a save on the subject, default behavior is set to true')
                                                ->example('false')
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

                                                    ->arrayNode('customHtml')
                                                        ->children()
                                                            ->enumNode('position')
                                                                ->values(['top', 'center', 'bottom'])
                                                                ->defaultValue('top')
                                                                ->info('Set position of custom HTML inside modal (top, center, bottom).')
                                                            ->end()
                                                            ->scalarNode('service')
                                                                ->cannotBeEmpty()
                                                                ->info('Define a custom service for rendering custom HTML within the note modal.')
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
                            ->validate()
                                ->ifTrue(function ($v) {
                                    if (($v['type'] ?? 'workflow') === 'state_machine') {
                                        foreach ($v['transitions'] ?? [] as $transition) {
                                            if (count($transition['to']) > 1) {
                                                return true;
                                            }
                                        }

                                        foreach ($v['globalActions'] ?? [] as $transition) {
                                            if (count($transition['to']) > 1) {
                                                return true;
                                            }
                                        }
                                    }

                                    return false;
                                })
                                ->thenInvalid('Type `state_machine` does not support multiple `to` definitions for transitions and global actions. Change definition or type to `workflow`.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->addDefaultsIfNotSet()
            ->end();
    }

    /**
     * Add predefined properties specific extension config
     */
    private function addPredefinedPropertiesNode(ArrayNodeDefinition $rootNode): void
    {
        $predefinedPropertiesNode = $rootNode
            ->children()
            ->arrayNode('properties')
            ->ignoreExtraKeys()
            ->addDefaultsIfNotSet();

        $predefinedPropertiesNode
        ->children()
            ->arrayNode('predefined')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('definitions')
                    ->normalizeKeys(false)
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')->end()
                                ->scalarNode('description')->end()
                                ->scalarNode('key')->end()
                                ->scalarNode('type')->end()
                                ->scalarNode('data')->end()
                                ->scalarNode('config')->end()
                                ->scalarNode('ctype')->end()
                                ->booleanNode('inheritable')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function ($v) {
                                            return (bool)$v;
                                        })
                                        ->end()
                                ->end()
                                ->integerNode('creationDate')->end()
                                ->integerNode('modificationDate')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * Add perspectives specific extension config
     */
    private function addPerspectivesNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('perspectives')
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('definitions')
                        ->normalizeKeys(false)
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('iconCls')->end()
                                    ->scalarNode('icon')->end()
                                    ->variableNode('toolbar')->end()
                                    ->arrayNode('dashboards')
                                        ->children()
                                            ->variableNode('disabledPortlets')->end()
                                        ->end()
                                        ->children()
                                            ->variableNode('predefined')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('elementTree')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('type')->end()
                                                ->scalarNode('position')->end()
                                                ->scalarNode('name')->end()
                                                ->booleanNode('expanded')->end()
                                                ->scalarNode('hidden')->end()
                                                ->integerNode('sort')->end()
                                                ->scalarNode('id')->end()
                                                ->variableNode('treeContextMenu')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * Add custom views specific extension config
     */
    private function addCustomViewsNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('custom_views')
                    ->ignoreExtraKeys()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('definitions')
                        ->normalizeKeys(false)
                            ->prototype('array')
                            ->children()
                                ->scalarNode('id')->end()
                                ->scalarNode('treetype')->end()
                                ->scalarNode('name')->end()
                                ->scalarNode('condition')->end()
                                ->scalarNode('icon')->end()
                                ->scalarNode('rootfolder')->end()
                                ->scalarNode('showroot')->end()
                                ->variableNode('classes')->end()
                                ->scalarNode('position')->end()
                                ->scalarNode('sort')->end()
                                ->booleanNode('expanded')->end()
                                ->scalarNode('having')->end()
                                ->scalarNode('where')->end()
                                ->variableNode('treeContextMenu')->end()
                                ->arrayNode('joins')
                                    ->protoType('array')
                                        ->children()
                                            ->scalarNode('type')->end()
                                            ->scalarNode('condition')->end()
                                            ->variableNode('name')->end()
                                            ->variableNode('columns')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addTemplatingEngineNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('templating_engine')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('twig')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('sandbox_security_policy')
                        ->info('Allowlist tags, filters & functions for evaluating twig
                        templates in a sandbox environment e.g. used by Mailer & Text layout component.')
                        ->children()
                            ->arrayNode('tags')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('filters')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('functions')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addGotenbergNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('gotenberg')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_url')
                            ->defaultValue('http://gotenberg:3000')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addDependencyNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('dependency')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('enabled')
                        ->defaultValue(true)
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * @deprecated
     */
    private function addChromiumNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('chromium')
                    ->setDeprecated('pimcore/pimcore', '11.2', 'Chromium service is deprecated and will be removed in Pimcore 12. Use Gotenberg instead.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('uri')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
