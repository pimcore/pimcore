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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Bundle\CoreBundle\Command\CleanupClassificationStoreEavTablesCommand;
use Pimcore\Bundle\CoreBundle\Command\VerifyClassificationStoreCommand;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The last two steps: the verification that every document matches its own checksum and that no EAV
 * row survived, and the cleanup that drops the two EAV tables of a class - which it refuses, naming
 * the stores that are in the way, while any store the class uses is still on EAV.
 *
 * @group model.datatype.classificationstore
 */
class MigrationVerifyCleanupTest extends AbstractMigrationTest
{
    use MigrationClassFixtureTrait;

    private const CLEANUP_CLASS_ID = 'CsMigrationCleanup';

    private const MIGRATED_AT = 1762000000;

    private const CONVERTED_FIELD = 'csCleanupConverted';

    private const PENDING_FIELD = 'csCleanupPending';

    /**
     * A class of its own holding more documents than one verification batch, to walk the table
     * across two of its boundaries.
     */
    private const BATCH_CLASS_ID = 'CsMigrationBatch';

    private const BATCH_FIELD = 'csBatchField';

    private const BATCH_ROWS = 201;

    protected function tearDown(): void
    {
        $this->cleanUpMigrationFixture();

        parent::tearDown();
    }

    /**
     * A document whose `values` were changed without its checksum is named with its object, its
     * field and its group, and the run exits non-zero.
     */
    public function testVerifyNamesARowWhoseChecksumNoLongerMatchesItsValues(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'verified', 'verify-corrupt');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $rows = $this->readDocumentRows($objectId);
        $this->assertCount(1, $rows);

        Db::get()->executeStatement(
            'UPDATE ' . Db::get()->quoteIdentifier($this->getDocumentTable())
            . ' SET `values` = ? WHERE `id` = ?',
            ['{"v":1,"k":{}}', $objectId]
        );

        $tester = $this->runVerify();

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString(
            sprintf('object %d, field "%s", group %d', $objectId, self::FIELDNAME, (int) $rows[0]['groupId']),
            $tester->getDisplay()
        );
        $this->assertStringContainsString('checksum does not match', $tester->getDisplay());
    }

    /**
     * A pair the drain has not reached is an EAV row that survives for a field on the document
     * engine, which is the other half of the check.
     */
    public function testVerifyNamesAPairThatStillHoldsEavRows(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'not drained yet', 'verify-pending');
        $objectId = (int) $object->getId();

        $this->flipStore();

        $tester = $this->runVerify();

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString(
            sprintf('object %d, field "%s": EAV rows survive', $objectId, self::FIELDNAME),
            $tester->getDisplay()
        );
    }

    /**
     * A class whose EAV tables are gone has nothing left to survive, so the check is complete
     * rather than failing on the missing tables.
     */
    public function testVerifyPassesWhenTheEavTablesAreGone(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'drained', 'verify-complete');

        $this->flipStore();
        $this->getMigrationService()
            ->drainPair($this->getClass(), (int) $object->getId(), self::FIELDNAME, self::MIGRATED_AT);

        $this->dropTable($this->getDataTable());
        $this->dropTable($this->getGroupsTable());

        $tester = $this->runVerify();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('no EAV rows survive', $tester->getDisplay());
    }

    /**
     * A document an earlier release wrote carries neither a checksum nor a migration stamp, so
     * there is nothing to compare it against: it is reported as unproven rather than as corrupt,
     * and the run still passes.
     */
    public function testARowWrittenBeforeChecksumsWereStampedIsUnprovenRatherThanCorrupt(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'written earlier', 'verify-unproven');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        Db::get()->update(
            $this->getDocumentTable(),
            ['checksum' => null, 'migratedAt' => null],
            ['id' => $objectId]
        );

        $this->dropTable($this->getDataTable());
        $this->dropTable($this->getGroupsTable());

        $tester = $this->runVerify();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('written before checksums were stamped', $tester->getDisplay());
    }

    /**
     * The `values` column holds everything one group of one object stores, so the verification
     * walks the document table along its primary key a batch at a time rather than reading it in
     * one query - which is what keeps a class whose data does not fit in the process verifiable at
     * all. The rows either side of every batch boundary are the ones a resumption gets wrong, so
     * those are the ones corrupted here.
     */
    public function testVerifyWalksEveryDocumentOfAClassThatHoldsMoreThanOneBatch(): void
    {
        $storeId = $this->createMigrationStore();
        $class = $this->saveMigrationClass(
            self::BATCH_CLASS_ID,
            [$this->migrationStoreField(self::BATCH_FIELD, $storeId)]
        );
        $this->flipMigrationStore($storeId);

        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, 'unrelated', 'verify-batches')->getId();

        $groupIds = [];
        for ($index = 0; $index < self::BATCH_ROWS; $index++) {
            $groupIds[] = $this->createMigrationGroup($storeId, sprintf('csBatchGroup_%s_%d', uniqid(), $index));
        }
        sort($groupIds);

        foreach ($groupIds as $groupId) {
            $this->insertBatchDocumentRow($objectId, $groupId, '{"group":' . $groupId . '}');
        }

        // the first row, the two either side of each batch boundary, and the last one
        $corrupted = [
            $groupIds[0],
            $groupIds[99],
            $groupIds[100],
            $groupIds[199],
            $groupIds[self::BATCH_ROWS - 1],
        ];

        foreach ($corrupted as $groupId) {
            Db::get()->update(
                self::DOCUMENT_TABLE_PREFIX . self::BATCH_CLASS_ID,
                ['checksum' => sha1('something else')],
                ['id' => $objectId, 'fieldname' => self::BATCH_FIELD, 'groupId' => $groupId]
            );
        }

        $report = $this->getMigrationService()->verify($class);

        foreach ($corrupted as $groupId) {
            $this->assertContains(
                sprintf(
                    'object %d, field "%s", group %d: the stored checksum does not match its values',
                    $objectId,
                    self::BATCH_FIELD,
                    $groupId
                ),
                $report->failures
            );
        }

        $this->assertCount(count($corrupted), $report->failures, 'and no row is seen twice');
        $this->assertSame([], $report->unproven);
    }

    /**
     * The flip is per store while the cleanup is per class, so a second field on a store nobody
     * flipped keeps both tables - and the refusal names that store by id and by name, because that
     * is the one place the route is not self-evident.
     */
    public function testCleanupRefusesAndNamesTheStoreStillBlockingIt(): void
    {
        [, $pendingStoreId, $pendingStoreName] = $this->setUpClassWithTwoStores();

        $tester = $this->runCleanup();

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString(
            sprintf('store %d ("%s")', $pendingStoreId, $pendingStoreName),
            $tester->getDisplay()
        );

        $this->assertTrue($this->tableExists(self::DATA_TABLE_PREFIX . self::CLEANUP_CLASS_ID));
        $this->assertTrue($this->tableExists(self::GROUPS_TABLE_PREFIX . self::CLEANUP_CLASS_ID));
    }

    /**
     * Once every store the class uses is on the document engine and the verification of that class
     * holds, both EAV tables go.
     */
    public function testCleanupDropsBothTablesOnceEveryStoreIsConverted(): void
    {
        [$class, $pendingStoreId] = $this->setUpClassWithTwoStores();

        $this->convertRemainingStore($class, $pendingStoreId);

        $tester = $this->runCleanup();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertFalse($this->tableExists(self::DATA_TABLE_PREFIX . self::CLEANUP_CLASS_ID));
        $this->assertFalse($this->tableExists(self::GROUPS_TABLE_PREFIX . self::CLEANUP_CLASS_ID));
    }

    /**
     * A dry run reports what it would drop and drops nothing.
     */
    public function testADryRunReportsWhatItWouldDropAndDropsNothing(): void
    {
        [$class, $pendingStoreId] = $this->setUpClassWithTwoStores();

        $this->convertRemainingStore($class, $pendingStoreId);

        $tester = $this->runCleanup(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString(
            self::DATA_TABLE_PREFIX . self::CLEANUP_CLASS_ID,
            $tester->getDisplay()
        );
        $this->assertTrue($this->tableExists(self::DATA_TABLE_PREFIX . self::CLEANUP_CLASS_ID));
        $this->assertTrue($this->tableExists(self::GROUPS_TABLE_PREFIX . self::CLEANUP_CLASS_ID));
    }

    /**
     * A class carrying one classification store field on a flipped store, whose rows are already
     * converted, and one on a store that is still on EAV.
     *
     * @return array{0: ClassDefinition, 1: int, 2: string}
     */
    private function setUpClassWithTwoStores(): array
    {
        $convertedStoreId = $this->createMigrationStore();
        $pendingStoreId = $this->createMigrationStore();

        $convertedGroupId = $this->createMigrationGroup($convertedStoreId);
        $convertedKeyId = $this->createMigrationKey($convertedStoreId, $convertedGroupId);
        $pendingGroupId = $this->createMigrationGroup($pendingStoreId);
        $pendingKeyId = $this->createMigrationKey($pendingStoreId, $pendingGroupId);

        $class = $this->saveMigrationClass(self::CLEANUP_CLASS_ID, [
            $this->migrationStoreField(self::CONVERTED_FIELD, $convertedStoreId),
            $this->migrationStoreField(self::PENDING_FIELD, $pendingStoreId),
        ]);

        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, 'unrelated', 'cleanup-owner')->getId();

        $this->seedMigrationEavRows(
            self::CLEANUP_CLASS_ID,
            $objectId,
            self::CONVERTED_FIELD,
            $convertedGroupId,
            $convertedKeyId,
            'converted'
        );
        $this->seedMigrationEavRows(
            self::CLEANUP_CLASS_ID,
            $objectId,
            self::PENDING_FIELD,
            $pendingGroupId,
            $pendingKeyId,
            'still on eav'
        );

        $this->flipMigrationStore($convertedStoreId);
        $this->drainBacklog($class);

        $pendingStore = StoreConfig::getById($pendingStoreId);
        $this->assertInstanceOf(StoreConfig::class, $pendingStore);

        return [$class, $pendingStoreId, (string) $pendingStore->getName()];
    }

    private function convertRemainingStore(ClassDefinition $class, int $pendingStoreId): void
    {
        $this->flipMigrationStore($pendingStoreId);
        $this->drainBacklog($class);
    }

    private function drainBacklog(ClassDefinition $class): void
    {
        $service = $this->getMigrationService();

        foreach ($service->getBacklog($class) as [$objectId, $fieldname]) {
            $service->drainPair($class, $objectId, $fieldname, self::MIGRATED_AT);
        }

        $this->assertSame([], $service->getBacklog($class));
    }

    /**
     * One document row, stamped the way the drain stamps it, so the verification has a checksum to
     * recompute rather than an unproven row.
     */
    private function insertBatchDocumentRow(int $objectId, int $groupId, string $values): void
    {
        Db::get()->insert(self::DOCUMENT_TABLE_PREFIX . self::BATCH_CLASS_ID, [
            'id' => $objectId,
            'fieldname' => self::BATCH_FIELD,
            'groupId' => $groupId,
            'collectionId' => null,
            'values' => $values,
            'checksum' => sha1($values),
            'migratedAt' => self::MIGRATED_AT,
        ]);
    }

    private function runVerify(): CommandTester
    {
        $tester = new CommandTester(new VerifyClassificationStoreCommand($this->getMigrationService()));
        $tester->execute(['--class' => $this->getClassId()], ['interactive' => false]);

        return $tester;
    }

    /**
     * @param array<string, string|bool> $input
     */
    private function runCleanup(array $input = []): CommandTester
    {
        $tester = new CommandTester(new CleanupClassificationStoreEavTablesCommand($this->getMigrationService()));
        $tester->execute($input + ['--class' => self::CLEANUP_CLASS_ID], ['interactive' => false]);

        return $tester;
    }
}
