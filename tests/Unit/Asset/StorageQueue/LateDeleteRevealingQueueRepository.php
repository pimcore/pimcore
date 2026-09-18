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

namespace Pimcore\Tests\Unit\Asset\StorageQueue;

use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepositoryInterface;

/**
 * The move-side mirror of LateMoveRevealingQueueRepository: a producer tombstones part of a
 * pending move's target while that move is already draining, and the row only becomes readable
 * once its transaction commits.
 *
 * The hidden Delete is revealed from the given findDeletesQueuedAfter() call onwards.
 */
final class LateDeleteRevealingQueueRepository implements StorageOperationQueueRepositoryInterface
{
    private int $deleteLookups = 0;

    public function __construct(
        private readonly InMemoryStorageOperationQueueRepository $inner,
        private readonly StorageOperation $hiddenDelete,
        private readonly int $revealFromCall,
    ) {
    }

    /**
     * @return StorageOperation[]
     */
    public function findDeletesQueuedAfter(string $storage, int $afterId): array
    {
        $deletes = $this->inner->findDeletesQueuedAfter($storage, $afterId);

        if (++$this->deleteLookups >= $this->revealFromCall
            && $this->hiddenDelete->getStorage() === $storage
            && (int) $this->hiddenDelete->getId() > $afterId
        ) {
            $deletes[] = $this->hiddenDelete;
        }

        return $deletes;
    }

    /**
     * @return StorageOperation[]
     */
    public function all(): array
    {
        return $this->inner->all();
    }

    public function findOverlappingMoveOlderThan(
        string $storage,
        string $prefix,
        int $beforeId
    ): ?StorageOperation {
        return $this->inner->findOverlappingMoveOlderThan($storage, $prefix, $beforeId);
    }

    public function add(StorageOperation $operation): void
    {
        $this->inner->add($operation);
    }

    public function repointMoves(string $storage, string $movedPrefix, string $newPrefix): void
    {
        $this->inner->repointMoves($storage, $movedPrefix, $newPrefix);
    }

    /**
     * @return StorageOperation[]
     */
    public function findCovering(string $storage, string $logicalPath): array
    {
        return $this->inner->findCovering($storage, $logicalPath);
    }

    /**
     * @return StorageOperation[]
     */
    public function findWithTargetUnder(string $storage, string $prefix): array
    {
        return $this->inner->findWithTargetUnder($storage, $prefix);
    }

    /**
     * @return StorageOperation[]
     */
    public function findSourceCovering(string $storage, string $path): array
    {
        return $this->inner->findSourceCovering($storage, $path);
    }

    public function hasOperations(string $storage): bool
    {
        return $this->inner->hasOperations($storage);
    }

    public function findById(int $id): ?StorageOperation
    {
        return $this->inner->findById($id);
    }

    public function remove(int $id): void
    {
        $this->inner->remove($id);
    }

    public function removeIfUnchanged(StorageOperation $operation): bool
    {
        return $this->inner->removeIfUnchanged($operation);
    }
}
