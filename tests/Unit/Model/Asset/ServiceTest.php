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
}
