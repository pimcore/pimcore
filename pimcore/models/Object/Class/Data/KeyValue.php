<?php 

class Object_Class_Data_KeyValue extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "keyValue";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_KeyValue";

    public $keyWidth;

    public $valueWidth;

    public $height;

    public $maxheight;

    public $groupWidth;

    /**
     * This method is called in Object_Class::save() and is used to create the database table for the localized data
     * @return void
     */
    public function classSaved($class)
    {
        // iterate over fieldDefinitions array and check if there is an item of type
        // object_Class_Data_KeyValue
        // if found, create the table, otherwise do nothing

        $keyValue = new Object_Data_KeyValue();
        $keyValue->setClass($class);
        $fieldDefinitions = $class->getFieldDefinitions();
        //TODO is this even called if type keyvalue not part of the class def?
        foreach ($fieldDefinitions as $definition) {
            if ($definition instanceof Object_Class_Data_KeyValue) {
                Logger::debug("found definition of type keyvalue, create table");
                $keyValue->createUpdateTable();
                break;
            }
        }
    }


    public function save($object, $params = array())
    {
        Logger::debug("save called");
        $pairs = $object->{  "get" . ucfirst($this->getName()) }();

        if ($pairs instanceof Object_Data_KeyValue) {
            $pairs->setClass($object->getClass());
            $pairs->setObjectId($object->getId());
            $pairs->save();
        }
    }


    /**
     * @return integer
     */
    public function getKeyWidth() {
        return $this->keyWidth;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setKeyWidth($width) {
        $this->keyWidth = $this->getAsIntegerCast($width);
        return $this;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setGroupWidth($width) {
        $this->groupWidth = $this->getAsIntegerCast($width);
        return $this;
    }


    /**
     * @return integer
     */
    public function getMaxheight() {
        return $this->maxheight;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setMaxheight($maxheight) {
        $this->maxheight = $this->getAsIntegerCast($maxheight);
        return $this;
    }


    /**
     * @return integer
     */
    public function getGroupWidth() {
        return $this->groupWidth;
    }




    /**
 * @return integer
 */
    public function getValueWidth() {
        return $this->valueWidth;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setValueWidth($width) {
        $this->valueWidth = $this->getAsIntegerCast($width);
        return $this;
    }


    public function load($object, $params = array())
    {
        $pairs = new Object_Data_KeyValue();
        $pairs->setClass($object->getClass());
        $pairs->setObjectId($object->getId());
        $pairs->load();

        return $pairs;
    }

    public function delete($object)
    {
        $pairs = $object->{ "get" . ucfirst($this->getName()) }();

        if ($pairs instanceof Object_Data_KeyValue) {
            $pairs->setClass($object->getClass());
            $pairs->setObjectId($object->getId());
            $pairs->delete();

        }
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param Object_Data_KeyValue $data
     * @param null|Object_Abstract $object
     * @return tbd
     */
    public function getDataForEditmode($data, $object = null) {
        $result = array();
        if (!$data) {
            return $result;
        }

        $properties = $data->getProperties();

        foreach ($properties as $key => $property) {

            // Logger::debug($property);
            $key = $property["key"];
            $keyConfig = Object_KeyValue_KeyConfig::getById($key);
            $property["type"] = $keyConfig->getType();
            $property["possiblevalues"] = $keyConfig->getPossibleValues();

            $niceName = $keyConfig->getDescription();
            if ($niceName == "") {
                $niceName = "~" . $keyConfig->getName() . "~";
            }

            $groupId = $keyConfig->getGroup();

            if ($groupId) {
                $group = Object_KeyValue_GroupConfig::getById($groupId);

                if (strlen($group->getDescription()) > 0) {
                    $groupName = $group->getDescription();
                } else {
                    $groupName = $group->getName();
                }

                $property["group"] = $groupName;
            }


            $property["description"] = $niceName;
            $result[] = $property;
        }
        return $result;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {

        $class = $object->getClass();
        $objectId = $object->getId();

        $pairs = array();
        foreach ($data as $pair) {
            $key = $pair["key"];
            $pairs[$key] = $pair;
        }


        $keyValue = new Object_Data_KeyValue();

        $keyValue->setObjectId($objectId);
        $keyValue->setProperties($pairs);

        return $keyValue;
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        // TODO throw exception if not valid
    }

    public function isDiffChangeAllowed() {
        return true;
    }



    public function getDiffDataFromEditmode($data, $object = null) {
        $result = array();

        // filter everything out that doesn't exist anymore
        foreach ($data as $pair) {
            if ($pair["data"]) {
                $result[] = $pair["data"];
            }
        }
        $dataFromEditMode = $this->getDataFromEditmode($result, $object);
        return $dataFromEditMode;
    }

    public function getDiffDataForEditMode($data, $object = null) {

        $properties = $data->getProperties();
        $result = array();

        foreach ($properties as $key => $property) {

            $key = $property["key"];

            $diffdata = array();
            $diffdata["field"] = $this->getName();
            $diffdata["key"] = $this->getName() . "~" . $key;
            $diffdata["type"] = $this->fieldtype;
            unset($property["id"]);
            unset($property["o_id"]);
            unset($property["source"]);
            $diffdata["data"] = $property;


            $keyConfig = Object_KeyValue_KeyConfig::getById($key);
            $niceName = $keyConfig->getDescription();
            if ($niceName == "") {
                $niceName = "~" . $keyConfig->getName() . "~";
            }

            $prettyValue = $property["value"];
            if ($keyConfig->getType() == "select") {
                $possibleValues = Zend_Json::decode($keyConfig->getPossibleValues());

                foreach($possibleValues as $pValue) {
                    if ($pValue["key"] == $property["value"]) {
                        $prettyValue = $pValue["value"];
                        break;
                    }
                }
            }
            $diffdata["value"] = $prettyValue;


            $diffdata["title"] = $niceName;
            $diffdata["disabled"] = !($this->isDiffChangeAllowed());
            $result[] = $diffdata;
        }

        return $result;
    }



    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {

        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        $data = $object->$getter();
        if ($data) {
            $result = array();
            foreach ($data->arr as $item) {
                $resultItem = array(
                    "key" => $item["key"],
                    "value" => $item["value"]
                );
                $result[] = $resultItem;
            }
            return $result;
        }
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null)
    {
        if ($value) {
            $pairs = array();
            foreach ($value as $property) {
                $property = (array) $property;

                if (key_exists("key", $property)) {
                    $pairs[] = $property;
                }
            }

            $keyValueData = new Object_Data_KeyValue();
            $keyValueData->setProperties($pairs);
            $keyValueData->setClass($object->getClass());
            $keyValueData->setObjectId($object->getId());
            return ($keyValueData);
        }
    }

}
