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

class Geobounds {

    /**
     * @var Geopoint
     */
    public $nortEast;

    /**
     * @var Geopoint
     */
    public $southWest;

    /**
     * @param null $nortEast
     * @param null $southWest
     */
    public function __construct($nortEast = null, $southWest = null) {
        if ($nortEast) {
            $this->setNorthEast($nortEast);
        }
        if ($southWest) {
            $this->setSouthWest($southWest);
        }
    }

    /**
     * @return Geopoint
     */
    public function getNorthEast() {
        return $this->nortEast;
    }

    /**
     * @param $nortEast
     * @return $this
     */
    public function setNorthEast($nortEast) {
        $this->nortEast = $nortEast;
        return $this;
    }

    /**
     * @return Geopoint
     */
    public function getSouthWest() {
        return $this->southWest;
    }

    /**
     * @param $southWest
     * @return $this
     */
    public function setSouthWest($southWest) {
        $this->southWest = $southWest;
        return $this;
    }

    /**
     * @return string
     */
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
