<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_ClassTest extends Test_Base {

    public function setUp() {
        // every single rest test assumes a clean database
        Test_Tool::cleanUp();
        parent::setUp();
    }



    public function testGetClass() {
        $this->printTestName();
        $object = Test_Tool::createEmptyObject();
        $classId = $object->getClassId();

        $this->assertEquals("unittest", Object_Class::getById($classId)->getName());
        $restClass1 = Test_RestClient::getInstance()->getClassById($classId);
        $this->assertEquals("unittest", $restClass1->getName());

        $restClass2 = Test_RestClient::getInstance()->getObjectMetaById($object->getId());
        $this->assertEquals("unittest", $restClass2->getName());
    }

}
