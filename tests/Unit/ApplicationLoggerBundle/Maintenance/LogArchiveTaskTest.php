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

namespace Pimcore\Tests\Unit\ApplicationLoggerBundle\Maintenance;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Bundle\ApplicationLoggerBundle\Maintenance\LogArchiveTask;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * Regression tests for the application log archiving task.
 *
 * The task archives log rows with an "INSERT INTO <archive> SELECT ... FROM application_logs"
 * and only afterwards deletes them from the source table. Whenever anything in between failed,
 * the source rows survived and the next run inserted them into the archive table a second time.
 * Nothing rejected those duplicates - the archive table carries no key on "id", and the ARCHIVE
 * storage engine (the configured default) cannot carry one - so the archive tables kept growing
 * with every run.
 *
 * Archiving therefore has to be idempotent: a row already present in the archive table must
 * never be inserted again, while still being removed from the source table.
 *
 * @internal
 */
final class LogArchiveTaskTest extends TestCase
{
    private const ARCHIVE_THRESHOLD_EXCEEDING_DAYS = 60;

    protected bool $cleanupDbInSetup = false;

    private Connection $db;

    private string $archiveTable;

    private string $sourceTable;

    private LockFactory $lockFactory;

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Db::get();
        $this->archiveTable = $this->db->quoteIdentifier(
            ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_' . date('Y') . '_' . date('m')
        );
        $this->sourceTable = $this->db->quoteIdentifier(ApplicationLoggerDb::TABLE_NAME);
        $this->lockFactory = new LockFactory(new InMemoryStore());

        $this->createSourceTable();
        $this->resetTables();
    }

    protected function tearDown(): void
    {
        $this->resetTables();

        parent::tearDown();
    }

    public function testAlreadyArchivedRowsAreNotArchivedASecondTime(): void
    {
        $ids = $this->insertSourceLogs(4);
        $this->createArchiveTable();
        $this->preArchive([$ids[0], $ids[1]]);

        $this->runTask();

        $this->assertArchivedExactlyOnce($ids);
    }

    public function testRepeatedExecutionDoesNotGrowTheArchiveTable(): void
    {
        $ids = $this->insertSourceLogs(3);
        $this->createArchiveTable();
        $this->preArchive($ids);

        $this->runTask();
        // the rows are back in the source table, exactly as a failed DELETE would leave them
        $this->insertSourceLogs(3, $ids);
        $this->runTask();

        $this->assertArchivedExactlyOnce($ids);
    }

    public function testRowsAreArchivedAndRemovedFromTheSourceTable(): void
    {
        $ids = $this->insertSourceLogs(3);
        $this->createArchiveTable();

        $this->runTask();

        $this->assertArchivedExactlyOnce($ids);
        $this->assertSame(0, $this->countSourceRows(), 'Archived rows must be gone from the source table.');
    }

    public function testAlreadyArchivedRowsAreStillRemovedFromTheSourceTable(): void
    {
        $ids = $this->insertSourceLogs(3);
        $this->createArchiveTable();
        $this->preArchive($ids);

        $this->runTask();

        $this->assertSame(
            0,
            $this->countSourceRows(),
            'Rows archived by an earlier, interrupted run must still be deleted from the source table.'
        );
    }

    public function testRecentRowsAreLeftUntouched(): void
    {
        $recentId = $this->insertSourceLog(new DateTimeImmutable('-1 day'));
        $this->createArchiveTable();

        $this->runTask();

        $this->assertSame(
            0,
            $this->countArchivedRows($recentId),
            'A row below the archive threshold must not be archived.'
        );
        $this->assertSame(1, $this->countSourceRows(), 'A row below the archive threshold must be kept.');
    }

    public function testAConcurrentRunIsSkippedInsteadOfArchivingTheSameEntriesAgain(): void
    {
        $ids = $this->insertSourceLogs(3);
        $this->createArchiveTable();

        // hold the task's lock, the way a still running earlier execution would
        $concurrentRun = $this->lockFactory->createLock(LogArchiveTask::class, 86400);
        self::assertTrue($concurrentRun->acquire(), 'The lock must be free before this test holds it.');

        try {
            $this->runTask();
        } finally {
            $concurrentRun->release();
        }

        foreach ($ids as $id) {
            $this->assertSame(
                0,
                $this->countArchivedRows($id),
                'A run that cannot take the lock must archive nothing.'
            );
        }
        $this->assertSame(3, $this->countSourceRows(), 'A skipped run must not delete anything either.');

        // and once the lock is free again the entries are archived exactly once
        $this->runTask();

        $this->assertArchivedExactlyOnce($ids);
    }

    public function testAFileObjectDeletedByAConcurrentRunDoesNotFailTheRun(): void
    {
        $ids = $this->insertSourceLogs(1, [], 'gone/by/now.log');
        $this->createArchiveTable();

        // the file is still there when it is looked at and gone when it is deleted
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->expects($this->exactly(2))->method('fileExists')
            ->with('gone/by/now.log')->willReturnOnConsecutiveCalls(true, false);
        $storage->expects($this->once())->method('delete')
            ->with('gone/by/now.log')->willThrowException(UnableToDeleteFile::atLocation('gone/by/now.log'));
        $storage->method('directoryExists')->willReturn(false);

        $this->runTask($storage);

        $this->assertArchivedExactlyOnce($ids);
        $this->assertSame(0, $this->countSourceRows(), 'The run must complete and clear the source table.');
    }

    public function testAFileObjectThatCannotBeDeletedStillFailsTheRun(): void
    {
        $this->insertSourceLogs(1, [], 'stuck/file.log');
        $this->createArchiveTable();

        $storage = $this->createMock(FilesystemOperator::class);
        $storage->expects($this->exactly(2))->method('fileExists')->with('stuck/file.log')->willReturn(true);
        $storage->expects($this->once())->method('delete')
            ->with('stuck/file.log')->willThrowException(UnableToDeleteFile::atLocation('stuck/file.log'));

        $this->expectException(UnableToDeleteFile::class);

        try {
            $this->runTask($storage);
        } finally {
            $this->assertSame(1, $this->countSourceRows(), 'A failed deletion must leave the entries retryable.');
        }
    }

    public function testAnArchiveDirectoryDeletedByAConcurrentRunDoesNotFailTheRun(): void
    {
        $oldMonth = (new DateTimeImmutable('-24 months'));
        $oldTable = $this->db->quoteIdentifier(
            ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_' . $oldMonth->format('Y') . '_' . $oldMonth->format('m')
        );
        $this->db->executeStatement('CREATE TABLE IF NOT EXISTS ' . $oldTable . ' (id BIGINT(20) NOT NULL)');

        $storage = $this->createMock(FilesystemOperator::class);
        // any other expired archive table in the test database goes through the same path, so the
        // calls are tracked per directory rather than by position
        $checked = [];
        $storage->method('directoryExists')->willReturnCallback(
            static function (string $path) use (&$checked): bool {
                $checked[$path] = ($checked[$path] ?? 0) + 1;

                return $checked[$path] === 1;
            }
        );
        $deleted = [];
        $storage->method('deleteDirectory')->willReturnCallback(
            static function (string $path) use (&$deleted): void {
                $deleted[] = $path;

                throw UnableToDeleteDirectory::atLocation($path);
            }
        );

        try {
            $this->runTask($storage);

            $this->assertNotEmpty($deleted, 'The deletion must have been attempted.');
            foreach ($deleted as $path) {
                $this->assertSame(2, $checked[$path], 'The directory must be checked again after the failure.');
            }
        } finally {
            $this->db->executeStatement('DROP TABLE IF EXISTS ' . $oldTable);
        }
    }

    private function runTask(?FilesystemOperator $storage = null): void
    {
        (new LogArchiveTask($this->db, new Config(), new NullLogger(), $this->lockFactory, $storage))->execute();
    }

    /**
     * @param int[] $reuseIds
     *
     * @return int[]
     */
    private function insertSourceLogs(int $amount, array $reuseIds = [], ?string $fileObject = null): array
    {
        $timestamp = new DateTimeImmutable('-' . self::ARCHIVE_THRESHOLD_EXCEEDING_DAYS . ' days');
        $ids = [];

        for ($i = 0; $i < $amount; $i++) {
            $ids[] = $this->insertSourceLog($timestamp, $reuseIds[$i] ?? null, $fileObject);
        }

        return $ids;
    }

    private function insertSourceLog(DateTimeImmutable $timestamp, ?int $id = null, ?string $fileObject = null): int
    {
        $data = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'message' => 'log archive task test',
            'priority' => 'info',
            'component' => 'log-archive-task-test',
            'fileobject' => $fileObject,
        ];

        if ($id !== null) {
            $data['id'] = $id;
        }

        $this->db->insert(ApplicationLoggerDb::TABLE_NAME, $data);

        return $id ?? (int) $this->db->lastInsertId();
    }

    /**
     * Copies rows into the archive table without removing them from the source table - exactly
     * the state an archive run that died after its INSERT but before its DELETE leaves behind.
     *
     * @param int[] $ids
     */
    private function preArchive(array $ids): void
    {
        $this->db->executeStatement(
            'INSERT INTO ' . $this->archiveTable . ' SELECT * FROM ' . $this->sourceTable
            . ' WHERE id IN (:ids)',
            ['ids' => $ids],
            ['ids' => ArrayParameterType::INTEGER]
        );
    }

    private function createSourceTable(): void
    {
        $this->db->executeStatement(
            'CREATE TABLE IF NOT EXISTS ' . $this->sourceTable . " (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `pid` INT(11) NULL DEFAULT NULL,
                `timestamp` DATETIME NOT NULL,
                `message` TEXT NULL,
                `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
                `fileobject` VARCHAR(1024) DEFAULT NULL,
                `info` VARCHAR(1024) DEFAULT NULL,
                `component` VARCHAR(190) DEFAULT NULL,
                `source` VARCHAR(190) DEFAULT NULL,
                `relatedobject` INT(11) UNSIGNED DEFAULT NULL,
                `relatedobjecttype` ENUM('object','document','asset') DEFAULT NULL,
                `maintenanceChecked` TINYINT(1) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `timestamp` (`timestamp`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci"
        );
    }

    /**
     * The task only creates the archive table when it does not exist yet, and would use the
     * configured storage engine for it. Creating it upfront keeps these tests independent of
     * which storage engines the database under test happens to offer.
     */
    private function createArchiveTable(): void
    {
        $this->db->executeStatement(
            'CREATE TABLE ' . $this->archiveTable . " (
                id BIGINT(20) NOT NULL,
                `pid` INT(11) NULL DEFAULT NULL,
                `timestamp` DATETIME NOT NULL,
                message VARCHAR(1024),
                `priority` ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL,
                fileobject VARCHAR(1024),
                info VARCHAR(1024),
                component VARCHAR(255),
                source VARCHAR(255) NULL DEFAULT NULL,
                relatedobject BIGINT(20),
                relatedobjecttype ENUM('object', 'document', 'asset'),
                maintenanceChecked TINYINT(1)
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci"
        );
    }

    /**
     * @param int[] $ids
     */
    private function assertArchivedExactlyOnce(array $ids): void
    {
        foreach ($ids as $id) {
            $this->assertSame(
                1,
                $this->countArchivedRows($id),
                sprintf('Log entry %d must be present in the archive table exactly once.', $id)
            );
        }
    }

    private function countArchivedRows(int $id): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . $this->archiveTable . ' WHERE id = ?', [$id]);
    }

    private function countSourceRows(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . $this->sourceTable);
    }

    private function resetTables(): void
    {
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . $this->archiveTable);
        $this->db->executeStatement('DELETE FROM ' . $this->sourceTable);
    }
}
