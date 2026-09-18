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

    /**
     * @param bool $stopOnError end the run at the first failing row instead of isolating it.
     *                          Rows are independent by default, so one unprocessable row must not
     *                          block the rest of the queue - but during a risky window (a large
     *                          migration, say) an operator can ask for a hard stop instead.
     */
    public function process(
        ?int $onlyId = null,
        ?int $maxRuntimeSeconds = null,
        ?Closure $heartbeat = null,
        bool $stopOnError = false
    ): StorageQueueProcessingResult {
        $deadline = $maxRuntimeSeconds !== null ? time() + $maxRuntimeSeconds : null;
        $stoppedOnError = false;
        $processed = 0;
        $failed = 0;
        $timedOut = false;
        $errors = [];
        $clearedAssetMove = false;
        // Deletes that did not complete in this run. A deferred Delete stays an ordering barrier:
        // a later overlapping Move must not carry away the very bytes it was queued to sweep.
        $unfinishedDeletes = [];

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
            $operations = $this->orderForProcessing($this->repository->all());
        }

        foreach ($operations as $operation) {
            if ($deadline !== null && time() >= $deadline) {
                $timedOut = true;

                break;
            }

            $this->invokeHeartbeat($heartbeat); // row boundary

            if ($operation->getType() === StorageOperationType::Move) {
                $blockingDelete = $this->findUnfinishedDeleteBlocking($operation, $unfinishedDeletes);
                if ($blockingDelete !== null) {
                    $this->logSkippedMove($operation, $blockingDelete);

                    continue; // stays queued; the Delete gets its turn first on a later run
                }
            }

            try {
                if ($this->processOperation($operation, $deadline, $heartbeat)) {
                    $processed++;
                    if ($operation->getType() === StorageOperationType::Move && $operation->getStorage() === 'asset') {
                        $clearedAssetMove = true;
                    }
                } else {
                    $this->rememberUnfinishedDelete($operation, $unfinishedDeletes);
                }
                // incomplete rows (deadline hit, undated entries, contested rows) stay queued
                // for the next run - processOperation removes its own row on completion
            } catch (Exception $e) {
                $this->rememberUnfinishedDelete($operation, $unfinishedDeletes);
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
        // Cluster membership, not merely a shared target: an intervening overlapping Delete
        // splits the cluster, and a full run then drains those rows in plain FIFO order. Refusing
        // here on a shared target alone would reject a row a normal run processes first.
        $all = $this->repository->all();
        $clusterKeys = $this->moveClusterKeys($all);
        $cluster = $clusterKeys[(int) $operation->getId()] ?? null;

        $newest = null;
        foreach ($all as $candidate) {
            if ($cluster === null
                || $candidate->getType() !== StorageOperationType::Move
                || (int) $candidate->getId() <= (int) $operation->getId()
                || ($clusterKeys[(int) $candidate->getId()] ?? null) !== $cluster
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
        return $this->repository->findOverlappingMoveOlderThan(
            $delete->getStorage(),
            $delete->getSourcePrefix(),
            (int) $delete->getId()
        );
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
     * Re-reads the pending Move rows and re-applies the blocker check.
     *
     * Residual window, accepted deliberately: a producer allocates its row id inside an open
     * transaction and the row only becomes readable on commit, so no number of re-reads can rule
     * out a lower-id Move appearing between the last read and the next delete() call. Closing it
     * entirely would mean holding mutual exclusion against every asset save for the duration of a
     * sweep, which is a far worse trade than the narrow window that remains. The checks here bound
     * that window to a single storage call rather than to a whole listing.
     */
    private function findLateBlockingMove(StorageOperation $delete): ?StorageOperation
    {
        return $this->findPendingMoveDependingOn($delete);
    }

    /**
     * Records a row that did not complete, when it is (or has just become) a Delete.
     *
     * Re-read rather than trusting the row we started with: live traffic converts a Move whose
     * target was deleted into a Delete mid-drain, and the drain then reports "not completed"
     * while still holding the original Move. Treating that as a Move would let a later
     * overlapping Move relocate the very bytes the converted Delete now owns.
     *
     * @param list<StorageOperation> $unfinishedDeletes
     */
    private function rememberUnfinishedDelete(StorageOperation $operation, array &$unfinishedDeletes): void
    {
        $current = $this->repository->findById((int) $operation->getId());
        if ($current !== null && $current->getType() === StorageOperationType::Delete) {
            $unfinishedDeletes[] = $current;
        }
    }

    /**
     * The run-loop counterpart to findPendingMoveDependingOn(): that one holds a Delete back while
     * an older Move still needs its content, this one holds a Move back while an older Delete that
     * could not run yet still has a claim on the same content.
     *
     * Without it a deferred Delete stops being a barrier. Its later overlapping Moves keep their
     * FIFO turn, relocate the bytes the Delete was queued to sweep, and the Delete then completes
     * against an empty source on a later run - leaving explicitly deleted content alive under the
     * move target. Only Deletes queued BEFORE the Move qualify; a Delete queued afterwards is the
     * Move's successor in FIFO order and has no claim on what the Move relocates first.
     *
     * @param list<StorageOperation> $unfinishedDeletes
     */
    private function findUnfinishedDeleteBlocking(StorageOperation $move, array $unfinishedDeletes): ?StorageOperation
    {
        foreach ($unfinishedDeletes as $delete) {
            if ($delete->getStorage() === $move->getStorage()
                && (int) $delete->getId() < (int) $move->getId()
                && $this->deleteOverlapsMove($delete, $move)
            ) {
                return $delete;
            }
        }

        return null;
    }

    private function logSkippedMove(StorageOperation $move, StorageOperation $blocking): void
    {
        $this->logger->info(
            'Storage queue move skipped - an older delete on an overlapping prefix has not run yet',
            [
                'move' => $move->getId(),
                'storage' => $move->getStorage(),
                'moveSource' => $move->getSourcePrefix(),
                'moveTarget' => $move->getTargetPrefix(),
                'blockedBy' => $blocking->getId(),
                'deletePrefix' => $blocking->getSourcePrefix(),
            ]
        );
    }

    /**
     * Pending Delete rows overlapping the Move, on its storage.
     *
     * Deliberately not bounded by the Move's id. A producer allocates its row id inside an open
     * transaction, so a Delete that commits mid-drain can carry a LOWER id than the Move and
     * would be invisible to an id-bounded lookup for the rest of the run - long enough for the
     * Move to relocate the bytes out of the deleted prefix. Rows that were already visible when
     * the run was ordered never reach this point anyway: the run loop skips a Move that an
     * unfinished older Delete overlaps.
     *
     * Targeted rather than a scan of the whole queue: this is re-read at every drain checkpoint,
     * so hydrating every row here would make a run cost O(moves x queue size).
     *
     * Residual window, the same one the Delete side carries: a producer allocates its row id
     * inside an open transaction and the row only becomes readable on commit, so a tombstone can
     * still appear between the last read and the next copy. Entries already materialised at that
     * point keep their fresh modification time and the Delete will spare them. Closing it
     * entirely would mean holding mutual exclusion against every asset save for the length of a
     * drain; the re-reads bound the window to a single storage call instead.
     *
     * @return StorageOperation[]
     */
    private function findPendingDeletes(StorageOperation $move): array
    {
        return $this->repository->findPendingDeletesOverlapping(
            $move->getStorage(),
            $move->getSourcePrefix(),
            (string) $move->getTargetPrefix()
        );
    }

    /**
     * Groups Delete rows by their prefix, so a lookup for a path walks that path and its
     * ancestors instead of scanning every Delete for every file.
     *
     * @param StorageOperation[] $deletes
     *
     * @return array<string, list<StorageOperation>>
     */
    private function indexDeletesByPrefix(array $deletes): array
    {
        $index = [];
        foreach ($deletes as $delete) {
            $index[trim($delete->getSourcePrefix(), '/')][] = $delete;
        }

        return $index;
    }

    /**
     * The first Delete naming $path or any of its ancestors that $accept approves.
     *
     * @param array<string, list<StorageOperation>> $index
     */
    private function findDeleteCovering(array $index, string $path, callable $accept): ?StorageOperation
    {
        $path = trim($path, '/');
        foreach ([$path, ...$this->ancestorPrefixes($path)] as $candidate) {
            foreach ($index[$candidate] ?? [] as $delete) {
                if ($accept($delete)) {
                    return $delete;
                }
            }
        }

        return null;
    }

    /**
     * Whether the Delete represents a user action that came after the Move.
     *
     * Queue ids cannot answer this on their own: a producer allocates its id inside an open
     * transaction, so a row that commits later can still carry a lower id. The recorded creation
     * time is the honest record of what the user did second, with the id only breaking ties
     * within the same second.
     */
    private function isQueuedAfter(StorageOperation $delete, StorageOperation $move): bool
    {
        $deleteAt = $delete->getCreatedAt()->getTimestamp();
        $moveAt = $move->getCreatedAt()->getTimestamp();

        if ($deleteAt !== $moveAt) {
            return $deleteAt > $moveAt;
        }

        return (int) $delete->getId() > (int) $move->getId();
    }

    private function logMoveDeferredForDelete(
        StorageOperation $move,
        StorageOperation $delete,
        string $path
    ): void {
        $this->logger->info(
            'Storage queue move deferred - an older delete covers this content and must run first',
            [
                'move' => $move->getId(),
                'storage' => $move->getStorage(),
                'source' => $path,
                'blockedBy' => $delete->getId(),
                'deletePrefix' => $delete->getSourcePrefix(),
            ]
        );
    }

    /**
     * The Delete that already tombstoned $target, if the entry predates it.
     *
     * @param array<string, list<StorageOperation>> $deleteIndex
     */
    private function findTombstoneCovering(
        array $deleteIndex,
        StorageOperation $move,
        string $target,
        int $lastModified
    ): ?StorageOperation {
        return $this->findDeleteCovering(
            $deleteIndex,
            $target,
            fn (StorageOperation $delete): bool =>
                // Only a Delete the user issued AFTER the move tombstones its target. An older one
                // refers to whatever stood there before, and FIFO has the move repopulate the path
                // afterwards.
                $this->isQueuedAfter($delete, $move)
                // The Delete's own cutoff rule decides the rest: content older than the tombstone
                // is what the user deleted, content written afterwards is namespace reuse.
                && $lastModified < $delete->getCreatedAt()->getTimestamp()
        );
    }

    private function logTombstonedMoveEntry(
        StorageOperation $move,
        StorageOperation $tombstone,
        string $path,
        string $target
    ): void {
        $this->logger->info(
            'Storage queue move entry dropped - its target was deleted after the move was queued',
            [
                'move' => $move->getId(),
                'storage' => $move->getStorage(),
                'source' => $path,
                'target' => $target,
                'tombstonedBy' => $tombstone->getId(),
                'deletePrefix' => $tombstone->getSourcePrefix(),
            ]
        );
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
     *
     * @return StorageOperation[]
     */
    /**
     * Cluster identity per Move row, for the newest-first drain: same storage, same target
     * prefix, and no overlapping Delete queued between one member and the next.
     *
     * Reversing a cluster moves its later members ahead of everything between them, so a Delete
     * in that gap would suddenly see different content. A Delete that precedes every member of
     * the cluster is not in any such gap and must NOT split it: doing so would drain the older
     * row first, let it claim the shared target, and leave the newer row's fresher content to be
     * discarded by literal-wins - the very data loss the newest-first drain exists to prevent.
     *
     * @param StorageOperation[] $operations
     *
     * @return array<int, string>
     */
    private function moveClusterKeys(array $operations): array
    {
        $barriers = $this->moveBarriers($operations);

        $keys = [];
        $segment = [];
        $previousMemberId = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== StorageOperationType::Move) {
                continue;
            }

            $group = $operation->getStorage() . "\0" . (string) $operation->getTargetPrefix();
            $id = (int) $operation->getId();

            if (!isset($segment[$group])) {
                $segment[$group] = 0;
            } elseif (($barriers[$id] ?? 0) > $previousMemberId[$group]) {
                $segment[$group]++; // an overlapping Delete sits between the two members
            }

            $keys[$id] = $segment[$group] . "\0" . $group;
            $previousMemberId[$group] = $id;
        }

        return $keys;
    }

    /**
     * For each Move row, the id of the most recent Delete queued before it that overlaps the
     * Move (0 when there is none). Used by moveClusterKeys() to tell whether such a Delete sits
     * between two same-target rows.
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

    private function orderForProcessing(array $operations): array
    {
        // Deletes always keep strict FIFO, and a cluster is never drained across a Delete that
        // overlaps it: the later Move could carry content out of the very prefix the Delete was
        // queued to remove, and the dependency check in processDelete() deliberately only
        // considers rows older than the Delete, so it would not cover a Move that jumped ahead
        // of it either. Unrelated Deletes do not split a cluster.
        $clusterKeys = $this->moveClusterKeys($operations);

        $moveClusters = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== StorageOperationType::Move) {
                continue;
            }
            $moveClusters[$clusterKeys[(int) $operation->getId()]][] = $operation;
        }

        $emittedClusters = [];
        $ordered = [];
        foreach ($operations as $operation) {
            if ($operation->getType() !== StorageOperationType::Move) {
                $ordered[] = $operation;

                continue;
            }

            $targetPrefix = $clusterKeys[(int) $operation->getId()];
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
            // Re-read rather than trusting the check above: it may have been answered before an
            // even older Move became visible or was repointed onto this prefix, and dropping the
            // row now would let that Move materialise a subtree whose Delete no longer exists.
            if (($late = $this->findLateBlockingMove($operation)) !== null) {
                $this->logDeferredDelete($operation, $late);

                return false;
            }

            $this->repository->remove((int) $operation->getId());

            return true; // nothing left - idempotent completion
        }

        $entriesSinceCheck = 0;
        $recheckedBeforeFirstDelete = false;
        foreach ($adapter->listContents($source, true) as $item) {
            if (++$entriesSinceCheck >= self::DEADLINE_CHECK_INTERVAL) {
                $entriesSinceCheck = 0;
                $this->invokeHeartbeat($heartbeat);
                if ($deadline !== null && time() >= $deadline) {
                    return false;
                }
                // The snapshot taken above can age during a long sweep: a producer may queue an
                // overlapping Move meanwhile. Re-read and stop before deleting its content.
                $late = $this->findLateBlockingMove($operation);
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

            if (!$recheckedBeforeFirstDelete) {
                // The initial check sits BEFORE listContents(), so on object storage the entire
                // listing round trip falls inside the window - and a prefix holding fewer entries
                // than the interval never reaches the periodic re-check at all. One more read
                // immediately before the first destructive call covers both cases.
                $recheckedBeforeFirstDelete = true;
                $late = $this->findLateBlockingMove($operation);
                if ($late !== null) {
                    $this->logDeferredDelete($operation, $late);

                    return false;
                }
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

        // Same reasoning as the empty-prefix completion above: the row must not be dropped
        // while an older Move that overlaps it has become visible during the sweep.
        if (($late = $this->findLateBlockingMove($operation)) !== null) {
            $this->logDeferredDelete($operation, $late);

            return false;
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
        // Pending Deletes that overlap this move, re-read at the drain checkpoints below and
        // kept as a prefix index so the per-entry lookups cost one step per path segment rather
        // than one comparison per Delete.
        $deleteIndex = $this->indexDeletesByPrefix($this->findPendingDeletes($current));
        $recheckedBeforeFirstMaterialisation = false;

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
                    $deleteIndex = $this->indexDeletesByPrefix($this->findPendingDeletes($current));
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

                if (!$recheckedBeforeFirstMaterialisation) {
                    // The snapshot above is taken before any listing work, so on object storage a
                    // whole listing round trip sits inside the window - and a prefix smaller than
                    // the check interval never reaches the checkpoint above at all. Re-read once
                    // before the first decision so a Delete committed meanwhile is seen.
                    $recheckedBeforeFirstMaterialisation = true;
                    $deleteIndex = $this->indexDeletesByPrefix($this->findPendingDeletes($current));
                }

                // A Delete queued BEFORE this move, covering the entry's own source, has to run
                // first: FIFO says the bytes are swept and the move finds nothing. Copying now
                // would carry them to the target and leave that Delete to complete against an
                // empty source, so the move yields its turn and is retried once the Delete ran.
                $blockingSourceDelete = $this->findDeleteCovering(
                    $deleteIndex,
                    $path,
                    fn (StorageOperation $delete): bool => !$this->isQueuedAfter($delete, $current)
                );
                if ($blockingSourceDelete !== null) {
                    $this->logMoveDeferredForDelete($current, $blockingSourceDelete, $path);

                    return false;
                }

                // Checked BEFORE the equality branch below: that branch copies, and a copy under
                // an already deleted target gets a fresh modification time the Delete would then
                // read as namespace reuse.
                $tombstoned = $this->findTombstoneCovering($deleteIndex, $current, $target, $lastModified);

                if ($lastModified === $cutoff) {
                    // Exact boundary: a same-second write cannot be told apart from content that
                    // legitimately predates the row. Copy it to the target so it is reachable
                    // there too, but never delete the source and never track it in $copied - it
                    // must not be swept, re-targeted on a repoint, or block completion (the
                    // completion re-list already treats equality as non-blocking). Under a
                    // tombstoned target the source is still preserved, but nothing is
                    // materialised: ambiguity must not resurrect a deleted path either.
                    if ($tombstoned === null && !$adapter->fileExists($target)) {
                        $adapter->copy($path, $target, new Config());
                    }

                    continue;
                }

                // $lastModified < $cutoff: unambiguously pre-cutoff content
                if ($tombstoned !== null) {
                    // The user deleted this path after queueing the move, so its bytes are only
                    // still here because the move had not run yet. Materialising them at the
                    // target would stamp a fresh modification time on them, and that Delete would
                    // then read them as post-cutoff namespace reuse and spare them for good.
                    // Drop them at the source instead - which is what the two operations mean
                    // together - and let the Delete complete against a prefix that never appears.
                    $this->logTombstonedMoveEntry($current, $tombstoned, $path, $target);
                    $adapter->delete($path);

                    continue;
                }

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
