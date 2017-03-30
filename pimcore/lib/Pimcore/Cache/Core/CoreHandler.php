<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Cache\Core;

use Pimcore\Cache\Pool\CacheItem;
use Pimcore\Cache\Pool\PimcoreCacheItemInterface;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\PurgeableCacheItemPoolInterface;
use Pimcore\Model\Document\Hardlink\Wrapper\WrapperInterface;
use Pimcore\Model\Element\ElementInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Core pimcore cache handler with logic handling deferred save on shutdown (specialized for internal pimcore use). This
 * explicitely does not expose a PSR-6 API but is intended for internal use from Pimcore\Cache or directly. Actual
 * cache calls are forwarded to a PSR-6 cache implementation though.
 */
class CoreHandler implements LoggerAwareInterface, CoreHandlerInterface
{
    use LoggerAwareTrait;

    /**
     * @var PimcoreCacheItemPoolInterface
     */
    protected $itemPool;

    /**
     * @var WriteLockInterface
     */
    protected $writeLock;

    /**
     * Actually write/load to/from cache?
     * @var bool
     */
    protected $enabled = true;

    /**
     * Is the cache handled in CLI mode?
     * @var bool
     */
    protected $handleCli = false;

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
     * @var \Closure
     */
    protected $emptyCacheItemClosure;

    /**
     * @param PimcoreCacheItemPoolInterface $adapter
     * @param WriteLockInterface $writeLock
     */
    public function __construct(PimcoreCacheItemPoolInterface $adapter, WriteLockInterface $writeLock)
    {
        $this->setItemPool($adapter);

        $this->writeLock = $writeLock;
    }

    /**
     * @param PimcoreCacheItemPoolInterface $itemPool
     * @return $this
     */
    protected function setItemPool(PimcoreCacheItemPoolInterface $itemPool)
    {
        $this->itemPool = $itemPool;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWriteLock()
    {
        return $this->writeLock;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return bool
     */
    public function getHandleCli()
    {
        return $this->handleCli;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param bool $handleCli
     * @return $this
     */
    public function setHandleCli($handleCli)
    {
        $this->handleCli = (bool)$handleCli;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return bool
     */
    public function getForceImmediateWrite()
    {
        return $this->forceImmediateWrite;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param bool $forceImmediateWrite
     * @return $this
     */
    public function setForceImmediateWrite($forceImmediateWrite)
    {
        $this->forceImmediateWrite = (bool)$forceImmediateWrite;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
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
            $data = $item->get();
            $data = unserialize($data);

            if (is_object($data)) {
                $data->____pimcore_cache_item__ = $key; // TODO where is this used?
            }

            return $data;
        }

        return false;
    }

    /**
     * Get PSR-6 cache item
     *
     * @param $key
     * @return PimcoreCacheItemInterface
     */
    public function getItem($key)
    {
        if (!$this->enabled) {
            $this->logger->debug('Key {key} doesn\'t exist in cache (deactivated)', ['key' => $key]);

            // create empty cache item
            return $this->itemPool->createCacheItem($key);
        }

        $item = $this->itemPool->getItem($key);
        if ($item->isHit()) {
            $this->logger->debug('Successfully got data for key {key} from cache', ['key' => $key]);
        } else {
            $this->logger->debug('Key {key} doesn\'t exist in cache', ['key' => $key]);
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
     * @param int|null $priority
     * @param bool $force
     * @return bool
     */
    public function save($key, $data, array $tags = [], $lifetime = null, $priority = 0, $force = false)
    {
        CacheItem::validateKey($key);

        if (!$this->enabled) {
            $this->logger->debug('Not saving object {key} to cache (deactivated)', ['key' => $key]);

            return false;
        }

        if ($this->isCli()) {
            if (!$this->handleCli && !$force) {
                $this->logger->debug(
                    'Not saving {key} to cache as process is running in CLI mode (pass force to override or set handleCli to true)',
                    ['key' => $key]
                );

                return false;
            }
        }

        if ($force || $this->forceImmediateWrite) {
            if ($this->writeLock->hasLock()) {
                $this->logger->warning(
                    'Not saving {key} to cache as there\'s an active write lock',
                    ['key' => $key]
                );

                return false;
            }

            $item = $this->prepareCacheItem($key, $data, $lifetime);
            if (null === $item) {
                // logging is done in prepare method if item could not be created
                return false;
            }

            // add cache tags to item
            $item = $this->prepareCacheTags($item, $data, $tags);
            if (null === $item) {
                return false;
            }

            return $this->storeCacheItem($item, $data, $force);
        } else {
            $cacheQueueItem = new CacheQueueItem($key, $data, $tags, $lifetime, $priority, $force);

            return $this->addToSaveQueue($cacheQueueItem);
        }
    }

    /**
     * Add item to save queue, respecting maxWriteToCacheItems setting
     *
     * @param CacheQueueItem $item
     * @return bool
     */
    protected function addToSaveQueue(CacheQueueItem $item)
    {
        $this->saveQueue[$item->getKey()] = $item;

        // order by priority
        uasort($this->saveQueue, function (CacheQueueItem $a, CacheQueueItem $b) {
            if ($a->getPriority() === $b->getPriority()) {
                // records with serialized data have priority, to save cpu cycles. if the item has a CacheItem set, data
                // was already serialized
                if (null !== $a->getCacheItem()) {
                    return -1;
                } else {
                    return 1;
                }
            }

            return $a->getPriority() < $b->getPriority() ? 1 : -1;
        });

        // remove overrun
        array_splice($this->saveQueue, $this->maxWriteToCacheItems);

        // check if item is still on queue and serialize the data into a CacheItem
        if (isset($this->saveQueue[$item->getKey()])) {
            $cacheItem = $this->prepareCacheItem($item->getKey(), $item->getData(), $item->getLifetime());
            if ($cacheItem) {
                // add cache item with serialized data to queue item
                $item->setCacheItem($cacheItem);

                return true;
            } else {
                // cache item could not be created - remove queue item
                unset($this->saveQueue[$item->getKey()]);

                // logging is done in prepare method if item could not be created
                return false;
            }
        } else {
            $this->logger->warning(
                'Not saving {key} to cache as it did not fit into the save queue (max items on queue: {maxItems})',
                [
                    'key'      => $item->getKey(),
                    'maxItems' => $this->maxWriteToCacheItems
                ]
            );
        }

        return false;
    }

    /**
     * Prepare data for cache item and handle items we don't want to save (e.g. hardlinks)
     *
     * @param string $key
     * @param mixed $data
     * @param int|\DateInterval|null $lifetime
     *
     * @return PimcoreCacheItemInterface|null
     */
    protected function prepareCacheItem($key, $data, $lifetime = null)
    {
        // do not cache hardlink-wrappers
        if ($data instanceof WrapperInterface) {
            $this->logger->warning(
                'Not saving {key} to cache as it is a hardlink wrapper',
                ['key' => $key]
            );

            return null;
        }

        // clean up and prepare models
        if ($data instanceof ElementInterface) {
            // check for corrupt data
            if (!$data->getId()) {
                return null;
            }

            // _fulldump is a temp var which is used to trigger a full serialized dump in __sleep eg. in Document, \Object_Abstract
            if (isset($data->_fulldump)) {
                unset($data->_fulldump);
            }
        }

        if (is_object($data) && isset($data->____pimcore_cache_item__)) {
            unset($data->____pimcore_cache_item__);
        }

        // See #1005 - serialize the element now as we don't know what happens until it is actually persisted on shutdown and we
        // could end up with corrupt objects in cache
        //
        // TODO symfony cache adapters serialize as well - find a way to avoid double serialization
        $itemData = serialize($data);

        $item = $this->itemPool->createCacheItem($key, $itemData);
        $item->expiresAfter($lifetime);

        return $item;
    }

    /**
     * Create tags for cache item - do this as late as possible as this is potentially expensive (nested items, dependencies)
     *
     * @param PimcoreCacheItemInterface $cacheItem
     * @param $data
     * @param array $tags
     * @return null|PimcoreCacheItemInterface
     */
    protected function prepareCacheTags(PimcoreCacheItemInterface $cacheItem, $data, array $tags = [])
    {
        // clean up and prepare models
        if ($data instanceof ElementInterface) {
            // get tags for this element
            $tags = $data->getCacheTags($tags);

            $this->logger->debug(
                'Prepared {class} {id} for data cache with tags: {tags}',
                [
                    'class' => get_class($data),
                    'id'    => $data->getId(),
                    'tags'  => $tags
                ]
            );
        }

        // normalize tags to array
        if (!empty($tags) && !is_array($tags)) {
            $tags = [$tags];
        }

        // array_values() because the tags from \Element_Interface and some others are associative eg. array("object_123" => "object_123")
        $tags = array_values($tags);
        $tags = array_unique($tags);

        // check if any of our tags is in cleared tags or tags ignored on save lists
        foreach ($tags as $tag) {
            if (in_array($tag, $this->clearedTags)) {
                $this->logger->debug('Aborted caching for key {key} because tag {tag} is in the cleared tags list', [
                    'key'  => $cacheItem->getKey(),
                    'tag'  => $tag,
                    'tags' => $tags
                ]);

                return null;
            }

            if (in_array($tag, $this->tagsIgnoredOnSave)) {
                $this->logger->debug('Aborted caching for key {key} because tag {tag} is in the ignored tags on save list', [
                    'key'  => $cacheItem->getKey(),
                    'tag'  => $tag,
                    'tags' => $tags
                ]);

                return null;
            }
        }

        $cacheItem->setTags($tags);

        return $cacheItem;
    }

    /**
     * Actually store the item in the cache
     *
     * @param PimcoreCacheItemInterface $item
     * @param mixed $data
     * @param bool $force
     * @return bool
     */
    protected function storeCacheItem(PimcoreCacheItemInterface $item, $data, $force = false)
    {
        if (!$this->enabled) {
            // TODO return true here as the noop (not storing anything) is basically successful?
            return false;
        }

        // don't put anything into the cache, when cache is cleared
        if ($this->cacheCleared && !$force) {
            return false;
        }

        if ($data instanceof ElementInterface) {
            if (!$data->__isBasedOnLatestData()) {
                $this->logger->warning('Not saving {key} to cache as element is not based on latest data', [
                    'key' => $item->getKey()
                ]);

                // TODO: this check needs to be done recursive, especially for Objects (like cache tags)
                // all other entities shouldn't have references at all in the cache so it shouldn't matter
                return false;
            }
        }

        $result = $this->itemPool->save($item);

        if ($result) {
            $this->logger->debug('Added entry {key} to cache', ['key' => $item->getKey()]);
        } else {
            $this->logger->error(
                'Failed to add entry {key} to cache. Item size was {itemSize}',
                [
                    'key'      => $item->getKey(),
                    'itemSize' => formatBytes(strlen($item->get()))
                ]
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
        CacheItem::validateKey($key);

        $this->writeLock->lock();

        return $this->itemPool->deleteItem($key);
    }

    /**
     * Empty the cache
     *
     * @return bool
     */
    public function clearAll()
    {
        $this->writeLock->lock();

        $this->logger->info('Clearing the whole cache');

        $result = $this->itemPool->clear();

        // immediately acquire the write lock again (force), because the lock is in the cache too
        $this->writeLock->lock(true);

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
        $this->writeLock->lock();

        $originalTags = $tags;

        $this->logger->debug(
            'Clearing cache tags: {tags}',
            ['tags' => $tags]
        );

        $tags = $this->normalizeClearTags($tags);
        if (count($tags) > 0) {
            $result = $this->itemPool->invalidateTags($tags);

            if ($result) {
                $this->addClearedTags($tags);
            }

            return $result;
        }

        $this->logger->warning(
            'Could not clear tags as tag list is empty after normalization. List was: {tags}',
            [
                'tags'         => $tags,
                'originalTags' => $originalTags
            ]
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

        $this->logger->debug('Clearing shutdown cache tags: {tags}', ['tags' => $this->tagsClearedOnShutdown]);

        $result = $this->itemPool->invalidateTags($this->tagsClearedOnShutdown);

        if ($result) {
            $this->addClearedTags($this->tagsClearedOnShutdown);
            $this->tagsClearedOnShutdown = [];
        }

        return $result;
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
     * Add tag to list of cleared tags (internal use only)
     *
     * @param string $tags
     * @return $this
     */
    protected function addClearedTags($tags)
    {
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            $this->clearedTags[] = $tag;
        }

        $this->clearedTags = array_unique($this->clearedTags);

        return $this;
    }

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     *
     * @param string $tag
     * @return $this
     */
    public function addTagClearedOnShutdown($tag)
    {
        $this->writeLock->lock();

        $this->tagsClearedOnShutdown[] = $tag;
        $this->tagsClearedOnShutdown = array_unique($this->tagsClearedOnShutdown);

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function addTagIgnoredOnSave($tag)
    {
        $this->tagsIgnoredOnSave[] = $tag;
        $this->tagsIgnoredOnSave = array_unique($this->tagsIgnoredOnSave);

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function removeTagIgnoredOnSave($tag)
    {
        $this->tagsIgnoredOnSave = array_filter($this->tagsIgnoredOnSave, function ($t) use ($tag) {
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
        $this->tagsIgnoredOnClear = array_unique($this->tagsIgnoredOnClear);

        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function removeTagIgnoredOnClear($tag)
    {
        $this->tagsIgnoredOnClear = array_filter($this->tagsIgnoredOnClear, function ($t) use ($tag) {
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

        if ($this->writeLock->hasLock()) {
            if (count($this->saveQueue) > 0) {
                $this->logger->debug(
                    'Not writing save queue as there\'s an active write lock. Save queue contains {saveQueueCount} items.',
                    ['saveQueueCount' => count($this->saveQueue)]
                );
            }

            return false;
        }

        $processedKeys = [];
        foreach ($this->saveQueue as $queueItem) {
            $key = $queueItem->getCacheItem()->getKey();

            // check if key was already processed and don't save it again
            if (in_array($key, $processedKeys)) {
                $this->logger->warning('Not writing item as key {key} was already processed', ['key' => $key]);

                continue;
            }

            $cacheItem = $queueItem->getCacheItem();
            $cacheItem = $this->prepareCacheTags($cacheItem, $queueItem->getData(), $queueItem->getTags());

            $result = true;
            if (null === $cacheItem) {
                $result = false;
                $this->logger->error('Not writing item {key} to cache as prepareCacheTags failed', ['key' => $key]);
            } else {
                $result = $this->storeCacheItem($queueItem->getCacheItem(), $queueItem->getData(), $queueItem->isForce());
                if (!$result) {
                    $this->logger->error('Unable to write item {key} to cache', ['key' => $key]);
                }
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

        $doWrite = true;

        // writes make only sense for HTTP(S)
        // CLI are normally longer running scripts that tend to produce race conditions
        // so CLI scripts are not writing to the cache at all
        if ($this->isCli()) {
            if (!($this->handleCli || $forceWrite)) {
                $doWrite = false;

                $queueCount = count($this->saveQueue);
                if ($queueCount > 0) {
                    $this->logger->debug(
                        'Not writing save queue to cache as process is running in CLI mode. Save queue contains {saveQueueCount} items.',
                        ['saveQueueCount' => count($this->saveQueue)]
                    );
                }
            }
        }

        // write collected items to cache backend
        if ($doWrite) {
            $this->writeSaveQueue();
        }

        // remove the write lock
        $this->writeLock->removeLock();

        return $this;
    }

    /**
     * Purge orphaned/invalid data
     *
     * @return bool
     */
    public function purge()
    {
        $result = true;
        if ($this->itemPool instanceof PurgeableCacheItemPoolInterface) {
            $result = $this->itemPool->purge();
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return bool
     */
    protected function isCli()
    {
        return php_sapi_name() === 'cli';
    }
}
