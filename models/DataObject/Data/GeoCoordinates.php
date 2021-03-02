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
