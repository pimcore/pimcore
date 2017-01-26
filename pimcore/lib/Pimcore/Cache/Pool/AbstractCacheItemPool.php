<?php

namespace Pimcore\Cache\Pool;

use Pimcore\Cache\Pool\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractCacheItemPool implements PimcoreCacheItemPoolInterface
{
    use LoggerAwareTrait;

    /**
     * @var PimcoreCacheItemInterface[]
     */
    protected $deferred = [];

    /**
     * @var int
     */
    protected $defaultLifeTime = 0;

    /**
     * @param int $defaultLifeTime
     */
    public function __construct($defaultLifeTime = 0)
    {
        $this->defaultLifeTime = $defaultLifeTime;
    }

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|\Traversable The corresponding values found in the cache
     */
    abstract protected function doFetch(array $ids);

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    abstract protected function doHave($id);

    /**
     * Deletes all items in the pool.
     *
     * @param string The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    abstract protected function doClear($namespace);

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    abstract protected function doDelete(array $ids);

    /**
     * Transform cache key into storage ID (e.g. prefix with namespace)
     *
     * @param string $key
     * @return string
     */
    protected function getId($key)
    {
        CacheItem::validateKey($key);

        return $key;
    }

    /**
     * Create a cache item
     *
     * @param string $key
     * @param mixed $value
     * @param array $tags
     * @param bool $isHit
     * @return PimcoreCacheItemInterface
     */
    public function createCacheItem($key, $value = null, array $tags = [], $isHit = false)
    {
        $cacheItem = new CacheItem($key, $value, $isHit, $tags, $this->defaultLifeTime);

        return $cacheItem;
    }

    /**
     * Generate cache item result from requested keys, including items for negative hits.
     *
     * @param array $items
     * @param array $keys
     * @return \Generator|PimcoreCacheItemInterface[]
     */
    protected function generateItems($items, &$keys)
    {
        try {
            foreach ($items as $id => $data) {
                $key = $keys[$id];
                unset($keys[$id]);

                // create items with data
                yield $key => $this->createCacheItem($key, $data['value'], $data['tags'], true);
            }
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch requested items', ['keys' => array_values($keys), 'exception' => $e]);
        }

        // create items with isHit = false for the remaining keys
        foreach ($keys as $key) {
            yield $key => $this->createCacheItem($key);
        }
    }

    /**
     * @param mixed $data
     * @return string
     */
    protected function serializeData($data)
    {
        return serialize($data);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    protected function unserializeData($serialized)
    {
        return unserialize($serialized);
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        if ($this->deferred) {
            $this->commit();
        }

        $id = $this->getId($key);

        $isHit = false;
        $value = null;

        $data = [
            'value' => null,
            'tags'  => []
        ];

        try {
            foreach ($this->doFetch([$id]) as $data) {
                $isHit = true;
            }
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch key "{key}"', ['key' => $key, 'exception' => $e]);
        }

        return $this->createCacheItem($key, $data['value'], $data['tags'], $isHit);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = [])
    {
        if ($this->deferred) {
            $this->commit();
        }

        if (empty($keys)) {
            return [];
        }

        $ids = [];
        foreach ($keys as $key) {
            $ids[] = $this->getId($key);
        }

        try {
            $items = $this->doFetch($ids);
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch requested items', ['keys' => $keys, 'exception' => $e]);
            $items = [];
        }

        // [$id => $key]
        $ids = array_combine($ids, $keys);

        return $this->generateItems($items, $ids);
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        $id = $this->getId($key);

        if (isset($this->deferred[$key])) {
            $this->commit();
        }

        try {
            return $this->doHave($id);
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to check if key "{key}" is cached', ['key' => $key, 'exception' => $e]);

            return false;
        }
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $this->deferred = [];

        try {
            return $this->doClear('');
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to clear the cache', ['exception' => $e]);

            return false;
        }
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        $ids = [];

        foreach ($keys as $key) {
            $ids[$key] = $this->getId($key);
            unset($this->deferred[$key]);
        }

        try {
            if ($this->doDelete($ids)) {
                return true;
            }
        } catch (\Exception $e) {
        }

        $ok = true;

        // When bulk-delete failed, retry each item individually
        foreach ($ids as $key => $id) {
            try {
                $e = null;
                if ($this->doDelete([$id])) {
                    continue;
                }
            } catch (\Exception $e) {
            }

            CacheItem::log($this->logger, 'Failed to delete key "{key}"', ['key' => $key, 'exception' => $e]);
            $ok = false;
        }

        return $ok;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        if (!$item instanceof PimcoreCacheItemInterface) {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return $this->commit();
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if (!$item instanceof PimcoreCacheItemInterface) {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * Invalidates cached items using a tag.
     *
     * @param string $tag The tag to invalidate
     *
     * @throws InvalidArgumentException When $tags is not valid
     *
     * @return bool True on success
     */
    public function invalidateTag($tag)
    {
        return $this->invalidateTags([$tag]);
    }

    public function __destruct()
    {
        if ($this->deferred) {
            $this->commit();
        }
    }
}
