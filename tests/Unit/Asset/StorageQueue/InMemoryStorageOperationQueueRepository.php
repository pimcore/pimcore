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

use DateTimeImmutable;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepositoryInterface;
use Pimcore\Asset\StorageQueue\StorageOperationType;

/**
 * In-memory fake implementing the repository contract for adapter unit tests, including the
 * repoint / convert / self-mapping-drop semantics the real repository implements in SQL.
 */
final class InMemoryStorageOperationQueueRepository implements StorageOperationQueueRepositoryInterface
{
    /** @var StorageOperation[] */
    private array $operations = [];

    private int $nextId = 1;

    public function add(StorageOperation $operation): void
    {
        if ($operation->getType() === StorageOperationType::Move) {
            $this->repointMoves($operation->getStorage(), $operation->getSourcePrefix(), (string) $operation->getTargetPrefix());
        } else {
            $this->convert($operation->getStorage(), $operation->getSourcePrefix());
        }

        $this->operations[] = new StorageOperation(
            $this->nextId++,
            $operation->getStorage(),
            $operation->getType(),
            $operation->getSourcePrefix(),
            $operation->getTargetPrefix(),
            $operation->getCreatedAt(),
        );
    }

    public function findCovering(string $storage, string $logicalPath): array
    {
        $covering = array_filter(
            $this->operations,
            static fn (StorageOperation $op) => $op->getStorage() === $storage
                && $op->getType() === StorageOperationType::Move
                && ($op->getTargetPrefix() === $logicalPath
                    || str_starts_with($logicalPath, $op->getTargetPrefix() . '/'))
        );
        usort(
            $covering,
            static fn (StorageOperation $a, StorageOperation $b) =>
                [mb_strlen((string) $b->getTargetPrefix()), $b->getId()] <=> [mb_strlen((string) $a->getTargetPrefix()), $a->getId()]
        );

        return array_values($covering);
    }

    public function hasOperations(string $storage): bool
    {
        foreach ($this->operations as $op) {
            if ($op->getStorage() === $storage) {
                return true;
            }
        }

        return false;
    }

    public function all(): array
    {
        return array_values($this->operations);
    }

    public function findById(int $id): ?StorageOperation
    {
        foreach ($this->operations as $op) {
            if ($op->getId() === $id) {
                return $op;
            }
        }

        return null;
    }

    public function remove(int $id): void
    {
        $this->operations = array_values(
            array_filter($this->operations, static fn (StorageOperation $op) => $op->getId() !== $id)
        );
    }

    public function removeIfUnchanged(StorageOperation $operation): bool
    {
        foreach ($this->operations as $i => $op) {
            if ($op->getId() === $operation->getId()
                && $op->getStorage() === $operation->getStorage()
                && $op->getType() === $operation->getType()
                && $op->getSourcePrefix() === $operation->getSourcePrefix()
                && $op->getTargetPrefix() === $operation->getTargetPrefix()
            ) {
                unset($this->operations[$i]);
                $this->operations = array_values($this->operations);

                return true;
            }
        }

        return false;
    }

    public function repointMoves(string $storage, string $movedPrefix, string $newPrefix): void
    {
        $this->repoint($storage, $movedPrefix, $newPrefix);
    }

    private function repoint(string $storage, string $movedPrefix, string $newPrefix): void
    {
        foreach ($this->operations as $i => $op) {
            $target = $op->getTargetPrefix();
            if ($op->getStorage() !== $storage || $op->getType() !== StorageOperationType::Move || $target === null) {
                continue;
            }
            if ($target === $movedPrefix || str_starts_with($target, $movedPrefix . '/')) {
                $newTarget = $newPrefix . mb_substr($target, mb_strlen($movedPrefix));
                if ($newTarget === $op->getSourcePrefix()) {
                    unset($this->operations[$i]); // self-mapping drop

                    continue;
                }
                $this->operations[$i] = new StorageOperation(
                    $op->getId(), $op->getStorage(), $op->getType(), $op->getSourcePrefix(), $newTarget, $op->getCreatedAt()
                );
            }
        }
        $this->operations = array_values($this->operations);
    }

    private function convert(string $storage, string $deletedPrefix): void
    {
        foreach ($this->operations as $i => $op) {
            $target = $op->getTargetPrefix();
            if ($op->getStorage() !== $storage || $op->getType() !== StorageOperationType::Move || $target === null) {
                continue;
            }
            if ($target === $deletedPrefix || str_starts_with($target, $deletedPrefix . '/')) {
                $this->operations[$i] = new StorageOperation(
                    $op->getId(), $op->getStorage(), StorageOperationType::Delete, $op->getSourcePrefix(), null, new DateTimeImmutable()
                );
            }
        }
    }
}
