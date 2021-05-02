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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Geo;

use Pimcore\Model;
use Pimcore\Tool\Serialize;

abstract class AbstractGeo extends Model\DataObject\ClassDefinition\Data implements Model\DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface
{
    /**
     * @internal
     *
     * @var float
     */
    public $lat = 0.0;

    /**
     * @internal
     *
     * @var float
     */
    public $lng = 0.0;

    /**
     * @internal
     *
     * @var int
     */
    public $zoom = 1;

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var string
     */
    public $mapType = 'roadmap';

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     *
     * @return $this
     */
    public function setLat($lat)
    {
        $this->lat = (float) $lat;

        return $this;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     *
     * @return $this
     */
    public function setLng($lng)
    {
        $this->lng = (float) $lng;

        return $this;
    }

    /**
     * @return int
     */
    public function getZoom()
    {
        return $this->zoom;
    }

    /**
     * @param int $zoom
     *
     * @return $this
     */
    public function setZoom($zoom)
    {
        $this->zoom = (int) $zoom;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @param mixed $value
     * @param Model\DataObject\AbstractObject|null $object
     * @param array $params
     *
     * @return string
     */
    public function marshalBeforeEncryption($value, $object = null, $params = [])
    {
        return Serialize::serialize($value);
    }

    /**
     * @param string $value
     * @param Model\DataObject\AbstractObject|null $object
     * @param array $params
     *
     * @return mixed
     */
    public function unmarshalAfterDecryption($value, $object = null, $params = [])
    {
        return Serialize::unserialize($value);
    }
}
