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
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainOutcome;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainPairResult;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
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

    /**
     * A class carrying more pending pairs than the backlog reads in one batch, to walk the two value
     * tables across two batch boundaries. The pairs are field names of one object rather than many
     * objects, which is the cheap way to hold a few hundred of them.
     */
    private const BATCHED_CLASS_ID = 'CsMigrationBatchedBacklog';

    private const BATCHED_PAIRS = 201;

    /**
     * A class carrying one pair more than the drain's garbage-collection interval, so that the
     * interval goes by while the pairs are being converted. The pairs are field names of one object
     * again, which is the cheap way to hold a hundred of them.
     */
    private const GARBAGE_CLASS_ID = 'CsMigrationGarbageInterval';

    private const GARBAGE_PAIRS = DrainClassificationStoreCommand::GARBAGE_INTERVAL + 1;

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
        $this->assertStringContainsString('do not account for', (string) $result->divergence);
        $this->assertSame($eavRows, $this->countEavRowsFor($objectId), 'the newer values are not deleted');
        $this->assertSame($converted, $this->readDocumentRows($objectId), 'and nothing is written over them');
    }

    /**
     * And the two values are compared as texts, not as numbers: `'00123'` and `'123'` are two
     * different inputs of an input field, which keeps either verbatim. A comparison that equated
     * them would have the reconciliation call this pair migrated and delete the surviving row -
     * deleting the newer of the two values while the documents keep the older one.
     */
    public function testAConvertedPairWhoseEavRowHoldsANumericLookingStringIsReportedRatherThanDeleted(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, '123', 'drain-numeric-string');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $service = $this->getMigrationService();

        $this->assertSame(
            DrainOutcome::Converted,
            $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT)->outcome
        );
        $converted = $this->readDocumentRows($objectId);
        $this->assertSame(0, $this->countEavRowsFor($objectId));

        // the writer that had not seen the flip writes a value that compares equal to '123' under
        // PHP's loose comparison and is a different string
        $this->seedEavRows($objectId, '00123');
        $eavRows = $this->countEavRowsFor($objectId);
        $this->assertGreaterThan(0, $eavRows);

        $result = $service->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $this->assertSame(DrainOutcome::Diverged, $result->outcome);
        $this->assertStringContainsString('do not account for', (string) $result->divergence);
        $this->assertSame($eavRows, $this->countEavRowsFor($objectId), "'00123' is not '123'");
        $this->assertSame($converted, $this->readDocumentRows($objectId));
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
     * The backlog of a class is read a bounded batch at a time, so a class holding more pairs than
     * one batch has to come back whole all the same - and its count has to agree with what the walk
     * yields, because that count is what the run's progress is measured against.
     *
     * Both value tables hold several rows per pair - a value row per key and language, a group row
     * per group - so a batch that counted rows instead of pairs would return fewer pairs than it was
     * asked for and move its cursor past the ones it did not return. The pairs here therefore carry
     * two value rows each, and the boundaries of the batches are where they would be dropped.
     */
    public function testTheBacklogComesBackWholeAcrossItsBatchBoundaries(): void
    {
        $storeId = $this->createMigrationStore();
        $groupId = $this->createMigrationGroup($storeId);
        $keyId = $this->createMigrationKey($storeId, $groupId);
        $secondKeyId = $this->createMigrationKey($storeId, $groupId);

        $fieldNames = [];
        for ($index = 0; $index < self::BATCHED_PAIRS; $index++) {
            $fieldNames[] = sprintf('csBatched%03d', $index);
        }

        $class = $this->saveMigrationClass(
            self::BATCHED_CLASS_ID,
            array_map(fn (string $name) => $this->migrationStoreField($name, $storeId), $fieldNames)
        );

        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, 'unrelated', 'batched-owner')->getId();

        foreach ($fieldNames as $fieldname) {
            $this->seedMigrationEavRows(
                self::BATCHED_CLASS_ID,
                $objectId,
                $fieldname,
                $groupId,
                $keyId,
                'first row of ' . $fieldname
            );
            // a second value row under the same pair, so the pair is two rows of the data table
            Db::get()->insert(self::DATA_TABLE_PREFIX . self::BATCHED_CLASS_ID, [
                'id' => $objectId,
                'groupId' => $groupId,
                'keyId' => $secondKeyId,
                'fieldname' => $fieldname,
                'language' => 'default',
                'type' => 'input',
                'value' => 'second row of ' . $fieldname,
            ]);
        }

        $this->flipMigrationStore($storeId);
        $service = $this->getMigrationService();

        $expected = array_map(static fn (string $name): array => [$objectId, $name], $fieldNames);

        $this->assertSame($expected, $this->pendingPairs($class), 'every pair, once, in order');
        $this->assertSame(count($expected), $service->countBacklog($class), 'and the count agrees');
        $this->assertSame(
            [self::BATCHED_CLASS_ID => count($expected)],
            $service->getBacklogSizeByClass(self::BATCHED_CLASS_ID)
        );
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

        $this->assertCount(2, $this->pendingPairs($this->getClass()));

        // the run is interrupted after one pair
        $service->drainPair($this->getClass(), (int) $first->getId(), self::FIELDNAME, self::MIGRATED_AT);
        $converted = $this->readDocumentRows((int) $first->getId());

        $backlog = $this->pendingPairs($this->getClass());
        $this->assertSame([[(int) $second->getId(), self::FIELDNAME]], $backlog);

        foreach ($backlog as [$objectId, $fieldname]) {
            $this->assertSame(
                DrainOutcome::Converted,
                $service->drainPair($this->getClass(), $objectId, $fieldname, self::MIGRATED_AT)->outcome
            );
        }

        $this->assertSame([], $this->pendingPairs($this->getClass()));
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
        $this->assertSame([], $this->pendingPairs($nextClass));
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

        $this->assertSame([], $this->pendingPairs($this->getClass()));
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
        $this->assertSame([], $this->pendingPairs($class));
        $this->assertSame(
            [self::NUMERIC_CLASS_FIELD],
            $this->fieldNamesIn(self::DOCUMENT_TABLE_PREFIX . self::NUMERIC_CLASS_ID)
        );
    }

    /**
     * Every pair loads its object, and a loaded object stays registered in the runtime cache - so a
     * drain of an installation's backlog would hold every object it had touched. The parallelization
     * clears the cache once a batch, which an install without the optional parallelization package
     * reaches only once, after the whole item stream: the drain therefore counts the pairs itself
     * and clears the cache on the interval, in whichever of the two it is running.
     */
    public function testTheRuntimeCacheIsClearedOnAnIntervalWhileThePairsAreConverted(): void
    {
        $class = $this->setUpGarbageIntervalClass();

        $tester = new CommandTester($this->drainCommand());
        $tester->execute(
            ['--class' => self::GARBAGE_CLASS_ID],
            ['interactive' => false, 'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]
        );

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $this->assertSame(
            1,
            substr_count(
                $tester->getDisplay(),
                sprintf('Collect garbage after %d pair(s).', DrainClassificationStoreCommand::GARBAGE_INTERVAL)
            ),
            'the one interval the backlog goes past was collected on, while the pairs were converting'
        );

        $this->assertSame([], $this->pendingPairs($class), 'and the whole backlog converted');
    }

    /**
     * With `--processes` the pairs are converted in worker processes, and the outcome of a pair is
     * counted in the process that converted it. The process that discovered the backlog and prints
     * the report is not that process: its own counters stay at zero, and a report taken from them
     * would announce a successful run as `0 of 2 pair(s)` at a rate of nothing per second.
     *
     * Real workers cannot be spawned from here - the command runs through a CommandTester, with no
     * console application for the parallelization to build a child command line from - so what this
     * runs is a drain whose outcomes are recorded where the reporting process cannot see them,
     * which is what a worker's are.
     */
    public function testTheTotalsDoNotDependOnTheProcessThatConvertedThePairs(): void
    {
        $first = $this->createObjectWithValue(self::INPUT_KEY, 'first', 'worker-first');
        $second = $this->createObjectWithValue(self::INPUT_KEY, 'second', 'worker-second');

        $this->flipStore();

        $tester = new CommandTester($this->workerDrainCommand());
        $tester->execute(['--class' => $this->getClassId()], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        // the progress line of a batch is the counters of the process running the batch, which is
        // why this run reports `0 of 2` on its way through and the totals are not taken from there
        $display = $tester->getDisplay();
        $this->assertStringContainsString('2 of 2 pair(s) converted in', $display);
        $this->assertStringContainsString('2 pair(s) converted.', $display);

        // and the pairs the report counted really did convert
        $this->assertSame([], $this->pendingPairs($this->getClass()));
        $this->assertNotSame([], $this->readDocumentRows((int) $first->getId()));
        $this->assertNotSame([], $this->readDocumentRows((int) $second->getId()));
    }

    /**
     * A pair a worker left on EAV fails the run as well, though the divergence that left it there
     * was reported in the worker: what is left of the backlog is read back here, and a run that did
     * not empty it exits non-zero.
     */
    public function testAPairLeftOnEavByAWorkerFailsTheRun(): void
    {
        // the value tables carry a foreign key on `objects`, which a folder satisfies: the pair is
        // then one no concrete object answers, which is a divergence
        $folder = new Folder();
        $folder->setParentId(1);
        $folder->setKey('worker-divergence-folder');
        $folder->save();

        $object = $this->createObjectWithValue(self::INPUT_KEY, 'converts fine', 'worker-divergence');

        $this->flipStore();
        $this->seedEavRows((int) $folder->getId(), 'a row no object answers');

        $tester = new CommandTester($this->workerDrainCommand());
        $tester->execute(['--class' => $this->getClassId()], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('1 pair(s) are still on EAV', $tester->getDisplay());

        // the pair that diverged kept its rows, and the one behind it converted all the same
        $this->assertGreaterThan(0, $this->countEavRowsFor((int) $folder->getId()));
        $this->assertSame([], $this->readDocumentRows((int) $folder->getId()));
        $this->assertSame(0, $this->countEavRowsFor((int) $object->getId()));
    }

    private function drainCommand(): DrainClassificationStoreCommand
    {
        return new DrainClassificationStoreCommand($this->getMigrationService());
    }

    /**
     * The command as the process that discovers a backlog its workers convert: the pairs are
     * drained, and what became of each of them is counted in a process this one does not share.
     */
    private function workerDrainCommand(): DrainClassificationStoreCommand
    {
        return new class($this->getMigrationService()) extends DrainClassificationStoreCommand {
            public function __construct(DocumentEngineMigrationService $migrationService)
            {
                parent::__construct($migrationService);

                // an anonymous class does not inherit the #[AsCommand] attribute of the class it
                // extends, and the run lock is taken on the name that attribute carries
                $this->setName('pimcore:classificationstore:drain');
            }

            protected function recordOutcome(string $classId, DrainPairResult $result): void
            {
                // counted in the worker that converted the pair
            }
        };
    }

    /**
     * One class holding one pair more than the garbage-collection interval, on one object.
     */
    private function setUpGarbageIntervalClass(): ClassDefinition
    {
        $storeId = $this->createMigrationStore();
        $groupId = $this->createMigrationGroup($storeId);
        $keyId = $this->createMigrationKey($storeId, $groupId);

        $fieldNames = [];
        for ($index = 0; $index < self::GARBAGE_PAIRS; $index++) {
            $fieldNames[] = sprintf('csGarbage%03d', $index);
        }

        $class = $this->saveMigrationClass(
            self::GARBAGE_CLASS_ID,
            array_map(fn (string $name) => $this->migrationStoreField($name, $storeId), $fieldNames)
        );

        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, 'unrelated', 'garbage-owner')->getId();

        foreach ($fieldNames as $fieldname) {
            $this->seedMigrationEavRows(
                self::GARBAGE_CLASS_ID,
                $objectId,
                $fieldname,
                $groupId,
                $keyId,
                'value of ' . $fieldname
            );
        }

        $this->flipMigrationStore($storeId);

        return $class;
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
