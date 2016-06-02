<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_ObjectTest extends Test_BaseRest
{
    public function setUp()
    {
        // every single rest test assumes a clean database
        Test_Tool::cleanUp();
        parent::setUp();
    }

    /**
     * creates a class called "unittest" containing all Object_Class_Data Types currently available.
     * @return void
     */
    public function testObjectList()
    {
        $this->printTestName();
        $list = self::getRestClient()->getObjectList();
//        if (count($list) > 1) {
////            var_dump($list);
//            $id1 = $list[0]->getId();
//            $id2 = $list[1]->getId();
////            print($id1 . "\n");
////            print($id2 . "\n");
//            $object1 = Object_Abstract::getById($id1);
//            $object2 = Object_Abstract::getById($id2);
////            print($object1->getKey() . "\n");
////            print($object2->getKey() . "\n");
////            die("check the db!");
//        }
        $this->assertEquals(1, count($list), "expected 1 list item");

        $this->assertEquals("folder", $list[0]->getType(), "expected type to be folder");
    }

    public function testObjectGet()
    {
        $this->printTestName();
        $object = self::getRestClient()->getObjectById(1);
        $this->assertEquals("folder", $object->getType(), "expected type to be folder");
        $this->assertEquals(1, $object->getId(), "wrong id");

        $originalCount = Test_Tool::getObjectCount();

        $emptyObject = Test_Tool::createEmptyObject();
        $id = $emptyObject->getId();
        $this->assertTrue(Test_Tool::getObjectCount() == $originalCount + 1);
        $object = self::getRestClient()->getObjectById($id);
        $this->assertNotNull($object, "expected new object");
    }


    public function testCreateObjectConcrete()
    {
        $this->printTestName();
        $this->assertEquals(1, Test_Tool::getObjectCount());

        $unsavedObject = Test_Tool::createEmptyObject("", false);
        // object not saved, object count must still be one
        $this->assertEquals(1, Test_Tool::getObjectCount());

        $time = time();

        $result = self::getRestClient()->createObjectConcrete($unsavedObject);
//        var_dump($result);
        $this->assertTrue($result->success, "request not successful . " . $result->msg);
        $this->assertEquals(2, Test_Tool::getObjectCount());

        $id = $result->id;
        $this->assertTrue($id > 1, "id must be greater than 1");

        $objectDirect = Object_Abstract::getById($id);
        $creationDate = $objectDirect->getCreationDate();
        $this->assertTrue($creationDate >= $time, "wrong creation date");

        // as the object key is unique there must be exactly one object with that key
        $list = self::getRestClient()->getObjectList("o_key = '" . $unsavedObject->getKey() . "'");
        $this->assertEquals(1, count($list));
    }

    public function testDelete()
    {
        $this->printTestName();

        $savedObject = Test_Tool::createEmptyObject();

        $savedObject = Object_Abstract::getById($savedObject->getId());
        $this->assertNotNull($savedObject);

        $response = self::getRestClient()->deleteObject($savedObject->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        Pimcore::collectGarbage();

        $savedObject = Object_Abstract::getById($savedObject->getId());
        $this->assertTrue($savedObject == null, "object still exists");
    }

    public function testFolder()
    {
        $this->printTestName();

        // create folder but don't save it
        $folder = Test_Tool::createEmptyObject("myfolder", false);
        $folder->setType("folder");

        $fitem = Object_Abstract::getById($folder->getId());
        $this->assertNull($fitem);

        $response = self::getRestClient()->createObjectFolder($folder);
        $this->assertTrue($response->success, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, "id not set");

        $folderDirect = Object_Abstract::getById($id);
        $this->assertTrue($folderDirect->getType() == "folder");

        $folderRest = self::getRestClient()->getObjectById($id);
        $this->assertTrue(Test_Tool::objectsAreEqual($folderRest, $folderDirect, false), "objects are not equal");

        self::getRestClient()->deleteObject($id);

        Pimcore::collectGarbage();
        $folderDirect = Object_Abstract::getById($id);
        $this->assertNull($folderDirect, "folder still exists");
    }
}
