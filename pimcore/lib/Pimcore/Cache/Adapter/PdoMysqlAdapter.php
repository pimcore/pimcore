<?php

namespace Pimcore\Cache\Adapter;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\InvalidArgumentException as CacheInvalidArgumentException;

class PdoMysqlAdapter implements TagAwareAdapterInterface, PurgeAwareAdapterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @var string
     */
    protected $namespace = '';

    /**
     * @var int|null The maximum length to enforce for identifiers or null when no limit applies
     */
    protected $maxIdLength;

    /**
     * @var CacheItem[]
     */
    protected $deferred = [];

    /**
     * @var \Closure
     */
    protected $createCacheItem;

    /**
     * @var \Closure
     */
    protected $mergeByLifetime;

    /**
     * @var \Closure
     */
    protected $extractCacheData;

    /**
     * @param \PDO $db
     * @param int $defaultLifetime
     */
    public function __construct(\PDO $db, $defaultLifetime = 0)
    {
        $this->db = $db;

        if (null !== $this->maxIdLength && strlen($this->namespace) > $this->maxIdLength - 24) {
            throw new CacheInvalidArgumentException(
                sprintf('Namespace must be %d chars max, %d given ("%s")', $this->maxIdLength - 24, strlen($this->namespace), $this->namespace)
            );
        }

        $this->initCreateCacheItemClosure($defaultLifetime);
        $this->initMergeByLifetimeClosure();
        $this->initExtractCacheDataClosure();
    }

    protected function initCreateCacheItemClosure($defaultLifetime = 0)
    {
        $this->createCacheItem = \Closure::bind(
            function ($key, $value, $isHit) use ($defaultLifetime) {
                $item                  = new CacheItem();
                $item->key             = $key;
                $item->value           = $value;
                $item->isHit           = $isHit;
                $item->defaultLifetime = $defaultLifetime;

                return $item;
            },
            null,
            CacheItem::class
        );
    }

    protected function initMergeByLifetimeClosure()
    {
        $this->mergeByLifetime = \Closure::bind(
            function ($deferred, $namespace, &$expiredIds) {
                $byLifetime = array();
                $now        = time();
                $expiredIds = array();

                foreach ($deferred as $key => $item) {
                    if (null === $item->expiry) {
                        $byLifetime[0 < $item->defaultLifetime ? $item->defaultLifetime : 0][$namespace . $key] = $item->value;
                    } elseif ($item->expiry > $now) {
                        $byLifetime[$item->expiry - $now][$namespace . $key] = $item->value;
                    } else {
                        $expiredIds[] = $namespace . $key;
                    }
                }

                return $byLifetime;
            },
            null,
            CacheItem::class
        );
    }

    protected function initExtractCacheDataClosure()
    {
        $this->extractCacheData = \Closure::bind(
            function (CacheItem $item) {
                return [
                    'tags'   => $item->tags,
                    'expiry' => $item->expiry
                ];
            },
            null,
            CacheItem::class
        );
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

        $f = $this->createCacheItem;
        $isHit = false;
        $value = null;

        try {
            foreach ($this->doFetch(array($id)) as $value) {
                $isHit = true;
            }
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch key "{key}"', array('key' => $key, 'exception' => $e));
        }

        return $f($key, $value, $isHit);
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
    public function getItems(array $keys = array())
    {
        if ($this->deferred) {
            $this->commit();
        }
        $ids = array();

        foreach ($keys as $key) {
            $ids[] = $this->getId($key);
        }
        try {
            $items = $this->doFetch($ids);
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch requested items', array('keys' => $keys, 'exception' => $e));
            $items = array();
        }
        $ids = array_combine($ids, $keys);

        return $this->generateItems($items, $ids);
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
        $now = time();

        $idCondition = str_pad('', (count($ids) << 1) - 1, '?,');
        $fetchQuery  = 'SELECT id, CASE WHEN expire IS NULL OR expire > ? THEN data ELSE NULL FROM cache WHERE id IN (' . $idCondition . ')';

        $stmt = $this->db->prepare($fetchQuery);

        $i = 1;
        $stmt->bindValue($i++, $now, \PDO::PARAM_INT);
        foreach ($ids as $id) {
            $stmt->bindValue($i++, $id);
        }

        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            if (null !== $row[1]) {
                $value = unserialize($row[1]);

                yield $row[0] => $value;
            }
        }
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
            CacheItem::log($this->logger, 'Failed to check if key "{key}" is cached', array('key' => $key, 'exception' => $e));

            return false;
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
        $sql = "SELECT 1 FROM cache WHERE id = :id AND (expire IS NULL OR expire > :time)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $this->deferred = array();

        try {
            return $this->doClear($this->namespace);
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to clear the cache', array('exception' => $e));

            return false;
        }
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear($namespace)
    {
        $this->db->beginTransaction();

        foreach (['cache', 'cache_tags'] as $table) {
            $this->db->exec('TRUNCATE TABLE ' . $table);
        }

        return $this->db->commit();
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
        return $this->deleteItems(array($key));
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
        $ids = array();

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
                if ($this->doDelete(array($id))) {
                    continue;
                }
            } catch (\Exception $e) {
            }
            CacheItem::log($this->logger, 'Failed to delete key "{key}"', array('key' => $key, 'exception' => $e));
            $ok = false;
        }

        return $ok;
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
        $cacheStmt = $this->db->prepare('DELETE FROM cache WHERE id = ?');
        $tagsStmt  = $this->db->prepare('DELETE FROM cache_tags WHERE id = ?');

        $this->db->beginTransaction();

        try {
            foreach ($ids as $id) {
                $cacheStmt->execute([$id]);
                $tagsStmt->execute([$id]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->doClear($this->namespace); // truncate

            return false;
        }

        return true;
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
        if (!$item instanceof CacheItem) {
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
        if (!$item instanceof CacheItem) {
            return false;
        }
        $this->deferred[$item->getKey()] = $item;

        return true;
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

        $ok = true;
        $extract = $this->extractCacheData;

        try {
            $this->db->beginTransaction();

            foreach ($this->deferred as $item) {
                $data = $extract($item);

                // TODO INSERT..ON DUPLICATE KEY UPDATE?
                $insertQuery = 'REPLACE INTO cache (id, data, expire, mtime) VALUES (:id, :data, :expire, :mtime)';
                $stmt = $this->db->prepare($insertQuery);

                $stmt->bindParam(':id', $item->getKey());
                $stmt->bindParam(':data', serialize($item->get()));
                $stmt->bindParam(':expire', $data['expiry']);
                $stmt->bindParam(':mtime', time());

                $ok = $ok && $stmt->execute();

                if (count($data['tags']) > 0) {
                    $tagQuery = 'REPLACE INTO cache_tags (id, tag) VALUES (?, ?)';
                    $tagStmt  = $this->db->prepare($tagQuery);

                    while ($tag = array_shift($data['tags'])) {
                        $ok = $ok && $tagStmt->execute([$item->getKey(), $tag]);
                    }
                }
            }

            $ok = $ok && $this->db->commit();
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->db->rollBack();

            return false;
        }

        return $ok;
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws InvalidArgumentException When $tags is not valid
     */
    public function invalidateTags(array $tags)
    {
        $keys = $this->getItemKeysByTags($tags);

        return $this->deleteItems($keys);
    }

    /**
     * @param array $tags
     * @return array
     */
    protected function getItemKeysByTags(array $tags)
    {
        if (empty($tags)) {
            return [];
        }

        $tagCondition = str_pad('', (count($tags) << 1) - 1, '?,');
        $fetchQuery   = 'SELECT id FROM cache_tags WHERE tag IN (' . $tagCondition . ')';

        $stmt = $this->db->prepare($fetchQuery);

        $i = 1;
        foreach ($tags as $tag) {
            $stmt->bindValue($i++, $tag);
        }

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Do maintenance tasks - e.g. purge invalid items. This can take a long time and should only be called
     * for maintenance, not in code affecting the end user.
     */
    public function purge()
    {
        $expiredStmt = $this->db->prepare('SELECT id FROM cache WHERE expire < UNIX_TIMESTAMP() OR mtime < (UNIX_TIMESTAMP() - 864000)');
        $expiredStmt->execute();

        $expiredIds = $expiredStmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->deleteItems($expiredIds);

        $orphanedTagsStmt = $this->db->prepare('SELECT ct.id, ct.tag FROM cache_tags ct LEFT JOIN cache c ON c.id = ct.id WHERE c.id IS NULL');
        $orphanedTagsStmt->execute();

        if ($orphanedTagsStmt->rowCount() > 0) {
            $this->db->beginTransaction();

            $deleteStmt = $this->db->prepare('DELETE FROM cache_tags WHERE id = :id AND tag = :tag');

            while ($row = $orphanedTagsStmt->fetch(\PDO::FETCH_ASSOC)) {
                $deleteStmt->execute([
                    'id'  => $row['id'],
                    'tag' => $row['tag']
                ]);
            }

            $this->db->commit();
        }
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getId($key)
    {
        CacheItem::validateKey($key);

        if (null === $this->maxIdLength) {
            return $this->namespace . $key;
        }
        if (strlen($id = $this->namespace . $key) > $this->maxIdLength) {
            $id = $this->namespace . substr_replace(base64_encode(hash('sha256', $key, true)), ':', -22);
        }

        return $id;
    }

    /**
     * @param $items
     * @param $keys
     * @return \Generator
     */
    protected function generateItems($items, &$keys)
    {
        $f = $this->createCacheItem;

        try {
            foreach ($items as $id => $value) {
                $key = $keys[$id];
                unset($keys[$id]);
                yield $key => $f($key, $value, true);
            }
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch requested items', array('keys' => array_values($keys), 'exception' => $e));
        }

        foreach ($keys as $key) {
            yield $key => $f($key, null, false);
        }
    }
}
