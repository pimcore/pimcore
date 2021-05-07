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

class GeoCoordinates extends Geopoint
{
    /**
     * @param float|null $latitude
     * @param float|null $longitude
     */
    public function __construct($latitude = null, $longitude = null)
    {
        parent::__construct($longitude, $latitude);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->latitude . '; ' . $this->longitude;
    }
}
