<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Support\Test\DataType;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Cache;
use Pimcore\DataObject\Consent\Service;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\Note;
use Pimcore\Tests\Support\Helper\DataType\TestDataHelper;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

abstract class AbstractDataTypeTestCase extends TestCase
{
    protected bool $cleanupDbInSetup = true;

    protected TestDataHelper $testDataHelper;

    protected int $seed = 1;

    protected Unittest $testObject;

    protected Unittest $comparisonObject;

    public function _inject(TestDataHelper $testData): void
    {
        $this->testDataHelper = $testData;
    }

    /**
     * Calls fill* methods on the object as needed in test
     *
     */
    protected function fillObject(Concrete $object, array|string $fields = [], ?array &$returnData = []): void
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
            throw new InvalidArgumentException('Fields needs to be an array');
        }

        foreach ($fields as $field) {
            $method = $field['method'];

            if (!$method) {
                throw new InvalidArgumentException(sprintf('Need a method to call'));
            }

            if (!method_exists($this->testDataHelper, $method)) {
                throw new InvalidArgumentException(sprintf('Method %s does not exist', $method));
            }

            $methodArguments = [$object, $field['field'], $this->seed];

            $additionalArguments = $field['arguments'] ?? [];
            foreach ($additionalArguments as $aa) {
                $methodArguments[] = $aa;
            }
            if (isset(func_get_args()[2])) {
                $methodArguments[] = &$returnData;
            }

            call_user_func_array([$this->testDataHelper, $method], $methodArguments);
        }
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testBooleanSelect(): void
    {
        $this->createTestObject('booleanSelect');

        $this->refreshObject();
        $this->testDataHelper->assertBooleanSelect($this->testObject, 'booleanSelect', $this->seed);
    }

    abstract protected function createTestObject(array $fields = [], ?array &$params = []): Unittest;

    abstract public function refreshObject(): void;

    public function testBricks(): void
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

    public function testCalculatedValue(): void
    {
        $this->createTestObject([
            [
                'method' => 'fillCalculatedValue',
                'field' => 'calculatedValue',
            ],
        ]);

        // create a random number and hand it over to the calculator via the runtime and then make sure it will be returned
        $value = uniqid();
        Cache\RuntimeCache::set('modeltest.testCalculatedValue.value', $value);

        $valueFromCalculator = $this->testObject->getCalculatedValue();
        $this->assertEquals($value, $valueFromCalculator, 'calculated value does not match');

        //check if it got written to the query table

        $this->testObject->save();

        $db = Db::get();
        $select = 'SELECT calculatedValue from object_query_' . $this->testObject->getClassId()
                        . ' WHERE oo_id = ' . $this->testObject->getId();
        $row = $db->fetchAssociative($select);

        $this->assertEquals($value, $row['calculatedValue'], 'value should have been written to query table');
    }

    public function testCalculatedValueExpression(): void
    {
        $this->createTestObject([
            [
                'method' => 'fillCalculatedValue',
                'field' => 'calculatedValueExpression',
            ],
        ]);

        $this->testObject->setFirstname('Jane');

        $value = $this->testObject->getCalculatedValueExpression();
        $this->assertEquals('Jane some calc', $value, 'calculated value does not match');

        //check if it got written to the query table

        $this->testObject->save();

        $db = Db::get();
        $select = 'SELECT calculatedValueExpression from object_query_' . $this->testObject->getClassId()
            . ' WHERE oo_id = ' . $this->testObject->getId();
        $row = $db->fetchAssociative($select);

        $this->assertEquals('Jane some calc', $row['calculatedValueExpression'], 'value should have been written to query table');
    }

    public function testCalculatedValueExpressionConstant(): void
    {
        $this->createTestObject([
            [
                'method' => 'fillCalculatedValue',
                'field' => 'calculatedValueExpressionConstant',
            ],
        ]);

        $value = $this->testObject->getCalculatedValueExpressionConstant();
        $this->assertNotEquals(PIMCORE_PROJECT_ROOT, $value, 'calculated returns constant value');
    }

    public function testCheckbox(): void
    {
        $this->createTestObject('checkbox');

        $this->refreshObject();
        $this->testDataHelper->assertCheckbox($this->testObject, 'checkbox', $this->seed);
    }

    public function testConsent(): void
    {
        $this->createTestObject();

        $service = Pimcore::getContainer()->get(Service::class);
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

    public function testCountry(): void
    {
        $this->createTestObject('country');

        $this->refreshObject();
        $this->testDataHelper->assertCountry($this->testObject, 'country', $this->seed);
    }

    public function testCountryMultiSelect(): void
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

    public function testDate(): void
    {
        $this->createTestObject('date');

        $this->refreshObject();
        $this->testDataHelper->assertDate($this->testObject, 'date', $this->seed);
    }

    public function testDateTime(): void
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

    public function testEmail(): void
    {
        $this->createTestObject('email');

        $this->refreshObject();
        $this->testDataHelper->assertEmail($this->testObject, 'email', $this->seed);
    }

    public function testEncrypted(): void
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

    public function testExternalImage(): void
    {
        $this->createTestObject('externalImage');

        $this->refreshObject();
        $this->testDataHelper->assertExternalImage($this->testObject, 'externalImage', $this->seed);
    }

    public function testFieldCollection(): void
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

    public function testFirstname(): void
    {
        $this->createTestObject('firstname');

        $this->refreshObject();
        $this->testDataHelper->assertFirstname($this->testObject, 'firstname', $this->seed);
    }

    public function testGender(): void
    {
        $this->createTestObject('gender');

        $this->refreshObject();
        $this->testDataHelper->assertGender($this->testObject, 'gender', $this->seed);
    }

    public function testGeobounds(): void
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

    public function testGeopoint(): void
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

    public function testGeopolygon(): void
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

    public function testGeopolyline(): void
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

    public function testHotspotImage(): void
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

    public function testHref(): void
    {
        TestHelper::createEmptyObjects();
        $this->createTestObject('href');

        $this->refreshObject();
        $this->testDataHelper->assertHref($this->testObject, 'href', $this->seed);
    }

    public function testImage(): void
    {
        $this->createTestObject('image');

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getImage());

        $this->testDataHelper->assertImage($this->testObject, 'image', $this->seed);
    }

    public function testImageGallery(): void
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

    public function testInput(): void
    {
        $this->createTestObject('input');

        $this->refreshObject();
        $this->testDataHelper->assertInput($this->testObject, 'input', $this->seed);
    }

    public function testInputQuantityValue(): void
    {
        $this->createTestObject('inputQuantityValue');

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getInputQuantityValue());

        $this->testDataHelper->assertInputQuantityValue($this->testObject, 'inputQuantityValue', $this->seed);
    }

    public function testLanguage(): void
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

    public function testLanguageMultiSelect(): void
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

    public function testLastname(): void
    {
        $this->createTestObject('lastname');

        $this->refreshObject();
        $this->testDataHelper->assertLastname($this->testObject, 'lastname', $this->seed);
    }

    public function testLazyLocalizedMultihref(): void
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
        Cache\RuntimeCache::clear();

        $this->testObject = DataObject::getById($this->testObject->getId());

        $this->testDataHelper->assertObjects($this->testObject, 'lmultihrefLazy', $this->seed, 'en');
        $this->testDataHelper->assertObjects($this->testObject, 'lmultihrefLazy', $this->seed, 'de');
    }

    public function testLink(): void
    {
        $this->createTestObject('link');

        $this->refreshObject();
        $this->testDataHelper->assertLink($this->testObject, 'link', $this->seed);
    }

    public function testLocalizedInput(): void
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

    public function testLocalizedObjects(): void
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

    public function testLocalizedInputNumberAsString(): void
    {
        $this->createTestObject();
        $this->testObject->setLinput('0001', 'en');
        $this->testObject->setLinput('0.1000', 'de');
        $this->testObject->save();

        $expectedEN = $this->testObject->getLinput('en');
        $expectedDE = $this->testObject->getLinput('de');
        $this->testDataHelper->assertIsNotEqual($this->testObject, 'linput', $expectedEN, '000001');
        $this->testDataHelper->assertIsNotEqual($this->testObject, 'linput', $expectedDE, '0.100000');
    }

    public function testLocalizedUrlSlug(): void
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

        $this->testObject = Concrete::getById($this->testObject->getId(), ['force' => true]);
        $this->testDataHelper->assertUrlSlug($this->testObject, 'lurlSlug', $this->seed, 'en');
        $this->testDataHelper->assertUrlSlug($this->testObject, 'lurlSlug', $this->seed, 'de');
    }

    public function testMultiHref(): void
    {
        TestHelper::createEmptyObjects();
        $this->createTestObject('multihref');

        $this->refreshObject();
        $this->testDataHelper->assertMultihref($this->testObject, 'multihref', $this->seed);
    }

    public function testMultiSelect(): void
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

    public function testNumeric(): void
    {
        $this->createTestObject('number');

        $this->refreshObject();
        $this->testDataHelper->assertNumber($this->testObject, 'number', $this->seed);
    }

    public function testObjects(): void
    {
        // // this will create a couple of objects which can be used for references
        TestHelper::createEmptyObjects();

        $this->createTestObject('objects');

        $this->refreshObject();
        $this->testDataHelper->assertObjects($this->testObject, 'objects', $this->seed);
    }

    public function testReverseRelation(): void
    {
        // // this will create a couple of objects which can be used for references
        $testObjects = TestHelper::createEmptyObjects();

        $this->createTestObject('objects');

        $this->assertCount(1, $testObjects[0]->getNonowner());
        $this->testDataHelper->assertObjectsEqual($testObjects[0]->getNonowner()[0], $this->testObject);

        $this->refreshObject();
        $this->assertCount(1, $testObjects[0]->getNonowner());
        $this->testDataHelper->assertObjectsEqual($testObjects[0]->getNonowner()[0], $this->testObject);
    }

    public function testObjectsWithMetadata(): void
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

    public function testPassword(): void
    {
        $this->createTestObject('password');

        $this->refreshObject();
        $this->testDataHelper->assertPassword($this->testObject, 'password', $this->seed);
    }

    public function testQuantityValue(): void
    {
        $this->createTestObject('quantityValue', $returnData);

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getQuantityValue());

        $this->testDataHelper->assertQuantityValue($this->testObject, 'quantityValue', $this->seed);
        $this->testDataHelper->checkValidityQuantityValue($this->testObject, 'quantityValue', $this->seed);
    }

    public function testRgbaColor(): void
    {
        $this->createTestObject('rgbaColor');

        $this->refreshObject();
        $this->testDataHelper->assertRgbaColor($this->testObject, 'rgbaColor', $this->seed);
        $this->testDataHelper->checkValidityRgbaColor($this->testObject, 'rgbaColor', $this->seed);
    }

    public function testSelect(): void
    {
        $this->createTestObject('select');

        $this->refreshObject();
        $this->testDataHelper->assertSelect($this->testObject, 'select', $this->seed);
    }

    public function testSlider(): void
    {
        $this->createTestObject('slider');

        $this->refreshObject();
        $this->testDataHelper->assertSlider($this->testObject, 'slider', $this->seed);
    }

    public function testStructuredTable(): void
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

    public function testTable(): void
    {
        $this->createTestObject('table');

        $this->refreshObject();
        $this->testDataHelper->assertTable($this->testObject, 'table', $this->seed);
    }

    public function testTextarea(): void
    {
        $this->createTestObject('textarea');

        $this->refreshObject();
        $this->testDataHelper->assertTextarea($this->testObject, 'textarea', $this->seed);
    }

    public function testTime(): void
    {
        $this->createTestObject('time');

        $this->refreshObject();
        $this->testDataHelper->assertTime($this->testObject, 'time', $this->seed);
    }

    public function testUrlSlug(): void
    {
        $this->createTestObject('urlSlug');

        $this->refreshObject();
        $this->testDataHelper->assertUrlSlug($this->testObject, 'urlSlug', $this->seed);

        // test invalid slug

        $validSlug = new UrlSlug('/xyz/abc');
        $this->testObject->setUrlSlug([$validSlug]);
        $this->testObject->save();

        // test lookup
        /** @var UrlSlug $slug */
        $slug = UrlSlug::resolveSlug('/xyz/abc');
        $this->assertTrue($slug instanceof UrlSlug, 'expected a slug');
        $action = $slug->getAction();
        $this->assertEquals('MyController::myAction', $action, 'wrong controller/action');

        // check uniqueness
        $ex = null;
        $duplicateSlug = new UrlSlug('/xyz/abc');
        $this->testObject->setUrlSlug2([$duplicateSlug]);
        $ex = null;

        try {
            $this->testObject->save();
        } catch (Exception $e) {
            $ex = $e;
        }
        $this->assertNotNull($ex, 'duplicate slug, expected an exception');
    }

    public function testUser(): void
    {
        $this->createTestObject('user');

        $this->refreshObject();
        $this->testDataHelper->assertUser($this->testObject, 'user', $this->seed);
    }

    public function testVideo(): void
    {
        $returnData = [];

        $this->createTestObject('video', $returnData);

        $this->refreshObject();
        $this->assertNotNull($this->testObject->getVideo());

        $this->testDataHelper->assertVideo($this->testObject, 'video', $returnData, $this->seed);
    }

    public function testWysiwyg(): void
    {
        $this->createTestObject('wysiwyg');

        $this->refreshObject();
        $this->testDataHelper->assertWysiwyg($this->testObject, 'wysiwyg', $this->seed);
    }
}
