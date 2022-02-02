<?php

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

namespace Pimcore\Cache;

use Exception;
use Pimcore\Cache;

trait RuntimeCacheTrait
{
    /**
     * @var bool
     */
    private static bool $cacheEnabled = false;

    /**
     * @param bool $cacheEnabled
     */
    public static function setCacheEnabled(bool $cacheEnabled): void
    {
        self::$cacheEnabled = $cacheEnabled;
    }

    /**
     * @return bool
     */
    public static function getCacheEnabled(): bool
    {
        return self::$cacheEnabled;
    }

    /**
     * Set cache item for a given cache key
     *
     * @param mixed $config
     * @param string $cacheKey
     */
    private static function setCache(mixed $config, string $cacheKey): void
    {
        if (self::$cacheEnabled) {
            Cache\Runtime::set($cacheKey, $config);
        }

        Cache::save($config, $cacheKey, [], null, 0, true);
    }

    /**
     * Remove a cache item for a given cache key
     *
     * @param string $cacheKey
     */
    private static function removeCache(string $cacheKey): void
    {
        Cache::remove($cacheKey);
        Cache\Runtime::set($cacheKey, null);
    }

    /**
     * Get a cache item for a given cache key
     *
     * @param string $cacheKey
     *
     * @return mixed
     *
     * @throws Exception
     */
    private static function getCache(string $cacheKey): mixed
    {
        if (self::$cacheEnabled && Cache\Runtime::isRegistered($cacheKey) && $config = Cache\Runtime::get($cacheKey)) {
            return $config;
        }

        return Cache::load($cacheKey);
    }
}
