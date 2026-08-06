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
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * Regression test for the ORIGINAL format: the processor streams the source
     * file to the storage without writing the local temp path, but used to pass
     * that never-written temp path to addThumbnailFileToCache(), so the status
     * cache row was never created and every generation logged an error.
     */
    public function testOriginalFormatThumbnailWritesStatusCache(): void
    {
        /** @var Asset\Image $asset */
        $asset = $this->testAsset;

        $thumbConfig = TestHelper::createThumbnailConfigurationOriginalFormat();
        $thumbnailName = $thumbConfig->getName();

        $thumb = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumb->getFilename()));

        //create thumbnail
        $thumb->getPath(['deferredAllowed' => false]);

        $this->assertTrue(Storage::get('thumbnail')->fileExists($thumb->getPathReference(true)['storagePath']));
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumb->getFilename()));

        //ORIGINAL is a passthrough of the source file, so the cached dimensions
        //and file size must be those of the source
        $cacheRow = Db::get()->fetchAssociative(
            'SELECT filesize, width, height FROM assets_image_thumbnail_cache WHERE cid = ? AND name = ? AND filename = ?',
            [$asset->getId(), $thumbnailName, $thumb->getFilename()]
        );
        $this->assertNotFalse($cacheRow);
        $this->assertSame($asset->getFileSize(), (int) $cacheRow['filesize']);
        $this->assertSame($asset->getWidth(), (int) $cacheRow['width']);
        $this->assertSame($asset->getHeight(), (int) $cacheRow['height']);
    }

    /**
     * Regression test for #18317: thumbnails generated by Pimcore 12.3.0-12.3.11
     * for configs without a crop box live under a hash that included the (false)
     * crop box flag. After the hash fix they must be reused - file AND status
     * cache row moved to the current name - instead of being regenerated.
     */
    public function testCropBoxCompatThumbnailIsMigratedNotRegenerated(): void
    {
        /** @var Asset\Image $asset */
        $asset = $this->testAsset;
        $thumbnailName = $this->thumbnailName;

        $thumb = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);

        $storage = Storage::get('thumbnail');
        $config = $thumb->getConfig();
        $this->assertFalse($config->isUseCropBox());

        // generate the thumbnail under the current (fixed) hash
        $thumb->getPath(['deferredAllowed' => false]);
        $storagePath = $thumb->getPathReference(true)['storagePath'];
        $filename = $thumb->getFilename();
        $this->assertTrue($storage->fileExists($storagePath));
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $filename));

        // simulate the 12.3.0-12.3.11 on-disk state: same file and cache row, but
        // under the hash that always included the crop box flag
        $currentHash = $config->getHash([$asset->getChecksum()]);
        $compatHash = $config->getCropBoxCompatHash([$asset->getChecksum()]);
        $this->assertNotSame($currentHash, $compatHash);

        $compatFilename = str_replace('.' . $currentHash . '.', '.' . $compatHash . '.', $filename);
        $compatStoragePath = str_replace('.' . $currentHash . '.', '.' . $compatHash . '.', $storagePath);

        $storage->move($storagePath, $compatStoragePath);
        $asset->getDao()->moveThumbnailCache($thumbnailName, $filename, $compatFilename);

        // precondition: nothing under the current name, everything under the old one
        $this->assertFalse($storage->fileExists($storagePath));
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $filename));
        $this->assertTrue($storage->fileExists($compatStoragePath));
        $compatModificationDate = $asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $compatFilename);
        $this->assertNotNull($compatModificationDate);

        // request the thumbnail again - the fallback must adopt the old file
        $asset->getThumbnail($thumbnailName)->getPath(['deferredAllowed' => false]);

        // the old file and cache row are gone, the current ones are restored ...
        $this->assertTrue($storage->fileExists($storagePath));
        $this->assertFalse($storage->fileExists($compatStoragePath));
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $compatFilename));

        // ... and the modification date is preserved, proving the file was moved
        // and the cache row migrated rather than the thumbnail regenerated
        $this->assertSame(
            $compatModificationDate,
            $asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $filename),
        );
    }
}
