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

namespace Pimcore\Asset\StorageQueue;

use Closure;
use Exception;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use Pimcore\Cache;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Applies pending storage operations on the inner (undecorated) adapters. Rows are processed
 * strictly in FIFO (id ascending) order, single-threaded — the order is load-bearing when a
 * delete tombstone overlaps an earlier move's source. Guards (from the design review):
 * entries newer than the row's cutoff belong to a re-used namespace and are never touched;
 * entries that cannot be dated are never treated destructively; an existing target key is
 * never overwritten (literal wins, and gives idempotent resume). The cutoff is exactly the
 * row's own createdAt - rows routinely sit in the queue for hours (nightly cron is the design),
 * and any attempt to narrow that window based on "now" would misclassify legitimate same-day
 * reuse content as pre-cutoff and destroy or teleport it.
 *
 * @internal
 */
final class StorageOperationQueueProcessor
{
    private const DEADLINE_CHECK_INTERVAL = 100;

    private const COMPLETION_ATTEMPTS = 3;

    public function __construct(
        private readonly ContainerInterface $innerAdapters,
        private readonly StorageOperationQueueRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly int $checkInterval = self::DEADLINE_CHECK_INTERVAL,
    ) {
    }

    public function process(?int $onlyId = null, ?int $maxRuntimeSeconds = null, ?Closure $heartbeat = null): StorageQueueProcessingResult
    {
        $deadline = $maxRuntimeSeconds !== null ? time() + $maxRuntimeSeconds : null;
        $processed = 0;
        $failed = 0;
        $timedOut = false;
        $errors = [];
        $clearedAssetMove = false;

        $operations = $onlyId !== null
            ? array_filter([$this->repository->findById($onlyId)])
            : $this->orderForProcessing($this->repository->all());

        foreach ($operations as $operation) {
            if ($deadline !== null && time() >= $deadline) {
                $timedOut = true;

                break;
            }

            $this->invokeHeartbeat($heartbeat); // row boundary

            try {
                if ($this->processOperation($operation, $deadline, $heartbeat)) {
                    $processed++;
                    if ($operation->getType() === StorageOperationType::Move && $operation->getStorage() === 'asset') {
                        $clearedAssetMove = true;
                    }
                }
                // incomplete rows (deadline hit, undated entries, contested rows) stay queued
                // for the next run - processOperation removes its own row on completion
            } catch (Exception $e) {
                $failed++;
                $errors[] = sprintf(
                    '#%d %s %s: %s',
                    $operation->getId(),
                    $operation->getType()->value,
                    $operation->getSourcePrefix(),
                    $e->getMessage()
                );
                $this->logger->error('Storage queue operation failed', [
                    'operation' => $operation->getId(),
                    'storage' => $operation->getStorage(),
                    'exception' => $e,
                ]);
            }
        }

        if ($clearedAssetMove) {
            Cache::clearTag('output'); // window-era physical URLs may sit in full-page cache
        }

        return new StorageQueueProcessingResult(
            $processed,
            $failed,
            count($this->repository->all()),
            $timedOut,
            $errors,
        );
    }

    /**
     * @return bool true when the operation is fully applied and its row has been removed
     */
    private function processOperation(StorageOperation $operation, ?int $deadline, ?Closure $heartbeat): bool
    {
        /** @var FilesystemAdapter $adapter */
        $adapter = $this->innerAdapters->get($operation->getStorage());

        return $operation->getType() === StorageOperationType::Move
            ? $this->processMove($adapter, $operation, $deadline, $heartbeat)
            : $this->processDelete($adapter, $operation, $deadline, $heartbeat);
    }

    /**
     * Invokes the optional heartbeat closure (typically a lock refresh) at interval ticks and
     * row boundaries during processing. Exceptions are swallowed: a heartbeat failure (e.g. the
     * lock was lost) is worse handled by aborting a run than by finishing it - single-host
     * semantics are assumed and documented at the call site (the command).
     */
    private function invokeHeartbeat(?Closure $heartbeat): void
    {
        if ($heartbeat === null) {
            return;
        }

        try {
            $heartbeat();
        } catch (Exception $e) {
            $this->logger->debug('Storage queue heartbeat failed', ['exception' => $e]);
        }
    }

    /**
     * Reorders operations for processing: global id-ASC (FIFO) is preserved, except that Move
     * rows sharing an IDENTICAL target_prefix are drained newest-first within their cluster, at
     * the position of the cluster's first (oldest) member. Delete rows and Move rows with
     * distinct targets are untouched and keep strict FIFO.
     *
     * Rationale: a re-move can flatten several rows onto the same final target (e.g. a pending
     * A -> B move gets repointed to A -> C when B -> C is queued). If the source content was
     * replaced with fresher bytes while pending, strict FIFO would drain the OLDER row first,
     * landing stale bytes at the shared target; the newer row would then see the target already
     * occupied and delete its own (fresher) source content - permanent data loss. Draining the
     * newest row in the cluster first lands the freshest bytes at the target before any older,
     * superseded row gets a chance to claim that key.
     *
     * Pure and side-effect-free so it can be unit-tested directly.
     *
     * @param StorageOperation[] $operations
     *
     * @return StorageOperation[]
     */
    private function orderForProcessing(array $operations): array
    {
        $moveClusters = [];
        foreach ($operations as $operation) {
            if ($operation->getType() === StorageOperationType::Move) {
                $moveClusters[(string) $operation->getTargetPrefix()][] = $operation;
            }
        }

        $emittedClusters = [];
        $ordered = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== StorageOperationType::Move) {
                $ordered[] = $operation;

                continue;
            }

            $targetPrefix = (string) $operation->getTargetPrefix();
            $cluster = $moveClusters[$targetPrefix];
            if (count($cluster) < 2) {
                $ordered[] = $operation;

                continue;
            }

            if (isset($emittedClusters[$targetPrefix])) {
                continue; // already emitted (reversed) at the position of its first member
            }
            $emittedClusters[$targetPrefix] = true;
            foreach (array_reverse($cluster) as $clusterMember) {
                $ordered[] = $clusterMember;
            }
        }

        return $ordered;
    }

    /**
     * Delete rows are immutable once queued (only Move rows are repointed/converted by live
     * traffic), so no reconciliation is needed here - just the deadline-honoring re-listing
     * on completion (M5) and self-removal on completion.
     */
    private function processDelete(FilesystemAdapter $adapter, StorageOperation $operation, ?int $deadline, ?Closure $heartbeat): bool
    {
        $cutoff = $operation->getCreatedAt()->getTimestamp();
        $source = $operation->getSourcePrefix();

        if (!$adapter->directoryExists($source)) {
            $this->repository->remove((int) $operation->getId());

            return true; // nothing left - idempotent completion
        }

        $entriesSinceCheck = 0;
        foreach ($adapter->listContents($source, true) as $item) {
            if ($deadline !== null && ++$entriesSinceCheck >= self::DEADLINE_CHECK_INTERVAL) {
                $entriesSinceCheck = 0;
                $this->invokeHeartbeat($heartbeat);
                if (time() >= $deadline) {
                    return false;
                }
            }
            if (!$item->isFile()) {
                continue;
            }

            $path = $item->path();
            $lastModified = $item->lastModified() ?? $adapter->lastModified($path)->lastModified();
            if ($lastModified === null || $lastModified >= $cutoff) {
                continue; // undated (never destructive), same-second write, or namespace-reuse content
            }

            $adapter->delete($path);
        }

        // completion check on a fresh listing, deadline-honoring (M5)
        $filesRemain = false;
        $entriesSinceCheck = 0;
        foreach ($adapter->listContents($source, true) as $item) {
            if ($deadline !== null && ++$entriesSinceCheck >= self::DEADLINE_CHECK_INTERVAL) {
                $entriesSinceCheck = 0;
                $this->invokeHeartbeat($heartbeat);
                if (time() >= $deadline) {
                    return false;
                }
            }
            if (!$item->isFile()) {
                continue;
            }
            $lastModified = $item->lastModified() ?? $adapter->lastModified($item->path())->lastModified();
            if ($lastModified === null || $lastModified < $cutoff) {
                return false; // undated or still-pending entries keep the row alive - equality is spared, so it must not block completion either
            }
            $filesRemain = true;
        }

        if (!$filesRemain) {
            $adapter->deleteDirectory($source); // cleanup (empty dirs on local backends)
        }

        $this->repository->remove((int) $operation->getId());

        return true;
    }

    /**
     * Move rows can be mutated by live traffic while they are being drained: repointed to a new
     * target (re-move) or converted into a Delete row (a covering delete). Periodically
     * re-fetches the row (every $checkInterval listing entries) and reconciles the copies made
     * so far against its current state, so the drain never strands files at a target the row no
     * longer points to, or resurrects content the user just deleted.
     */
    private function processMove(FilesystemAdapter $adapter, StorageOperation $operation, ?int $deadline, ?Closure $heartbeat): bool
    {
        $current = $operation;
        $cutoff = $current->getCreatedAt()->getTimestamp(); // anchored to the ORIGINAL creation - repoint does not change it
        $source = $current->getSourcePrefix();
        $copied = []; // relative suffix => target prefix the copy was made under

        if ($adapter->directoryExists($source)) {
            $entriesSinceCheck = 0;
            foreach ($adapter->listContents($source, true) as $item) {
                if (++$entriesSinceCheck >= $this->checkInterval) {
                    $entriesSinceCheck = 0;
                    $this->invokeHeartbeat($heartbeat);
                    if ($deadline !== null && time() >= $deadline) {
                        return false;
                    }
                    $current = $this->refreshMoveRow($adapter, $current, $copied);
                    if ($current === null) {
                        return false; // row vanished or was converted - tracked copies already reconciled
                    }
                }
                if (!$item->isFile()) {
                    continue;
                }
                $path = $item->path();
                $lastModified = $item->lastModified() ?? $adapter->lastModified($path)->lastModified();
                if ($lastModified === null) {
                    continue; // undated - never destructive
                }
                $suffix = mb_substr($path, mb_strlen($source));
                $target = $this->targetPrefixOf($current) . $suffix;

                if ($lastModified > $cutoff) {
                    continue; // namespace-reuse content, strictly post-cutoff - never touched
                }

                if ($lastModified === $cutoff) {
                    // Exact boundary: a same-second write cannot be told apart from content that
                    // legitimately predates the row. Copy it to the target so it is reachable
                    // there too, but never delete the source and never track it in $copied - it
                    // must not be swept, re-targeted on a repoint, or block completion (the
                    // completion re-list already treats equality as non-blocking).
                    if (!$adapter->fileExists($target)) {
                        $adapter->copy($path, $target, new Config());
                    }

                    continue;
                }

                // $lastModified < $cutoff: unambiguously pre-cutoff content
                if (!$adapter->fileExists($target)) {
                    $adapter->copy($path, $target, new Config());
                    if (!$adapter->fileExists($target)) {
                        throw new RuntimeException(sprintf('Copy verification failed for %s -> %s', $path, $target));
                    }
                }
                // existing target key: literal wins - never overwrite; the source entry is superseded
                $copied[$suffix] = $this->targetPrefixOf($current);
                $adapter->delete($path);
            }

            // completion check on a fresh listing, deadline-honoring (M5)
            $filesRemain = false;
            $entriesSinceCheck = 0;
            foreach ($adapter->listContents($source, true) as $item) {
                if ($deadline !== null && ++$entriesSinceCheck >= $this->checkInterval) {
                    $entriesSinceCheck = 0;
                    $this->invokeHeartbeat($heartbeat);
                    if (time() >= $deadline) {
                        return false;
                    }
                }
                if (!$item->isFile()) {
                    continue;
                }
                $lastModified = $item->lastModified() ?? $adapter->lastModified($item->path())->lastModified();
                if ($lastModified === null || $lastModified < $cutoff) {
                    return false; // undated or still-pending entries keep the row alive - equality is spared, so it must not block completion either
                }
                $filesRemain = true;
            }
            if (!$filesRemain) {
                $adapter->deleteDirectory($source); // cleanup (empty dirs on local backends)
            }
        }

        return $this->completeMove($adapter, $current, $copied);
    }

    /**
     * Re-fetches the row and reconciles tracked copies with its current state. Returns the fresh
     * row to continue with, or null when the drain must abort (row vanished or converted to a
     * delete - in the latter case the tracked copies are logically deleted content and are
     * removed here; the converted row is processed on a later run under its own cutoff).
     *
     * @param array<string, string> $copied relative suffix => target prefix the copy was made
     *                                       under; updated in place to reflect the current target
     */
    private function refreshMoveRow(FilesystemAdapter $adapter, StorageOperation $known, array &$copied): ?StorageOperation
    {
        $fresh = $this->repository->findById((int) $known->getId());
        if ($fresh === null || $fresh->getType() !== StorageOperationType::Move) {
            if ($fresh !== null) {
                foreach ($copied as $suffix => $usedTarget) {
                    $stale = $usedTarget . $suffix;
                    if ($adapter->fileExists($stale)) {
                        $adapter->delete($stale);
                    }
                }
            }

            return null;
        }
        if ($fresh->getTargetPrefix() !== $known->getTargetPrefix()) {
            foreach ($copied as $suffix => $usedTarget) {
                if ($usedTarget === $fresh->getTargetPrefix()) {
                    continue;
                }
                $stale = $usedTarget . $suffix;
                $new = $this->targetPrefixOf($fresh) . $suffix;
                if ($adapter->fileExists($stale)) {
                    if (!$adapter->fileExists($new)) {
                        $adapter->copy($stale, $new, new Config());
                    }
                    $adapter->delete($stale);
                }
                $copied[$suffix] = $this->targetPrefixOf($fresh);
            }
        }

        return $fresh;
    }

    /**
     * A Move row's target prefix is guaranteed non-null by the StorageOperation value object;
     * this narrows the type for static analysis and fails loudly if the invariant ever breaks.
     */
    private function targetPrefixOf(StorageOperation $operation): string
    {
        return $operation->getTargetPrefix() ?? throw new RuntimeException(sprintf('Move operation #%d has no target prefix', (int) $operation->getId()));
    }

    /**
     * @param array<string, string> $copied
     */
    private function completeMove(FilesystemAdapter $adapter, StorageOperation $current, array $copied): bool
    {
        for ($attempt = 0; $attempt < self::COMPLETION_ATTEMPTS; $attempt++) {
            if ($this->repository->removeIfUnchanged($current)) {
                return true;
            }
            $current = $this->refreshMoveRow($adapter, $current, $copied);
            if ($current === null) {
                return false;
            }
        }

        return false; // row keeps changing under us - leave it for the next run
    }
}
