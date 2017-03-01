<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_DataTypeTestIn extends Test_BaseRest
{
    public static $seed;

    public static $localObject;

    public static $restObject;


    public static function setUpBeforeClass()
    {
        print("### setUpBeforeClass " . __FILE__);
        // every single rest test assumes a clean database
        Test_Tool::cleanUp();

        // this will create a couple of objects which can be used for references
        Test_Tool::createEmptyObjects();

        self::$seed = 1;

        $tmpObject = Test_Tool::createFullyFledgedObject("local", false, self::$seed);

        $response = self::getRestClient()->createObjectConcrete($tmpObject);
        if (!$response->success) {
            var_dump($response);
            throw new Exception("could not create test object");
        }
        self::$localObject = Object_Abstract::getById($response->id);
        self::$restObject = $tmpObject;
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function testInput()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertInput(self::$localObject, "input", self::$seed));
    }

    public function testNumber()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertNumber(self::$localObject, "number", self::$seed));
    }

    public function testTextarea()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTextarea(self::$localObject, "textarea", self::$seed));
    }

    public function testSlider()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertSlider(self::$localObject, "slider", self::$seed));
    }

    public function testHref()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertHref(self::$localObject, "href", self::$seed));
    }

    public function testMultiHref()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertMultihref(self::$localObject, "multihref", self::$seed));
    }

    public function testImage()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertImage(self::$localObject, "image", self::$seed));
    }

    public function testLanguage()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertLanguage(self::$localObject, "languagex", self::$seed));
    }

    public function testCountry()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCountry(self::$localObject, "country", self::$seed));
    }

    public function testDate()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertDate(self::$localObject, "date", self::$seed));
    }

    public function testDateTime()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertDate(self::$localObject, "datetime", self::$seed));
    }


    public function testSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertSelect(self::$localObject, "select", self::$seed));
    }

    public function testMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertMultiSelect(self::$localObject, "multiselect", self::$seed));
    }

    public function testUser()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertUser(self::$localObject, "user", self::$seed));
    }

    public function testCheckbox()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCheckbox(self::$localObject, "checkbox", self::$seed));
    }

    public function testTime()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTime(self::$localObject, "time", self::$seed));
    }

    public function testWysiwyg()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertWysiwyg(self::$localObject, "wysiwyg", self::$seed));
    }

    public function testCountryMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCountryMultiSelect(self::$localObject, "countries", self::$seed));
    }

    public function testLanguageMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCountryMultiSelect(self::$localObject, "languages", self::$seed));
    }

    public function testGeopoint()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertGeopoint(self::$localObject, "point", self::$seed));
    }

    public function testGeobounds()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertGeobounds(self::$localObject, "bounds", self::$restObject, self::$seed));
    }

    public function testGeopolygon()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertGeopolygon(self::$localObject, "poly", self::$restObject, self::$seed));
    }

    public function testTable()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTable(self::$localObject, "table", self::$restObject, self::$seed));
    }

    public function testLink()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertLink(self::$localObject, "link", self::$restObject, self::$seed));
    }

    public function testStructuredTable()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertStructuredTable(self::$localObject, "structuredtable", self::$restObject, self::$seed));
    }

    public function testObjects()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertObjects(self::$localObject, "objects", self::$restObject, self::$seed));
    }

    public function testObjectsWithMetadata()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertObjectsWithmetadata(self::$localObject, "objectswithmetadata", self::$restObject, self::$seed));
    }

    public function testLInput()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertInput(self::$localObject, "linput", self::$seed, "en"));
        $this->assertTrue(Test_Data::assertInput(self::$localObject, "linput", self::$seed, "de"));
    }

    public function testLObjects()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertObjects(self::$localObject, "lobjects", self::$restObject, self::$seed, "en"));
        $this->assertTrue(Test_Data::assertObjects(self::$localObject, "lobjects", self::$restObject, self::$seed, "de"));
    }

    public function testKeyValue()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertKeyValue(self::$localObject, "keyvaluepairs", self::$seed));
    }

    public function testBricks()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertBricks(self::$localObject, "mybricks", self::$seed));
    }

    public function testFieldCollection()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertFieldCollection(self::$localObject, "myfieldcollection", self::$seed));
    }
}
