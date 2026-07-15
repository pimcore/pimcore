<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CustomReportsBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_custom_reports');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                    ->arrayNode('definitions')
                        ->normalizeKeys(false)
                        ->prototype('array')
                            ->children()
                                ->scalarNode('id')->end()
                                ->scalarNode('name')->end()
                                ->scalarNode('niceName')->end()
                                ->scalarNode('sql')->end()
                                ->scalarNode('group')->end()
                                ->scalarNode('groupIconClass')->end()
                                ->scalarNode('iconClass')->end()
                                ->booleanNode('menuShortcut')->end()
                                ->scalarNode('reportClass')->end()
                                ->scalarNode('chartType')->end()
                                ->scalarNode('pieColumn')->end()
                                ->scalarNode('pieLabelColumn')->end()
                                ->variableNode('xAxis')->end()
                                ->variableNode('yAxis')->end()
                                ->integerNode('modificationDate')->end()
                                ->integerNode('creationDate')->end()
                                ->booleanNode('shareGlobally')->end()
                                ->variableNode('sharedUserNames')->end()
                                ->variableNode('sharedRoleNames')->end()
                                ->arrayNode('dataSourceConfig')
                                    ->prototype('variable')
                                    ->end()
                                ->end()
                                ->arrayNode('columnConfiguration')
                                    ->prototype('variable')
                                    ->end()
                                ->end()
                                ->booleanNode('pagination')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('adapters')
                        ->useAttributeAsKey('name')
                            ->prototype('scalar')
                        ->end()
                    ->end()
                    ->arrayNode('sql_adapter')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('denied_tables')
                                ->info('Table names (case-insensitive, matched as whole words anywhere in the sql/from/where/groupby fragments of a Custom Report SQL data source) that reports are never allowed to reference.')
                                ->scalarPrototype()
                                    ->validate()
                                        ->ifTrue(static fn ($value): bool => !is_string($value))
                                        ->thenInvalid('Each entry in "denied_tables" must be a string, got %s.')
                                    ->end()
                                ->end()
                                ->defaultValue(['users'])
                            ->end()
                            ->arrayNode('denied_columns')
                                ->info('Column names (case-insensitive, matched as whole words anywhere in the sql/from/where/groupby fragments of a Custom Report SQL data source) that reports are never allowed to reference, regardless of table.')
                                ->scalarPrototype()
                                    ->validate()
                                        ->ifTrue(static fn ($value): bool => !is_string($value))
                                        ->thenInvalid('Each entry in "denied_columns" must be a string, got %s.')
                                    ->end()
                                ->end()
                                ->defaultValue(['password', 'passwordRecoveryToken', 'twoFactorAuthentication', 'apiKey', 'secret', 'token'])
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        ConfigurationHelper::addConfigLocationWithWriteTargetNodes($rootNode, ['custom_reports' => PIMCORE_CONFIGURATION_DIRECTORY . '/custom_reports']);

        return $treeBuilder;
    }
}
