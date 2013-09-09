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

class Object_Class_Data_Video extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "video";

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
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "text";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "text";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_Video";

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
        $this->width = $this->getAsIntegerCast($width);
        return $this;
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
        return $this;
    }

    /**
     * @see Object_Class_Data::getDataForResource
     * @param Asset $data
     * @param null|Object_Abstract $object
     * @return integer|null
     */
    public function getDataForResource($data, $object = null) {
        if($data) {
            $data = clone $data;
            if($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getId());
            }
            if($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getId());
            }

            $data = object2array($data);
            return serialize($data);
        }
        return null;
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param integer $data
     * @return Asset
     */
    public function getDataFromResource($data) {
        if($data) {
            $raw = unserialize($data);

            if($raw["type"] == "asset") {
                if($asset = Asset::getById($raw["data"])) {
                    $raw["data"] = $asset;
                }
            }

            if($raw["poster"]) {
                if($poster = Asset::getById($raw["poster"])) {
                    $raw["poster"] = $poster;
                }
            }

            if($raw["data"]) {
                $video = new Object_Data_Video();
                $video->setData($raw["data"]);
                $video->setType($raw["type"]);
                $video->setPoster($raw["poster"]);
                $video->setTitle($raw["title"]);
                $video->setDescription($raw["description"]);
                return $video;
            }
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
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param Asset $data
     * @param null|Object_Abstract $object
     * @return integer
     */
    public function getDataForEditmode($data, $object = null) {

        if($data) {
            $data = clone $data;
            if($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getFullpath());
            }
            if($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getFullpath());
            }
            $data = object2array($data);
        }

        return $data;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param integer $data
     * @param null|Object_Abstract $object
     * @return Asset
     */
    public function getDataFromEditmode($data, $object = null) {

        $video = null;

        if($data["type"] == "asset") {
            if($asset = Asset::getByPath($data["data"])){
                $data["data"] = $asset;
            } else {
                $data["data"] = null;
            }
        }

        if($data["poster"]) {
            if($poster = Asset::getByPath($data["poster"])){
                $data["poster"] = $poster;
            } else {
                $data["poster"] = null;
            }
        }

        if(!empty($data["data"])) {
            $video = new Object_Data_Video();
            $video->setData($data["data"]);
            $video->setType($data["type"]);
            $video->setPoster($data["poster"]);
            $video->setTitle($data["title"]);
            $video->setDescription($data["description"]);
        }

        return $video;
    }

    public function getDataForGrid($data, $object = null) {
        if ($data && $data->getType() == "asset" && $data->getData() instanceof Asset) {
            return $data->getData()->getId();
        }
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param Asset_Image $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data && $data->getType() == "asset" && $data->getData() instanceof Asset) {
            return '<img src="/admin/asset/get-video-thumbnail/id/' . $data->getData()->getId() . '/width/100/height/100/aspectratio/true" />';
        }

        return parent::getVersionPreview($data);
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if ($data) {
            $value = $data->getData();
            if($value instanceof Asset) {
                $value = $value->getId();
            }
            return $data->getType() . "~" . $value;
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

        if($importValue && strpos($importValue, "~")) {
            list($type, $data) = explode("~", $importValue);
            if($type && $data) {
                $video = new Object_Data_Video();
                $video->setType($type);
                if($type == "asset") {
                    if($asset = Asset::getById($data)) {
                        $video->setData($asset);
                    } else {
                        return null;
                    }
                } else {
                    $video->setData($data);
                }
            }
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

        if ($data && $data->getData() instanceof Asset) {
            if (!array_key_exists($data->getData()->getCacheTag(), $tags)) {
                $tags = $data->getData()->getCacheTags($tags);
            }
        }

        if ($data && $data->getPoster() instanceof Asset) {
            if (!array_key_exists($data->getPoster()->getCacheTag(), $tags)) {
                $tags = $data->getPoster()->getCacheTags($tags);
            }
        }

        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data) {

        $dependencies = array();

        if ($data && $data->getData() instanceof Asset) {
            $dependencies["asset_" . $data->getData()->getId()] = array(
                "id" => $data->getData()->getId(),
                "type" => "asset"
            );
        }

        if ($data && $data->getPoster() instanceof Asset) {
            $dependencies["asset_" . $data->getPoster()->getId()] = array(
                "id" => $data->getPoster()->getId(),
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
        $data = $this->getDataFromObjectParam($object);
        if($data){
            return  $this->getDataForResource($data);
        }
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @param mixed $relatedObject
     * @return mixed
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $idMapper = null) {

        // @TODO
        return null;
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return false;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null) {
        $versionPreview = null;

        if ($data && $data->getData() instanceof Asset) {
            $versionPreview = '/admin/asset/get-video-thumbnail/id/' . $data->getData()->getId() . '/width/100/height/100/aspectratio/true';
        }

        if ($versionPreview) {
            $value = array();
            $value["src"] = $versionPreview;
            $value["type"] = "img";
            return $value;
        }

        return "";
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

        if ($data && $data->getData() instanceof Asset) {
            if(array_key_exists("asset", $idMapping) and array_key_exists($data->getData()->getId(), $idMapping["asset"])) {
                $data->setData(Asset::getById($idMapping["asset"][$data->getData()->getId()]));
            }
        }

        if ($data && $data->getPoster() instanceof Asset) {
            if(array_key_exists("asset", $idMapping) and array_key_exists($data->getPoster()->getId(), $idMapping["asset"])) {
                $data->setPoster(Asset::getById($idMapping["asset"][$data->getPoster()->getId()]));
            }
        }

        return $data;
    }
}
