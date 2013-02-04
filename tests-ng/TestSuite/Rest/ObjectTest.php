<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_ObjectTest extends Test_Base {

    public function setUp() {
        // every single rest test assumes a clean database
        Test_Tool::cleanUp();
    }

    /**
     * creates a class called "unittest" containing all Object_Class_Data Types currently available.
     * @return void
     */
    public function testObjectList() {
        $this->printTestName();
        $list = Test_RestClient::getInstance()->getObjectList();
        $this->assertEquals(1, count($list), "expcted 1 list item");
        $this->assertEquals("folder", $list[0]->getType(), "expected type to be folder");
    }

    public function testObjectGet() {
        $this->printTestName();
        $object = Test_RestClient::getInstance()->getObjectById(1);
        $this->assertEquals("folder", $object->getType(), "expected type to be folder");
        $this->assertEquals(1, $object->getId(), "wrong id");

        $object = Test_RestClient::getInstance()->getObjectById(2);
        $this->assertNull($object, "object not created yet");

        $emptyObject = Test_Tool::createEmptyObject();
        $id = $emptyObject->getId();

        $object = Test_RestClient::getInstance()->getObjectById($id);
        $this->assertNotNull($object, "expected new object");
    }


    public function testCreateObjectConcrete() {
        $this->printTestName();
        $this->assertEquals(1, Test_Tool::getObjectCount());

        $unsavedObject = Test_Tool::createEmptyObject("", false);
        // object not saved, object count must still be one
        $this->assertEquals(1, Test_Tool::getObjectCount());

        $time = time();

        $result = Test_RestClient::getInstance()->createObjectConcrete($unsavedObject);
        $this->assertTrue($result->success, "request not successful");
        $this->assertEquals(2, Test_Tool::getObjectCount());

        $id = $result->id;
        $this->assertTrue($id > 1, "id must be greater than 1");

        $objectDirect = Object_Abstract::getById($id);
        $creationDate = $objectDirect->getCreationDate();
        $this->assertTrue($creationDate >= $time, "wrong creation date");

        // as the object key is unique there must be exactly one object with that key
        $list = Test_RestClient::getInstance()->getObjectList("o_key = '" . $unsavedObject->getKey() . "'");
        $this->assertEquals(1, count($list));
    }

    public function testDelete() {
        $this->printTestName();

        $savedObject = Test_Tool::createEmptyObject();

        $savedObject = Object_Abstract::getById($savedObject->getId());
        $this->assertNotNull($savedObject);

        $response = Test_RestClient::getInstance()->deleteObject($savedObject->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        Pimcore::collectGarbage();

        $savedObject = Object_Abstract::getById($savedObject->getId());
        $this->assertTrue($savedObject == null, "object still exists");
    }

    public function testFolder() {
        $this->printTestName();

        // create folder but don't save it
        $folder = Test_Tool::createEmptyObject("myfolder", false);
        $folder->setType("folder");

        $fitem = Object_Abstract::getById($folder->getId());
        $this->assertNull($fitem);

        $response = Test_RestClient::getInstance()->createObjectFolder($folder);
        $this->assertTrue($response->success, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, "id not set");

        $folderDirect = Object_Abstract::getById($id);
        $this->assertTrue($folderDirect->getType() == "folder");

        $folderRest = Test_RestClient::getInstance()->getObjectById($id);
        $this->assertTrue(Test_Tool::objectsAreEqual($folderRest, $folderDirect, false), "objects are not equal");

        Test_RestClient::getInstance()->deleteObject($id);

        Pimcore::collectGarbage();
        $folderDirect = Object_Abstract::getById($id);
        $this->assertNull($folderDirect, "folder still exists");
    }


}
