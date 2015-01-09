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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Data;

use Pimcore\Model;
use Pimcore\Model\Asset;

class Hotspotimage {

    /**
     * @var Asset\Image
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

    /**
     * @param null $image
     * @param array $hotspots
     * @param array $marker
     * @param array $crop
     */
    public function __construct($image = null, $hotspots = [], $marker = array(), $crop = array()) {
        if($image instanceof Asset\Image) {
            $this->image = $image;
        } else if (is_numeric($image)){
            $this->image = Asset\Image::getById($image);
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

    /**
     * @param $hotspots
     * @return $this
     */
    public function setHotspots($hotspots) {
        $this->hotspots = $hotspots;
        return $this;
    }

    /**
     * @return array|\array[]
     */
    public function getHotspots() {
        return $this->hotspots;
    }

    /**
     * @param $marker
     * @return $this
     */
    public function setMarker($marker) {
        $this->marker = $marker;
        return $this;
    }

    /**
     * @return array|\array[]
     */
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

    /**
     * @param $image
     * @return $this
     */
    public function setImage($image) {
        $this->image = $image;
        return $this;
    }

    /**
     * @return Asset|Asset\Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * @param null $thumbnailName
     * @return mixed
     */
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
            $thumbConfig = new Asset\Image\Thumbnail\Config();
        }

        if($crop) {
            $thumbConfig->addItemAt(0,"cropPercent", array(
                "width" => $crop["cropWidth"],
                "height" => $crop["cropHeight"],
                "y" => $crop["cropTop"],
                "x" => $crop["cropLeft"]
            ));

            $hash = md5(\Pimcore\Tool\Serialize::serialize($thumbConfig->getItems()));
            $thumbConfig->setName($thumbConfig->getName() . "_auto_" . $hash);
        }

        return $this->getImage()->getThumbnail($thumbConfig);
    }

    /**
     * @return string
     */
    public function __toString() {
        if($this->image) {
            return $this->image->__toString();
        }
        return ""; 
    }
}
