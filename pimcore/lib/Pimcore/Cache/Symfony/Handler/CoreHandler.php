<?php

namespace Pimcore\Cache\Symfony\Handler;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

class CoreHandler
{
    use LoggerAwareTrait;

    /**
     * @var TagAwareAdapter
     */
    protected $adapter;

    /**
     * @var \Closure[]
     */
    protected $createClosures = [];

    /**
     * Use actual cache adapter or null adapter?
     * @var bool
     */
    protected $enabled = true;

    /**
     * Default item lifetime
     * @var int
     */
    protected $defaultLifetime = 2419200; // 28 days

    /**
     * @var bool
     */
    protected $useWriteLock = true;

    /**
     * @var string
     */
    protected $writeLockKey = 'system_cache_write_lock';

    /**
     * @var int
     */
    protected $writeLockLifetime = 30;

    /**
     * Contains the items which should be written to the cache on shutdown
     * @var SaveStackItem[]
     */
    protected $saveStack = [];

    /**
     * Tags which were already cleared
     * @var array
     */
    protected $clearedTags = [];

    /**
     * Items having one of the tags in this list are not saved
     * @var array
     */
    protected $tagsIgnoredOnSave = [];

    /**
     * Items having one of the tags in this list are not cleared when calling clearTags
     * @var array
     */
    protected $tagsIgnoredOnClear = [];

    /**
     * Items having tags which are in this array are cleared on shutdown. This is especially for the output-cache.
     * @var array
     */
    protected $tagsClearedOnShutdown = [];

    /**
     * State variable which is set to true after the cache was cleared - prevent new items being
     * written to cache after a clear.
     *
     * @var bool
     */
    protected $cacheCleared = false;

    /**
     * Tags in this list are shifted to the clearTagsOnShutdown list when scheduled via clearTags. See comment on normalizeClearTags
     * method why this exists.
     * @var array
     */
    protected $shutdownTags = ['output'];

    /**
     * Determine if delete actions should always be done on the actual cache adapter. See comment on getActiveAdapter()
     * @var bool
     */
    protected $handleDeletesOnCacheAdapter = true;

    /**
     * If set to true items are directly written into the cache, and do not get into the queue
     * @var bool
     */
    protected $forceImmediateWrite = false;

    /**
     * How many items should stored to the cache within one process
     * @var int
     */
    protected $maxWriteToCacheItems = 50;

    /**
     * Prefix which will be added to every item-key
     * @var string
     */
    protected $cachePrefix = 'pimcore_';

    /**
     * Contains the timestamp of the writeLockTime from the current process
     * this is to recheck when removing the write lock (if the value is different -> higher) do not remove the lock
     * because then another process has acquired a lock
     *
     * @var int
     */
    protected $writeLockTimestamp;

    /**
     * Normalize cache key (add prefix, trim, ...)
     *
     * @param string $key
     * @return string
     */
    protected function normalizeCacheKey($key)
    {
        return $this->cachePrefix . $key;
    }

    /**
     * Load data from cache (retrieves data from cache item)
     *
     * @param $key
     * @return bool|mixed
     */
    public function load($key)
    {
        $item = $this->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        return false;
    }

    /**
     * Get PSR-6 cache item
     *
     * @param $key
     * @return CacheItemInterface
     */
    public function getItem($key)
    {
        $normalizedKey = $this->normalizeCacheKey($key);

        if (!$this->enabled) {
            $this->logger->debug(sprintf('Key %s doesn\'t exist in cache (deactivated)', $key), ['key' => $key]);

            return $this->createEmptyCacheItem($normalizedKey);
        }

        $item = $this->adapter->getItem($normalizedKey);
        if ($item->isHit()) {
            $this->logger->debug(sprintf('Successfully got data for key %s from cache', $key), ['key' => $key]);
        } else {
            $this->logger->debug(sprintf('Key %s doesn\'t exist in cache', $key), ['key' => $key]);
        }

        return $item;
    }

    /**
     * Save data to cache
     *
     * @param string $key
     * @param string $data
     * @param array $tags
     * @param int|\DateInterval|null $lifetime
     * @param bool $force
     * @return bool
     */
    public function save($key, $data, array $tags = [], $lifetime = null, $force = false)
    {
        if (php_sapi_name() === 'cli' && !$force) {
            $this->logger->debug(
                sprintf('Not saving %s to cache as process is running in CLI mode (pass force to override)', $key),
                ['key' => $key]
            );

            return false;
        }

        $item = $this->createCacheItem($this->normalizeCacheKey($key), $data, $tags, $lifetime);

        if ($force || $this->forceImmediateWrite) {
            if ($this->hasWriteLock()) {
                $this->logger->warning(
                    sprintf('Not saving %s to cache as there\'s an active write lock', $key),
                    ['key' => $key]
                );

                return false;
            }

            return $this->store($item, $force);
        } else {
            if (count($this->saveStack) < $this->maxWriteToCacheItems) {
                $this->saveStack[] = new SaveStackItem($item, $force);

                return true;
            } else {
                $this->logger->warning(
                    sprintf('Not saving %s to cache as saveStack reached a maximum of %d items', $key, $this->maxWriteToCacheItems),
                    ['key' => $key]
                );
            }
        }

        return false;
    }

    /**
     * Actually store the item in the cache
     *
     * @param CacheItemInterface|CacheItem $item
     * @param bool $force
     * @return bool
     */
    protected function storeItem(CacheItemInterface $item, $force = false)
    {
        if (!$this->enabled) {
            // TODO return true here as the noop (not storing anything) is basically successful?
            return false;
        }

        // don't put anything into the cache, when cache is cleared
        if ($this->cacheCleared && !$force) {
            return false;
        }
    }

    /**
     * Writes save stack to the cache
     *
     * @return bool
     */
    public function commitSaveStack()
    {
    }

    /**
     * Remove a cache item
     *
     * @param $key
     * @return bool
     */
    public function remove($key)
    {
        $this->setWriteLock();

        return $this->adapter->deleteItem($this->normalizeCacheKey($key));
    }

    /**
     * Empty the cache
     *
     * @return bool
     */
    public function clearAll()
    {
        $this->setWriteLock();

        $result = $this->adapter->clear();

        // immediately acquire the write lock again (force), because the lock is in the cache too
        $this->setWriteLock(true);

        // set state to cache cleared - prevents new items being written to cache
        $this->cacheCleared = true;

        return $result;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function clearTag($tag)
    {
        return $this->clearTags([$tag]);
    }

    /**
     * @param string[] $tags
     * @return bool
     */
    public function clearTags(array $tags)
    {
        $this->setWriteLock();

        $originalTags = $tags;

        $this->logger->info(
            sprintf('Clearing cache tags: %s', implode(', ', $tags)),
            ['tags' => $tags]
        );

        $tags = $this->normalizeClearTags($tags);
        if (count($tags) > 0) {
            return $this->adapter->invalidateTags($tags);
        }

        $this->logger->warning(
            sprintf('Could not clear tags as tag list is empty after normalization. List was: %s', implode(', ', $tags)),
            ['tags' => $originalTags]
        );

        return false;
    }

    /**
     * Clears all tags stored in tagsClearedOnShutdown, this function is executed during Pimcore shutdown
     *
     * @return bool
     */
    public function clearTagsOnShutdown()
    {
        if (empty($this->tagsClearedOnShutdown)) {
            return true;
        }

        $this->logger->info(
            sprintf('Clearing shutdown cache tags: %s', implode(', ', $this->tagsClearedOnShutdown)),
            ['tags' => $this->tagsClearedOnShutdown]
        );

        return $this->adapter->invalidateTags($this->tagsClearedOnShutdown);
    }

    /**
     * Normalize (unique) clear tags and shift special tags to shutdown (e.g. output)
     *
     * @param array $tags
     * @return array
     */
    protected function normalizeClearTags(array $tags)
    {
        $blacklist = $this->tagsIgnoredOnClear;

        // Shutdown tags are special tags being shifted to shutdown when scheduled to clear via clearTags. Explanation for
        // the "output" tag:
        // check for the tag output, because items with this tags are only cleared after the process is finished
        // the reason is that eg. long running importers will clean the output-cache on every save/update, that's not necessary,
        // only cleaning the output-cache on shutdown should be enough
        foreach ($this->shutdownTags as $shutdownTag) {
            if (in_array($shutdownTag, $shutdownTag)) {
                $this->addTagClearedOnShutdown($shutdownTag);
                $blacklist[] = $shutdownTag;
            }
        }

        // ensure that every tag is unique
        $tags = array_unique($tags);

        // don't clear tags in ignore array
        $tags = array_filter($tags, function ($tag) use ($blacklist) {
            return !in_array($tag, $blacklist);
        });

        return $tags;
    }

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     *
     * @param string $tag
     * @return $this
     */
    protected function addTagClearedOnShutdown($tag)
    {
        $this->setWriteLock();
        $this->tagsClearedOnShutdown[] = $tag;

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function addTagIgnoredOnSave($tag)
    {
        $this->tagsIgnoredOnSave[] = $tag;

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function removeTagIgnoredOnSave($tag)
    {
        $this->tagsIgnoredOnSave = array_filter($this->tagsIgnoredOnSave, function($t) use ($tag) {
            return $t !== $tag;
        });

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function addTagIgnoredOnClear($tag)
    {
        $this->tagsIgnoredOnClear[] = $tag;

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function removeTagIgnoredOnClear($tag)
    {
        $this->tagsIgnoredOnClear = array_filter($this->tagsIgnoredOnClear, function($t) use ($tag) {
            return $t !== $tag;
        });

        return $this;
    }

    /**
     * @param string $key
     * @return CacheItemInterface
     */
    protected function createEmptyCacheItem($key)
    {
        if (isset($this->createClosures['empty'])) {
            $createClosure = $this->createClosures['empty'];
        } else {
            $createClosure = \Closure::bind(
                function ($key) {
                    $item = new CacheItem();
                    $item->key = $key;

                    return $item;
                },
                null,
                CacheItem::class
            );

            $this->createClosures['empty'] = $createClosure;
        }

        return $createClosure($key);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param string[] $tags
     * @param int|\DateInterval|null $lifetime
     * @return mixed
     */
    protected function createCacheItem($key, $data, array $tags = [], $lifetime = null)
    {
        if (isset($this->createClosures['create'])) {
            $createClosure = $this->createClosures['create'];
        } else {
            $createClosure = \Closure::bind(
                function () use ($key, $data, $tags, $lifetime) {
                    $item = new CacheItem();
                    $item->key = $key;

                    // TODO symfony cache adapters serialize as well - find a way to avoid double serialization
                    $item->value = serialize($data);

                    $item->defaultLifetime = $this->defaultLifetime;
                    $item->tag($tags);
                    $item->expiresAfter($lifetime);

                    return $item;
                },
                null,
                CacheItem::class
            );

            $this->createClosures['create'] = $createClosure;
        }

        return $createClosure($key, $data, $tags, $lifetime);
    }

    /**
     * Set a write lock (prevents items being written to cache)
     *
     * @param bool $force
     * @return bool
     */
    protected function setWriteLock($force = false)
    {
        if (!$this->useWriteLock) {
            return true;
        }

        if (!$this->writeLockTimestamp || $force) {
            $this->writeLockTimestamp = time();

            $item = $this->createCacheItem(
                $this->writeLockKey,
                $this->writeLockTimestamp,
                [],
                $this->writeLockLifetime
            );

            return $this->adapter->save($item);
        }

        return false;
    }

    /**
     * Check if a write lock is active (prevents items being written to cache)
     *
     * @return bool
     */
    protected function hasWriteLock()
    {
        if (!$this->useWriteLock) {
            return false;
        }

        if ($this->writeLockTimestamp && $this->writeLockTimestamp > 0) {
            return true;
        }

        $item = $this->adapter->getItem($this->writeLockKey);
        if ($item->isHit()) {
            $lock = $item->get();

            if ($lock > (time() - $this->writeLockLifetime)) {
                $this->writeLockTimestamp = $lock;
                return true;
            }
        }

        // TODO is this needed?
        $this->writeLockTimestamp = 0;

        return false;
    }

    /**
     * Remove write lock from instance and from cache
     *
     * @return bool
     */
    protected function removeWriteLock()
    {
        if (!$this->useWriteLock) {
            return true;
        }

        if ($this->writeLockTimestamp) {
            $item = $this->adapter->getItem($this->writeLockKey);
            if ($item->isHit()) {
                $lock = $item->get();

                // only remove the lock if it was created by this process
                if ($lock < $this->writeLockTimestamp) {
                    $this->adapter->deleteItem($this->writeLockKey);

                    // TODO null or 0?
                    $this->writeLockTimestamp = null;

                    return true;
                }
            }
        }

        return false;
    }
}
