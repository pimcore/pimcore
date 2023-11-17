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

class GeoCoordinates implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected ?float $longitude = null;

    protected ?float $latitude = null;

    public function __construct(?float $latitude = null, ?float $longitude = null)
    {
        if ($latitude !== null) {
            $this->setLatitude($latitude);
        }

        if ($longitude !== null) {
            $this->setLongitude($longitude);
        }

        $this->markMeDirty();
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * @return $this
     */
    public function setLongitude(?float $longitude): static
    {
        if ($this->longitude !== $longitude) {
            $this->longitude = $longitude;
            $this->markMeDirty();
        }

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @return $this
     */
    public function setLatitude(?float $latitude): static
    {
        if ($this->latitude !== $latitude) {
            $this->latitude = $latitude;
            $this->markMeDirty();
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->latitude . '; ' . $this->longitude;
    }
}
