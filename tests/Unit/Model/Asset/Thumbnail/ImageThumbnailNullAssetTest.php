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

namespace Pimcore\Tests\Unit\Model\Asset\Thumbnail;

use Pimcore\Model\Asset\Document\ImageThumbnail as DocumentImageThumbnail;
use Pimcore\Model\Asset\Video\ImageThumbnail as VideoImageThumbnail;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * A thumbnail may be constructed without a backing asset (its `$asset` property is
 * nullable). `getAsset()` must therefore be able to return `null` instead of violating
 * its own return type with a `TypeError`.
 */
class ImageThumbnailNullAssetTest extends TestCase
{
    public function testVideoImageThumbnailWithoutAssetReturnsNull(): void
    {
        $thumbnail = new VideoImageThumbnail(null);

        $this->assertNull($thumbnail->getAsset());
    }

    public function testDocumentImageThumbnailWithoutAssetReturnsNull(): void
    {
        $thumbnail = new DocumentImageThumbnail(null);

        $this->assertNull($thumbnail->getAsset());
    }
}
