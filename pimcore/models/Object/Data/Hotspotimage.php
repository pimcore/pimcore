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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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


    public function __construct($image, $hotspots) {
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
    }

    public function setHotspots($hotspots) {
        $this->hotspots = $hotspots;
    }

    public function getHotspots() {
        return $this->hotspots;
    }

    public function setImage($image) {
        $this->image = $image;
    }

    public function getImage() {
        return $this->image;
    }

    public function __toString() {
        if($this->image) {
            return $this->image->__toString();
        }
        return ""; 
    }


}
