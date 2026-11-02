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

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;

/**
 * Regression tests for pimcore/platform-version#246.
 *
 * When the original image could not be extracted from a video - because the video file is gone from
 * storage or because no video adapter is available - generate() used to bail out early, leaving an
 * empty path reference behind. getPath() then returned null, violating its `string` return type and
 * crashing with a TypeError instead of degrading to the "filetype not supported" placeholder.
 *
 * @group model.asset.video-image-thumbnail
 */
final class VideoImageThumbnailTest extends ModelTestCase
{
    private const PLACEHOLDER = '/bundles/pimcoreadmin/img/filetype-not-supported.svg';

    protected function tearDown(): void
    {
        TestHelper::clearThumbnailConfigurations();

        parent::tearDown();
    }

    /**
     * A time offset is passed explicitly so that the test does not depend on getDuration(), which
     * would itself require a readable video file.
     */
    private function thumbnailOfVideoMissingOnStorage(): Asset\Video\ImageThumbnail
    {
        $asset = TestHelper::createVideoAsset();
        $config = TestHelper::createThumbnailConfigurationScaleByWidth();

        Storage::get('asset')->delete($asset->getRealFullPath());

        return new Asset\Video\ImageThumbnail($asset, $config, 1, null, false);
    }

    public function testGetPathReturnsPlaceholderWhenVideoIsMissingOnStorage(): void
    {
        $path = $this->thumbnailOfVideoMissingOnStorage()->getPath(['deferredAllowed' => false, 'frontend' => false]);

        $this->assertSame(self::PLACEHOLDER, $path);
    }

    public function testGetPathReturnsPlaceholderForFrontendWhenVideoIsMissingOnStorage(): void
    {
        $path = $this->thumbnailOfVideoMissingOnStorage()->getPath(['deferredAllowed' => false, 'frontend' => true]);

        $this->assertSame(self::PLACEHOLDER, $path);
    }

    /**
     * The rest of the API built on top of the path reference has to stay usable as well - this is
     * what indexing (e.g. the generic data index) relies on.
     */
    public function testThumbnailStaysUsableWhenVideoIsMissingOnStorage(): void
    {
        $thumbnail = $this->thumbnailOfVideoMissingOnStorage();

        $this->assertFalse($thumbnail->exists());
        $this->assertSame('svg', $thumbnail->getFileExtension());
        $this->assertStringStartsWith('image/svg', $thumbnail->getMimeType());
        $this->assertNull($thumbnail->getFileSize());
    }

    /**
     * An incomplete path reference must never resolve to null either, no matter how it came to be.
     */
    public function testConvertToWebPathFallsBackToPlaceholderForEmptyPathReference(): void
    {
        $thumbnail = new Asset\Video\ImageThumbnail(null);

        foreach ([true, false] as $frontend) {
            $this->assertSame(
                self::PLACEHOLDER,
                TestHelper::callMethod($thumbnail, 'convertToWebPath', [[], $frontend])
            );
        }
    }
}
