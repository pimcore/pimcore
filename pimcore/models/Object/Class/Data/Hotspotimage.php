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
                            if($metaData["value"] instanceof Element_Interface) {
                                $metaData["value"] = $metaData["value"]->getId();
                            }
                        }
                    }
                }
                return $data;
            };

            $metaData["hotspots"] = $rewritePath($metaData["hotspots"]);
            $metaData["marker"] = $rewritePath($metaData["marker"]);

            $metaData = Pimcore_Tool_Serialize::serialize($metaData);

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
     * @see Object_Class_Data::getDataFromResource
     * @param Object_Data_Hotspotimage $data
     * @return Asset
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__image"] || $data[$this->getName() . "__hotspots"]) {

            $metaData = $data[$this->getName() . "__hotspots"];

            // check if the data is JSON (backward compatibility)
            $md = json_decode($metaData, true);
            if(!$md) {
                $md = Pimcore_Tool_Serialize::unserialize($metaData);
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
                                $el = Element_Service::getElementById($metaData["type"], $metaData["value"]);
                                $metaData["value"] = $el;
                            }
                        }
                    }
                }
                return $data;
            };

            $hotspots = $rewritePath($hotspots);
            $marker = $rewritePath($marker);

            return new Object_Data_Hotspotimage($data[$this->getName() . "__image"], $hotspots, $marker, $crop);
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

            $rewritePath = function ($data) {

                if(!is_array($data)) {
                    return array();
                }

                foreach ($data as &$element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as &$metaData) {
                            if($metaData["value"] instanceof Element_Interface) {
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
     * @see Object_Class_Data::getDataFromEditmode
     * @param Object_Data_Hotspotimage $data
     * @param null|Object_Abstract $object
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
                            $el = Element_Service::getElementByPath($metaData["type"], $metaData["value"]);
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


        return new Object_Data_Hotspotimage($data["image"], $data["hotspots"], $data["marker"], $data["crop"]);
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
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof Object_Data_Hotspotimage) {
            return base64_encode(Pimcore_Tool_Serialize::serialize($data));
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


            $getMetaDataCacheTags = function ($d, $tags) {

                if(!is_array($d)) {
                    return $tags;
                }

                foreach ($d as $element) {
                    if(array_key_exists("data",$element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach($element["data"] as $metaData) {
                            if($metaData["value"] instanceof Element_Interface) {
                                $tags = $metaData["value"]->getCacheTags($tags);
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
     */
    public function resolveDependencies($data) {

        $dependencies = array();

        if ($data instanceof Object_Data_Hotspotimage && $data->getImage() instanceof Asset_Image) {
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
                            if($metaData["value"] instanceof Element_Interface) {
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
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null) {
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
        if($data instanceof Object_Data_Hotspotimage && $data->getImage()) {
            $id = $data->getImage()->getId();
            if(array_key_exists("asset", $idMapping) and array_key_exists($id, $idMapping["asset"])) {
                $data->setImage(Asset::getById($idMapping["asset"][$id]));

                // reset hotspot, marker & crop
                $data->setHotspots(null);
                $data->setMarker(null);
                $data->setCrop(null);
            }
        }
        return $data;
    }
}
