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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Geo;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AfterDecryptionUnmarshallerInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\BeforeEncryptionMarshallerInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Traits\DataHeightTrait;
use Pimcore\Model\DataObject\Traits\DataWidthTrait;
use Pimcore\Tool\Serialize;

abstract class AbstractGeo extends Data implements TypeDeclarationSupportInterface, BeforeEncryptionMarshallerInterface, AfterDecryptionUnmarshallerInterface
{
    use DataHeightTrait;
    use DataWidthTrait;

    /**
     * @internal
     *
     */
    public float $lat = 0.0;

    /**
     * @internal
     *
     */
    public float $lng = 0.0;

    /**
     * @internal
     *
     */
    public int $zoom = 1;

    /**
     * @internal
     *
     */
    public string $mapType = 'roadmap';

    public function getLat(): float
    {
        return $this->lat;
    }

    /**
     * @return $this
     */
    public function setLat(float $lat): static
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    /**
     * @return $this
     */
    public function setLng(float $lng): static
    {
        $this->lng = $lng;

        return $this;
    }

    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * @return $this
     */
    public function setZoom(int $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function marshalBeforeEncryption(mixed $value, Concrete $object = null, array $params = []): mixed
    {
        return Serialize::serialize($value);
    }

    public function unmarshalAfterDecryption(mixed $value, Concrete $object = null, array $params = []): mixed
    {
        return Serialize::unserialize($value);
    }
}
