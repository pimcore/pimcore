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
use Pimcore\Config;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetThumbnailCacheTest extends TestCase
{
    protected Asset $testAsset;

    protected string $thumbnailName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
        $this->assertInstanceOf(Asset\Image::class, $this->testAsset);

        $thumbnailConfig = TestHelper::createThumbnailConfigurationScaleByWidth();
        $this->thumbnailName = $thumbnailConfig->getName();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        TestHelper::clearThumbnailConfigurations();
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testThumbnailCache(): void
    {
        $asset = $this->testAsset;
        $thumbnailName = $this->thumbnailName;

        /** @var Asset\Image $asset * */
        $thumbConfig = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);

        $thumbnailStorage = Storage::get('thumbnail');
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));

        //check if thumbnail exists after getting path reference deferred
        $pathReference = $thumbConfig->getPathReference(true);
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));

        //create thumbnail
        $thumbConfig->getPath(['deferredAllowed' => false]);

        //recheck if thumbnail exists
        $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));

        //update asset
        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image2.jpg')));
        $asset->save();

        //check if cache is cleared
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));

        //fetch config again as the asset checksum changed
        $thumbConfig = $asset->getThumbnail($thumbnailName);
        $pathReference = $thumbConfig->getPathReference(true);

        //load asset via public service controller
        $controller = new PublicServicesController();
        $subRequest = new Request(attributes: [
            'assetId' => $asset->getId(),
            'thumbnailName' => $thumbnailName,
            'filename' => $thumbConfig->getFilename(),
            'type' => 'image',
            'prefix' => '',
        ]);
        $response = $controller->thumbnailAction($subRequest);
        $response->sendContent(); // calls getStream() in order to generate the thumbnail file

        //check if cache is filled
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));

        //delete just file on file system
        //check if cache cleared - expected to not be cleared
        $thumbnailStorage->delete($pathReference['storagePath']);
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));

        //check via controller
        //check if thumbnail is regenerated and cache is filled
        $subRequest = new Request(attributes: [
            'assetId' => $asset->getId(),
            'thumbnailName' => $thumbnailName,
            'filename' => $thumbConfig->getFilename(),
            'type' => 'image',
            'prefix' => '',
        ]);
        $response = $controller->thumbnailAction($subRequest);
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));

        //delete again from file system
        $thumbnailStorage->delete($pathReference['storagePath']);
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));
    }

    public function testThumbnailCacheControlHeaders(): void
    {
        $customLifetime = 3600;

        $assetsConfig = Config::getSystemConfiguration('assets');
        $originalLifetime = $assetsConfig['thumbnails']['cache_lifetime'];
        $assetsConfig['thumbnails']['cache_lifetime'] = $customLifetime;
        Config::setSystemConfiguration($assetsConfig, 'assets');

        try {
            $asset = $this->testAsset;
            $thumbnailName = $this->thumbnailName;

            /** @var Asset\Image $asset */
            $thumbConfig = $asset->getThumbnail($thumbnailName);
            $asset->clearThumbnails(true);

            $thumbnailStorage = Storage::get('thumbnail');

            // Generate the thumbnail so it exists in storage
            $thumbConfig->getPath(['deferredAllowed' => false]);
            $pathReference = $thumbConfig->getPathReference(true);
            $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));

            $controller = new PublicServicesController();

            // Branch 1: existing file – the storage path is used as the request URI so
            // getStreamedResponseForThumbnail() serves it directly without re-generating.
            $storagePath = $pathReference['storagePath'];
            $existingFileRequest = Request::create($storagePath);
            $existingFileRequest->attributes->set('assetId', $asset->getId());
            $existingFileRequest->attributes->set('thumbnailName', $thumbnailName);
            $existingFileRequest->attributes->set('filename', $thumbConfig->getFilename());
            $existingFileRequest->attributes->set('type', 'image');
            $existingFileRequest->attributes->set('prefix', '');

            $timeBefore = time();
            $response = $controller->thumbnailAction($existingFileRequest);
            $timeAfter = time();

            $this->assertInstanceOf(StreamedResponse::class, $response);
            $this->assertTrue($response->headers->hasCacheControlDirective('public'));
            $this->assertSame($customLifetime, (int) $response->headers->getCacheControlDirective('max-age'));
            $expiresTimestamp = strtotime($response->headers->get('Expires'));
            $this->assertGreaterThanOrEqual($timeBefore + $customLifetime, $expiresTimestamp);
            $this->assertLessThanOrEqual($timeAfter + $customLifetime, $expiresTimestamp);

            // Branch 2: on-demand generation – delete the file so it must be re-generated.
            $thumbnailStorage->delete($storagePath);
            $this->assertFalse($thumbnailStorage->fileExists($storagePath));

            $onDemandRequest = new Request(attributes: [
                'assetId' => $asset->getId(),
                'thumbnailName' => $thumbnailName,
                'filename' => $thumbConfig->getFilename(),
                'type' => 'image',
                'prefix' => '',
            ]);

            $timeBefore2 = time();
            $response2 = $controller->thumbnailAction($onDemandRequest);
            $timeAfter2 = time();

            $this->assertInstanceOf(StreamedResponse::class, $response2);
            $this->assertTrue($response2->headers->hasCacheControlDirective('public'));
            $this->assertSame($customLifetime, (int) $response2->headers->getCacheControlDirective('max-age'));
            $expiresTimestamp2 = strtotime($response2->headers->get('Expires'));
            $this->assertGreaterThanOrEqual($timeBefore2 + $customLifetime, $expiresTimestamp2);
            $this->assertLessThanOrEqual($timeAfter2 + $customLifetime, $expiresTimestamp2);
        } finally {
            $assetsConfig['thumbnails']['cache_lifetime'] = $originalLifetime;
            Config::setSystemConfiguration($assetsConfig, 'assets');
        }
    }
}
