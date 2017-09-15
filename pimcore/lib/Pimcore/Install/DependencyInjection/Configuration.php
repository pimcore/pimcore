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

namespace Pimcore\Install\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('pimcore_installer');
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->scalarNode('info_message')->end()
                ->booleanNode('copy_profile_files')
                    ->defaultTrue()
                ->end()
                ->arrayNode('parameters')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('profile')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('database_credentials')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('user')->end()
                                ->scalarNode('password')->end()
                                ->scalarNode('dbname')->end()
                                ->scalarNode('host')->end()
                                ->scalarNode('socket')->end()
                                ->scalarNode('port')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
