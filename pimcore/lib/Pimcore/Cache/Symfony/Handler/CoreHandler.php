<?php

namespace Pimcore\Cache\Symfony\Handler;

use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Element\ElementInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

class CoreHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TagAwareAdapterInterface
     */
    protected $adapter;

    /**
     * @var \Closure[]
     */
    protected $createClosures = [];

    /**
     * Actually write/load to/from cache?
     * @var bool
     */
    protected $enabled = true;

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
     * @var CacheQueueItem[]
     */
    protected $saveQueue = [];

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
     * Contains the timestamp of the writeLockTime from the current process
     * this is to recheck when removing the write lock (if the value is different -> higher) do not remove the lock
     * because then another process has acquired a lock
     *
     * @var int
     */
    protected $writeLockTimestamp;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->setAdapter($adapter);
    }

    /**
     * @param AdapterInterface $adapter
     * @return $this
     */
    protected function setAdapter(AdapterInterface $adapter)
    {
        // if adapter is not tag aware, wrap it in TagAwareAdapter
        if (!$adapter instanceof TagAwareAdapterInterface) {
            $adapter = new TagAwareAdapter($adapter);
        }

        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    /**
     * @return $this
     */
    public function enable()
    {
        return $this->setEnabled(true);
    }

    /**
     * @return $this
     */
    public function disable()
    {
        return $this->setEnabled(false);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function getForceImmediateWrite()
    {
        return $this->forceImmediateWrite;
    }

    /**
     * @param bool $forceImmediateWrite
     */
    public function setForceImmediateWrite($forceImmediateWrite)
    {
        $this->forceImmediateWrite = (bool)$forceImmediateWrite;
    }

    /**
     * Load data from cache (retrieves data from cache item)
     *
     * @param $key
     * @return bool|mixed
     */
    public function load($key)
    {
        // TODO: omitted ____pimcore_cache_item__ here - is this used anywhere?
        $item = $this->getItem($key);

        if ($item->isHit()) {
            $data = $item->get();
            $data = unserialize($data);

            return $data;
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
        if (!$this->enabled) {
            $this->logger->debug(sprintf('Key %s doesn\'t exist in cache (deactivated)', $key), ['key' => $key]);

            return $this->createEmptyCacheItem($key);
        }

        $item = $this->adapter->getItem($key);
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

        $item = $this->prepareCacheItem($key, $data, $tags, $lifetime);
        if (null === $item) {
            // logging is done in prepare method if item could not be created
            return false;
        }

        if ($force || $this->forceImmediateWrite) {
            if ($this->hasWriteLock()) {
                $this->logger->warning(
                    sprintf('Not saving %s to cache as there\'s an active write lock', $key),
                    ['key' => $key]
                );

                return false;
            }

            return $this->storeCacheItem($item, $force);
        } else {
            if (count($this->saveQueue) < $this->maxWriteToCacheItems) {
                $this->saveQueue[] = new CacheQueueItem($item, $force);

                return true;
            } else {
                $this->logger->warning(
                    sprintf('Not saving %s to cache as saveQueue reached a maximum of %d items', $key, $this->maxWriteToCacheItems),
                    ['key' => $key]
                );
            }
        }

        return false;
    }

    /**
     * Prepare data for cache item and handle items we don't want to save (e.g. hardlinks)
     *
     * @param string $key
     * @param mixed $data
     * @param array $tags
     * @param int|\DateInterval|null $lifetime
     * @return CacheItemInterface|null
     */
    protected function prepareCacheItem($key, $data, array $tags = [], $lifetime = null)
    {
        // do not cache hardlink-wrappers
        if ($data instanceof WrapperInterface) {
            $this->logger->debug(
                sprintf('Not saving %s to cache as it is a hardlink wrapper', $key),
                ['key' => $key]
            );

            return null;
        }

        // clean up and prepare models
        if ($data instanceof ElementInterface) {
            // check for corrupt data
            if ($data->getId() < 1) {
                return null;
            }

            // _fulldump is a temp var which is used to trigger a full serialized dump in __sleep eg. in Document, \Object_Abstract
            if (isset($data->_fulldump)) {
                unset($data->_fulldump);
            }

            // get tags for this element
            $tags = $data->getCacheTags($tags);

            $this->logger->debug(
                sprintf(
                    'Prepared %s %d for data cache with tags: %s',
                    get_class($data),
                    $data->getId(),
                    implode(', ', $tags)
                ),
                [
                    'id'   => $data->getId(),
                    'tags' => $tags
                ]
            );
        }

        // normalize tags to array
        if (!empty($tags) && !is_array($tags)) {
            $tags = [$tags];
        }

        // array_values() because the tags from \Element_Interface and some others are associative eg. array("object_123" => "object_123")
        $tags = array_values($tags);

        // check if any of our tags is in cleared tags or tags ignored on save lists
        foreach ($tags as $tag) {
            if (in_array($tag, $this->clearedTags)) {
                $this->logger->debug(
                    sprintf('Aborted caching for key %s because tag %s is in the cleared tags list', $key, $tag),
                    ['key' => $data->getId(), 'tags' => $tags]
                );

                return null;
            }

            if (in_array($tag, $this->tagsIgnoredOnSave)) {
                $this->logger->debug(
                    sprintf('Aborted caching for key %s because tag %s is in the ignored tags on save list', $key, $tag),
                    ['key' => $data->getId(), 'tags' => $tags]
                );

                return null;
            }
        }

        // See #1005 - serialize the element now as we don't know what happens until it is actually persisted on shutdown and we
        // could end up with corrupt objects in cache
        //
        // TODO symfony cache adapters serialize as well - find a way to avoid double serialization
        $itemData = serialize($data);

        $item = $this->createCacheItem($key, $itemData, $tags, $lifetime);

        return $item;
    }

    /**
     * Actually store the item in the cache
     *
     * @param CacheItemInterface|CacheItem $item
     * @param bool $force
     * @return bool
     */
    protected function storeCacheItem(CacheItemInterface $item, $force = false)
    {
        if (!$this->enabled) {
            // TODO return true here as the noop (not storing anything) is basically successful?
            return false;
        }

        // don't put anything into the cache, when cache is cleared
        if ($this->cacheCleared && !$force) {
            return false;
        }

        $result = $this->adapter->save($item);

        if ($result) {
            $this->logger->debug(sprintf('Added entry %s to cache', $item->getKey()), ['key' => $item->getKey()]);
        } else {
            $this->logger->error(
                sprintf(
                    'Failed to add entry %s to cache. Item size was %s',
                    $item->getKey(),
                    formatBytes(strlen($item->get()))
                )
            );
        }

        return $result;
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

        return $this->adapter->deleteItem($key);
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
            if (in_array($shutdownTag, $tags)) {
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
    public function addTagClearedOnShutdown($tag)
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
     * Writes save queue to the cache
     *
     * @return bool
     */
    public function writeSaveQueue()
    {
        $totalResult = true;

        if ($this->hasWriteLock()) {
            if (count($this->saveQueue) > 0) {
                $this->logger->warning(
                    sprintf(
                        'Not writing save queue as there\'s an active write log. Save queue contains %d items',
                        count($this->saveQueue)
                    )
                );
            }

            return false;
        }

        $processedKeys = [];
        foreach ($this->saveQueue as $queueItem) {
            $key = $queueItem->getCacheItem()->getKey();

            // check if key was already processed and don't save it again
            if (in_array($key, $processedKeys)) {
                $this->logger->warning(
                    sprintf('Not writing item as key %s was already processed', $key),
                    ['key' => $key]
                );

                continue;
            }


            $result = $this->storeCacheItem($queueItem->getCacheItem(), $queueItem->getForce());

            if (!$result) {
                $this->logger->error(sprintf('Unable to write item %s to cache', $key));
            }

            $processedKeys[] = $key;

            $totalResult = $totalResult && $result;
        }

        // reset
        $this->saveQueue = [];

        return $totalResult;
    }

    /**
     * Shut down pimcore - write cache entries and clean up
     *
     * @param bool $forceWrite
     * @return $this
     */
    public function shutdown($forceWrite = false)
    {
        // clear tags scheduled for the shutdown
        $this->clearTagsOnShutdown();

        // write collected items to cache backend
        if (php_sapi_name() !== 'cli' || $forceWrite) {
            // makes only sense for HTTP(S)
            // CLI are normally longer running scripts that tend to produce race conditions
            // so CLI scripts are not writing to the cache at all
            $this->writeSaveQueue();
        }

        // remove the write lock
        $this->removeWriteLock();

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
                    $item->value = $data;
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
