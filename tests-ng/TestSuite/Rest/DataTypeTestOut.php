<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_DataTypeTestOut extends Test_Base {

    static $seed;

    static $localObject;

    static $restObject;

    public static function setUpBeforeClass() {
        // every single rest test assumes a clean database
        Test_Tool::cleanUp();



        // this will create a couple of objects which can be used for references
        Test_Tool::createEmptyObjects();




        self::$seed = 1;
        self::$localObject = Test_Tool::createFullyFledgedObject("local", true, self::$seed);

            self::$restObject = Test_RestClient::getInstance()->getObjectById(self::$localObject->getId());

    }

    public function setUp() {
        parent::setUp();

    }

    public function testInput() {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertInput(self::$restObject, "input", self::$seed));
    }

    public function testNumber() {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertNumber(self::$restObject, "number", self::$seed));
    }

    public function testTextarea() {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTextarea(self::$restObject, "textarea", self::$seed));
    }

    public function testSlider() {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertSlider(self::$restObject, "slider", self::$seed));
    }

    public function testHref() {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertHref(self::$restObject, "href", self::$seed));
    }

}
