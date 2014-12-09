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
use Pimcore\Model\Object;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool\Serialize;

class Hotspotimage extends Model\Object\ClassDefinition\Data\Image {

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
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\Hotspotimage";


    /**
     * @var int
     */
    public $ratioX;

    /**
     * @var int
     */
    public $ratioY;

    /**
     * @param int $ratioX
     */
    public function setRatioX($ratioX)
    {
        $this->ratioX = $ratioX;
    }

    /**
     * @return int
     */
    public function getRatioX()
    {
        return $this->ratioX;
    }

    /**
     * @param int $ratioY
     */
    public function setRatioY($ratioY)
    {
        $this->ratioY = $ratioY;
    }

    /**
     * @return int
     */
    public function getRatioY()
    {
        return $this->ratioY;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param Object\Data\Hotspotimage $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer|null
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object\Data\Hotspotimage) {
            $imageId = null;
            if($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $metaData = array(
                "hotspots" => $data->getHotspots(),
                "marker" => $data->getMarker(),
                "crop" => $data->getCrop()
            );

            $rewritePath = function ($data) {

                if(!is_array($data)) {
                    return array();
                }

                foreach ($data as &$element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as &$metaData) {
                            if($metaData["value"] instanceof Element\ElementInterface) {
                                $metaData["value"] = $metaData["value"]->getId();
                            }
                        }
                    }
                }
                return $data;
            };

            $metaData["hotspots"] = $rewritePath($metaData["hotspots"]);
            $metaData["marker"] = $rewritePath($metaData["marker"]);

            $metaData = Serialize::serialize($metaData);

            return array(
                $this->getName() . "__image" => $imageId,
                $this->getName() . "__hotspots" => $metaData
            );
        }
        return array(
            $this->getName() . "__image" => null,
            $this->getName() . "__hotspots" => null
        );
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param Object\Data\Hotspotimage $data
     * @return Asset
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__image"] || $data[$this->getName() . "__hotspots"]) {

            $metaData = $data[$this->getName() . "__hotspots"];

            // check if the data is JSON (backward compatibility)
            $md = json_decode($metaData, true);
            if(!$md) {
                $md = Serialize::unserialize($metaData);
            } else {
                if(is_array($md) && count($md)) {
                    $md["hotspots"] = $md;
                }
            }

            $hotspots = empty($md["hotspots"]) ? null : $md["hotspots"];
            $marker = empty($md["marker"]) ? null : $md["marker"];
            $crop = empty($md["crop"]) ? null : $md["crop"];

            $rewritePath = function ($data) {

                if(!is_array($data)) {
                    return array();
                }

                foreach ($data as &$element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as &$metaData) {
                            if(in_array($metaData["type"], array("object","asset","document"))) {
                                $el = Element\Service::getElementById($metaData["type"], $metaData["value"]);
                                $metaData["value"] = $el;
                            }
                        }
                    }
                }
                return $data;
            };

            $hotspots = $rewritePath($hotspots);
            $marker = $rewritePath($marker);

            return new Object\Data\Hotspotimage($data[$this->getName() . "__image"], $hotspots, $marker, $crop);
        }
        return null;

    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param Object\Data\Hotspotimage $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer|null
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param Object\Data\Hotspotimage $data
     * @param null|Model\Object\AbstractObject $object
     * @return integer
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data instanceof Object\Data\Hotspotimage) {
            $imageId = null;
            if($data->getImage()) {
                $imageId = $data->getImage()->getId();
            }

            $rewritePath = function ($data) {

                if(!is_array($data)) {
                    return array();
                }

                foreach ($data as &$element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as &$metaData) {
                            if($metaData["value"] instanceof Element\ElementInterface) {
                                $metaData["value"] = $metaData["value"]->getFullPath();
                            }
                        }
                    }
                }
                return $data;
            };

            $marker = $rewritePath($data->getMarker());
            $hotspots = $rewritePath($data->getHotspots());

            return array(
                "image" => $imageId,
                "hotspots" => $hotspots,
                "marker" => $marker,
                "crop" => $data->getCrop()
            );
        }
        return null;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param Object\Data\Hotspotimage $data
     * @param null|Model\Object\AbstractObject $object
     * @return Asset
     */
    public function getDataFromEditmode($data, $object = null) {

        $rewritePath = function ($data) {

            if(!is_array($data)) {
                return array();
            }

            foreach ($data as &$element) {
                if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                    foreach($element["data"] as &$metaData) {
                        if(in_array($metaData["type"], array("object","asset","document"))) {
                            $el = Element\Service::getElementByPath($metaData["type"], $metaData["value"]);
                            $metaData["value"] = $el;
                        }
                    }
                }
            }
            return $data;
        };

        if(array_key_exists("marker",$data) && is_array($data["marker"]) && count($data["marker"]) > 0) {
            $data["marker"] = $rewritePath($data["marker"]);
        }

        if(array_key_exists("hotspots",$data) && is_array($data["hotspots"]) && count($data["hotspots"]) > 0) {
            $data["hotspots"] = $rewritePath($data["hotspots"]);
        }


        return new Object\Data\Hotspotimage($data["image"], $data["hotspots"], $data["marker"], $data["crop"]);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param Asset\Image $data
     * @return string
     */
    public function getVersionPreview($data) {
        if ($data instanceof Object\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            return '<img src="/admin/asset/get-image-thumbnail/id/' . $data->getImage()->getId() . '/width/100/height/100/aspectratio/true" />';
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
        if ($data instanceof Object\Data\Hotspotimage) {
            return base64_encode(Serialize::serialize($data));
        } else return null;
    }

    /**
     * @param string $importValue
     * @return mixed|null|Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue) {
        $value = null;
        $value = Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof Object\Data\Hotspotimage) {
            return $value;
        } else {
            return null;
        }
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

        if ($data instanceof Object\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            if (!array_key_exists($data->getImage()->getCacheTag(), $tags)) {
                $tags = $data->getImage()->getCacheTags($tags);
            }


            $getMetaDataCacheTags = function ($d, $tags) {

                if(!is_array($d)) {
                    return $tags;
                }

                foreach ($d as $element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as $metaData) {
                            if($metaData["value"] instanceof Element\ElementInterface) {
                                if(!array_key_exists($metaData["value"]->getCacheTag(), $tags)) {
                                    $tags = $metaData["value"]->getCacheTags($tags);
                                }
                            }
                        }
                    }
                }
                return $tags;
            };

            $marker = $data->getMarker();
            $hotspots = $data->getHotspots();

            $tags = $getMetaDataCacheTags($marker, $tags);
            $tags = $getMetaDataCacheTags($hotspots, $tags);
        }

        return $tags;
    }

    /**
     * @param mixed $data
     * @return array
     */
    public function resolveDependencies($data) {

        $dependencies = array();

        if ($data instanceof Object\Data\Hotspotimage && $data->getImage() instanceof Asset\Image) {
            $dependencies["asset_" . $data->getImage()->getId()] = array(
                "id" => $data->getImage()->getId(),
                "type" => "asset"
            );

            $getMetaDataDependencies = function ($data, $dependencies) {

                if(!is_array($data)) {
                    return $dependencies;
                }

                foreach ($data as $element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as $metaData) {
                            if($metaData["value"] instanceof Element\ElementInterface) {
                                $dependencies[$metaData["type"] . "_" . $metaData["value"]->getId()] = array(
                                    "id" => $metaData["value"]->getId(),
                                    "type" => $metaData["type"]
                                );
                            }
                        }
                    }
                }
                return $dependencies;
            };

            $dependencies = $getMetaDataDependencies($data->getMarker(), $dependencies);
            $dependencies = $getMetaDataDependencies($data->getHotspots(), $dependencies);
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
     * @param mixed $value
     * @param null $relatedObject
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $idMapper = null) {
        $hotspotImage = $this->getFromCsvImport($value);
        /** @var $hotspotImage Object\Data\Hotspotimage */

        if(!$hotspotImage) {
            return null;
        }

        $theImage = $hotspotImage->getImage();

        if (!$theImage) {
            return null;
        }

        $id = $theImage->getId();

        if ($idMapper && !empty($id)) {
            $id = $idMapper->getMappedId("asset", $id);
            $fromMapper = true;
        }

        $asset = Asset::getById($id);
        if(empty($id) && !$fromMapper){
            return null;
        } else if (is_numeric($id) and $asset instanceof Asset) {
            $hotspotImage->setImage($asset);
            return $hotspotImage;
        } else {
            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                throw new \Exception("cannot get values from web service import - invalid data, referencing unknown (hotspot) asset with id [ ".$id." ]");
            } else {
                $idMapper->recordMappingFailure("object", $relatedObject->getId(), "asset", $value);
            }
        }
    }

    /**
     * @param $data
     * @param null $object
     * @return null
     */
    public function getDataForGrid($data, $object = null) {

        if ($data instanceof Object\Data\Hotspotimage && $data->getImage() instanceof Asset) {
            return $data->getImage();
        } else {
            return null;
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
        if($data instanceof Object\Data\Hotspotimage && $data->getImage()) {
            $id = $data->getImage()->getId();
            if(array_key_exists("asset", $idMapping) and array_key_exists($id, $idMapping["asset"])) {
                $data->setImage(Asset::getById($idMapping["asset"][$id]));

                // reset hotspot, marker & crop
                $data->setHotspots(null);
                $data->setMarker(null);
                $data->setCrop(null);
            }


            if($data->getHotspots()) {
                $data->setHotspots($this->rewriteIdsInDataEntries($data->getHotspots(), $idMapping));
            }
            if($data->getMarker()) {
                $data->setMarker($this->rewriteIdsInDataEntries($data->getMarker(), $idMapping));
            }
        }

        return $data;
    }

    /**
     * @param $dataArray
     * @param $idMapping
     * @return array
     */
    private function rewriteIdsInDataEntries($dataArray, $idMapping) {
        $newDataArray = array();
        if($dataArray) {
            foreach($dataArray as $dataArrayEntry) {
                if($dataArrayEntry['data']) {
                    $newData = array();
                    foreach($dataArrayEntry['data'] as $dataEntry) {
                        //rewrite objects
                        if($dataEntry['type'] == 'object' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if(array_key_exists("object", $idMapping) and array_key_exists($id, $idMapping["object"])) {
                                $dataEntry['value'] = Object::getById($idMapping["object"][$id]);
                            }
                        }
                        //rewrite assets
                        if($dataEntry['type'] == 'asset' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if(array_key_exists("asset", $idMapping) and array_key_exists($id, $idMapping["asset"])) {
                                $dataEntry['value'] = Asset::getById($idMapping["asset"][$id]);
                            }
                        }
                        //rewrite documents
                        if($dataEntry['type'] == 'document' && $dataEntry['value']) {
                            $id = $dataEntry['value']->getId();
                            if(array_key_exists("document", $idMapping) and array_key_exists($id, $idMapping["document"])) {
                                $dataEntry['value'] = Document::getById($idMapping["document"][$id]);
                            }
                        }
                        $newData[] = $dataEntry;
                    }
                    $dataArrayEntry['data'] = $newData;
                }
                $newDataArray[] = $dataArrayEntry;
            }
        }
        return $newDataArray;
    }
}
