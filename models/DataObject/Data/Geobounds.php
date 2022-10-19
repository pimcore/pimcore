<?php
declare(strict_types=1);

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

class Geobounds implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var GeoCoordinates|null
     */
    protected ?GeoCoordinates $northEast;

    /**
     * @var GeoCoordinates|null
     */
    protected ?GeoCoordinates $southWest;

    /**
     * @param GeoCoordinates|null $northEast
     * @param GeoCoordinates|null $southWest
     */
    public function __construct(GeoCoordinates $northEast = null, GeoCoordinates $southWest = null)
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
     * @return GeoCoordinates|null
     */
    public function getNorthEast(): ?GeoCoordinates
    {
        return $this->northEast;
    }

    /**
     * @param GeoCoordinates $northEast
     *
     * @return $this
     */
    public function setNorthEast(GeoCoordinates $northEast): static
    {
        $this->northEast = $northEast;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return GeoCoordinates|null
     */
    public function getSouthWest(): ?GeoCoordinates
    {
        return $this->southWest;
    }

    /**
     * @param GeoCoordinates $southWest
     *
     * @return $this
     */
    public function setSouthWest(GeoCoordinates $southWest): static
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
