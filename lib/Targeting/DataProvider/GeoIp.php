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

use GeoIp2\Model\City;
use GeoIp2\ProviderInterface;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Loads geolocation from GeoIP (IP to geo database).
 */
class GeoIp implements DataProviderInterface
{
    const PROVIDER_KEY = 'geoip';

    /**
     * @var ProviderInterface
     */
    private $geoIpProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CoreHandlerInterface
     */
    private $cache;

    public function __construct(
        ProviderInterface $geoIpProvider,
        LoggerInterface $logger
    ) {
        $this->geoIpProvider = $geoIpProvider;
        $this->logger = $logger;
    }

    public function setCache(CoreHandlerInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has(self::PROVIDER_KEY)) {
            return;
        }

        $result = $this->loadData($visitorInfo);

        $visitorInfo->set(
            self::PROVIDER_KEY,
            $result
        );
    }

    public function loadData(VisitorInfo $visitorInfo)
    {
        $result = null;
        $request = $visitorInfo->getRequest();

        $ip = $request->getClientIp();

        if ($this->isPublicIp($ip)) {
            $result = $this->resolveIp($ip);
        }

        $result = $this->handleOverrides($request, $result);

        return $result;
    }

    private function handleOverrides(Request $request, array $result = null)
    {
        $overrides = OverrideAttributeResolver::getOverrideValue($request, 'location');
        if (empty($overrides)) {
            return $result;
        }

        $result = $result ?? [];

        if (isset($overrides['country']) && !empty($overrides['country'])) {
            $result['country'] = array_merge($result['country'] ?? [], [
                'iso_code' => $overrides['country'],
            ]);
        }

        return $result;
    }

    private function isPublicIp(string $ip): bool
    {
        $result = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return $result === $ip;
    }

    private function resolveIp(string $ip)
    {
        if (null === $this->cache) {
            return $this->doResolveIp($ip);
        }

        $cacheKey = implode('_', ['targeting', self::PROVIDER_KEY, sha1($ip)]);

        if ($result = $this->cache->load($cacheKey)) {
            return $result;
        }

        $result = $this->doResolveIp($ip);
        if (!$result) {
            return $result;
        }

        $this->cache->save($cacheKey, $result, ['targeting', 'targeting_' . self::PROVIDER_KEY]);

        return $result;
    }

    private function doResolveIp(string $ip)
    {
        $city = null;

        try {
            $city = $this->geoIpProvider->city($ip);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return null;
        }

        if (!$city) {
            return null;
        }

        return $this->extractData($city);
    }

    protected function extractData(City $city): array
    {
        $data = $city->jsonSerialize();

        // remove localized names as we don't need them
        $filter = function ($key) {
            return 'names' !== $key;
        };

        foreach (array_keys($data) as $section) {
            $data[$section] = array_filter($data[$section], $filter, ARRAY_FILTER_USE_KEY);
        }

        if (isset($data['subdivisions']) && count($data['subdivisions']) > 0) {
            foreach ($data['subdivisions'] as $idx => $subdivision) {
                $data['subdivisions'][$idx] = array_filter($data['subdivisions'][$idx], $filter, ARRAY_FILTER_USE_KEY);
            }
        }

        return $data;
    }
}
