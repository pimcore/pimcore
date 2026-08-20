<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Asset\StorageQueue;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Pimcore\Cache\RuntimeCache;

/**
 * Queue of pending physical storage operations. Rows are also consulted by the
 * QueueAwareStorageAdapter to translate logical paths to their current physical location
 * while an operation is pending.
 *
 * @internal
 */
final class StorageOperationQueueRepository
{
    private const TABLE = 'asset_storage_operation_queue';

    private const HAS_OPERATIONS_CACHE_KEY = 'asset_storage_operation_queue_has_';

    public function __construct(private readonly Connection $db)
    {
    }

    public function add(StorageOperation $operation): void
    {
        if ($operation->getType() === StorageOperationType::Move) {
            $this->repointRowsUnder($operation->getStorage(), $operation->getSourcePrefix(), $operation->getTargetPrefix());
        } else {
            $this->convertCoveredMovesToDeletes($operation->getStorage(), $operation->getSourcePrefix());
        }

        $this->db->insert(self::TABLE, [
            'storage' => $operation->getStorage(),
            'operation' => $operation->getType()->value,
            'source_prefix' => $operation->getSourcePrefix(),
            'target_prefix' => $operation->getTargetPrefix(),
            'created_at' => $operation->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);

        $this->invalidateHasOperationsCache($operation->getStorage());
    }

    /**
     * @return StorageOperation[] move operations whose target prefix covers the logical path,
     *                            most specific target first
     */
    public function findCovering(string $storage, string $logicalPath): array
    {
        if (!$this->hasOperations($storage)) {
            return [];
        }

        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . "
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`target_prefix` = :path OR :path LIKE CONCAT(`target_prefix`, '/%'))
             ORDER BY LENGTH(`target_prefix`) DESC, `id` DESC",
            ['storage' => $storage, 'path' => $logicalPath]
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function hasOperations(string $storage): bool
    {
        $cacheKey = self::HAS_OPERATIONS_CACHE_KEY . $storage;
        if (RuntimeCache::isRegistered($cacheKey)) {
            return (bool) RuntimeCache::get($cacheKey);
        }

        $has = (bool) $this->db->fetchOne(
            'SELECT 1 FROM ' . self::TABLE . ' WHERE `storage` = :storage LIMIT 1',
            ['storage' => $storage]
        );
        RuntimeCache::set($cacheKey, $has);

        return $has;
    }

    /**
     * @return StorageOperation[]
     */
    public function all(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM ' . self::TABLE . ' ORDER BY `id` ASC');

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(int $id): ?StorageOperation
    {
        $row = $this->db->fetchAssociative(
            'SELECT * FROM ' . self::TABLE . ' WHERE `id` = :id',
            ['id' => $id]
        );

        return $row === false ? null : $this->hydrate($row);
    }

    public function remove(int $id): void
    {
        $operation = $this->findById($id);
        $this->db->delete(self::TABLE, ['id' => $id]);

        if ($operation !== null) {
            $this->invalidateHasOperationsCache($operation->getStorage());
        }
    }

    /**
     * A prefix that was itself the target of pending moves is moving on: repoint those rows to
     * the new target so lookups stay flat (single-hop candidates, never chains). Rows that
     * become self-mappings (moved back to their source) are dropped.
     */
    private function repointRowsUnder(string $storage, string $movedPrefix, string $newPrefix): void
    {
        $this->db->executeStatement(
            'UPDATE ' . self::TABLE . "
             SET `target_prefix` = CONCAT(:newPrefix, SUBSTRING(`target_prefix`, :cutLength))
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`target_prefix` = :movedPrefix OR `target_prefix` LIKE CONCAT(:movedPrefixParam, '/%'))",
            [
                'newPrefix' => $newPrefix,
                'cutLength' => strlen($movedPrefix) + 1,
                'storage' => $storage,
                'movedPrefix' => $movedPrefix,
                'movedPrefixParam' => $movedPrefix,
            ]
        );

        $this->db->executeStatement(
            'DELETE FROM ' . self::TABLE . "
             WHERE `storage` = :storage AND `operation` = 'move' AND `source_prefix` = `target_prefix`",
            ['storage' => $storage]
        );
    }

    /**
     * Deleting a prefix that is the target of pending moves logically deletes the legacy objects
     * still sitting at those moves' source prefixes: convert the move rows into delete rows.
     */
    private function convertCoveredMovesToDeletes(string $storage, string $deletedPrefix): void
    {
        $this->db->executeStatement(
            'UPDATE ' . self::TABLE . "
             SET `operation` = 'delete', `target_prefix` = NULL, `created_at` = :now
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`target_prefix` = :deletedPrefix OR `target_prefix` LIKE CONCAT(:deletedPrefixParam, '/%'))",
            [
                'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'storage' => $storage,
                'deletedPrefix' => $deletedPrefix,
                'deletedPrefixParam' => $deletedPrefix,
            ]
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): StorageOperation
    {
        return new StorageOperation(
            (int) $row['id'],
            (string) $row['storage'],
            StorageOperationType::from((string) $row['operation']),
            (string) $row['source_prefix'],
            $row['target_prefix'] === null ? null : (string) $row['target_prefix'],
            new DateTimeImmutable((string) $row['created_at']),
        );
    }

    private function invalidateHasOperationsCache(string $storage): void
    {
        $cacheKey = self::HAS_OPERATIONS_CACHE_KEY . $storage;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
    }
}
