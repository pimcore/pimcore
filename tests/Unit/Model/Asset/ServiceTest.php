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

namespace Pimcore\Tests\Unit\Model\Asset;

use League\Flysystem\FilesystemOperator;
use Pimcore;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Service;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Storage;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
class ServiceTest extends TestCase
{
    public function testGetStreamedResponseFromImageThumbnailReturnsNullOnGenerationError(): void
    {
        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'error',
            'src' => '/bundles/pimcoreadmin/img/filetype-not-supported.svg',
        ]);

        // the nullable contract of this public helper also applies to failed generations;
        // none of the stream/metadata operations may run for the placeholder path reference
        $thumbnail->expects($this->never())->method('getStream');
        $thumbnail->expects($this->never())->method('getMimeType');
        $thumbnail->expects($this->never())->method('getFileSize');

        $response = Service::getStreamedResponseFromImageThumbnail($thumbnail, [
            'type' => 'image',
            'filename' => 'testimage.jpg',
        ]);

        $this->assertNull($response);
    }

    public function testGetStreamedResponseFromImageThumbnailReturnsNullForMissingStream(): void
    {
        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'thumbnail',
            'src' => '/testimage/1/image-thumb__1__unittest/testimage.jpg',
            'storagePath' => '/testimage/1/image-thumb__1__unittest/testimage.jpg',
        ]);
        $thumbnail->method('getStream')->willReturn(null);

        // none of the metadata/copy operations may run on a missing stream, they
        // would fail on the storage or with a TypeError (writeStream(null))
        $thumbnail->expects($this->never())->method('getMimeType');
        $thumbnail->expects($this->never())->method('getFileSize');

        $response = Service::getStreamedResponseFromImageThumbnail($thumbnail, [
            'type' => 'image',
            'filename' => 'testimage.jpg',
        ]);

        $this->assertNull($response);
    }

    public function testGetStreamedResponseFromImageThumbnailDoesNotMirrorAssetPassthroughUnderRequestedExtension(): void
    {
        // a pass-through path reference points at the original asset's own bytes, so it must
        // never be copied into thumbnail storage under a caller-chosen extension
        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'asset',
            'src' => '/original-asset.svg',
        ]);
        $thumbnail->method('getStream')->willReturn(fopen('php://memory', 'r+'));
        $thumbnail->method('getMimeType')->willReturn('image/svg+xml');
        $thumbnail->method('getFileSize')->willReturn(0);

        $storageMock = $this->createMock(FilesystemOperator::class);
        $storageMock->expects($this->never())->method('fileExists');
        $storageMock->expects($this->never())->method('writeStream');
        $storageMock->expects($this->never())->method('readStream');

        $response = $this->withThumbnailStorage($storageMock, fn () => Service::getStreamedResponseFromImageThumbnail($thumbnail, [
            'type' => 'image',
            // the requested extension differs from the asset's own; only the extension is
            // attacker-controlled here, the write target would be derived from the asset path
            'filename' => 'zzz-totally-unrelated.php',
        ]));

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testGetStreamedResponseFromImageThumbnailDoesNotWriteDisallowedRequestedFormat(): void
    {
        // even for a real generated thumbnail (not a pass-through), the requested extension
        // must be a configured allowed format before it is mirrored into thumbnail storage
        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'thumbnail',
            'src' => '/testimage/1/image-thumb__1__unittest/testimage.jpeg',
        ]);
        $thumbnail->method('getStream')->willReturn(fopen('php://memory', 'r+'));
        $thumbnail->method('getMimeType')->willReturn('image/jpeg');
        $thumbnail->method('getFileSize')->willReturn(0);

        $storageMock = $this->createMock(FilesystemOperator::class);
        $storageMock->expects($this->never())->method('fileExists');
        $storageMock->expects($this->never())->method('writeStream');
        $storageMock->expects($this->never())->method('readStream');

        $response = $this->withThumbnailStorage($storageMock, fn () => Service::getStreamedResponseFromImageThumbnail($thumbnail, [
            'type' => 'image',
            'filename' => 'testimage.php',
        ]));

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testGetStreamedResponseFromImageThumbnailStillMirrorsAllowedAutoFormatMismatch(): void
    {
        // legitimate case the copy exists for: an auto-optimized thumbnail generated as jpeg but
        // requested as png must still be mirrored under the requested (allowed) extension
        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'thumbnail',
            'src' => '/testimage/1/image-thumb__1__unittest/testimage.jpeg',
        ]);
        $stream = fopen('php://memory', 'r+');
        $thumbnail->method('getStream')->willReturn($stream);
        $thumbnail->method('getMimeType')->willReturn('image/jpeg');
        $thumbnail->method('getFileSize')->willReturn(0);

        $requestedPath = '/testimage/1/image-thumb__1__unittest/testimage.png';

        $storageMock = $this->createMock(FilesystemOperator::class);
        $storageMock->expects($this->once())
            ->method('fileExists')
            ->with($requestedPath)
            ->willReturn(false);
        $storageMock->expects($this->once())
            ->method('writeStream')
            ->with($requestedPath, $stream);
        $storageMock->expects($this->once())
            ->method('readStream')
            ->with($requestedPath)
            ->willReturn(fopen('php://memory', 'r+'));

        $response = $this->withThumbnailStorage($storageMock, fn () => Service::getStreamedResponseFromImageThumbnail($thumbnail, [
            'type' => 'image',
            'filename' => 'testimage.png',
        ]));

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    /**
     * Runs $callback with the thumbnail storage replaced by $storage.
     *
     * Pimcore\Tool\Storage resolves each storage from a tagged service locator, so the whole
     * service is swapped for one backed by a locator that returns $storage for the thumbnail
     * storage and delegates everything else to the original.
     */
    private function withThumbnailStorage(FilesystemOperator $storage, callable $callback): mixed
    {
        $storageService = Pimcore::getContainer()->get(Storage::class);

        $property = new ReflectionProperty(Storage::class, 'locator');
        $originalLocator = $property->getValue($storageService);

        $property->setValue($storageService, new class($storage, $originalLocator) implements ContainerInterface {
            public function __construct(
                private FilesystemOperator $thumbnailStorage,
                private ContainerInterface $original,
            ) {
            }

            public function has(string $id): bool
            {
                return $id === 'pimcore.thumbnail.storage' || $this->original->has($id);
            }

            public function get(string $id): mixed
            {
                return $id === 'pimcore.thumbnail.storage'
                    ? $this->thumbnailStorage
                    : $this->original->get($id);
            }
        });

        try {
            return $callback();
        } finally {
            $property->setValue($storageService, $originalLocator);
        }
    }
}
