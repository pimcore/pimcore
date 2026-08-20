<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Asset\StorageQueue\QueueAwareStorageAdapter;
use Pimcore\Asset\StorageQueue\StorageOperationQueueProcessor;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepositoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorates the asset-related Flysystem adapter services with the queue-aware adapter when
 * pimcore.assets.storage_operation_queue.enabled is true. The undecorated adapters stay
 * reachable via the standard <decoratorId>.inner references (used by the queue processor).
 *
 * @internal
 */
final class StorageQueueAdapterPass implements CompilerPassInterface
{
    private const STORAGES = [
        'asset' => 'flysystem.adapter.pimcore.asset.storage',
        'thumbnail' => 'flysystem.adapter.pimcore.thumbnail.storage',
        'asset_cache' => 'flysystem.adapter.pimcore.asset_cache.storage',
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('pimcore.assets.storage_operation_queue.enabled')
            || !$container->getParameter('pimcore.assets.storage_operation_queue.enabled')) {
            return;
        }

        foreach (self::STORAGES as $storageName => $adapterId) {
            if (!$container->hasDefinition($adapterId) && !$container->hasAlias($adapterId)) {
                continue;
            }

            $decoratorId = 'pimcore.asset.storage_queue.adapter.' . $storageName;
            $definition = new Definition(QueueAwareStorageAdapter::class);
            $definition->setDecoratedService($adapterId);
            $definition->setArguments([
                new Reference($decoratorId . '.inner'),
                new Reference(StorageOperationQueueRepositoryInterface::class),
                $storageName,
            ]);
            $container->setDefinition($decoratorId, $definition);
        }

        $innerReferences = [];
        foreach (self::STORAGES as $storageName => $adapterId) {
            if ($container->hasDefinition('pimcore.asset.storage_queue.adapter.' . $storageName)) {
                $innerReferences[$storageName] = new Reference('pimcore.asset.storage_queue.adapter.' . $storageName . '.inner');
            }
        }

        if ($innerReferences === []) {
            return;
        }

        $processor = new Definition(StorageOperationQueueProcessor::class);
        $processor->setArguments([
            ServiceLocatorTagPass::register($container, $innerReferences),
            new Reference(StorageOperationQueueRepositoryInterface::class),
            new Reference('logger'),
        ]);
        $container->setDefinition('pimcore.asset.storage_queue.processor', $processor);
    }
}
