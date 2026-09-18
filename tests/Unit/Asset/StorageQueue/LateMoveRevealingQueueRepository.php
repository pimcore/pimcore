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
 * Models the queue-insertion race: a producer allocates a Move row id inside an open transaction
 * and commits it only afterwards, so a row with a LOWER id than an already-visible Delete becomes
 * readable part-way through that Delete's run.
 *
 * The hidden row is revealed from the given all() call onwards, which is the same read the
 * processor uses to refresh its pending-move snapshot.
 */
final class LateMoveRevealingQueueRepository implements StorageOperationQueueRepositoryInterface
{
    private int $blockerChecks = 0;

    public function __construct(
        private readonly InMemoryStorageOperationQueueRepository $inner,
        private readonly StorageOperation $hiddenMove,
        private readonly int $revealFromCall,
    ) {
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
        $found = $this->inner->findOverlappingMoveOlderThan($storage, $prefix, $beforeId);
        if ($found !== null) {
            return $found;
        }

        if (++$this->blockerChecks < $this->revealFromCall) {
            return null; // not committed yet
        }

        return $this->hiddenMove->getStorage() === $storage && (int) $this->hiddenMove->getId() < $beforeId
            ? $this->hiddenMove
            : null;
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
