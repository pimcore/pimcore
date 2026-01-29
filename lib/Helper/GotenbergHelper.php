<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Helper;

use Exception;
use Pimcore\Cache;
use Pimcore\Config;

/**
 * @internal
 */
class GotenbergHelper
{
    private static ?bool $validPing = null;

    private const CACHE_KEY = 'gotenberg_ping';
    private const STATUS_AVAILABLE = 'available';
    private const STATUS_UNAVAILABLE = 'unavailable';

    private static function healthPing(): bool
    {
        $gotenbergBaseUrl = Config::getSystemConfiguration('gotenberg')['base_url'];
        if ($gotenbergBaseUrl) {
            try {
                $ch = curl_init(rtrim($gotenbergBaseUrl, '/') . '/health');

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 2,
                    CURLOPT_CONNECTTIMEOUT => 2,
                ]);

                curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                return $status === 200;
            } catch (\Throwable $e) {
                return false;
            }
        }
        return false;
    }

    public static function isAvailable(): bool
    {
        if (self::$validPing !== null) {
            return self::$validPing;
        }

        $cachedStatus = Cache::load(self::CACHE_KEY);

        if ($cachedStatus === self::STATUS_AVAILABLE) {
            self::$validPing = true;
            return true;
        }

        if ($cachedStatus === self::STATUS_UNAVAILABLE) {
            self::$validPing = false;
            return false;
        }

        $ttl = Config::getSystemConfiguration('gotenberg')['ping_cache_ttl'];

        if (self::healthPing()) {
            self::$validPing = true;
            Cache::save(self::STATUS_AVAILABLE, self::CACHE_KEY, [], $ttl);
            return true;
        }

        self::$validPing = false;
        Cache::save(self::STATUS_UNAVAILABLE, self::CACHE_KEY, [], $ttl);
        return false;
    }
}
