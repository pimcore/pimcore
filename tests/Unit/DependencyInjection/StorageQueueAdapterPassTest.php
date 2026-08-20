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

namespace Pimcore\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Asset\StorageQueue\QueueAwareStorageAdapter;
use Pimcore\Asset\StorageQueue\StorageOperationQueueProcessor;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\StorageQueueAdapterPass;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class StorageQueueAdapterPassTest extends Unit
{
    private function buildContainer(bool $enabled): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore.assets.storage_operation_queue.enabled', $enabled);
        foreach (['pimcore.asset.storage', 'pimcore.thumbnail.storage', 'pimcore.asset_cache.storage'] as $storage) {
            $container->setDefinition('flysystem.adapter.' . $storage, new Definition(stdClass::class));
        }
        (new StorageQueueAdapterPass())->process($container);

        return $container;
    }

    public function testDisabledFlagLeavesAdaptersUntouched(): void
    {
        $container = $this->buildContainer(false);

        $this->assertFalse($container->hasDefinition('pimcore.asset.storage_queue.adapter.asset'));
    }

    public function testEnabledFlagDecoratesAllThreeStorages(): void
    {
        $container = $this->buildContainer(true);

        foreach (['asset' => 'pimcore.asset.storage', 'thumbnail' => 'pimcore.thumbnail.storage', 'asset_cache' => 'pimcore.asset_cache.storage'] as $name => $storage) {
            $decoratorId = 'pimcore.asset.storage_queue.adapter.' . $name;
            $this->assertTrue($container->hasDefinition($decoratorId), $decoratorId);
            $definition = $container->getDefinition($decoratorId);
            $this->assertSame(QueueAwareStorageAdapter::class, $definition->getClass());
            $this->assertSame(['flysystem.adapter.' . $storage, null, 0], $definition->getDecoratedService());
            $this->assertSame($name, $definition->getArgument(2));
        }
    }

    public function testMissingAdapterServiceIsSkipped(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore.assets.storage_operation_queue.enabled', true);
        $container->setDefinition('flysystem.adapter.pimcore.asset.storage', new Definition(stdClass::class));

        (new StorageQueueAdapterPass())->process($container);

        $this->assertTrue($container->hasDefinition('pimcore.asset.storage_queue.adapter.asset'));
        $this->assertFalse($container->hasDefinition('pimcore.asset.storage_queue.adapter.thumbnail'));
    }

    public function testEnabledFlagRegistersTheProcessor(): void
    {
        $container = $this->buildContainer(true);

        $this->assertTrue($container->hasDefinition('pimcore.asset.storage_queue.processor'));
        $definition = $container->getDefinition('pimcore.asset.storage_queue.processor');
        $this->assertSame(StorageOperationQueueProcessor::class, $definition->getClass());
    }

    public function testDisabledFlagRegistersNoProcessor(): void
    {
        $container = $this->buildContainer(false);

        $this->assertFalse($container->hasDefinition('pimcore.asset.storage_queue.processor'));
    }
}
