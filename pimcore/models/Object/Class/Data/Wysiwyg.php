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
        return $this;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
        return $this;
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
    public function preGetData ($object, $params = array()) {

        $data = "";
        if($object instanceof Object_Concrete) {
            $data = $object->{$this->getName()};
        } else if ($object instanceof Object_Localizedfield) {
            $data = $params["data"];
        } else if ($object instanceof Object_Fieldcollection_Data_Abstract) {
            $data = $object->{$this->getName()};
        } else if ($object instanceof Object_Objectbrick_Data_Abstract) {
            $data = $object->{$this->getName()};
        }

        return Pimcore_Tool_Text::wysiwygText($data);
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null) {
        if ($data) {
            $value = array();
            $value["html"] = $data;
            $value["type"] = "html";
            return $value;
        } else {
            return "";
        }
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
        $html = str_get_html($data);
        if($html) {
            $s = $html->find("a[pimcore_id],img[pimcore_id]");

            if($s) {
                foreach ($s as $el) {
                    if ($el->href || $el->src) {
                        $type = $el->pimcore_type;
                        $id = (int) $el->pimcore_id;

                        if(array_key_exists($type, $idMapping)) {
                            if(array_key_exists($id, $idMapping[$type])) {
                                $el->outertext = str_replace('="' . $el->pimcore_id . '"', '="' . $idMapping[$type][$id] . '"', $el->outertext);
                            }
                        }
                    }
                }
            }

            $data = $html->save();

            $html->clear();
            unset($html);
        }

        return $data;
    }
}
