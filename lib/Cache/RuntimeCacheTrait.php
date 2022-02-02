<?php
/**
 *
 * Created by PhpStorm.
 *
 * @date: 02.02.2022
 *
 * @author: Andreas Wroblewski <andreas.wroblewski@codafish.net>
 * @copyright: codafish><> GmbH, https://codafish.net
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
