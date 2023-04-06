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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @internal
 */
final class ConfigurationHelper
{
    public static function addConfigLocationWithWriteTargetNodes(ArrayNodeDefinition $rootNode, array $nodes): void
    {
        $storageNode = $rootNode
            ->children()
                ->arrayNode('config_location')
                ->addDefaultsIfNotSet()
                ->children();

        foreach ($nodes as $node => $dir) {
            ConfigurationHelper::addConfigLocationTargetNode($storageNode, $node, $dir);
        }
    }

    public static function addConfigLocationTargetNode(NodeBuilder $node, string $name, string $folder): void
    {
        $node->
        arrayNode($name)
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('is_symfony_config_disabled')
                    ->defaultFalse()
                ->end()
                ->arrayNode('write_target')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('type')
                        ->values(['symfony-config', 'settings-store', 'disabled'])
                        ->defaultValue('symfony-config')
                    ->end()
                    ->arrayNode('options')
                        ->defaultValue(['directory' => '%kernel.project_dir%' . $folder])
                        ->variablePrototype()
                    ->end()
                ->end()
            ->end();
    }
}
