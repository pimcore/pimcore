<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\Model;

class GeoLocation
{
    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    /**
     * @var float|null
     */
    private $altitude;

    public function __construct(float $latitude, float $longitude, float $altitude = null)
    {
        if (!($latitude >= -90 && $latitude <= 90)) {
            throw new \InvalidArgumentException('Latitude is invalid');
        }

        if (!($longitude >= -180 && $longitude <= 180)) {
            throw new \InvalidArgumentException('Longitude is invalid');
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->altitude = $altitude;
    }

    public static function build($latitude, $longitude, $altitude = null): self
    {
        return new self(
            (float)$latitude,
            (float)$longitude,
            null !== $altitude ? (float)$altitude : null
        );
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @return float|null
     */
    public function getAltitude()
    {
        return $this->altitude;
    }
}
