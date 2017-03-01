<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_DataTypeTestOut extends Test_BaseRest
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
        self::$localObject = Test_Tool::createFullyFledgedObject("local", true, self::$seed);
        self::$restObject = self::getRestClient()->getObjectById(self::$localObject->getId());
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function testInput()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertInput(self::$restObject, "input", self::$seed));
    }

    public function testNumber()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertNumber(self::$restObject, "number", self::$seed));
    }

    public function testTextarea()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTextarea(self::$restObject, "textarea", self::$seed));
    }

    public function testSlider()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertSlider(self::$restObject, "slider", self::$seed));
    }

    public function testHref()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertHref(self::$restObject, "href", self::$seed));
    }

    public function testMultiHref()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertMultihref(self::$restObject, "multihref", self::$seed));
    }

    public function testImage()
    {
        $this->printTestName();
        $this->assertNotNull(self::$localObject->getImage());
        $this->assertNotNull(self::$restObject->getImage());
        $this->assertTrue(Test_Data::assertImage(self::$restObject, "image", self::$seed));
    }

    public function testHotspotImage()
    {
        $this->printTestName();
        $this->assertNotNull(self::$localObject->getHotspotImage());
        $this->assertNotNull(self::$restObject->getHotspotImage());
        $this->assertTrue(Test_Data::assertHotspotImage(self::$restObject, "hotspotimage", self::$seed));
    }

    public function testLanguage()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertLanguage(self::$restObject, "languagex", self::$seed));
    }

    public function testCountry()
    {
        $this->printTestName();
        $this->assertNotNull(self::$restObject->getCountry());
        $this->assertTrue(Test_Data::assertCountry(self::$restObject, "country", self::$seed));
    }

    public function testDate()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertDate(self::$restObject, "date", self::$seed));
    }

    public function testDateTime()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertDate(self::$restObject, "datetime", self::$seed));
    }


    public function testSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertSelect(self::$restObject, "select", self::$seed));
    }

    public function testMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertMultiSelect(self::$restObject, "multiselect", self::$seed));
    }

    public function testUser()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertUser(self::$restObject, "user", self::$seed));
    }

    public function testCheckbox()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCheckbox(self::$restObject, "checkbox", self::$seed));
    }

    public function testTime()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTime(self::$restObject, "time", self::$seed));
    }

    public function testWysiwyg()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertWysiwyg(self::$restObject, "wysiwyg", self::$seed));
    }

    public function testPassword()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertPassword(self::$restObject, "password", self::$seed));
    }

    public function testCountryMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCountryMultiSelect(self::$restObject, "countries", self::$seed));
    }

    public function testLanguageMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertCountryMultiSelect(self::$restObject, "languages", self::$seed));
    }

    public function testGeopoint()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertGeopoint(self::$restObject, "point", self::$seed));
    }

    public function testGeobounds()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertGeobounds(self::$restObject, "bounds", self::$localObject, self::$seed));
    }

    public function testGeopolygon()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertGeopolygon(self::$restObject, "poly", self::$localObject, self::$seed));
    }

    public function testTable()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertTable(self::$restObject, "table", self::$localObject, self::$seed));
    }

    public function testLink()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertLink(self::$restObject, "link", self::$localObject, self::$seed));
    }

    public function testStructuredTable()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertStructuredTable(self::$restObject, "structuredtable", self::$localObject, self::$seed));
    }

    public function testObjects()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertObjects(self::$restObject, "objects", self::$localObject, self::$seed));
    }

    public function testObjectsWithMetadata()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertObjectsWithmetadata(self::$restObject, "objectswithmetadata", self::$localObject, self::$seed));
    }

    public function testLInput()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertInput(self::$restObject, "linput", self::$seed, "en"));
        $this->assertTrue(Test_Data::assertInput(self::$restObject, "linput", self::$seed, "de"));
    }

    public function testLObjects()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertObjects(self::$restObject, "lobjects", self::$restObject, self::$seed, "en"));
        $this->assertTrue(Test_Data::assertObjects(self::$restObject, "lobjects", self::$restObject, self::$seed, "de"));
    }

    public function testKeyValue()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertKeyValue(self::$restObject, "keyvaluepairs", self::$seed));
    }

    public function testBricks()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertBricks(self::$restObject, "mybricks", self::$seed));
    }

    public function testFieldCollection()
    {
        $this->printTestName();
        $this->assertTrue(Test_Data::assertFieldCollection(self::$restObject, "myfieldcollection", self::$seed));
    }
}
