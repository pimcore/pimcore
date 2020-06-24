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

use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\OperatingSystem;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Targeting\Debug\Util\OverrideAttributeResolver;
use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class Device implements DataProviderInterface
{
    const PROVIDER_KEY = 'device';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The cache handler caching detected results
     *
     * @var CoreHandlerInterface
     */
    private $cache;

    /**
     * The cache pool which is passed to the DeviceDetector
     *
     * @var PimcoreCacheItemPoolInterface
     */
    private $cachePool;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setCache(CoreHandlerInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setCachePool(PimcoreCacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has(self::PROVIDER_KEY)) {
            return;
        }

        $userAgent = $visitorInfo->getRequest()->headers->get('User-Agent');

        $result = $this->loadData($userAgent);
        $result = $this->handleOverrides($visitorInfo->getRequest(), $result);

        $visitorInfo->set(
            self::PROVIDER_KEY,
            $result
        );
    }

    private function handleOverrides(Request $request, array $result = null)
    {
        $overrides = OverrideAttributeResolver::getOverrideValue($request, 'device');
        if (empty($overrides)) {
            return $result;
        }

        $result = $result ?? [];

        if (isset($overrides['hardwarePlatform']) && !empty($overrides['hardwarePlatform'])) {
            $result['device'] = array_merge($result['device'] ?? [], [
                'type' => $overrides['hardwarePlatform'],
            ]);
        }

        if (isset($overrides['operatingSystem']) && !empty($overrides['operatingSystem'])) {
            $result['os'] = array_merge($result['os'] ?? [], [
                'short_name' => $overrides['operatingSystem'],
            ]);
        }

        if (isset($overrides['browser']) && !empty($overrides['browser'])) {
            $result['client'] = array_merge($result['client'] ?? [], [
                'type' => 'browser',
                'name' => $overrides['browser'],
            ]);
        }

        return $result;
    }

    private function loadData(string $userAgent)
    {
        if (null === $this->cache) {
            return $this->doLoadData($userAgent);
        }

        $cacheKey = implode('_', ['targeting', self::PROVIDER_KEY, sha1($userAgent)]);

        if ($result = $this->cache->load($cacheKey)) {
            return $result;
        }

        $result = $this->doLoadData($userAgent);
        if (!$result) {
            return $result;
        }

        $this->cache->save($cacheKey, $result, ['targeting', 'targeting_' . self::PROVIDER_KEY]);

        return $result;
    }

    private function doLoadData(string $userAgent)
    {
        try {
            $dd = new DeviceDetector($userAgent);

            if (null !== $this->cachePool) {
                $dd->setCache(new PSR6Bridge($this->cachePool));
            }

            $dd->parse();
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return null;
        }

        return $this->extractData($dd);
    }

    protected function extractData(DeviceDetector $dd): array
    {
        if ($dd->isBot()) {
            return [
                'user_agent' => $dd->getUserAgent(),
                'bot' => $dd->getBot(),
                'is_bot' => true,
            ];
        }

        $osFamily = OperatingSystem::getOsFamily($dd->getOs('short_name'));
        $browserFamily = Browser::getBrowserFamily($dd->getClient('short_name'));

        $processed = [
            'user_agent' => $dd->getUserAgent(),
            'bot' => $dd->getBot(),
            'is_bot' => $dd->isBot(),
            'os' => $dd->getOs(),
            'os_family' => $osFamily !== false ? $osFamily : 'Unknown',
            'client' => $dd->getClient(),
            'device' => [
                'type' => $dd->getDeviceName(),
                'brand' => $dd->getBrand(),
                'model' => $dd->getModel(),
            ],
            'browser_family' => $browserFamily !== false ? $browserFamily : 'Unknown',
        ];

        return $processed;
    }
}
