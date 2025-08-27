<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\XliffBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_xliff');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
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
        ->end();

        return $treeBuilder;
    }
}
