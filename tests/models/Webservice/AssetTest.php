<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class Webservice_AssetTest extends PHPUnit_Framework_TestCase {


    /**
     * @return string
     */
    protected function getListCondition() {

        $conf = Zend_Registry::get("pimcore_config_test");
        return $conf->webservice->asset->condition;

    }

    /**
     * @return Zend_Soap_Client
     */
    protected function getSoapClient() {

        return Test_Tool::getSoapClient();
    }

    public function testAssetFolder() {

        $client = $this->getSoapClient();

        //get an asset list with 3 elements
        $condition = "`type`='folder'";
        $generalCondition = $this->getListCondition();
        if (!empty($generalCondition)) {
            if (!empty($condition)) {
                $condition .= " AND " . $generalCondition;
            } else {
                $condition = $generalCondition;
            }

        }

        $order = "";
        $orderKey = "";
        $offset = 0;
        $limit = 3;
        $groupBy = "";

           
        $wsDocument = $client->getAssetList($condition, $order, $orderKey, $offset, $limit, $groupBy);

        $this->assertTrue(is_array($wsDocument) and $wsDocument[0] instanceof Webservice_Data_Asset_List_Item);

        //take the second element, first will be home
        $id = $wsDocument[0]->id;
        $this->assertTrue(is_numeric($id));

        $wsDocument = $client->getAssetFolderById($id);

        $this->assertTrue($wsDocument instanceof Webservice_Data_Asset_Folder_Out);


        $asset = new Asset_Folder();
        $wsDocument->reverseMap($asset);

        //some checks to see if we got a valid object
        $this->assertTrue($asset->getCreationDate() > 0);
        $this->assertTrue(strlen($asset->getPath()) > 0);


        //copy the object retrieved from ws
        $new = clone $asset;
        $new->id = null;
        $new->setFilename($asset->getFilename() . "_php-unit-test-copy");
        $new->setResource(null);

        //send new asset back via ws
        $apiAsset = Webservice_Data_Mapper::map($new, "Webservice_Data_Asset_Folder_In", "in");
        $id = $client->createAssetFolder($apiAsset);
        $this->assertTrue($id > 0);

        $wsDocument = $client->getAssetFolderById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Asset_Folder_Out);

        $refetchAsset = new Asset_Folder();
        $wsDocument->reverseMap($refetchAsset);

        //compare to original
        $original = Asset::getById($id);
        $original->getProperties();


        $this->assertTrue(Test_Tool::assetsAreEqual($original, $refetchAsset,true));

        //update asset file and set custom settings
        $refetchAsset->setCustomSettings(array("customSettingTest"=>"This is a test"));
        $apiAsset = Webservice_Data_Mapper::map($refetchAsset, "Webservice_Data_Asset_Folder_In", "in");
        $success = $client->updateAssetFolder($apiAsset);
        $this->assertTrue($success);

        $wsDocument = $client->getAssetFolderById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Asset_Folder_Out);

        $refetchAsset = new Asset_Folder();
        $wsDocument->reverseMap($refetchAsset);

        $cs = $refetchAsset->getCustomSettings();
        $this->assertTrue(is_array($cs));
        $this->assertTrue($cs["customSettingTest"] == "This is a test");

        Test_Tool::resetRegistry();
        //compare to original
        $original = Asset_Folder::getById($id);
        $this->assertTrue(Test_Tool::assetsAreEqual($original, $refetchAsset,true));


        //delete our test copy
        $success = $client->deleteAsset($refetchAsset->getId());
        $this->assertTrue($success);


    }

    public function testAssetFile() {

        $client = $this->getSoapClient();

        //get an asset list with 3 elements
        $condition = "`type`='image'";
        $generalCondition = $this->getListCondition();
        if (!empty($generalCondition)) {
            if (!empty($condition)) {
                $condition .= " AND " . $generalCondition;
            } else {
                $condition = $generalCondition;
            }

        }

        $order = "";
        $orderKey = "";
        $offset = 0;
        $limit = 3;
        $groupBy = "";

        $wsDocument = $client->getAssetList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->assertTrue(is_array($wsDocument) and $wsDocument[0] instanceof Webservice_Data_Asset_List_Item);

        //take the first element and fetch asset image
        $id = $wsDocument[0]->id;
        $this->assertTrue(is_numeric($id));

        $wsDocument = $client->getAssetFileById($id);


        $this->assertTrue($wsDocument instanceof Webservice_Data_Asset_File_Out);


        $asset = new Asset_Image();
        $wsDocument->reverseMap($asset);

        //some checks to see if we got a valid object
        $this->assertTrue($asset->getCreationDate() > 0);
        $this->assertTrue(strlen($asset->getPath()) > 0);


        //copy the object retrieved from ws
        $new = clone $asset;
        $new->id = null;
        $new->setFilename("php-unit-test-copy_" . $asset->getFilename());
        $new->setResource(null);

        //send new asset back via ws
        $apiAsset = Webservice_Data_Mapper::map($new, "Webservice_Data_Asset_File_In", "in");
        $id = $client->createAssetFile($apiAsset);
        $this->assertTrue($id > 0);

        $wsDocument = $client->getAssetFileById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Asset_File_Out);

        $refetchAsset = new Asset_Image();
        $wsDocument->reverseMap($refetchAsset);


        //compare to original
        $original = Asset::getById($id);
        $this->assertTrue(Test_Tool::assetsAreEqual($original, $refetchAsset,true));


        //update asset file and set custom settings
        $refetchAsset->setCustomSettings(array("customSettingTest"=>"This is a test"));
        $refetchAsset->setData(file_get_contents(TESTS_PATH . "/resources/assets/images/image5.jpg"));
        $apiAsset = Webservice_Data_Mapper::map($refetchAsset, "Webservice_Data_Asset_File_In", "in");
        $success = $client->updateAssetFile($apiAsset);
        $this->assertTrue($success);

        $wsDocument = $client->getAssetFileById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Asset_File_Out);

        $refetchAsset = new Asset_Image();
        $wsDocument->reverseMap($refetchAsset);


        $cs = $refetchAsset->getCustomSettings();
        $this->assertTrue(is_array($cs));
        $this->assertTrue($cs["customSettingTest"] == "This is a test");

        Test_Tool::resetRegistry();
        //compare to original
        $original = Asset::getById($id);
        $this->assertTrue(Test_Tool::assetsAreEqual($original, $refetchAsset,true));


        //delete our test copy
        $success = $client->deleteAsset($refetchAsset->getId());
        $this->assertTrue($success);


    }


}
