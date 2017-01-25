<?php

namespace Pimcore\Cache\Core;

use Pimcore\Cache\Pool\PimcoreCacheItemInterface;

class CacheQueueItem
{
    /**
     * @var PimcoreCacheItemInterface
     */
    protected $cacheItem;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param PimcoreCacheItemInterface $cacheItem
     * @param bool $force
     */
    public function __construct(PimcoreCacheItemInterface $cacheItem, $force = false)
    {
        $this->cacheItem = $cacheItem;
        $this->force     = (bool)$force;
    }

    /**
     * @return PimcoreCacheItemInterface
     */
    public function getCacheItem()
    {
        return $this->cacheItem;
    }

    /**
     * @return bool
     */
    public function getForce()
    {
        return $this->force;
    }
}
