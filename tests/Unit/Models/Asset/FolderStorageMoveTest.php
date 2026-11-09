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

namespace Pimcore\Tests\Unit\Models\Asset;

use Codeception\Test\Unit;
use FilesystemIterator;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Pimcore\Model\Asset;
use Pimcore\Tests\Unit\Asset\StorageQueue\MarkerSemanticsAdapterDecorator;
use Pimcore\Tests\Unit\Asset\StorageQueue\NonRenamingAdapterDecorator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;

/**
 * Covers Asset::moveDirectoryOnStorage(): the physical part of a folder rename/move.
 * Whether the per-file fallback runs must be decided by what is actually left at the
 * old path, not by whether the native move threw - on marker-materializing backends
 * (PEES-1617) the native move "succeeds" while the whole subtree stays behind.
 */
class FolderStorageMoveTest extends Unit
{
    private string $tmpDir;

    protected function _before(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/folder-storage-move-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
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

    public function testDirectoryMoveFallsBackWhenNativeMoveRelocatesOnlyAMarkerObject(): void
    {
        $decorator = new MarkerSemanticsAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir));
        $storage = new Filesystem($decorator);
        $storage->write('old-folder/sub/image.jpg', 'payload');
        $decorator->addMarker('old-folder');

        $this->moveDirectoryOnStorage($storage, '/old-folder', 'new-folder');

        self::assertTrue($storage->fileExists('/new-folder/sub/image.jpg'), 'children must be relocated');
        self::assertSame('payload', $storage->read('/new-folder/sub/image.jpg'));
        self::assertFalse($storage->directoryExists('/old-folder'), 'nothing may stay at the old prefix');
    }

    public function testDirectoryMoveFallsBackWhenNativeMoveIsNotSupported(): void
    {
        $decorator = new NonRenamingAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir));
        $storage = new Filesystem($decorator);
        $storage->write('old-folder/sub/image.jpg', 'payload');

        $this->moveDirectoryOnStorage($storage, '/old-folder', 'new-folder');

        self::assertTrue($storage->fileExists('/new-folder/sub/image.jpg'));
        self::assertFalse($storage->directoryExists('/old-folder'));
    }

    public function testDirectoryMoveUsesTheNativeMoveWhereSupported(): void
    {
        $storage = new Filesystem(new LocalFilesystemAdapter($this->tmpDir));
        $storage->write('old-folder/sub/image.jpg', 'payload');

        $this->moveDirectoryOnStorage($storage, '/old-folder', 'new-folder');

        self::assertTrue($storage->fileExists('/new-folder/sub/image.jpg'));
        self::assertFalse($storage->directoryExists('/old-folder'));
    }

    public function testThumbnailFallbackOfANonFolderAssetRelocatesTheThumbnailDirectory(): void
    {
        // For a non-folder asset the thumbnail directory (".../<id>") differs from the
        // asset's own old path (".../<filename>") - the fallback must operate on the
        // thumbnail paths, not on the asset path.
        $decorator = new NonRenamingAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir));
        $storage = new Filesystem($decorator);
        $storage->write('old-dir/123/image-thumb__123__preview/img.jpg', 'thumb-bytes');

        $image = new Asset\Image();
        $image->setPath('/new-dir/');
        $image->setFilename('img.jpg');

        $method = new ReflectionMethod(Asset::class, 'moveThumbnailDirectoryOnStorage');
        $method->invoke($image, $storage, '/old-dir/123', '/new-dir/123');

        self::assertTrue(
            $storage->fileExists('/new-dir/123/image-thumb__123__preview/img.jpg'),
            'thumbnails must be relocated to the new thumbnail directory'
        );
        self::assertFalse($storage->directoryExists('/old-dir/123'), 'nothing may stay at the old thumbnail directory');
    }

    private function moveDirectoryOnStorage(Filesystem $storage, string $oldPath, string $newFilename): void
    {
        $folder = new Asset\Folder();
        $folder->setPath('/');
        $folder->setFilename($newFilename);

        $method = new ReflectionMethod(Asset::class, 'moveDirectoryOnStorage');
        $method->invoke($folder, $storage, $oldPath);
    }
}
