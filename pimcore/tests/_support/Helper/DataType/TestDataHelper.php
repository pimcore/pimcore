<?php

namespace Pimcore\Tests\Helper\DataType;

use Codeception\Module;
use Codeception\Util\Debug;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Object;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Property;
use Pimcore\Model\User;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Tool\Authentication;

class TestDataHelper extends Module
{
    const IMAGE = "sampleimage.jpg";
    const DOCUMENT = "sampledocument.txt";
    const HOTSPOT_IMAGE = "hotspot.jpg";

    /**
     * @param Concrete    $object
     * @param string      $field
     * @param int         $seed
     * @param string|null $language
     */
    public function fillInput(Concrete $object, $field, $seed = 1, $language = null)
    {
        Debug::debug(sprintf('WRITE FIELD (%d): %s, SEED: %d, LANGUAGE: %s', $object->getId(), $field, $seed, $language));

        $setter = "set" . ucfirst($field);
        if ($language) {
            Debug::debug(sprintf('WRITE CONTENT (%d): %s', $object->getId(), $language . "content" . $seed));

            $object->$setter($language . "content" . $seed, $language);
        } else {
            Debug::debug(sprintf('WRITE CONTENT (%d): %s', $object->getId(), "content" . $seed));

            $object->$setter("content" . $seed);
        }
    }

    /**
     * @param Concrete    $object
     * @param string      $field
     * @param int         $seed
     * @param string|null $language
     */
    public function assertInput(Concrete $object, $field, $seed = 1, $language = null)
    {
        Debug::debug(sprintf('READ FIELD (%d): %s, SEED: %d, LANGUAGE: %s', $object->getId(), $field, $seed, $language));

        $getter = "get" . ucfirst($field);
        if ($language) {
            $value = $object->$getter($language);
        } else {
            $value = $object->$getter();
        }

        Debug::debug(sprintf('READ VALUE (%d): %s', $object->getId(), $value));

        $expected = $language . "content" . $seed;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillNumber(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(123 + $seed);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertNumber(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = "123" + $seed;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillTextarea(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("sometext<br>" . $seed);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertTextarea(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = "sometext<br>" . $seed;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillHref(Concrete $object, $field, $seed = 1)
    {
        $setter  = "set" . ucfirst($field);
        $objects = $this->getObjectList();
        $object->$setter($objects[0]);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertHref(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $objects  = $this->getObjectList();
        $expected = $objects[0];

        $this->assertNotNull($value);
        $this->assertInstanceOf(AbstractObject::class, $value);
        $this->assertObjectsEqual($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillMultihref(Concrete $object, $field, $seed = 1)
    {
        $setter  = "set" . ucfirst($field);
        $objects = $this->getObjectList();
        $objects = array_slice($objects, 0, 4);

        $object->$setter($objects);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertMultihref(Concrete $object, $field, $seed = 1)
    {
        $getter        = "get" . ucfirst($field);
        $value         = $object->$getter();
        $objects       = $this->getObjectList();
        $expectedArray = array_slice($objects, 0, 4);

        $this->assertCount(count($expectedArray), $value);

        for ($i = 0; $i < count($expectedArray); $i++) {
            $this->assertNotNull($value[$i]);
            $this->assertInstanceOf(AbstractObject::class, $value[$i]);
            $this->assertObjectsEqual($expectedArray[$i], $value[$i]);
        }
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillSlider(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(7 + ($seed % 3));
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertSlider(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = 7 + ($seed % 3);

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillImage(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $asset = Asset::getByPath("/" . static::IMAGE);
        if (!$asset) {
            $asset = TestHelper::createImageAsset("", null, false);
            $asset->setFilename(static::IMAGE);
            $asset->save();
        }

        $object->$setter($asset);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertImage(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = Asset::getByPath("/" . static::IMAGE);

        foreach ([$value, $expected] as $item) {
            $this->assertNotNull($item);
            $this->assertInstanceOf(Asset::class, $item);
        }

        $this->assertAssetsEqual($expected, $value);
    }

    /**
     * @return array
     */
    private function createHotspots()
    {
        $result = [];

        $hotspot1         = new \stdClass();
        $hotspot1->name   = "hotspot1";
        $hotspot1->width  = "10";
        $hotspot1->height = "20";
        $hotspot1->top    = "30";
        $hotspot1->left   = "40";
        $result[]        = $hotspot1;

        $hotspot2 = clone $hotspot1;
        $hotspot2->width  = "10";
        $hotspot2->height = "50";
        $hotspot2->top    = "20";
        $hotspot2->left   = "40";
        $result[]        = $hotspot2;

        return $result;
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillHotspotImage(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $asset = Asset::getByPath("/" . static::HOTSPOT_IMAGE);
        if (!$asset) {
            $asset = TestHelper::createImageAsset("", null, false);
            $asset->setFilename(static::HOTSPOT_IMAGE);
            $asset->save();
        }

        $hotspots     = $this->createHotspots();
        $hotspotImage = new Object\Data\Hotspotimage($asset, $hotspots);
        $object->$setter($hotspotImage);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertHotspotImage(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);

        /** @var Object\Data\Hotspotimage $value */
        $value    = $object->$getter();
        $hotspots = $value->getHotspots();

        $this->assertCount(2, $hotspots);
        $this->assertInstanceOf(Object\Data\Hotspotimage::class, $value);

        $asset    = Asset::getByPath("/" . static::HOTSPOT_IMAGE);
        $hotspots = $this->createHotspots();
        $expected = new Object\Data\Hotspotimage($asset, $hotspots);

        $this->assertAssetsEqual($expected->getImage(), $value->getImage());
        $this->assertEquals($expected->getHotspots(), $value->getHotspots());
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillLanguage(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("de");
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertLanguage(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = "de";

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillCountry(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("AU");
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertCountry(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = "AU";

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillDate(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $date   = new \DateTime();
        $date->setDate(2000, 12, 24);

        $object->$setter($date);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertDate(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        /** @var \DateTime $value */
        $value  = $object->$getter();

        $expected = new \DateTime();
        $expected->setDate(2000, 12, 24);

        $this->assertEquals(
            $expected->format("Y-m-d"),
            $value->format("Y-m-d")
        );
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillSelect(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(1 + ($seed % 2));
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertSelect(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = 1 + ($seed % 2);

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillMultiSelect(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(["1", "2"]);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertMultiSelect(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = ["1", "2"];

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillUser(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $username = "unittestdatauser" . $seed;
        $user     = User::getByName($username);

        if (!$user) {
            /** @var User $user */
            $user = User::create([
                "parentId" => 0,
                "username" => $username,
                "password" => Authentication::getPasswordHash($username, $username),
                "active"   => true
            ]);

            $user->setAdmin(true);
            $user->save();
        }

        $object->$setter($user->getId());
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertUser(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $user     = User::getByName("unittestdatauser" . $seed);
        $expected = $user->getId();

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillCheckbox(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(($seed % 2) == true);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertCheckbox(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = ($seed % 2) == true;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillTime(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("06:4" . $seed % 10);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertTime(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = "06:4" . $seed % 10;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillWysiwyg(Concrete $object, $field, $seed = 1)
    {
        $this->fillTextarea($object, $field, $seed);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertWysiwyg(Concrete $object, $field, $seed = 1)
    {
        return $this->assertTextarea($object, $field, $seed);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillPassword(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("sEcret$%!" . $seed);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertPassword(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value  = $object->$getter();

        // it is intended that no password is sent
        $this->assertNull($value, 'Password getter is expected to return null');
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillCountryMultiSelect(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(["1", "2"]);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertCountryMultiSelect(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = ["1", "2"];

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillLanguageMultiSelect(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(["1", "2"]);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertLanguageMultiSelect(Concrete $object, $field, $seed = 1)
    {
        $getter   = "get" . ucfirst($field);
        $value    = $object->$getter();
        $expected = ["1", "3"];

        $this->assertEquals($expected, $value);
    }

    /**
     * @return Object\Data\Geopoint
     */
    protected function getGeopointFixture()
    {
        $longitude = 2.2008440814678;
        $latitude  = 102.25112915039;
        $point     = new Object\Data\Geopoint($longitude, $latitude);

        return $point;
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillGeopoint(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter($this->getGeopointFixture());
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertGeopoint(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        /** @var Object\Data\Geopoint $value */
        $value  = $object->$getter();

        $this->assertNotNull($value);
        $this->assertInstanceOf(Object\Data\Geopoint::class, $value);

        $expected = $this->getGeopointFixture();

        $this->assertEquals($expected->__toString(), $value->__toString(), 'String representations are equal');
        $this->assertEquals($expected, $value, 'Objects are equal');
    }

    /**
     * @return Object\Data\Geobounds
     */
    protected function getGeoboundsFixture()
    {
        return new Object\Data\Geobounds(
            new Object\Data\Geopoint(150.96588134765625, -33.704920213014425),
            new Object\Data\Geopoint(150.60333251953125, -33.893217379440884)
        );
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillGeobounds(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter($this->getGeoboundsFixture());
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param Concrete $comparisonObject
     * @param int      $seed
     */
    public function assertGeobounds(Concrete $object, $field, Concrete $comparisonObject = null, $seed = 1)
    {
        $getter = 'get' . ucfirst($field);

        /** @var Object\Data\Geobounds $value */
        $value = $object->$getter();

        $this->assertNotNull($value);
        $this->assertInstanceOf(Object\Data\Geobounds::class, $value);

        $expected = $this->getGeoboundsFixture();

        $this->assertEquals($expected->__toString(), $value->__toString(), 'String representations are equal');
        $this->assertEquals($expected, $value, 'Objects are equal');

        // comparison object is only set on REST tests
        if (null === $comparisonObject) {
            return;
        }

        $fd           = $object->getClass()->getFieldDefinition($field);
        $valueData    = TestHelper::getComparisonDataForField($field, $fd, $object);
        $expectedData = TestHelper::getComparisonDataForField($field, $fd, $comparisonObject);

        $this->assertEquals($expectedData, $valueData);
    }

    /**
     * @return Object\Data\Geopoint[]
     */
    protected function getGeopolygonFixture()
    {
        return [
            new Object\Data\Geopoint(150.54428100585938, -33.464671118242684),
            new Object\Data\Geopoint(150.73654174804688, -33.913733814316245),
            new Object\Data\Geopoint(151.2542724609375, -33.9946115848146)
        ];
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillGeopolygon(Concrete $object, $field, $seed = 1)
    {
        $setter  = "set" . ucfirst($field);
        $object->$setter($this->getGeopolygonFixture());
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param          $comparisonObject
     * @param int      $seed
     */
    public function assertGeopolygon(Concrete $object, $field, Concrete $comparisonObject = null, $seed = 1)
    {
        $getter = 'get' . ucfirst($field);

        /** @var Object\Data\Geopoint[] $value */
        $value = $object->$getter();

        $expected = $this->getGeopolygonFixture();

        $this->assertTrue(is_array($value));
        $this->assertCount(count($expected), $value);
        $this->assertEquals($expected, $value);

        foreach ($value as $i => $point) {
            $expectedPoint = $expected[$i];

            $this->assertNotNull($point);
            $this->assertInstanceOf(Object\Data\Geopoint::class, $point);
            $this->assertEquals($expectedPoint->__toString(), $point->__toString(), 'String representations are equal');
            $this->assertEquals($expectedPoint, $point, 'Objects are equal');
        }

        // comparison object is only set on REST tests
        if (null === $comparisonObject) {
            return;
        }

        $fd           = $object->getClass()->getFieldDefinition($field);
        $valueData    = TestHelper::getComparisonDataForField($field, $fd, $object);
        $expectedData = TestHelper::getComparisonDataForField($field, $fd, $comparisonObject);

        $this->assertEquals($expectedData, $valueData);
    }

    /**
     * @param $seed
     *
     * @return array
     */
    protected function getTableDataFixture($seed)
    {
        return [["eins", "zwei", "drei"], [$seed, 2, 3], ["a", "b", "c"]];
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillTable(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter($this->getTableDataFixture($seed));
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param Concrete $comparisonObject
     * @param int      $seed
     */
    public function assertTable(Concrete $object, $field, Concrete $comparisonObject = null, $seed = 1)
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();

        $expected = $this->getTableDataFixture($seed);

        $this->assertEquals($expected, $value);

        // comparison object is only set on REST tests
        if (null === $comparisonObject) {
            return;
        }

        $fd           = $object->getClass()->getFieldDefinition($field);
        $valueData    = TestHelper::getComparisonDataForField($field, $fd, $object);
        $expectedData = TestHelper::getComparisonDataForField($field, $fd, $comparisonObject);

        $this->assertEquals($expectedData, $valueData);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillLink(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $doc = Document::getByPath("/" . static::DOCUMENT . $seed);

        if (!$doc) {
            $doc = TestHelper::createEmptyDocumentPage(null, false);
            $doc->setProperties($this->createRandomProperties());
            $doc->setKey(static::DOCUMENT . $seed);
            $doc->save();
        }

        $link = new Object\Data\Link();
        $link->setPath($doc);

        $object->$setter($link);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertLink(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        /** @var Object\Data\Link $link */
        $link = $object->$getter();

        $this->assertNotNull($link);
        $this->assertInstanceOf(Object\Data\Link::class, $link);

        $document = Document::getByPath($link->getObject());
        $expected = Document::getByPath("/" . static::DOCUMENT . $seed);

        foreach (['expected' => $expected, 'value' => $document] as $desc => $item) {
            $this->assertNotNull($item, $desc . ' is not null');
            $this->assertInstanceOf(Document::class, $item, $desc . ' is a Document');
        }

        $this->assertDocumentsEqual($expected, $document);
    }

    /**
     * @param int $seed
     * @return Object\Data\StructuredTable
     */
    private function getStructuredTableData($seed = 1)
    {
        $data['row1']['col1'] = 1 + $seed;
        $data['row2']['col1'] = 2 + $seed;
        $data['row3']['col1'] = 3 + $seed;

        $data['row1']['col2'] = "text_a_" . $seed;
        $data['row2']['col2'] = "text_b_" . $seed;
        $data['row3']['col2'] = "text_c_" . $seed;

        $st = new Object\Data\StructuredTable();
        $st->setData($data);

        return $st;
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillStructuredtable(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter($this->getStructuredTableData($seed));
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param Concrete $comparisonObject
     * @param int      $seed
     */
    public function assertStructuredTable(Concrete $object, $field, Concrete $comparisonObject = null, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        /** @var Object\Data\StructuredTable $value */
        $value  = $object->$getter();

        $this->assertNotNull($value);
        $this->assertInstanceOf(Object\Data\StructuredTable::class, $value);

        $expected = $this->getStructuredTableData($seed);

        $this->assertEquals($expected, $value);
        $this->assertEquals($expected->getData(), $value->getData());

        // comparison object is only set on REST tests
        if (null === $comparisonObject) {
            return;
        }

        $fd           = $object->getClass()->getFieldDefinition($field);
        $valueData    = TestHelper::getComparisonDataForField($field, $fd, $object);
        $expectedData = TestHelper::getComparisonDataForField($field, $fd, $comparisonObject);

        $this->assertEquals($expectedData, $valueData);
    }

    /**
     * @param Concrete    $object
     * @param string      $field
     * @param int         $seed
     * @param string|null $language
     */
    public function fillObjects(Concrete $object, $field, $seed = 1, $language = null)
    {
        $setter  = "set" . ucfirst($field);
        $objects = $this->getObjectList("o_type = 'object'");

        if ($language) {
            if ($language == "de") {
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

    /**
     * @param Concrete      $object
     * @param string        $field
     * @param Concrete|null $comparisonObject
     * @param int           $seed
     * @param string|null   $language
     */
    public function assertObjects(Concrete $object, $field, $seed = 1, $language = null)
    {
        $getter = "get" . ucfirst($field);

        $objects = $this->getObjectList("o_type = 'object'");
        if ($language) {
            if ($language === "de") {
                $expectedArray = array_slice($objects, 0, 6);
            } else {
                $expectedArray = array_slice($objects, 0, 5);
            }
            $value = $object->$getter($language);
        } else {
            $expectedArray = array_slice($objects, 0, 4);
            $value         = $object->$getter();
        }

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
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillObjectsWithMetadata(Concrete $object, $field, $seed = 1)
    {
        $setter  = "set" . ucfirst($field);
        $objects = $this->getObjectList("o_type = 'object' AND o_className = 'unittest'");
        $objects = array_slice($objects, 0, 4);

        $metaobjects = [];
        foreach ($objects as $o) {
            $mo = new Object\Data\ObjectMetadata($field, ["meta1", "meta2"], $o);
            $mo->setMeta1("value1" . $seed);
            $mo->setMeta2("value2" . $seed);
            $metaobjects[] = $mo;
        }

        $object->$setter($metaobjects);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param Concrete $comparisonObject
     * @param int      $seed
     */
    public function assertObjectsWithMetadata(Concrete $object, $field, Concrete $comparisonObject, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value  = $object->$getter();

        $fd            = $object->getClass()->getFieldDefinition($field);
        $valueForField = TestHelper::getComparisonDataForField($field, $fd, $object);
        $expected      = TestHelper::getComparisonDataForField($field, $fd, $comparisonObject);

        $this->assertEquals($expected, $valueForField);

        $rel1 = $value[0];
        $meta = $rel1->getMeta1();

        $this->assertEquals("value1" . $seed, $meta, "sample value does not match");
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillBricks(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        $brick = new Object\Objectbrick\Data\UnittestBrick($object);
        $brick->setBrickInput("brickinput" . $seed);

        /** @var Object\Unittest\Mybricks $objectbricks */
        $objectbricks = $object->$getter();
        $objectbricks->setUnittestBrick($brick);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertBricks(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        $value = $object->$getter();

        /** @var Object\Unittest\Mybricks $value */
        $value = $value->getUnittestBrick();

        /** @var Object\Objectbrick\Data\UnittestBrick $value */
        $value = $value->getBrickinput();

        $expected = "brickinput" . $seed;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function fillFieldCollection(Concrete $object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $fc = new Object\Fieldcollection\Data\Unittestfieldcollection();
        $fc->setFieldinput1("field1" . $seed);
        $fc->setFieldinput2("field2" . $seed);

        $items = new Object\Fieldcollection([$fc], $field);
        $object->$setter($items);
    }

    /**
     * @param Concrete $object
     * @param string   $field
     * @param int      $seed
     */
    public function assertFieldCollection(Concrete $object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        /** @var Object\Fieldcollection $value */
        $value = $object->$getter();

        $this->assertEquals(1, $value->getCount(), 'expected 1 item');

        $value = $value->getItems();

        /** @var Object\Fieldcollection\Data\Unittestfieldcollection $value */
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
    }

    public function assertElementsEqual(ElementInterface $e1, ElementInterface $e2)
    {
        $this->assertEquals(get_class($e1), get_class($e2));
        $this->assertEquals($e1->getId(), $e2->getId());
        $this->assertEquals($e1->getType(), $e2->getType());
        $this->assertEquals($e1->getFullPath(), $e2->getFullPath());
    }

    public function assertDocumentsEqual(Document $doc1, Document $doc2)
    {
        $this->assertElementsEqual($doc1, $doc2);

        $str1 = TestHelper::createDocumentComparisonString($doc1);
        $str2 = TestHelper::createDocumentComparisonString($doc2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    public function assertAssetsEqual(Asset $asset1, Asset $asset2)
    {
        $this->assertElementsEqual($asset1, $asset2);

        $str1 = TestHelper::createAssetComparisonString($asset1);
        $str2 = TestHelper::createAssetComparisonString($asset2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    public function assertObjectsEqual(AbstractObject $obj1, AbstractObject $obj2)
    {
        $this->assertElementsEqual($obj1, $obj2);

        $str1 = TestHelper::createObjectComparisonString($obj1);
        $str2 = TestHelper::createObjectComparisonString($obj2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    /**
     * @param ElementInterface[] $elements
     * @return array
     */
    private function getElementPaths(array $elements = [])
    {
        $paths = [];
        foreach ($elements as $element) {
            if (!($element instanceof ElementInterface)) {
                throw new \InvalidArgumentException(sprintf('Invalid element. Must be an instance of %s', ElementInterface::class));
            }

            $paths[] = $element->getRealFullPath();
        }

        return $paths;
    }

    /**
     * @return Property[]
     */
    private function createRandomProperties()
    {
        $properties = [];

        // object property
        $property = new Property();
        $property->setType("object");
        $property->setName("object");
        $property->setInheritable(true);

        $properties[] = $property;

        return $properties;
    }

    /**
     * @param null $condition
     *
     * @return Concrete[]
     */
    private function getObjectList($condition = null)
    {
        $list = new Object\Listing();
        $list->setOrderKey("o_id");
        $list->setCondition($condition);

        $objects = $list->load();

        return $objects;
    }
}
