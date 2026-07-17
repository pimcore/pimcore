<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\DataType;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Pimcore\Model\User;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class NormalizerTest
 *
 * @group model.datatype.normalizer
 */
class NormalizerTest extends ModelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    /**
     * The NormalizerInterface contract this suite enforces: normalize() output must
     * survive a JSON encode/decode boundary (that is what makes the format portable —
     * it is persisted e.g. by Classificationstore\Dao and shipped over APIs) and
     * denormalize() must restore an equal value from the decoded representation.
     * Without this boundary, object instances leaking through normalize() go
     * undetected because denormalize() receives them back unchanged.
     */
    private function jsonRoundTrip(mixed $normalizedValue): mixed
    {
        return json_decode(
            json_encode($normalizedValue, JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function testBooleanSelect(): void
    {
        $originalValue = true;
        $fd = new DataObject\ClassDefinition\Data\BooleanSelect();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testCheckbox(): void
    {
        $originalValue = true;
        $fd = new DataObject\ClassDefinition\Data\Checkbox();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testConsent(): void
    {
        $originalValue = new DataObject\Data\Consent(true);
        $fd = new DataObject\ClassDefinition\Data\Consent();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testCountry(): void
    {
        $originalValue = 'de';
        $fd = new DataObject\ClassDefinition\Data\Country();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testCountrymultiselect(): void
    {
        $originalValue = ['de', 'en'];
        $fd = new DataObject\ClassDefinition\Data\Countrymultiselect();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testDate(): void
    {
        $ts = time();
        $originalValue = new Carbon();
        $originalValue->setTimestamp($ts);
        $fd = new DataObject\ClassDefinition\Data\Date();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($ts, $normalizedValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testDatetime(): void
    {
        $ts = time();
        $originalValue = new Carbon();
        $originalValue->setTimestamp($ts);
        $fd = new DataObject\ClassDefinition\Data\Datetime();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($ts, $normalizedValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testEmail(): void
    {
        $originalValue = uniqid();
        $fd = new DataObject\ClassDefinition\Data\Email();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testEncryptedField(): void
    {
        $container = \Pimcore::getContainer();
        if (!$container->hasParameter('pimcore.encryption.secret') || !$container->getParameter('pimcore.encryption.secret')) {
            $this->markTestSkipped('no pimcore.encryption.secret configured for the test environment');
        }

        $delegate = new DataObject\ClassDefinition\Data\Input();
        $fd = new DataObject\ClassDefinition\Data\EncryptedField();
        $fd->setDelegate($delegate);
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $originalValue = new DataObject\Data\EncryptedField($delegate, 'top secret value');

        // default: plain representation (consumers like a GDPR data-subject export need the data)
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertSame('top secret value', $normalizedValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);

        // opt-in: value stays encrypted — normalize() output is used as a storage/wire
        // format, persisting it must not silently defeat encryption at rest
        $normalizedValue = $fd->normalize($originalValue, ['preserveEncryption' => true]);
        $this->assertIsArray($normalizedValue);
        $this->assertArrayHasKey(DataObject\ClassDefinition\Data\EncryptedField::ENCRYPTED_NORMALIZED_KEY, $normalizedValue);
        $this->assertStringNotContainsString('top secret value', json_encode($normalizedValue, JSON_THROW_ON_ERROR));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testDateRange(): void
    {
        $originalValue = CarbonPeriod::create('2023-01-01', '2025-12-31');

        $fd = new DataObject\ClassDefinition\Data\DateRange();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertIsArray($normalizedValue);
        $this->assertCount(2, $normalizedValue, 'expected the period boundaries only, not every date the period contains');
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue->getStartDate(), $denormalizedValue->getStartDate());
        $this->assertEquals($originalValue->getEndDate(), $denormalizedValue->getEndDate());
    }

    public function testExternalImage(): void
    {
        $originalValue = new DataObject\Data\ExternalImage('http://someurl.com');
        // was Data\Email() — the passthrough normalize returned the ExternalImage object
        // unchanged, so the pre-JSON-boundary assertion compared the object to itself
        // and the type was never actually tested
        $fd = new DataObject\ClassDefinition\Data\ExternalImage();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testFirstname(): void
    {
        $originalValue = 'john' . uniqid();
        $fd = new DataObject\ClassDefinition\Data\Firstname();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGender(): void
    {
        $originalValue = 'male';
        $fd = new DataObject\ClassDefinition\Data\Gender();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeobounds(): void
    {
        $ownerInfo = $this->getDummyOwnerInfo();
        $originalValue = new DataObject\Data\Geobounds(new DataObject\Data\GeoCoordinates(123, -120), new DataObject\Data\GeoCoordinates(456, +130));
        $originalValue->_setOwner($ownerInfo['owner']);
        $originalValue->_setOwnerFieldname($ownerInfo['fieldname']);
        $originalValue->_setOwnerLanguage($ownerInfo['language']);

        $fd = new DataObject\ClassDefinition\Data\Geobounds();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue), $ownerInfo);

        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeopoint(): void
    {
        $ownerInfo = $this->getDummyOwnerInfo();
        $originalValue = new DataObject\Data\GeoCoordinates(123, 56);
        $originalValue->_setOwner($ownerInfo['owner']);
        $originalValue->_setOwnerFieldname($ownerInfo['fieldname']);
        $originalValue->_setOwnerLanguage($ownerInfo['language']);

        $fd = new DataObject\ClassDefinition\Data\Geopoint();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue), $ownerInfo);

        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeopolygon(): void
    {
        $ownerInfo = $this->getDummyOwnerInfo();
        $c1 = new DataObject\Data\GeoCoordinates(123, -120);
        $c1->_setOwner($ownerInfo['owner']);
        $c1->_setOwnerFieldname($ownerInfo['fieldname']);
        $c1->_setOwnerLanguage($ownerInfo['language']);
        $c2 = new DataObject\Data\GeoCoordinates(50, 70);
        $c2->_setOwner($ownerInfo['owner']);
        $c2->_setOwnerFieldname($ownerInfo['fieldname']);
        $c2->_setOwnerLanguage($ownerInfo['language']);
        $c3 = new DataObject\Data\GeoCoordinates(56, 130);
        $c3->_setOwner($ownerInfo['owner']);
        $c3->_setOwnerFieldname($ownerInfo['fieldname']);
        $c3->_setOwnerLanguage($ownerInfo['language']);
        $originalValue = [$c1, $c2, $c3];

        $fd = new DataObject\ClassDefinition\Data\Geopolygon();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);

        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue), $ownerInfo);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeopolyline(): void
    {
        $ownerInfo = $this->getDummyOwnerInfo();
        $c1 = new DataObject\Data\GeoCoordinates(123, -120);
        $c1->_setOwner($ownerInfo['owner']);
        $c1->_setOwnerFieldname($ownerInfo['fieldname']);
        $c1->_setOwnerLanguage($ownerInfo['language']);
        $c2 = new DataObject\Data\GeoCoordinates(50, 70);
        $c2->_setOwner($ownerInfo['owner']);
        $c2->_setOwnerFieldname($ownerInfo['fieldname']);
        $c2->_setOwnerLanguage($ownerInfo['language']);
        $c3 = new DataObject\Data\GeoCoordinates(56, 130);
        $c3->_setOwner($ownerInfo['owner']);
        $c3->_setOwnerFieldname($ownerInfo['fieldname']);
        $c3->_setOwnerLanguage($ownerInfo['language']);
        $originalValue = [$c1, $c2, $c3];

        $fd = new DataObject\ClassDefinition\Data\Geopolyline();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);

        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue), $ownerInfo);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    private function getDummyOwnerInfo(): array
    {
        return [
            'owner' => 'dummy owner',
            'fieldname' => 'dummy field',
            'language' => 'en',
        ];
    }

    public function testHotspotimage(): void
    {
        $asset = TestHelper::createImageAsset();

        $originalValue = new Hotspotimage();
        $originalValue->setImage($asset);
        $originalValue->setCrop([
            'cropWidth' => 60,
            'cropHeight' => 78,
            'cropTop' => 4.1,
            'cropLeft' => 4.2,
            'cropPercent' => true,
        ]);

        $originalValue->setMarker([
            [
                'top' => 56,
                'left' => 62,
                // metadata entries are MarkerHotspotItem instances on a loaded value and
                // must be re-wrapped by denormalize() after the JSON boundary
                'data' => [
                    new MarkerHotspotItem([
                        'name' => 'campaign',
                        'type' => 'textfield',
                        'value' => 'summer',
                    ]),
                ],
            ],
        ]);

        $fd = new DataObject\ClassDefinition\Data\Hotspotimage();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testImage(): void
    {
        $originalValue = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\Image();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testImageGallery(): void
    {
        $originalValue = [];
        for ($i = 0; $i < 3; $i++) {
            $asset = TestHelper::createImageAsset();

            $item = new Hotspotimage();
            $item->setImage($asset);
            $item->setCrop([
                'cropWidth' => 60 + $i,
                'cropHeight' => 78 + $i,
                'cropTop' => 4.1 + $i,
                'cropLeft' => 4.2 + $i,
                'cropPercent' => true,
            ]);

            $item->setMarker([
                [
                    'top' => 56 + $i,
                    'left' => 62 + $i,
                    'data' => [
                        new MarkerHotspotItem([
                            'name' => 'campaign',
                            'type' => 'textfield',
                            'value' => 'summer-' . $i,
                        ]),
                    ],
                ],
            ]);
            $originalValue[] = $item;
        }
        $originalValue = new DataObject\Data\ImageGallery($originalValue);

        $fd = new DataObject\ClassDefinition\Data\ImageGallery();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testInput(): void
    {
        $originalValue = uniqid();
        $fd = new DataObject\ClassDefinition\Data\Input();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testInputQuantityValue(): void
    {
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation('cm');
        if (!$unit) {
            throw new Exception('unknown id');
        }
        $originalValue = new DataObject\Data\InputQuantityValue('123', $unit);
        $fd = new DataObject\ClassDefinition\Data\InputQuantityValue();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));

        $this->assertTrue($denormalizedValue instanceof DataObject\Data\InputQuantityValue);
        $this->assertEquals($originalValue->getValue(), $denormalizedValue->getValue());
        $this->assertEquals($originalValue->getUnitId(), $denormalizedValue->getUnitId());
    }

    public function testLink(): void
    {
        $targetObject = TestHelper::createEmptyObject();

        $originalValue = new Link();
        $originalValue->setInternalType('object');
        $originalValue->setInternal($targetObject->getId());
        $originalValue->setTarget('_blank');
        $originalValue->setTitle('sometitle');
        $fd = new DataObject\ClassDefinition\Data\Link();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testLocalizedfields(): void
    {
        $object = TestHelper::createEmptyObject();
        $targetObject = TestHelper::createEmptyObject();
        $this->assertTrue($object instanceof Unittest);

        $fd = $object->getClass()->getFieldDefinition('localizedfields');

        $object->setLinput('123');
        $object->setLObjects([$targetObject]);

        $originalValue = $object->getLocalizedfields();

        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue, ['object' => $object]);
        /** @var DataObject\Localizedfield $denormalizedValue */
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue), ['object' => $object]);
        $this->assertEquals('123', $denormalizedValue->getLocalizedValue('linput'));

        $objects = $denormalizedValue->getLocalizedValue('lobjects');
        $this->assertEquals($targetObject->getId(), $objects[0]->getId());
    }

    public function testManyToManyObjectRelation(): void
    {
        $targetObject1 = TestHelper::createEmptyObject();
        $targetObject2 = TestHelper::createEmptyObject();

        $originalValue = [$targetObject1, $targetObject2];

        $fd = new DataObject\ClassDefinition\Data\ManyToManyObjectRelation();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $this->assertEquals(2, count($normalizedValue));
        $this->assertTrue(is_array($normalizedValue[0]));
        $this->assertTrue(is_array($normalizedValue[1]));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($targetObject1->getId(), $denormalizedValue[0]->getId());
        $this->assertEquals($targetObject2->getId(), $denormalizedValue[1]->getId());
    }

    public function testManyToManyRelation(): void
    {
        $targetObject1 = TestHelper::createEmptyObject();
        $targetObject2 = TestHelper::createEmptyObject();
        $targetAsset1 = TestHelper::createImageAsset();

        $originalValue = [$targetObject1, $targetObject2, $targetAsset1];

        $fd = new DataObject\ClassDefinition\Data\ManyToManyRelation();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $this->assertEquals(3, count($normalizedValue));
        $this->assertTrue(is_array($normalizedValue[0]));
        $this->assertTrue(is_array($normalizedValue[1]));
        $this->assertTrue(is_array($normalizedValue[2]));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($targetObject1->getId(), $denormalizedValue[0]->getId());
        $this->assertEquals($targetObject2->getId(), $denormalizedValue[1]->getId());
        $this->assertEquals($targetAsset1->getId(), $denormalizedValue[2]->getId());
    }

    public function testManyToOneRelation(): void
    {
        $originalValue = TestHelper::createEmptyObject();

        $fd = new DataObject\ClassDefinition\Data\ManyToOneRelation();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue->getId(), $denormalizedValue->getId());
    }

    public function testMultiselect(): void
    {
        $originalValue = ['A', 'B', 'C'];
        $fd = new DataObject\ClassDefinition\Data\Multiselect();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testNumeric(): void
    {
        $originalValue = 123.1;
        $fd = new DataObject\ClassDefinition\Data\Numeric();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testPassword(): void
    {
        $originalValue = 'mysecret';
        $fd = new DataObject\ClassDefinition\Data\Password();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testQuantityValue(): void
    {
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation('cm');
        if (!$unit) {
            throw new Exception('unknown id');
        }
        $originalValue = new DataObject\Data\QuantityValue(123.4, $unit);
        $fd = new DataObject\ClassDefinition\Data\QuantityValue();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));

        $this->assertTrue($denormalizedValue instanceof DataObject\Data\QuantityValue);
        $this->assertEquals($originalValue->getValue(), $denormalizedValue->getValue());
        $this->assertEquals($originalValue->getUnitId(), $denormalizedValue->getUnitId());
    }

    public function testRgbaColor(): void
    {
        $originalValue = new DataObject\Data\RgbaColor(1, 2, 3, 12);
        $fd = new DataObject\ClassDefinition\Data\RgbaColor();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));

        $this->assertTrue($denormalizedValue instanceof DataObject\Data\RgbaColor);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testSelect(): void
    {
        $originalValue = 'Z';
        $fd = new DataObject\ClassDefinition\Data\Select();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testSlider(): void
    {
        $originalValue = 77;
        $fd = new DataObject\ClassDefinition\Data\Slider();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testStructuredTable(): void
    {
        $data = ['row1' => ['col1' => '1', 'col2' => '2'],
            'row2' => ['col1' => '3', 'col2' => '4'], ];
        $originalValue = new DataObject\Data\StructuredTable();
        $originalValue->setData($data);

        $fd = new DataObject\ClassDefinition\Data\StructuredTable();

        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testTable(): void
    {
        $originalValue = [
            ['A', 'B', 'C'],
            ['E', 'F', 'G'],
            ];

        $fd = new DataObject\ClassDefinition\Data\Table();

        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $this->assertEquals($normalizedValue, $originalValue);

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testTextarea(): void
    {
        $originalValue = uniqid() . "\n" . uniqid();
        $fd = new DataObject\ClassDefinition\Data\Input();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testTime(): void
    {
        $originalValue = '01:23';
        $fd = new DataObject\ClassDefinition\Data\Time();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testUrlSlug(): void
    {
        $originalValue = [
            new DataObject\Data\UrlSlug('/abc', 1),
            new DataObject\Data\UrlSlug('/ebf', 2),
        ];
        $fd = new DataObject\ClassDefinition\Data\UrlSlug();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $this->assertNotEquals($originalValue, $normalizedValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testUser(): void
    {
        $user = User::getByName('admin');
        $originalValue = $user->getId();
        $fd = new DataObject\ClassDefinition\Data\User();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);

        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testVideo(): void
    {
        $video = TestHelper::createImageAsset();
        $this->assertNotNull($video);
        $poster = TestHelper::createImageAsset();

        $originalValue = new DataObject\Data\Video();
        $originalValue->setType('asset');
        $originalValue->setData($video);
        $originalValue->setPoster($poster);
        $originalValue->setTitle('title');
        $originalValue->setDescription('description');

        $fd = new DataObject\ClassDefinition\Data\Video();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $this->assertNotEquals($originalValue, $normalizedValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testWysiwyg(): void
    {
        $originalValue = uniqid() . '<br />' . uniqid();
        $fd = new DataObject\ClassDefinition\Data\Wysiwyg();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($this->jsonRoundTrip($normalizedValue));
        $this->assertEquals($originalValue, $denormalizedValue);
    }
}
