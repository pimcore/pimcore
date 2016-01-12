<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
