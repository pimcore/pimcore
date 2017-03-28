<?php

namespace Pimcore\Cache\Pool;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;

interface PimcoreCacheItemPoolInterface extends TaggableCacheItemPoolInterface, LoggerAwareInterface
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
}
