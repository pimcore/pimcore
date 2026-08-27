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

use Closure;
use Codeception\Test\Unit;
use DateTimeImmutable;
use FilesystemIterator;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
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
use ReflectionMethod;
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

    /**
     * F1 regression: A -> B queued 2h ago; B is then deleted while A's legacy content is still
     * pending, converting the Move row into a Delete-A tombstone. Namespace reuse: content
     * written into the re-created A folder AFTER the move was queued (but before the delete
     * conversion) must survive - only a fresh "now" cutoff on conversion would misclassify it
     * as pre-cutoff and destroy it.
     */
    public function testConvertedRowSparesContentWrittenIntoReusedSourceNamespace(): void
    {
        $this->writeWithMtime('A/old.jpg', 'old', time() - 10800); // -3h: predates the move
        $this->addRow(StorageOperationType::Move, 'A', 'B', new DateTimeImmutable('-2 hours'));
        // A is re-created (namespace reuse) after the move was queued, before the delete arrives
        $this->writeWithMtime('A/reused.jpg', 'reused', time() - 3600); // -1h

        // simulates a live QueueAwareStorageAdapter::deleteDirectory('B'): converts the pending
        // A -> B move into a Delete-A tombstone (preserving A's original created_at, per F1)
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Delete, 'B', null, new DateTimeImmutable()
        ));

        $result = $this->processor()->process();

        $this->assertSame(0, $result->getFailedRows());
        $this->assertFalse($this->adapter->fileExists('A/old.jpg'), 'pre-cutoff legacy content swept');
        $this->assertSame(
            'reused',
            $this->adapter->read('A/reused.jpg'),
            'content written into the reused source namespace after the move must survive the sweep'
        );
        $this->assertSame([], $this->repository->all(), 'row(s) complete - remaining content is post-cutoff');
    }

    /**
     * F3 regression: timestamps have 1-second resolution, so a file written in the SAME second
     * as the row's cutoff must be treated as post-cutoff (spared) - and, symmetrically, must not
     * block the row's completion either.
     */
    public function testSameSecondWriteIsNeverSwept(): void
    {
        $ts = time() - 3600;
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Delete, 'Trash', null, (new DateTimeImmutable())->setTimestamp($ts)
        ));
        $this->writeWithMtime('Trash/pre-cutoff.jpg', 'pre', $ts - 10);
        $this->writeWithMtime('Trash/equal.jpg', 'equal', $ts);

        $result = $this->processor()->process();

        $this->assertFalse($this->adapter->fileExists('Trash/pre-cutoff.jpg'), 'strictly pre-cutoff file swept');
        $this->assertSame('equal', $this->adapter->read('Trash/equal.jpg'), 'same-second file must survive');
        $this->assertSame([], $this->repository->all(), 'completion must not be blocked by the equal-timestamp file');
    }

    public function testEmptySourceCompletesImmediately(): void
    {
        $this->addRow(StorageOperationType::Move, 'Ghost', 'Elsewhere/Ghost');

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame([], $this->repository->all());
    }

    public function testOnlyIdFilters(): void
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

    public function testRowsAreProcessedInFifoOrder(): void
    {
        // Both rows drain into the same target key with different content. G2 (Copilot review
        // round 2): Move rows sharing an identical target_prefix are drained newest-first, so
        // row2 (added second) must win and row1's colliding file is superseded-deleted - this
        // is the same newest-first tie-break exercised by testReplacedAssetSurvivesFlattenedReMoveDrain,
        // here for two independently-queued rows that happen to share a target.
        $this->writeWithMtime('A/same.jpg', 'from-A', time() - 7200);
        $this->writeWithMtime('B/same.jpg', 'from-B', time() - 7200);
        $this->addRow(StorageOperationType::Move, 'A', 'T', new DateTimeImmutable('-1 hour'));
        $this->addRow(StorageOperationType::Move, 'B', 'T', new DateTimeImmutable('-1 hour'));

        $result = $this->processor()->process();

        $this->assertSame(2, $result->getProcessedRows());
        $this->assertSame('from-B', $this->adapter->read('T/same.jpg'), 'row2 (newest, same target) copy wins; row1 file is superseded-deleted');
        $this->assertFalse($this->adapter->fileExists('A/same.jpg'));
        $this->assertFalse($this->adapter->fileExists('B/same.jpg'));
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

    /**
     * C1 regression: live traffic re-moves the row's target (B -> C) while the processor is
     * mid-drain, holding a stale A -> B snapshot. Without reconciliation, files copied to B
     * before the repoint strand there permanently once row #1 is removed (its DB row now
     * points at C, but the bytes physically sit at B with nothing left to move them again).
     */
    public function testMidDrainRepointReconcilesCopiesToNewTarget(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->writeWithMtime("A/file{$i}.jpg", "content-{$i}", time() - 7200);
        }
        $this->addRow(StorageOperationType::Move, 'A', 'B', new DateTimeImmutable('+5 seconds'));

        $mutatingAdapter = new StorageOperationQueueProcessorTestMutatingAdapter(
            $this->adapter,
            4,
            function (): void {
                // simulates a live QueueAwareStorageAdapter::move() re-moving B -> C: repoints
                // the pending A -> B row to A -> C and inserts the B -> C row
                $this->repository->add(new StorageOperation(
                    null, 'asset', StorageOperationType::Move, 'B', 'C', new DateTimeImmutable()
                ));
            }
        );
        $locator = new StorageOperationQueueProcessorTestAdapterLocator($mutatingAdapter);
        $processor = new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger(), 3);

        $result = $processor->process();

        $this->assertSame(1, $result->getProcessedRows(), 'row #1 (moved-then-repointed) completed');
        for ($i = 1; $i <= 8; $i++) {
            $this->assertSame(
                "content-{$i}",
                $this->adapter->read("C/file{$i}.jpg"),
                "file{$i} must land at the final target C, not strand at the stale mid-drain target B"
            );
            $this->assertFalse($this->adapter->fileExists("B/file{$i}.jpg"), "file{$i} must not remain at the stale target B");
        }

        // row #2 (B -> C, inserted by the callback) is still queued - a second run finishes it
        $result = $processor->process();

        $this->assertSame([], $this->repository->all(), 'queue empty once the follow-up row is processed');
        $this->assertFalse($this->adapter->directoryExists('B'), 'stale target cleaned up once drained');
        for ($i = 1; $i <= 8; $i++) {
            $this->assertSame("content-{$i}", $this->adapter->read("C/file{$i}.jpg"));
        }
    }

    /**
     * C1 regression: live traffic deletes the row's target (B) while the processor is mid-drain,
     * converting the pending A -> B move into a Delete-A tombstone. Without reconciliation, the
     * processor - still holding the stale Move snapshot - keeps copying A's remaining content
     * into B after the user already deleted it (deleted-content resurrection).
     */
    public function testMidDrainConversionToDeleteRemovesTrackedCopies(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->writeWithMtime("A/file{$i}.jpg", "content-{$i}", time() - 7200);
        }
        $this->addRow(StorageOperationType::Move, 'A', 'B', new DateTimeImmutable('+5 seconds'));

        $mutatingAdapter = new StorageOperationQueueProcessorTestMutatingAdapter(
            $this->adapter,
            4,
            function (): void {
                // simulates a live QueueAwareStorageAdapter::deleteDirectory('B'): converts the
                // pending A -> B move row into a Delete-A tombstone and adds a Delete-B tombstone
                $this->repository->add(new StorageOperation(
                    null, 'asset', StorageOperationType::Delete, 'B', null, new DateTimeImmutable()
                ));
            }
        );
        $locator = new StorageOperationQueueProcessorTestAdapterLocator($mutatingAdapter);
        $processor = new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger(), 3);

        $result = $processor->process();

        $this->assertSame(0, $result->getProcessedRows(), 'the converted row is aborted mid-drain, not completed');
        for ($i = 1; $i <= 8; $i++) {
            $this->assertFalse(
                $this->adapter->fileExists("B/file{$i}.jpg"),
                "file{$i}: tracked copy at the stale target B must be removed, not resurrect deleted content"
            );
        }
        $remaining = $this->repository->all();
        $this->assertCount(2, $remaining, 'the converted Delete-A row and the new Delete-B row both remain queued');
        $sources = array_map(static fn (StorageOperation $op) => $op->getSourcePrefix(), $remaining);
        sort($sources);
        $this->assertSame(['A', 'B'], $sources);
        foreach ($remaining as $op) {
            $this->assertSame(StorageOperationType::Delete, $op->getType());
        }

        $result = $processor->process();

        $this->assertSame([], $this->repository->all(), 'queue empty once both delete rows complete');
        $this->assertFalse($this->adapter->directoryExists('A'));
        for ($i = 1; $i <= 8; $i++) {
            $this->assertFalse($this->adapter->fileExists("A/file{$i}.jpg"));
            $this->assertFalse($this->adapter->fileExists("B/file{$i}.jpg"));
        }
    }

    /**
     * G2 regression (Copilot review round 2): pending A -> B; the user replaces the asset
     * (fresh bytes land literally at B/x); the user then moves B -> C, which repoints the
     * pending row (A -> C, older) and adds a new row (B -> C, newer) - both now sharing the
     * SAME target. Strict FIFO drains the older A -> C row first, landing the STALE bytes at
     * C/x; the newer B -> C row then sees the target already occupied and deletes the FRESH
     * B/x - permanent data loss. Draining same-target Move rows newest-first fixes this.
     */
    public function testReplacedAssetSurvivesFlattenedReMoveDrain(): void
    {
        $t1 = time() - 7200; // row1 (A -> B, later repointed to A -> C) cutoff
        $t2 = time() - 3600; // row2 (B -> C) cutoff

        $this->writeWithMtime('A/x.jpg', 'stale-bytes', $t1 - 3600); // pre-cutoff for row1
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'A', 'B', (new DateTimeImmutable())->setTimestamp($t1)
        ));
        // fresh replacement, written between row1's and row2's cutoffs
        $this->writeWithMtime('B/x.jpg', 'fresh-bytes', $t1 + 1800);
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'B', 'C', (new DateTimeImmutable())->setTimestamp($t2)
        ));

        $result = $this->processor()->process();

        $this->assertSame(
            'fresh-bytes',
            $this->adapter->read('C/x.jpg'),
            'the fresh replacement must survive the flattened re-move drain'
        );
        $this->assertFalse($this->adapter->fileExists('A/x.jpg'));
        $this->assertFalse($this->adapter->fileExists('B/x.jpg'));
        $this->assertFalse($this->adapter->directoryExists('A'), 'source A fully drained');
        $this->assertFalse($this->adapter->directoryExists('B'), 'source B fully drained');
        $this->assertSame(2, $result->getProcessedRows());
        $this->assertSame([], $this->repository->all(), 'queue empty - both rows complete');
    }

    /**
     * G3 regression (Copilot review round 2): a Move-row entry whose mtime lands EXACTLY on the
     * row's cutoff second was, on BASE, skipped entirely by the drain (same treatment as a
     * strictly-post-cutoff namespace-reuse file) - yet the completion re-list already spares
     * equality from blocking completion, so the row completed and stranded the file: gone from
     * the (deleted) source directory tree conceptually reachable only at the source, never
     * copied to the target. The fix copies equality files to the target (without deleting the
     * source) so they stay reachable at BOTH ends.
     */
    public function testSameSecondFileOnMoveRowStaysReadableAtBothEnds(): void
    {
        $ts = time() - 3600;
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns', (new DateTimeImmutable())->setTimestamp($ts)
        ));
        $this->writeWithMtime('Campaigns/pre-cutoff.jpg', 'pre', $ts - 10);
        $this->writeWithMtime('Campaigns/equal.jpg', 'equal', $ts);

        $result = $this->processor()->process();

        $this->assertSame(1, $result->getProcessedRows(), 'the row completes');
        $this->assertSame([], $this->repository->all());
        $this->assertSame('equal', $this->adapter->read('Archive/Campaigns/equal.jpg'), 'equal-cutoff file must be reachable at the target');
        $this->assertSame('equal', $this->adapter->read('Campaigns/equal.jpg'), 'equal-cutoff file must still be present at the source (duplicate accepted)');
        $this->assertSame('pre', $this->adapter->read('Archive/Campaigns/pre-cutoff.jpg'), 'strictly pre-cutoff sibling is moved normally');
        $this->assertFalse($this->adapter->fileExists('Campaigns/pre-cutoff.jpg'), 'strictly pre-cutoff sibling is removed from the source');
    }

    /**
     * G4 (Copilot review round 2): the processor invokes the optional heartbeat closure at
     * interval ticks during a drain, so a caller (the command) can refresh a held lock on long
     * runs.
     */
    public function testHeartbeatIsInvokedDuringProcessing(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->write("Campaigns/file{$i}.jpg", "content-{$i}");
        }
        $this->addRow(StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns');
        $locator = new StorageOperationQueueProcessorTestAdapterLocator($this->adapter);
        $processor = new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger(), 2);

        $calls = 0;
        $result = $processor->process(null, null, function () use (&$calls): void {
            $calls++;
        });

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertGreaterThanOrEqual(1, $calls, 'heartbeat must fire at least once across the interval ticks');
    }

    public function testThrowingHeartbeatDoesNotFailProcessing(): void
    {
        $this->write('Campaigns/a.jpg', 'a');
        $this->addRow(StorageOperationType::Move, 'Campaigns', 'Archive/Campaigns');
        $locator = new StorageOperationQueueProcessorTestAdapterLocator($this->adapter);
        $processor = new StorageOperationQueueProcessor($locator, $this->repository, new NullLogger(), 1);

        $result = $processor->process(null, null, static function (): void {
            throw new RuntimeException('lock lost');
        });

        $this->assertSame(1, $result->getProcessedRows(), 'a failing heartbeat must not fail the run');
        $this->assertSame(0, $result->getFailedRows());
        $this->assertTrue($this->adapter->fileExists('Archive/Campaigns/a.jpg'));
    }

    /**
     * H2 (Copilot round 3): two Move rows share an IDENTICAL target - a re-move flattened them
     * onto the same cluster (see orderForProcessing). Processing the OLDER row directly via
     * --id, out of the newest-first cluster order that full runs enforce, risks landing stale
     * bytes at the shared target and then having the newer row see the target occupied and
     * delete its own (fresher) source content. --id must refuse the older row instead.
     */
    public function testIdRefusesOlderRowOfSameTargetCluster(): void
    {
        $this->write('A/same.jpg', 'from-A');
        $this->write('B/same.jpg', 'from-B');
        $this->addRow(StorageOperationType::Move, 'A', 'T');
        $this->addRow(StorageOperationType::Move, 'B', 'T');
        $olderId = $this->repository->all()[0]->getId();
        $newerId = $this->repository->all()[1]->getId();

        $result = $this->processor()->process($olderId);

        $this->assertSame(0, $result->getProcessedRows());
        $this->assertSame(1, $result->getFailedRows());
        $this->assertCount(1, $result->getErrors());
        $this->assertStringContainsString((string) $newerId, $result->getErrors()[0]);
        $this->assertTrue($this->adapter->fileExists('A/same.jpg'), 'older row source untouched');
        $this->assertTrue($this->adapter->fileExists('B/same.jpg'), 'newer row source untouched');
        $this->assertFalse($this->adapter->fileExists('T/same.jpg'), 'nothing landed at the shared target');
        $this->assertCount(2, $this->repository->all(), 'both rows still queued');
    }

    public function testIdProcessesNewestRowOfCluster(): void
    {
        $this->write('A/same.jpg', 'from-A');
        $this->write('B/same.jpg', 'from-B');
        $this->addRow(StorageOperationType::Move, 'A', 'T');
        $this->addRow(StorageOperationType::Move, 'B', 'T');
        $newerId = $this->repository->all()[1]->getId();

        $result = $this->processor()->process($newerId);

        $this->assertSame(1, $result->getProcessedRows());
        $this->assertSame(0, $result->getFailedRows());
        $this->assertSame('from-B', $this->adapter->read('T/same.jpg'));
        $this->assertFalse($this->adapter->fileExists('B/same.jpg'));
        $this->assertSame(1, $result->getPendingRows(), 'older row untouched, stays queued');
    }

    public function testOrderForProcessingKeepsFifoOtherwise(): void
    {
        $ops = [
            new StorageOperation(1, 'asset', StorageOperationType::Delete, 'D', null, new DateTimeImmutable()),
            new StorageOperation(2, 'asset', StorageOperationType::Move, 'M1', 'X', new DateTimeImmutable()),
            new StorageOperation(3, 'asset', StorageOperationType::Move, 'M2', 'Y', new DateTimeImmutable()),
            new StorageOperation(4, 'asset', StorageOperationType::Move, 'M3', 'Z', new DateTimeImmutable()),
            new StorageOperation(5, 'asset', StorageOperationType::Move, 'M4', 'Z', new DateTimeImmutable()),
        ];

        $processor = $this->processor();
        $method = new ReflectionMethod($processor, 'orderForProcessing');
        $method->setAccessible(true);

        /** @var StorageOperation[] $ordered */
        $ordered = $method->invoke($processor, $ops);

        $this->assertSame(
            [1, 2, 3, 5, 4],
            array_map(static fn (StorageOperation $op) => $op->getId(), $ordered),
            'only the equal-target Move cluster (ids 4 and 5) is reversed, in place; everything else stays FIFO'
        );
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

/**
 * Test-only Flysystem adapter decorator: wraps another adapter and invokes a callback exactly
 * once, right after the Nth copy() call completes, to simulate live traffic mutating the row
 * the processor is currently draining (a re-move or a covering delete) against the fake
 * repository, exactly as QueueAwareStorageAdapter::move()/deleteDirectory() would. Delegates
 * every other call unchanged.
 */
final class StorageOperationQueueProcessorTestMutatingAdapter implements FilesystemAdapter
{
    private int $copyCount = 0;

    public function __construct(
        private readonly FilesystemAdapter $inner,
        private readonly int $afterNthCopy,
        private readonly Closure $callback,
    ) {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->inner->copy($source, $destination, $config);
        $this->copyCount++;
        if ($this->copyCount === $this->afterNthCopy) {
            ($this->callback)();
        }
    }

    public function fileExists(string $path): bool
    {
        return $this->inner->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->inner->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->inner->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->inner->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->inner->read($path);
    }

    public function readStream(string $path)
    {
        return $this->inner->readStream($path);
    }

    public function delete(string $path): void
    {
        $this->inner->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->inner->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->inner->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->inner->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->inner->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->inner->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->inner->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->inner->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->inner->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->inner->move($source, $destination, $config);
    }
}
