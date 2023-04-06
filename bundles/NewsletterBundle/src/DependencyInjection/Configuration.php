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

namespace Pimcore\Bundle\NewsletterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_newsletter');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
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
                    ->defaultFalse()
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
                ->scalarNode('default_url_prefix')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
