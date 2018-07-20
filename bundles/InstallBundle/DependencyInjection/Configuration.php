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
                ->arrayNode('files')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('install')
                            ->info('Defines if profile files should be installed. If this is false, only the DB will be installed.')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('symlink')
                            ->info('Symlink files instead of copying them')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('parameters')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('profile')
                            ->info('The install profile to use')
                            ->defaultNull()
                        ->end()
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
