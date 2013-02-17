<?php 

class Object_Data_KeyValue extends Pimcore_Model_Abstract {


    /**
     * @var Object_Class
     */
    public $class;

    /**
     * @var int
     */
    public $objectId;

    /**
     * @var array
     */
    public $arr = array();

    public function __construct() {
    }

    /**
     * @param Object_Class $class
     * @return void
     */
    public function setClass(Object_Class $class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return Object_Class
     */
    public function getClass()
    {
        return $this->class;
    }

    public function __toString() {
        $str = "Object_Data_KeyValue oid=" . $this->objectId . "\n";
        $props = $this->getInternalProperties();

        if (is_array($props)) {
            foreach($props as $prop) {
                $str .= "    " . $prop["key"] . "=>" . $prop["value"] . "\n";
            }
        }
        return $str;
    }

    public function getObjectId() {
        return $this->objectId;
    }

    public function setObjectId($objectId) {
        $this->objectId = $objectId;
        return $this;
    }

    public function setProperties($arr) {
        $newProperties = array();
        foreach ($arr as $key => $pair) {

            if (!$pair["inherited"]) {
                $newProperties[$key] = $pair;
            }
        }

        $this->arr = $newProperties;
        return $this;
    }

    public function getInternalProperties() {
        return $this->arr;
    }

    public function getProperties() {
        $result = array();
        $object = Object_Abstract::getById($this->objectId);
        $objectName = $object->getKey();

        foreach ($this->arr as $pair) {
            $pair["inherited"] = false;
            $pair["source"] = $object->getId();
            $result[] = $pair;
        }

        $parent = Object_Service::hasInheritableParentObject($object);
        while ($parent) {
            $kv = $parent->getKeyvaluepairs();
            $parentProperties = $kv->getInternalProperties();


            foreach ($parentProperties as $parentPair) {
                $keyId = $parentPair["key"];
                $add = true;

                for ($i = 0; $i < count($result); ++$i) {
                    $resultPair = $result[$i];

                    $resultKey = $resultPair["key"];

                    $existingPair = null;
                    if ($resultKey == $keyId) {
                        $add = false;
                        if (empty($resultPair["altSource"])) {
                             $resultPair["altSource"] = $parent->getId();
                            $resultPair["altValue"] = $parentPair["value"];
                        }

                        $result[$i] = $resultPair;
                        break;
                    }
                }


                if ($add) {
                    $parentPair["inherited"] = true;
                    $parentPair["source"] = $parent->getId();
                    $parentPair["altSource"] = $parent->getId();
                    $parentPair["altValue"] = $parentPair["value"];
                    $result[] = $parentPair;
                } else {

                }
            }

            $parent = Object_Service::hasInheritableParentObject($parent);
        }

        return $result;
    }


    function getKeyId($propName) {
        $keyConfig = Object_KeyValue_KeyConfig::getByName($propName);

        if (!$keyConfig) {
            throw new Exception("key does not exist");
        }
        $keyId =  $keyConfig->getId();
        return $keyId;
    }


    /** Throws an exception if property is not globally defined. Null, if it is not set
     * for this object.
     * @param $propName
     */
    public function getProperty($propName) {
        $keyId =  $this->getKeyId($propName);

        // the key name is valid, now iterate over the object's pairs
        $propsWithInheritance = $this->getProperties();
        foreach ($propsWithInheritance as $pair) {
            if ($pair["key"] == $keyId) {
                return ($pair["value"]);
            }
        }
    }

    public function setPropertyWithId($keyId, $value) {
        // the key name is valid, now iterate over the object's pairs
        for ($i = 0; $i < count($this->arr); $i++) {
            $pair = $this->arr[$i];
            if ($pair["key"] == $keyId) {
                $pair["value"] = $value;
                $this->arr[$i] = $pair;
                return;
            }
        }

        $pair = array();
        $pair["key"] = $keyId;
        $pair["value"] = $value;
        $this->arr[] = $pair;
        return $this;
    }

    public function setProperty($propName, $value) {
        $keyId =  $this->getKeyId($propName);
        $this->setPropertyWithId($keyId, $value);
    }

    public function __call($name, $arguments) {
        if(substr($name, 0, 3) == "get") {
            $key = substr($name, 3, strlen($name)-3);
            return $this->getProperty($key);
        } else if(substr($name, 0, 3) == "set") {
            $key = substr($name, 3, strlen($name)-3);
            return $this->setProperty($key, $arguments[0]);
        }
        return parent::__call($name, $arguments);
    }
}
