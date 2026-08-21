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
        $command = new StorageQueueProcessCommand($this->lockFactory(), null);
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
        $command = new StorageQueueProcessCommand($this->lockFactory(), $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 processed', $tester->getDisplay());
        $this->assertTrue($this->adapter->fileExists('Moved/One/a.jpg'));
    }

    public function testProcessWarnsWhenIdOptionMatchesNoRow(): void
    {
        $command = new StorageQueueProcessCommand($this->lockFactory(), $this->realProcessor());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--id' => '999999']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('0 processed', $tester->getDisplay());
        $this->assertStringContainsString('No queue row found with id 999999', $tester->getDisplay());
    }

    public function testProcessExitsFailureWhenARowFails(): void
    {
        // storage 'asset' resolves, but source dir listing fails? Simpler: unknown storage via a strict locator
        $strictLocator = new StorageQueueCommandsTestStrictLocator();
        $processor = new StorageOperationQueueProcessor($strictLocator, $this->repository, new NullLogger());
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Delete, 'Anything', null, new DateTimeImmutable()
        ));
        $command = new StorageQueueProcessCommand($this->lockFactory(), $processor);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('1 failed', $tester->getDisplay());
    }

    public function testProcessSkipsCleanlyWhenLocked(): void
    {
        $lockFactory = $this->lockFactory();
        $foreign = $lockFactory->createLock('asset_storage_operation_queue_process');
        $this->assertTrue($foreign->acquire());
        $command = new StorageQueueProcessCommand($lockFactory, $this->realProcessor());
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
