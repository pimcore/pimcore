<?php
/**
 * Created by IntelliJ IDEA.
 * User: josef.aichhorn@elements.at
 * Date: 11.11.2013
 */


class TestSuite_Basics_ObjectTest extends Test_Base
{
    public function setUp()
    {
        Test_Tool::cleanUp();
        parent::setUp();
    }


    /**
     * Verifies that a object with the same parent ID cannot be created.
     */
    public function testParentIdentical()
    {
        $this->printTestName();

        $savedObject = Test_Tool::createEmptyObject();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        try {
            $savedObject->save();
            $this->fail("Expected an exception");
        } catch (Exception $e) {
        }
    }

    /**
     * Parent ID of a new object cannot be 0
     */
    public function testParentIs0()
    {
        $this->printTestName();

        $savedObject = Test_Tool::createEmptyObject("", false);
        $this->assertTrue($savedObject->getId() == 0);

        $savedObject->setParentId(0);
        try {
            $savedObject->save();
            $this->fail("Expected an exception");
        } catch (Exception $e) {
        }
    }
}
