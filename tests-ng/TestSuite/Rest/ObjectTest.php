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
}
