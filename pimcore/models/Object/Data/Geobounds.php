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

class Object_Data_Geobounds {


    /**
     * @var Object_Data_Geopoint
     */
    public $nortEast;

    /**
     * @var Object_Data_Geopoint
     */
    public $southWest;


    public function __construct($nortEast = null, $southWest = null) {
        if ($nortEast) {
            $this->setNorthEast($nortEast);
        }
        if ($southWest) {
            $this->setSouthWest($southWest);
        }
    }

    public function getNorthEast() {
        return $this->nortEast;
    }

    public function setNorthEast($nortEast) {
        $this->nortEast = $nortEast;
    }

    public function getSouthWest() {
        return $this->southWest;
    }

    public function setSouthWest($southWest) {
        $this->southWest = $southWest;
    }

    public function __toString() {
        $string = "";
        if($this->nortEast) {
            $string .= $this->nortEast;
        }
        if(!empty($string)) {
            $string .= " - ";
        }
        if($this->nortEast) {
            $string .= $this->nortWest;
        }

        return $string;
    }
}
