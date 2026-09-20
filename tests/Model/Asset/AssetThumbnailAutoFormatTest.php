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

use Pimcore\Bundle\CoreBundle\Controller\PublicServicesController;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The web-optimized ("Auto") thumbnail format is stored as "SOURCE" by the classic admin UI
 * and as "auto" by Pimcore Studio. Both spellings must produce the same thumbnails.
 */
final class AssetThumbnailAutoFormatTest extends TestCase
{
    private Image $testAsset;

    /**
     * @var string[]
     */
    private array $configNames = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
    }

    protected function tearDown(): void
    {
        $this->testAsset->clearThumbnails(true);
        foreach ($this->configNames as $name) {
            TestHelper::clearThumbnailConfiguration($name);
        }

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

    public function testDeferredAutoFormatVariantRequestDeliversRequestedFormat(): void
    {
        $config = $this->createConfig('auto');
        $autoFormatConfigs = $config->getAutoFormatThumbnailConfigs();
        if (!isset($autoFormatConfigs['webp'])) {
            $this->markTestSkipped('WebP is not supported by the image adapter of this environment');
        }

        $webpThumbnail = $this->testAsset->getThumbnail($autoFormatConfigs['webp']);
        $webpStoragePath = $webpThumbnail->getPathReference(true)['storagePath'];
        $this->assertFalse(Storage::get('thumbnail')->fileExists($webpStoragePath));

        $request = new Request(attributes: [
            'assetId' => $this->testAsset->getId(),
            'thumbnailName' => $config->getName(),
            'filename' => $webpThumbnail->getFilename(),
            'type' => 'image',
            'prefix' => '',
        ]);
        $response = (new PublicServicesController())->thumbnailAction($request);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        $this->assertTrue(Storage::get('thumbnail')->fileExists($webpStoragePath));
    }

    private function createConfig(string $format): Config
    {
        $name = 'assettest_format_' . strtolower($format);
        TestHelper::clearThumbnailConfiguration($name);

        $config = new Config();
        $config->setName($name);
        $config->setFormat($format);
        $config->addItem('scaleByWidth', ['width' => 100], 'default');
        $config->save(true);
        $this->configNames[] = $name;

        return $config;
    }
}
