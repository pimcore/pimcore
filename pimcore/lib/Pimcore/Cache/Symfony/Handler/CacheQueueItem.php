<?php

namespace Pimcore\Cache\Symfony\Handler;

use Psr\Cache\CacheItemInterface;

class CacheQueueItem
{
    /**
     * @var CacheItemInterface
     */
    protected $cacheItem;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param CacheItemInterface $cacheItem
     * @param bool $force
     */
    public function __construct(CacheItemInterface $cacheItem, $force = false)
    {
        $this->cacheItem = $cacheItem;
        $this->force     = (bool)$force;
    }

    /**
     * @return CacheItemInterface
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
