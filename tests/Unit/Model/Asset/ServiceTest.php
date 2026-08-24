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
use League\Flysystem\UnableToReadFile;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Service;
use Pimcore\Tests\Support\Test\TestCase;

class ServiceTest extends TestCase
{
    public function testGetStreamedResponseForThumbnailRethrowsWhenDirectDeliveryReadFails(): void
    {
        $uri = '/testimage/1/image-thumb__1__unittest/testimage.jpg';

        $storage = $this->createMock(FilesystemOperator::class);
        // the file exists on the direct-delivery check and still exists after the failed read
        $storage->method('fileExists')->willReturn(true);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation($uri));

        $this->expectException(UnableToReadFile::class);
        Service::getStreamedResponseForThumbnail([
            'type' => 'image',
            'asset_id' => 1,
            'thumbnail_name' => 'unittest',
            'filename' => 'testimage.jpg',
            'file_extension' => 'jpg',
            'prefix' => '',
        ], $uri, $storage);
    }

    public function testGetStreamedResponseForThumbnailFallsThroughWhenFileDisappears(): void
    {
        $uri = '/testimage/999999999/image-thumb__999999999__unittest/testimage.jpg';

        $storage = $this->createMock(FilesystemOperator::class);
        // the file exists on the direct-delivery check, but is gone when read and re-checked
        $storage->method('fileExists')->willReturnOnConsecutiveCalls(true, false);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation($uri));

        // falls through to the regular thumbnail resolution, which cannot resolve the
        // non-existing asset and returns null (-> 404 at the controller)
        $response = Service::getStreamedResponseForThumbnail([
            'type' => 'image',
            'asset_id' => 999999999,
            'thumbnail_name' => 'unittest',
            'filename' => 'testimage.jpg',
            'file_extension' => 'jpg',
            'prefix' => '',
        ], $uri, $storage);

        $this->assertNull($response);
    }

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
}
