<?php

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

namespace Pimcore\Bundle\UuidBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_uuid');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('instance_identifier')
                ->defaultNull()
                ->info('UUID instance identifier. Has to be unique throughout multiple Pimcore instances. UUID generation will be automatically enabled if a Instance identifier is provided (do not change the instance identifier afterwards - this will cause invalid UUIDs)')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
