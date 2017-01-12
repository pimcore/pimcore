<?php

namespace Pimcore\Cache;

use Psr\Cache\CacheItemInterface;

interface CacheItemFactoryInterface
{
    /**
     * Create a cache item with the given key and data
     *
     * @param string $key
     * @param mixed $data
     * @param array $tags
     * @param int|\DateInterval|null|bool $lifetime
     * @return CacheItemInterface
     */
    public function createCacheItem($key, $data, array $tags = [], $lifetime = null);

    /**
     * Create an empty cache item
     *
     * @param $key
     * @return CacheItemInterface
     */
    public function createEmptyCacheItem($key);
}
