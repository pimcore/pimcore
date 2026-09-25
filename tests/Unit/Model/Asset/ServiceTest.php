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

use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Service;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Storage;

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

    public function testGetStreamedResponseFromImageThumbnailRejectsExtensionOutsideAllowedFormats(): void
    {
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        rewind($stream);

        $thumbnail = $this->createMock(ThumbnailInterface::class);
        // this mirrors the SVG pass-through path reference, which points straight at
        // the (attacker-controlled) original asset instead of a generated thumbnail
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'asset',
            'src' => '/evil/4/evil.svg',
        ]);
        $thumbnail->method('getStream')->willReturn($stream);
        $thumbnail->method('getMimeType')->willReturn('image/svg+xml');
        $thumbnail->method('getFileSize')->willReturn(strlen('<svg xmlns="http://www.w3.org/2000/svg"></svg>'));

        $storage = Storage::get('thumbnail');
        $requestedFile = '/evil/4/evil.php';
        if ($storage->fileExists($requestedFile)) {
            $storage->delete($requestedFile);
        }

        try {
            $response = Service::getStreamedResponseFromImageThumbnail($thumbnail, [
                'type' => 'image',
                'filename' => 'evil.php',
            ]);

            $this->assertNull($response);
            $this->assertFalse(
                $storage->fileExists($requestedFile),
                'a requested extension outside the configured allowed_formats must never be persisted to the public thumbnail storage'
            );
        } finally {
            if ($storage->fileExists($requestedFile)) {
                $storage->delete($requestedFile);
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function testGetStreamedResponseFromImageThumbnailStillServesAllowedMismatchedExtension(): void
    {
        $content = 'not-a-real-jpeg-but-good-enough-for-the-copy';
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        $thumbnail = $this->createMock(ThumbnailInterface::class);
        // an already-generated thumbnail requested under a different (still allowed) output
        // format, e.g. auto-optimized format negotiation from jpg to webp
        $thumbnail->method('getPathReference')->willReturn([
            'type' => 'thumbnail',
            'src' => '/testimage/1/image-thumb__1__unittest/testimage.jpg',
            'storagePath' => '/testimage/1/image-thumb__1__unittest/testimage.jpg',
        ]);
        $thumbnail->method('getStream')->willReturn($stream);
        $thumbnail->method('getMimeType')->willReturn('image/webp');
        $thumbnail->method('getFileSize')->willReturn(strlen($content));

        $storage = Storage::get('thumbnail');
        $requestedFile = '/testimage/1/image-thumb__1__unittest/testimage.webp';
        if ($storage->fileExists($requestedFile)) {
            $storage->delete($requestedFile);
        }

        try {
            $response = Service::getStreamedResponseFromImageThumbnail($thumbnail, [
                'type' => 'image',
                'filename' => 'testimage.webp',
            ]);

            $this->assertNotNull($response);
            $this->assertTrue(
                $storage->fileExists($requestedFile),
                'a requested extension that is part of the configured allowed_formats must still be served via the existing copy-on-mismatch behavior'
            );
        } finally {
            if ($storage->fileExists($requestedFile)) {
                $storage->delete($requestedFile);
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
