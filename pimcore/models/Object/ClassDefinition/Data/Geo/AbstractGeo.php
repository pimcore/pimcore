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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data\Geo;

use Pimcore\Model;

abstract class AbstractGeo extends Model\Object\ClassDefinition\Data
{
    /**
     * @var float
     */
    public $lat = 0.0;

    /**
     * @var float
     */
    public $lng = 0.0;

    /**
     * @var integer
     */
    public $zoom = 1;

    /**
     * @var string
     */
    public $mapType = 'roadmap';

    public function getLat()
    {
        return $this->lat;
    }

    public function setLat($lat)
    {
        $this->lat = (float) $lat;

        return $this;
    }

    public function getLng()
    {
        return $this->lng;
    }

    public function setLng($lng)
    {
        $this->lng = (float) $lng;

        return $this;
    }

    public function getZoom()
    {
        return $this->zoom;
    }

    public function setZoom($zoom)
    {
        $this->zoom = (int) $zoom;

        return $this;
    }

    public function getMapType()
    {
        return $this->mapType;
    }

    public function setMapType($mapType)
    {
        $this->mapType = $mapType;

        return $this;
    }
}
