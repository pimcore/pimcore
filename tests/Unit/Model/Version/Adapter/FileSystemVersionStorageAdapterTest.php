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

declare(strict_types=1);

namespace Pimcore\Tests\Unit\Model\Version\Adapter;

use ArrayIterator;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;

class FileSystemVersionStorageAdapterTest extends TestCase
{
    public function testDeleteIgnoresDeleteRaceWhenFileDisappears(): void
    {
        $version = $this->createVersion(100, 12345, 'object');
        $storageFilePath = 'object/g10000/12345/100';
        $binaryFilePath = 'object/g10000/12345/100.bin';

        $checkedPaths = [];
        $storage = $this->createMock(FilesystemOperator::class);
        $storage
            ->expects($this->exactly(2))
            ->method('delete')
            ->willThrowException(UnableToDeleteFile::atLocation('any/location', 'No such file or directory'));
        $storage
            ->expects($this->exactly(2))
            ->method('fileExists')
            ->willReturnCallback(function (string $path) use (&$checkedPaths): bool {
                $checkedPaths[] = $path;

                return false;
            });
        // recursiveDeleteEmptyDirs runs after the storage file is "deleted"
        // (race won). Returning a non-empty listing stops the recursion at the
        // first directory without exercising deleteDirectory().
        $storage
            ->method('listContents')
            ->willReturn(new DirectoryListing(new ArrayIterator([
                new FileAttributes('other-version'),
            ])));

        $adapter = $this->createAdapterWithStorage($storage);

        $adapter->delete($version, false);

        $this->assertSame([$storageFilePath, $binaryFilePath], $checkedPaths);
    }

    public function testDeleteRethrowsDeleteFailureWhenFileStillExists(): void
    {
        $version = $this->createVersion(100, 12345, 'object');
        $storage = $this->createMock(FilesystemOperator::class);
        $storage
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(UnableToDeleteFile::atLocation('any/location', 'Permission denied'));
        $storage
            ->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $adapter = $this->createAdapterWithStorage($storage);

        $this->expectException(UnableToDeleteFile::class);
        $adapter->delete($version, false);
    }

    private function createAdapterWithStorage(FilesystemOperator $storage): FileSystemVersionStorageAdapter
    {
        $adapterReflection = new ReflectionClass(FileSystemVersionStorageAdapter::class);
        /** @var FileSystemVersionStorageAdapter $adapter */
        $adapter = $adapterReflection->newInstanceWithoutConstructor();

        $storageProperty = $adapterReflection->getProperty('storage');
        $storageProperty->setValue($adapter, $storage);

        return $adapter;
    }

    private function createVersion(int $id, int $cid, string $ctype): Version
    {
        $versionReflection = new ReflectionClass(Version::class);
        /** @var Version $version */
        $version = $versionReflection->newInstanceWithoutConstructor();

        $version->setId($id);
        $version->setCid($cid);
        $version->setCtype($ctype);

        return $version;
    }
}
