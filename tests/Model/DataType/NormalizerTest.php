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

namespace Pimcore\Tests\Model\DataType;

use Carbon\Carbon;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\User;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

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

    /**
     * @inheritdoc
     */
    protected function needsDb()
    {
        return true;
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testBooleanSelect()
    {
        $originalValue = true;
        $fd = new DataObject\ClassDefinition\Data\BooleanSelect();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testCheckbox()
    {
        $originalValue = true;
        $fd = new DataObject\ClassDefinition\Data\Checkbox();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testConsent()
    {
        $originalValue = new DataObject\Data\Consent(true);
        $fd = new DataObject\ClassDefinition\Data\Consent();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testCountry()
    {
        $originalValue = 'de';
        $fd = new DataObject\ClassDefinition\Data\Country();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testCountrymultiselect()
    {
        $originalValue = ['de', 'en'];
        $fd = new DataObject\ClassDefinition\Data\Countrymultiselect();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testDate()
    {
        $ts = time();
        $originalValue = new Carbon();
        $originalValue->setTimestamp($ts);
        $fd = new DataObject\ClassDefinition\Data\Date();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($ts, $normalizedValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testDatetime()
    {
        $ts = time();
        $originalValue = new Carbon();
        $originalValue->setTimestamp($ts);
        $fd = new DataObject\ClassDefinition\Data\Datetime();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($ts, $normalizedValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testEmail()
    {
        $originalValue = uniqid();
        $fd = new DataObject\ClassDefinition\Data\Email();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testEncryptedField()
    {
        $this->markTestSkipped('implement this as soon as marshal() is gone');
    }

    public function testExternalImage()
    {
        $originalValue = new DataObject\Data\ExternalImage('http://someurl.com');
        $fd = new DataObject\ClassDefinition\Data\Email();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testFirstname()
    {
        $originalValue = 'john' . uniqid();
        $fd = new DataObject\ClassDefinition\Data\Firstname();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGender()
    {
        $originalValue = 'male';
        $fd = new DataObject\ClassDefinition\Data\Gender();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeobounds()
    {
        $originalValue = new DataObject\Data\Geobounds(new DataObject\Data\GeoCoordinates(123, -120), new DataObject\Data\GeoCoordinates(456, +130));
        $fd = new DataObject\ClassDefinition\Data\Geobounds();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeopoint()
    {
        $originalValue = new DataObject\Data\GeoCoordinates(123, 56);
        $fd = new DataObject\ClassDefinition\Data\Geopoint();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testGeopolygon()
    {
        $originalValue = [
            new DataObject\Data\GeoCoordinates(123, -120),
            new DataObject\Data\GeoCoordinates(50, 70),
            new DataObject\Data\GeoCoordinates(56, 130),
        ];
        $fd = new DataObject\ClassDefinition\Data\Geopolygon();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);

        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testHotspotimage()
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
            ],
        ]);

        $fd = new DataObject\ClassDefinition\Data\Hotspotimage();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testImage()
    {
        $originalValue = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\Image();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testImageGallery()
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
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testInput()
    {
        $originalValue = uniqid();
        $fd = new DataObject\ClassDefinition\Data\Input();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testInputQuantityValue()
    {
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation('cm');
        if (!$unit) {
            throw new \Exception('unknown id');
        }
        $originalValue = new DataObject\Data\InputQuantityValue('123', $unit);
        $fd = new DataObject\ClassDefinition\Data\InputQuantityValue();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);

        $this->assertTrue($denormalizedValue instanceof DataObject\Data\InputQuantityValue);
        $this->assertEquals($originalValue->getValue(), $denormalizedValue->getValue());
        $this->assertEquals($originalValue->getUnitId(), $denormalizedValue->getUnitId());
    }

    public function testLink()
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
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testLocalizedfields()
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
        $denormalizedValue = $fd->denormalize($normalizedValue, ['object' => $object]);
        $this->assertEquals('123', $denormalizedValue->getLocalizedValue('linput'));

        $objects = $denormalizedValue->getLocalizedValue('lobjects');
        $this->assertEquals($targetObject->getId(), $objects[0]->getId());
    }

    public function testManyToManyObjectRelation()
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

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($targetObject1->getId(), $denormalizedValue[0]->getId());
        $this->assertEquals($targetObject2->getId(), $denormalizedValue[1]->getId());
    }

    public function testManyToManyRelation()
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

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($targetObject1->getId(), $denormalizedValue[0]->getId());
        $this->assertEquals($targetObject2->getId(), $denormalizedValue[1]->getId());
        $this->assertEquals($targetAsset1->getId(), $denormalizedValue[2]->getId());
    }

    public function testManyToOneRelation()
    {
        $originalValue = TestHelper::createEmptyObject();

        $fd = new DataObject\ClassDefinition\Data\ManyToOneRelation();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue->getId(), $denormalizedValue->getId());
    }

    public function testMultiselect()
    {
        $originalValue = ['A', 'B', 'C'];
        $fd = new DataObject\ClassDefinition\Data\Multiselect();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testNewsletterActive()
    {
        $originalValue = true;
        $fd = new DataObject\ClassDefinition\Data\NewsletterActive();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testNewsletterConfirmed()
    {
        $originalValue = true;
        $fd = new DataObject\ClassDefinition\Data\NewsletterConfirmed();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testNumeric()
    {
        $originalValue = 123.1;
        $fd = new DataObject\ClassDefinition\Data\Numeric();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testPassword()
    {
        $originalValue = 'mysecret';
        $fd = new DataObject\ClassDefinition\Data\Password();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testQuantityValue()
    {
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation('cm');
        if (!$unit) {
            throw new \Exception('unknown id');
        }
        $originalValue = new DataObject\Data\QuantityValue(123.4, $unit);
        $fd = new DataObject\ClassDefinition\Data\QuantityValue();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);

        $this->assertTrue($denormalizedValue instanceof DataObject\Data\QuantityValue);
        $this->assertEquals($originalValue->getValue(), $denormalizedValue->getValue());
        $this->assertEquals($originalValue->getUnitId(), $denormalizedValue->getUnitId());
    }

    public function testRgbaColor()
    {
        $originalValue = new DataObject\Data\RgbaColor(1, 2, 3, 12);
        $fd = new DataObject\ClassDefinition\Data\RgbaColor();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertNotEquals($normalizedValue, $originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);

        $this->assertTrue($denormalizedValue instanceof DataObject\Data\RgbaColor);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testSelect()
    {
        $originalValue = 'Z';
        $fd = new DataObject\ClassDefinition\Data\Select();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testSlider()
    {
        $originalValue = 77;
        $fd = new DataObject\ClassDefinition\Data\Slider();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testStructuredTable()
    {
        $data = ['row1' => ['col1' => '1', 'col2' => '2'],
            'row2' => ['col1' => '3', 'col2' => '4'], ];
        $originalValue = new DataObject\Data\StructuredTable();
        $originalValue->setData($data);

        $fd = new DataObject\ClassDefinition\Data\StructuredTable();

        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertTrue(is_array($normalizedValue));

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testTable()
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

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testTextarea()
    {
        $originalValue = uniqid() . "\n" . uniqid();
        $fd = new DataObject\ClassDefinition\Data\Input();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testTime()
    {
        $originalValue = '01:23';
        $fd = new DataObject\ClassDefinition\Data\Time();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testUrlSlug()
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
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testUser()
    {
        $user = User::getByName('admin');
        $originalValue = $user->getId();
        $fd = new DataObject\ClassDefinition\Data\User();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');

        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($originalValue, $normalizedValue);

        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testVideo()
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
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }

    public function testWysiwyg()
    {
        $originalValue = uniqid() . '<br>' . uniqid();
        $fd = new DataObject\ClassDefinition\Data\Wysiwyg();
        $this->assertTrue($fd instanceof NormalizerInterface, 'expected NormalizerInterface');
        $normalizedValue = $fd->normalize($originalValue);
        $this->assertEquals($normalizedValue, $originalValue);
        $denormalizedValue = $fd->denormalize($normalizedValue);
        $this->assertEquals($originalValue, $denormalizedValue);
    }
}
