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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Geo;

use Pimcore\Model;
use Pimcore\Tool\Serialize;

abstract class AbstractGeo extends Model\DataObject\ClassDefinition\Data implements Model\DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface
{
    use Model\DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * @var float
     */
    public $lat = 0.0;

    /**
     * @var float
     */
    public $lng = 0.0;

    /**
     * @var int
     */
    public $zoom = 1;

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
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
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
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
