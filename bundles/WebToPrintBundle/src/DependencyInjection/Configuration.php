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

namespace Pimcore\Bundle\WebToPrintBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_web_to_print');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->scalarNode('pdf_creation_php_memory_limit')
                    ->defaultValue('2048M')
                ->end()
                ->scalarNode('default_controller_print_page')
                    ->defaultValue('App\\Controller\\Web2printController::defaultAction')
                ->end()
                ->scalarNode('default_controller_print_container')
                    ->defaultValue('App\\Controller\\Web2printController::containerAction')
                ->end()
                ->booleanNode('enableInDefaultView')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('generalTool')
                    ->defaultValue('')
                ->end()
                ->scalarNode('generalDocumentSaveMode')->end()
                ->scalarNode('pdfreactorVersion')->end()
                ->scalarNode('pdfreactorProtocol')->end()
                ->scalarNode('pdfreactorServer')->end()
                ->scalarNode('pdfreactorServerPort')->end()
                ->scalarNode('pdfreactorBaseUrl')->end()
                ->scalarNode('pdfreactorApiKey')->end()
                ->scalarNode('pdfreactorLicence')->end()
                ->booleanNode('pdfreactorEnableLenientHttpsMode')->end()
                ->booleanNode('pdfreactorEnableDebugMode')->end()
                ->scalarNode('chromiumHostUrl')->end()
                ->scalarNode('chromiumSettings')->end()
                ->scalarNode('gotenbergHostUrl')->end()
                ->scalarNode('gotenbergSettings')->end()
            ->end();

        return $treeBuilder;
    }
}
