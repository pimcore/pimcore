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

class Object_Data_Geopoint {


    /**
     * @var double
     */
    public $longitude;

    /**
     * @var double
     */
    public $latitude;


    public function __construct($longitude = null, $latitude = null) {
        if ($longitude !== null) {
            $this->setLongitude($longitude);
        }
        if ($latitude !== null) {
            $this->setLatitude($latitude);
        }
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = (double) $longitude;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function setLatitude($latitude) {
        $this->latitude = (double) $latitude;
    }

    public function __toString() {
        return $this->longitude . "; " . $this->latitude;
    }
}
