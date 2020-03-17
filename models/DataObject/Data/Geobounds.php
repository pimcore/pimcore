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

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class Geobounds implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var Geopoint
     */
    protected $northEast;

    /**
     * @var Geopoint
     */
    protected $southWest;

    /**
     * @param Geopoint|null $northEast
     * @param Geopoint|null $southWest
     */
    public function __construct($northEast = null, $southWest = null)
    {
        if ($northEast) {
            $this->setNorthEast($northEast);
        }
        if ($southWest) {
            $this->setSouthWest($southWest);
        }
        $this->markMeDirty();
    }

    /**
     * @return Geopoint
     */
    public function getNorthEast()
    {
        return $this->northEast;
    }

    /**
     * @param Geopoint $northEast
     *
     * @return $this
     */
    public function setNorthEast($northEast)
    {
        $this->northEast = $northEast;
        $this->markMeDirty();

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
     * @param Geopoint $southWest
     *
     * @return $this
     */
    public function setSouthWest($southWest)
    {
        $this->southWest = $southWest;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = '';
        if ($this->northEast) {
            $string .= $this->northEast;
        }
        if (!empty($string)) {
            $string .= ' - ';
        }
        if ($this->southWest) {
            $string .= $this->southWest;
        }

        return $string;
    }
}
