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

class Object_Class_Data_Image extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "image";

    /**
     * @var integer
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var integer
     */
    public $height;

    /**
     * @var string
     */
    public $uploadPath;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "int(11)";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "int(11)";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Asset_Image";

    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $this->getAsIntegerCast($width);;
    }

    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
    }

    public function getDefaultValue() {
        return null;
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param Asset $data
     * @param null|Object_Abstract $object
     * @return integer|null
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Asset) {
            return $data->getId();
        }
        return null;
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param integer $data
     * @return Asset
     */
    public function getDataFromResource($data) {
        if (intval($data) > 0) {
            return Asset_Image::getById($data);
        }
        return null;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param Asset $data
     * @param null|Object_Abstract $object
     * @return integer|null
     */
    public function getDataForQueryResource($data, $object = null) {

     
        if ($data instanceof Asset) {
            return $data->getId();
        } 
        return null;
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param Asset $data
     * @param null|Object_Abstract $object
     * @return integer
     */
    public function getDataForEditmode($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param integer $data
     * @param null|Object_Abstract $object
     * @return Asset
     */
    public function getDataFromEditmode($data, $object = null) {
        return $this->getDataFromResource($data);
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param Asset_Image $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data instanceof Asset_Image) {
            return '<img src="/admin/asset/get-image-thumbnail/id/' . $data->getId() . '/width/100/height/100/aspectratio/true" />';
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        if ($object->$getter() instanceof Element_Interface) {
            return $object->$getter()->getFullPath();
        } else return null;
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue) {
        $value = null;
        if ($el = Asset::getByPath($importValue)) {
            $value = $el;
        }
        else {
            $value = null;
        }
        return $value;
    }

    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Asset_Image) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                $tags = $data->getCacheTags($tags);
            }
        }
        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data) {

        $dependencies = array();

        if ($data instanceof Asset) {
            $dependencies["asset_" . $data->getId()] = array(
                "id" => $data->getId(),
                "type" => "asset"
            );
        }

        return $dependencies;
    }


        /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport ($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        if($object->$getter() instanceof Asset){
            return  $object->$getter()->getId();
        }
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @param mixed $object
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null) {
        
        $asset = Asset::getById($value);
        if(empty($value)){
            return null;
        } else if (is_numeric($value) and $asset instanceof Asset) {
            return $asset;
        } else {
            throw new Exception("cannot get values from web service import - invalid data, referencing unknown asset with id [ ".$value." ]");
        }
    }

    /**
     * @param string $uploadPath
     */
    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = $uploadPath;
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
    }


}
