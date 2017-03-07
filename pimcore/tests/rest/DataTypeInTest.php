<?php

namespace Pimcore\Tests\Rest;

use Codeception\Util\Debug;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\Localizedfield;
use Pimcore\Model\Object\Unittest;
use Pimcore\Tests\Helper\Datatype\TestData;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class DataTypeInTest extends RestTestCase
{
    /**
     * @var TestData
     */
    protected $testData;

    /**
     * @var int
     */
    protected static $seed = 1;

    /**
     * @var Unittest
     */
    protected static $localObject;

    /**
     * @var Unittest
     */
    protected static $restObject;

    /**
     * @var bool
     */
    protected $cleanupInSetup = false;

    public function _inject(TestData $testData)
    {
        $this->testData = $testData;
    }

    public static function setUpBeforeClass()
    {
        Localizedfield::setStrictMode(true);

        static::$seed        = 1;
        static::$localObject = null;
        static::$restObject  = null;
    }

    public function setUp()
    {
        parent::setUp();

        // this will create a couple of objects which can be used for references
        TestHelper::createEmptyObjects();

        // only create object once per run
        if (null === static::$localObject) {
            $this->createTestObject();
        }
    }

    protected function createTestObject()
    {
        Debug::debug('CREATING TEST OBJECT');

        $restObject = TestHelper::createFullyFledgedObject($this->testData, "local", false, static::$seed);

        $response = $this->restClient->createObjectConcrete($restObject);

        $this->assertTrue($response->success);

        $localObject = AbstractObject::getById($response->id);

        $this->assertNotNull($localObject);
        $this->assertInstanceOf(Concrete::class, $localObject);

        static::$localObject = $localObject;
        static::$restObject  = $restObject;
    }

    public function testInput()
    {
        $this->testData->assertInput(static::$localObject, "input", static::$seed);
    }

    public function testNumber()
    {
        $this->testData->assertNumber(static::$localObject, "number", static::$seed);
    }

    public function testTextarea()
    {
        $this->testData->assertTextarea(static::$localObject, "textarea", static::$seed);
    }

    public function testSlider()
    {
        $this->testData->assertSlider(static::$localObject, "slider", static::$seed);
    }

    public function testHref()
    {
        $this->markTestIncomplete();

        $this->testData->assertHref(static::$localObject, "href", static::$seed);
    }

    public function testMultiHref()
    {
        $this->markTestIncomplete();

        $this->testData->assertMultihref(static::$localObject, "multihref", static::$seed);
    }

    public function testImage()
    {
        $this->markTestIncomplete();

        $this->assertNotNull(static::$localObject->getImage());
        $this->assertNotNull(static::$restObject->getImage());

        $this->testData->assertImage(static::$localObject, "image", static::$seed);
    }

    public function testHotspotImage()
    {
        $this->markTestIncomplete();

        $this->assertNotNull(static::$localObject->getHotspotImage());
        $this->assertNotNull(static::$restObject->getHotspotImage());

        $this->testData->assertHotspotImage(static::$localObject, 'hotspotimage', static::$seed);
    }

    public function testLanguage()
    {
        $this->testData->assertLanguage(static::$localObject, "languagex", static::$seed);
    }

    public function testCountry()
    {
        $this->testData->assertCountry(static::$localObject, "country", static::$seed);
    }

    public function testDate()
    {
        $this->testData->assertDate(static::$localObject, "date", static::$seed);
    }

    public function testDateTime()
    {
        $this->testData->assertDate(static::$localObject, "datetime", static::$seed);
    }

    public function testSelect()
    {
        $this->testData->assertSelect(static::$localObject, "select", static::$seed);
    }

    public function testMultiSelect()
    {
        $this->testData->assertMultiSelect(static::$localObject, "multiselect", static::$seed);
    }

    public function testUser()
    {
        $this->testData->assertUser(static::$localObject, "user", static::$seed);
    }

    public function testCheckbox()
    {
        $this->testData->assertCheckbox(static::$localObject, "checkbox", static::$seed);
    }

    public function testTime()
    {
        $this->testData->assertTime(static::$localObject, "time", static::$seed);
    }

    public function testWysiwyg()
    {
        $this->testData->assertWysiwyg(static::$localObject, "wysiwyg", static::$seed);
    }

    public function testPassword()
    {
        $this->testData->assertPassword(static::$localObject, "password", static::$seed);
    }

    public function testCountryMultiSelect()
    {
        $this->testData->assertCountryMultiSelect(static::$localObject, "countries", static::$seed);
    }

    public function testLanguageMultiSelect()
    {
        $this->testData->assertCountryMultiSelect(static::$localObject, "languages", static::$seed);
    }

    public function testGeopoint()
    {
        $this->testData->assertGeopoint(static::$localObject, "point", static::$seed);
    }

    public function testGeobounds()
    {
        $this->testData->assertGeobounds(static::$localObject, "bounds", static::$restObject, static::$seed);
    }

    public function testGeopolygon()
    {
        $this->testData->assertGeopolygon(static::$localObject, "poly", static::$restObject, static::$seed);
    }

    public function testTable()
    {
        $this->testData->assertTable(static::$localObject, "table", static::$restObject, static::$seed);
    }

    public function testLink()
    {
        $this->markTestSkipped();

        $this->testData->assertLink(static::$localObject, "link", static::$seed);
    }

    public function testStructuredTable()
    {
        $this->testData->assertStructuredTable(static::$localObject, "structuredtable", static::$restObject, static::$seed);
    }

    public function testObjects()
    {
        $this->testData->assertObjects(static::$localObject, "objects", static::$restObject, static::$seed);
    }

    public function testObjectsWithMetadata()
    {
        $this->testData->assertObjectsWithmetadata(static::$localObject, "objectswithmetadata", static::$restObject, static::$seed);
    }

    public function testLInput()
    {
        $this->markTestIncomplete();

        $this->testData->assertInput(static::$localObject, "linput", static::$seed, "en");
        $this->testData->assertInput(static::$localObject, "linput", static::$seed, "de");
    }

    public function testLObjects()
    {
        $this->markTestIncomplete();

        $this->testData->assertObjects(static::$localObject, "lobjects", static::$restObject, static::$seed, "en");
        $this->testData->assertObjects(static::$localObject, "lobjects", static::$restObject, static::$seed, "de");
    }

    public function testBricks()
    {
        $this->testData->assertBricks(static::$localObject, "mybricks", static::$seed);
    }

    public function testFieldCollection()
    {
        $this->testData->assertFieldCollection(static::$localObject, "myfieldcollection", static::$seed);
    }
}
