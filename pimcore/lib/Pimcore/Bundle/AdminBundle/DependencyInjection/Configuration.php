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

        $rootNode->append($this->buildDataObjectsNode());

        return $treeBuilder;
    }

    protected function buildDataObjectsNode() {
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
                        ->info("
    MY_CLASS_NAME: 
		include: true
		allowDelete: false
		includeRelations:
			- manualSegemens
			- calculatedSegments
                        ")
                        ->children()
                            ->booleanNode("include")
                                ->info("Set if class should be considered in export.")
                                ->defaultTrue()
                            ->end()
                            ->booleanNode("allowDelete")
                                ->info("Allow delete of objects directly in preview grid.")
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
        return $gdprDataExtractor;
    }
}
