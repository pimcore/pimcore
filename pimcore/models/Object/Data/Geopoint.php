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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data;

class Geopoint
{

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
    public function __construct($longitude = null, $latitude = null)
    {
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
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = (double) $longitude;

        return $this;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = (double) $latitude;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->longitude . "; " . $this->latitude;
    }
}
