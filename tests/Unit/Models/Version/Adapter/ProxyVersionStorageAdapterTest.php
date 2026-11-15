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

namespace Pimcore\Tests\Unit\Model\Version\Adapter;

use Pimcore\Model\Version\Adapter\ElementDelegateVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\ElementTypeAwareStorageTypeInterface;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\ProxyVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;

class ProxyVersionStorageAdapterTest extends TestCase
{
    public function testElementTypeAwareStorageTypeIsForwarded(): void
    {
        $proxy = $this->createProxy();
        $this->assertInstanceOf(ElementTypeAwareStorageTypeInterface::class, $proxy);

        $asset = $this->createMock(VersionStorageAdapterInterface::class);
        $asset->method('getStorageType')->willReturn('db');
        $default = $this->createMock(VersionStorageAdapterInterface::class);
        $default->method('getStorageType')->willReturn('fs');
        $proxy->setStorageAdapter(new ElementDelegateVersionStorageAdapter(['asset' => $asset], $default));

        $this->assertSame('db', $proxy->getStorageTypeForElementType('asset', 1, 1));
        $this->assertSame('fs', $proxy->getStorageTypeForElementType('object', 1, null));
    }

    public function testPlainAdapterFallsBackToGetStorageType(): void
    {
        $proxy = $this->createProxy();
        $plain = $this->createMock(VersionStorageAdapterInterface::class);
        $plain->method('getStorageType')->with(5, 7)->willReturn('db');
        $proxy->setStorageAdapter($plain);

        $this->assertSame('db', $proxy->getStorageTypeForElementType('asset', 5, 7));
    }

    private function createProxy(): ProxyVersionStorageAdapter
    {
        // the constructor requires a FileSystemVersionStorageAdapter, which needs the container for its default storage
        $fileSystemAdapter = (new ReflectionClass(FileSystemVersionStorageAdapter::class))->newInstanceWithoutConstructor();

        return new ProxyVersionStorageAdapter($fileSystemAdapter);
    }
}
