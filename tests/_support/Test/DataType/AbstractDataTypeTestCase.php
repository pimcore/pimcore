<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Test\DataType;

use Pimcore\Cache;
use Pimcore\DataObject\Consent\Service;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\Note;
use Pimcore\Tests\Helper\DataType\TestDataHelper;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Util\TestHelper;

abstract class AbstractDataTypeTestCase extends TestCase
{
    /**
     * @var bool
     */
    protected $cleanupDbInSetup = true;

    /**
     * @var TestDataHelper
     */
    protected $testDataHelper;

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

    /**
     * @param TestDataHelper $testData
     */
    public function _inject(TestDataHelper $testData)
    {
        $this->testDataHelper = $testData;
    }

    /**
     * Calls fill* methods on the object as needed in test
     *
     * @param Concrete     $object
     * @param array|string $fields
     * @param array $returnData
     */
    protected function fillObject(Concrete $object, $fields = [], &$returnData = [])
    {
        // allow to pass only a string (e.g. input) -> fillInput($object, "input", $seed)
        if (!is_array($fields)) {
            $fields = [
                [
                    'method' => 'fill' . ucfirst($fields),
                    'field' => $fields,
                ],
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

            if (!method_exists($this->testDataHelper, $method)) {
                throw new \InvalidArgumentException(sprintf('Method %s does not exist', $method));
            }

            $methodArguments = [$object, $field['field'], $this->seed];

            $additionalArguments = isset($field['arguments']) ? $field['arguments'] : [];
            foreach ($additionalArguments as $aa) {
                $methodArguments[] = $aa;
            }

            $methodArguments[] = &$returnData;

            call_user_func_array([$this->testDataHelper, $method], $methodArguments);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function needsDb()
    {
        return true;
    }

    public function testBooleanSelect()
    {
        $this->createTestObject('booleanSelect');

        $this->refreshObject();
        $this->testDataHelper->assertBooleanSelect($this->testObject, 'booleanSelect', $this->seed);
    }

    /**
     * @param array $fields
     * @param $params
     *
     * @return Unittest
     */
    abstract protected function createTestObject($fields = [], &$params = []);

    abstract public function refreshObject();

    public function testBricks()
    {
        $this->createTestObject([
            [
                'method' => 'fillBricks',
                'field' => 'mybricks',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertBricks($this->testObject, 'mybricks', $this->seed);
    }

    public function testCalculatedValue()
    {
        $this->createTestObject([
            [
                'method' => 'fillCalculatedValue',
                'field' => 'calculatedValue',
            ],
        ]);

        // create a random number and hand it over to the calculator via the runtime and then make sure it will be returned
        $value = uniqid();
        Cache\Runtime::set('modeltest.testCalculatedValue.value', $value);

        $valueFromCalculator = $this->testObject->getCalculatedValue();
        $this->assertEquals($value, $valueFromCalculator, 'calculated value does not match');

        // now call the setter and retry, shouldn't have any effect
        $newValue = uniqid();
        $this->testObject->setCalculatedValue($newValue);

        $valueFromCalculator = $this->testObject->getCalculatedValue();
        $this->assertEquals($value, $valueFromCalculator, 'calculated value does not match');

        //check if it got written to the query table

        $this->testObject->save();

        $db = Db::get();
        $select = 'SELECT calculatedValue from object_query_' . $this->testObject->getClassId()
                        . ' WHERE oo_id = ' . $this->testObject->getId();
        $row = $db->fetchRow($select);

        $this->assertEquals($value, $row['calculatedValue'], 'value should have been written to query table');
    }

    public function testCheckbox()
    {
        $this->createTestObject('checkbox');

        $this->refreshObject();
        $this->testDataHelper->assertCheckbox($this->testObject, 'checkbox', $this->seed);
    }

    public function testConsent()
    {
        $this->createTestObject();

        $service = \Pimcore::getContainer()->get(Service::class);
        $service->giveConsent($this->testObject, 'consent', 'some consent content');

        $this->refreshObject();

        /** @var Consent $consent */
        $consent = $this->testObject->getConsent();

        $this->assertNotNull($consent, 'Consent must not be null');
        $this->assertTrue($consent->getConsent(), 'Consent given but still false');

        /** @var Note $consentNote */
        $consentNote = $consent->getNote();
        $this->assertEquals($consentNote->getDescription(), 'some consent content');
        $expectedValue = new Consent(true);
        $this->testDataHelper->assertIsEqual($this->testObject, 'consent', $expectedValue, $consent);

        // now revoke the consent
        $service->revokeConsent($this->testObject, 'consent');

        $this->refreshObject();

        $consent = $this->testObject->getConsent();
        $this->assertNotNull($consent, 'Consent must not be null');
        $this->assertFalse($consent->getConsent(), 'Consent given but still false');
    }

    public function testCountry()
    {
        $this->createTestObject('country');

        $this->refreshObject();
        $this->testDataHelper->assertCountry($this->testObject, 'country', $this->seed);
    }

    public function testCountryMultiSelect()
    {
        $this->createTestObject([
            [
                'method' => 'fillMultiSelect',
                'field' => 'countries',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertCountryMultiSelect($this->testObject, 'countries', $this->seed);
    }

    public function testDate()
    {
        $this->createTestObject('date');

        $this->refreshObject();
        $this->testDataHelper->assertDate($this->testObject, 'date', $this->seed);
    }

    public function testDateTime()
    {
        $this->createTestObject([
            [
                'method' => 'fillDate',
                'field' => 'datetime',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertDate($this->testObject, 'datetime', $this->seed);
    }

    public function testEmail()
    {
        $this->createTestObject('email');

        $this->refreshObject();
        $this->testDataHelper->assertEmail($this->testObject, 'email', $this->seed);
    }

    public function testEncrypted()
    {
        $this->createTestObject('encryptedField');

        $this->refreshObject();

        $this->testDataHelper->assertEncrypted($this->testObject, 'encryptedField', $this->seed);

        $db = Db::get();
        $result = $db->fetchOne('select encryptedField from object_store_' . $this->testObject->getClassId() . ' where oo_id=' .  $this->testObject->getId());
        $this->assertNotNull($result);

        $this->assertNotTrue($result === 'content');
        $this->assertFalse(strpos($result, 'content'));
    }

    public function testExternalImage()
    {
        $this->createTestObject('externalImage');

        $this->refreshObject();
        $this->testDataHelper->assertExternalImage($this->testObject, 'externalImage', $this->seed);
    }

    public function testFieldCollection()
    {
        $this->createTestObject([
            [
                'method' => 'fillFieldCollection',
                'field' => 'myfieldcollection',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertFieldCollection($this->testObject, 'myfieldcollection', $this->seed);
    }

    public function testFirstname()
    {
        $this->createTestObject('firstname');

        $this->refreshObject();
        $this->testDataHelper->assertFirstname($this->testObject, 'firstname', $this->seed);
    }

    public function testGender()
    {
        $this->createTestObject('gender');

        $this->refreshObject();
        $this->testDataHelper->assertGender($this->testObject, 'gender', $this->seed);
    }

    public function testGeobounds()
    {
        $this->createTestObject([
            [
                'method' => 'fillGeobounds',
                'field' => 'bounds',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertGeobounds($this->testObject, 'bounds', $this->seed);
        $this->testDataHelper->checkValidityGeobounds($this->testObject, 'point', $this->seed);
    }

    public function testGeopoint()
    {
        $this->createTestObject([
            [
                'method' => 'fillGeoCoordinates',
                'field' => 'point',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertGeoCoordinates($this->testObject, 'point', $this->seed);
        $this->testDataHelper->checkValidityGeoCoordinates($this->testObject, 'point', $this->seed);
    }

    public function testGeopolygon()
    {
        $this->createTestObject([
            [
                'method' => 'fillGeopolygon',
                'field' => 'polygon',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertGeopolygon($this->testObject, 'polygon', $this->seed);
        $this->testDataHelper->checkValidityGeopolygon($this->testObject, 'polygon', $this->seed);
    }

    public function testGeopolyline()
    {
        $this->createTestObject([
            [
                'method' => 'fillGeopolyline',
                'field' => 'polyline',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertGeopolygon($this->testObject, 'polyline', $this->seed);
        $this->testDataHelper->checkValidityGeopolyline($this->testObject, 'polyline', $this->seed);
    }

    public function testHotspotImage()
    {
        $this->createTestObject([
            [
                'method' => 'fillHotspotImage',
                'field' => 'hotspotimage',
            ],
        ]);

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getHotspotImage());

        $this->testDataHelper->assertHotspotImage($this->testObject, 'hotspotimage', $this->seed);
    }

    public function testHref()
    {
        TestHelper::createEmptyObjects();
        $this->createTestObject('href');

        $this->refreshObject();
        $this->testDataHelper->assertHref($this->testObject, 'href', $this->seed);
    }

    public function testImage()
    {
        $this->createTestObject('image');

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getImage());

        $this->testDataHelper->assertImage($this->testObject, 'image', $this->seed);
    }

    public function testImageGallery()
    {
        $this->createTestObject([
            [
                'method' => 'fillImageGallery',
                'field' => 'imageGallery',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertImageGallery($this->testObject, 'imageGallery', $this->seed);
    }

    public function testIndexFieldSelectionField()
    {
        $this->createTestObject('indexFieldSelectionField');

        $this->refreshObject();
        $this->testDataHelper->assertIndexFieldSelectionField($this->testObject, 'indexFieldSelectionField', $this->seed);
    }

    public function testIndexFieldSelection()
    {
        $this->createTestObject('indexFieldSelection');

        $this->refreshObject();
        $this->testDataHelper->assertIndexFieldSelection($this->testObject, 'indexFieldSelection', $this->seed);
    }

    public function testIndexFieldSelectionCombo()
    {
        $this->createTestObject('indexFieldSelectionCombo');

        $this->refreshObject();
        $this->testDataHelper->assertIndexFieldSelectionCombo($this->testObject, 'indexFieldSelectionCombo', $this->seed);
    }

    public function testInput()
    {
        $this->createTestObject('input');

        $this->refreshObject();
        $this->testDataHelper->assertInput($this->testObject, 'input', $this->seed);
    }

    public function testInputQuantityValue()
    {
        $this->createTestObject('inputQuantityValue');

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getInputQuantityValue());

        $this->testDataHelper->assertInputQuantityValue($this->testObject, 'inputQuantityValue', $this->seed);
    }

    public function testLanguage()
    {
        $this->createTestObject([
            [
                'method' => 'fillLanguage',
                'field' => 'languagex',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertLanguage($this->testObject, 'languagex', $this->seed);
    }

    public function testLanguageMultiSelect()
    {
        $this->createTestObject([
            [
                'method' => 'fillMultiSelect',
                'field' => 'languages',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertCountryMultiSelect($this->testObject, 'languages', $this->seed);
    }

    public function testLastname()
    {
        $this->createTestObject('lastname');

        $this->refreshObject();
        $this->testDataHelper->assertLastname($this->testObject, 'lastname', $this->seed);
    }

    public function testLazyLocalizedMultihref()
    {
        TestHelper::createEmptyObjects();

        $this->createTestObject([
            [
                'method' => 'fillObjects',
                'field' => 'lmultihrefLazy',
                'arguments' => ['de'],
            ],
            [
                'method' => 'fillObjects',
                'field' => 'lmultihrefLazy',
                'arguments' => ['en'],
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertObjects($this->testObject, 'lmultihrefLazy', $this->seed, 'en');
        $this->testDataHelper->assertObjects($this->testObject, 'lmultihrefLazy', $this->seed, 'de');

        Cache::clearAll();
        Cache\Runtime::clear();

        $this->testObject = DataObject::getById($this->testObject->getId());

        $this->testDataHelper->assertObjects($this->testObject, 'lmultihrefLazy', $this->seed, 'en');
        $this->testDataHelper->assertObjects($this->testObject, 'lmultihrefLazy', $this->seed, 'de');
    }

    public function testLink()
    {
        $this->createTestObject('link');

        $this->refreshObject();
        $this->testDataHelper->assertLink($this->testObject, 'link', $this->seed);
    }

    public function testLocalizedInput()
    {
        $this->createTestObject([
            [
                'method' => 'fillInput',
                'field' => 'linput',
                'arguments' => ['de'],
            ],
            [
                'method' => 'fillInput',
                'field' => 'linput',
                'arguments' => ['en'],
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertInput($this->testObject, 'linput', $this->seed, 'en');
        $this->testDataHelper->assertInput($this->testObject, 'linput', $this->seed, 'de');
    }

    public function testLocalizedObjects()
    {
        TestHelper::createEmptyObjects();

        $this->createTestObject([
            [
                'method' => 'fillObjects',
                'field' => 'lobjects',
                'arguments' => ['de'],
            ],
            [
                'method' => 'fillObjects',
                'field' => 'lobjects',
                'arguments' => ['en'],
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertObjects($this->testObject, 'lobjects', $this->seed, 'en');
        $this->testDataHelper->assertObjects($this->testObject, 'lobjects', $this->seed, 'de');
    }

    public function testLocalizedUrlSlug()
    {
        $this->createTestObject([
            [
                'method' => 'fillUrlSlug',
                'field' => 'lurlSlug',
                'arguments' => ['de'],
            ],
            [
                'method' => 'fillUrlSlug',
                'field' => 'lurlSlug',
                'arguments' => ['en'],
            ],
        ]);

        $this->refreshObject();

        $this->testObject = Concrete::getById($this->testObject->getId(), true);
        $this->testDataHelper->assertUrlSlug($this->testObject, 'lurlSlug', $this->seed, 'en');
        $this->testDataHelper->assertUrlSlug($this->testObject, 'lurlSlug', $this->seed, 'de');
    }

    public function testMultiHref()
    {
        TestHelper::createEmptyObjects();
        $this->createTestObject('multihref');

        $this->refreshObject();
        $this->testDataHelper->assertMultihref($this->testObject, 'multihref', $this->seed);
    }

    public function testMultiSelect()
    {
        $this->createTestObject([
            [
                'method' => 'fillMultiSelect',
                'field' => 'multiselect',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertMultiSelect($this->testObject, 'multiselect', $this->seed);
    }

    public function testNewsletterActive()
    {
        $this->createTestObject('newsletterActive');

        $this->refreshObject();
        $this->testDataHelper->assertNewsletterActive($this->testObject, 'newsletterActive', $this->seed);
    }

    public function testNewsletterConfirmed()
    {
        $this->createTestObject('newsletterConfirmed');

        $this->refreshObject();
        $this->testDataHelper->assertNewsletterConfirmed($this->testObject, 'newsletterConfirmed', $this->seed);
    }

    public function testNumeric()
    {
        $this->createTestObject('number');

        $this->refreshObject();
        $this->testDataHelper->assertNumber($this->testObject, 'number', $this->seed);
    }

    public function testObjects()
    {
        // // this will create a couple of objects which can be used for references
        TestHelper::createEmptyObjects();

        $this->createTestObject('objects');

        $this->refreshObject();
        $this->testDataHelper->assertObjects($this->testObject, 'objects', $this->seed);
    }

    public function testObjectsWithMetadata()
    {
        TestHelper::createEmptyObjects();

        $this->createTestObject([
            [
                'method' => 'fillObjectsWithMetadata',
                'field' => 'objectswithmetadata',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertObjectsWithmetadata($this->testObject, 'objectswithmetadata', $this->seed);
    }

    public function testPassword()
    {
        $this->createTestObject('password');

        $this->refreshObject();
        $this->testDataHelper->assertPassword($this->testObject, 'password', $this->seed);
    }

    public function testQuantityValue()
    {
        $this->createTestObject('quantityValue', $returnData);

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getQuantityValue());

        $this->testDataHelper->assertQuantityValue($this->testObject, 'quantityValue', $this->seed);
        $this->testDataHelper->checkValidityQuantityValue($this->testObject, 'quantityValue', $this->seed);
    }

    public function testRgbaColor()
    {
        $this->createTestObject('rgbaColor');

        $this->refreshObject();
        $this->testDataHelper->assertRgbaColor($this->testObject, 'rgbaColor', $this->seed);
        $this->testDataHelper->checkValidityRgbaColor($this->testObject, 'rgbaColor', $this->seed);
    }

    public function testSelect()
    {
        $this->createTestObject('select');

        $this->refreshObject();
        $this->testDataHelper->assertSelect($this->testObject, 'select', $this->seed);
    }

    public function testSlider()
    {
        $this->createTestObject('slider');

        $this->refreshObject();
        $this->testDataHelper->assertSlider($this->testObject, 'slider', $this->seed);
    }

    public function testStructuredTable()
    {
        $this->createTestObject([
            [
                'method' => 'fillStructuredTable',
                'field' => 'structuredtable',
            ],
        ]);

        $this->refreshObject();
        $this->testDataHelper->assertStructuredTable($this->testObject, 'structuredtable', $this->seed);
    }

    public function testTable()
    {
        $this->createTestObject('table');

        $this->refreshObject();
        $this->testDataHelper->assertTable($this->testObject, 'table', $this->seed);
    }

    public function testTextarea()
    {
        $this->createTestObject('textarea');

        $this->refreshObject();
        $this->testDataHelper->assertTextarea($this->testObject, 'textarea', $this->seed);
    }

    public function testTime()
    {
        $this->createTestObject('time');

        $this->refreshObject();
        $this->testDataHelper->assertTime($this->testObject, 'time', $this->seed);
    }

    public function testUrlSlug()
    {
        $this->createTestObject('urlSlug');

        $this->refreshObject();
        $this->testDataHelper->assertUrlSlug($this->testObject, 'urlSlug', $this->seed);

        // test invalid slug

        $validSlug = new UrlSlug('/xyz/abc');
        $this->testObject->setUrlSlug([$validSlug]);
        $this->testObject->save();

        $invalidSlug = new UrlSlug('/xyz      /abc');
        $this->testObject->setUrlSlug([$invalidSlug]);
        $ex = null;
        try {
            $this->testObject->save();
        } catch (\Exception $e) {
            $ex = $e;
        }
        $this->assertNotNull($ex, 'invalid slug, expected an exception');

        // make sure the invalid slug wasn't save and get a fresh copy
        $this->testObject = Concrete::getById($this->testObject->getId(), true);

        // test lookup
        $slug = UrlSlug::resolveSlug('/xyz/abc');
        $this->assertTrue($slug instanceof UrlSlug, 'expected a slug');
        /** @var $slug UrlSlug */
        $action = $slug->getAction();
        $this->assertEquals('MyController::myAction', $action, 'wrong controller/action');

        // check uniqueness
        $ex = null;
        $duplicateSlug = new UrlSlug('/xyz/abc');
        $this->testObject->setUrlSlug2([$duplicateSlug]);
        $ex = null;
        try {
            $this->testObject->save();
        } catch (\Exception $e) {
            $ex = $e;
        }
        $this->assertNotNull($ex, 'duplicate slug, expected an exception');
    }

    public function testUser()
    {
        $this->createTestObject('user');

        $this->refreshObject();
        $this->testDataHelper->assertUser($this->testObject, 'user', $this->seed);
    }

    public function testVideo()
    {
        $returnData = [];
        $this->createTestObject('video', $returnData);

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getVideo());

        $this->testDataHelper->assertVideo($this->testObject, 'video', $this->seed, $returnData);
    }

    public function testWysiwyg()
    {
        $this->createTestObject('wysiwyg');

        $this->refreshObject();
        $this->testDataHelper->assertWysiwyg($this->testObject, 'wysiwyg', $this->seed);
    }
}
