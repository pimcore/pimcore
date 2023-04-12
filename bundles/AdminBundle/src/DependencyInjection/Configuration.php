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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection;

use Pimcore\Bundle\AdminBundle\Security\ContentSecurityPolicyHandler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Adds configuration for gdpr data provider
 *
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_admin');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->append($this->buildGdprDataExtractorNode());
        $rootNode->append($this->buildObjectsNode());
        $rootNode->append($this->buildAssetsNode());
        $rootNode->append($this->buildDocumentsNode());
        $rootNode->append($this->addNotificationsNode());
        $rootNode->append($this->addUserNode());

        $rootNode->children()
            ->arrayNode('admin_languages')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('csrf_protection')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('excluded_routes')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('admin_csp_header')
                ->canBeDisabled()
                ->info('Can be used to enable or disable the Content Security Policy headers.')
                ->children()
                    ->arrayNode('exclude_paths')
                        ->scalarPrototype()->end()
                        ->info('Regular Expressions like: /^\/path\/toexclude/')
                    ->end()
                    ->arrayNode('additional_urls')
                        ->addDefaultsIfNotSet()
                        ->normalizeKeys(false)
                        ->children()
                            ->arrayNode(ContentSecurityPolicyHandler::DEFAULT_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::IMG_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::SCRIPT_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::STYLE_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::CONNECT_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::FONT_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::MEDIA_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode(ContentSecurityPolicyHandler::FRAME_OPT)
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('custom_admin_path_identifier')
                ->defaultNull()
                ->validate()
                    ->ifTrue(function ($v) {
                        return strlen($v) < 20;
                    })
                    ->thenInvalid('custom_admin_path_identifier must be at least 20 characters long')
                ->end()
            ->end()
            ->scalarNode('custom_admin_route_name')
                ->defaultValue('my_custom_admin_entry_point')
            ->end()
            ->arrayNode('branding')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('login_screen_invert_colors')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('color_login_screen')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('color_admin_interface')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('color_admin_interface_background')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('login_screen_custom_image')
                        ->defaultValue('')
                    ->end()
                ->end()
            ->end()
        ;

        $this->addAdminNode($rootNode);

        return $treeBuilder;
    }

    protected function buildGdprDataExtractorNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('gdpr_data_extractor');

        $gdprDataExtractor = $treeBuilder->getRootNode();
        $gdprDataExtractor->addDefaultsIfNotSet();

        $dataObjects = $treeBuilder->getRootNode()->children()->arrayNode('dataObjects');
        $dataObjects
            ->addDefaultsIfNotSet()
            ->info('Settings for DataObjects DataProvider');

        $dataObjects
            ->children()
                ->arrayNode('classes')
                    ->info('Configure which classes should be considered, array key is class name')
                    ->prototype('array')
                        ->info('
    MY_CLASS_NAME:
		include: true
		allowDelete: false
		includedRelations:
			- manualSegemens
			- calculatedSegments
                        ')
                        ->children()
                            ->booleanNode('include')
                                ->info('Set if class should be considered in export.')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('allowDelete')
                                ->info('Allow delete of objects directly in preview grid.')
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('includedRelations')
                                ->info('List relation attributes that should be included recursively into export.')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $gdprDataExtractor->append($dataObjects);

        $assets = $treeBuilder->getRootNode()->children()->arrayNode('assets');

        $assets
            ->addDefaultsIfNotSet()
            ->info('Settings for Assets DataProvider');

        $assets
            ->children()
                ->arrayNode('types')
                    ->info('Configure which types should be considered')
                    ->prototype('array')
                    ->info('asset types')
                ->end()->defaultValue([])
            ->end();

        $gdprDataExtractor->append($assets);

        return $gdprDataExtractor;
    }

    protected function buildEventsNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('notes_events');
        $notesEvents = $treeBuilder->getRootNode();

        $notesEvents
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('types')
                    ->info('List all notes/event types.')
                    ->prototype('scalar')->end()
                    ->defaultValue(['', 'content', 'seo', 'warning', 'notice'])
                ->end()
            ->end()
        ;

        return $notesEvents;
    }

    protected function buildObjectsNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('objects');
        $objectsNode = $treeBuilder->getRootNode();

        $objectsNode
            ->addDefaultsIfNotSet()
            ->append($this->buildEventsNode());

        return $objectsNode;
    }

    protected function buildAssetsNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('assets');
        $assetsNode = $treeBuilder->getRootNode();

        $assetsNode
            ->addDefaultsIfNotSet()
            ->append($this->buildEventsNode());

        return $assetsNode;
    }

    protected function buildDocumentsNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('documents');
        $documentsNode = $treeBuilder->getRootNode();

        $documentsNode
            ->addDefaultsIfNotSet()
            ->append($this->buildEventsNode());

        $documentsNode
            ->children()
                ->arrayNode('email_search')
                    ->info('List of searchable email documents.')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;



        return $documentsNode;
    }

    /**
     * Add admin config
     */
    private function addAdminNode(ArrayNodeDefinition $rootNode): void
    {
        // add session attribute bag config
        $this->addAdminSessionAttributeBags($rootNode);

        // unauthenticated routes won't be double checked for authentication in AdminControllerListener
        $this->addRoutesChild($rootNode, 'unauthenticated_routes');

        $rootNode
            ->children()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addAdminSessionAttributeBags(ArrayNodeDefinition $adminNode): void
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

    protected function addNotificationsNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('notifications');
        $notificationsNode = $treeBuilder->getRootNode();

        $notificationsNode
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
                ->arrayNode('check_new_notification')
                    ->canBeDisabled()
                    ->info('Can be used to enable or disable the check of new notifications (url: /admin/notification/find-last-unread).')
                    ->children()
                        ->integerNode('interval')
                            ->info('Interval in seconds to check new notifications')
                            ->defaultValue(30)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $notificationsNode;
    }

    protected function addUserNode(): ArrayNodeDefinition|NodeDefinition
    {
        $treeBuilder = new TreeBuilder('user');
        $userNode = $treeBuilder->getRootNode();

        $userNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('default_key_bindings')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('key')->isRequired()->end()
                            ->scalarNode('action')->isRequired()->end()
                            ->scalarNode('alt')->defaultFalse()->end()
                            ->scalarNode('ctrl')->defaultFalse()->end()
                            ->scalarNode('shift')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $userNode;
    }
}
