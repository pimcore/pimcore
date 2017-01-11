<?php

namespace Pimcore\Cache\Symfony\Adapter;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class CoreAdapter implements TagAwareAdapterInterface
{
    /**
     * Cache adapter to use when enabled
     * @var TagAwareAdapterInterface
     */
    protected $cacheAdapter;

    /**
     * Null adapter to use when not enabled
     * @var TagAwareAdapterInterface
     */
    protected $nullAdapter;

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
     * Tags which were already cleared
     * @var array
     */
    protected $clearedTagsStack = [];

    /**
     * Items having tags which are in this array are cleared on shutdown. This is especially for the output-cache.
     * @var array
     */
    protected $clearTagsOnShutown = [];

    /**
     * Ttems having one of the tags in this store are not cleared when calling invalidateTag/invalidateTags
     * @var array
     */
    protected $ignoredTagsOnClear = [];

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
     * @param AdapterInterface $cacheAdapter
     * @param AdapterInterface $nullAdapter
     */
    public function __construct(AdapterInterface $cacheAdapter, AdapterInterface $nullAdapter)
    {
        $this
            ->setAdapter('cacheAdapter', $cacheAdapter)
            ->setAdapter('nullAdapter', $nullAdapter);
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
        return $this->setEnabled(true);
    }

    /**
     * @param $property
     * @param AdapterInterface $adapter
     * @return $this
     */
    protected function setAdapter($property, AdapterInterface $adapter)
    {
        // if adapter is not tag aware, wrap it in TagAwareAdapter
        if (!$adapter instanceof TagAwareAdapterInterface) {
            $adapter = new TagAwareAdapter($adapter);
        }

        $this->$property = $adapter;

        return $this;
    }

    /**
     * @param bool $deleteAction
     * @return TagAwareAdapterInterface
     */
    public function getActiveAdapter($deleteAction = false)
    {
        if ($this->handleDeletesOnCacheAdapter) {
            // do not disable clearing, it's better purging items on active cache here than
            // having inconsistent data because of wrong usage
            if ($deleteAction) {
                return $this->cacheAdapter;
            }
        }

        return $this->enabled
            ? $this->cacheAdapter
            : $this->nullAdapter;
    }

    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        return $this
            ->getActiveAdapter()
            ->getItem($key);
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = [])
    {
        return $this
            ->getActiveAdapter()
            ->getItems([]);
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return $this
            ->getActiveAdapter()
            ->hasItem($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this
            ->getActiveAdapter(true)
            ->clear();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        return $this
            ->getActiveAdapter(true)
            ->deleteItem($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys)
    {
        return $this
            ->getActiveAdapter(true)
            ->deleteItems($keys);
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        return $this
            ->getActiveAdapter()
            ->save($item);
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this
            ->getActiveAdapter()
            ->saveDeferred($item);
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return $this
            ->getActiveAdapter()
            ->commit();
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function invalidateTag($tag)
    {
        return $this->invalidateTags([$tag]);
    }

    /**
     * @inheritDoc
     */
    public function invalidateTags(array $tags)
    {
        return $this
            ->getActiveAdapter(true)
            ->invalidateTags($tags);
    }
}
