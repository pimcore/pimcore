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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;
use Pimcore\Tool\Serialize;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;

class Image extends Model\Document\Tag {

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
    public $hotspots = array();

    /**
     * @var array
     */
    public $marker = array();

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "image";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return array(
            "id" => $this->id,
            "alt" => $this->alt,
            "cropPercent" => $this->cropPercent,
            "cropWidth" => $this->cropWidth,
            "cropHeight" => $this->cropHeight,
            "cropTop" => $this->cropTop,
            "cropLeft" => $this->cropLeft,
            "hotspots" => $this->hotspots,
            "marker" => $this->marker
        );
    }

    /**
     *
     */
    public function getDataForResource() {

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

        $marker = $rewritePath($this->marker);
        $hotspots = $rewritePath($this->hotspots);

        return array(
            "id" => $this->id,
            "alt" => $this->alt,
            "cropPercent" => $this->cropPercent,
            "cropWidth" => $this->cropWidth,
            "cropHeight" => $this->cropHeight,
            "cropTop" => $this->cropTop,
            "cropLeft" => $this->cropLeft,
            "hotspots" => $hotspots,
            "marker" => $marker
        );
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return array
     */
    public function getDataEditmode() {
        if ($this->image instanceof Asset\Image) {

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

            $marker = $rewritePath($this->marker);
            $hotspots = $rewritePath($this->hotspots);

            return array(
                "id" => $this->id,
                "path" => $this->image->getPath() . $this->image->getFilename(),
                "alt" => $this->alt,
                "cropPercent" => $this->cropPercent,
                "cropWidth" => $this->cropWidth,
                "cropHeight" => $this->cropHeight,
                "cropTop" => $this->cropTop,
                "cropLeft" => $this->cropLeft,
                "hotspots" => $hotspots,
                "marker" => $marker
            );
        }
        return null;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend() {

        if (!is_array($this->options)) {
            $this->options = array();
        }

        if ($this->image instanceof Asset) {
            if ((isset($this->options["thumbnail"]) && $this->options["thumbnail"]) || $this->cropPercent) {
                // create a thumbnail first
                $autoName = false;

                $thumbConfig = $this->image->getThumbnailConfig($this->options["thumbnail"]);
                if(!$thumbConfig && $this->cropPercent) {
                    $thumbConfig = new Asset\Image\Thumbnail\Config();
                }

                if($this->cropPercent) {
                    $cropConfig = array(
                        "width" => $this->cropWidth,
                        "height" => $this->cropHeight,
                        "y" => $this->cropTop,
                        "x" => $this->cropLeft
                    );

                    $thumbConfig->addItemAt(0,"cropPercent", $cropConfig);

                    // also crop media query specific configs
                    if($thumbConfig->hasMedias()) {
                        foreach($thumbConfig->getMedias() as $mediaName => $mediaItems) {
                            $thumbConfig->addItemAt(0,"cropPercent", $cropConfig, $mediaName);
                        }
                    }

                    $autoName = true;
                }

                if($this->options["highResolution"] && $this->options["highResolution"] > 1) {
                    $thumbConfig->setHighResolution($this->options["highResolution"]);
                }

                // autogenerate a name for the thumbnail because it's different from the original
                if($autoName) {
                    $hash = md5(Serialize::serialize($thumbConfig->getItems()));
                    $thumbConfig->setName($thumbConfig->getName() . "_auto_" . $hash);
                }

                $imagePath = $this->image->getThumbnail($thumbConfig);
            } else {
                $imagePath = $this->image->getFullPath();
            }

            $altText = $this->alt;
            $titleText = $this->alt;
            if(empty($titleText)) {
                if($this->getImage()->getMetadata("title")) {
                    $titleText = $this->getImage()->getMetadata("title");
                }
            }
            if(empty($altText)) {
                if($this->getImage()->getMetadata("alt")) {
                    $altText = $this->getImage()->getMetadata("alt");
                } else {
                    $altText = $titleText;
                }
            }

            // get copyright from asset
            if($this->getImage()->getMetadata("copyright")) {
                if(!empty($altText)) {
                    $altText .= " | ";
                }
                if(!empty($titleText)) {
                    $titleText .= " | ";
                }
                $altText .= ("© " . $this->getImage()->getMetadata("copyright"));
                $titleText .= ("© " . $this->getImage()->getMetadata("copyright"));
            }

            $defaultAttributes = array(
                "alt" => $altText
            );
            if(!empty($titleText)) {
                $defaultAttributes["title"] = $titleText;
            }

            // add attributes to image
            $allowedAttributes = array("alt", "align", "border", "height", "hspace", "ismap", "longdesc", "usemap",
                "vspace", "width", "class", "dir", "id", "lang", "style", "title", "xml:lang", "onmouseover",
                "onabort", "onclick", "ondblclick", "onmousedown", "onmousemove", "onmouseout", "onmouseup",
                "onkeydown", "onkeypress", "onkeyup", "itemprop", "itemscope", "itemtype");

            $htmlEscapeAttributes = array("alt", "align", "border", "height", "hspace",  "longdesc", "usemap",
                "vspace", "width", "class", "dir", "id", "lang",  "title");


            $customAttributes = array();
            if(array_key_exists("attributes", $this->options) && is_array($this->options["attributes"])) {
                $customAttributes = $this->options["attributes"];
            }

            $availableAttribs = array_merge($this->options, $defaultAttributes, $customAttributes);

            $attribs = [];
            $attribsRaw = [];
            foreach ($availableAttribs as $key => $value) {
                if ((is_string($value) || is_numeric($value)) && (in_array($key, $allowedAttributes) || array_key_exists($key, $customAttributes))) {
                    $attribsRaw[$key] = $value;

                    if(in_array($key,$htmlEscapeAttributes)){
                        $value = htmlspecialchars($value);
                    }
                    $attribs[] = $key . '="' . $value . '"';
                }
            }

            if($imagePath instanceof Asset\Image\Thumbnail) {
                // thumbnail's HTML is always generated by the thumbnail itself
                return $imagePath->getHTML($attribsRaw);
            } else {
                return '<img src="' . $imagePath . '" ' . implode(" ", $attribs) . ' />';
            }
        }
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {

        if (strlen($data) > 2) {
            $data = Serialize::unserialize($data);
        }


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

        if(array_key_exists("marker",$data) && is_array($data["marker"]) && count($data["marker"]) > 0) {
            $data["marker"] = $rewritePath($data["marker"]);
        }

        if(array_key_exists("hotspots",$data) && is_array($data["hotspots"]) && count($data["hotspots"]) > 0) {
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

        try {
            $this->image = Asset\Image::getById($this->id);
        }
        catch (\Exception $e) {
        }
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {

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

        $this->id = $data["id"];
        $this->alt = $data["alt"];
        $this->cropPercent = $data["cropPercent"];
        $this->cropWidth = $data["cropWidth"];
        $this->cropHeight = $data["cropHeight"];
        $this->cropTop = $data["cropTop"];
        $this->cropLeft = $data["cropLeft"];
        $this->marker = $data["marker"];
        $this->hotspots = $data["hotspots"];

        $this->image = Asset\Image::getById($this->id);
        return $this;
    }

    /*
      * @return string
      */
    public function getText() {
        return $this->alt;
    }

    /*
      * @return string
      */
    public function getAlt() {
        return $this->getText();
    }

    /*
      * @return string
      */
    public function getSrc() {
        if ($this->image instanceof Asset) {
            return $this->image->getFullPath();
        }
        return "";
    }

    /**
     * @return Asset\Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * @param Asset\Image $image
     * @return Model\Document\Tag\Image
     */
    public function setImage($image)
    {
        $this->image = $image;
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
     * @return Asset\Image\Thumbnail|string
     */
    public function getThumbnail($conf) {
        if ($this->image instanceof Asset) {

            $thumbConfig = $this->image->getThumbnailConfig($conf);
            if($thumbConfig && $this->cropPercent) {
                $thumbConfig->addItemAt(0,"cropPercent", array(
                    "width" => $this->cropWidth,
                    "height" => $this->cropHeight,
                    "y" => $this->cropTop,
                    "x" => $this->cropLeft
                ));
                $hash = md5(Serialize::serialize($thumbConfig->getItems()));
                $thumbConfig->setName($thumbConfig->getName() . "_auto_" . $hash);
            }

            return $this->image->getThumbnail($thumbConfig);
        }
        return "";
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        if ($this->image instanceof Asset\Image) {
            return false;
        }
        return true;
    }


    /**
     * @param $ownerDocument
     * @param array $blockedTags
     */
    public function getCacheTags($ownerDocument, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if ($this->image instanceof Asset) {
            if (!array_key_exists($this->image->getCacheTag(), $tags)) {
                $tags = $this->image->getCacheTags($tags);
            }
        }

        $getMetaDataCacheTags = function ($data, $tags) {

            if(!is_array($data)) {
                return $tags;
            }

            foreach ($data as $element) {
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

        $tags = $getMetaDataCacheTags($this->marker, $tags);
        $tags = $getMetaDataCacheTags($this->hotspots, $tags);

        return $tags;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        if ($this->image instanceof Asset\Image) {
            $key = "asset_" . $this->image->getId();

            $dependencies[$key] = array(
                "id" => $this->image->getId(),
                "type" => "asset"
            );
        }

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

        $dependencies = $getMetaDataDependencies($this->marker, $dependencies);
        $dependencies = $getMetaDataDependencies($this->hotspots, $dependencies);

        return $dependencies;
    }

    /**
     * @param Model\Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        $data = $wsElement->value;
        if ($data->id !==null) {
            $this->alt = $data->alt;
            $this->id = $data->id;

            if ($idMapper) {
                $this->id = $idMapper->getMappedId("asset", $data->id);
            }

            if (is_numeric($this->id)) {
                $this->image = Asset\Image::getById($this->id);
                if (!$this->image instanceof Asset\Image) {
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
     * @return void
     */
    public function rewriteIds($idMapping) {
        if(array_key_exists("asset", $idMapping) and array_key_exists($this->getId(), $idMapping["asset"])) {
            $this->setId($idMapping["asset"][$this->getId()]);

            // reset marker & hotspot information
            $this->setHotspots(array());
            $this->setMarker(array());
            $this->setCropPercent(false);
            $this->setImage(null);
        }
    }
}
