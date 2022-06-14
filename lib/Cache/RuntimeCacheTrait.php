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

/**
 * @deprecated
 */
trait RuntimeCacheTrait
{
    /**
     * @var bool
     *
     * @deprecated
     */
    private static bool $cacheEnabled = false;

    /**
     * @param bool $cacheEnabled
     *
     * @deprecated
     */
    public static function setCacheEnabled(bool $cacheEnabled): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11.', __METHOD__)
        );
        self::$cacheEnabled = $cacheEnabled;
    }

    /**
     * @return bool
     *
     * @deprecated
     */
    public static function getCacheEnabled(): bool
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11.', __METHOD__)
        );

        return self::$cacheEnabled;
    }

    /**
     * Set cache item for a given cache key
     *
     * @param mixed $config
     * @param string $cacheKey
     *
     * @deprecated
     */
    private static function setCache(mixed $config, string $cacheKey): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use \Pimcore\Cache\Runtime::set() and \Pimcore\Cache::save() instead.', __METHOD__)
        );
        if (self::$cacheEnabled) {
            Cache\Runtime::set($cacheKey, $config);
        }

        Cache::save($config, $cacheKey, [], null, 0, true);
    }

    /**
     * Remove a cache item for a given cache key
     *
     * @param string $cacheKey
     *
     * @deprecated
     */
    private static function removeCache(string $cacheKey): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use \Pimcore\Cache\Runtime::set() with null value and \Pimcore\Cache::remove() instead.', __METHOD__)
        );
        Cache::remove($cacheKey);
        Cache\Runtime::set($cacheKey, null);
    }

    /**
     * Get a cache item for a given cache key
     *
     * @deprecated
     *
     * @param string $cacheKey
     *
     * @return mixed
     *
     * @throws Exception
     */
    private static function getCache(string $cacheKey): mixed
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use \Pimcore\Cache\Runtime::get() and \Pimcore\Cache::load() instead.', __METHOD__)
        );

        if (self::$cacheEnabled && Cache\Runtime::isRegistered($cacheKey) && $config = Cache\Runtime::get($cacheKey)) {
            return $config;
        }

        return Cache::load($cacheKey);
    }
}
