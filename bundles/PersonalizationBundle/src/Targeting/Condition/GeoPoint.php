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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Location\Coordinate;
use Location\Distance\Haversine;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\GeoLocation;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProviderDependentInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\GeoLocation as GeoLocationModel;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

class GeoPoint extends AbstractVariableCondition implements DataProviderDependentInterface
{
    private ?float $latitude = null;

    private ?float $longitude = null;

    private ?int $radius = null;

    public function __construct(float $latitude = null, float $longitude = null, int $radius = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->radius = $radius;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(array $config): static
    {
        return new static(
            $config['latitude'] ? (float)$config['latitude'] : null,
            $config['longitude'] ? (float)$config['longitude'] : null,
            $config['radius'] ? (int)$config['radius'] : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProviderKeys(): array
    {
        return [GeoLocation::PROVIDER_KEY];
    }

    /**
     * {@inheritdoc}
     */
    public function canMatch(): bool
    {
        return !empty($this->latitude) && !empty($this->longitude) && !empty($this->radius);
    }

    /**
     * {@inheritdoc}
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        /** @var GeoLocationModel|null $location */
        $location = $visitorInfo->get(GeoLocation::PROVIDER_KEY);

        if (!$location) {
            return false;
        }

        $distance = $this->calculateDistance(
            $this->latitude, $this->longitude,
            $location->getLatitude(), $location->getLongitude()
        );

        if ($distance < ($this->radius * 1000)) {
            $this->setMatchedVariables([
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
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
        $distance = $calculator->getDistance($coordA, $coordB);

        return $distance;
    }
}
