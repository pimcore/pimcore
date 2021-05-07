<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

/**
 * @deprecated Replace this with GeoCoordinates until Pimcore 10 as the constructor will change
 */
class Geopoint implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @param float|null $longitude
     * @param float|null $latitude
     */
    public function __construct($longitude = null, $latitude = null)
    {
        if ($longitude !== null) {
            $this->setLongitude($longitude);
        }
        if ($latitude !== null) {
            $this->setLatitude($latitude);
        }
        $this->markMeDirty();
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     *
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $longitude = (float)$longitude;

        if ($this->longitude != $longitude) {
            $this->longitude = $longitude;
            $this->markMeDirty();
        }

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
     * @param float $latitude
     *
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $latitude = (float)$latitude;
        if ($this->latitude != $latitude) {
            $this->latitude = $latitude;
            $this->markMeDirty();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->longitude . '; ' . $this->latitude;
    }
}
