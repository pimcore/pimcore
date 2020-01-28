<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Model\Asset;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class AssetTest extends RestTestCase
{
    public function setUp()
    {
        parent::setUp();

        if ($this->cleanupDbInSetup) {
            // delete all assets before each test
            // dropping the whole directory might fail when there are still
            // locks on existing files, so we just delete every single file
            TestHelper::cleanupDirectory(PIMCORE_ASSET_DIRECTORY);
        }
    }

    public function testCreateAssetFile()
    {
        $originalContent = $this->getAssetFileContent();

        $this->assertTrue(strlen($originalContent) > 0);
        $this->assertEquals(1, TestHelper::getAssetCount());

        $asset = TestHelper::createImageAsset('', $originalContent, false);

        // asset not saved, asset count must still be one
        $this->assertEquals(1, TestHelper::getAssetCount());

        $time = time();

        $result = $this->restClient->createAsset($asset);

        $this->assertTrue($result->data->id > 0, 'request not successful');
        $this->assertEquals(2, TestHelper::getAssetCount());

        $id = $result->data->id;
        $this->assertTrue($id > 1, 'id must be greater than 1');

        $assetDirect = Asset::getById($id);
        $creationDate = $assetDirect->getCreationDate();

        $this->assertGreaterThanOrEqual($time, $creationDate, 'wrong creation date');

        $properties = $asset->getProperties();
        $this->assertEquals(1, count($properties), 'property count does not match');

        $property = $properties[0];
        $this->assertEquals('bla', $property->getData());

        // as the asset key is unique there must be exactly one object with that key
        $list = $this->restClient->getAssetList('{"filename" :"' . $asset->getKey() . '"}');
        $this->assertEquals(1, count($list));

        // now check if the file exists
        $filename = PIMCORE_ASSET_DIRECTORY . DIRECTORY_SEPARATOR . $asset->getFilename();

        $savedContent = file_get_contents($filename);

        $this->assertEquals($originalContent, $savedContent, 'asset was not saved correctly');
    }

    public function testDelete()
    {
        $originalContent = $this->getAssetFileContent();
        $savedAsset = TestHelper::createImageAsset('', $originalContent, true);

        $savedAsset = Asset::getById($savedAsset->getId());
        $this->assertNotNull($savedAsset);

        $response = $this->restClient->deleteAsset($savedAsset->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        \Pimcore::collectGarbage();

        $savedAsset = Asset::getById($savedAsset->getId());
        $this->assertTrue($savedAsset === null, 'asset still exists');
    }

    public function testFolder()
    {
        // create folder but don't save it
        $folder = TestHelper::createAssetFolder('myfolder', false);

        $fitem = Asset::getById($folder->getId());
        $this->assertNull($fitem);

        $response = $this->restClient->createAssetFolder($folder);
        $this->assertTrue($response->data->id > 0, "request wasn't successful");

        $id = $response->data->id;
        $this->assertTrue($id > 1, 'id not set');

        $folderDirect = Asset::getById($id);
        $this->assertEquals('folder', $folderDirect->getType());

        $folderRest = $this->restClient->getAssetById($id);
        $this->assertTrue(TestHelper::assetsAreEqual($folderRest, $folderDirect, false), 'assets are not equal');

        $this->restClient->deleteAsset($id);

        \Pimcore::collectGarbage();

        $folderDirect = Asset::getById($id);
        $this->assertNull($folderDirect, 'folder still exists');
    }

    /**
     * @param bool $assert
     *
     * @return string
     */
    protected function getAssetFilePath($assert = true)
    {
        $imageFile = TestHelper::resolveFilePath('assets/images/image5.jpg');

        if ($assert) {
            $this->assertFileExists($imageFile);
            $this->assertFileIsReadable($imageFile);
        }

        return $imageFile;
    }

    /**
     * @param bool $assert
     *
     * @return string
     */
    protected function getAssetFileContent($assert = true)
    {
        $content = file_get_contents(
            $this->getAssetFilePath($assert)
        );

        return $content;
    }
}
