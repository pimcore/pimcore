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
 * @package    Object
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Data_Hotspotimage {


    /**
     * @var Asset_Image
     */
    public $image;

    /**
     * @var array[]
     */
    public $hotspots;

    /**
     * @var array[]
     */
    public $marker;

    /**
     * @var array[]
     */
    public $crop;


    public function __construct($image, $hotspots, $marker = array(), $crop = array()) {
        if($image instanceof Asset_Image) {
            $this->image = $image;
        } else {
            $this->image = Asset_Image::getById($image);
        }

        if(is_array($hotspots)) {
            $this->hotspots = array();
            foreach($hotspots as $h) {
                $this->hotspots[] = $h;
            }
        }

        if(is_array($marker)) {
            $this->marker = array();
            foreach($marker as $m) {
                $this->marker[] = $m;
            }
        }

        if(is_array($crop)) {
            $this->crop = $crop;
        }
    }

    public function setHotspots($hotspots) {
        $this->hotspots = $hotspots;
        return $this;
    }

    public function getHotspots() {
        return $this->hotspots;
    }

    public function setMarker($marker) {
        $this->marker = $marker;
        return $this;
    }

    public function getMarker() {
        return $this->marker;
    }

    /**
     * @param \array[] $crop
     */
    public function setCrop($crop)
    {
        $this->crop = $crop;
    }

    /**
     * @return \array[]
     */
    public function getCrop()
    {
        return $this->crop;
    }

    public function setImage($image) {
        $this->image = $image;
        return $this;
    }

    public function getImage() {
        return $this->image;
    }

    public function getThumbnail($thumbnailName = null) {

        if(!$this->getImage()) {
            return "";
        }

        $crop = null;
        if(is_array($this->getCrop())) {
            $crop = $this->getCrop();
        }

        $thumbConfig = $this->getImage()->getThumbnailConfig($thumbnailName);
        if(!$thumbConfig && $crop) {
            $thumbConfig = new Asset_Image_Thumbnail_Config();
        }

        if($crop) {
            $thumbConfig->addItemAt(0,"cropPercent", array(
                "width" => $crop["cropWidth"],
                "height" => $crop["cropHeight"],
                "y" => $crop["cropTop"],
                "x" => $crop["cropLeft"]
            ));

            $hash = md5(Pimcore_Tool_Serialize::serialize($thumbConfig->getItems()));
            $thumbConfig->setName("auto_" . $hash);
        }

        $imagePath = $this->getImage()->getThumbnail($thumbConfig);
        return $imagePath;
    }

    public function __toString() {
        if($this->image) {
            return $this->image->__toString();
        }
        return ""; 
    }
}
