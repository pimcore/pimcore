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
use FilesystemIterator;
use League\Flysystem\Config;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToGeneratePublicUrl;
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
}
