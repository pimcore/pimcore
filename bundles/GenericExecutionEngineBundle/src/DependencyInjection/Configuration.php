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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\DependencyInjection;

use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_generic_execution_engine');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode->children()
            ->enumNode('error_handling')
                ->values([ErrorHandlingMode::CONTINUE_ON_ERROR->value, ErrorHandlingMode::STOP_ON_FIRST_ERROR->value])
                ->info('Specifies how errors should be handled for all job run executions.')
                ->defaultValue(ErrorHandlingMode::CONTINUE_ON_ERROR->value)
            ->end()
            ->arrayNode('execution_context')
                ->prototype('array')
                    ->children()
                        ->scalarNode('translations_domain')
                            ->info('Translation domain which should be used by the job run. Default value is "admin".')
                            ->defaultValue('admin')
                        ->end()
                        ->enumNode('error_handling')
                            ->values(
                                [
                                    ErrorHandlingMode::CONTINUE_ON_ERROR->value,
                                    ErrorHandlingMode::STOP_ON_FIRST_ERROR->value,
                                ]
                            )
                            ->info(
                                'Error handling behavior which should be used by the job run.' .
                                ' Overrides the global value.'
                            )
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
