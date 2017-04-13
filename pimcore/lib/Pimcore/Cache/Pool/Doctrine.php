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

use Doctrine\DBAL\Connection;
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
     * @param int $defaultLifetime
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

        $stmt = $this->db->executeQuery(
            'SELECT id, CASE WHEN expire IS NULL OR expire > ? THEN data ELSE NULL END FROM cache WHERE id IN (?)',
            [
                $now,
                $ids
            ],
            [
                \PDO::PARAM_INT,
                Connection::PARAM_INT_ARRAY
            ]
        );

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
        $result = $this->db->fetchColumn('SELECT 1 FROM cache WHERE id = :id AND (expire IS NULL OR expire > :time)', [
            'id'   => $id,
            'time' => time()
        ]);

        return (bool) $result;
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string @namespace The prefix used for all identifiers managed by this pool
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

        $this->db->commit();

        return true;
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

        $stmt = $this->db->executeQuery(
            'SELECT DISTINCT id FROM cache_tags WHERE tag IN (?)',
            [$tags],
            [Connection::PARAM_INT_ARRAY]
        );

        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $result;
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

                $stmt = $this->db->executeQuery($insertQuery, [
                    'id'     => $item->getKey(),
                    'data'   => $this->serializeData($item->get()),
                    'expire' => $item->getExpiry(),
                    'mtime'  => time()
                ]);

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

            $this->db->commit();

            return true;
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
     *
     * @return bool
     */
    protected function removeNotMatchingTags($id, array $tags)
    {
        $stmt = $this->db->executeQuery(
            'DELETE FROM cache_tags WHERE id = ? AND tag NOT IN (?)',
            [
                $id,
                $tags
            ],
            [
                \PDO::PARAM_INT,
                Connection::PARAM_STR_ARRAY
            ]
        );

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
        $stmt = $this->db->executeQuery('SELECT id FROM cache WHERE expire < UNIX_TIMESTAMP() OR mtime < (UNIX_TIMESTAMP() - 864000)');
        $ids  = $stmt->fetchAll(\PDO::FETCH_COLUMN);

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

            $this->db->commit();

            return true;
        }

        return true;
    }
}
