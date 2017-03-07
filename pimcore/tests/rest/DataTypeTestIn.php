<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Tests\RestTester;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class DataTypeTestIn extends RestTestCase
{
    public static $seed;

    public static $localObject;

    public static $restObject;

    public static function setUpBeforeClass()
    {
        print("### setUpBeforeClass " . __FILE__);
        // every single rest test assumes a clean database
        TestHelper::cleanUp();

        // this will create a couple of objects which can be used for references
        TestHelper::createEmptyObjects();

        self::$seed = 1;

        $tmpObject = TestHelper::createFullyFledgedObject("local", false, self::$seed);

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
        $this->markTestSkipped('Not implemented yet');
    }

    public function testInput()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertInput(self::$localObject, "input", self::$seed));
    }

    public function testNumber()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertNumber(self::$localObject, "number", self::$seed));
    }

    public function testTextarea()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertTextarea(self::$localObject, "textarea", self::$seed));
    }

    public function testSlider()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertSlider(self::$localObject, "slider", self::$seed));
    }

    public function testHref()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertHref(self::$localObject, "href", self::$seed));
    }

    public function testMultiHref()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertMultihref(self::$localObject, "multihref", self::$seed));
    }

    public function testImage()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertImage(self::$localObject, "image", self::$seed));
    }

    public function testLanguage()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertLanguage(self::$localObject, "languagex", self::$seed));
    }

    public function testCountry()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCountry(self::$localObject, "country", self::$seed));
    }

    public function testDate()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertDate(self::$localObject, "date", self::$seed));
    }

    public function testDateTime()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertDate(self::$localObject, "datetime", self::$seed));
    }


    public function testSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertSelect(self::$localObject, "select", self::$seed));
    }

    public function testMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertMultiSelect(self::$localObject, "multiselect", self::$seed));
    }

    public function testUser()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertUser(self::$localObject, "user", self::$seed));
    }

    public function testCheckbox()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCheckbox(self::$localObject, "checkbox", self::$seed));
    }

    public function testTime()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertTime(self::$localObject, "time", self::$seed));
    }

    public function testWysiwyg()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertWysiwyg(self::$localObject, "wysiwyg", self::$seed));
    }

    public function testCountryMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCountryMultiSelect(self::$localObject, "countries", self::$seed));
    }

    public function testLanguageMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCountryMultiSelect(self::$localObject, "languages", self::$seed));
    }

    public function testGeopoint()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertGeopoint(self::$localObject, "point", self::$seed));
    }

    public function testGeobounds()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertGeobounds(self::$localObject, "bounds", self::$restObject, self::$seed));
    }

    public function testGeopolygon()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertGeopolygon(self::$localObject, "poly", self::$restObject, self::$seed));
    }

    public function testTable()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertTable(self::$localObject, "table", self::$restObject, self::$seed));
    }

    public function testLink()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertLink(self::$localObject, "link", self::$restObject, self::$seed));
    }

    public function testStructuredTable()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertStructuredTable(self::$localObject, "structuredtable", self::$restObject, self::$seed));
    }

    public function testObjects()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertObjects(self::$localObject, "objects", self::$restObject, self::$seed));
    }

    public function testObjectsWithMetadata()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertObjectsWithmetadata(self::$localObject, "objectswithmetadata", self::$restObject, self::$seed));
    }

    public function testLInput()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertInput(self::$localObject, "linput", self::$seed, "en"));
        $this->assertTrue(TestData::assertInput(self::$localObject, "linput", self::$seed, "de"));
    }

    public function testLObjects()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertObjects(self::$localObject, "lobjects", self::$restObject, self::$seed, "en"));
        $this->assertTrue(TestData::assertObjects(self::$localObject, "lobjects", self::$restObject, self::$seed, "de"));
    }

    public function testKeyValue()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertKeyValue(self::$localObject, "keyvaluepairs", self::$seed));
    }

    public function testBricks()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertBricks(self::$localObject, "mybricks", self::$seed));
    }

    public function testFieldCollection()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertFieldCollection(self::$localObject, "myfieldcollection", self::$seed));
    }
}
