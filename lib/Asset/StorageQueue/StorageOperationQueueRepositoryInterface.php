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

    public function hasOperations(string $storage): bool;

    /**
     * All pending operations, ordered by id ascending (FIFO — load-bearing for processing order).
     *
     * @return StorageOperation[]
     */
    public function all(): array;

    public function findById(int $id): ?StorageOperation;

    public function remove(int $id): void;
}
