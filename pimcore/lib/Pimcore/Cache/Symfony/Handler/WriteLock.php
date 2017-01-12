<?php

namespace Pimcore\Cache\Symfony\Handler;

use Psr\Cache\CacheItemPoolInterface;

class WriteLock implements WriteLockInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var CacheItemPoolInterface
     */
    protected $adapter;

    /**
     * @var CacheItemFactoryInterface
     */
    protected $cacheItemFactory;

    /**
     * @var string
     */
    protected $cacheKey = 'system_cache_write_lock';

    /**
     * @var int
     */
    protected $lifetime = 30;

    /**
     * Contains the timestamp of the write lock time from the current process
     *
     * This is to recheck when removing the write lock (if the value is different -> higher) do not remove the lock
     * because then another process has acquired a lock.
     *
     * @var int|null
     */
    protected $timestamp;

    /**
     * @param CacheItemPoolInterface $adapter
     * @param CacheItemFactoryInterface $cacheItemFactory
     */
    public function __construct(CacheItemPoolInterface $adapter, CacheItemFactoryInterface $cacheItemFactory)
    {
        $this->adapter          = $adapter;
        $this->cacheItemFactory = $cacheItemFactory;
    }

    /**
     * Set a write lock (prevents items being written to cache)
     *
     * @param bool $force
     * @return bool
     */
    public function lock($force = false)
    {
        if (!$this->enabled) {
            return true;
        }

        if (!$this->timestamp || $force) {
            $this->timestamp = time();

            $item = $this->cacheItemFactory->createCacheItem(
                $this->cacheKey,
                $this->timestamp,
                [],
                $this->lifetime
            );

            return $this->adapter->save($item);
        }

        return false;
    }

    /**
     * Check if a write lock is active
     *
     * @return bool
     */
    public function hasLock()
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->timestamp && $this->timestamp > 0) {
            return true;
        }

        $item = $this->adapter->getItem($this->cacheKey);
        if ($item->isHit()) {
            $lock = $item->get();

            if ($lock > (time() - $this->lifetime)) {
                $this->timestamp = $lock;
                return true;
            }
        }

        // TODO is this needed?
        $this->timestamp = 0;

        return false;
    }

    /**
     * Remove write lock from instance and from cache
     *
     * @return bool
     */
    public function removeLock()
    {
        if (!$this->enabled) {
            return true;
        }

        if ($this->timestamp) {
            $item = $this->adapter->getItem($this->cacheKey);
            if ($item->isHit()) {
                $lock = $item->get();

                // only remove the lock if it was created by this process
                if ($lock < $this->timestamp) {
                    $this->adapter->deleteItem($this->cacheKey);

                    // TODO null or 0?
                    $this->timestamp = null;

                    return true;
                }
            }
        }

        return false;
    }
}
