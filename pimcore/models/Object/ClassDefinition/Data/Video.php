<?php 
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Asset;

class Video extends Model\Object\ClassDefinition\Data
{

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
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\Video";

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param integer $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return integer|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data) {
            $data = clone $data;
            if ($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getId());
            }
            if ($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getId());
            }

            $data = object2array($data);

            return serialize($data);
        }

        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param integer $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return Asset
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data) {
            $raw = unserialize($data);

            if ($raw["type"] == "asset") {
                if ($asset = Asset::getById($raw["data"])) {
                    $raw["data"] = $asset;
                }
            }

            if ($raw["poster"]) {
                if ($poster = Asset::getById($raw["poster"])) {
                    $raw["poster"] = $poster;
                }
            }

            if ($raw["data"]) {
                $video = new Object\Data\Video();
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
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return integer|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return integer
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            $data = clone $data;
            if ($data->getData() instanceof Asset) {
                $data->setData($data->getData()->getFullpath());
            }
            if ($data->getPoster() instanceof Asset) {
                $data->setPoster($data->getPoster()->getFullpath());
            }
            $data = object2array($data);
        }

        return $data;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param integer $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return Asset
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $video = null;

        if ($data["type"] == "asset") {
            if ($asset = Asset::getByPath($data["data"])) {
                $data["data"] = $asset;
            } else {
                $data["data"] = null;
            }
        }

        if ($data["poster"]) {
            if ($poster = Asset::getByPath($data["poster"])) {
                $data["poster"] = $poster;
            } else {
                $data["poster"] = null;
            }
        }

        if (!empty($data["data"])) {
            $video = new Object\Data\Video();
            $video->setData($data["data"]);
            $video->setType($data["type"]);
            $video->setPoster($data["poster"]);
            $video->setTitle($data["title"]);
            $video->setDescription($data["description"]);
        }

        return $video;
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return mixed
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data && $data->getType() == "asset" && $data->getData() instanceof Asset) {
            return $data->getData()->getId();
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param Asset\Image $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data && $data->getType() == "asset" && $data->getData() instanceof Asset) {
            return '<img src="/admin/asset/get-video-thumbnail/id/' . $data->getData()->getId() . '/width/100/height/100/aspectratio/true" />';
        }

        return parent::getVersionPreview($data, $object, $params);
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data) {
            $value = $data->getData();
            if ($value instanceof Asset) {
                $value = $value->getId();
            }

            return $data->getType() . "~" . $value;
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed|null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = null;

        if ($importValue && strpos($importValue, "~")) {
            list($type, $data) = explode("~", $importValue);
            if ($type && $data) {
                $video = new Object\Data\Video();
                $video->setType($type);
                if ($type == "asset") {
                    if ($asset = Asset::getById($data)) {
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
     * @param $object
     * @param mixed $params
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Object\Data\Video) {
            $value = $data->getTitle() . " " . $data->getDescription();

            return $value;
        }

        return "";
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

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
     * @param $data
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data && $data->getData() instanceof Asset) {
            $dependencies["asset_" . $data->getData()->getId()] = [
                "id" => $data->getData()->getId(),
                "type" => "asset"
            ];
        }

        if ($data && $data->getPoster() instanceof Asset) {
            $dependencies["asset_" . $data->getPoster()->getId()] = [
                "id" => $data->getPoster()->getId(),
                "type" => "asset"
            ];
        }

        return $dependencies;
    }


        /**
     * converts data to be exposed via webservices
     * @param string $object
     * @param mixed $params
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data) {
            return  $this->getDataForResource($data, $params);
        }
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @param mixed $relatedObject
     * @param mixed $params
     * @param $idMapper
     * @return mixed
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {

        // @TODO
        return null;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        $versionPreview = null;

        if ($data && $data->getData() instanceof Asset) {
            $versionPreview = '/admin/asset/get-video-thumbnail/id/' . $data->getData()->getId() . '/width/100/height/100/aspectratio/true';
        }

        if ($versionPreview) {
            $value = [];
            $value["src"] = $versionPreview;
            $value["type"] = "img";

            return $value;
        }

        return "";
    }

    /**
     * @param $object
     * @param $idMapping
     * @param array $params
     * @return mixed
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data && $data->getData() instanceof Asset) {
            if (array_key_exists("asset", $idMapping) and array_key_exists($data->getData()->getId(), $idMapping["asset"])) {
                $data->setData(Asset::getById($idMapping["asset"][$data->getData()->getId()]));
            }
        }

        if ($data && $data->getPoster() instanceof Asset) {
            if (array_key_exists("asset", $idMapping) and array_key_exists($data->getPoster()->getId(), $idMapping["asset"])) {
                $data->setPoster(Asset::getById($idMapping["asset"][$data->getPoster()->getId()]));
            }
        }

        return $data;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof Object\Data\Video) {
            $result = [];
            $result["type"] = $value->getType();
            if ($value->getTitle()) {
                $result["title"] = $value->getTitle();
            }

            if ($value->getDescription()) {
                $result["description"] = $value->getDescription();
            }

            $poster = $value->getPoster();
            if ($poster) {
                $result["poster"] = [
                    "type" => Model\Element\Service::getType($poster),
                    "id" => $poster->getId()
                ];
            }

            $data = $value->getData();

            if ($data && $value->getType() == "asset") {
                $result["data"] = [
                    "type" => Model\Element\Service::getType($data),
                    "id" => $data->getId()
                ];
            } else {
                $result["data"] = $data;
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $video = new Object\Data\Video();
            $video->setType($value["type"]);
            $video->setTitle($value["title"]);
            $video->setDescription($value["description"]);

            if ($value["poster"]) {
                $video->setPoster(Model\Element\Service::getElementById($value["poster"]["type"], $value["poster"]["id"]));
            }

            if ($value["data"]) {
                if (is_array($value["data"])) {
                    $video->setData(Model\Element\Service::getElementById($value["data"]["type"], $value["data"]["id"]));
                } else {
                    $video->setData($value["data"]);
                }
            }

            return $video;
        }
    }
}
