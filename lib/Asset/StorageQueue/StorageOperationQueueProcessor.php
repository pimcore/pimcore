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

    /**
     * @var list<StorageOperation>|null run-scoped snapshot, see pendingMoves()
     */
    private ?array $pendingMoves = null;

    private const COMPLETION_ATTEMPTS = 3;

    public function __construct(
        private readonly ContainerInterface $innerAdapters,
        private readonly StorageOperationQueueRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly int $checkInterval = self::DEADLINE_CHECK_INTERVAL,
    ) {
    }

    /**
     * @param bool $stopOnError end the run at the first failing row instead of isolating it. By
     *                          default the run carries on after a failure, so one unprocessable
     *                          row does not stop the rest - though a Move that could not complete
     *                          still keeps an overlapping Delete deferred, which is the whole
     *                          point of the dependency guard. During a risky window (a large
     *                          migration, say) an operator can ask for a hard stop instead.
     */
    public function process(
        ?int $onlyId = null,
        ?int $maxRuntimeSeconds = null,
        ?Closure $heartbeat = null,
        bool $stopOnError = false
    ): StorageQueueProcessingResult {
        $deadline = $maxRuntimeSeconds !== null ? time() + $maxRuntimeSeconds : null;
        $this->pendingMoves = null; // fresh snapshot per run
        $stoppedOnError = false;
        $processed = 0;
        $failed = 0;
        $timedOut = false;
        $errors = [];
        $clearedAssetMove = false;
        /** @var array<int, int>|null $barriers */
        $barriers = null;
        $haltedClusters = [];

        if ($onlyId !== null) {
            $requested = $this->repository->findById($onlyId);
            $operations = array_filter([$requested]);

            if ($requested !== null && $requested->getType() === StorageOperationType::Move) {
                $newerSameTarget = $this->findNewerSameTargetRow($requested);
                $olderDelete = $this->findOlderOverlappingDelete($requested);
                if ($newerSameTarget !== null) {
                    $operations = [];
                    $failed++;
                    $errors[] = sprintf(
                        '#%d move %s: refusing to process out of order - row #%d targets the same prefix "%s" and is newer; run without --id so the cluster drains newest-first',
                        $requested->getId(),
                        $requested->getSourcePrefix(),
                        $newerSameTarget->getId(),
                        (string) $requested->getTargetPrefix()
                    );
                } elseif ($olderDelete !== null) {
                    $operations = [];
                    $failed++;
                    $errors[] = sprintf(
                        '#%d move %s: refusing to process out of order - row #%d deletes the overlapping prefix "%s" and is older; run without --id so the queue drains in order',
                        $requested->getId(),
                        $requested->getSourcePrefix(),
                        $olderDelete->getId(),
                        $olderDelete->getSourcePrefix()
                    );
                }
            }
        } else {
            $queued = $this->repository->all();
            $barriers = $this->moveBarriers($queued);
            $operations = $this->orderForProcessing($queued, $barriers);
        }

        foreach ($operations as $operation) {
            if ($deadline !== null && time() >= $deadline) {
                $timedOut = true;

                break;
            }

            $this->invokeHeartbeat($heartbeat); // row boundary

            $cluster = $operation->getType() === StorageOperationType::Move && $barriers !== null
                ? $this->clusterKey($operation, $barriers)
                : null;

            // A same-target cluster drains newest-first so the freshest bytes claim the target
            // before any superseded row can. Once a member has not landed, the rest of that
            // cluster must wait: an older row would otherwise put stale bytes at the shared
            // target, and the row that failed would then find it occupied, treat its own source
            // as superseded and delete it. Rows outside the cluster are unaffected - a single
            // unprocessable row still must not hold up the queue.
            if ($cluster !== null && isset($haltedClusters[$cluster])) {
                continue; // stays queued for the next run
            }

            try {
                if ($this->processOperation($operation, $deadline, $heartbeat)) {
                    $processed++;
                    if ($operation->getType() === StorageOperationType::Move && $operation->getStorage() === 'asset') {
                        $clearedAssetMove = true;
                    }
                } elseif ($cluster !== null) {
                    // incomplete, not failed: the bytes are still at the source, so the same
                    // reasoning applies and the rest of the cluster waits too
                    $haltedClusters[$cluster] = true;
                }
                if ($operation->getType() === StorageOperationType::Move) {
                    // The snapshot below is what later Deletes consult. A Move that just drained
                    // no longer blocks anything, and one that ended incomplete may have been
                    // repointed under us, so the cached copy is stale either way. Dropping it
                    // costs one re-read per Move rather than per Delete, which is the ratio the
                    // snapshot exists to protect.
                    $this->pendingMoves = null;
                }
                // incomplete rows (deadline hit, undated entries, contested rows) stay queued
                // for the next run - processOperation removes its own row on completion
            } catch (Exception $e) {
                $failed++;
                if ($cluster !== null) {
                    $haltedClusters[$cluster] = true;
                }
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

                if ($stopOnError) {
                    $stoppedOnError = true;

                    break;
                }
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
            $stoppedOnError,
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
     * Finds the newest (highest id) Move row in the same storage sharing an IDENTICAL
     * target_prefix with $operation, other than $operation itself. Used to refuse processing an
     * older member of a same-target cluster via --id: orderForProcessing's newest-first drain
     * order is load-bearing for such clusters (see its docblock), and --id bypasses that
     * ordering entirely, so it must refuse instead of risking the same data-loss shape.
     */
    private function findNewerSameTargetRow(StorageOperation $operation): ?StorageOperation
    {
        $newest = null;
        foreach ($this->repository->all() as $candidate) {
            if ($candidate->getType() !== StorageOperationType::Move
                || $candidate->getStorage() !== $operation->getStorage()
                || $candidate->getTargetPrefix() !== $operation->getTargetPrefix()
                || (int) $candidate->getId() <= (int) $operation->getId()
            ) {
                continue;
            }
            if ($newest === null || (int) $candidate->getId() > (int) $newest->getId()) {
                $newest = $candidate;
            }
        }

        return $newest;
    }

    /**
     * Whether two storage prefixes name overlapping content: the same prefix, or one nested
     * inside the other. Both nesting directions matter, since either makes one operation's
     * outcome depend on whether the other ran first.
     */
    private function prefixesOverlap(string $a, string $b): bool
    {
        $a = trim($a, '/');
        $b = trim($b, '/');

        return $a === $b
            || str_starts_with($a, $b . '/')
            || str_starts_with($b, $a . '/');
    }

    /**
     * Whether the Delete's prefix overlaps either end of the Move.
     *
     * The SOURCE matters because the Move's bytes are still sitting there; the TARGET matters
     * because a prefix that only exists once the Move has run is content the Delete has not
     * seen yet. Ordering the two rows differently changes the outcome in both cases.
     */
    private function deleteOverlapsMove(StorageOperation $delete, StorageOperation $move): bool
    {
        foreach ([$move->getSourcePrefix(), $move->getTargetPrefix()] as $movePath) {
            if ($movePath !== null && $this->prefixesOverlap($delete->getSourcePrefix(), $movePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finds a pending Move the given Delete must not run ahead of.
     *
     * Two overlaps matter, in both nesting directions:
     *  - the Move's SOURCE: its bytes are still there (the move is only queued), so sweeping
     *    them would leave the asset with no copy anywhere and the Move permanently unsatisfiable;
     *  - the Move's TARGET: the Delete names a path that only exists through that Move, so the
     *    content it refers to has not been materialised yet. Completing the Delete now would let
     *    the Move recreate exactly the subtree the user deleted.
     *
     * Only Moves OLDER than the Delete qualify. A Move queued afterwards has to be processed
     * after it in FIFO order anyway, and letting it defer the Delete would allow content that
     * was explicitly deleted to be rescued out of the swept prefix.
     */
    private function findPendingMoveDependingOn(StorageOperation $delete): ?StorageOperation
    {
        foreach ($this->pendingMoves() as $candidate) {
            if ($candidate->getStorage() !== $delete->getStorage()
                || (int) $candidate->getId() >= (int) $delete->getId()
            ) {
                continue;
            }

            if ($this->deleteOverlapsMove($delete, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The mirror image of findPendingMoveDependingOn(), for the --id entry point.
     *
     * A full run drains in FIFO order, so an older Delete always gets its chance before a Move
     * that overlaps it. --id skips that ordering entirely: it would carry the bytes out of the
     * deleted prefix first, and the Delete would then find an empty directory and complete
     * silently - leaving explicitly deleted content alive under the move target.
     *
     * Overlap is tested in both nesting directions, and against the Move's target as well: a
     * Delete covering the target names content the Move has not materialised yet, so running the
     * Move first would recreate the subtree the Delete is meant to remove.
     */
    private function findOlderOverlappingDelete(StorageOperation $move): ?StorageOperation
    {
        foreach ($this->repository->all() as $candidate) {
            if ($candidate->getType() !== StorageOperationType::Delete
                || $candidate->getStorage() !== $move->getStorage()
                || (int) $candidate->getId() >= (int) $move->getId()
            ) {
                continue;
            }

            if ($this->deleteOverlapsMove($candidate, $move)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Run-scoped snapshot of the pending Move rows, so a backlog of Delete rows does not re-read
     * and re-hydrate the whole queue once per row. Refreshed explicitly while a long sweep is
     * running, since producers keep writing to the queue during a processor run.
     *
     * @return list<StorageOperation>
     */
    private function pendingMoves(): array
    {
        if ($this->pendingMoves === null) {
            $this->refreshPendingMoves();
        }

        return $this->pendingMoves ?? [];
    }

    private function logDeferredDelete(StorageOperation $delete, StorageOperation $blocking): void
    {
        $this->logger->info(
            'Storage queue delete deferred - a pending move still needs this content',
            [
                'delete' => $delete->getId(),
                'storage' => $delete->getStorage(),
                'prefix' => $delete->getSourcePrefix(),
                'blockedBy' => $blocking->getId(),
                'moveSource' => $blocking->getSourcePrefix(),
                'moveTarget' => $blocking->getTargetPrefix(),
            ]
        );
    }

    private function refreshPendingMoves(): void
    {
        $moves = [];
        foreach ($this->repository->all() as $row) {
            if ($row->getType() === StorageOperationType::Move) {
                $moves[] = $row;
            }
        }

        $this->pendingMoves = $moves;
    }

    /**
     * Cluster identity for the newest-first drain: same target prefix, same storage, and the same
     * most recent blocking Delete (see moveBarriers()).
     *
     * @param array<int, int> $barriers
     */
    private function clusterKey(StorageOperation $move, array $barriers): string
    {
        $barrier = $barriers[(int) $move->getId()] ?? 0;

        return $barrier . "\0" . $move->getStorage() . "\0" . (string) $move->getTargetPrefix();
    }

    /**
     * For each Move row, the id of the most recent Delete queued before it that overlaps the
     * Move (0 when there is none).
     *
     * This is what a same-target cluster may not be drained across. Reversing a cluster moves its
     * later members ahead of everything between them, so a Delete in that gap would suddenly see
     * different content: either the Move carried bytes out of the prefix the Delete was queued to
     * remove, or it dropped bytes into it just before the sweep. Two Moves share a barrier id
     * exactly when no such Delete sits between them, which is precisely when reordering them is
     * safe. A Delete that overlaps neither end of the Move is irrelevant and must NOT split the
     * cluster - doing so would strand the older row's stale bytes at the shared target, the very
     * data loss the newest-first drain exists to prevent.
     *
     * @param StorageOperation[] $operations
     *
     * @return array<int, int>
     */
    private function moveBarriers(array $operations): array
    {
        // Comparing every Move against every preceding Delete is quadratic, and it runs before
        // the first row-level deadline check, so a large backlog could burn the whole --max-runtime
        // budget on ordering alone. Two prefix indexes, both maintained incrementally as the queue
        // is walked, answer the same question with a handful of lookups per row:
        //   $exact[storage][p]      - newest Delete seen so far naming exactly prefix p,
        //                             which answers "same prefix" and, walked over the Move's
        //                             ancestors, "the Delete covers the Move";
        //   $descendant[storage][p] - newest Delete seen so far sitting strictly below p, which
        //                             answers "the Delete sits inside the Move".
        // Together those are exactly the three cases prefixesOverlap() tests, at a cost of one
        // lookup per path segment instead of one comparison per earlier Delete.
        $barriers = [];
        $exact = [];
        $descendant = [];

        foreach ($operations as $operation) {
            $storage = $operation->getStorage();

            if ($operation->getType() === StorageOperationType::Delete) {
                $id = (int) $operation->getId();
                $prefix = trim($operation->getSourcePrefix(), '/');
                $exact[$storage][$prefix] = max($exact[$storage][$prefix] ?? 0, $id);
                foreach ($this->ancestorPrefixes($prefix) as $ancestor) {
                    $descendant[$storage][$ancestor] = max($descendant[$storage][$ancestor] ?? 0, $id);
                }

                continue;
            }

            // everything else in the queue is a Move
            $barrier = 0;
            foreach ([$operation->getSourcePrefix(), $operation->getTargetPrefix()] as $movePath) {
                if ($movePath === null) {
                    continue;
                }
                $movePath = trim($movePath, '/');
                $barrier = max(
                    $barrier,
                    $exact[$storage][$movePath] ?? 0,
                    $descendant[$storage][$movePath] ?? 0
                );
                foreach ($this->ancestorPrefixes($movePath) as $ancestor) {
                    $barrier = max($barrier, $exact[$storage][$ancestor] ?? 0);
                }
            }
            $barriers[(int) $operation->getId()] = $barrier;
        }

        return $barriers;
    }

    /**
     * The strict ancestor prefixes of a storage prefix, deepest first: "a/b/c" gives "a/b", "a".
     *
     * @return list<string>
     */
    private function ancestorPrefixes(string $prefix): array
    {
        $ancestors = [];
        $current = trim($prefix, '/');

        while (($slash = strrpos($current, '/')) !== false) {
            $current = substr($current, 0, $slash);
            $ancestors[] = $current;
        }

        return $ancestors;
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
     * @return StorageOperation[]
     */
    private function orderForProcessing(array $operations, ?array $barriers = null): array
    {
        // Deletes always keep strict FIFO, and a cluster is never drained across a Delete that
        // overlaps it: the later Move could carry content out of the very prefix the Delete was
        // queued to remove, and the dependency check in processDelete() deliberately only
        // considers rows older than the Delete, so it would not cover a Move that jumped ahead
        // of it either. Unrelated Deletes do not split a cluster.
        $barriers ??= $this->moveBarriers($operations);

        $moveClusters = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== StorageOperationType::Move) {
                continue;
            }
            $moveClusters[$this->clusterKey($operation, $barriers)][] = $operation;
        }

        $emittedClusters = [];
        $ordered = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== StorageOperationType::Move) {
                $ordered[] = $operation;

                continue;
            }

            $targetPrefix = $this->clusterKey($operation, $barriers);
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

        // Checked BEFORE the "nothing here" completion below: a Delete can name a path that only
        // exists through a pending Move, in which case the prefix is legitimately empty right now
        // and dropping the row would let the Move recreate the deleted subtree later.
        $blocking = $this->findPendingMoveDependingOn($operation);
        if ($blocking !== null) {
            $this->logDeferredDelete($operation, $blocking);

            return false;
        }

        if (!$adapter->directoryExists($source)) {
            $this->repository->remove((int) $operation->getId());

            return true; // nothing left - idempotent completion
        }

        $entriesSinceCheck = 0;
        foreach ($adapter->listContents($source, true) as $item) {
            if (++$entriesSinceCheck >= self::DEADLINE_CHECK_INTERVAL) {
                $entriesSinceCheck = 0;
                $this->invokeHeartbeat($heartbeat);
                if ($deadline !== null && time() >= $deadline) {
                    return false;
                }
                // The snapshot taken above can age during a long sweep: a producer may queue an
                // overlapping Move meanwhile. Re-read and stop before deleting its content.
                $this->refreshPendingMoves();
                $late = $this->findPendingMoveDependingOn($operation);
                if ($late !== null) {
                    $this->logDeferredDelete($operation, $late);

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
        // The options the original move was resolved with, recorded on the row when it was
        // queued. Without them the adapter falls back to flysystem defaults, which on S3 means
        // reading the source object ACL before every copy.
        $copyConfig = new Config($current->getCopyOptions() ?? []);

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
                    // a live re-move repoints the row and carries its own options along
                    $copyConfig = new Config($current->getCopyOptions() ?? []);
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
                        $adapter->copy($path, $target, $copyConfig);
                    }

                    continue;
                }

                // $lastModified < $cutoff: unambiguously pre-cutoff content
                if (!$adapter->fileExists($target)) {
                    $adapter->copy($path, $target, $copyConfig);
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
                        // The repointed row's own options - this relocation is part of applying it.
                        $adapter->copy($stale, $new, new Config($fresh->getCopyOptions() ?? []));
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
