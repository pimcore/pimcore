<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Tests\RestTester;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class DataTypeTestOut extends RestTestCase
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
        self::$localObject = TestHelper::createFullyFledgedObject("local", true, self::$seed);
        self::$restObject = self::getRestClient()->getObjectById(self::$localObject->getId());
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function testInput()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertInput(self::$restObject, "input", self::$seed));
    }

    public function testNumber()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertNumber(self::$restObject, "number", self::$seed));
    }

    public function testTextarea()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertTextarea(self::$restObject, "textarea", self::$seed));
    }

    public function testSlider()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertSlider(self::$restObject, "slider", self::$seed));
    }

    public function testHref()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertHref(self::$restObject, "href", self::$seed));
    }

    public function testMultiHref()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertMultihref(self::$restObject, "multihref", self::$seed));
    }

    public function testImage()
    {
        $this->printTestName();
        $this->assertNotNull(self::$localObject->getImage());
        $this->assertNotNull(self::$restObject->getImage());
        $this->assertTrue(TestData::assertImage(self::$restObject, "image", self::$seed));
    }

    public function testHotspotImage()
    {
        $this->printTestName();
        $this->assertNotNull(self::$localObject->getHotspotImage());
        $this->assertNotNull(self::$restObject->getHotspotImage());
        $this->assertTrue(TestData::assertHotspotImage(self::$restObject, "hotspotimage", self::$seed));
    }

    public function testLanguage()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertLanguage(self::$restObject, "languagex", self::$seed));
    }

    public function testCountry()
    {
        $this->printTestName();
        $this->assertNotNull(self::$restObject->getCountry());
        $this->assertTrue(TestData::assertCountry(self::$restObject, "country", self::$seed));
    }

    public function testDate()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertDate(self::$restObject, "date", self::$seed));
    }

    public function testDateTime()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertDate(self::$restObject, "datetime", self::$seed));
    }


    public function testSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertSelect(self::$restObject, "select", self::$seed));
    }

    public function testMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertMultiSelect(self::$restObject, "multiselect", self::$seed));
    }

    public function testUser()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertUser(self::$restObject, "user", self::$seed));
    }

    public function testCheckbox()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCheckbox(self::$restObject, "checkbox", self::$seed));
    }

    public function testTime()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertTime(self::$restObject, "time", self::$seed));
    }

    public function testWysiwyg()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertWysiwyg(self::$restObject, "wysiwyg", self::$seed));
    }

    public function testPassword()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertPassword(self::$restObject, "password", self::$seed));
    }

    public function testCountryMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCountryMultiSelect(self::$restObject, "countries", self::$seed));
    }

    public function testLanguageMultiSelect()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertCountryMultiSelect(self::$restObject, "languages", self::$seed));
    }

    public function testGeopoint()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertGeopoint(self::$restObject, "point", self::$seed));
    }

    public function testGeobounds()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertGeobounds(self::$restObject, "bounds", self::$localObject, self::$seed));
    }

    public function testGeopolygon()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertGeopolygon(self::$restObject, "poly", self::$localObject, self::$seed));
    }

    public function testTable()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertTable(self::$restObject, "table", self::$localObject, self::$seed));
    }

    public function testLink()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertLink(self::$restObject, "link", self::$localObject, self::$seed));
    }

    public function testStructuredTable()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertStructuredTable(self::$restObject, "structuredtable", self::$localObject, self::$seed));
    }

    public function testObjects()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertObjects(self::$restObject, "objects", self::$localObject, self::$seed));
    }

    public function testObjectsWithMetadata()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertObjectsWithmetadata(self::$restObject, "objectswithmetadata", self::$localObject, self::$seed));
    }

    public function testLInput()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertInput(self::$restObject, "linput", self::$seed, "en"));
        $this->assertTrue(TestData::assertInput(self::$restObject, "linput", self::$seed, "de"));
    }

    public function testLObjects()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertObjects(self::$restObject, "lobjects", self::$restObject, self::$seed, "en"));
        $this->assertTrue(TestData::assertObjects(self::$restObject, "lobjects", self::$restObject, self::$seed, "de"));
    }

    public function testKeyValue()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertKeyValue(self::$restObject, "keyvaluepairs", self::$seed));
    }

    public function testBricks()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertBricks(self::$restObject, "mybricks", self::$seed));
    }

    public function testFieldCollection()
    {
        $this->printTestName();
        $this->assertTrue(TestData::assertFieldCollection(self::$restObject, "myfieldcollection", self::$seed));
    }
}
