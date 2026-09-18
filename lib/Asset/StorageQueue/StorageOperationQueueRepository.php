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
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Pimcore\Cache\RuntimeCache;

/**
 * Queue of pending physical storage operations. Rows are also consulted by the
 * QueueAwareStorageAdapter to translate logical paths to their current physical location
 * while an operation is pending.
 *
 * @internal
 */
final class StorageOperationQueueRepository implements StorageOperationQueueRepositoryInterface
{
    private const TABLE = 'asset_storage_operation_queue';

    private const HAS_OPERATIONS_CACHE_KEY = 'asset_storage_operation_queue_has_';

    public function __construct(private readonly Connection $db)
    {
    }

    public function add(StorageOperation $operation): void
    {
        if ($operation->getType() === StorageOperationType::Move) {
            $this->repointMoves(
                $operation->getStorage(),
                $operation->getSourcePrefix(),
                $operation->getTargetPrefix(),
                $operation->getCopyOptions() ?? []
            );
        } else {
            $this->convertCoveredMovesToDeletes($operation->getStorage(), $operation->getSourcePrefix());
        }

        $this->db->insert(self::TABLE, [
            'storage' => $operation->getStorage(),
            'operation' => $operation->getType()->value,
            'source_prefix' => $operation->getSourcePrefix(),
            'target_prefix' => $operation->getTargetPrefix(),
            'created_at' => $operation->getCreatedAt()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'copy_options' => $operation->getCopyOptions() === null
                ? null
                : json_encode($operation->getCopyOptions(), JSON_THROW_ON_ERROR),
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
               AND (`target_prefix` = :path OR LEFT(:path2, CHAR_LENGTH(`target_prefix`) + 1) = CONCAT(`target_prefix`, '/'))
             ORDER BY LENGTH(`target_prefix`) DESC, `id` DESC",
            ['storage' => $storage, 'path' => $logicalPath, 'path2' => $logicalPath]
        );

        return array_map($this->hydrate(...), $rows);
    }

    /**
     * @return StorageOperation[] active move operations whose target prefix equals $prefix or
     *                            lies under it, shallowest target first
     */
    public function findWithTargetUnder(string $storage, string $prefix): array
    {
        if (!$this->hasOperations($storage)) {
            return [];
        }

        if ($prefix === '') {
            // every target lies under the root - no prefix comparison needed (and CONCAT('', '/')
            // would never match, since target_prefix never carries a leading slash)
            $rows = $this->db->fetchAllAssociative(
                'SELECT * FROM ' . self::TABLE . " WHERE `storage` = :storage AND `operation` = 'move'
                 ORDER BY LENGTH(`target_prefix`) ASC, `id` ASC",
                ['storage' => $storage]
            );

            return array_map($this->hydrate(...), $rows);
        }

        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . "
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`target_prefix` = :prefix OR LEFT(`target_prefix`, CHAR_LENGTH(:prefixB) + 1) = CONCAT(:prefixC, '/'))
             ORDER BY LENGTH(`target_prefix`) ASC, `id` ASC",
            ['storage' => $storage, 'prefix' => $prefix, 'prefixB' => $prefix, 'prefixC' => $prefix]
        );

        return array_map($this->hydrate(...), $rows);
    }

    /**
     * @return StorageOperation[] move operations whose source prefix covers the path, most
     *                            specific source first
     */
    public function findSourceCovering(string $storage, string $path): array
    {
        if (!$this->hasOperations($storage)) {
            return [];
        }

        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . "
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`source_prefix` = :path OR LEFT(:path2, CHAR_LENGTH(`source_prefix`) + 1) = CONCAT(`source_prefix`, '/'))
             ORDER BY LENGTH(`source_prefix`) DESC, `id` DESC",
            ['storage' => $storage, 'path' => $path, 'path2' => $path]
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

    public function removeIfUnchanged(StorageOperation $operation): bool
    {
        // copy_options is part of the compared state - a live repoint can rewrite it while leaving
        // the target alone, so a row matching on the other columns is not necessarily the row the
        // processor applied. It is compared in PHP rather than in the DELETE: the column is a real
        // JSON type on MySQL and LONGTEXT on MariaDB, so a literal <=> against a serialized string
        // only matches on the latter. Reading first opens no window the DELETE below does not
        // already have, and completeMove() refreshes and retries on a miss either way.
        $current = $this->findById((int) $operation->getId());
        if ($current === null
            || $this->canonicalCopyOptions($current->getCopyOptions())
               !== $this->canonicalCopyOptions($operation->getCopyOptions())
        ) {
            return false;
        }

        $affected = $this->db->executeStatement(
            'DELETE FROM ' . self::TABLE
            . ' WHERE `id` = :id AND `storage` = :storage AND `operation` = :operation'
            . ' AND `source_prefix` = :sourcePrefix AND (`target_prefix` <=> :targetPrefix)',
            [
                'id' => (int) $operation->getId(),
                'storage' => $operation->getStorage(),
                'operation' => $operation->getType()->value,
                'sourcePrefix' => $operation->getSourcePrefix(),
                'targetPrefix' => $operation->getTargetPrefix(),
            ]
        );

        if ($affected > 0) {
            $this->invalidateHasOperationsCache($operation->getStorage());

            return true;
        }

        return false;
    }

    /**
     * A prefix that was itself the target of pending moves is moving on: repoint those rows to
     * the new target so lookups stay flat (single-hop candidates, never chains). Rows that
     * become self-mappings (moved back to their source) are dropped.
     */
    public function repointMoves(
        string $storage,
        string $movedPrefix,
        string $newPrefix,
        ?array $copyOptions = null
    ): void {
        $parameters = [
            'newPrefix' => $newPrefix,
            'storage' => $storage,
            'movedPrefix' => $movedPrefix,
            'movedPrefixB' => $movedPrefix,
            'movedPrefixC' => $movedPrefix,
            'movedPrefixD' => $movedPrefix,
        ];

        $copyOptionsAssignment = '';
        if ($copyOptions !== null) {
            $copyOptionsAssignment = ', `copy_options` = :copyOptions';
            $parameters['copyOptions'] = $copyOptions === []
                ? null
                : json_encode($copyOptions, JSON_THROW_ON_ERROR);
        }

        $this->db->executeStatement(
            'UPDATE ' . self::TABLE . '
             SET `target_prefix` = CONCAT(:newPrefix, SUBSTRING(`target_prefix`, CHAR_LENGTH(:movedPrefixD) + 1))' . $copyOptionsAssignment . "
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`target_prefix` = :movedPrefix OR LEFT(`target_prefix`, CHAR_LENGTH(:movedPrefixB) + 1) = CONCAT(:movedPrefixC, '/'))",
            $parameters
        );

        $this->db->executeStatement(
            'DELETE FROM ' . self::TABLE . "
             WHERE `storage` = :storage AND `operation` = 'move' AND `source_prefix` = `target_prefix`",
            ['storage' => $storage]
        );

        // a self-mapping drop can empty the queue, so the cache needs invalidating even when
        // this is called outside of add() (e.g. after a native rename that queued nothing)
        $this->invalidateHasOperationsCache($storage);
    }

    /**
     * Deleting a prefix that is the target of pending moves logically deletes the legacy objects
     * still sitting at those moves' source prefixes: convert the move rows into delete rows. The
     * row's original `created_at` is preserved (NOT stamped with a fresh now): a fresh cutoff
     * would classify content written into a re-created source namespace between the move and the
     * delete as pre-cutoff and sweep it, when it must survive (Copilot review finding, PR #19383).
     */
    private function convertCoveredMovesToDeletes(string $storage, string $deletedPrefix): void
    {
        $this->db->executeStatement(
            'UPDATE ' . self::TABLE . "
             SET `operation` = 'delete', `target_prefix` = NULL, `copy_options` = NULL
             WHERE `storage` = :storage
               AND `operation` = 'move'
               AND (`target_prefix` = :deletedPrefix OR LEFT(`target_prefix`, CHAR_LENGTH(:deletedPrefixB) + 1) = CONCAT(:deletedPrefixC, '/'))",
            [
                'storage' => $storage,
                'deletedPrefix' => $deletedPrefix,
                'deletedPrefixB' => $deletedPrefix,
                'deletedPrefixC' => $deletedPrefix,
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
            new DateTimeImmutable((string) $row['created_at'], new DateTimeZone('UTC')),
            $this->decodeCopyOptions($row['copy_options'] ?? null),
        );
    }

    /**
     * A stable representation for comparing two option sets. MySQL normalises a JSON object's key
     * order on storage, so the decoded array need not come back in the order it went in; an empty
     * set and an absent one mean the same thing.
     *
     * @param array<string, mixed>|null $options
     */
    private function canonicalCopyOptions(?array $options): ?string
    {
        if ($options === null || $options === []) {
            return null;
        }

        ksort($options);

        return json_encode($options, JSON_THROW_ON_ERROR);
    }

    /**
     * Rows predating the column decode to null, as do installs that have not run the ALTER
     * statement yet - both keep the previous behaviour of copying with flysystem's own defaults.
     *
     * @return array<string, mixed>|null
     */
    private function decodeCopyOptions(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }

    /**
     * Per-request RuntimeCache flag ONLY (amended after Copilot review, PR #19383): a shared
     * cache layer here would race with the move's open transaction - another process could
     * re-cache `false` between the in-transaction invalidation and the commit, hiding the
     * committed move from queue-aware reads until that shared cache entry happened to expire.
     */
    private function invalidateHasOperationsCache(string $storage): void
    {
        $cacheKey = self::HAS_OPERATIONS_CACHE_KEY . $storage;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
    }
}
