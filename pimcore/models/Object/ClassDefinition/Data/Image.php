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
use Pimcore\Model\Asset;
use Pimcore\Model\Element;

class Image extends Model\Object\ClassDefinition\Data
{

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
     * @see Model\Object\ClassDefinition\Data::getDataForResource
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return integer|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromResource
     * @param integer $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return Asset
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (intval($data) > 0) {
            return Asset\Image::getById($data);
        }

        return null;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataForQueryResource
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return integer|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if ($data instanceof Asset) {
            return $data->getId();
        }

        return null;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataForEditmode
     * @param Asset $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return integer
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
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
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getVersionPreview
     * @param Asset\Image $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail?id=' . $data->getId() . '&width=100&height=100&aspectratio=true" />';
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Element\ElementInterface) {
            return $data->getRealFullPath();
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed|null|Asset
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = null;
        if ($el = Asset::getByPath($importValue)) {
            $value = $el;
        } else {
            $value = null;
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
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if ($data instanceof Asset) {
            $dependencies["asset_" . $data->getId()] = [
                "id" => $data->getId(),
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
        if ($data instanceof Asset) {
            return  $data->getId();
        }
    }


    /**
     * @param mixed $value
     * @param null $object
     * @param array $params
     * @param null $idMapper
     * @return null|Asset|Asset\Archive|Asset\Audio|Asset\Document|Asset\Folder|Asset\Image|Asset\Text|Asset\Unknown|Asset\Video
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        $id = $value;

        if ($idMapper && !empty($value)) {
            $id = $idMapper->getMappedId("asset", $value);
            $fromMapper = true;
        }

        $asset = Asset::getById($id);
        if (empty($id) && !$fromMapper) {
            return null;
        } elseif (is_numeric($value) and $asset instanceof Asset) {
            return $asset;
        } else {
            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                throw new \Exception("cannot get values from web service import - invalid data, referencing unknown asset with id [ ".$value." ]");
            } else {
                $idMapper->recordMappingFailure("object", $object->getId(), "asset", $value);
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
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
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
        if ($data instanceof Asset\Image) {
            $versionPreview = "/admin/asset/get-image-thumbnail?id=" . $data->getId() . "&width=150&height=150&aspectratio=true";
        }

        if ($versionPreview) {
            $value = [];
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
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Asset\Image) {
            if (array_key_exists("asset", $idMapping) and array_key_exists($data->getId(), $idMapping["asset"])) {
                return Asset::getById($idMapping["asset"][$data->getId()]);
            }
        }

        return $data;
    }

    /**
     * @param Model\Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\Object\ClassDefinition\Data $masterDefinition)
    {
        $this->uploadPath = $masterDefinition->uploadPath;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof \Pimcore\Model\Asset\Image) {
            return [
                "type" => "asset",
                "id" => $value->getId()
            ];
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        $id = $value["id"];
        if (intval($id) > 0) {
            return Asset\Image::getById($id);
        }
    }
}
