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
use Pimcore;
use Pimcore\Asset\StorageQueue\FrontendPathResolver;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepository;
use Pimcore\Asset\StorageQueue\StorageOperationType;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;

class StorageOperationQueueRepositoryTest extends TestCase
{
    private StorageOperationQueueRepository $repository;

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The dedicated migration was removed in favor of documented manual table setup (see
        // doc/02_Assets/05_Asset_Storage_Operation_Queue.md, which is the canonical DDL) - a
        // fresh test environment bootstrapped from install.sql no longer has this table, so this
        // test class creates it itself. Kept in sync with the doc's CREATE TABLE statement.
        Db::get()->executeStatement(
            'CREATE TABLE IF NOT EXISTS `asset_storage_operation_queue` (
                `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
                `storage` VARCHAR(50) NOT NULL,
                `operation` ENUM(\'move\',\'delete\') NOT NULL,
                `source_prefix` VARCHAR(765) NOT NULL,
                `target_prefix` VARCHAR(765) DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;'
        );
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

    public function testFindSourceCoveringMatchesEqualCoveringAndSiblingPrefix(): void
    {
        $this->repository->add($this->move('asset', 'A', 'B'));
        $this->repository->add($this->move('asset', 'A/inner', 'B/inner')); // nested source, more specific

        // covering: exact source match
        $exact = $this->repository->findSourceCovering('asset', 'A');
        $this->assertSame(['A'], array_map(static fn (StorageOperation $op) => $op->getSourcePrefix(), $exact));

        // covering: descendant of the source, most specific first
        $covering = $this->repository->findSourceCovering('asset', 'A/inner/img.jpg');
        $this->assertCount(2, $covering);
        $this->assertSame('A/inner', $covering[0]->getSourcePrefix());
        $this->assertSame('A', $covering[1]->getSourcePrefix());

        // sibling prefix boundary: 'A-old' must not match source 'A'
        $this->repository->add($this->move('asset', 'A-old', 'C'));
        $this->assertSame([], array_filter(
            $this->repository->findSourceCovering('asset', 'A-old/img.jpg'),
            static fn (StorageOperation $op) => $op->getSourcePrefix() === 'A'
        ));
        $this->assertSame(
            ['A-old'],
            array_map(static fn (StorageOperation $op) => $op->getSourcePrefix(), $this->repository->findSourceCovering('asset', 'A-old/img.jpg'))
        );

        // different storage must not match
        $this->assertSame([], $this->repository->findSourceCovering('thumbnail', 'A/inner/img.jpg'));

        // a path outside any source prefix matches nothing
        $this->assertSame([], $this->repository->findSourceCovering('asset', 'Unrelated/img.jpg'));
    }

    public function testFindWithTargetUnderMatchesDescendantsAndRoot(): void
    {
        $this->repository->add($this->move('asset', 'A', 'Archive/A'));
        $this->repository->add($this->move('asset', 'Other', 'ArchiveSibling'));

        $underArchive = $this->repository->findWithTargetUnder('asset', 'Archive');
        $this->assertCount(1, $underArchive);
        $this->assertSame('Archive/A', $underArchive[0]->getTargetPrefix());

        // sibling prefix boundary: 'ArchiveSibling' must not match 'Archive'
        $this->assertSame(['Archive/A'], array_map(
            static fn (StorageOperation $op) => $op->getTargetPrefix(),
            $underArchive
        ));

        $atRoot = $this->repository->findWithTargetUnder('asset', '');
        $targets = array_map(static fn (StorageOperation $op) => $op->getTargetPrefix(), $atRoot);
        sort($targets);
        $this->assertSame(['Archive/A', 'ArchiveSibling'], $targets);

        $this->assertSame([], $this->repository->findWithTargetUnder('thumbnail', 'Archive'));
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

    public function testRepointMovesWithoutInsert(): void
    {
        $this->repository->add($this->move('asset', 'A', 'B'));

        $this->repository->repointMoves('asset', 'B', 'C');

        $all = $this->repository->all();
        $this->assertCount(1, $all);
        $this->assertSame('A', $all[0]->getSourcePrefix());
        $this->assertSame('C', $all[0]->getTargetPrefix());
    }

    public function testRepointMovesDropsSelfMappingAndInvalidatesHasOperationsCache(): void
    {
        $this->repository->add($this->move('asset', 'A', 'B'));

        $this->repository->repointMoves('asset', 'B', 'A');

        $this->assertSame([], $this->repository->all());
        $this->assertFalse($this->repository->hasOperations('asset'));
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

    public function testRemoveIfUnchangedMatchesAllColumns(): void
    {
        $this->repository->add($this->move('asset', 'A', 'B'));
        $stored = $this->repository->all()[0];

        // target differs from what's stored - must not remove, row stays queued
        $stale = new StorageOperation(
            $stored->getId(), 'asset', StorageOperationType::Move, 'A', 'Elsewhere', $stored->getCreatedAt()
        );
        $this->assertFalse($this->repository->removeIfUnchanged($stale));
        $this->assertNotNull($this->repository->findById((int) $stored->getId()));
        $this->assertTrue($this->repository->hasOperations('asset'));

        // full match (including a delete row's NULL target, via the null-safe comparison) - removes
        $this->assertTrue($this->repository->removeIfUnchanged($stored));
        $this->assertNull($this->repository->findById((int) $stored->getId()));
        $this->assertFalse($this->repository->hasOperations('asset'), 'cache invalidated on success');

        $this->repository->add($this->delete('asset', 'Trash'));
        $deleteRow = $this->repository->all()[0];
        $this->assertTrue($this->repository->removeIfUnchanged($deleteRow), 'null target_prefix matches via null-safe comparison');
        $this->assertFalse($this->repository->hasOperations('asset'));
    }

    public function testConversionPreservesOriginalCreatedAt(): void
    {
        // /A -> /B pending, queued an hour ago; then /B is deleted - the converted A row must
        // keep its ORIGINAL cutoff, not a fresh "now" (a fresh cutoff would sweep content written
        // into a re-created source namespace between the move and the delete).
        $original = new DateTimeImmutable('-1 hour');
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'A', 'B', $original
        ));
        $this->repository->add($this->delete('asset', 'B'));

        $all = $this->repository->all();
        $converted = null;
        foreach ($all as $op) {
            if ($op->getSourcePrefix() === 'A') {
                $converted = $op;
            }
        }
        $this->assertNotNull($converted);
        $this->assertSame(StorageOperationType::Delete, $converted->getType());
        $this->assertEqualsWithDelta(
            $original->getTimestamp(),
            $converted->getCreatedAt()->getTimestamp(),
            2,
            'conversion must preserve the original created_at, not stamp a fresh now'
        );
    }

    public function testCreatedAtRoundTripsAcrossTimezones(): void
    {
        $originalTz = date_default_timezone_get();

        try {
            date_default_timezone_set('Pacific/Kiritimati'); // UTC+14
            $written = new DateTimeImmutable('now');
            $this->repository->add(new StorageOperation(
                null, 'asset', StorageOperationType::Move, 'TzSource', 'TzTarget', $written
            ));

            date_default_timezone_set('Etc/GMT+12'); // UTC-12 - 26h apart from the writer
            $read = $this->repository->all()[0]->getCreatedAt();

            $this->assertEqualsWithDelta(
                $written->getTimestamp(),
                $read->getTimestamp(),
                2,
                'created_at must represent the same instant regardless of PHP default timezone'
            );
        } finally {
            date_default_timezone_set($originalTz);
        }
    }

    public function testFrontendPathResolverIsWiredAndResolvesThroughRealRepository(): void
    {
        $resolver = Pimcore::getContainer()->get(FrontendPathResolver::class);
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'WiredSource', 'WiredTarget', new DateTimeImmutable()
        ));

        $resolved = $resolver->resolvePhysicalPath('/WiredTarget/a.jpg');

        // the container-built resolver carries enabled=false (test kernel default),
        // so it must return identity - this pins BOTH the wiring and the zero-cost
        // disabled guard against the real container
        $this->assertSame('/WiredTarget/a.jpg', $resolved);

        // and the mapping itself against the REAL repository (bypassing the bool):
        $enabledResolver = new FrontendPathResolver($this->repository, true);
        $this->assertSame('/WiredSource/a.jpg', $enabledResolver->resolvePhysicalPath('/WiredTarget/a.jpg'));
    }
}
