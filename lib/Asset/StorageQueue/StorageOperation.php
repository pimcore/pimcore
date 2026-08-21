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
use InvalidArgumentException;

/**
 * A pending physical storage operation: a folder move whose objects still (partly) live under
 * the source prefix, or a folder deletion whose objects are not yet physically removed.
 *
 * @internal
 */
final readonly class StorageOperation
{
    public function __construct(
        private ?int $id,
        private string $storage,
        private StorageOperationType $type,
        private string $sourcePrefix,
        private ?string $targetPrefix,
        private DateTimeImmutable $createdAt,
    ) {
        $this->assertValidPrefix($sourcePrefix);

        if ($type === StorageOperationType::Move) {
            if ($targetPrefix === null) {
                throw new InvalidArgumentException('A move operation requires a target prefix');
            }
            $this->assertValidPrefix($targetPrefix);
        }

        if ($type === StorageOperationType::Delete && $targetPrefix !== null) {
            throw new InvalidArgumentException('A delete operation must not carry a target prefix');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getType(): StorageOperationType
    {
        return $this->type;
    }

    public function getSourcePrefix(): string
    {
        return $this->sourcePrefix;
    }

    public function getTargetPrefix(): ?string
    {
        return $this->targetPrefix;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function assertValidPrefix(string $prefix): void
    {
        if ($prefix === '' || str_starts_with($prefix, '/') || str_ends_with($prefix, '/')) {
            throw new InvalidArgumentException(
                sprintf('Storage prefix must be a non-empty relative path without leading/trailing slash, got "%s"', $prefix)
            );
        }
    }
}
