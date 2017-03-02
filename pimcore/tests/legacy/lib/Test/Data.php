<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 05.02.13
 * Time: 11:20
 * To change this template use File | Settings | File Templates.
 */
class Test_Data
{
    const IMAGE = "sampleimage.jpg";

    const DOCUMENT = "sampledocument.txt";

    const HOTSPOT_IMAGE = "hotspot.jpg";

    private static function createRandomProperties()
    {
        $properties = [];

            // object property
        $property = new Property();
        $property->setType("object");
        $property->setName("object");
        $property->setInheritable(true);
        $properties["bla"] = $property;
    }

    private static function getObjectList($condition = null)
    {
        $list = new Object_List();
        $list->setOrderKey("o_id");
        $list->setCondition($condition);
        $objects = $list->load();

        return $objects;
    }

    public static function fillInput($object, $field, $seed = 1, $language = null)
    {
        $setter = "set" . ucfirst($field);
        if ($language) {
            $object->$setter($language . "content" . $seed, $language);
        } else {
            $object->$setter("content" . $seed);
        }
    }

    public static function assertInput($object, $field, $seed = 1, $language = null)
    {
        $getter = "get" . ucfirst($field);
        if ($language) {
            $value = $object->$getter($language);
        } else {
            $value = $object->$getter();
        }
        $expected = $language . "content" . $seed;

        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillNumber($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(123 + $seed);
    }

    public static function assertNumber($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "123" + $seed;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillTextarea($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("sometext<br>" . $seed);
    }

    public static function assertTextarea($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "sometext<br>" . $seed;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillHref($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $objects = self::getObjectList();
        $object->$setter($objects[0]);
    }

    public static function assertHref($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $objects = self::getObjectList();
        $expected = $objects[0];

        if ($value != $expected) {
            print("   expected " . $expected->getId() . " but was " . $value->getId());

            return false;
        }

        return true;
    }


    public static function fillMultihref($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $objects = self::getObjectList();
        $objects = array_slice($objects, 0, 4);

        $object->$setter($objects);
    }

    public static function assertMultihref($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $objects = self::getObjectList();
        $expectedArray = array_slice($objects, 0, 4);

        if (count($expectedArray) != count($value)) {
            print("count is different  " . count($expectedArray) . " != " . count($value) . "\n");

            return false;
        }

        for ($i = 0; $i < count($expectedArray); $i++) {
            if ($value[$i] != $expectedArray[$i]) {
                print("   expected " . $expectedArray[$i]->getId() . " but was " . $value[$i]->getId());

                return false;
            }
        }

        return true;
    }




    public static function fillSlider($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(7 + ($seed % 3));
    }

    public static function assertSlider($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = 7 + ($seed % 3);
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillImage($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $asset = Asset::getByPath("/". self::IMAGE);
        if (!$asset) {
            $asset = Test_Tool::createImageAsset("", null, false);
            $asset->setFilename(self::IMAGE);
            $asset->save();
        }

        $object->$setter($asset);
    }

    public static function assertImage($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = Asset::getByPath("/" . self::IMAGE);
        if ($expected != $value) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    private static function createHotspots()
    {
        $result = [];
        $hotspot = new stdClass();
        $hotspot->name = "hotspot1";
        $hotspot->width = "10";
        $hotspot->height = "20";
        $hotspot->top  = "30";
        $hotspot->left = "40";
        $result[] = $hotspot;
        $hotspot->width = "10";
        $hotspot->height = "50";
        $hotspot->top  = "20";
        $hotspot->left = "40";
        $result[] = $hotspot;

        return $result;
    }

    public static function fillHotspotImage($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $asset = Asset::getByPath("/". self::HOTSPOT_IMAGE);
        if (!$asset) {
            $asset = Test_Tool::createImageAsset("", null, false);
            $asset->setFilename(self::HOTSPOT_IMAGE);
            $asset->save();
        }

        $hotspots = self::createHotspots();
        $hotspotImage = new Object_Data_Hotspotimage($asset, $hotspots);
        $object->$setter($hotspotImage);
    }

    public static function assertHotspotImage($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $hotspots = $value->getHotspots();
        if (count($hotspots)  != 2) {
            print("hotspot count is " . count($hotspots));

            return false;
        }
        $asset = Asset::getByPath("/" . self::HOTSPOT_IMAGE);
        $hotspots = self::createHotspots();
        $expected = new Object_Data_Hotspotimage($asset, $hotspots);

        $value = Test_Tool::createAssetComparisonString($value->getImage());
        $expected = Test_Tool::createAssetComparisonString($expected->getImage());

        if ($expected != $value) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillLanguage($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("de");
    }

    public static function assertLanguage($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "de";
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillCountry($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("AU");
    }

    public static function assertCountry($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "AU";
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillDate($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $date = new \DateTime();
        $date->setDate(2000, 12, 24);

        $object->$setter($date);
    }

    public static function assertDate($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();

        $expected = new \DateTime();
        $expected->setDate(2000, 12, 24);

        if ($value->format("Y-m-d") != $expected->format("Y-m-d")) {
            print("   expected " . $expected->format("Y-m-d") . " but was " . $value->format("Y-m-d"));

            return false;
        }

        return true;
    }

    public static function fillSelect($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(1 + ($seed % 2));
    }

    public static function assertSelect($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = 1 + ($seed % 2);
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillMultiSelect($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(["1", "2"]);
    }

    public static function assertMultiSelect($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = ["1", "2"];
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillUser($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $username = "unittestdatauser" . $seed;
        $user = User::getByName($username);

        if (!$user) {
            $user = User::create([
                "parentId" => 0,
                "username" => $username,
                "password" => Pimcore_Tool_Authentication::getPasswordHash($username, $username),
                "active" => true
            ]);
            $user->setAdmin(true);
            $user->save();
        }

        $object->$setter($user->getId());
    }

    public static function assertUser($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $user = User::getByName("unittestdatauser" . $seed);
        $expected = $user->getId();
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillCheckbox($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(($seed % 2) == true);
    }

    public static function assertCheckbox($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = ($seed % 2) == true;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillTime($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("06:4" . $seed % 10);
    }

    public static function assertTime($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "06:4" . $seed % 10;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillWysiwyg($object, $field, $seed = 1)
    {
        self::fillTextarea($object, $field, $seed);
    }

    public static function assertWysiwyg($object, $field, $seed = 1)
    {
        return self::assertTextarea($object, $field, $seed);
    }

    public static function fillPassword($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter("sEcret$%!" . $seed);
    }

    public static function assertPassword($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        // it is intended that no password is sent
        $expected = null;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value . "\n");

            return false;
        }

        return true;
    }

    public static function fillCountryMultiSelect($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(["1", "2"]);
    }

    public static function assertCountryMultiSelect($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = ["1", "2"];
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillLanguageMultiSelect($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $object->$setter(["1", "2"]);
    }

    public static function assertLanguageMultiSelect($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = ["1", "3"];
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillGeopoint($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $longitude = 2.2008440814678;
        $latitude = 102.25112915039;
        $point = new Object_Data_Geopoint($longitude, $latitude);
        $object->$setter($point);
    }

    public static function assertGeopoint($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();

        $longitude = 2.2008440814678;
        $latitude = 102.25112915039;
        $expected = new Object_Data_Geopoint($longitude, $latitude);

        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillGeobounds($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $bounds = new Object_Data_Geobounds(new Object_Data_Geopoint(150.96588134765625, -33.704920213014425), new Object_Data_Geopoint(150.60333251953125, -33.893217379440884));
        $object->$setter($bounds);
    }

    public static function assertGeobounds($object, $field, $comparisonObject, $seed = 1)
    {
        $fd = $object->getClass()->getFieldDefinition($field);
        $value = Test_Tool::getComparisonDataForField($field, $fd, $object);
        $expected = Test_Tool::getComparisonDataForField($field, $fd, $comparisonObject);


        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillGeopolygon($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $polygon  = [new Object_Data_Geopoint(150.54428100585938, -33.464671118242684), new Object_Data_Geopoint(150.73654174804688, -33.913733814316245), new Object_Data_Geopoint(151.2542724609375, -33.9946115848146)];
        $object->$setter($polygon);
    }

    public static function assertGeopolygon($object, $field, $comparisonObject, $seed = 1)
    {
        $fd = $object->getClass()->getFieldDefinition($field);
        $value = Test_Tool::getComparisonDataForField($field, $fd, $object);
        $expected = Test_Tool::getComparisonDataForField($field, $fd, $comparisonObject);

        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillTable($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $tabledata  = [["eins", "zwei", "drei"], [$seed, 2, 3], ["a", "b", "c"]];
        $object->$setter($tabledata);
    }

    public static function assertTable($object, $field, $comparisonObject, $seed = 1)
    {
        $fd = $object->getClass()->getFieldDefinition($field);
        $value = Test_Tool::getComparisonDataForField($field, $fd, $object);
        $expected = Test_Tool::getComparisonDataForField($field, $fd, $comparisonObject);

        if ($value != $expected) {
            $getter = "get" . ucfirst($field);
            print("   expected:\n" . print_r($object->$getter(), true) . " \n\nbut was:\n" . print_r($comparisonObject->$getter(), true) . "\n\n\n");

            return false;
        }

        return true;
    }


    public static function fillLink($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);


        $doc = Document::getByPath("/" . self::DOCUMENT . $seed);

        if (!$doc) {
            $doc = Test_Tool::createEmptyDocumentPage(null, false);
            $doc->setProperties(self::createRandomProperties());
            $doc->setKey(self::DOCUMENT . $seed);
            $doc->setParentId(1);
            $doc->save();
        }

        $object->$setter("content" . $seed);
    }

    public static function assertLink($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();

        $expected = Document::getByPath("/" . self::DOCUMENT . $seed);

        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    private static function getStructuredTableData($seed = 1)
    {
        $data['row1']['col1'] = 1 + $seed;
        $data['row2']['col1'] = 2 + $seed;
        $data['row3']['col1'] = 3 + $seed;

        $data['row1']['col2'] = "text_a_" . $seed;
        $data['row2']['col2'] = "text_b_" . $seed;
        $data['row3']['col2'] = "text_c_" . $seed;
    }




    public static function fillStructuredtable($object, $field, $comparisonObject, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $data = new Object_Data_StructuredTable();
        $tabledata = self::getStructuredTableData($seed);
        $data->setData($tabledata);
        $object->$setter($data);
    }

    public static function assertStructuredTable($object, $field, $comparisonObject, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();

        $fd = $object->getClass()->getFieldDefinition($field);
        $value = Test_Tool::getComparisonDataForField($field, $fd, $object);
        $expected = Test_Tool::getComparisonDataForField($field, $fd, $comparisonObject);

        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }

    public static function fillObjects($object, $field, $seed = 1, $language = null)
    {
        $setter = "set" . ucfirst($field);
        $objects = self::getObjectList("o_type = 'object'");
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

    public static function assertObjects($object, $field, $comparisonObject = null, $seed = 1, $language = null)
    {
        $getter = "get" . ucfirst($field);

        $objects = self::getObjectList("o_type = 'object'");
        if ($language) {
            if ($language == "de") {
                $expectedArray = array_slice($objects, 0, 6);
            } else {
                $expectedArray = array_slice($objects, 0, 5);
            }
            $value = $object->$getter($language);
        } else {
            $expectedArray = array_slice($objects, 0, 4);
            $value = $object->$getter();
        }

        if (count($expectedArray) != count($value)) {
            print("count is different  " . count($expectedArray) . " != " . count($value) . "\n");

            return false;
        }

        for ($i = 0; $i < count($expectedArray); $i++) {
            if ($value[$i] != $expectedArray[$i]) {
                print("   expected " . $expectedArray[$i]->getId() . " but was " . $value[$i]->getId());

                return false;
            }
        }

        return true;
    }

    public static function fillObjectsWithMetadata($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        $objects = self::getObjectList("o_type = 'object' AND o_className = 'unittest'");
        $objects = array_slice($objects, 0, 4);

        $metaobjects = [];
        foreach ($objects as $o) {
            $mo = new Object_Data_ObjectMetadata($field, ["meta1", "meta2"], $o);
            $mo->setMeta1("value1" . $seed);
            $mo->setMeta2("value2" . $seed);
            $metaobjects[] = $mo;
        }

        $object->$setter($metaobjects);
    }

    public static function assertObjectsWithMetadata($object, $field, $comparisonObject, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();

        $fd = $object->getClass()->getFieldDefinition($field);
        $valueForField = Test_Tool::getComparisonDataForField($field, $fd, $object);
        $expected = Test_Tool::getComparisonDataForField($field, $fd, $comparisonObject);

        if ($valueForField != $expected) {
            print("   expected " . $expected . " but was " . $valueForField);

            return false;
        }

        $rel1 = $value[0];
        $meta = $rel1->getMeta1();
        if ($meta != ("value1".$seed)) {
            print("sample value does not match");

            return false;
        }

        return true;
    }

    private static function setupKeyValueConig()
    {
        if (!Object_KeyValue_KeyConfig::getByName("unittest_key1")) {
            $config = new Object_KeyValue_KeyConfig();
            $config->setName("unittest_key1");
            $config->setType("text");
            $config->save();
        }

        if (!Object_KeyValue_KeyConfig::getByName("unittest_key2")) {
            $config = new Object_KeyValue_KeyConfig();
            $config->setName("unittest_key2");
            $config->setType("select");

            $options = [
                ["key" => "option1", "value" => "1"],
                ["key" => "option2", "value" => "2"]
            ];
            $config->setPossibleValues(json_encode($options));

            $config->save();
        }
    }

    private static function createPairs($seed = 1)
    {
        $keyConfig1 = Object_KeyValue_KeyConfig::getByName("unittest_key1");
        $keyConfig2 = Object_KeyValue_KeyConfig::getByName("unittest_key2");

        $pair = [];
        $pair["key"] = $keyConfig1->getId();
        $pair["value"] = "text1_" . $seed;
        $pair["metadata"] = "meta1_" . $seed;
        $pairs[] = $pair;

        $pair = [];
        $pair["key"] = $keyConfig2->getId();
        $pair["value"] = 1 + ($seed % 2);
        $pair["metadata"] = "metda" . (1 + ($seed % 2));


        $pairs[] = $pair;

        return $pairs;
    }

    public static function fillKeyValue($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);
        self::setupKeyValueConig();

        $keyvalue = new Object_Data_KeyValue();
        $pairs = self::createPairs();
        $keyvalue->setProperties($pairs);
        $keyvalue->setObjectId($object->getId());
        $object->$setter($keyvalue);
    }

    public static function assertKeyValue($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);

        $value = $object->$getter();
        $expected = self::createPairs($seed);

        $properties = $value->getProperties();

        if (count($expected) != count($properties)) {
            print("    number of properties do not match\n");

            return false;
        }

        for ($i = 0; $i < count($expected); $i++) {
            $p1 = $expected[i];
            $p2 = $properties[i];
            if ($p1["key"] != $p2["key"] || $p1["value"] != $p2["value"] || $p1["metadata"] != $p2["metadata"]) {
                print("    property does not match\n");

                return false;
            }
        }

        return true;
    }


    public static function fillBricks($object, $field, $seed = 1)
    {
        $setter = "get" . ucfirst($field);

        $brick = new Object_Objectbrick_Data_UnittestBrick($object);
        $brick->setBrickInput("brickinput" . $seed);
        $objectbricks = $object->$setter();
        $objectbricks->setUnittestBrick($brick);
    }

    public static function assertBricks($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $value = $value->getUnittestBrick();
        $value = $value->getBrickinput();

        $expected = "brickinput" . $seed;

        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);

            return false;
        }

        return true;
    }


    public static function fillFieldCollection($object, $field, $seed = 1)
    {
        $setter = "set" . ucfirst($field);

        $fc = new Object_Fieldcollection_Data_Unittestfieldcollection();
        $fc->setFieldinput1("field1" . $seed);
        $fc->setFieldinput2("field2" . $seed);

        $items = new Object_Fieldcollection([$fc], $field);
        $object->$setter($items);
    }

    public static function assertFieldCollection($object, $field, $seed = 1)
    {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();

        if ($value->getCount() != 1) {
            print("    expected 1 item");

            return false;
        }

        $value = $value->getItems();
        $value = $value[0];
        if ($value->getFieldinput1() != "field1" . $seed) {
            print("field1" . $seed . " but was " . $value->getFieldInput1());

            return false;
        }

        if ($value->getFieldInput2() != "field2" . $seed) {
            print("field2" . $seed . " but was " . $value->getFieldInput2());

            return false;
        }


        return true;
    }
}
