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

use LogicException;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepositoryInterface;

/**
 * Fake implementing the repository contract where every method throws. On a disabled install the
 * `asset_storage_operation_queue` table may not exist at all - a single stray repository call from
 * the disabled adapter would throw (e.g. a real `Doctrine\DBAL\Exception\TableNotFoundException`).
 * Used to prove the disabled adapter never touches the repository, for any public method.
 */
final class ThrowingStorageOperationQueueRepository implements StorageOperationQueueRepositoryInterface
{
    public function add(StorageOperation $operation): void
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function repointMoves(string $storage, string $movedPrefix, string $newPrefix): void
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function findCovering(string $storage, string $logicalPath): array
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function findWithTargetUnder(string $storage, string $prefix): array
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function findSourceCovering(string $storage, string $path): array
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function hasOperations(string $storage): bool
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function all(): array
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function findById(int $id): ?StorageOperation
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function remove(int $id): void
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }

    public function removeIfUnchanged(StorageOperation $operation): bool
    {
        throw new LogicException(__METHOD__ . ' must not be called when the adapter is disabled');
    }
}
