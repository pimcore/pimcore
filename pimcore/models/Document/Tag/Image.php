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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Tag_Image extends Document_Tag {

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
     * @var Asset_Image
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
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "image";
    }

    /**
     * @see Document_Tag_Interface::getData
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
            "cropLeft" => $this->cropLeft
        );
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return array
     */
    public function getDataEditmode() {
        if ($this->image instanceof Asset_Image) {
            return array(
                "id" => $this->id,
                "path" => $this->image->getPath() . $this->image->getFilename(),
                "alt" => $this->alt,
                "cropPercent" => $this->cropPercent,
                "cropWidth" => $this->cropWidth,
                "cropHeight" => $this->cropHeight,
                "cropTop" => $this->cropTop,
                "cropLeft" => $this->cropLeft
            );
        }
        return null;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {
        if ($this->image instanceof Asset) {

            $thumbnailInUse = false;
            if ($this->options["thumbnail"]) {
                // create a thumbnail first

                $thumbConfig = $this->image->getThumbnailConfig($this->options["thumbnail"]);
                if($this->cropPercent) {
                    $thumbConfig->addItemAt(0,"cropPercent", array(
                        "width" => $this->cropWidth,
                        "height" => $this->cropHeight,
                        "y" => $this->cropTop,
                        "x" => $this->cropLeft
                    ));
                    $hash = md5(Pimcore_Tool_Serialize::serialize($thumbConfig->getItems()));
                    $thumbConfig->setName("auto_" . $hash);
                }

                if ($imagePath = $this->image->getThumbnail($thumbConfig)) {
                    list($width, $height) = getimagesize(PIMCORE_DOCUMENT_ROOT . $imagePath);
                    $thumbnailInUse = true;
                }
            }

            if (!$thumbnailInUse) {
                $imagePath = $this->image->getPath() . $this->image->getFilename();

                // width & height
                $options = $this->getOptions();
                if ($options["width"]) {
                    $width = $options["width"];
                }
                if ($options["height"]) {
                    $height = $options["height"];
                }
            }

            // add attributes to image
            $allowedAttributes = array("alt", "align", "border", "height", "hspace", "ismap", "longdesc", "usemap",
                "vspace", "width", "class", "dir", "id", "lang", "style", "title", "xml:lang", "onmouseover",
                "onabort", "onclick", "ondblclick", "onmousedown", "onmousemove", "onmouseout", "onmouseup",
                "onkeydown", "onkeypress", "onkeyup", "itemprop", "itemscope", "itemtype");

            $htmlEscapeAttributes = array("alt", "align", "border", "height", "hspace",  "longdesc", "usemap",
                "vspace", "width", "class", "dir", "id", "lang",  "title");

            $defaultAttributes = array(
                "alt" => $this->alt,
                "title" => $this->alt,
                "height" => $height,
                "width" => $width
            );

            if (!is_array($this->options)) {
                $this->options = array();
            }

            $customAttributes = array();
            if(array_key_exists("attributes", $this->options) && is_array($this->options["attributes"])) {
                $customAttributes = $this->options["attributes"];
            }

            $availableAttribs = array_merge($defaultAttributes, $customAttributes, $this->options);

            foreach ($availableAttribs as $key => $value) {
                if ((is_string($value) || is_numeric($value)) && (in_array($key, $allowedAttributes) || array_key_exists($key, $customAttributes))) {
                    if(in_array($key,$htmlEscapeAttributes)){
                        $value = htmlspecialchars($value);
                    }
                    $attribs[] = $key . '="' . $value . '"';
                }
            }

            return '<img src="' . $imagePath . '" ' . implode(" ", $attribs) . ' />';
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {

        if (strlen($data) > 2) {
            $data = Pimcore_Tool_Serialize::unserialize($data);
        }

        $this->id = $data["id"];
        $this->alt = $data["alt"];
        $this->cropPercent = $data["cropPercent"];
        $this->cropWidth = $data["cropWidth"];
        $this->cropHeight = $data["cropHeight"];
        $this->cropTop = $data["cropTop"];
        $this->cropLeft = $data["cropLeft"];

        try {
            $this->image = Asset_Image::getById($this->id);
        }
        catch (Exception $e) {
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->id = $data["id"];
        $this->alt = $data["alt"];
        $this->cropPercent = $data["cropPercent"];
        $this->cropWidth = $data["cropWidth"];
        $this->cropHeight = $data["cropHeight"];
        $this->cropTop = $data["cropTop"];
        $this->cropLeft = $data["cropLeft"];

        $this->image = Asset_Image::getById($this->id);
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
     * @return Asset_Image
     */
    public function getImage() {
        return $this->image;
    }

    /*
      * @return string
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
                $hash = md5(Pimcore_Tool_Serialize::serialize($thumbConfig->getItems()));
                $thumbConfig->setName("auto_" . $hash);
            }

            return $this->image->getThumbnail($thumbConfig);
        }
        return "";
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        if ($this->image instanceof Asset_Image) {
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

        return $tags;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        if ($this->image instanceof Asset_Image) {
            $key = "asset_" . $this->image->getId();

            $dependencies[$key] = array(
                "id" => $this->image->getId(),
                "type" => "asset"
            );
        }

        return $dependencies;
    }


    /**
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $data
     * @return void
     */
    public function getFromWebserviceImport($wsElement) {
        $data = $wsElement->value;
        if ($data->id !==null) {

            $this->alt = $data->alt;
            $this->id = $data->id;
            if (is_numeric($this->id)) {
                $this->image = Asset_Image::getById($this->id);
                if (!$this->image instanceof Asset_Image) {
                    throw new Exception("cannot get values from web service import - referenced image with id [ " . $this->id . " ] is unknown");
                }
            } else {
                throw new Exception("cannot get values from web service import - id is not valid");
            }


        }


    }

    /**
     * @param float $cropHeight
     */
    public function setCropHeight($cropHeight)
    {
        $this->cropHeight = $cropHeight;
    }

    /**
     * @return float
     */
    public function getCropHeight()
    {
        return $this->cropHeight;
    }

    /**
     * @param float $cropLeft
     */
    public function setCropLeft($cropLeft)
    {
        $this->cropLeft = $cropLeft;
    }

    /**
     * @return float
     */
    public function getCropLeft()
    {
        return $this->cropLeft;
    }

    /**
     * @param boolean $cropPercent
     */
    public function setCropPercent($cropPercent)
    {
        $this->cropPercent = $cropPercent;
    }

    /**
     * @return boolean
     */
    public function getCropPercent()
    {
        return $this->cropPercent;
    }

    /**
     * @param float $cropTop
     */
    public function setCropTop($cropTop)
    {
        $this->cropTop = $cropTop;
    }

    /**
     * @return float
     */
    public function getCropTop()
    {
        return $this->cropTop;
    }

    /**
     * @param float $cropWidth
     */
    public function setCropWidth($cropWidth)
    {
        $this->cropWidth = $cropWidth;
    }

    /**
     * @return float
     */
    public function getCropWidth()
    {
        return $this->cropWidth;
    }

}
