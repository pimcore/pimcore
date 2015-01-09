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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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
