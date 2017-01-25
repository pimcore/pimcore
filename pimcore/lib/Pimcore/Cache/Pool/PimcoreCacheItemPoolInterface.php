<?php

namespace Pimcore\Cache\Pool;

use Cache\TagInterop\TaggableCacheItemPoolInterface;

interface PimcoreCacheItemPoolInterface extends TaggableCacheItemPoolInterface
{
    /**
     * {@inheritdoc}
     *
     * @return PimcoreCacheItemInterface
     */
    public function getItem($key);

    /**
     * {@inheritdoc}
     *
     * @return array|\Traversable|PimcoreCacheItemInterface[]
     */
    public function getItems(array $keys = []);

    /**
     * Create a cache item
     *
     * @param string $key
     * @param mixed $value
     * @param array $tags
     * @param bool $isHit
     *
     * @return PimcoreCacheItemInterface
     */
    public function createCacheItem($key, $value = null, array $tags = [], $isHit = false);

    /**
     * Runs maintenance tasks which could take a long time. Should only be called from maintenance scripts.
     *
     * @return bool True on success
     */
    public function purge();
}
