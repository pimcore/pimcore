<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data;

class Geobounds
{
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
    public function __construct($nortEast = null, $southWest = null)
    {
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
    public function getNorthEast()
    {
        return $this->nortEast;
    }

    /**
     * @param $nortEast
     *
     * @return $this
     */
    public function setNorthEast($nortEast)
    {
        $this->nortEast = $nortEast;

        return $this;
    }

    /**
     * @return Geopoint
     */
    public function getSouthWest()
    {
        return $this->southWest;
    }

    /**
     * @param $southWest
     *
     * @return $this
     */
    public function setSouthWest($southWest)
    {
        $this->southWest = $southWest;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = '';
        if ($this->nortEast) {
            $string .= $this->nortEast;
        }
        if (!empty($string)) {
            $string .= ' - ';
        }
        if ($this->nortEast) {
            $string .= $this->nortWest;
        }

        return $string;
    }
}
