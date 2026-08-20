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

namespace Pimcore\Tests\Service\Asset\StorageQueue;

use DateTimeImmutable;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepository;
use Pimcore\Asset\StorageQueue\StorageOperationType;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;

class StorageOperationQueueRepositoryTest extends TestCase
{
    private StorageOperationQueueRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Db::get()->executeStatement('DELETE FROM asset_storage_operation_queue');
        $this->repository = new StorageOperationQueueRepository(Db::get());
    }

    private function move(string $storage, string $source, string $target): StorageOperation
    {
        return new StorageOperation(null, $storage, StorageOperationType::Move, $source, $target, new DateTimeImmutable());
    }

    private function delete(string $storage, string $source): StorageOperation
    {
        return new StorageOperation(null, $storage, StorageOperationType::Delete, $source, null, new DateTimeImmutable());
    }

    public function testAddAndFindCoveringOrdersMostSpecificFirst(): void
    {
        $this->repository->add($this->move('asset', 'A', 'C'));
        $this->repository->add($this->move('asset', 'C/inner-old', 'C/inner'));

        $covering = $this->repository->findCovering('asset', 'C/inner/img.jpg');

        $this->assertCount(2, $covering);
        $this->assertSame('C/inner', $covering[0]->getTargetPrefix());
        $this->assertSame('C', $covering[1]->getTargetPrefix());
    }

    public function testFindCoveringMatchesOnPathBoundariesOnly(): void
    {
        $this->repository->add($this->move('asset', 'Car-old', 'Car'));

        $this->assertCount(1, $this->repository->findCovering('asset', 'Car/img.jpg'));
        $this->assertCount(1, $this->repository->findCovering('asset', 'Car'));
        $this->assertCount(0, $this->repository->findCovering('asset', 'Carpets/img.jpg'));
        $this->assertCount(0, $this->repository->findCovering('thumbnail', 'Car/img.jpg'));
    }

    public function testAddMoveRepointsExistingRowsUnderTheMovedPrefix(): void
    {
        // /A -> /B still pending, then /B -> /C: row 1 must become A -> C
        $this->repository->add($this->move('asset', 'A', 'B'));
        $this->repository->add($this->move('asset', 'B', 'C'));

        $all = $this->repository->all();
        $this->assertCount(2, $all);
        $pairs = array_map(
            static fn (StorageOperation $op) => $op->getSourcePrefix() . '->' . $op->getTargetPrefix(),
            $all
        );
        sort($pairs);
        $this->assertSame(['A->C', 'B->C'], $pairs);
    }

    public function testAddMoveBackDropsTheSelfMapping(): void
    {
        // /A -> /B pending, then /B -> /A: row 1 becomes A -> A and is dropped
        $this->repository->add($this->move('asset', 'A', 'B'));
        $this->repository->add($this->move('asset', 'B', 'A'));

        $all = $this->repository->all();
        $this->assertCount(1, $all);
        $this->assertSame('B', $all[0]->getSourcePrefix());
        $this->assertSame('A', $all[0]->getTargetPrefix());
    }

    public function testAddDeleteConvertsCoveredMoveRowsToDeletes(): void
    {
        // /A -> /B pending, then /B is deleted: legacy objects under A are logically deleted too
        $this->repository->add($this->move('asset', 'A', 'B'));
        $this->repository->add($this->delete('asset', 'B'));

        $all = $this->repository->all();
        $this->assertCount(2, $all);
        $byType = [];
        foreach ($all as $op) {
            $byType[$op->getType()->value][] = $op->getSourcePrefix();
        }
        sort($byType['delete']);
        $this->assertSame(['A', 'B'], $byType['delete']);
        $this->assertArrayNotHasKey('move', $byType);
    }

    public function testRepointOnlyAffectsTheSameStorage(): void
    {
        $this->repository->add($this->move('thumbnail', 'A', 'B'));
        $this->repository->add($this->move('asset', 'B', 'C'));

        $thumbnailRows = array_filter(
            $this->repository->all(),
            static fn (StorageOperation $op) => $op->getStorage() === 'thumbnail'
        );
        $this->assertSame('B', array_values($thumbnailRows)[0]->getTargetPrefix());
    }

    public function testHasOperationsAndRemove(): void
    {
        $this->assertFalse($this->repository->hasOperations('asset'));

        $this->repository->add($this->move('asset', 'A', 'B'));
        $this->assertTrue($this->repository->hasOperations('asset'));
        $this->assertFalse($this->repository->hasOperations('thumbnail'));

        $id = $this->repository->all()[0]->getId();
        $this->assertNotNull($this->repository->findById($id));

        $this->repository->remove($id);
        $this->assertFalse($this->repository->hasOperations('asset'));
        $this->assertNull($this->repository->findById($id));
    }

    public function testUnderscoreInPrefixDoesNotOverMatch(): void
    {
        // '_' is a legal asset-key character and a LIKE metacharacter - it must match literally
        $this->repository->add($this->move('asset', 'AxB-old', 'AxB'));
        $this->repository->add($this->delete('asset', 'A_B'));

        $all = $this->repository->all();
        $byType = [];
        foreach ($all as $op) {
            $byType[$op->getType()->value][] = $op->getSourcePrefix();
        }
        // the AxB move row must NOT have been converted by deleting A_B
        $this->assertSame(['AxB-old'], $byType['move'] ?? []);
        $this->assertSame(['A_B'], $byType['delete'] ?? []);

        // and a stored target 'A_B' must not cover 'AxB/...' paths
        $this->repository->add($this->move('asset', 'A_B-old', 'A_B'));
        $covering = $this->repository->findCovering('asset', 'AxB/img.jpg');
        $this->assertSame(['AxB'], array_map(static fn ($op) => $op->getTargetPrefix(), $covering));
    }

    public function testRepointHandlesMultibytePrefixes(): void
    {
        // umlauts are multi-byte in UTF-8; splice math must be character-based
        $this->repository->add($this->move('asset', 'Küchengeräte-old', 'Küchengeräte'));
        $this->repository->add($this->move('asset', 'Küchengeräte-old/Öfen-legacy', 'Küchengeräte/Öfen'));
        $this->repository->add($this->move('asset', 'Küchengeräte', 'Archiv/Küchengeräte'));

        $pairs = array_map(
            static fn ($op) => $op->getSourcePrefix() . '->' . $op->getTargetPrefix(),
            $this->repository->all()
        );
        sort($pairs);
        $this->assertSame([
            'Küchengeräte->Archiv/Küchengeräte',
            'Küchengeräte-old->Archiv/Küchengeräte',
            'Küchengeräte-old/Öfen-legacy->Archiv/Küchengeräte/Öfen',
        ], $pairs);
    }
}
