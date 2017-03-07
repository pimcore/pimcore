<?php

namespace Pimcore\Tests\Rest;

use Codeception\Util\Debug;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
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
    protected $seed = 1;

    /**
     * @var Unittest
     */
    protected $testObject;

    /**
     * @var Unittest
     */
    protected $comparisonObject;

    public function _inject(TestData $testData)
    {
        $this->testData = $testData;
    }

    /**
     * @param array|string $fields
     *
     * @return Unittest
     */
    protected function createTestObject($fields = [])
    {
        Debug::debug('CREATING TEST OBJECT: ' . json_encode($fields, true));

        $object = TestHelper::createEmptyObject('local', false, true);
        $this->fillObject($object, $fields);

        $response = $this->restClient->createObjectConcrete($object);

        $this->assertTrue($response->success);

        /** @var Unittest $localObject */
        $localObject = AbstractObject::getById($response->id);

        $this->assertNotNull($localObject);
        $this->assertInstanceOf(Concrete::class, $localObject);

        Debug::debug('TEST OBJECT: ' . $localObject->getId());
        Debug::debug('COMPARISON OBJECT: ' . json_encode($response, true));

        $this->testObject       = $localObject;
        $this->comparisonObject = $object;

        return $this->testObject;
    }

    /**
     * Calls fill* methods on the object as needed in test
     *
     * @param Concrete     $object
     * @param array|string $fields
     */
    protected function fillObject(Concrete $object, $fields = [])
    {
        // allow to pass only a string (e.g. input) -> fillInput($object, "input", $seed)
        if (!is_array($fields)) {
            $fields = [
                [
                    'method' => 'fill' . ucfirst($fields),
                    'field'  => $fields
                ]
            ];
        }

        if (!is_array($fields)) {
            throw new \InvalidArgumentException('Fields needs to be an array');
        }

        foreach ($fields as $field) {
            $method = $field['method'];

            if (!$method) {
                throw new \InvalidArgumentException(sprintf('Need a method to call'));
            }

            if (!method_exists($this->testData, $method)) {
                throw new \InvalidArgumentException(sprintf('Method %s does not exist', $method));
            }

            $methodArguments = [$object, $field['field'], $this->seed];

            $additionalArguments = isset($field['arguments']) ? $field['arguments'] : [];
            foreach ($additionalArguments as $aa) {
                $methodArguments[] = $aa;
            }

            call_user_func_array([$this->testData, $method], $methodArguments);
        }
    }

    /**
     * @group only
     */
    public function testInput()
    {
        $this->createTestObject('input');

        $this->testData->assertInput($this->testObject, "input", $this->seed);
    }

    public function testNumber()
    {
        $this->createTestObject('number');

        $this->testData->assertNumber($this->testObject, "number", $this->seed);
    }

    public function testTextarea()
    {
        $this->createTestObject('textarea');

        $this->testData->assertTextarea($this->testObject, "textarea", $this->seed);
    }

    public function testSlider()
    {
        $this->createTestObject('slider');

        $this->testData->assertSlider($this->testObject, "slider", $this->seed);
    }

    public function testHref()
    {
        $this->markTestIncomplete();

        TestHelper::createEmptyObjects();
        $this->createTestObject('href');

        $this->testData->assertHref($this->testObject, "href", $this->seed);
    }

    public function testMultiHref()
    {
        $this->markTestIncomplete();

        TestHelper::createEmptyObjects();
        $this->createTestObject('multihref');

        $this->testData->assertMultihref($this->testObject, "multihref", $this->seed);
    }

    public function testImage()
    {
        $this->markTestIncomplete();

        $this->createTestObject('image');

        $this->assertNotNull($this->testObject->getImage());
        $this->assertNotNull($this->comparisonObject->getImage());

        $this->testData->assertImage($this->testObject, "image", $this->seed);
    }

    public function testHotspotImage()
    {
        $this->markTestIncomplete();

        $this->createTestObject([
            [
                'method'    => 'fillHotspotImage',
                'field'     => 'hotspotimage'
            ]
        ]);

        $this->assertNotNull($this->testObject->getHotspotImage());
        $this->assertNotNull($this->comparisonObject->getHotspotImage());

        $this->testData->assertHotspotImage($this->testObject, 'hotspotimage', $this->seed);
    }

    public function testLanguage()
    {
        $this->createTestObject([
            [
                'method'    => 'fillLanguage',
                'field'     => 'languagex'
            ]
        ]);

        $this->testData->assertLanguage($this->testObject, "languagex", $this->seed);
    }

    public function testCountry()
    {
        $this->createTestObject('country');

        $this->testData->assertCountry($this->testObject, "country", $this->seed);
    }

    public function testDate()
    {
        $this->createTestObject('date');

        $this->testData->assertDate($this->testObject, "date", $this->seed);
    }

    public function testDateTime()
    {
        $this->createTestObject([
            [
                'method'    => 'fillDate',
                'field'     => 'datetime'
            ]
        ]);

        $this->testData->assertDate($this->testObject, "datetime", $this->seed);
    }

    public function testTime()
    {
        $this->createTestObject('time');

        $this->testData->assertTime($this->testObject, "time", $this->seed);
    }

    public function testSelect()
    {
        $this->createTestObject('select');

        $this->testData->assertSelect($this->testObject, "select", $this->seed);
    }

    public function testMultiSelect()
    {
        $this->createTestObject([
            [
                'method'    => 'fillMultiSelect',
                'field'     => 'multiselect'
            ]
        ]);

        $this->testData->assertMultiSelect($this->testObject, "multiselect", $this->seed);
    }

    public function testUser()
    {
        $this->createTestObject('user');

        $this->testData->assertUser($this->testObject, "user", $this->seed);
    }

    public function testCheckbox()
    {
        $this->createTestObject('checkbox');

        $this->testData->assertCheckbox($this->testObject, "checkbox", $this->seed);
    }

    public function testWysiwyg()
    {
        $this->createTestObject('wysiwyg');

        $this->testData->assertWysiwyg($this->testObject, "wysiwyg", $this->seed);
    }

    public function testPassword()
    {
        $this->createTestObject('password');

        $this->testData->assertPassword($this->testObject, "password", $this->seed);
    }

    public function testCountryMultiSelect()
    {
        $this->createTestObject([
            [
                'method'    => 'fillMultiSelect',
                'field'     => 'countries'
            ]
        ]);

        $this->testData->assertCountryMultiSelect($this->testObject, "countries", $this->seed);
    }

    public function testLanguageMultiSelect()
    {
        $this->createTestObject([
            [
                'method'    => 'fillMultiSelect',
                'field'     => 'languages'
            ]
        ]);

        $this->testData->assertCountryMultiSelect($this->testObject, "languages", $this->seed);
    }

    public function testGeopoint()
    {
        $this->createTestObject([
            [
                'method'    => 'fillGeopoint',
                'field'     => 'point'
            ]
        ]);

        $this->testData->assertGeopoint($this->testObject, "point", $this->seed);
    }

    public function testGeobounds()
    {
        $this->createTestObject([
            [
                'method'    => 'fillGeobounds',
                'field'     => 'bounds'
            ]
        ]);

        $this->testData->assertGeobounds($this->testObject, "bounds", $this->comparisonObject, $this->seed);
    }

    public function testGeopolygon()
    {
        $this->createTestObject([
            [
                'method'    => 'fillGeopolygon',
                'field'     => 'poly'
            ]
        ]);

        $this->testData->assertGeopolygon($this->testObject, "poly", $this->comparisonObject, $this->seed);
    }

    public function testTable()
    {
        $this->createTestObject('table');

        $this->testData->assertTable($this->testObject, "table", $this->comparisonObject, $this->seed);
    }

    public function testLink()
    {
        $this->markTestSkipped();

        $this->createTestObject('link');

        $this->testData->assertLink($this->testObject, "link", $this->seed);
    }

    public function testStructuredTable()
    {
        $this->createTestObject([
            [
                'method'    => 'fillStructuredTable',
                'field'     => 'structuredtable'
            ]
        ]);

        $this->testData->assertStructuredTable($this->testObject, "structuredtable", $this->comparisonObject, $this->seed);
    }

    public function testObjects()
    {
        // // this will create a couple of objects which can be used for references
        TestHelper::createEmptyObjects();

        $this->createTestObject('objects');

        $this->testData->assertObjects($this->testObject, "objects", $this->comparisonObject, $this->seed);
    }

    public function testObjectsWithMetadata()
    {
        TestHelper::createEmptyObjects();

        $this->createTestObject([
            [
                'method'    => 'fillObjectsWithMetadata',
                'field'     => 'objectswithmetadata'
            ]
        ]);

        $this->testData->assertObjectsWithmetadata($this->testObject, "objectswithmetadata", $this->comparisonObject, $this->seed);
    }

    public function testLInput()
    {
        $this->markTestIncomplete('Localized fields seem to have a bug');

        $this->createTestObject([
            [
                'method'    => 'fillInput',
                'field'     => 'linput',
                'arguments' => ['de']
            ],
            [
                'method'    => 'fillInput',
                'field'     => 'linput',
                'arguments' => ['en']
            ]
        ]);

        $this->testData->assertInput($this->testObject, "linput", $this->seed, "en");
        $this->testData->assertInput($this->testObject, "linput", $this->seed, "de");
    }

    public function testLObjects()
    {
        $this->markTestIncomplete('Localized fields seem to have a bug');

        $this->createTestObject([
            [
                'method'    => 'fillObjects',
                'field'     => 'lobjects',
                'arguments' => ['de']
            ],
            [
                'method'    => 'fillObjects',
                'field'     => 'lobjects',
                'arguments' => ['en']
            ]
        ]);

        $this->testData->assertObjects($this->testObject, "lobjects", $this->comparisonObject, $this->seed, "en");
        $this->testData->assertObjects($this->testObject, "lobjects", $this->comparisonObject, $this->seed, "de");
    }

    public function testBricks()
    {
        $this->createTestObject([
            [
                'method' => 'fillBricks',
                'field'  => 'mybricks'
            ]
        ]);

        $this->testData->assertBricks($this->testObject, "mybricks", $this->seed);
    }

    public function testFieldCollection()
    {
        $this->createTestObject([
            [
                'method' => 'fillFieldCollection',
                'field'  => 'myfieldcollection'
            ]
        ]);

        $this->testData->assertFieldCollection($this->testObject, "myfieldcollection", $this->seed);
    }
}
