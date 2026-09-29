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

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\DelegateVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\ElementDelegateVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use stdClass;

class ElementDelegateVersionStorageAdapterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRoutes(): iterable
    {
        yield 'asset goes to its route' => ['asset', 'asset'];
        yield 'object goes to its route' => ['object', 'object'];
        yield 'unmapped document goes to the default' => ['document', 'default'];
    }

    /**
     * @dataProvider provideRoutes
     */
    public function testAllOperationsAreRoutedByElementType(string $elementType, string $expectedRoute): void
    {
        $routes = [
            'asset' => $this->createAdapter('fs'),
            'object' => $this->createAdapter('fs'),
            'default' => $this->createAdapter('fs'),
        ];
        $version = $this->createVersion($elementType);
        $stream = fopen('php://memory', 'r+');

        foreach ($routes as $name => $adapter) {
            $calls = $name === $expectedRoute ? $this->once() : $this->never();
            $adapter->expects($calls)->method('save')->with($version, 'meta', $stream);
            $adapter->expects($name === $expectedRoute ? $this->once() : $this->never())->method('loadMetaData')->with($version)->willReturn('meta');
            $adapter->expects($name === $expectedRoute ? $this->once() : $this->never())->method('loadBinaryData')->with($version)->willReturn($stream);
            $adapter->expects($name === $expectedRoute ? $this->once() : $this->never())->method('getBinaryFileStream')->with($version)->willReturn($stream);
            $adapter->expects($name === $expectedRoute ? $this->once() : $this->never())->method('getFileStream')->with($version)->willReturn($stream);
            $adapter->expects($name === $expectedRoute ? $this->once() : $this->never())->method('delete')->with($version, true);
        }

        $delegate = new ElementDelegateVersionStorageAdapter(
            ['asset' => $routes['asset'], 'object' => $routes['object']],
            $routes['default'],
        );

        $delegate->save($version, 'meta', $stream);
        $this->assertSame('meta', $delegate->loadMetaData($version));
        $this->assertSame($stream, $delegate->loadBinaryData($version));
        $this->assertSame($stream, $delegate->getBinaryFileStream($version));
        $this->assertSame($stream, $delegate->getFileStream($version));
        $delegate->delete($version, true);
    }

    public function testStorageTypeIsTheOneOfTheRoute(): void
    {
        $delegate = new ElementDelegateVersionStorageAdapter(
            ['asset' => $this->createAdapter('fs')],
            $this->createAdapter('db'),
        );

        $this->assertSame('fs', $delegate->getStorageTypeForElementType('asset', 10, 10));
        $this->assertSame('db', $delegate->getStorageTypeForElementType('object', 10, null));
        $this->assertSame('db', $delegate->getStorageTypeForElementType('document', 10, null));
    }

    public function testStorageTypeOfANestedSizeBasedDelegateDependsOnTheSize(): void
    {
        $sizeBased = new DelegateVersionStorageAdapter(100, $this->createAdapter('db'), $this->createAdapter('fs'));
        $delegate = new ElementDelegateVersionStorageAdapter(['object' => $sizeBased], $this->createAdapter('fs'));

        $this->assertSame('db', $delegate->getStorageTypeForElementType('object', 50, null));
        $this->assertSame('fs', $delegate->getStorageTypeForElementType('object', 500, null));
    }

    public function testStorageTypeOfANestedElementDelegateIsResolvedForTheElementType(): void
    {
        $nested = new ElementDelegateVersionStorageAdapter(['asset' => $this->createAdapter('db')], $this->createAdapter('fs'));
        $delegate = new ElementDelegateVersionStorageAdapter(['asset' => $nested], $this->createAdapter('fs'));

        $this->assertSame('db', $delegate->getStorageTypeForElementType('asset', 10, 10));
    }

    public function testStorageTypeWithoutElementTypeIsTheOneOfTheDefault(): void
    {
        $delegate = new ElementDelegateVersionStorageAdapter(['asset' => $this->createAdapter('fs')], $this->createAdapter('db'));

        $this->assertSame('db', $delegate->getStorageType(10, 10));
    }

    public function testUnknownElementTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"objects"');

        new ElementDelegateVersionStorageAdapter(['objects' => $this->createAdapter('fs')], $this->createAdapter('fs'));
    }

    public function testRouteThatIsNotAnAdapterIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"asset"');

        new ElementDelegateVersionStorageAdapter(['asset' => new stdClass()], $this->createAdapter('fs'));
    }

    private function createAdapter(string $storageType): VersionStorageAdapterInterface&MockObject
    {
        $adapter = $this->createMock(VersionStorageAdapterInterface::class);
        $adapter->method('getStorageType')->willReturn($storageType);

        return $adapter;
    }

    private function createVersion(string $elementType): Version
    {
        /** @var Version $version */
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setId(100);
        $version->setCid(12345);
        $version->setCtype($elementType);

        return $version;
    }
}
