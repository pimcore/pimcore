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

namespace Pimcore\Install\Profile\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ManifestConfiguration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('manifest');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('name')
                    ->info('The name of the install profile as shown in the installer.')
                    ->defaultNull()
                ->end()
                ->arrayNode('files')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('add')
                            ->info('Files to copy/symlink during installations. Can be paths or globs.')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('db')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('data_files')
                            ->info('DB data files to import during installation.')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('pimcore_bundles')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('enable')
                            ->info('Bundles to enable during installation. Can be either set to a boolean or configure options explicitely.')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->treatTrueLike(['enabled' => true])
                                ->treatNullLike(['enabled' => true])
                                ->treatFalseLike(['enabled' => false])
                                ->canBeDisabled()
                                ->children()
                                    ->integerNode('priority')->end()
                                    ->arrayNode('environments')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('install')
                            ->info('Bundles to install during installation. Not that bundles listed here will not be automatically enabled.')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->treatTrueLike(['enabled' => true])
                                ->treatNullLike(['enabled' => true])
                                ->treatFalseLike(['enabled' => false])
                                ->canBeDisabled()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
