<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\JobExecutionEngineBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_job_execution_engine');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode->children()
            ->arrayNode('execution_context')
                ->prototype('array')
                    ->children()
                        ->scalarNode('translations_domain')
                            ->info('Translation domain which should be used by the job run. Default value is "admin".')
                            ->defaultValue('admin')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
