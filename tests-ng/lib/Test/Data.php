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

    private function getObjectList() {
        $list = new Object_List();
        $list->setOrderKey("o_id");
        $objects = $list->load();
        return $objects;
    }

    public static function fillInput($object, $field, $seed = 1) {
        $setter = "set" . ucfirst($field);
        $object->$setter("content" . $seed);
    }

    public static function assertInput($object, $field, $seed = 1) {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "content" . $seed;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);
            return false;
        }
        return true;
    }

    public static function fillNumber($object, $field, $seed = 1) {
        $setter = "set" . ucfirst($field);
        $object->$setter(123 + $seed);
    }

    public static function assertNumber($object, $field, $seed = 1) {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "123" + $seed;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);
            return false;
        }
        return true;
    }

    public static function fillTextarea($object, $field, $seed = 1) {
        $setter = "set" . ucfirst($field);
        $object->$setter("sometext<br>" . $seed);
    }

    public static function assertTextarea($object, $field, $seed = 1) {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = "sometext<br>" . $seed;
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);
            return false;
        }
        return true;
    }

    public static function fillHref($object, $field, $seed = 1) {
        $setter = "set" . ucfirst($field);
        $objects = self::getObjectList();
        $object->$setter($objects[0]);
    }

    public static function assertHref($object, $field, $seed = 1) {
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


    public static function fillMultihref($object, $field, $seed = 1) {
        $setter = "set" . ucfirst($field);
        $objects = self::getObjectList();
        $objects = array_slice($objects,0,4);

        $object->$setter($objects);
    }

    public static function assertMultihref($object, $field, $seed = 1) {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $objects = self::getObjectList();
        $expectedArray = array_slice($objects,0,4);

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




    public static function fillSlider($object, $field, $seed = 1) {
        $setter = "set" . ucfirst($field);
        $object->$setter(7 + ($seed % 3));
    }

    public static function assertSlider($object, $field, $seed = 1) {
        $getter = "get" . ucfirst($field);
        $value = $object->$getter();
        $expected = 7 + ($seed % 3);
        if ($value != $expected) {
            print("   expected " . $expected . " but was " . $value);
            return false;
        }
        return true;
    }


}
