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

class Object_Class_Data_Wysiwyg extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "wysiwyg";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "longtext";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "longtext";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "string";

    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $this->getAsIntegerCast($width);
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        return Pimcore_Tool_Text::wysiwygText($data);
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        return Pimcore_Tool_Text::wysiwygText($data);
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {

        $data = $this->getDataForResource($data);

        $data = strip_tags($data, "<a><img>");
        $data = str_replace("\r\n", " ", $data);
        $data = str_replace("\n", " ", $data);
        $data = str_replace("\r", " ", $data);
        $data = str_replace("\t", "", $data);
        $data = preg_replace ('#[ ]+#', ' ', $data);

        return $data;
    }


    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        return $data;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        return $data;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data) {
        return Pimcore_Tool_Text::getDependenciesOfWysiwygText($data);
    }
    
    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array()) {
        return Pimcore_Tool_Text::getCacheTagsOfWysiwygText($data, $tags);
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
           throw new Exception("Empty mandatory field [ ".$this->getName()." ]");
        }
        $dependencies = Pimcore_Tool_Text::getDependenciesOfWysiwygText($data);
        if (is_array($dependencies)) {
            foreach ($dependencies as $key => $value) {
                $el = Element_Service::getElementById($value['type'], $value['id']);
                if (!$el) {              
                    throw new Exception("invalid dependency in wysiwyg text");
                }
            }
        }
    }

    /**
     * @param Object_Concrete $object
     * @return string
     */
    public function preGetData ($object) {
        $key = $this->getName();
        return Pimcore_Tool_Text::wysiwygText($object->$key);
    }

}
