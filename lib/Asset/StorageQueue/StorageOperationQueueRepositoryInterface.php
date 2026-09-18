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

/**
 * @internal
 */
interface StorageOperationQueueRepositoryInterface
{
    public function add(StorageOperation $operation): void;

    /**
     * Repoints pending move rows whose target equals or lies under the moved prefix, dropping
     * resulting self-mappings; invalidates the has-operations cache (rows may be dropped).
     */
    public function repointMoves(string $storage, string $movedPrefix, string $newPrefix): void;

    /**
     * Move operations whose target prefix covers the logical path, most specific target first.
     *
     * @return StorageOperation[]
     */
    public function findCovering(string $storage, string $logicalPath): array;

    /**
     * Active move operations whose target prefix equals the given prefix or lies under it
     * (pattern-free comparison). Used for ancestor listings: a pending move's target may be a
     * descendant of a listed path even though nothing physically exists there yet.
     *
     * @return StorageOperation[]
     */
    public function findWithTargetUnder(string $storage, string $prefix): array;

    /**
     * Active move operations whose source prefix covers the given path (equal, or the path
     * starts with source_prefix . '/'), most specific source first. A key under a pending
     * move's source prefix may hold the only physical copy of the moved logical file - used to
     * guard literal writes from destroying it (Copilot round-3 finding, PR #19383).
     *
     * @return StorageOperation[]
     */
    public function findSourceCovering(string $storage, string $path): array;

    /**
     * The oldest pending Move row on this storage with an id below $beforeId whose source OR
     * target prefix overlaps $prefix in either nesting direction, or null when there is none.
     *
     * Targeted on purpose: the processor asks this repeatedly while sweeping a Delete, and
     * hydrating the whole queue for each of those checks would make a large Delete cost
     * O(files x queue size).
     */
    public function findOverlappingMoveOlderThan(
        string $storage,
        string $prefix,
        int $beforeId
    ): ?StorageOperation;

    /**
     * Pending Delete rows on this storage queued after $afterId, oldest first.
     *
     * Targeted for the same reason as the lookup above: a Move consults this while draining, and
     * hydrating the whole queue per Move row would make a run cost O(moves x queue size).
     *
     * @return StorageOperation[]
     */
    public function findDeletesQueuedAfter(string $storage, int $afterId): array;

    public function hasOperations(string $storage): bool;

    /**
     * All pending operations, ordered by id ascending (FIFO — load-bearing for processing order).
     *
     * @return StorageOperation[]
     */
    public function all(): array;

    public function findById(int $id): ?StorageOperation;

    public function remove(int $id): void;

    /**
     * Compare-and-delete: removes the row only if it still matches every given column (id,
     * storage, operation type, source prefix, target prefix) at the moment of the delete.
     * Guards against removing a row that live traffic mutated (repoint on re-move, conversion
     * to a delete on a covering delete) after the processor read it into memory but before it
     * finished applying it - the caller must reconcile and retry when this returns false.
     */
    public function removeIfUnchanged(StorageOperation $operation): bool;
}
