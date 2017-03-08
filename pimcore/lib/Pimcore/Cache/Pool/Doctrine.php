<?php

namespace Pimcore\Cache\Pool;

use Doctrine\DBAL\Driver\Connection;
use Pimcore\Cache\Pool\Exception\CacheException;
use Pimcore\Cache\Pool\Exception\InvalidArgumentException;

class Doctrine extends AbstractCacheItemPool implements PurgeableCacheItemPoolInterface
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db, $defaultLifetime = 0)
    {
        parent::__construct($defaultLifetime);

        $this->db = $db;
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

        if (empty($ids)) {
            return;
        }

        $idCondition = str_pad('', (count($ids) << 1) - 1, '?,');
        $fetchQuery  = 'SELECT id, CASE WHEN expire IS NULL OR expire > ? THEN data ELSE NULL END FROM cache WHERE id IN (' . $idCondition . ')';

        $stmt = $this->db->prepare($fetchQuery);

        $i = 1;
        $stmt->bindValue($i++, $now, \PDO::PARAM_INT);
        foreach ($ids as $id) {
            $stmt->bindValue($i++, $id);
        }

        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            if (null !== $row[1]) {
                $value = $this->unserializeData($row[1]);

                // we don't load tags from the DB, therefore $cacheItem->getPreviousTags() doesn't return anything
                // if we need previous tags, update the query to join the tags table and to return them as result
                yield $row[0] => [
                    'value' => $value,
                    'tags'  => []
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

        $this->db->exec('ALTER TABLE cache_tags ENGINE=InnoDB');

        return $this->db->commit();
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
        $this->db->beginTransaction();

        $cacheStmt = $this->db->prepare('DELETE FROM cache WHERE id = ?');
        $tagsStmt  = $this->db->prepare('DELETE FROM cache_tags WHERE id = ?');

        try {
            foreach ($ids as $id) {
                $cacheStmt->execute([$id]);
                $tagsStmt->execute([$id]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->clear(); // truncate on error

            return false;
        }

        return true;
    }

    /**
     * Fetches all item keys matching the given tags
     *
     * @param array $tags
     *
     * @return array
     */
    protected function getItemKeysByTags(array $tags)
    {
        if (empty($tags)) {
            return [];
        }

        $tagCondition = str_pad('', (count($tags) << 1) - 1, '?,');
        $fetchQuery   = 'SELECT DISTINCT id FROM cache_tags WHERE tag IN (' . $tagCondition . ')';

        $stmt = $this->db->prepare($fetchQuery);

        $i = 1;
        foreach ($tags as $tag) {
            $stmt->bindValue($i++, $tag);
        }

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
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
        $keys = $this->getItemKeysByTags($tags);

        return $this->deleteItems($keys);
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

        try {
            $this->db->beginTransaction();

            // save every item in the deferred queue, even if expired to make sure expired items are updated
            // in the DB (see CachePoolTest::testSaveExpired())
            /** @var PimcoreCacheItemInterface $item */
            foreach ($this->deferred as $key => $item) {
                // remove item from queue to make sure it is processed only once
                unset($this->deferred[$key]);

                $insertQuery = <<<SQL
INSERT INTO
    cache (id, data, expire, mtime) VALUES (:id, :data, :expire, :mtime)
    ON DUPLICATE KEY UPDATE data = VALUES(data), expire = VALUES(expire), mtime = VALUES(mtime)
SQL;

                $stmt = $this->db->prepare($insertQuery);

                $stmt->bindParam(':id', $item->getKey());
                $stmt->bindParam(':data', $this->serializeData($item->get()));
                $stmt->bindParam(':expire', $item->getExpiry());
                $stmt->bindParam(':mtime', time());
                $result = $stmt->execute();

                if (!$result) {
                    throw new CacheException(sprintf('Failed to execute insert query for item %s', $item->getKey()));
                }

                $tags = $item->getTags();
                if (count($tags) > 0) {
                    $this->removeNotMatchingTags($item->getKey(), $tags);

                    $tagQuery = 'INSERT INTO cache_tags (id, tag) VALUES (?, ?) ON DUPLICATE KEY UPDATE tag = VALUES(tag)';
                    $tagStmt  = $this->db->prepare($tagQuery);

                    while ($tag = array_shift($tags)) {
                        $tagStmt->execute([$item->getKey(), $tag]);
                    }
                }
            }

            return $this->db->commit();
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->db->rollBack();

            return false;
        }
    }

    /**
     * Remove all not matching tags from item
     *
     * @param $id
     * @param array $tags
     * @return bool
     */
    protected function removeNotMatchingTags($id, array $tags)
    {
        $condition = str_pad('', (count($tags) << 1) - 1, '?,');
        $query     = 'DELETE FROM cache_tags WHERE id = ? AND tag NOT IN (' . $condition . ')';

        $stmt = $this->db->prepare($query);

        $i = 1;
        $stmt->bindValue($i++, $id);
        foreach ($tags as $tag) {
            $stmt->bindValue($i++, $tag);
        }

        return $stmt->execute();
    }

    /**
     * Runs maintenance tasks which could take a long time. Should only be called from maintenance scripts.
     *
     * @return bool True on success
     */
    public function purge()
    {
        // TODO purge tags only if expired items job was successful?
        $items = $this->purgeExpiredItems();
        $tags  = $this->purgeOrphanedTags();

        return $items && $tags;
    }

    /**
     * @return bool
     */
    protected function purgeExpiredItems()
    {
        $stmt = $this->db->prepare('SELECT id FROM cache WHERE expire < UNIX_TIMESTAMP() OR mtime < (UNIX_TIMESTAMP() - 864000)');
        $stmt->execute();

        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($ids)) {
            return $this->deleteItems($ids);
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function purgeOrphanedTags()
    {
        $stmt = $this->db->prepare('SELECT ct.id, ct.tag FROM cache_tags ct LEFT JOIN cache c ON c.id = ct.id WHERE c.id IS NULL');
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $this->db->beginTransaction();

            $deleteStmt = $this->db->prepare('DELETE FROM cache_tags WHERE id = :id AND tag = :tag');

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $deleteStmt->execute([
                    'id'  => $row['id'],
                    'tag' => $row['tag']
                ]);
            }

            return $this->db->commit();
        }

        return true;
    }
}
