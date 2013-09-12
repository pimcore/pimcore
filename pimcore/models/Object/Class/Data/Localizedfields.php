<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_Localizedfields extends Object_Class_Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "localizedfields";


    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "array";

    /**
     * @var array
     */
    public $childs = array();


    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $region;

    /**
     * @var string
     */
    public $layout;

    /**
     * @var string
     */
    public $title;

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * @var array
     */
    private $fieldDefinitionsCache;


    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null)
    {
        $fieldData = array();
        $metaData = array();

        if (!$data instanceof Object_Localizedfield) {
            return array();
        }

        $result = $this->doGetDataForEditMode($data, $object, $fieldData, $metaData, 1);

        return $result;
    }

    private function doGetDataForEditMode($data, $object, &$fieldData, &$metaData, $level = 1) {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        foreach ($data->getItems() as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $key = $fd->getName();
                $fdata = $fd->getDataForEditmode($values[$fd->getName()], $object);

                if (empty($fieldData[$language][$key])) {
                    // never override existing data
                    $fieldData[$language][$key] = $fdata;
                    if (!empty($fdata)) {
                        $metaData[$language][$key] = array("inherited" => $level > 1, "objectid" => $object->getId());
                    }
                }
            }
        }


        if ($inheritanceAllowed) {
            // check if there is a parent with the same type
            $parent = Object_Service::hasInheritableParentObject($object);
            if ($parent) {
                // same type, iterate over all language and all fields and check if there is something missing
                $validLanguages = Pimcore_Tool::getValidLanguages();
                $foundEmptyValue = false;

                foreach ($validLanguages as $language) {
                    $fieldDefinitions = $this->getFieldDefinitions();
                    foreach ($fieldDefinitions as $fd) {
                        $key = $fd->getName();
                        if (empty($fieldData[$language][$key])) {
                            $foundEmptyValue = true;
                            $inherited = true;
                            $metaData[$language][$key] = array("inherited" => true, "objectid" => $parent->getId());
                        }
                    }
                }

                if ($foundEmptyValue) {
                    // still some values are passing, ask the parent
                    $parentData = $parent->getLocalizedFields();
                    $parentResult = $this->doGetDataForEditMode($parentData, $parent, $fieldData, $metaData, $level + 1);
                    Logger::debug("merge results");
                }
            }
        }

        $result = array(
            "data" => $fieldData,
            "metaData" => $metaData,
            "inherited" => $inherited
        );

        return $result;
    }



    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null)
    {
        $localizedFields = $this->getDataFromObjectParam($object);

        if(!$localizedFields instanceof Object_Localizedfield) {
            $localizedFields = new Object_Localizedfield();
        }

        if (is_array($data)) {
            foreach ($data as $language => $fields) {
                foreach ($fields as $name => $fdata) {
                    $localizedFields->setLocalizedValue($name, $this->getFielddefinition($name)->getDataFromEditmode($fdata), $language);
                }
            }
        }

        return $localizedFields;
    }

    public function getDataForGrid($data, $object = null) {
        $result = new stdClass();
        foreach ($this->getFieldDefinitions() as $fd) {
            $key = $fd->getName();
            $result->$key = $object->{"get".ucfirst($fd->getName())}();
            if(method_exists($fd, "getDataForGrid")) {
                $result->$key = $fd->getDataForGrid($result->$key);
            }
        }
        return $result;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        // this is handled directly in the template
        // /pimcore/modules/admin/views/scripts/object/preview-version.php
        return "LOCALIZED FIELDS";
    }

    /**
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        return "NOT SUPPORTED";
    }

    /**
     * @param string $importValue
     * @return null
     */
    public function getFromCsvImport($importValue)
    {
        return;
    }

    /**
     * @param Object_Abstract $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {

        $data = $object->{$this->getName()};
        $wsData = array();

        $items = null;

        if (!$data instanceof Object_Localizedfield) {
            $items = array();
        } else {
            $items = $data->getItems();
        }

        if(Zend_Registry::isRegistered("Zend_Locale")) {
            $localeBak = Zend_Registry::get("Zend_Locale");
        } else {
            $localeBak = null;
        }

        $validLanguages = Pimcore_Tool::getValidLanguages();

        foreach ($validLanguages as $language) {

            foreach ($this->getFieldDefinitions() as $fd) {
                Zend_Registry::set("Zend_Locale", new Zend_Locale($language));

                $el = new Webservice_Data_Object_Element();
                $el->name = $fd->getName();
                $el->type = $fd->getFieldType();
                $el->value = $fd->getForWebserviceExport($object);
                if ($el->value ==  null && self::$dropNullValues) {
                    continue;
                }
                $el->language = $language;
                $wsData[] = $el;
            }
        }
        if ($localeBak) {
            Zend_Registry::set("Zend_Locale", $localeBak);
        }

        return $wsData;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null)
    {
        if (is_array($value)) {

            $validLanguages = Pimcore_Tool::getValidLanguages();

            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                foreach($value as $v){
                    if (!in_array($v->language, $validLanguages)) {
                        throw new Exception("Invalid language in localized fields");
                    }
                }
            }

            $localizedFields = $object->getLocalizedFields();
            if (!$localizedFields) {
                $localizedFields = new Object_Localizedfield();
            }

            if($object instanceof Object_Concrete) {
                $localizedFields->setObject($object);
            }

            foreach ($value as $field) {
                if ($field instanceof stdClass) {
                    $field = Pimcore_Tool_Cast::castToClass("Webservice_Data_Object_Element", $field);
                }

                if ($idMapper && $idMapper->ignoreMappingFailures()){
                    if (!in_array($field->language, $validLanguages)) {
                        continue;
                    }
                }

                if(!$field instanceof Webservice_Data_Object_Element){
                    throw new Exception("Invalid import data in field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName()." ]");
                }
                $fd = $this->getFielddefinition($field->name);
                if (!$fd instanceof Object_Class_Data) {
                    if ($idMapper && $idMapper->ignoreMappingFailures()){
                        continue;
                    }
                    throw new Exception("Unknown field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName()." ] ");
                } else if ($fd->getFieldtype() != $field->type){
                    throw new Exception("Type mismatch for field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName()." ]. Should be [ ".$fd->getFieldtype()." ], but is [ ".$field->type." ] ");
                }

                $localizedFields->setLocalizedValue($field->name, $this->getFielddefinition($field->name)->getFromWebserviceImport($field->value, $object, $idMapper), $field->language);
            }

            return $localizedFields;
        } else if (!empty($value)) {
            throw new Exception("Invalid data in localized fields");
        } else return null;
    }


    /**
     * @return array
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * @param array $childs
     * @return void
     */
    public function setChilds($childs)
    {
        $this->childs = $childs;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasChilds()
    {
        if (is_array($this->childs) && count($this->childs) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $child
     * @return void
     */
    public function addChild($child)
    {
        $this->childs[] = $child;
    }

    /**
     * @param mixed $data
     * @param array $blockedKeys
     * @return void
     */
    public function setValues($data = array(), $blockedKeys = array())
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = "set" . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
        return $this;
    }


    public function save($object, $params = array())
    {
        $localizedFields = $this->getDataFromObjectParam($object);
        if ($localizedFields instanceof Object_Localizedfield) {
            $localizedFields->setObject($object);
            $localizedFields->save();
        }
    }

    public function load($object, $params = array())
    {
        $localizedFields = new Object_Localizedfield();
        $localizedFields->setObject($object);
        $localizedFields->load();

        return $localizedFields;
    }

    public function delete($object)
    {
        $localizedFields = $this->getDataFromObjectParam($object);

        if ($localizedFields instanceof Object_Localizedfield) {
            $localizedFields->setObject($object);
            $localizedFields->delete();
        }
    }

    /**
     * This method is called in Object_Class::save() and is used to create the database table for the localized data
     * @return void
     */
    public function classSaved($class)
    {
        $localizedFields = new Object_Localizedfield();
        $localizedFields->setClass($class);
        $localizedFields->createUpdateTable();
    }

    public function preGetData($object, $params = array())
    {
        if(!$object instanceof Object_Concrete) {
            throw new \Exception("Localized Fields are only valid in Objects");
        }

        if (!$object->localizedfields instanceof Object_Localizedfield) {
            $lf = new Object_Localizedfield();
            $lf->setObject($object);

            $object->localizedfields = $lf;
        }
        return $object->localizedfields;
    }

    public function getGetterCode($class)
    {

        $code = "";
        $code .= parent::getGetterCode($class);

        foreach ($this->getFieldDefinitions() as $fd) {

            /**
             * @var $fd Object_Class_Data
             */
            $code .= $fd->getGetterCodeLocalizedfields($class);

        }

        return $code;
    }

    public function getSetterCode($class)
    {

        $code = "";
        $code .= parent::getSetterCode($class);

        foreach ($this->getFieldDefinitions() as $fd) {

            /**
             * @var $fd Object_Class_Data
             */
            $code .= $fd->getSetterCodeLocalizedfields($class);
        }

        return $code;
    }

    public function getFielddefinition($name)
    {
        $fds = $this->getFieldDefinitions();
        if ($fds[$name]) {
            return $fds[$name];
        }
        return;
    }

    public function getFieldDefinitions()
    {
        if(empty($this->fieldDefinitionsCache)) {
            $this->fieldDefinitionsCache = $this->doGetFieldDefinitions();
        }

        return $this->fieldDefinitionsCache;
    }

    public function doGetFieldDefinitions($def = null, $fields = array())
    {

        if ($def === null) {
            $def = $this->getChilds();
        }

        if (is_array($def)) {
            foreach ($def as $child) {
                $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
            }
        }

        if ($def instanceof Object_Class_Layout) {
            if ($def->hasChilds()) {
                foreach ($def->getChilds() as $child) {
                    $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
                }
            }
        }

        if ($def instanceof Object_Class_Data) {
            $fields[$def->getName()] = $def;
        }

        return $fields;
    }


    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array())
    {
        $tags = is_array($tags) ? $tags : array();

        if (!$data instanceof Object_Localizedfield) {
            return $tags;
        }

        foreach ($data->getItems() as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $tags = $fd->getCacheTags($values[$fd->getName()], $ownerObject, $tags);
            }
        }

        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data)
    {
        $dependencies = array();

        if (!$data instanceof Object_Localizedfield) {
            return array();
        }

        foreach ($data->getItems() as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $dependencies = array_merge($dependencies, $fd->resolveDependencies($values[$fd->getName()]));
            }
        }

        return $dependencies;
    }

    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);
        return $this;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setRegion($region)
    {
        $this->region = $region;
        return $this;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);
        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        $data = $data->getItems();
        $conf = Pimcore_Config::getSystemConfig();
        if($conf->general->validLanguages) {
            $languages = explode(",",$conf->general->validLanguages);
        }

        if(!$omitMandatoryCheck){
            foreach ($languages as $language) {
                foreach ($this->getFieldDefinitions() as $fd) {
                    $fd->checkValidity($data[$language][$fd->getName()]);
                }
            }
        }
    }


    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @return array|null
     */
    public function getDiffDataForEditmode($data, $object = null)
    {
        $return = array();

        $myname = $this->getName();

        if (!$data instanceof Object_Localizedfield) {
            return array();
        }

        foreach ($data->getItems() as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $fieldname = $fd->getName();

                $subdata = $fd->getDiffDataForEditmode($values[$fieldname], $object);

                foreach ($subdata as $item) {
                    $diffdata["field"] = $this->getName();
                    $diffdata["key"] = $this->getName() . "~" . $fieldname . "~" . $item["key"] . "~". $language;

                    $diffdata["type"] = $item["type"];
                    $diffdata["value"] = $item["value"];

                    // this is not needed anymoe
                    unset($item["type"]);
                    unset($item["value"]);

                    $diffdata["title"] = $this->getName() . " / " . $item["title"];
                    $diffdata["lang"] = $language;
                    $diffdata["data"] = $item;
                    $diffdata["extData"] = array(
                        "fieldname" => $fieldname
                    );

                    $diffdata["disabled"] = $item["disabled"];
                    $return[] = $diffdata;
                }
            }
        }

        return $return;
    }

    /** See parent class.
     * @param $data
     * @param null $object
     * @return null|Pimcore_Date
     */

    public function getDiffDataFromEditmode($data, $object = null)
    {
        $localFields = $this->getDataFromObjectParam($object);
        $localData = array();

        // get existing data
        if($localFields instanceof Object_Localizedfield) {
            $localData = $localFields->getItems();
        }

        $mapping = array();
        foreach ($data as $item) {
            $extData = $item["extData"];
            $fieldname = $extData["fieldname"];
            $language = $item["lang"];
            $values = $mapping[$fieldname];

            $itemdata = $item["data"];

            if ($itemdata) {
                if (!$values) {
                    $values = array();
                }

                $values[] = $itemdata;
            }

            $mapping[$language][$fieldname] = $values;
        }

        foreach ($mapping as $language => $fields) {

            foreach ($fields as $key => $value) {

                $fd = $this->getFielddefinition($key);
                if ($fd & $fd->isDiffChangeAllowed()) {

                    if ($value == null) {
                        unset($localData[$language][$key]);
                    } else {
                        $localData[$language][$key] = $fd->getDiffDataFromEditmode($value);
                    }
                }
            }
        }

        $localizedFields = new Object_Localizedfield($localData);
        return $localizedFields;
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }


    public function __sleep() {
        $vars = get_object_vars($this);
        unset($vars['fieldDefinitionsCache']);
        return array_keys($vars);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     * @return Element_Interface
     */
    public function rewriteIds($object, $idMapping, $params = array()) {
        $data = $this->getDataFromObjectParam($object, $params);

        $validLanguages = Pimcore_Tool::getValidLanguages();

        foreach ($validLanguages as $language) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if(method_exists($fd, "rewriteIds")) {
                    $d = $fd->rewriteIds($data, $idMapping, array("language" => $language));
                    $data->setLocalizedValue($fd->getName(), $d, $language);
                }
            }
        }

        return $data;
    }
}
