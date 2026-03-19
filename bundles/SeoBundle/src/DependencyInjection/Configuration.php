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

namespace Pimcore\Bundle\SeoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_seo');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $this->addSitemapGenerators($rootNode);
        $this->addRedirectsConfig($rootNode);

        return $treeBuilder;
    }

    private function addSitemapGenerators(ArrayNodeDefinition $rootNode): void
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

    private function addRedirectsConfig(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('redirects')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('status_codes')
                            ->info('List all redirect status codes.')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                        ->booleanNode('auto_create_redirects')
                            ->info('Auto create redirects on moving documents & changing pretty url, updating Url slugs in Data Objects.')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end();
    }
}
