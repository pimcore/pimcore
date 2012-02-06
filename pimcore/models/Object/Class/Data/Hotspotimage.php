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

class Object_Class_Data_Hotspotimage extends Object_Class_Data_Image {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "hotspotimage";

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = array("image" => "int(11)","hotspots" => "text");

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = array("image" => "int(11)","hotspots" => "text");

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_Hotspotimage";


    /**
     * @see Object_Class_Data::getDataForResource
     * @param Object_Data_Hotspotimage $data
     * @param null|Object_Abstract $object
     * @return integer|null
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object_Data_Hotspotimage) {
            $imageId = null;
            if($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $hotspots = $data->getHotspots();
            if($hotspots != "null" && !empty($hotspots)) {
                $hotspots = json_encode($data->getHotspots());
            } else {
                $hotspots = null;
            }
            return array(
                $this->getName() . "__image" => $imageId,
                $this->getName() . "__hotspots" => $hotspots
            );
        }
        return array(
            $this->getName() . "__image" => null,
            $this->getName() . "__hotspots" => null
        );
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param Object_Data_Hotspotimage $data
     * @return Asset
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__image"] || $data[$this->getName() . "__hotspots"]) {
            $hotspots = json_decode($data[$this->getName() . "__hotspots"]);
            if($hotspots == "null") {
                $hotspots = null;
            }

            return new Object_Data_Hotspotimage($data[$this->getName() . "__image"], $hotspots);
        }
        return null;

    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param Object_Data_Hotspotimage $data
     * @param null|Object_Abstract $object
     * @return integer|null
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param Object_Data_Hotspotimage $data
     * @param null|Object_Abstract $object
     * @return integer
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data instanceof Object_Data_Hotspotimage) {
            $imageId = null;
            if($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }
            return array(
                "image" => $imageId,
                "hotspots" => $data->getHotspots()
            );
        }
        return null;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param Object_Data_Hotspotimage $data
     * @param null|Object_Abstract $object
     * @return Asset
     */
    public function getDataFromEditmode($data, $object = null) {
        return new Object_Data_Hotspotimage($data["image"], $data["hotspots"]);
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param Asset_Image $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data instanceof Object_Data_Hotspotimage && $data->getImage() instanceof Asset_Image) {
            return '<img src="/admin/asset/get-image-thumbnail/id/' . $data->getImage()->getId() . '/width/100/height/100/aspectratio/true" />';
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
        if ($object->$getter() instanceof Object_Data_Hotspotimage) {
            return base64_encode(Pimcore_Tool_Serialize::serialize($object->$getter()));
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
        $value = Pimcore_Tool_Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof Object_Data_Hotspotimage) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Object_Data_Hotspotimage && $data->getImage() instanceof Asset_Image) {
            if (!array_key_exists($data->getImage()->getCacheTag(), $tags)) {
                $tags = $data->getImage()->getCacheTags($tags);
            }
        }
        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data) {

        $dependencies = array();

        if ($data instanceof Object_Data_Hotspotimage && $data->getImage() instanceof Asset_Image) {
            $dependencies["asset_" . $data->getImage()->getId()] = array(
                "id" => $data->getImage()->getId(),
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
        return $this->getForCsvExport($object);
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value) {
        return $this->getFromCsvImport($value);
    }

    /**
     * @param $data
     * @param null $object
     * @return null
     */
    public function getDataForGrid($data, $object = null) {

        if ($data instanceof Object_Data_Hotspotimage && $data->getImage() instanceof Asset) {
            return $data->getImage();
        } else {
            return null;
        }
    }


}
