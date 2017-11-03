<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\Condition;

use GeoIp2\Model\City;
use Pimcore\Targeting\DataProvider\GeoIp;
use Pimcore\Targeting\Model\VisitorInfo;

class GeoPoint implements DataProviderDependentConditionInterface
{
    /**
     * @var float
     */
    private $longitude;

    /**
     * @var float
     */
    private $latitude;

    /**
     * @var int
     */
    private $radius;

    public function __construct(float $longitude = null, float $latitude = null, int $radius = null)
    {
        $this->longitude = $longitude;
        $this->latitude  = $latitude;
        $this->radius    = $radius;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static(
            $config['longitude'] ?? null,
            $config['latitude'] ?? null,
            $config['radius'] ?? null
        );
    }

    /**
     * @inheritDoc
     */
    public function getDataProviderKeys(): array
    {
        return [GeoIp::PROVIDER_KEY];
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return !empty($this->longitude) && !empty($this->latitude) && !empty($this->radius);
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        /** @var City $city */
        $city = $visitorInfo->get(GeoIp::PROVIDER_KEY);

        if (!$city) {
            return false;
        }

        // TODO calculate distance
        return false;
    }
}
