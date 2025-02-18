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

use Exception;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;

/**
 * Class AssetTest
 *
 * @package Pimcore\Tests\Model\Asset
 *
 * @group model.asset.asset
 */
class AssetTest extends ModelTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        TestHelper::clearThumbnailConfigurations();
    }

    protected Asset $testAsset;

    public function testCRUD(): void
    {
        // create
        $path = TestHelper::resolveFilePath('assets/images/image5.jpg');
        $expectedData = file_get_contents($path);
        $fileSize = strlen($expectedData);
        $this->assertTrue(strlen((string)$fileSize) > 0);

        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image5.jpg');
        $this->assertInstanceOf(Asset\Image::class, $this->testAsset);

        $this->reloadAsset();
        $data = $this->testAsset->getData();
        $this->assertEquals($data, $expectedData);

        // move and rename
        $newParent = Asset\Service::createFolderByPath(uniqid());
        $newPath = $newParent->getFullPath() . '/' . $this->testAsset->getKey() . '_new';

        $this->testAsset->setParentId($newParent->getId());
        $this->testAsset->setKey($this->testAsset->getKey() . '_new');
        $this->testAsset->save();
        $this->reloadAsset();

        $byPath = Asset::getByPath($newPath);
        $this->assertInstanceOf(Asset::class, $byPath);
        $this->assertEquals($this->testAsset->getId(), $byPath->getId());

        $this->reloadAsset();
        $data = $this->testAsset->getData();
        $this->assertEquals($data, $expectedData);

        $this->assertTrue($newParent->hasChildren());

        // change the data

        $path = TestHelper::resolveFilePath('assets/images/image4.jpg');
        $expectedData = file_get_contents($path);
        $fileSize = strlen($expectedData);
        $this->assertTrue(strlen((string)$fileSize) > 0);
        $this->testAsset->setData($expectedData);
        $this->testAsset->save();
        $this->reloadAsset();
        $data = $this->testAsset->getData();
        $this->assertEquals($data, $expectedData);

        // delete
        $this->testAsset->delete();
        $this->assertFalse($newParent->hasChildren());
    }

    /**
     * Parent ID of a new object cannot be 0
     */
    public function testParentIs0(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createImageAsset('', null, false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(0);
        $savedObject->save();
    }

    /**
     * Parent ID of a new object cannot be null
     */
    public function testParentIsNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createImageAsset('', null, false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(null);
        $savedObject->save();
    }

    /**
     * Verifies that an object with the same parent ID cannot be created.
     */
    public function testParentIdentical(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
        $savedObject = TestHelper::createImageAsset();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        $savedObject->save();
    }

    /**
     * Parent ID must resolve to an existing element
     *
     * @group notfound
     */
    public function testParentNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID not found.');
        $savedObject = TestHelper::createImageAsset('', null, false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(999999);
        $savedObject->save();
    }

    /**
     * Verifies that asset PHP API version note is saved
     */
    public function testSavingVersionNotes(): void
    {
        $versionNote = ['versionNote' => 'a new version of this asset'];
        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
        $this->testAsset->save($versionNote);
        $this->assertEquals($this->testAsset->getLatestVersion(null, true)->getNote(), $versionNote['versionNote']);
    }

    public function testThumbnails(): void
    {
        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
        $this->assertInstanceOf(Asset\Image::class, $this->testAsset);

        $this->reloadAsset();
        $this->assertEquals(1024, $this->testAsset->getWidth());
        $this->assertEquals(768, $this->testAsset->getHeight());

        // rotate 90°
        $config = TestHelper::createThumbnailConfigurationRotate();
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(768, $thumbnail->getWidth());
        $this->assertEquals(1024, $thumbnail->getHeight());

        // rotate 45°
        $config = TestHelper::createThumbnailConfigurationRotate(45);
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertTrue($thumbnail->getWidth() > 1024);
        $this->assertTrue($thumbnail->getHeight() > 768);

        // scale by width (shrink)
        $config = TestHelper::createThumbnailConfigurationScaleByWidth();
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(256, $thumbnail->getWidth());
        $this->assertEquals(192, $thumbnail->getHeight());

        // check if the thumbnail file is there
        $pathReference = $thumbnail->getPathReference(false);
        $stream = Storage::get($pathReference['type'])->readStream($pathReference['src']);
        $this->assertTrue(is_resource($stream));
        $thumbnailContent = stream_get_contents($stream);
        $fileSizeThumbnail = strlen($thumbnailContent);

        $path = TestHelper::resolveFilePath('assets/images/image1.jpg');
        $expectedData = file_get_contents($path);
        $fileSize = strlen($expectedData);

        $this->assertTrue($fileSizeThumbnail < $fileSize);

        $thumbnailimageSizeInfo = getimagesize($thumbnail->getLocalFile());
        $this->assertEquals(256, $thumbnailimageSizeInfo[0]);
        $this->assertEquals(192, $thumbnailimageSizeInfo[1]);

        // scale by width (factor 2x) without forceResize
        $config = TestHelper::createThumbnailConfigurationScaleByWidth(2048, false);
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(1024, $thumbnail->getWidth());
        $this->assertEquals(768, $thumbnail->getHeight());

        // scale by width (factor 2x) with forceResize
        $config = TestHelper::createThumbnailConfigurationScaleByWidth(2048, true);
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(2048, $thumbnail->getWidth());
        $this->assertEquals(1536, $thumbnail->getHeight());

        // test custom format thumbnails
        $webpThumbnail = $thumbnail->getAsFormat('webp');
        $jpgThumbnail = $thumbnail->getAsFormat('jpg');
        $pngThumbnail = $thumbnail->getAsFormat('png');

        $this->assertStringEndsWith('.webp', $webpThumbnail->getPath());
        $this->assertStringEndsWith('.jpg', $jpgThumbnail->getPath());
        $this->assertStringEndsWith('.png', $pngThumbnail->getPath());

        // clean the thumbnails
        try {
            $stream = $thumbnail->getStream();
        } catch (Exception $e) {
            $stream = null;
        }

        $this->assertTrue(is_resource($stream));

        $this->testAsset->clearThumbnails(true);

        try {
            $stream1 = $thumbnail->getStream();
        } catch (Exception $e) {
            $stream1 = null;
        }

        $this->assertFalse(is_resource($stream1));
    }

    public function reloadAsset(): void
    {
        $this->testAsset = Asset::getById($this->testAsset->getId(), ['force' => true]);
    }

    /**
     * Verifies that an asset can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification(): void
    {
        $userId = 101;
        $asset = TestHelper::createImageAsset();

        //custom user modification
        $asset->setUserModification($userId);
        $asset->save();
        $this->assertEquals($userId, $asset->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $asset = Asset::getById($asset->getId(), ['force' => true]);
        $asset->save();
        $this->assertEquals(0, $asset->getUserModification(), 'Expected auto assigned user modification id');
    }

    /**
     * Verifies that an asset can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate(): void
    {
        $customDateTime = new \Carbon\Carbon();
        $customDateTime = $customDateTime->subHour();

        $asset = TestHelper::createDocumentAsset();

        //custom modification date
        $asset->setModificationDate($customDateTime->getTimestamp());
        $asset->save();
        $this->assertEquals($customDateTime->getTimestamp(), $asset->getModificationDate(), 'Expected custom modification date');

        //auto generated modification date
        $currentTime = time();
        $asset = Asset::getById($asset->getId(), ['force' => true]);
        $asset->save();
        $this->assertGreaterThanOrEqual($currentTime, $asset->getModificationDate(), 'Expected auto assigned modification date');
    }

    public function testForceReload(): void
    {
        $asset = TestHelper::createImageAsset();

        $this->assertTrue(Asset::getById($asset->getId()) === Asset::getById($asset->getId()));
        $this->assertFalse(Asset::getById($asset->getId()) === Asset::getById($asset->getId(), ['force' => true]));
    }

    public function testAssetFullPath(): void
    {
        $asset = TestHelper::createImageAsset();

        $thumbnailConfig = TestHelper::createThumbnailConfigurationScaleByWidth();

        $this->assertMatchesRegularExpression('@^(https?|data):@', $asset->getFrontendPath());
        $this->assertStringContainsString($asset->getFullPath(), $asset->getFrontendPath());

        $thumbnail = $asset->getThumbnail($thumbnailConfig->getName());

        $thumbnailFullUrl = $thumbnail->getFrontendPath();

        $this->assertMatchesRegularExpression('@^(https?|data):@', $thumbnailFullUrl);
        $this->assertStringContainsString($thumbnail->getPath(), $thumbnailFullUrl);
    }
}
