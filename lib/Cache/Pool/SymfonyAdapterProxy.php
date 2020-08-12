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

namespace Pimcore\Cache\Pool;

use Pimcore\Cache\Pool\Exception\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

class SymfonyAdapterProxy extends AbstractCacheItemPool
{
    /**
     * @var TagAwareAdapterInterface
     */
    protected $adapter;

    /**
     * @var \Closure
     */
    protected $transformItemClosure;

    /**
     * @param TagAwareAdapterInterface|AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter, $defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);

        // auto-wrap adapter in tag aware adapter if the passed adapter is not tag aware
        if (!($adapter instanceof TagAwareAdapterInterface)) {
            $adapter = new TagAwareAdapter($adapter);
        }

        $this->adapter = $adapter;
    }

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return array|\Traversable The corresponding values found in the cache
     */
    protected function doFetch(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $keys = [];
        foreach ($ids as $id) {
            $keys[$id] = $id;
        }

        /** @var CacheItem $item */
        foreach ($this->adapter->getItems($ids) as $item) {
            if ($item->isHit()) {
                $data = $this->unserializeData($item->get());

                yield $item->getKey() => [
                    'value' => $data,
                    'tags' => [],
                ];
            }
        }
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    protected function doHave($id)
    {
        return $this->adapter->getItem($id)->isHit();
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string $namespace The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear($namespace)
    {
        return $this->adapter->clear($namespace);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    protected function doDelete(array $ids)
    {
        return $this->adapter->deleteItems($ids);
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        if (empty($this->deferred)) {
            return true;
        }

        $keys = array_keys($this->deferred);
        $symfonyItems = [];

        /** @var CacheItem $symfonyItem */
        foreach ($this->adapter->getItems($keys) as $symfonyItem) {
            $symfonyItems[$symfonyItem->getKey()] = $symfonyItem;
        }

        foreach ($keys as $key) {
            $cacheItem = $this->deferred[$key];
            $symfonyItem = $symfonyItems[$key];

            unset($this->deferred[$key]);
            unset($symfonyItems[$key]);

            $this->transformItem($cacheItem, $symfonyItem);

            $this->adapter->saveDeferred($symfonyItem);
        }

        if (!empty($this->deferred) || !empty($symfonyItems)) {
            $this->logger->error('Not all deferred cache items were processed', [
                'deferred' => array_keys($this->deferred),
                'symfony' => array_keys($symfonyItems),
            ]);

            return false;
        }

        return $this->adapter->commit();
    }

    /**
     * @param PimcoreCacheItemInterface $cacheItem
     * @param CacheItem $symfonyItem
     */
    protected function transformItem(PimcoreCacheItemInterface $cacheItem, CacheItem $symfonyItem)
    {
        if (null === $this->transformItemClosure) {
            $this->transformItemClosure = \Closure::bind(
                function (CacheItem $symfonyItem, $data, array $tags, $expiry) {
                    $symfonyItem->value = $data;
                    $symfonyItem->expiry = $expiry;

                    if (property_exists($symfonyItem, 'tags')) {
                        $symfonyItem->tags = [];
                    }

                    $symfonyItem->tag($tags);
                },
                null,
                CacheItem::class
            );
        }

        $tags = $cacheItem->getTags();

        $closure = $this->transformItemClosure;
        $closure($symfonyItem, $this->serializeData($cacheItem->get()), $tags, $cacheItem->getExpiry());
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @throws InvalidArgumentException When $tags is not valid
     *
     * @return bool True on success
     */
    protected function doInvalidateTags(array $tags)
    {
        return $this->adapter->invalidateTags($tags);
    }
}
