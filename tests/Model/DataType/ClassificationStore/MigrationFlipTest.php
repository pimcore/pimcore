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
use Pimcore\Db;
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
    ];

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

    private function runFlip(?string $store = null): CommandTester
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
