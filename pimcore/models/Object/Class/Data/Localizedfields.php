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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object 
     * @return string
     */
    public function getDataForEditmode($data, $object = null)
    {
        $return = array();

        if (!$data instanceof Object_Localizedfield) {
            return array();
        }

        foreach ($data->getItems() as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $return[$language][$fd->getName()] = $fd->getDataForEditmode($values[$fd->getName()], $object);
            }
        }

        return $return;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null)
    {
        $localFields = $object->{"get" . ucfirst($this->getName())}();
        $localData = array();

        // get existing data
        if($localFields instanceof Object_Localizedfield) {
            $localData = $localFields->getItems();
        }


        if (is_array($data)) {
            foreach ($data as $language => $fields) {
                foreach ($fields as $name => $fdata) {
                    $localData[$language][$name] = $this->getFielddefinition($name)->getDataFromEditmode($fdata);
                }
            }
        }

        $localizedFields = new Object_Localizedfield($localData);

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

        if (!$data instanceof Object_Localizedfield) {
            return array();
        }

        if(Zend_Registry::isRegistered("Zend_Locale")) {
            $localeBak = Zend_Registry::get("Zend_Locale");
        } else {
            $localeBak = null;
        }

        foreach ($data->getItems() as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                Zend_Registry::set("Zend_Locale", new Zend_Locale($language));

                $el = new Webservice_Data_Object_Element();
                $el->name = $fd->getName();
                $el->type = $fd->getFieldType();
                $el->value = $fd->getForWebserviceExport($object);
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
    public function getFromWebserviceImport($value)
    {
        if (is_array($value)) {

            $validLanguages = Pimcore_Tool::getValidLanguages();
            foreach($value as $v){
                if (!in_array($v->language, $validLanguages)) {
                    throw new Exception("Invalid language in localized fields");
                }
            }

            $data = array();
            foreach ($value as $field) {

                    if(!$field instanceof Webservice_Data_Object_Element){
                        throw new Exception("Invalid import data in field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName()." ]");
                    }
                    $fd = $this->getFielddefinition($field->name);
                    if (!$fd instanceof Object_Class_Data) {
                        throw new Exception("Unknnown field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName()." ] ");
                    } else if ($fd->getFieldtype() != $field->type){
                        throw new Exception("Type mismatch for field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName()." ]. Should be [ ".$fd->getFieldtype()." ], but is [ ".$field->type." ] ");
                    }
                    $data[$field->language][$field->name] = $this->getFielddefinition($field->name)->getFromWebserviceImport($field->value);

            }

            $localizedFields = new Object_Localizedfield($data);

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
    }


    public function save($object, $params = array())
    {
        $localizedFields = $object->{  "get" . ucfirst($this->getName()) }();
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
        $localizedFields = $object->{ "get" . ucfirst($this->getName()) }();

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

    public function preGetData($object)
    {
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

    public function getFieldDefinitions($def = null, $fields = array())
    {

        if ($def === null) {
            $def = $this->getChilds();
        }

        if (is_array($def)) {
            foreach ($def as $child) {
                $fields = array_merge($fields, $this->getFieldDefinitions($child, $fields));
            }
        }

        if ($def instanceof Object_Class_Layout) {
            if ($def->hasChilds()) {
                foreach ($def->getChilds() as $child) {
                    $fields = array_merge($fields, $this->getFieldDefinitions($child, $fields));
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
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setRegion($region)
    {
        $this->region = $region;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);
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

}
