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
use Location\Coordinate;
use Location\Distance\Haversine;
use Pimcore\Targeting\Condition\Traits\VariableConditionTrait;
use Pimcore\Targeting\DataProvider\GeoIp;
use Pimcore\Targeting\Model\VisitorInfo;

class GeoPoint implements DataProviderDependentConditionInterface, VariableConditionInterface
{
    use VariableConditionTrait;

    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    /**
     * @var int
     */
    private $radius;

    public function __construct(float $latitude = null, float $longitude = null, int $radius = null)
    {
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
        $this->radius    = $radius;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static(
            $config['latitude'] ?? null,
            $config['longitude'] ?? null,
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
        return !empty($this->latitude) && !empty($this->longitude) && !empty($this->radius);
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        /** @var City $city */
        $city = $visitorInfo->get(GeoIp::PROVIDER_KEY);

        if (!$city || empty($city->location->latitude) || empty($city->location->longitude)) {
            return false;
        }

        $distance = $this->calculateDistance(
            (float)$this->latitude, (float)$this->longitude,
            (float)$city->location->latitude, (float)$city->location->longitude
        );

        if ($distance < ($this->radius * 1000)) {
            $this->setMatchedVariables([
                'latitude'  => (float)$city->location->latitude,
                'longitude' => (float)$city->location->longitude
            ]);

            return true;
        }

        return false;
    }

    private function calculateDistance(float $latA, float $longA, float $latB, float $longB): float
    {
        $coordA = new Coordinate($latA, $longA);
        $coordB = new Coordinate($latB, $longB);

        $calculator = new Haversine();
        $distance   = $calculator->getDistance($coordA, $coordB);

        return $distance;
    }
}
