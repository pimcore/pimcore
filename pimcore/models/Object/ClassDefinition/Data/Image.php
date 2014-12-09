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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;

class Image extends Model\Object\ClassDefinition\Data {

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
    public $phpdocType = "\\Pimcore\\Model\\Asset\\Image";

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
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer|null
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Asset) {
            return $data->getId();
        }
        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param integer $data
     * @return Asset
     */
    public function getDataFromResource($data) {
        if (intval($data) > 0) {
            return Asset\Image::getById($data);
        }
        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer|null
     */
    public function getDataForQueryResource($data, $object = null) {

        if ($data instanceof Asset) {
            return $data->getId();
        } 
        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer
     */
    public function getDataForEditmode($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param integer $data
     * @param null|Model\Object\AbstractObject $object
     * @return Asset
     */
    public function getDataFromEditmode($data, $object = null) {
        return $this->getDataFromResource($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param Asset\Image $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail/id/' . $data->getId() . '/width/100/height/100/aspectratio/true" />';
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof Element\ElementInterface) {
            return $data->getFullPath();
        } else return null;
    }

    /**
     * @param $importValue
     * @return mixed|null|Asset
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
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Asset\Image) {
            if (!array_key_exists($data->getCacheTag(), $tags)) {
                $tags = $data->getCacheTags($tags);
            }
        }
        return $tags;
    }

    /**
     * @param $data
     * @return array
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
        $data = $this->getDataFromObjectParam($object);
        if($data instanceof Asset){
            return  $data->getId();
        }
    }


    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $idMapper = null) {

        $id = $value;

        if ($idMapper && !empty($value)) {
            $id = $idMapper->getMappedId("asset", $value);
            $fromMapper = true;

        }

        $asset = Asset::getById($id);
        if(empty($id) && !$fromMapper){
            return null;
        } else if (is_numeric($value) and $asset instanceof Asset) {
            return $asset;
        } else {
            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                throw new \Exception("cannot get values from web service import - invalid data, referencing unknown asset with id [ ".$value." ]");
            } else {
                $idMapper->recordMappingFailure("object", $relatedObject->getId(), "asset", $value);
            }
        }
    }

    /**
     * @param $uploadPath
     * @return $this
     */
    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = $uploadPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
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
        $versionPreview = null;
        if ($data instanceof Asset\Image) {
            $versionPreview = "/admin/asset/get-image-thumbnail/id/" . $data->getId() . "/width/150/height/150/aspectratio/true";
        }

        if ($versionPreview) {
            $value = array();
            $value["src"] = $versionPreview;
            $value["type"] = "img";
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
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = array()) {
        $data = $this->getDataFromObjectParam($object, $params);
        if($data instanceof Asset\Image) {
            if(array_key_exists("asset", $idMapping) and array_key_exists($data->getId(), $idMapping["asset"])) {
                return Asset::getById($idMapping["asset"][$data->getId()]);
            }
        }
        return $data;
    }

    /**
     * @param Model\Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\Object\ClassDefinition\Data $masterDefinition) {
        $this->uploadPath = $masterDefinition->uploadPath;
    }
}
