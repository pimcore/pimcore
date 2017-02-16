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
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;
use Pimcore\Tool\Serialize;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Image extends Model\Document\Tag
{

    /**
     * ID of the referenced image
     *
     * @var integer
     */
    public $id;

    /**
     * The ALT text of the image
     *
     * @var string
     */
    public $alt;

    /**
     * Contains the imageobject itself
     *
     * @var Asset\Image
     */
    public $image;

    /**
     * @var bool
     */
    public $cropPercent = false;

    /**
     * @var float
     */
    public $cropWidth;

    /**
     * @var float
     */
    public $cropHeight;

    /**
     * @var float
     */
    public $cropTop;

    /**
     * @var float
     */
    public $cropLeft;

    /**
     * @var array
     */
    public $hotspots = [];

    /**
     * @var array
     */
    public $marker = [];

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "image";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return [
            "id" => $this->id,
            "alt" => $this->alt,
            "cropPercent" => $this->cropPercent,
            "cropWidth" => $this->cropWidth,
            "cropHeight" => $this->cropHeight,
            "cropTop" => $this->cropTop,
            "cropLeft" => $this->cropLeft,
            "hotspots" => $this->hotspots,
            "marker" => $this->marker
        ];
    }

    /**
     *
     */
    public function getDataForResource()
    {
        return [
            "id" => $this->id,
            "alt" => $this->alt,
            "cropPercent" => $this->cropPercent,
            "cropWidth" => $this->cropWidth,
            "cropHeight" => $this->cropHeight,
            "cropTop" => $this->cropTop,
            "cropLeft" => $this->cropLeft,
            "hotspots" => $this->hotspots,
            "marker" => $this->marker
        ];
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return array
     */
    public function getDataEditmode()
    {
        $image = $this->getImage();

        if ($image instanceof Asset\Image) {
            $rewritePath = function ($data) {
                if (!is_array($data)) {
                    return [];
                }

                foreach ($data as &$element) {
                    if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                        foreach ($element["data"] as &$metaData) {
                            if ($metaData["value"] instanceof Element\ElementInterface) {
                                $metaData["value"] = $metaData["value"]->getRealFullPath();
                            }
                        }
                    }
                }

                return $data;
            };

            $marker = $rewritePath($this->marker);
            $hotspots = $rewritePath($this->hotspots);

            $marker = object2array($marker);
            $hotspots = object2array($hotspots);

            return [
                "id" => $this->id,
                "path" => $image->getFullPath(),
                "alt" => $this->alt,
                "cropPercent" => $this->cropPercent,
                "cropWidth" => $this->cropWidth,
                "cropHeight" => $this->cropHeight,
                "cropTop" => $this->cropTop,
                "cropLeft" => $this->cropLeft,
                "hotspots" => $hotspots,
                "marker" => $marker
            ];
        }

        return null;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend()
    {
        if (!is_array($this->options)) {
            $this->options = [];
        }

        $image = $this->getImage();

        if ($image instanceof Asset) {
            if ((isset($this->options["thumbnail"]) && $this->options["thumbnail"]) || $this->cropPercent) {
                // create a thumbnail first
                $autoName = false;

                $thumbConfig = $image->getThumbnailConfig($this->options["thumbnail"]);
                if (!$thumbConfig && $this->cropPercent) {
                    $thumbConfig = new Asset\Image\Thumbnail\Config();
                }

                if ($this->cropPercent) {
                    $this->applyCustomCropping($thumbConfig);
                    $autoName = true;
                }

                if (isset($this->options["highResolution"]) && $this->options["highResolution"] > 1) {
                    $thumbConfig->setHighResolution($this->options["highResolution"]);
                }

                // autogenerate a name for the thumbnail because it's different from the original
                if ($autoName) {
                    $hash = md5(Serialize::serialize($thumbConfig->getItems()));
                    $thumbConfig->setName($thumbConfig->getName() . "_auto_" . $hash);
                }

                $deferred = true;
                if (isset($this->options["deferred"])) {
                    $deferred = $this->options["deferred"];
                }

                $thumbnail = $image->getThumbnail($thumbConfig, $deferred);
            } else {
                // we're using the thumbnail class only to generate the HTML
                $thumbnail = new Asset\Image\Thumbnail($image);
            }

            $attributes = array_merge($this->options, [
                "alt" => $this->alt,
                "title" => $this->alt
            ]);

            $removeAttributes = [];
            if (isset($this->options["removeAttributes"]) && is_array($this->options["removeAttributes"])) {
                $removeAttributes = $this->options["removeAttributes"];
            }

            // thumbnail's HTML is always generated by the thumbnail itself
            return $thumbnail->getHTML($attributes, $removeAttributes);
        }
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        if (strlen($data) > 2) {
            $data = Serialize::unserialize($data);
        }


        $rewritePath = function ($data) {
            if (!is_array($data)) {
                return [];
            }

            foreach ($data as &$element) {
                if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                    foreach ($element["data"] as &$metaData) {
                        // this is for backward compatibility (Array vs. MarkerHotspotItem)
                        if (is_array($metaData)) {
                            $metaData = new Element\Data\MarkerHotspotItem($metaData);
                        }
                    }
                }
            }

            return $data;
        };

        if (array_key_exists("marker", $data) && is_array($data["marker"]) && count($data["marker"]) > 0) {
            $data["marker"] = $rewritePath($data["marker"]);
        }

        if (array_key_exists("hotspots", $data) && is_array($data["hotspots"]) && count($data["hotspots"]) > 0) {
            $data["hotspots"] = $rewritePath($data["hotspots"]);
        }

        $this->id = $data["id"];
        $this->alt = $data["alt"];
        $this->cropPercent = $data["cropPercent"];
        $this->cropWidth = $data["cropWidth"];
        $this->cropHeight = $data["cropHeight"];
        $this->cropTop = $data["cropTop"];
        $this->cropLeft = $data["cropLeft"];
        $this->marker = $data["marker"];
        $this->hotspots = $data["hotspots"];

        return $this;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $rewritePath = function ($data) {
            if (!is_array($data)) {
                return [];
            }

            foreach ($data as &$element) {
                if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                    foreach ($element["data"] as &$metaData) {
                        $metaData = new Element\Data\MarkerHotspotItem($metaData);
                        if (in_array($metaData["type"], ["object", "asset", "document"])) {
                            $el = Element\Service::getElementByPath($metaData["type"], $metaData->getValue());
                            $metaData["value"] = $el;
                        }
                    }
                }
            }

            return $data;
        };

        if (is_array($data)) {
            if (array_key_exists("marker", $data) && is_array($data["marker"]) && count($data["marker"]) > 0) {
                $data["marker"] = $rewritePath($data["marker"]);
            }

            if (array_key_exists("hotspots", $data) && is_array($data["hotspots"]) && count($data["hotspots"]) > 0) {
                $data["hotspots"] = $rewritePath($data["hotspots"]);
            }

            $this->id = $data["id"];
            $this->alt = $data["alt"];
            $this->cropPercent = $data["cropPercent"];
            $this->cropWidth = $data["cropWidth"];
            $this->cropHeight = $data["cropHeight"];
            $this->cropTop = $data["cropTop"];
            $this->cropLeft = $data["cropLeft"];
            $this->marker = $data["marker"];
            $this->hotspots = $data["hotspots"];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->alt;
    }

    /**
     * @return string
     */
    public function getAlt()
    {
        return $this->getText();
    }

    /**
     * @return string
     */
    public function getSrc()
    {
        $image = $this->getImage();
        if ($image instanceof Asset) {
            return $image->getFullPath();
        }

        return "";
    }

    /**
     * @return Asset\Image
     */
    public function getImage()
    {
        if (!$this->image) {
            $this->image = Asset\Image::getById($this->getId());
        }

        return $this->image;
    }

    /**
     * @param Asset\Image $image
     * @return Model\Document\Tag\Image
     */
    public function setImage($image)
    {
        $this->image = $image;

        if ($image instanceof Asset) {
            $this->setId($image->getId());
        }

        return $this;
    }

    /**
     * @param int $id
     * @return Model\Document\Tag\Image
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @param $conf
     * @param bool $deferred
     * @return Asset\Image\Thumbnail|string
     */
    public function getThumbnail($conf, $deferred = true)
    {
        $image = $this->getImage();
        if ($image instanceof Asset) {
            $thumbConfig = $image->getThumbnailConfig($conf);
            if ($thumbConfig && $this->cropPercent) {
                $this->applyCustomCropping($thumbConfig);
                $hash = md5(Serialize::serialize($thumbConfig->getItems()));
                $thumbConfig->setName($thumbConfig->getName() . "_auto_" . $hash);
            }

            return $image->getThumbnail($thumbConfig, $deferred);
        }

        return "";
    }

    /**
     * @param $thumbConfig
     * @return mixed
     */
    protected function applyCustomCropping($thumbConfig)
    {
        $cropConfig = [
            "width" => $this->cropWidth,
            "height" => $this->cropHeight,
            "y" => $this->cropTop,
            "x" => $this->cropLeft
        ];

        $thumbConfig->addItemAt(0, "cropPercent", $cropConfig);

        // also crop media query specific configs
        if ($thumbConfig->hasMedias()) {
            foreach ($thumbConfig->getMedias() as $mediaName => $mediaItems) {
                $thumbConfig->addItemAt(0, "cropPercent", $cropConfig, $mediaName);
            }
        }
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        $image = $this->getImage();
        if ($image instanceof Asset\Image) {
            return false;
        }

        return true;
    }


    /**
     * @param $ownerDocument
     * @param array $tags
     * @return array|mixed
     */
    public function getCacheTags($ownerDocument, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        $image = $this->getImage();

        if ($image instanceof Asset) {
            if (!array_key_exists($image->getCacheTag(), $tags)) {
                $tags = $image->getCacheTags($tags);
            }
        }

        $getMetaDataCacheTags = function ($data, $tags) {
            if (!is_array($data)) {
                return $tags;
            }

            foreach ($data as $element) {
                if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                    foreach ($element["data"] as $metaData) {
                        if ($metaData["value"] instanceof Element\ElementInterface) {
                            if (!array_key_exists($metaData["value"]->getCacheTag(), $tags)) {
                                $tags = $metaData["value"]->getCacheTags($tags);
                            }
                        }
                    }
                }
            }

            return $tags;
        };

        $tags = $getMetaDataCacheTags($this->marker, $tags);
        $tags = $getMetaDataCacheTags($this->hotspots, $tags);

        return $tags;
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [];
        $image = $this->getImage();

        if ($image instanceof Asset\Image) {
            $key = "asset_" . $image->getId();

            $dependencies[$key] = [
                "id" => $image->getId(),
                "type" => "asset"
            ];
        }

        $getMetaDataDependencies = function ($data, $dependencies) {
            if (!is_array($data)) {
                return $dependencies;
            }

            foreach ($data as $element) {
                if (array_key_exists("data", $element) && is_array($element["data"]) && count($element["data"]) > 0) {
                    foreach ($element["data"] as $metaData) {
                        if ($metaData["value"] instanceof Element\ElementInterface) {
                            $dependencies[$metaData["type"] . "_" . $metaData["value"]->getId()] = [
                                "id" => $metaData["value"]->getId(),
                                "type" => $metaData["type"]
                            ];
                        }
                    }
                }
            }

            return $dependencies;
        };

        $dependencies = $getMetaDataDependencies($this->marker, $dependencies);
        $dependencies = $getMetaDataDependencies($this->hotspots, $dependencies);

        return $dependencies;
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param null $document
     * @param array $params
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->id !==null) {
            $this->alt = $data->alt;
            $this->id = $data->id;

            if ($idMapper) {
                $this->id = $idMapper->getMappedId("asset", $data->id);
            }

            if (is_numeric($this->id)) {
                $image = $this->getImage();
                if (!$image instanceof Asset\Image) {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure("document", $this->getDocumentId(), "asset", $data->id);
                    } else {
                        throw new \Exception("cannot get values from web service import - referenced image with id [ " . $this->id . " ] is unknown");
                    }
                }
            } else {
                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    $idMapper->recordMappingFailure("document", $this->getDocumentId(), "asset", $data->id);
                } else {
                    throw new \Exception("cannot get values from web service import - id is not valid");
                }
            }
        }
    }

    /**
     * @param $cropHeight
     * @return $this
     */
    public function setCropHeight($cropHeight)
    {
        $this->cropHeight = $cropHeight;

        return $this;
    }

    /**
     * @return float
     */
    public function getCropHeight()
    {
        return $this->cropHeight;
    }

    /**
     * @param $cropLeft
     * @return $this
     */
    public function setCropLeft($cropLeft)
    {
        $this->cropLeft = $cropLeft;

        return $this;
    }

    /**
     * @return float
     */
    public function getCropLeft()
    {
        return $this->cropLeft;
    }

    /**
     * @param $cropPercent
     * @return $this
     */
    public function setCropPercent($cropPercent)
    {
        $this->cropPercent = $cropPercent;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getCropPercent()
    {
        return $this->cropPercent;
    }

    /**
     * @param $cropTop
     * @return $this
     */
    public function setCropTop($cropTop)
    {
        $this->cropTop = $cropTop;

        return $this;
    }

    /**
     * @return float
     */
    public function getCropTop()
    {
        return $this->cropTop;
    }

    /**
     * @param $cropWidth
     * @return $this
     */
    public function setCropWidth($cropWidth)
    {
        $this->cropWidth = $cropWidth;

        return $this;
    }

    /**
     * @return float
     */
    public function getCropWidth()
    {
        return $this->cropWidth;
    }

    /**
     * @param array $hotspots
     */
    public function setHotspots($hotspots)
    {
        $this->hotspots = $hotspots;
    }

    /**
     * @return array
     */
    public function getHotspots()
    {
        return $this->hotspots;
    }

    /**
     * @param array $marker
     */
    public function setMarker($marker)
    {
        $this->marker = $marker;
    }

    /**
     * @return array
     */
    public function getMarker()
    {
        return $this->marker;
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
     * @param array $idMapping
     */
    public function rewriteIds($idMapping)
    {
        if (array_key_exists("asset", $idMapping) and array_key_exists($this->getId(), $idMapping["asset"])) {
            $this->setId($idMapping["asset"][$this->getId()]);

            // reset marker & hotspot information
            $this->setHotspots([]);
            $this->setMarker([]);
            $this->setCropPercent(false);
            $this->setImage(null);
        }
    }

    /**
     *
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ["image"];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
}
