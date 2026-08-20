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

namespace Pimcore\Tests\Unit\Asset\StorageQueue;

use Codeception\Test\Unit;
use DateTimeImmutable;
use FilesystemIterator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationQueueProcessor;
use Pimcore\Asset\StorageQueue\StorageOperationType;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class StorageOperationQueueProcessorTest extends Unit
{
    private string $tmpDir;

    private InMemoryStorageOperationQueueRepository $repository;

    private FilesystemAdapter $adapter;

    protected function _before(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/queue-processor-test-' . uniqid();
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

    private function processor(): StorageOperationQueueProcessor
    {
        $locator = new StorageOperationQueueProcessorTestAdapterLocator($this->adapter);

        return new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger());
    }

    private function addRow(StorageOperationType $type, string $source, ?string $target, ?DateTimeImmutable $createdAt = null): void
    {
        $this->repository->add(new StorageOperation(
            null, 'asset', $type, $source, $target, $createdAt ?? new DateTimeImmutable('+5 seconds')
        ));
        // default cutoff is slightly in the FUTURE so freshly written test fixtures count as pre-cutoff
    }

    private function write(string $path, string $content): void
    {
        $this->adapter->write($path, $content, new Config());
    }

    /**
     * Writes then backdates the file's mtime to a precise, deterministic timestamp - no
     * clock races. LocalFilesystemAdapter maps logical paths 1:1 under $this->tmpDir, so
     * touch() on the concatenated path backdates the exact object.
     */
    private function writeWithMtime(string $path, string $content, int $mtime): void
    {
        $this->adapter->write($path, $content, new Config());
        touch($this->tmpDir . '/' . $path, $mtime);
    }

    public function testMoveRowDrainsSourceToTarget(): void
    {
        $this->write('Campaigns/a.jpg', 'a');
        $this->write('Campaigns/sub/b.jpg', 'b');
        $this->addRow(StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame(0, $result->getFailedRows());
        $this->assertSame(0, $result->getPendingRows());
        $this->assertSame('a', $this->adapter->read('Archive/Campaigns/a.jpg'));
        $this->assertSame('b', $this->adapter->read('Archive/Campaigns/sub/b.jpg'));
        $this->assertFalse($this->adapter->directoryExists('Campaigns'), 'emptied source directory removed');
        $this->assertSame([], $this->repository->all());
    }

    public function testDeleteRowRemovesPreCutoffContentOnly(): void
    {
        // old.jpg predates the row's cutoff (now-1h) by a further 2h - unambiguously pre-cutoff.
        $this->writeWithMtime('Trash/old.jpg', 'old', time() - 7200);
        $this->addRow(StorageOperationType::Delete, 'Trash', null, new DateTimeImmutable('-1 hour'));
        // simulate namespace reuse: new content arrives (now) long after the row's cutoff
        $this->write('Trash/new.jpg', 'new');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertFalse($this->adapter->fileExists('Trash/old.jpg'));
        $this->assertSame('new', $this->adapter->read('Trash/new.jpg'), 'post-cutoff content untouched');
        $this->assertTrue($this->adapter->directoryExists('Trash'), 'directory kept - post-cutoff files remain');
        $this->assertSame([], $this->repository->all(), 'row removed - no pre-cutoff entries left');
    }

    public function testLiteralWinsTargetIsNeverOverwritten(): void
    {
        $this->write('Campaigns/a.jpg', 'stale-source');
        $this->write('Archive/Campaigns/a.jpg', 'fresh-target');
        $this->addRow(StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame('fresh-target', $this->adapter->read('Archive/Campaigns/a.jpg'), 'existing target key never overwritten');
        $this->assertFalse($this->adapter->fileExists('Campaigns/a.jpg'), 'superseded source removed');
    }

    public function testMoveCutoffLeavesNamespaceReuseContent(): void
    {
        // old.jpg predates the row's cutoff (now-1h) by a further 2h - unambiguously pre-cutoff.
        $this->writeWithMtime('Reused/old.jpg', 'old', time() - 7200);
        $this->addRow(StorageOperationType::Move, 'Reused', 'Elsewhere/Reused', new DateTimeImmutable('-1 hour'));
        // simulate namespace reuse: new content arrives (now) long after the row's cutoff
        $this->write('Reused/new.jpg', 'new');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame('old', $this->adapter->read('Elsewhere/Reused/old.jpg'));
        $this->assertSame('new', $this->adapter->read('Reused/new.jpg'), 'post-cutoff file stays at the reused namespace');
        $this->assertFalse($this->adapter->fileExists('Elsewhere/Reused/new.jpg'));
    }

    public function testContentWrittenAfterRowCreationIsNeverSwept(): void
    {
        // the production shape: the row was queued an hour ago, a user wrote into the
        // re-created source namespace 30 minutes ago, the cron runs now
        $this->writeWithMtime('Window/old.jpg', 'old', time() - 7200);
        $this->addRow(StorageOperationType::Delete, 'Window', null, new DateTimeImmutable('-1 hour'));
        $this->writeWithMtime('Window/during-the-day.jpg', 'user-data', time() - 1800);

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertFalse($this->adapter->fileExists('Window/old.jpg'));
        $this->assertSame('user-data', $this->adapter->read('Window/during-the-day.jpg'), 'content written after row creation must never be swept');
        $this->assertSame([], $this->repository->all(), 'row completes - remaining content is post-cutoff');
    }

    public function testEmptySourceCompletesImmediately(): void
    {
        $this->addRow(StorageOperationType::Move, 'Ghost', 'Elsewhere/Ghost');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame([], $this->repository->all());
    }

    public function testRowsAreProcessedInFifoOrderAndOnlyIdFilters(): void
    {
        $this->write('One/a.jpg', '1');
        $this->write('Two/b.jpg', '2');
        $this->addRow(StorageOperationType::Move, 'One', 'Moved/One');
        $this->addRow(StorageOperationType::Move, 'Two', 'Moved/Two');
        $onlyId = $this->repository->all()[1]->getId();

        $result = $this->processor()->process($onlyId);

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame(1, $result->getPendingRows(), 'row One untouched by --id run');
        $this->assertTrue($this->adapter->fileExists('Moved/Two/b.jpg'));
        $this->assertTrue($this->adapter->fileExists('One/a.jpg'));
    }

    public function testFailureIsolationContinuesWithNextRow(): void
    {
        // a row for a storage the locator does not know -> exception -> failed, next row still runs
        $this->repository->add(new StorageOperation(
            null, 'thumbnail', StorageOperationType::Move, 'Broken', 'Elsewhere/Broken', new DateTimeImmutable('+5 seconds')
        ));
        $this->write('Fine/a.jpg', 'ok');
        $this->addRow(StorageOperationType::Move, 'Fine', 'Moved/Fine');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getFailedRows());
        $this->assertSame(1, $result->getProcessedRows());
        $this->assertCount(1, $result->getErrors());
        $this->assertTrue($this->adapter->fileExists('Moved/Fine/a.jpg'));
        $this->assertSame(1, $result->getPendingRows(), 'failed row stays queued');
    }

    public function testMaxRuntimeZeroStopsBeforeAnyRow(): void
    {
        $this->write('One/a.jpg', '1');
        $this->addRow(StorageOperationType::Move, 'One', 'Moved/One');

        $result = $this->processor()->process(null, 0);

        $this->assertTrue($result->isTimedOut());
        $this->assertSame(0, $result->getProcessedRows());
        $this->assertSame(1, $result->getPendingRows());
        $this->assertTrue($this->adapter->fileExists('One/a.jpg'), 'nothing touched after deadline');
    }

    public function testResumeAfterPartialCopyIsIdempotent(): void
    {
        // simulate copy-then-crash: target already holds the copy, source entry still present
        $this->write('Campaigns/a.jpg', 'same-bytes');
        $this->write('Archive/Campaigns/a.jpg', 'same-bytes');
        $this->addRow(StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame('same-bytes', $this->adapter->read('Archive/Campaigns/a.jpg'));
        $this->assertFalse($this->adapter->fileExists('Campaigns/a.jpg'));
    }
}

/**
 * Test-only PSR-11 locator fake: resolves only the 'asset' storage, otherwise throws a
 * not-found exception, so testFailureIsolationContinuesWithNextRow can exercise the
 * processor's per-row failure isolation.
 */
final class StorageOperationQueueProcessorTestAdapterLocator implements ContainerInterface
{
    public function __construct(private readonly FilesystemAdapter $adapter)
    {
    }

    public function get(string $id): FilesystemAdapter
    {
        if ($id !== 'asset') {
            throw new StorageOperationQueueProcessorTestAdapterNotFoundException('no adapter for ' . $id);
        }

        return $this->adapter;
    }

    public function has(string $id): bool
    {
        return $id === 'asset';
    }
}

final class StorageOperationQueueProcessorTestAdapterNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
