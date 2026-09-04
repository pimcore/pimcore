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

use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;

/**
 * The web-optimized ("Auto") thumbnail format is stored as "SOURCE" by the classic admin UI
 * and as "auto" by Pimcore Studio. Both spellings must produce the same thumbnails.
 */
final class AssetThumbnailAutoFormatTest extends TestCase
{
    private Image $testAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
    }

    protected function tearDown(): void
    {
        $this->testAsset->clearThumbnails(true);
        TestHelper::clearThumbnailConfigurations();

        parent::tearDown();
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testAutoFormatGeneratesWebOptimizedThumbnail(): void
    {
        $thumbnail = $this->testAsset->getThumbnail($this->createConfig('auto'), false);

        $path = $thumbnail->getPath(['deferredAllowed' => false]);

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertTrue(Storage::get('thumbnail')->fileExists($thumbnail->getPathReference()['storagePath']));
    }

    public function testAutoFormatIsCaseInsensitive(): void
    {
        $thumbnail = $this->testAsset->getThumbnail($this->createConfig('AUTO'), false);

        $this->assertStringEndsWith('.jpg', $thumbnail->getPath(['deferredAllowed' => false]));
    }

    public function testAutoFormatRendersTheSamePictureSourcesAsSourceFormat(): void
    {
        $sourceHtml = $this->testAsset->getThumbnail($this->createConfig('SOURCE'), false)->getHtml();
        $autoHtml = $this->testAsset->getThumbnail($this->createConfig('auto'), false)->getHtml();

        $this->assertGreaterThan(0, substr_count($sourceHtml, '<source'));
        $this->assertSame(substr_count($sourceHtml, '<source'), substr_count($autoHtml, '<source'));
    }

    private function createConfig(string $format): Config
    {
        $config = new Config();
        $config->setName('assettest_format_' . strtolower($format));
        $config->setFormat($format);
        $config->addItem('scaleByWidth', ['width' => 100], 'default');

        return $config;
    }
}
