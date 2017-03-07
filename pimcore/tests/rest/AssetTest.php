<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class AssetTest extends RestTestCase
{
    public function setUp()
    {
        $this->markTestSkipped('Not implemented yet');
    }

    public function testCreateAssetFile()
    {
        $this->printTestName();

        $originalContent = file_get_contents(TESTS_PATH . "/resources/assets/images/image5.jpg");

        $this->assertTrue(strlen($originalContent) > 0);

        $this->assertEquals(1, TestHelper::getAssetCount());


        $asset = TestHelper::createImageAsset("", $originalContent, false);
        // object not saved, asset count must still be one
        $this->assertEquals(1, TestHelper::getAssetCount());

        $time = time();

        $result = self::getRestClient()->createAsset($asset);
        $this->assertTrue($result->id > 0, "request not successful");
        $this->assertEquals(2, TestHelper::getAssetCount());

        $id = $result->id;
        $this->assertTrue($id > 1, "id must be greater than 1");

        $assetDirect  = Asset::getById($id);
        $creationDate = $assetDirect->getCreationDate();
        $this->assertTrue($creationDate >= $time, "wrong creation date");
        $properties = $asset->getProperties();
        $this->assertEquals(1, count($properties), "property count does not match");
        $property = $properties[0];
        $this->assertEquals("bla", $property->getData());

        // as the asset key is unique there must be exactly one object with that key
        $list = self::getRestClient()->getAssetList("filename = '" . $asset->getKey() . "'");
        $this->assertEquals(1, count($list));

        // now check if the file exists
        $filename = PIMCORE_ASSET_DIRECTORY . "/" . $asset->getFilename();

        $savedContent = file_get_contents($filename);

        $this->assertEquals($originalContent, $savedContent, "asset was not saved correctly");
    }

    public function testDelete()
    {
        $this->printTestName();

        $originalContent = file_get_contents(TESTS_PATH . "/resources/assets/images/image5.jpg");
        $savedAsset      = TestHelper::createImageAsset("", $originalContent, true);

        $savedAsset = Asset::getById($savedAsset->getId());
        $this->assertNotNull($savedAsset);

        $response = self::getRestClient()->deleteAsset($savedAsset->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        Pimcore::collectGarbage();

        $savedAsset = Asset::getById($savedAsset->getId());
        $this->assertTrue($savedAsset == null, "asset still exists");
    }

    public function testFolder()
    {
        $this->printTestName();

        // create folder but don't save it
        $folder = TestHelper::createImageAsset("myfolder", null, false);
        $folder->setType("folder");

        $fitem = Asset::getById($folder->getId());
        $this->assertNull($fitem);

        $response = self::getRestClient()->createAssetFolder($folder);
        $this->assertTrue($response->id > 0, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, "id not set");

        $folderDirect = Asset::getById($id);
        $this->assertTrue($folderDirect->getType() == "folder");

        $folderRest = self::getRestClient()->getAssetById($id);
        $this->assertTrue(TestHelper::assetsAreEqual($folderRest, $folderDirect, false), "assets are not equal");

        self::getRestClient()->deleteAsset($id);

        Pimcore::collectGarbage();
        $folderDirect = Asset::getById($id);
        $this->assertNull($folderDirect, "folder still exists");
    }
}
