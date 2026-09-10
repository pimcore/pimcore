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

use ArrayIterator;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use Pimcore\Config;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use ReflectionMethod;

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
        // Even though the delete raced, the empty-directory sweep must still
        // run for the version's storage directory. Returning a non-empty
        // listing stops the recursion at the first directory without
        // exercising deleteDirectory().
        $storage
            ->expects($this->once())
            ->method('listContents')
            ->with('object/g10000/12345')
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
        // The failed delete is rethrown, so no directory cleanup may happen.
        $storage
            ->expects($this->never())
            ->method('listContents');

        $adapter = $this->createAdapterWithStorage($storage);

        $this->expectException(UnableToDeleteFile::class);
        $adapter->delete($version, false);
    }

    public function testResolveLocalFilePathReturnsNullForInMemoryStream(): void
    {
        $adapter = $this->createAdapterWithStorage($this->createMock(FilesystemOperator::class));
        $stream = fopen('php://temp', 'r+');

        try {
            $this->assertNull($this->callResolveLocalFilePath($adapter, $stream));
        } finally {
            fclose($stream);
        }
    }

    public function testResolveLocalFilePathReturnsPathForRealFile(): void
    {
        $adapter = $this->createAdapterWithStorage($this->createMock(FilesystemOperator::class));
        $tmpFile = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];

        try {
            $this->assertSame($tmpFilePath, $this->callResolveLocalFilePath($adapter, $tmpFile));
        } finally {
            fclose($tmpFile);
        }
    }

    public function testSaveFallsBackToWriteStreamWhenBinaryDataStreamIsInMemory(): void
    {
        $version = $this->createVersion(100, 12345, 'asset');
        $metadataFilePath = 'asset/g10000/12345/100';
        $binaryFilePath = 'asset/g10000/12345/100.bin';

        $binaryDataStream = fopen('php://temp', 'r+');
        fwrite($binaryDataStream, 'version binary data');
        rewind($binaryDataStream);

        // the freshly written .bin temp file resolves to a real local file, so
        // only the in-memory source stream prevents the hardlink
        $existingFileStream = tmpfile();

        $writtenPaths = [];
        $storage = $this->createMock(FilesystemOperator::class);
        $storage
            ->expects($this->exactly(2))
            ->method('write')
            ->willReturnCallback(function (string $path) use (&$writtenPaths): void {
                $writtenPaths[] = $path;
            });
        $storage
            ->expects($this->once())
            ->method('fileExists')
            ->with($binaryFilePath)
            ->willReturn(false);
        $storage
            ->expects($this->once())
            ->method('readStream')
            ->with($binaryFilePath)
            ->willReturn($existingFileStream);
        // the hardlink must not be attempted, so the .bin temp file may never
        // be deleted before the fallback writes the actual stream content
        $storage
            ->expects($this->never())
            ->method('delete');
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->with($binaryFilePath, $this->identicalTo($binaryDataStream));

        $adapter = $this->createAdapterWithStorage($storage);

        $originalAssetsConfig = Config::getSystemConfiguration('assets');
        $assetsConfig = $originalAssetsConfig ?? [];
        $assetsConfig['versions']['use_hardlinks'] = true;
        Config::setSystemConfiguration($assetsConfig, 'assets');

        try {
            $adapter->save($version, 'metadata', $binaryDataStream);
        } finally {
            Config::setSystemConfiguration($originalAssetsConfig, 'assets');
            fclose($binaryDataStream);
            fclose($existingFileStream);
        }

        $this->assertSame([$metadataFilePath, $binaryFilePath], $writtenPaths);
    }

    private function callResolveLocalFilePath(FileSystemVersionStorageAdapter $adapter, mixed $stream): ?string
    {
        $method = new ReflectionMethod(FileSystemVersionStorageAdapter::class, 'resolveLocalFilePath');
        $method->setAccessible(true);

        return $method->invoke($adapter, $stream);
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
