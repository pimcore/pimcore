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

use Pimcore\Bundle\CoreBundle\Command\DrainClassificationStoreCommand;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainOutcome;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The drain: one (objectId, fieldname) pair per transaction, write-if-absent, and a class that
 * halts on a divergence while the next one carries on. Reads and writes of the class keep working
 * throughout, which is what makes the conversion something an installation can run while it serves
 * requests.
 *
 * @group model.datatype.classificationstore
 */
class MigrationDrainTest extends AbstractMigrationTest
{
    use MigrationClassFixtureTrait;

    private const MIGRATED_AT = 1762000000;

    /**
     * A second class, holding more pairs than the halting one so that it is drained after it - the
     * backlog runs smallest class first.
     */
    private const NEXT_CLASS_ID = 'CsMigrationDrainNext';

    /**
     * @var string[] the field names the second class carries, one pair each
     */
    private const NEXT_CLASS_FIELDS = ['csNextOne', 'csNextTwo', 'csNextThree'];

    /**
     * A class whose id is numeric, which is what a class gets by default - the name has to be given
     * separately, as a class name cannot start with a digit.
     */
    private const NUMERIC_CLASS_ID = '990001';

    private const NUMERIC_CLASS_NAME = 'csMigrationNumericId';

    private const NUMERIC_CLASS_FIELD = 'csNumericField';

    protected function tearDown(): void
    {
        $this->cleanUpMigrationFixture();

        parent::tearDown();
    }

    /**
     * A converted object, an unconverted one and one converted between the two reads: all three
     * answer correctly, and a user save during the drain converts its own object.
     */
    public function testReadsAndWritesSucceedWhileTheClassIsMidDrain(): void
    {
        $first = $this->createObjectWithValue(self::INPUT_KEY, 'first', 'drain-first');
        $second = $this->createObjectWithValue(self::INPUT_KEY, 'second', 'drain-second');
        $third = $this->createObjectWithValue(self::INPUT_KEY, 'third', 'drain-third');

        $this->flipStore();
        $service = $this->getMigrationService();

        $this->assertSame(
            DrainOutcome::Converted,
            $service->drainPair($this->getClass(), (int) $first->getId(), self::FIELDNAME, self::MIGRATED_AT)->outcome
        );

        // the converted object reads its document, the unconverted ones still read through
        $this->assertSame('first', $this->readValue((int) $first->getId()));
        $this->assertSame('second', $this->readValue((int) $second->getId()));
        $this->assertSame('third', $this->readValue((int) $third->getId()));

        // a user save in the middle of the run converts its own object
        $reloaded = $this->reload((int) $third->getId());
        $reloaded->getCsstore()->setLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY),
            'third, saved mid-drain'
        );
        $reloaded->save();

        $this->assertSame(
            DrainOutcome::Converted,
            $service->drainPair($this->getClass(), (int) $second->getId(), self::FIELDNAME, self::MIGRATED_AT)->outcome
        );

        $this->assertSame('first', $this->readValue((int) $first->getId()));
        $this->assertSame('second', $this->readValue((int) $second->getId()));
        $this->assertSame('third, saved mid-drain', $this->readValue((int) $third->getId()));

        foreach ([$first, $second, $third] as $object) {
            $this->assertSame(0, $this->countEavRowsFor((int) $object->getId()));
        }
    }

    /**
     * A pair that already holds a document is counted as migrated: nothing is written over it, and
     * the EAV rows that survived beside it are removed when they hold nothing the documents do not.
     */
    public function testAPairThatAlreadyHoldsADocumentIsNotRewritten(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'saved by a user', 'drain-already');
        $objectId = (int) $object->getId();

        $this->flipStore();

        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setLocalizedKeyValue($this->getGroupId(), $this->getKeyId(self::INPUT_KEY), 'by a user');
        $reloaded->save();

        $before = $this->readDocumentRows($objectId);
        $this->assertCount(1, $before);

        // the EAV rows the user save removed are put back, holding what the documents hold, so that
        // the drain finds the pair in its backlog with a document already written
        $this->seedEavRows($objectId, 'by a user');
        $this->assertGreaterThan(0, $this->countEavRowsFor($objectId));

        $result = $this->getMigrationService()
            ->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $this->assertSame(DrainOutcome::AlreadyMigrated, $result->outcome);
        $this->assertSame($before, $this->readDocumentRows($objectId), 'the document bytes stay as they were');
        $this->assertSame(0, $this->countEavRowsFor($objectId));
    }

    /**
     * Which engine a store resolves to is memoized for the length of a request, so a process that
     * resolved EAV before the flip keeps writing EAV rows after its pair was converted. Those rows
     * hold values no document holds: the drain reports the pair and leaves both sides where they
     * are, because a drain that deleted them would be the one losing them.
     */
    public function testAConvertedPairWhoseEavRowsHoldOtherValuesIsReportedRatherThanDeleted(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'written on eav', 'drain-stale-writer');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $service = $this->getMigrationService();

        $this->assertSame(
            DrainOutcome::Converted,
            $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT)->outcome
        );
        $converted = $this->readDocumentRows($objectId);
        $this->assertCount(1, $converted);
        $this->assertSame(0, $this->countEavRowsFor($objectId));

        // the writer that still resolved the EAV engine saves after the pair was drained
        $this->seedEavRows($objectId, 'written by a process that had not seen the flip');
        $eavRows = $this->countEavRowsFor($objectId);
        $this->assertGreaterThan(0, $eavRows);

        $result = $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $this->assertSame(DrainOutcome::Diverged, $result->outcome);
        $this->assertStringContainsString('does not account for', (string) $result->divergence);
        $this->assertSame($eavRows, $this->countEavRowsFor($objectId), 'the newer values are not deleted');
        $this->assertSame($converted, $this->readDocumentRows($objectId), 'and nothing is written over them');
    }

    /**
     * The question is asked one way round: a surviving group activation row with no values holds
     * nothing the documents could be missing, so it is removed rather than reported - and neither
     * is the pair reported for the values the documents hold and the surviving rows do not.
     */
    public function testAConvertedPairWhoseSurvivingEavRowsHoldNoValueIsStillCountedAsMigrated(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'written on eav', 'drain-empty-group-row');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $service = $this->getMigrationService();
        $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $converted = $this->readDocumentRows($objectId);
        $this->seedEmptyGroupRow($objectId);
        $this->assertSame(1, $this->countEavRowsFor($objectId));

        $result = $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $this->assertSame(DrainOutcome::AlreadyMigrated, $result->outcome);
        $this->assertSame(0, $this->countEavRowsFor($objectId));
        $this->assertSame($converted, $this->readDocumentRows($objectId));
    }

    /**
     * There is no cursor: a re-run reads the backlog from the EAV rows that are left, so the pairs
     * an interrupted run did not reach convert and the ones it did are not written again.
     */
    public function testARerunContinuesFromTheSurvivingEavRows(): void
    {
        $first = $this->createObjectWithValue(self::INPUT_KEY, 'first', 'rerun-first');
        $second = $this->createObjectWithValue(self::INPUT_KEY, 'second', 'rerun-second');

        $this->flipStore();
        $service = $this->getMigrationService();

        $this->assertCount(2, $service->getBacklog($this->getClass()));

        // the run is interrupted after one pair
        $service->drainPair($this->getClass(), (int) $first->getId(), self::FIELDNAME, self::MIGRATED_AT);
        $converted = $this->readDocumentRows((int) $first->getId());

        $backlog = $service->getBacklog($this->getClass());
        $this->assertSame([[(int) $second->getId(), self::FIELDNAME]], $backlog);

        foreach ($backlog as [$objectId, $fieldname]) {
            $this->assertSame(
                DrainOutcome::Converted,
                $service->drainPair($this->getClass(), $objectId, $fieldname, self::MIGRATED_AT)->outcome
            );
        }

        $this->assertSame([], $service->getBacklog($this->getClass()));
        $this->assertSame($converted, $this->readDocumentRows((int) $first->getId()));
        $this->assertNotSame([], $this->readDocumentRows((int) $second->getId()));
    }

    /**
     * Batching, throttling and the run lock are the parallelization trait's, which is also what
     * puts the batch and segment options on the command.
     */
    public function testTheBatchingAndTheRunLockComeFromTheParallelizationTrait(): void
    {
        $this->assertContains(Parallelization::class, class_uses(DrainClassificationStoreCommand::class));

        $definition = $this->drainCommand()->getDefinition();
        foreach (['processes', 'main-process', 'batch-size', 'segment-size'] as $option) {
            $this->assertTrue($definition->hasOption($option), $option . ' comes from the trait');
        }
    }

    /**
     * A pair that does not compare equal keeps its EAV rows, loses its document to the rollback, is
     * named in the output and halts its class at the threshold - while the next class carries on.
     * The run exits non-zero because a class halted.
     */
    public function testADivergingPairHaltsItsClassAndTheRunExitsNonZero(): void
    {
        // the value tables carry a foreign key on `objects`, which a folder satisfies: the pair is
        // then one no concrete object answers, which is a divergence rather than an error
        $folder = new Folder();
        $folder->setParentId(1);
        $folder->setKey('drain-divergence-folder');
        $folder->save();

        $object = $this->createObjectWithValue(self::INPUT_KEY, 'converts fine', 'drain-divergence');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $this->seedEavRows((int) $folder->getId(), 'a row no object answers');

        $tester = $this->runDrain(['--max-divergences' => '1']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());

        $display = $tester->getDisplay();
        $this->assertStringContainsString(sprintf('object %d, field "csstore"', (int) $folder->getId()), $display);
        $this->assertStringContainsString('halted', $display);

        // the diverging pair stays wholly on EAV
        $this->assertGreaterThan(0, $this->countEavRowsFor((int) $folder->getId()));
        $this->assertSame([], $this->readDocumentRows((int) $folder->getId()));

        // and the class stopped, so the pair behind it was not touched either
        $this->assertGreaterThan(0, $this->countEavRowsFor($objectId));
        $this->assertSame([], $this->readDocumentRows($objectId));
    }

    /**
     * The threshold is per class: the class that reached it is skipped from there on, and the next
     * class is drained all the same. The run still exits non-zero, because a class halted.
     */
    public function testAHaltedClassDoesNotStopTheNextOne(): void
    {
        $folder = new Folder();
        $folder->setParentId(1);
        $folder->setKey('drain-next-class-folder');
        $folder->save();

        $object = $this->createObjectWithValue(self::INPUT_KEY, 'never reached', 'drain-next-class');
        $objectId = (int) $object->getId();

        $nextClass = $this->setUpNextClass($objectId);

        $this->flipStore();
        $this->seedEavRows((int) $folder->getId(), 'a row no object answers');

        // the halting class holds two pairs and the next one three, so the halting class is first
        $tester = $this->runDrain([], true);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());

        // the backlog is announced class by class in the order it is drained in, which is where the
        // halting class being the first of the two is read off
        $display = $tester->getDisplay();
        $this->assertStringContainsString($this->getClassId() . ' halted', $display);
        $this->assertLessThan(
            (int) strpos($display, self::NEXT_CLASS_ID . ': '),
            (int) strpos($display, $this->getClassId() . ': '),
            'the halting class has to be drained before the next one for this test to prove anything'
        );

        // the pair behind the divergence was skipped
        $this->assertGreaterThan(0, $this->countEavRowsFor($objectId));
        $this->assertSame([], $this->readDocumentRows($objectId));

        // and the next class was drained all the same
        $this->assertSame([], $this->getMigrationService()->getBacklog($nextClass));
        $this->assertCount(
            count(self::NEXT_CLASS_FIELDS),
            Db::get()->fetchAllAssociative(
                'SELECT * FROM ' . Db::get()->quoteIdentifier(self::DOCUMENT_TABLE_PREFIX . self::NEXT_CLASS_ID)
            )
        );
    }

    /**
     * A dry run reports the backlog of every class and converts nothing.
     */
    public function testADryRunReportsTheBacklogPerClassAndConvertsNothing(): void
    {
        $first = $this->createObjectWithValue(self::INPUT_KEY, 'first', 'dry-first');
        $second = $this->createObjectWithValue(self::INPUT_KEY, 'second', 'dry-second');

        $this->flipStore();

        $dataRows = $this->countRows($this->getDataTable(), self::FIELDNAME);
        $tester = $this->runDrain(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString($this->getClassId() . ': 2 pair(s) to convert', $tester->getDisplay());

        $this->assertSame([], $this->readDocumentRows((int) $first->getId()));
        $this->assertSame([], $this->readDocumentRows((int) $second->getId()));
        $this->assertSame($dataRows, $this->countRows($this->getDataTable(), self::FIELDNAME));
    }

    /**
     * A real run reports what it is achieving from its own measurement: the pairs it converted, the
     * time that took, the rate that works out to and how long the rest would take at it.
     */
    public function testARunReportsItsRateAndAnEstimate(): void
    {
        $this->createObjectWithValue(self::INPUT_KEY, 'first', 'rate-first');
        $this->createObjectWithValue(self::INPUT_KEY, 'second', 'rate-second');

        $this->flipStore();

        $tester = $this->runDrain();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $display = $tester->getDisplay();
        $this->assertStringContainsString('2 of 2 pair(s) converted in', $display);
        $this->assertStringContainsString('rate ', $display);
        $this->assertStringContainsString('estimated ', $display);
        $this->assertStringContainsString('remaining', $display);

        $this->assertSame([], $this->getMigrationService()->getBacklog($this->getClass()));
    }

    /**
     * The backlog is keyed by class id, and PHP makes an array key of a numeric string an int - so
     * a class with the id it gets by default hands every method taking a class id an int unless the
     * drain puts the string back. The dry run reports the backlog without reaching those calls,
     * which is why this runs the conversion itself.
     */
    public function testTheDrainConvertsAClassWhoseIdIsNumeric(): void
    {
        $storeId = $this->createMigrationStore();
        $groupId = $this->createMigrationGroup($storeId);
        $keyId = $this->createMigrationKey($storeId, $groupId);

        $class = $this->saveMigrationClass(
            self::NUMERIC_CLASS_ID,
            [$this->migrationStoreField(self::NUMERIC_CLASS_FIELD, $storeId)],
            self::NUMERIC_CLASS_NAME
        );
        $this->assertSame(self::NUMERIC_CLASS_ID, $class->getId());

        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, 'unrelated', 'numeric-owner')->getId();
        $this->seedMigrationEavRows(
            self::NUMERIC_CLASS_ID,
            $objectId,
            self::NUMERIC_CLASS_FIELD,
            $groupId,
            $keyId,
            'converted under a numeric class id'
        );

        $this->flipMigrationStore($storeId);

        $tester = $this->runDrain(['--class' => self::NUMERIC_CLASS_ID]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('1 pair(s) converted', $tester->getDisplay());
        $this->assertSame([], $this->getMigrationService()->getBacklog($class));
        $this->assertSame(
            [self::NUMERIC_CLASS_FIELD],
            $this->fieldNamesIn(self::DOCUMENT_TABLE_PREFIX . self::NUMERIC_CLASS_ID)
        );
    }

    private function drainCommand(): DrainClassificationStoreCommand
    {
        return new DrainClassificationStoreCommand($this->getMigrationService());
    }

    /**
     * @return string[] the distinct field names a value table holds
     */
    private function fieldNamesIn(string $table): array
    {
        return Db::get()->fetchFirstColumn(
            'SELECT DISTINCT fieldname FROM ' . Db::get()->quoteIdentifier($table) . ' ORDER BY fieldname'
        );
    }

    /**
     * A class of its own, carrying one classification store field on a flipped store of its own per
     * field name, each with one pair of EAV rows for the given object.
     */
    private function setUpNextClass(int $objectId): ClassDefinition
    {
        $storeId = $this->createMigrationStore();
        $groupId = $this->createMigrationGroup($storeId);
        $keyId = $this->createMigrationKey($storeId, $groupId);

        $class = $this->saveMigrationClass(
            self::NEXT_CLASS_ID,
            array_map(
                fn (string $fieldname) => $this->migrationStoreField($fieldname, $storeId),
                self::NEXT_CLASS_FIELDS
            )
        );

        foreach (self::NEXT_CLASS_FIELDS as $fieldname) {
            $this->seedMigrationEavRows(
                self::NEXT_CLASS_ID,
                $objectId,
                $fieldname,
                $groupId,
                $keyId,
                'converted: ' . $fieldname
            );
        }

        $this->flipMigrationStore($storeId);

        return $class;
    }

    /**
     * @param array<string, string|bool> $input
     * @param bool $everyClass whether the run is left unrestricted instead of naming the test class
     */
    private function runDrain(array $input = [], bool $everyClass = false): CommandTester
    {
        if (!$everyClass) {
            $input += ['--class' => $this->getClassId()];
        }

        $tester = new CommandTester($this->drainCommand());
        $tester->execute($input, ['interactive' => false]);

        return $tester;
    }

    private function readValue(int $objectId): mixed
    {
        return $this->reload($objectId)->getCsstore()->getLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY)
        );
    }

    private function countEavRowsFor(int $objectId): int
    {
        $db = Db::get();
        $count = 0;

        foreach ([$this->getDataTable(), $this->getGroupsTable()] as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }

            $count += (int) $db->fetchOne(
                'SELECT COUNT(*) FROM ' . $db->quoteIdentifier($table) . ' WHERE id = ? AND fieldname = ?',
                [$objectId, self::FIELDNAME]
            );
        }

        return $count;
    }

    /**
     * One group activation row and one value row for it, the way the EAV engine wrote them.
     */
    private function seedEavRows(int $objectId, string $value): void
    {
        $db = Db::get();
        $groupId = $this->getGroupId();

        $db->executeStatement(
            'INSERT IGNORE INTO ' . $db->quoteIdentifier($this->getGroupsTable())
            . ' (`id`, `groupId`, `fieldname`) VALUES (?, ?, ?)',
            [$objectId, $groupId, self::FIELDNAME]
        );
        $db->executeStatement(
            'INSERT IGNORE INTO ' . $db->quoteIdentifier($this->getDataTable())
            . ' (`id`, `groupId`, `keyId`, `fieldname`, `language`, `type`, `value`)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$objectId, $groupId, $this->getKeyId(self::INPUT_KEY), self::FIELDNAME, 'default', 'input', $value]
        );
    }

    /**
     * A group activation row with no value rows under it: the EAV side reads it back as an active
     * group holding nothing, which is a row the backlog finds and no value the documents could be
     * missing.
     */
    private function seedEmptyGroupRow(int $objectId): void
    {
        $db = Db::get();

        $db->executeStatement(
            'INSERT IGNORE INTO ' . $db->quoteIdentifier($this->getGroupsTable())
            . ' (`id`, `groupId`, `fieldname`) VALUES (?, ?, ?)',
            [$objectId, $this->getGroupId(self::FIRST_GROUP), self::FIELDNAME]
        );
    }
}
