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

namespace Pimcore\Tests\Unit\Models\Asset\Thumbnail;

use Pimcore\Model\Asset\Document\ImageThumbnail as DocumentImageThumbnail;
use Pimcore\Model\Asset\Video\ImageThumbnail as VideoImageThumbnail;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Video and document image thumbnails can be constructed without a backing asset.
 * Their own methods must not go through the non-nullable getAsset() in that case,
 * otherwise rendering such a thumbnail fails with a TypeError.
 */
class ImageThumbnailWithoutAssetTest extends TestCase
{
    /**
     * @return array<string, array{VideoImageThumbnail|DocumentImageThumbnail}>
     */
    public function thumbnailProvider(): array
    {
        return [
            'video' => [new VideoImageThumbnail(null)],
            'document' => [new DocumentImageThumbnail(null)],
        ];
    }

    /**
     * @dataProvider thumbnailProvider
     */
    public function testGetDimensionsWithoutAsset(VideoImageThumbnail|DocumentImageThumbnail $thumbnail): void
    {
        $dimensions = $thumbnail->getDimensions();

        $this->assertNull($dimensions['width']);
        $this->assertNull($dimensions['height']);
    }

    /**
     * @dataProvider thumbnailProvider
     */
    public function testGetFileSizeWithoutAsset(VideoImageThumbnail|DocumentImageThumbnail $thumbnail): void
    {
        $this->assertNull($thumbnail->getFileSize());
    }

    /**
     * @dataProvider thumbnailProvider
     */
    public function testGetPathWithoutAsset(VideoImageThumbnail|DocumentImageThumbnail $thumbnail): void
    {
        $this->assertNotSame('', $thumbnail->getPath());
    }
}
