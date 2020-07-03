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

namespace Pimcore\Targeting\DataProvider;

use Pimcore\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Targeting\Model\GeoLocation as GeoLocationModel;
use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Loads geolocation (only coordinates and optional altitude) from either
 * browser geolocation delivered as cookie or from geoip lookup as fallback.
 */
class GeoLocation implements DataProviderInterface
{
    const PROVIDER_KEY = 'geolocation';

    const COOKIE_NAME_GEOLOCATION = '_pc_tgl';

    /**
     * @var GeoIp
     */
    private $geoIpDataProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        GeoIp $geoIpProvider,
        LoggerInterface $logger
    ) {
        $this->geoIpDataProvider = $geoIpProvider;
        $this->logger = $logger;
    }

    public function load(VisitorInfo $visitorInfo)
    {
        $location = $this->loadLocation($visitorInfo);
        $location = $this->handleOverrides($visitorInfo->getRequest(), $location);

        $visitorInfo->set(
            self::PROVIDER_KEY,
            $location
        );
    }

    private function handleOverrides(Request $request, GeoLocationModel $location = null)
    {
        $overrides = OverrideAttributeResolver::getOverrideValue($request, 'location');
        if (empty($overrides)) {
            return $location;
        }

        $overrides = array_filter($overrides, function ($key) {
            return in_array($key, ['latitude', 'longitude', 'altitude']);
        }, ARRAY_FILTER_USE_KEY);

        $data = array_merge([
            'latitude' => $location ? $location->getLatitude() : null,
            'longitude' => $location ? $location->getLongitude() : null,
            'altitude' => $location ? $location->getAltitude() : null,
        ], $overrides);

        if (null !== $data['latitude'] && null !== $data['longitude']) {
            return GeoLocationModel::build(
                $data['latitude'],
                $data['longitude'],
                $data['altitude']
            );
        }
    }

    private function loadLocation(VisitorInfo $visitorInfo)
    {
        $location = $this->loadGeolocationData($visitorInfo);
        if ($location) {
            return $location;
        }

        // no location found - try to load from GeoIP
        return $this->loadGeoIpData($visitorInfo);
    }

    private function loadGeolocationData(VisitorInfo $visitorInfo)
    {
        // inform frontend that geolocation is wanted - this will work after the first request
        $visitorInfo->addFrontendDataProvider(self::PROVIDER_KEY);

        $request = $visitorInfo->getRequest();

        if (!$request->cookies->has(self::COOKIE_NAME_GEOLOCATION)) {
            return null;
        }

        $cookie = $request->cookies->get(self::COOKIE_NAME_GEOLOCATION);
        if (empty($cookie)) {
            return null;
        }

        $json = json_decode($cookie, true, 2);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return null;
        }

        $floatFromJson = function (string $property) use ($json) {
            if (!isset($json[$property]) || empty($json[$property])) {
                return null;
            }

            if (!is_numeric($json[$property])) {
                return null;
            }

            return (float)$json[$property];
        };

        $latitude = $floatFromJson('lat');
        $longitude = $floatFromJson('long');
        $altitude = $floatFromJson('alt');

        if (null !== $latitude && null !== $longitude) {
            try {
                return new GeoLocationModel($latitude, $longitude, $altitude);
            } catch (\Throwable $e) {
                $this->logger->error($e);
            }
        }
    }

    private function loadGeoIpData(VisitorInfo $visitorInfo)
    {
        $city = $this->geoIpDataProvider->loadData($visitorInfo);

        if (!$city || !$city['location']['latitude'] || !$city['location']['longitude']) {
            return null;
        }

        return new GeoLocationModel(
            (float)$city['location']['latitude'],
            (float)$city['location']['longitude']
        );
    }
}
