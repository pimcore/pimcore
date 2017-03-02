<?php
/**
 * Created by IntelliJ IDEA.
 * User: josef.aichhorn@elements.at
 * Date: 11.11.2013
 */


class TestSuite_Datatypes_LocalizedFieldTest extends Test_Base
{
    public function tearDown()
    {
        \Pimcore\Model\Object\Localizedfield::setStrictMode(Pimcore\Model\Object\Localizedfield::STRICT_DISABLED);
    }


    public function testStrictMode()
    {
        $this->printTestName();
        $object = Test_Tool::createEmptyObject();
        try {
            $object->setLinput("Test");
            $object->setLinput("Test", "ko");
        } catch (Exception $e) {
            $this->fail("Did not expect an exception");
        }

        \Pimcore\Model\Object\Localizedfield::setStrictMode(Pimcore\Model\Object\Localizedfield::STRICT_ENABLED);

        try {
            $object->setLinput("Test");
            $this->fail("Expected an exception");
        } catch (Exception $e) {
        }


        try {
            $object->setLinput("Test", "ko");
            $this->fail("Expected an exception");
        } catch (Exception $e) {
        }
    }
}
