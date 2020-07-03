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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Adds configuration for gdpr data provider
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pimcore_admin');

        $rootNode->append($this->buildGdprDataExtractorNode());
        $rootNode->append($this->buildObjectsNode());
        $rootNode->append($this->buildAssetsNode());
        $rootNode->append($this->buildDocumentsNode());

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
            ->scalarNode('custom_admin_path_identifier')
                ->defaultNull()
                ->validate()
                    ->ifTrue(function ($v) {
                        return strlen($v) < 20;
                    })
                    ->thenInvalid('custom_admin_path_identifier must be at least 20 characters long')
                ->end()
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
                    ->scalarNode('login_screen_custom_image')
                        ->defaultNull()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function buildGdprDataExtractorNode()
    {
        $treeBuilder = new TreeBuilder();

        $gdprDataExtractor = $treeBuilder->root('gdpr_data_extractor');
        $gdprDataExtractor->addDefaultsIfNotSet();

        $dataObjects = $treeBuilder->root('dataObjects');
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

        $assets = $treeBuilder->root('assets');
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

    /**
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function buildEventsNode()
    {
        $treeBuilder = new TreeBuilder();
        $notesEvents = $treeBuilder->root('notes_events');

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

    /**
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function buildObjectsNode()
    {
        $treeBuilder = new TreeBuilder();
        $objectsNode = $treeBuilder->root('objects');

        $objectsNode
            ->addDefaultsIfNotSet()
            ->append($this->buildEventsNode());

        return $objectsNode;
    }

    /**
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function buildAssetsNode()
    {
        $treeBuilder = new TreeBuilder();
        $assetsNode = $treeBuilder->root('assets');

        $assetsNode
            ->addDefaultsIfNotSet()
            ->append($this->buildEventsNode());

        return $assetsNode;
    }

    /**
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function buildDocumentsNode()
    {
        $treeBuilder = new TreeBuilder();
        $documentsNode = $treeBuilder->root('documents');

        $documentsNode
            ->addDefaultsIfNotSet()
            ->append($this->buildEventsNode());

        return $documentsNode;
    }
}
