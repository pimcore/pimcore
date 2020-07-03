<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\InstallBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('pimcore_install');
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->scalarNode('info_message')
                    ->info('Shows an info message on the installation screen')
                    ->defaultNull()
                ->end()
                ->arrayNode('parameters')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('database_credentials')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('user')->end()
                                ->scalarNode('password')->end()
                                ->scalarNode('dbname')->end()
                                ->scalarNode('host')->end()
                                ->scalarNode('port')->end()
                                ->scalarNode('unix_socket')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
