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

namespace Pimcore\Tests\Unit\Command;

use Codeception\Test\Unit;
use DateTimeImmutable;
use FilesystemIterator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueProcessor;
use Pimcore\Asset\StorageQueue\StorageOperationType;
use Pimcore\Bundle\CoreBundle\Command\Asset\StorageQueueProcessCommand;
use Pimcore\Bundle\CoreBundle\Command\Asset\StorageQueueStatusCommand;
use Pimcore\Tests\Unit\Asset\StorageQueue\InMemoryStorageOperationQueueRepository;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

class StorageQueueCommandsTest extends Unit
{
    private string $tmpDir;

    private InMemoryStorageOperationQueueRepository $repository;

    private FilesystemAdapter $adapter;

    protected function _before(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/queue-cmd-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->repository = new InMemoryStorageOperationQueueRepository();
        $this->adapter = new LocalFilesystemAdapter($this->tmpDir);
    }

    protected function _after(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }

    private function realProcessor(): StorageOperationQueueProcessor
    {
        $locator = new StorageQueueCommandsTestAdapterLocator($this->adapter);

        return new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger());
    }

    private function lockFactory(): LockFactory
    {
        return new LockFactory(new InMemoryStore());
    }

    public function testProcessFailsWithClearMessageWhenFeatureDisabled(): void
    {
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), null);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('storage_operation_queue', $tester->getDisplay());
    }

    public function testProcessDrainsRowsAndReportsCounts(): void
    {
        $this->adapter->write('One/a.jpg', '1', new Config());
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'One', 'Moved/One', new DateTimeImmutable('+5 seconds')
        ));
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 processed', $tester->getDisplay());
        $this->assertTrue($this->adapter->fileExists('Moved/One/a.jpg'));
    }

    /**
     * G4 (Copilot review round 2): the command wires a lock-refresh heartbeat into
     * process(). A small checkInterval forces several interval ticks (and refresh() calls)
     * across the drain of a single row - the run must still complete cleanly against a real
     * LockFactory-held lock.
     */
    public function testProcessCompletesWithLockHeartbeatOverMultipleIntervalTicks(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->adapter->write("Many/file{$i}.jpg", (string) $i, new Config());
        }
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'Many', 'Moved/Many', new DateTimeImmutable('+5 seconds')
        ));
        $locator = new StorageQueueCommandsTestAdapterLocator($this->adapter);
        $processor = new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger(), 2);
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), $processor);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 processed', $tester->getDisplay());
        for ($i = 1; $i <= 5; $i++) {
            $this->assertTrue($this->adapter->fileExists("Moved/Many/file{$i}.jpg"));
        }
    }

    public function testProcessWarnsWhenIdOptionMatchesNoRow(): void
    {
        // new flow: the row is resolved BEFORE the lock is acquired/processed - a nonexistent id
        // returns immediately, so no "processed" line is emitted at all
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--id' => '999999']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queue row found with id 999999', $tester->getDisplay());
    }

    public function testProcessReportsRetainedRowDistinctly(): void
    {
        // existing row that cannot complete in this run (deadline reached immediately) - must be
        // reported distinctly from "no such row", and must stay queued
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'One', 'Moved/One', new DateTimeImmutable()
        ));
        $id = $this->repository->all()[0]->getId();
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--id' => (string) $id, '--max-runtime' => '0']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('0 processed', $tester->getDisplay());
        $this->assertStringContainsString('stays queued', $tester->getDisplay());
        $this->assertNotNull($this->repository->findById($id), 'row must remain queued for the next run');
    }

    public function testProcessExitsFailureWhenARowFails(): void
    {
        // storage 'asset' resolves, but source dir listing fails? Simpler: unknown storage via a strict locator
        $strictLocator = new StorageQueueCommandsTestStrictLocator();
        $processor = new StorageOperationQueueProcessor($strictLocator, $this->repository, new NullLogger());
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Delete, 'Anything', null, new DateTimeImmutable()
        ));
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), $processor);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('1 failed', $tester->getDisplay());
    }

    public function testProcessRefusesIdOfOlderSameTargetRow(): void
    {
        // H2 (Copilot round 3): two Move rows share an identical target - --id on the older one
        // must refuse rather than risk stranding the newer row's fresher content.
        $this->adapter->write('A/same.jpg', 'from-A', new Config());
        $this->adapter->write('B/same.jpg', 'from-B', new Config());
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'A', 'T', new DateTimeImmutable()
        ));
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'B', 'T', new DateTimeImmutable()
        ));
        $olderId = $this->repository->all()[0]->getId();
        $newerId = $this->repository->all()[1]->getId();
        $command = new StorageQueueProcessCommand($this->repository, $this->lockFactory(), $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--id' => (string) $olderId]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('1 failed', $tester->getDisplay());
        $this->assertStringContainsString((string) $newerId, $tester->getDisplay());
        $this->assertTrue($this->adapter->fileExists('A/same.jpg'));
        $this->assertTrue($this->adapter->fileExists('B/same.jpg'));
        $this->assertFalse($this->adapter->fileExists('T/same.jpg'));
    }

    public function testProcessSkipsCleanlyWhenLocked(): void
    {
        $lockFactory = $this->lockFactory();
        $foreign = $lockFactory->createLock('asset_storage_operation_queue_process');
        $this->assertTrue($foreign->acquire());
        $command = new StorageQueueProcessCommand($this->repository, $lockFactory, $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('already running', $tester->getDisplay());
    }

    public function testStatusOnEmptyQueue(): void
    {
        $command = new StorageQueueStatusCommand($this->repository, true);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('empty', $tester->getDisplay());
    }

    public function testStatusWarnsOnOldRows(): void
    {
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'Old', 'Elsewhere/Old', new DateTimeImmutable('-3 days')
        ));
        $command = new StorageQueueStatusCommand($this->repository, true);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--warn-age' => '48']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('older than', $tester->getDisplay());
    }

    public function testStatusWarnsWhenRowsExistWhileFeatureDisabled(): void
    {
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'Fresh', 'Elsewhere/Fresh', new DateTimeImmutable()
        ));
        $command = new StorageQueueStatusCommand($this->repository, false);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('disabled', $tester->getDisplay());
    }
}

/**
 * Test-only PSR-11 locator fake: always resolves to the single fixture adapter.
 */
final class StorageQueueCommandsTestAdapterLocator implements ContainerInterface
{
    public function __construct(private readonly FilesystemAdapter $adapter)
    {
    }

    public function get(string $id): FilesystemAdapter
    {
        return $this->adapter;
    }

    public function has(string $id): bool
    {
        return true;
    }
}

/**
 * Test-only PSR-11 locator fake: never resolves any storage, so the processor's per-row
 * failure isolation is exercised.
 */
final class StorageQueueCommandsTestStrictLocator implements ContainerInterface
{
    public function get(string $id): never
    {
        throw new StorageQueueCommandsTestAdapterNotFoundException('no adapter for ' . $id);
    }

    public function has(string $id): bool
    {
        return false;
    }
}

final class StorageQueueCommandsTestAdapterNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
