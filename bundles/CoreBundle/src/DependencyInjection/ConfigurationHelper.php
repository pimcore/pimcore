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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

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

        foreach ($nodes as $node) {
            ConfigurationHelper::addConfigLocationTargetNode($storageNode, $node, '/var/config/' . $node);
        }

    }

    public static function addConfigLocationTargetNode(NodeBuilder $node, string $name, string $folder): void
    {
        $node->
        arrayNode($name)
            ->addDefaultsIfNotSet()
            ->children()
            ->enumNode('target')
            ->values(['symfony-config', 'settings-store'])
            ->defaultValue('symfony-config')
            ->end()
            ->arrayNode('options')
            ->defaultValue(['directory' => '%kernel.project_dir%' . $folder])
            ->variablePrototype()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    public static function getSymfonyConfigFiles(string $configPath, array $params = []): array
    {
        $result = [];
        $dirs = [];
        $finder = new Finder();

        if (is_dir($configPath)) {
            $dirs []= $configPath;
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

    public static function getConfigNodeFromSymfonyTree( ContainerBuilder $container, string $nodeName): array
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
