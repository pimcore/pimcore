<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Bundle\CoreBundle\Controller\PublicServicesController;
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
}
