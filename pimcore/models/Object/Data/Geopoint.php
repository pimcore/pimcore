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

class Geopoint {

    /**
     * @var double
     */
    public $longitude;

    /**
     * @var double
     */
    public $latitude;

    /**
     * @param null $longitude
     * @param null $latitude
     */
    public function __construct($longitude = null, $latitude = null) {
        if ($longitude !== null) {
            $this->setLongitude($longitude);
        }
        if ($latitude !== null) {
            $this->setLatitude($latitude);
        }
    }

    /**
     * @return float
     */
    public function getLongitude() {
        return $this->longitude;
    }

    /**
     * @param $longitude
     * @return $this
     */
    public function setLongitude($longitude) {
        $this->longitude = (double) $longitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getLatitude() {
        return $this->latitude;
    }

    /**
     * @param $latitude
     * @return $this
     */
    public function setLatitude($latitude) {
        $this->latitude = (double) $latitude;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->longitude . "; " . $this->latitude;
    }
}
