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

use Exception;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
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

    public function __construct(
        private readonly ContainerInterface $innerAdapters,
        private readonly StorageOperationQueueRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(?int $onlyId = null, ?int $maxRuntimeSeconds = null): StorageQueueProcessingResult
    {
        $deadline = $maxRuntimeSeconds !== null ? time() + $maxRuntimeSeconds : null;
        $processed = 0;
        $failed = 0;
        $timedOut = false;
        $errors = [];

        $operations = $onlyId !== null
            ? array_filter([$this->repository->findById($onlyId)])
            : $this->repository->all();

        foreach ($operations as $operation) {
            if ($deadline !== null && time() >= $deadline) {
                $timedOut = true;

                break;
            }

            try {
                if ($this->processOperation($operation, $deadline)) {
                    $this->repository->remove((int) $operation->getId());
                    $processed++;
                }
                // incomplete rows (deadline hit, undated entries) stay queued for the next run
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

        return new StorageQueueProcessingResult(
            $processed,
            $failed,
            count($this->repository->all()),
            $timedOut,
            $errors,
        );
    }

    /**
     * @return bool true when the operation is fully applied and its row can be removed
     */
    private function processOperation(StorageOperation $operation, ?int $deadline): bool
    {
        /** @var FilesystemAdapter $adapter */
        $adapter = $this->innerAdapters->get($operation->getStorage());
        $cutoff = $operation->getCreatedAt()->getTimestamp();
        $source = $operation->getSourcePrefix();

        if (!$adapter->directoryExists($source)) {
            return true; // nothing left - idempotent completion
        }

        $entriesSinceCheck = 0;
        foreach ($adapter->listContents($source, true) as $item) {
            if ($deadline !== null && ++$entriesSinceCheck >= self::DEADLINE_CHECK_INTERVAL) {
                $entriesSinceCheck = 0;
                if (time() >= $deadline) {
                    return false;
                }
            }
            if (!$item->isFile()) {
                continue;
            }

            $path = $item->path();
            $lastModified = $item->lastModified() ?? $adapter->lastModified($path)->lastModified();
            if ($lastModified === null || $lastModified > $cutoff) {
                continue; // undated (never destructive) or namespace-reuse content
            }

            if ($operation->getType() === StorageOperationType::Move) {
                $target = $operation->getTargetPrefix() . mb_substr($path, mb_strlen($source));
                if (!$adapter->fileExists($target)) {
                    $adapter->copy($path, $target, new Config());
                    if (!$adapter->fileExists($target)) {
                        throw new RuntimeException(sprintf('Copy verification failed for %s -> %s', $path, $target));
                    }
                }
                // existing target key: literal wins - never overwrite; the source entry is superseded
            }

            $adapter->delete($path);
        }

        // completion check on a fresh listing
        $filesRemain = false;
        foreach ($adapter->listContents($source, true) as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $lastModified = $item->lastModified() ?? $adapter->lastModified($item->path())->lastModified();
            if ($lastModified === null || $lastModified <= $cutoff) {
                return false; // undated or still-pending entries keep the row alive
            }
            $filesRemain = true;
        }

        if (!$filesRemain) {
            $adapter->deleteDirectory($source); // cleanup (empty dirs on local backends)
        }

        return true;
    }
}
