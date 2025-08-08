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

namespace Pimcore\Tests\Support\Helper\DataType;

use DateTime;
use Exception;
use InvalidArgumentException;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Property;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Helper\AbstractTestDataHelper;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Authentication;
use TypeError;

class TestDataHelper extends AbstractTestDataHelper
{
    const IMAGE = 'sampleimage.jpg';

    const DOCUMENT = 'sampledocument.txt';

    const HOTSPOT_IMAGE = 'hotspot.jpg';

    public function assertBooleanSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = ($seed % 2) == true;

        $this->assertEquals($expected, $value);
    }

    public function assertBricks(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        $value = $object->$getter();

        /** @var DataObject\Unittest\Mybricks $value */
        $value = $value->getUnittestBrick();

        /** @var DataObject\Objectbrick\Data\UnittestBrick $value */
        $inputValue = $value->getBrickinput();

        $expectedInputValue = 'brickinput' . $seed;

        $this->assertEquals($expectedInputValue, $inputValue);

        $fieldLazyRelation = $value->getBrickLazyRelation();
        $this->assertEquals(15, count($fieldLazyRelation), 'expected 15 items');

        Cache::clearAll();
        RuntimeCache::clear();
        $object = AbstractObject::getById($object->getId());
        $value = $object->$getter();
        $value = $value->getItems();

        /** @var DataObject\Fieldcollection\Data\Unittestfieldcollection $value */
        $value = $value[0];

        $fieldLazyRelation = $value->getBrickLazyRelation();
        $this->assertEquals(15, count($fieldLazyRelation), 'expected 15 items');
    }

    public function assertCountry(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = 'AU';

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function getFieldDefinition(Concrete $object, string $field): ?Data
    {
        $cd = $object->getClass();
        $fd = $cd->getFieldDefinition($field);

        return $fd;
    }

    public function assertIsEqual(Concrete $object, string $field, mixed $expected, mixed $value): void
    {
        $fd = $this->getFieldDefinition($object, $field);
        if ($fd instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
            $this->assertTrue($fd->isEqual($expected, $value), sprintf('Expected isEqual() returns true for data type: %s', ucfirst($field)));
        } else {
            $this->fail('Expected interface EqualComparisonInterface for data type ' . $fd->getFieldType());
        }
    }

    public function assertIsNotEqual(Concrete $object, string $field, mixed $expected, mixed $value): void
    {
        $fd = $this->getFieldDefinition($object, $field);
        if ($fd instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
            $this->assertFalse($fd->isEqual($expected, $value), sprintf('Expected isEqual() returns false for data type: %s', ucfirst($field)));
        } else {
            $this->fail('Expected interface EqualComparisonInterface for data type ' . $fd->getFieldType());
        }
    }

    public function assertGetDataForGrid(Concrete $object, string $field, mixed $value, mixed $expected): void
    {
        $fd = $this->getFieldDefinition($object, $field);
        if (method_exists($fd, 'getDataForGrid')) {
            $this->assertEquals($fd->getDataForGrid($value, $object), $expected);
        } else {
            $this->fail('Expected method getDataForGrid() for data type ' . $fd->getFieldType());
        }
    }

    public function assertCountryMultiSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = ['1', '2'];

        $this->assertEquals($expected, $value);
    }

    public function assertDate(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DateTime $value */
        $value = $object->$getter();

        $expected = new DateTime();
        $expected->setDate(2000, 12, 24);

        //set time for datetime isEqual comparison
        if ($field == 'datetime') {
            $expected->setTime((int)$value->format('H'), (int)$value->format('i'), (int)$value->format('s'));
        }

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals(
            $expected->format('Y-m-d'),
            $value->format('Y-m-d')
        );
    }

    public function assertDatePeriod(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var \Carbon\CarbonPeriod $value */
        $value = $object->$getter();

        $expected = new \Carbon\CarbonPeriod('2018-04-21', '3 days', '2018-04-27');

        $this->assertIsEqual($object, $field, $expected, $value);
    }

    public function assertEmail(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = 'john@doe.com' . $seed;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertEncrypted(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $getter = 'get' . ucfirst($field);
        if ($language) {
            $value = $object->$getter($language);
        } else {
            $value = $object->$getter();
        }

        $expected = $language . 'content' . $seed;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertGetDataForGrid($object, $field, $value, $expected);
        $this->assertEquals($expected, $value);
    }

    public function assertExternalImage(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        /** @var DataObject\Data\ExternalImage $container */
        $container = $object->$getter();

        $this->assertInstanceOf(DataObject\Data\ExternalImage::class, $container);

        $value = $container->getUrl();
        $expected = 'someUrl' . $seed;

        $this->assertEquals($expected, $value);
    }

    public function assertFieldCollection(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Fieldcollection $value */
        $value = $object->$getter();

        $this->assertEquals(1, $value->getCount(), 'expected 1 item');

        $value = $value->getItems();

        /** @var DataObject\Fieldcollection\Data\Unittestfieldcollection $value */
        $value = $value[0];

        $this->assertEquals(
            'field1' . $seed,
            $value->getFieldinput1(),
            'expected field1' . $seed . ' but was ' . $value->getFieldInput1()
        );

        $this->assertEquals(
            'field2' . $seed,
            $value->getFieldinput2(),
            'expected field2' . $seed . ' but was ' . $value->getFieldInput2()
        );

        $fieldRelation = $value->getFieldRelation();
        $this->assertEquals(10, count($fieldRelation), 'expected 10 items');

        $fieldLazyRelation = $value->getFieldLazyRelation();
        $this->assertEquals(15, count($fieldLazyRelation), 'expected 15 items');

        Cache::clearAll();
        RuntimeCache::clear();
        $object = AbstractObject::getById($object->getId());
        $value = $object->$getter();
        $value = $value->getItems();

        /** @var DataObject\Fieldcollection\Data\Unittestfieldcollection $value */
        $value = $value[0];
        $fieldRelation = $value->getFieldRelation();
        $this->assertEquals(10, count($fieldRelation), 'expected 10 items');

        $fieldLazyRelation = $value->getFieldLazyRelation();
        $this->assertEquals(15, count($fieldLazyRelation), 'expected 15 items');
    }

    public function assertFirstname(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $this->assertInput($object, $field, $seed, $language);
    }

    public function assertInput(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $getter = 'get' . ucfirst($field);
        if ($language) {
            $value = $object->$getter($language);
        } else {
            $value = $object->$getter();
        }

        $expected = $language . 'content' . $seed;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertGender(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = $seed % 2 === 0 ? 'male' : 'female';

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertGeobounds(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\Geobounds $value */
        $value = $object->$getter();

        $this->assertNotNull($value);
        $this->assertInstanceOf(DataObject\Data\Geobounds::class, $value);

        $expected = $this->getGeoboundsFixture();

        $this->assertEquals($expected->__toString(), $value->__toString(), 'String representations are equal');
        $this->assertEquals($expected, $value, 'Objects are equal');
    }

    protected function getGeoboundsFixture(): DataObject\Data\Geobounds
    {
        return new DataObject\Data\Geobounds(
            new DataObject\Data\GeoCoordinates(-33.704920213014, 150.60333251953),
            new DataObject\Data\GeoCoordinates(-33.893217379440, 150.60333251953)
        );
    }

    public function assertGeoCoordinates(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\GeoCoordinates $value */
        $value = $object->$getter();

        $this->assertNotNull($value);
        $this->assertInstanceOf(DataObject\Data\GeoCoordinates::class, $value);

        $expected = $this->getGeoCoordinatesFixture();

        $this->assertEquals($expected->__toString(), $value->__toString(), 'String representations are equal');
        $this->assertEquals($expected, $value, 'Objects are equal');
    }

    protected function getGeoCoordinatesFixture(): DataObject\Data\GeoCoordinates
    {
        $longitude = 2.2008440814678;
        $latitude = 102.25112915039;
        $point = new DataObject\Data\GeoCoordinates($latitude, $longitude);

        return $point;
    }

    public function assertGeopolygon(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\GeoCoordinates[] $value */
        $value = $object->$getter();

        $expected = $this->getGeopolygonFixture();

        $this->assertTrue(is_array($value));
        $this->assertCount(count($expected), $value);
        $this->assertEquals($expected, $value);

        foreach ($value as $i => $point) {
            $expectedPoint = $expected[$i];

            $this->assertNotNull($point);
            $this->assertInstanceOf(DataObject\Data\GeoCoordinates::class, $point);
            $this->assertEquals($expectedPoint->__toString(), $point->__toString(), 'String representations are equal');
            $this->assertEquals($expectedPoint, $point, 'Objects are equal');
        }
    }

    /**
     * @return DataObject\Data\GeoCoordinates[]
     */
    protected function getGeopolygonFixture(): array
    {
        return [
            new DataObject\Data\GeoCoordinates(-33.464671118242684, 150.54428100585938),
            new DataObject\Data\GeoCoordinates(-33.913733814316245, 150.73654174804688),
            new DataObject\Data\GeoCoordinates(-33.9946115848146, 151.2542724609375),
        ];
    }

    public function assertHotspotImage(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\Hotspotimage $value */
        $value = $object->$getter();
        $hotspots = $value->getHotspots();

        $this->assertCount(2, $hotspots);
        $this->assertInstanceOf(DataObject\Data\Hotspotimage::class, $value);

        $asset = Asset::getByPath('/' . static::HOTSPOT_IMAGE);
        $hotspots = $this->createHotspots();
        $expected = new DataObject\Data\Hotspotimage($asset, $hotspots);

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertAssetsEqual($expected->getImage(), $value->getImage());
        $this->assertEquals($expected->getHotspots(), $value->getHotspots());
    }

    private function createHotspots(int $idx = null, int $seed = 0): array
    {
        $result = [];

        $hotspot1 = [
            'name' => 'hotspot_' . (is_null($idx) ? 1 : $idx) . '_' . $seed,
            'width' => 10 + $idx,
            'height' => 20 + $idx,
            'top' => 30 + $idx,
            'left' => 40 + $idx,
        ];
        $result[] = $hotspot1;

        $hotspot2 = [
            'name' => 'hotspot_' . (is_null($idx) ? 2 : $idx) . '_' . $seed,
            'width' => 10 + $idx,
            'height' => 50 + $idx,
            'top' => 20 + $idx,
            'left' => 40 + $idx,
        ];

        $result[] = $hotspot2;

        return $result;
    }

    public function assertAssetsEqual(Asset $asset1, Asset $asset2): void
    {
        $this->assertElementsEqual($asset1, $asset2);

        $str1 = TestHelper::createAssetComparisonString($asset1);
        $str2 = TestHelper::createAssetComparisonString($asset2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    public function assertElementsEqual(ElementInterface $e1, ElementInterface $e2): void
    {
        $this->assertEquals(get_class($e1), get_class($e2));
        $this->assertEquals($e1->getId(), $e2->getId());
        $this->assertEquals($e1->getType(), $e2->getType());
        $this->assertEquals($e1->getFullPath(), $e2->getFullPath());
    }

    public function assertHref(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $objects = $this->getObjectList();
        $expected = $objects[0];

        $this->assertNotNull($value);
        $this->assertInstanceOf(AbstractObject::class, $value);
        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertObjectsEqual($expected, $value);
    }

    public function assertImage(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = Asset::getByPath('/' . static::IMAGE);

        foreach ([$value, $expected] as $item) {
            $this->assertNotNull($item);
            $this->assertInstanceOf(Asset::class, $item);
        }

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertAssetsEqual($expected, $value);
    }

    public function assertImageGallery(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\ImageGallery $value */
        $value = $object->$getter();
        $this->assertInstanceOf(DataObject\Data\ImageGallery::class, $value);
        $items = $value->getItems();

        $this->assertCount(2, $items);

        $item0 = $items[0];
        $this->assertEquals($item0->getImage()->getFilename(), 'gal0.jpg');

        $item2 = $items[1];
        $this->assertEquals($item2->getImage()->getFilename(), 'gal2.jpg');
        $hotspots = $item2->getHotspots();
        $this->assertEquals('hotspot_2_' . $seed, $hotspots[0]['name']);
    }

    public function assertInputQuantityValue(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        /** @var DataObject\Data\InputQuantityValue $qv */
        $qv = $object->$getter();

        $expectedAbbr = $this->mapUnit($seed);
        $actualAbbreviation = $qv->getUnit()->getAbbreviation();

        $this->assertInstanceOf(DataObject\Data\InputQuantityValue::class, $qv);
        $this->assertEquals($expectedAbbr, $actualAbbreviation);
        $this->assertEquals('abc' . $seed, $qv->getValue());
    }

    public function assertLanguage(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = 'de';

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertLanguageMultiSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = ['1', '3'];

        $this->assertEquals($expected, $value);
    }

    public function assertLastname(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $this->assertInput($object, $field, $seed, $language);
    }

    public function assertLink(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\Link $link */
        $link = $object->$getter();

        $this->assertNotNull($link);
        $this->assertInstanceOf(DataObject\Data\Link::class, $link);

        $document = Document::getByPath((string)$link->getElement());
        $expected = Document::getByPath('/' . static::DOCUMENT . $seed);

        foreach (['expected' => $expected, 'value' => $document] as $desc => $item) {
            $this->assertNotNull($item, $desc . ' is not null');
            $this->assertInstanceOf(Document::class, $item, $desc . ' is a Document');
        }

        $this->assertDocumentsEqual($expected, $document);
    }

    public function assertDocumentsEqual(Document $doc1, Document $doc2): void
    {
        $this->assertElementsEqual($doc1, $doc2);

        $str1 = TestHelper::createDocumentComparisonString($doc1);
        $str2 = TestHelper::createDocumentComparisonString($doc2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    public function assertMultiSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = ['1', '2'];

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertMultihref(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $objects = $this->getObjectList();
        $expectedArray = array_slice($objects, 0, 4);

        $this->assertCount(count($expectedArray), $value);
        $this->assertIsEqual($object, $field, $expectedArray, $value);

        for ($i = 0; $i < count($expectedArray); $i++) {
            $this->assertNotNull($value[$i]);
            $this->assertInstanceOf(AbstractObject::class, $value[$i]);
            $this->assertObjectsEqual($expectedArray[$i], $value[$i]);
        }
    }

    public function assertCheckbox(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = ($seed % 2) == true;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertNumber(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = '123' + $seed;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertObjects(Concrete|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $getter = 'get' . ucfirst($field);

        $objects = $this->getObjectList("`type` = 'object'");

        if ($language) {
            if ($language === 'de') {
                $expectedArray = array_slice($objects, 0, 6);
            } else {
                $expectedArray = array_slice($objects, 0, 5);
            }
            $value = $object->$getter($language);
        } else {
            $expectedArray = array_slice($objects, 0, 4);

            $value = $object->$getter();
        }

        $this->assertIsEqual($object, $field, $expectedArray, $value);

        $this->assertEquals(
            $this->getElementPaths($expectedArray),
            $this->getElementPaths($value)
        );

        $this->assertCount(count($expectedArray), $value);

        $this->assertEquals(
            $this->getElementPaths($expectedArray),
            $this->getElementPaths($value)
        );

        for ($i = 0; $i < count($expectedArray); $i++) {
            $this->assertNotNull($value[$i]);
            $this->assertInstanceOf(AbstractObject::class, $value[$i]);
            $this->assertObjectsEqual($expectedArray[$i], $value[$i]);
        }
    }

    /**
     * @param ElementInterface[] $elements
     */
    private function getElementPaths(array $elements = []): array
    {
        $paths = [];
        foreach ($elements as $element) {
            if (!($element instanceof ElementInterface)) {
                throw new InvalidArgumentException(sprintf('Invalid element. Must be an instance of %s', ElementInterface::class));
            }

            $paths[] = $element->getRealFullPath();
        }

        return $paths;
    }

    public function assertObjectsWithMetadata(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();

        $expected = $this->getObjectsWithMetadataFixture($field, $seed);

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertObjectMetadataEqual($expected, $value);
    }

    /**
     *
     * @return DataObject\Data\ObjectMetadata[]
     */
    public function getObjectsWithMetadataFixture(string $field, int $seed): array
    {
        $objects = $this->getObjectList("`type` = 'object' AND className = 'unittest'");
        $objects = array_slice($objects, 0, 4);

        $metaobjects = [];
        foreach ($objects as $o) {
            $mo = new DataObject\Data\ObjectMetadata($field, ['meta1', 'meta2'], $o);
            $mo->setMeta1('value1' . $seed);
            $mo->setMeta2('value2' . $seed);
            $metaobjects[] = $mo;
        }

        return $metaobjects;
    }

    /**
     * @param DataObject\Data\ObjectMetadata[] $expected
     * @param DataObject\Data\ObjectMetadata[] $value
     */
    protected function assertObjectMetadataEqual(array $expected, array $value): void
    {
        // see https://github.com/sebastianbergmann/phpunit/commit/50ad7e1c4e74dce3beff17bf9c9f5a458cbe9958
        $this->assertTrue(is_array($expected), 'expected an array');
        $this->assertTrue(is_array($value), 'expected an array');

        $this->assertCount(count($expected), $value);

        foreach ($expected as $i => $expectedMetadata) {
            $valueMetadata = $value[$i];

            $this->assertEquals($expectedMetadata->getColumns(), $valueMetadata->getColumns());
            $this->assertObjectsEqual($expectedMetadata->getElement(), $valueMetadata->getElement());

            foreach ($expectedMetadata->getColumns() as $column) {
                $getter = 'get' . ucfirst($column);
                $this->assertEquals($expectedMetadata->$getter(), $valueMetadata->$getter());
            }
        }
    }

    public function assertPassword(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();

        $unencryptedValue = 'sEcret$%!' . $seed;
        $this->assertNotNull($value, 'Password getter is expected to return non null value');

        $this->assertNotEquals($unencryptedValue, $value, 'Value not encrypted');

        $info = password_get_info($value);
        $this->assertNotNull($info['algo'], 'Not properly encrypted');
    }

    public function assertQuantityValue(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        /** @var DataObject\Data\QuantityValue $qv */
        $qv = $object->$getter();

        $expectedAbbr = $this->mapUnit($seed);
        $actualAbbreviation = $qv->getUnit()->getAbbreviation();

        $this->assertInstanceOf(DataObject\Data\QuantityValue::class, $qv);
        $this->assertEquals($expectedAbbr, $actualAbbreviation);
        $this->assertEquals(1000 + $seed, $qv->getValue());
    }

    public function assertRgbaColor(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        /** @var DataObject\Data\RgbaColor $value */
        $value = $object->$getter();
        $this->assertInstanceOf(DataObject\Data\RgbaColor::class, $value);

        $expectedBase = $seed % 200;

        $this->assertEquals($expectedBase, $value->getR());
        $this->assertEquals($expectedBase + 1, $value->getG());
        $this->assertEquals($expectedBase + 2, $value->getB());
        $this->assertEquals($expectedBase + 3, $value->getA());

        $expectedValue = new DataObject\Data\RgbaColor($expectedBase, $expectedBase + 1, $expectedBase + 2, $expectedBase + 3);

        $this->assertIsEqual($object, $field, $expectedValue, $value);
    }

    public function assertSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = 1 + ($seed % 2);

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertSlider(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = 7 + ($seed % 3);

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertStructuredTable(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\StructuredTable $value */
        $value = $object->$getter();

        $this->assertNotNull($value);
        $this->assertInstanceOf(DataObject\Data\StructuredTable::class, $value);

        $expected = $this->getStructuredTableData($seed);

        $this->assertEquals($expected, $value);
        $this->assertEquals($expected->getData(), $value->getData());
    }

    private function getStructuredTableData(int $seed = 1): DataObject\Data\StructuredTable
    {
        $data['row1']['col1'] = 1 + $seed;
        $data['row2']['col1'] = 2 + $seed;
        $data['row3']['col1'] = 3 + $seed;

        $data['row1']['col2'] = 'text_a_' . $seed;
        $data['row2']['col2'] = 'text_b_' . $seed;
        $data['row3']['col2'] = 'text_c_' . $seed;

        $st = new DataObject\Data\StructuredTable();
        $st->setData($data);

        return $st;
    }

    public function assertTable(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();

        $expected = $this->getTableDataFixture($seed);

        $this->assertEquals($expected, $value);
    }

    protected function getTableDataFixture(int $seed): array
    {
        return [['eins', 'zwei', 'drei'], [$seed, 2, 3], ['a', 'b', 'c']];
    }

    public function assertTime(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = '06:4' . $seed % 10;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertUrlSlug(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $getter = 'get' . ucfirst($field);
        if ($language) {
            $value = $object->$getter($language);
            $expected = '/' . $language . '/content' . $seed;
        } else {
            $value = $object->$getter();
            $expected = '/content' . $seed;
        }

        $this->assertTrue(is_array($value) && count($value) == 1, 'expected one item');

        /** @var DataObject\Data\UrlSlug $value */
        $value = $value[0];
        $value = $value->getSlug();

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function assertUser(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $user = User::getByName('unittestdatauser' . $seed);
        $expected = $user->getId();

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    /**
     * @param array<string, mixed> $returnParams
     */
    public function assertVideo(Concrete $object, string $field, array $returnParams, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        /** @var DataObject\Data\Video $value */
        $value = $object->$getter();
        $this->assertInstanceOf(DataObject\Data\Video::class, $value);

        $this->assertEquals('title' . $seed, $value->getTitle());
        $this->assertEquals('description' . $seed, $value->getDescription());
        $this->assertEquals('asset', $value->getType());

        $this->assertEquals($returnParams['poster']->getId(), $value->getPoster()->getId());
        $this->assertEquals($returnParams['video']->getId(), $value->getData()->getId());
    }

    // @todo
    public function assertWysiwyg(Concrete $object, string $field, int $seed = 1): void
    {
        $this->assertTextarea($object, $field, $seed);
    }

    public function assertTextarea(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();
        $expected = 'sometext<br />' . $seed;

        $this->assertIsEqual($object, $field, $expected, $value);
        $this->assertEquals($expected, $value);
    }

    public function checkValidityGeobounds(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        try {
            $object->$setter(1.234);
            $this->fail('expected an instance of Geobounds');
        } catch (TypeError $e) {
        }
    }

    public function checkValidityGeoCoordinates(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        try {
            $object->$setter(1.234);
            $this->fail('expected an instance of Geopoint');
        } catch (TypeError $e) {
        }
    }

    public function checkValidityGeopolyline(Concrete $object, string $field, int $seed = 1): void
    {
        $this->checkValidityGeopolygon($object, $field, $seed);
    }

    public function checkValidityGeopolygon(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        try {
            $invalidValue = ['1234', null];
            $object->$setter($invalidValue);
            $object->save();
            $this->fail('expected a ValidationException');
        } catch (Exception $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
        }
    }

    public function checkValidityQuantityValue(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        try {
            $invalidValue = new DataObject\Data\QuantityValue('abc');
            $object->$setter($invalidValue);
            $object->save();
            $this->fail('expected a ValidationException');
        } catch (Exception $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
        }
    }

    public function checkValidityRgbaColor(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        try {
            $invalidValue = new DataObject\Data\RgbaColor(1000, 2000, -1, 0);
            $object->$setter($invalidValue);
            $object->save();
            $this->fail('expected a ValidationException');
        } catch (Exception $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
        }

        try {
            $object->$setter('#FF0000');
            $this->fail('expected an instance of RgbaColor');
        } catch (TypeError $e) {
        }
    }

    public function fillBooleanSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(($seed % 2) == true);
    }

    public function fillBricks(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);

        $brick = new DataObject\Objectbrick\Data\UnittestBrick($object);
        $brick->setBrickInput('brickinput' . $seed);

        $emptyObjects = TestHelper::createEmptyObjects('myBrickPrefix', true, 10);
        $emptyLazyObjects = TestHelper::createEmptyObjects('myLazyBrickPrefix', true, 15);
        $brick->setBrickLazyRelation($emptyLazyObjects);

        /** @var DataObject\Unittest\Mybricks $objectbricks */
        $objectbricks = $object->$getter();
        $objectbricks->setUnittestBrick($brick);
    }

    public function fillCalculatedValue(Concrete $object, string $field, int $seed = 1): void
    {
        // nothing to do
    }

    public function fillCountry(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('AU');
    }

    public function fillCountryMultiSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(['1', '2']);
    }

    public function fillDate(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $date = new \Carbon\Carbon();
        $date->setDate(2000, 12, 24);

        $object->$setter($date);
    }

    public function fillDateRange(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $period = new \Carbon\CarbonPeriod('2018-04-21', '3 days', '2018-04-27');

        $object->$setter($period);
    }

    public function fillEmail(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('john@doe.com' . $seed);
    }

    public function fillEncryptedField(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $setter = 'set' . ucfirst($field);
        if ($language) {
            $object->$setter($language . 'content' . $seed, $language);
        } else {
            $object->$setter('content' . $seed);
        }
    }

    public function fillExternalImage(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $value = new DataObject\Data\ExternalImage('someUrl' . $seed);
        $object->$setter($value);
    }

    public function fillFieldCollection(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $fc = new DataObject\Fieldcollection\Data\Unittestfieldcollection();
        $fc->setFieldinput1('field1' . $seed);
        $fc->setFieldinput2('field2' . $seed);

        $emptyObjects = TestHelper::createEmptyObjects('myprefix', true, 10);
        $emptyLazyObjects = TestHelper::createEmptyObjects('myLazyPrefix', true, 15);
        $fc->setFieldRelation($emptyObjects);
        $fc->setFieldLazyRelation($emptyLazyObjects);
        $items = new DataObject\Fieldcollection([$fc], $field);
        $object->$setter($items);
    }

    public function fillFirstname(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $this->fillInput($object, $field, $seed, $language);
    }

    public function fillInput(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $setter = 'set' . ucfirst($field);
        if ($language) {
            $object->$setter($language . 'content' . $seed, $language);
        } else {
            $object->$setter('content' . $seed);
        }
    }

    public function fillGender(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $value = $seed % 2 == 0 ? 'male' : 'female';
        $object->$setter($value);
    }

    public function fillGeobounds(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getGeoboundsFixture());
    }

    public function fillGeoCoordinates(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getGeoCoordinatesFixture());
    }

    public function fillGeopolygon(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getGeopolygonFixture());
    }

    public function fillGeopolyline(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getGeopolygonFixture());
    }

    public function fillHotspotImage(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $asset = Asset::getByPath('/' . static::HOTSPOT_IMAGE);
        if (!$asset) {
            $asset = TestHelper::createImageAsset('', null, false);
            $asset->setFilename(static::HOTSPOT_IMAGE);
            $asset->save();
        }

        $hotspots = $this->createHotspots();
        $hotspotImage = new DataObject\Data\Hotspotimage($asset, $hotspots);
        $object->$setter($hotspotImage);
    }

    public function fillHref(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $objects = $this->getObjectList();
        $object->$setter($objects[0]);
    }

    public function fillImage(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $asset = Asset::getByPath('/' . static::IMAGE);
        if (!$asset) {
            $asset = TestHelper::createImageAsset('', null, false);
            $asset->setFilename(static::IMAGE);
            $asset->save();
        }

        $object->$setter($asset);
    }

    public function fillImageGallery(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $filenames = ['gal0.jpg', null, 'gal2.jpg'];
        $hotspotImages = [];

        $idx = 0;
        foreach ($filenames as $filename) {
            if (is_null($filename)) {
                $hotspotImages[] = null;
                $idx++;

                continue;
            }
            $asset = Asset::getByPath('/' . $filename);
            if (!$asset) {
                $asset = TestHelper::createImageAsset('', null, false);
                $asset->setFilename($filename);
                $asset->save();
                $hotspots = $this->createHotspots($idx, $seed);
                $hotspotImage = new DataObject\Data\Hotspotimage($asset, $hotspots);

                $hotspotImages[] = $hotspotImage;
            }
            $idx++;
        }

        $gallery = new DataObject\Data\ImageGallery($hotspotImages);
        $object->$setter($gallery);
    }

    public function fillInputQuantityValue(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $abbr = $this->mapUnit($seed);
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation($abbr);
        $this->assertNotNull($unit);
        $qv = new DataObject\Data\InputQuantityValue('abc' . $seed, $unit);

        $object->$setter($qv);
    }

    public function fillLanguage(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('de');
    }

    public function fillLanguageMultiSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(['1', '2']);
    }

    public function fillLastname(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $this->fillInput($object, $field, $seed, $language);
    }

    public function fillLink(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $doc = Document::getByPath('/' . static::DOCUMENT . $seed);

        if (!$doc) {
            $doc = TestHelper::createEmptyDocumentPage(null, false);
            $doc->setProperties($this->createRandomProperties());
            $doc->setKey(static::DOCUMENT . $seed);
            $doc->save();
        }

        $link = new DataObject\Data\Link();
        $link->setPath((string)$doc);

        $object->$setter($link);
    }

    /**
     * @return Property[]
     */
    private function createRandomProperties(): array
    {
        $properties = [];

        // object property
        $property = new Property();
        $property->setType('object');
        $property->setName('object');
        $property->setInheritable(true);

        $properties[] = $property;

        return $properties;
    }

    public function fillMultiSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(['1', '2']);
    }

    public function fillMultihref(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $objects = $this->getObjectList();
        $objects = array_slice($objects, 0, 4);

        $object->$setter($objects);
    }

    public function fillCheckbox(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(($seed % 2) == true);
    }

    public function fillNumber(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(123 + $seed);
    }

    public function fillObjects(Concrete|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $setter = 'set' . ucfirst($field);
        $objects = $this->getObjectList("`type` = 'object'");

        if ($language) {
            if ($language == 'de') {
                $objects = array_slice($objects, 0, 6);
            } else {
                $objects = array_slice($objects, 0, 5);
            }
            $object->$setter($objects, $language);
        } else {
            $objects = array_slice($objects, 0, 4);
            $object->$setter($objects);
        }
    }

    public function fillObjectsWithMetadata(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getObjectsWithMetadataFixture($field, $seed));
    }

    public function fillPassword(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('sEcret$%!' . $seed);
    }

    public function fillQuantityValue(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $abbr = $this->mapUnit($seed);
        $unit = DataObject\QuantityValue\Unit::getByAbbreviation($abbr);
        $this->assertNotNull($unit);
        $qv = new DataObject\Data\QuantityValue(1000 + $seed, $unit);

        $object->$setter($qv);
    }

    public function mapUnit(int $seed): string
    {
        $map = ['mm', 'cm', 'dm', 'm', 'km'];
        $seed = $seed % 5;

        return $map[$seed];
    }

    public function fillRgbaColor(Concrete $object, string $field, int $seed = 1): void
    {
        $value = $seed % 200;
        $value = new DataObject\Data\RgbaColor($value, $value + 1, $value + 2, $value + 3);

        $setter = 'set' . ucfirst($field);
        $object->$setter($value);
    }

    public function fillSelect(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter((string)(1 + ($seed % 2)));
    }

    public function fillSlider(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter(7 + ($seed % 3));
    }

    public function fillStructuredtable(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getStructuredTableData($seed));
    }

    public function fillTable(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter($this->getTableDataFixture($seed));
    }

    public function fillTime(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('06:4' . $seed % 10);
    }

    public function fillUrlSlug(Concrete $object, string $field, int $seed = 1, ?string $language = null): void
    {
        $setter = 'set' . ucfirst($field);
        if ($language) {
            $data = new DataObject\Data\UrlSlug('/' . $language . '/content' . $seed);
            $object->$setter([$data], $language);
        } else {
            $data = new DataObject\Data\UrlSlug('/content' . $seed);
            $object->$setter([$data]);
        }
    }

    public function fillUser(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $username = 'unittestdatauser' . $seed;
        $user = User::getByName($username);

        if (!$user) {
            $user = User::create([
                'parentId' => 0,
                'username' => $username,
                'password' => Authentication::getPasswordHash($username, $username),
                'active' => true,
            ]);

            $user->setAdmin(true);
            $user->save();
        }

        $object->$setter((string)$user->getId());
    }

    public function fillVideo(Concrete $object, string $field, int $seed = 1, array &$returnData = []): void
    {
        $setter = 'set' . ucfirst($field);

        $video = TestHelper::createVideoAsset();
        $this->assertNotNull($video);
        $poster = TestHelper::createImageAsset();

        $this->assertNotNull($poster);

        $returnData['video'] = $video;
        $returnData['poster'] = $poster;

        $value = new DataObject\Data\Video();
        $value->setType('asset');
        $value->setData($video);
        $value->setPoster($poster);
        $value->setTitle('title' . $seed);
        $value->setDescription('description' . $seed);

        $object->$setter($value);
    }

    public function fillWysiwyg(Concrete $object, string $field, int $seed = 1): void
    {
        $this->fillTextarea($object, $field, $seed);
    }

    public function fillTextarea(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('sometext<br />' . $seed);
    }
}
