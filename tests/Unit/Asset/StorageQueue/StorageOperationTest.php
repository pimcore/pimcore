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

use Codeception\Test\Unit;
use DateTimeImmutable;
use InvalidArgumentException;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationType;

class StorageOperationTest extends Unit
{
    public function testMoveOperationCarriesItsData(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-18 03:00:00');
        $operation = new StorageOperation(7, 'asset', StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns', $createdAt);

        $this->assertSame(7, $operation->getId());
        $this->assertSame('asset', $operation->getStorage());
        $this->assertSame(StorageOperationType::Move, $operation->getType());
        $this->assertSame('Campaigns', $operation->getSourcePrefix());
        $this->assertSame('Archive/Campaigns', $operation->getTargetPrefix());
        $this->assertSame($createdAt, $operation->getCreatedAt());
    }

    public function testDeleteOperationHasNoTargetPrefix(): void
    {
        $operation = new StorageOperation(null, 'thumbnail', StorageOperationType::Delete, 'Campaigns', null, new DateTimeImmutable());

        $this->assertNull($operation->getId());
        $this->assertNull($operation->getTargetPrefix());
    }

    public function testMoveWithoutTargetPrefixIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StorageOperation(null, 'asset', StorageOperationType::Move, 'Campaigns', null, new DateTimeImmutable());
    }

    public function testDeleteWithTargetPrefixIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StorageOperation(null, 'asset', StorageOperationType::Delete, 'Campaigns', 'Archive', new DateTimeImmutable());
    }

    public function testEmptyOrSlashWrappedPrefixesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StorageOperation(null, 'asset', StorageOperationType::Move, '/Campaigns/', 'Archive', new DateTimeImmutable());
    }
}
