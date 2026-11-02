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

use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\CleanupClassificationStoreEavTablesCommand;
use Pimcore\Bundle\CoreBundle\Command\DrainClassificationStoreCommand;
use Pimcore\Bundle\CoreBundle\Command\FlipClassificationStoreEngineCommand;
use Pimcore\Bundle\CoreBundle\Command\VerifyClassificationStoreCommand;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\StoreFlipConflictException;
use Pimcore\Db;
use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The flip: one direction, no engine argument, and the document tables of every class carrying a
 * field on the store - so that the first save after it has a table to write to.
 *
 * @group model.datatype.classificationstore
 */
class MigrationFlipTest extends AbstractMigrationTest
{
    use MigrationClassFixtureTrait;

    /**
     * A class whose two object bricks define one classification store field name on two stores of
     * their own: the value tables key their rows by (object, fieldname), so those two definitions
     * share one set of rows and cannot be split across two engines.
     */
    private const SPLIT_CLASS_ID = 'CsMigrationSplit';

    private const SPLIT_FIELD = 'csSplitField';

    private const FIRST_BRICK_KEY = 'csMigrationSplitBrickOne';

    private const SECOND_BRICK_KEY = 'csMigrationSplitBrickTwo';

    private const COMMAND_NAMES = [
        'pimcore:classificationstore:flip' => FlipClassificationStoreEngineCommand::class,
        'pimcore:classificationstore:drain' => DrainClassificationStoreCommand::class,
        'pimcore:classificationstore:verify' => VerifyClassificationStoreCommand::class,
        'pimcore:classificationstore:cleanup' => CleanupClassificationStoreEavTablesCommand::class,
    ];

    /**
     * The files this migration added, which must not reach for the execution engine bundle.
     */
    private const NEW_SOURCE_FILES = [
        'bundles/CoreBundle/src/Command/FlipClassificationStoreEngineCommand.php',
        'bundles/CoreBundle/src/Command/DrainClassificationStoreCommand.php',
        'bundles/CoreBundle/src/Command/VerifyClassificationStoreCommand.php',
        'bundles/CoreBundle/src/Command/CleanupClassificationStoreEavTablesCommand.php',
        'bundles/CoreBundle/src/DataObject/Classificationstore/DocumentEngineMigrationService.php',
        'bundles/CoreBundle/src/DataObject/Classificationstore/DrainOutcome.php',
        'bundles/CoreBundle/src/DataObject/Classificationstore/DrainPairResult.php',
        'bundles/CoreBundle/src/DataObject/Classificationstore/VerificationReport.php',
        'bundles/CoreBundle/src/DataObject/Classificationstore/StoreFlipConflictException.php',
    ];

    protected function tearDown(): void
    {
        $this->cleanUpMigrationFixture();

        parent::tearDown();
    }

    /**
     * It takes a store and nothing else: no engine to choose, no command to undo it, and a
     * confirmation that says in words what the flip commits to.
     */
    public function testTheFlipSetsTheEngineAndOffersNoOtherChoice(): void
    {
        $this->assertNull($this->getStoredEngine());

        $tester = $this->runFlip();

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(self::DOCUMENT_ENGINE, $this->getStoredEngine());

        $definition = (new FlipClassificationStoreEngineCommand($this->getMigrationService()))->getDefinition();
        $this->assertSame(['store'], array_keys($definition->getArguments()));
        $this->assertFalse($definition->hasOption('engine'), 'the engine is not a choice the flip offers');

        // the confirmation is wrapped into a block, so it is read without its line breaks
        $display = $this->unwrap($tester->getDisplay());
        $this->assertStringContainsString('exists only as a document', $display);
        $this->assertStringContainsString('There is no reverse migration', $display);
    }

    /**
     * The store is resolved by name as well as by id, the way the sibling store command takes one.
     */
    public function testTheStoreIsResolvedByNameAsWellAsById(): void
    {
        $this->assertSame(Command::SUCCESS, $this->runFlip(self::STORE_NAME)->getStatusCode());
        $this->assertSame(self::DOCUMENT_ENGINE, $this->getStoredEngine());
    }

    /**
     * Nothing saves a class because a store changed, so writing the engine column alone would leave
     * the first save after the flip without a table. The EAV tables of those classes are left
     * exactly as they are - they are what the store is still read from.
     */
    public function testTheFlipCreatesTheDocumentTableAndLeavesTheEavTablesAlone(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, 'written on eav', 'flip-eav');

        $dataRows = $this->countRows($this->getDataTable());
        $groupRows = $this->countRows($this->getGroupsTable());
        $this->assertGreaterThan(0, $dataRows, 'precondition: the value is stored in the EAV tables');
        $this->assertGreaterThan(0, $groupRows);

        // another test class leaves the document table of this class behind, so the state the flip
        // has to answer for - a class last saved while its store was on EAV - is made here
        $this->dropDocumentTable();
        $this->assertFalse($this->tableExists($this->getDocumentTable()));

        $this->runFlip();

        $this->assertTrue($this->tableExists($this->getDocumentTable()));
        $this->assertTrue($this->tableExists($this->getDataTable()));
        $this->assertTrue($this->tableExists($this->getGroupsTable()));
        $this->assertSame($dataRows, $this->countRows($this->getDataTable()));
        $this->assertSame($groupRows, $this->countRows($this->getGroupsTable()));

        // the save that follows the flip is the one that would have failed on a missing table
        $reloaded = $this->reload((int) $object->getId());
        $reloaded->getCsstore()->setLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY),
            'written after the flip'
        );
        $reloaded->save();

        $this->assertNotSame([], $this->readDocumentRows((int) $object->getId()));
    }

    /**
     * The table the flip creates is the one the previous release shipped: no marker row for an empty
     * converted store, `groupId` still part of the primary key and therefore not nullable, and both
     * cascading foreign keys still in place - the `classificationstore_groups` one is what makes
     * deleting a group destroy its values.
     */
    public function testTheFlipCreatesTheDocumentTableThisMigrationDoesNotAlter(): void
    {
        $this->dropDocumentTable();
        $this->runFlip();

        $table = $this->getDocumentTable();

        $this->assertSame(['id', 'fieldname', 'groupId'], $this->primaryKeyOf($table));
        $this->assertSame(
            ['classificationstore_groups' => 'CASCADE', 'objects' => 'CASCADE'],
            $this->foreignKeysOf($table)
        );
        $this->assertSame(
            ['id', 'fieldname', 'groupId', 'collectionId', 'values', 'checksum', 'migratedAt'],
            $this->columnNamesOf($table)
        );
        $this->assertSame('NO', $this->nullabilityOf($table, 'groupId'));
    }

    /**
     * The four commands resolve from the console application of the test kernel, which is also what
     * proves the drain service was constructible: its DBAL connection is not autowirable, and a
     * command whose service could not be built would not be found here.
     */
    public function testTheFourCommandsResolveFromTheConsoleApplicationAndNothingUndoesTheFlip(): void
    {
        $kernel = Pimcore::getKernel();
        $this->assertNotNull($kernel);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        foreach (self::COMMAND_NAMES as $name => $class) {
            $command = $application->find($name);

            $this->assertSame($name, $command->getName());
            $this->assertContains(
                str_replace('pimcore:', '', $name),
                $command->getAliases(),
                $name . ' needs its short alias'
            );

            // a command registered through the container is handed out lazily
            $this->assertInstanceOf(
                $class,
                $command instanceof LazyCommand ? $command->getCommand() : $command
            );
        }

        $this->assertFalse($application->has('pimcore:classificationstore:unflip'));
    }

    /**
     * The display with its block wrapping taken out, so that a message can be read as one line.
     */
    private function unwrap(string $display): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $display));
    }

    /**
     * Core gains no dependency on the execution engine bundle: the drain is a console command with
     * a transaction per pair, not a job.
     */
    public function testNoneOfTheNewCodeReachesForTheExecutionEngineBundle(): void
    {
        foreach (self::NEW_SOURCE_FILES as $file) {
            $path = PIMCORE_PROJECT_ROOT . '/' . $file;

            $this->assertFileExists($path);
            $this->assertStringNotContainsString(
                'GenericExecutionEngine',
                (string) file_get_contents($path),
                $file . ' must not reference the execution engine bundle'
            );
        }
    }

    /**
     * The drain service is instantiable with its DBAL connection injected, which the engine entries
     * in the bundle configuration have to spell out because `$db` is not autowirable.
     */
    public function testTheDrainServiceIsInstantiableWithItsConnection(): void
    {
        $this->assertInstanceOf(DocumentEngineMigrationService::class, $this->getMigrationService());
    }

    /**
     * Every process reads the engine column, so a save that saw the flip before the document table
     * of its class existed would fail with table-not-found - and a `CREATE TABLE` that failed half
     * way would leave the store switched onto tables that are not there. The tables therefore go
     * first, which the store configuration write itself is what proves: by the time it is
     * dispatched, the table is already there.
     */
    public function testTheFlipCreatesTheTablesBeforeItWritesTheEngineColumn(): void
    {
        $this->dropDocumentTable();
        $this->assertFalse($this->tableExists($this->getDocumentTable()));

        $tableWhenTheEngineWasWritten = null;
        $listener = function () use (&$tableWhenTheEngineWasWritten): void {
            $tableWhenTheEngineWasWritten ??= $this->tableExists($this->getDocumentTable());
        };

        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_UPDATE, $listener);

        try {
            $this->runFlip();
        } finally {
            $dispatcher->removeListener(DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_UPDATE, $listener);
        }

        $this->assertTrue($tableWhenTheEngineWasWritten, 'the document table has to exist by then');
        $this->assertSame(self::DOCUMENT_ENGINE, $this->getStoredEngine());
    }

    /**
     * A field name identifies a group of definitions rather than one, and the group has to agree on
     * one engine: flipping one of its stores alone would report success and then have every load,
     * save and drain of that field refused. The refusal comes before anything is written, and names
     * the store that would be left behind by id and by name.
     */
    public function testTheFlipRefusesToLeaveAFieldNameSplitAcrossTwoEngines(): void
    {
        [$firstStoreId, $secondStoreId] = $this->setUpClassWithASplitFieldName();

        $tester = $this->runFlip((string) $firstStoreId);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertNull($this->storedEngineOf($firstStoreId), 'nothing is written when it refuses');
        $this->assertFalse($this->tableExists(self::DOCUMENT_TABLE_PREFIX . self::SPLIT_CLASS_ID));

        // the message itself rather than the block the console wraps it into, so that the class,
        // the field and the store it has to name can be read as one line
        $store = StoreConfig::getById($firstStoreId);
        $this->assertInstanceOf(StoreConfig::class, $store);
        $message = '';

        try {
            $this->getMigrationService()->flip($store);
            $this->fail('the flip has to refuse');
        } catch (StoreFlipConflictException $exception) {
            $message = $exception->getMessage();
        }

        $this->assertStringContainsString(self::SPLIT_CLASS_ID, $message);
        $this->assertStringContainsString(self::SPLIT_FIELD, $message);
        $this->assertStringContainsString(sprintf('store %d', $secondStoreId), $message);
    }

    /**
     * And the way through is to take both stores over at once, which is what the argument accepts
     * more than one store for.
     */
    public function testTheStoresSharingAFieldNameAreFlippedTogether(): void
    {
        [$firstStoreId, $secondStoreId] = $this->setUpClassWithASplitFieldName();

        $tester = new CommandTester(new FlipClassificationStoreEngineCommand($this->getMigrationService()));
        $tester->execute(
            ['store' => [(string) $firstStoreId, (string) $secondStoreId]],
            ['interactive' => false]
        );

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(self::DOCUMENT_ENGINE, $this->storedEngineOf($firstStoreId));
        $this->assertSame(self::DOCUMENT_ENGINE, $this->storedEngineOf($secondStoreId));
        $this->assertTrue($this->tableExists(self::DOCUMENT_TABLE_PREFIX . self::SPLIT_CLASS_ID));

        // the field name is resolvable again, which is what the refusal above was protecting
        $this->assertSame(
            [self::SPLIT_FIELD],
            $this->getMigrationService()->getDocumentFieldNames($this->splitClass())
        );
    }

    /**
     * The step between the flip and the drain: a process that resolved the EAV engine before the
     * flip keeps writing EAV rows until its next reset, so the command says to let those writers
     * finish rather than leaving an operator to find out from the drain's report.
     */
    public function testTheFlipSaysToLetTheWritersThatPredateItFinish(): void
    {
        $display = $this->unwrap($this->runFlip()->getDisplay());

        $this->assertStringContainsString('let the requests and messenger messages', $display);
        $this->assertStringContainsString('resolved the EAV engine before this flip', $display);
    }

    /**
     * A class whose two bricks define one field name on two stores of their own, neither flipped.
     *
     * @return array{0: int, 1: int} the two store ids
     */
    private function setUpClassWithASplitFieldName(): array
    {
        $firstStoreId = $this->createMigrationStore();
        $secondStoreId = $this->createMigrationStore();

        $this->saveMigrationClass(self::SPLIT_CLASS_ID, [
            $this->registerMigrationBricks([
                self::FIRST_BRICK_KEY => [$this->migrationStoreField(self::SPLIT_FIELD, $firstStoreId)],
                self::SECOND_BRICK_KEY => [$this->migrationStoreField(self::SPLIT_FIELD, $secondStoreId)],
            ]),
        ]);

        return [$firstStoreId, $secondStoreId];
    }

    private function splitClass(): ClassDefinition
    {
        $class = ClassDefinition::getById(self::SPLIT_CLASS_ID, true);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class;
    }

    private function storedEngineOf(int $storeId): ?string
    {
        $engine = Db::get()->fetchOne('SELECT engine FROM classificationstore_stores WHERE id = ?', [$storeId]);

        return $engine === false || $engine === null ? null : (string) $engine;
    }

    /**
     * @param string|string[]|null $store
     */
    private function runFlip(string|array|null $store = null): CommandTester
    {
        $tester = new CommandTester(new FlipClassificationStoreEngineCommand($this->getMigrationService()));
        $tester->execute(['store' => $store ?? (string) $this->getStoreId()], ['interactive' => false]);

        return $tester;
    }

    /**
     * @return string[] the primary key columns, in their declared order
     */
    private function primaryKeyOf(string $table): array
    {
        return Db::get()->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
            . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'"
            . ' ORDER BY ORDINAL_POSITION',
            [$table]
        );
    }

    /**
     * @return array<string, string> referenced table => delete rule
     */
    private function foreignKeysOf(string $table): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT k.REFERENCED_TABLE_NAME AS referenced, r.DELETE_RULE AS deleteRule'
            . ' FROM information_schema.KEY_COLUMN_USAGE k'
            . ' JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME'
            . ' AND r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA'
            . ' WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            [$table]
        );

        $foreignKeys = [];
        foreach ($rows as $row) {
            $foreignKeys[(string) $row['referenced']] = (string) $row['deleteRule'];
        }

        ksort($foreignKeys);

        return $foreignKeys;
    }

    /**
     * @return string[] the column names, in their declared order
     */
    private function columnNamesOf(string $table): array
    {
        return Db::get()->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table]
        );
    }

    private function nullabilityOf(string $table, string $column): string
    {
        return (string) Db::get()->fetchOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }
}
