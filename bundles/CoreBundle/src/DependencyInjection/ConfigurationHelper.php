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

use Pimcore\Config\LocationAwareConfigRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class ConfigurationHelper
{
    public static function addConfigLocationWithWriteTargetNodes(ArrayNodeDefinition $rootNode, array $nodes, array $additionalNodes = []): NodeBuilder
    {
        $storageNode = $rootNode
            ->children()
            ->arrayNode(LocationAwareConfigRepository::CONFIG_LOCATION)
            ->addDefaultsIfNotSet()
            ->children();

        foreach ($nodes as $node => $dir) {
            ConfigurationHelper::addConfigLocationTargetNode($storageNode, $node, $dir, $additionalNodes);
        }

        return $storageNode;
    }

    public static function addConfigLocationTargetNode(NodeBuilder $node, string $name, string $folder, array $additionalNodes = []): void
    {
        //BC reasons: Remove this check in Pimcore 12
        if (!str_starts_with($folder, PIMCORE_PROJECT_ROOT)) {
            $folder = PIMCORE_PROJECT_ROOT . $folder;
        }

        if (in_array(LocationAwareConfigRepository::READ_TARGET, $additionalNodes)) {
            $node->
            arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                ->arrayNode(LocationAwareConfigRepository::WRITE_TARGET)
                ->addDefaultsIfNotSet()
                ->children()
                ->enumNode(LocationAwareConfigRepository::TYPE)
                ->values([LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG, LocationAwareConfigRepository::LOCATION_SETTINGS_STORE, LocationAwareConfigRepository::LOCATION_DISABLED])
                ->defaultValue('symfony-config')
                ->end()
                ->arrayNode(LocationAwareConfigRepository::OPTIONS)
                ->defaultValue([LocationAwareConfigRepository::DIRECTORY => $folder])
                ->variablePrototype()->end()
                ->end()
                ->end()
                ->end()
                ->arrayNode(LocationAwareConfigRepository::READ_TARGET)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode(LocationAwareConfigRepository::TYPE)
                            ->values([LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG, LocationAwareConfigRepository::LOCATION_SETTINGS_STORE])
                            ->defaultValue(null)
                        ->end()
                        ->arrayNode(LocationAwareConfigRepository::OPTIONS)
                            ->defaultValue([LocationAwareConfigRepository::DIRECTORY => null])
                            ->variablePrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        } else {
            $node->
            arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode(LocationAwareConfigRepository::WRITE_TARGET)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode(LocationAwareConfigRepository::TYPE)
                        ->values([LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG, LocationAwareConfigRepository::LOCATION_SETTINGS_STORE, LocationAwareConfigRepository::LOCATION_DISABLED])
                        ->defaultValue('symfony-config')
                    ->end()
                    ->arrayNode(LocationAwareConfigRepository::OPTIONS)
                    ->defaultValue([LocationAwareConfigRepository::DIRECTORY => $folder])
                    ->variablePrototype()->end()
                    ->end()
                ->end();
        }
    }

    public static function getSymfonyConfigFiles(string $configPath, array $params = []): array
    {
        $result = [];
        $dirs = [];
        $finder = new Finder();

        if (is_dir($configPath)) {
            $dirs[]= $configPath;
        }

        if (empty($dirs)) {
            return [];
        }

        $finder
            ->files()
            ->in($dirs);

        foreach (['*.yml', '*.yaml'] as $namePattern) {
            $finder->name($namePattern);
        }

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            if ($params['relativePath'] ?? false) {
                $path = $file->getRelativePathname();
            }

            $result[] = $path;
        }

        return $result;
    }

    public static function getConfigNodeFromSymfonyTree(ContainerBuilder $container, string $nodeName): array
    {
        $containerConfig = $container->getExtensionConfig($nodeName);
        $containerConfig = array_merge(...$containerConfig);

        $processor = new Processor();
        // @phpstan-ignore-next-line
        $configuration = $container->getExtension($nodeName)->getConfiguration($containerConfig, $container);
        $containerConfig = $processor->processConfiguration($configuration, [$nodeName => $containerConfig]);

        $resolvingBag = $container->getParameterBag();

        return $resolvingBag->resolveValue($containerConfig);
    }
}
