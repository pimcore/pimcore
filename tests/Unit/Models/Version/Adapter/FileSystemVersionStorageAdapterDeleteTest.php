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

namespace Pimcore\Tests\Unit\Models\Version\Adapter;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Tests\Support\Test\TestCase;

class FileSystemVersionStorageAdapterDeleteTest extends TestCase
{
    private function createAdapter(FilesystemOperator $storage): FileSystemVersionStorageAdapter
    {
        $adapter = new \ReflectionClass(FileSystemVersionStorageAdapter::class);
        $instance = $adapter->newInstanceWithoutConstructor();

        $prop = $adapter->getProperty('storage');
        $prop->setAccessible(true);
        $prop->setValue($instance, $storage);

        return $instance;
    }

    private function createVersion(int $id = 1, int $cid = 1, string $ctype = 'object'): Version
    {
        $version = new Version();
        $version->setId($id);
        $version->setCid($cid);
        $version->setCtype($ctype);

        return $version;
    }

    public function testDeleteIgnoresAlreadyDeletedFile(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);

        $storage->expects($this->once())
            ->method('delete')
            ->willThrowException(UnableToDeleteFile::atLocation('test/path'));

        // File no longer exists — race condition, should be silently ignored
        $storage->method('fileExists')
            ->willReturn(false);

        $adapter = $this->createAdapter($storage);
        $version = $this->createVersion();

        // Should not throw
        $adapter->delete($version, true);
    }

    public function testDeleteRethrowsWhenFileStillExists(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);

        $storage->expects($this->once())
            ->method('delete')
            ->willThrowException(UnableToDeleteFile::atLocation('test/path', 'Permission denied'));

        // File still exists — real error, should rethrow
        $storage->method('fileExists')
            ->willReturn(true);

        $adapter = $this->createAdapter($storage);
        $version = $this->createVersion();

        $this->expectException(UnableToDeleteFile::class);
        $adapter->delete($version, true);
    }

    public function testDeleteBinaryFileIgnoresAlreadyDeleted(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);

        $callCount = 0;
        $storage->method('delete')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 2) {
                    throw UnableToDeleteFile::atLocation('test/path.bin');
                }
            });

        // First call (metadata file) succeeds, second call (binary) throws
        $storage->method('fileExists')
            ->willReturn(false);

        $adapter = $this->createAdapter($storage);
        $version = $this->createVersion();

        // Should not throw — binary file already gone
        $adapter->delete($version, false);
    }

    public function testDeleteBinaryFileRethrowsOnRealError(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);

        $callCount = 0;
        $storage->method('delete')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 2) {
                    throw UnableToDeleteFile::atLocation('test/path.bin', 'Permission denied');
                }
            });

        $storage->method('fileExists')
            ->willReturnCallback(function (string $path) {
                // Binary file still exists — real error
                return str_ends_with($path, '.bin');
            });

        $adapter = $this->createAdapter($storage);
        $version = $this->createVersion();

        $this->expectException(UnableToDeleteFile::class);
        $adapter->delete($version, false);
    }
}
