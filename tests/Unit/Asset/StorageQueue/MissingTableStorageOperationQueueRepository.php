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

use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepositoryInterface;
use RuntimeException;

/**
 * Fake implementing the repository contract where every method throws
 * Doctrine\DBAL\Exception\TableNotFoundException, simulating a fresh install where the feature
 * flag is enabled (or the commands are otherwise reached) but the asset_storage_operation_queue
 * table has not been created yet (its dedicated migration was removed in favor of documented
 * manual setup - see doc/02_Assets/05_Asset_Storage_Operation_Queue.md).
 */
final class MissingTableStorageOperationQueueRepository implements StorageOperationQueueRepositoryInterface
{
    public function add(StorageOperation $operation): void
    {
        throw self::exception();
    }

    public function repointMoves(string $storage, string $movedPrefix, string $newPrefix): void
    {
        throw self::exception();
    }

    public function findCovering(string $storage, string $logicalPath): array
    {
        throw self::exception();
    }

    public function findWithTargetUnder(string $storage, string $prefix): array
    {
        throw self::exception();
    }

    public function findSourceCovering(string $storage, string $path): array
    {
        throw self::exception();
    }

    public function hasOperations(string $storage): bool
    {
        throw self::exception();
    }

    public function all(): array
    {
        throw self::exception();
    }

    public function findById(int $id): ?StorageOperation
    {
        throw self::exception();
    }

    public function remove(int $id): void
    {
        throw self::exception();
    }

    public function removeIfUnchanged(StorageOperation $operation): bool
    {
        throw self::exception();
    }

    private static function exception(): TableNotFoundException
    {
        return new TableNotFoundException(
            new class extends RuntimeException implements DriverExceptionInterface {
                public function getSQLState(): ?string
                {
                    return '42S02';
                }
            },
            null
        );
    }
}
