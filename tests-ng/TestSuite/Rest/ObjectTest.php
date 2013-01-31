<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_ObjectTest extends Test_Base {

    public function setUp() {
        print("cleanup\n");
        // every single rest test assumes a clean database
        Test_Tool::cleanUp();
    }

    /**
     * creates a class called "unittest" containing all Object_Class_Data Types currently available.
     * @return void
     */
    public function testObjectList() {

        print("running testObjectList\n");
        $list = Test_RestClient::getInstance()->getObjectList();
        $this->assertEquals(1, count($list), "expcted 1 list item");
        $this->assertEquals("folder", $list[0]->getType(), "expected type to be folder");
    }

    public function testObjectGet() {
        print("running testObjectGet\n");
        $object = Test_RestClient::getInstance()->getObjectById(1);
        $this->assertEquals("folder", $object->getType(), "expected type to be folder");
        $this->assertEquals(1, $object->getId(), "wrong id");

    }


}
