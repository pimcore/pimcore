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

namespace Pimcore\Tests\Unit\Asset\StorageQueue;

use Codeception\Test\Unit;
use DateTimeImmutable;
use FilesystemIterator;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use LogicException;
use Pimcore\Asset\StorageQueue\QueueAwareStorageAdapter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class QueueAwareStorageAdapterTest extends Unit
{
    private string $tmpDir;

    private InMemoryStorageOperationQueueRepository $repository;

    protected function _before(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/queue-adapter-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->repository = new InMemoryStorageOperationQueueRepository();
    }

    protected function _after(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }

    private function adapter(): QueueAwareStorageAdapter
    {
        return new QueueAwareStorageAdapter(
            new LocalFilesystemAdapter($this->tmpDir),
            $this->repository,
            'asset',
        );
    }

    public function testDelegatesBasicOperationsWithEmptyQueue(): void
    {
        $adapter = $this->adapter();
        $adapter->write('folder/file.txt', 'content', new Config());

        $this->assertTrue($adapter->fileExists('folder/file.txt'));
        $this->assertTrue($adapter->directoryExists('folder'));
        $this->assertSame('content', $adapter->read('folder/file.txt'));
        $this->assertSame('content', stream_get_contents($adapter->readStream('folder/file.txt')));
        $this->assertGreaterThan(0, $adapter->fileSize('folder/file.txt')->fileSize());
        $this->assertNotNull($adapter->lastModified('folder/file.txt')->lastModified());
        $this->assertSame('text/plain', $adapter->mimeType('folder/file.txt')->mimeType());

        $adapter->copy('folder/file.txt', 'folder/copy.txt', new Config());
        $this->assertTrue($adapter->fileExists('folder/copy.txt'));

        $adapter->move('folder/copy.txt', 'folder/moved.txt', new Config());
        $this->assertTrue($adapter->fileExists('folder/moved.txt'));
        $this->assertFalse($adapter->fileExists('folder/copy.txt'));

        $adapter->delete('folder/moved.txt');
        $this->assertFalse($adapter->fileExists('folder/moved.txt'));

        $adapter->createDirectory('newdir', new Config());
        $this->assertTrue($adapter->directoryExists('newdir'));

        $paths = [];
        foreach ($adapter->listContents('folder', true) as $item) {
            $paths[] = $item->path();
        }
        $this->assertSame(['folder/file.txt'], $paths);

        $this->assertSame([], $this->repository->all(), 'no queue rows for single-file operations');
    }

    public function testPublicUrlThrowsWhenInnerDoesNotSupportIt(): void
    {
        // LocalFilesystemAdapter without a prefixer implements none of the URL interfaces
        $this->expectException(UnableToGeneratePublicUrl::class);

        $this->adapter()->publicUrl('folder/file.txt', new Config());
    }

    public function testTemporaryUrlThrowsWhenInnerDoesNotSupportIt(): void
    {
        // LocalFilesystemAdapter implements none of the URL interfaces
        $this->expectException(UnableToGenerateTemporaryUrl::class);

        $this->adapter()->temporaryUrl('folder/file.txt', new DateTimeImmutable(), new Config());
    }

    public function testChecksumFallsBackToStreamHashWhenInnerIsNotAChecksumProvider(): void
    {
        // NB: LocalFilesystemAdapter itself implements ChecksumProvider, so it can't be used to
        // exercise the fallback branch. This inner only implements FilesystemAdapter (delegating
        // to a real Local adapter for actual file I/O), which forces QueueAwareStorageAdapter
        // into CalculateChecksumFromStream.
        $inner = new class(new LocalFilesystemAdapter($this->tmpDir)) implements FilesystemAdapter {
            public function __construct(private readonly FilesystemAdapter $local)
            {
            }

            public function fileExists(string $path): bool
            {
                return $this->local->fileExists($path);
            }

            public function directoryExists(string $path): bool
            {
                return $this->local->directoryExists($path);
            }

            public function write(string $path, string $contents, Config $config): void
            {
                $this->local->write($path, $contents, $config);
            }

            public function writeStream(string $path, $contents, Config $config): void
            {
                $this->local->writeStream($path, $contents, $config);
            }

            public function read(string $path): string
            {
                return $this->local->read($path);
            }

            public function readStream(string $path)
            {
                return $this->local->readStream($path);
            }

            public function delete(string $path): void
            {
                $this->local->delete($path);
            }

            public function deleteDirectory(string $path): void
            {
                $this->local->deleteDirectory($path);
            }

            public function createDirectory(string $path, Config $config): void
            {
                $this->local->createDirectory($path, $config);
            }

            public function setVisibility(string $path, string $visibility): void
            {
                $this->local->setVisibility($path, $visibility);
            }

            public function visibility(string $path): FileAttributes
            {
                return $this->local->visibility($path);
            }

            public function mimeType(string $path): FileAttributes
            {
                return $this->local->mimeType($path);
            }

            public function lastModified(string $path): FileAttributes
            {
                return $this->local->lastModified($path);
            }

            public function fileSize(string $path): FileAttributes
            {
                return $this->local->fileSize($path);
            }

            public function listContents(string $path, bool $deep): iterable
            {
                return $this->local->listContents($path, $deep);
            }

            public function move(string $source, string $destination, Config $config): void
            {
                $this->local->move($source, $destination, $config);
            }

            public function copy(string $source, string $destination, Config $config): void
            {
                $this->local->copy($source, $destination, $config);
            }
        };

        $adapter = new QueueAwareStorageAdapter($inner, $this->repository, 'asset');
        $adapter->write('folder/file.txt', 'content', new Config());

        $this->assertSame(md5('content'), $adapter->checksum('folder/file.txt', new Config()));
    }

    public function testChecksumDelegatesToInnerWhenItIsAChecksumProvider(): void
    {
        $inner = new class implements FilesystemAdapter, ChecksumProvider {
            private ?string $receivedPath = null;

            public function checksum(string $path, Config $config): string
            {
                $this->receivedPath = $path;

                return 'sentinel-checksum';
            }

            public function getReceivedPath(): ?string
            {
                return $this->receivedPath;
            }

            public function fileExists(string $path): bool
            {
                throw new LogicException('not implemented in stub');
            }

            public function directoryExists(string $path): bool
            {
                throw new LogicException('not implemented in stub');
            }

            public function write(string $path, string $contents, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function writeStream(string $path, $contents, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function read(string $path): string
            {
                throw new LogicException('not implemented in stub');
            }

            public function readStream(string $path)
            {
                throw new LogicException('not implemented in stub');
            }

            public function delete(string $path): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function deleteDirectory(string $path): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function createDirectory(string $path, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function setVisibility(string $path, string $visibility): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function visibility(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function mimeType(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function lastModified(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function fileSize(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function listContents(string $path, bool $deep): iterable
            {
                throw new LogicException('not implemented in stub');
            }

            public function move(string $source, string $destination, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function copy(string $source, string $destination, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }
        };

        $adapter = new QueueAwareStorageAdapter($inner, $this->repository, 'asset');

        $this->assertSame('sentinel-checksum', $adapter->checksum('folder/file.txt', new Config()));
        $this->assertSame('folder/file.txt', $inner->getReceivedPath());
    }
}
