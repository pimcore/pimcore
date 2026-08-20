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

/**
 * Best-effort translation of logical asset paths to their current physical location, for
 * prefix-based frontend URLs that point straight at the storage (CDN/bucket). While a covering
 * move row is pending, the bytes still live under the pre-move prefix, so a URL built from the
 * logical path would miss. Reads through QueueAwareStorageAdapter are handled by that adapter —
 * this resolver only affects URL generation and performs no storage I/O (most specific covering row
 * wins, without an existence check).
 *
 * @internal
 */
final class FrontendPathResolver
{
    public function __construct(
        private readonly StorageOperationQueueRepositoryInterface $repository,
        private readonly bool $enabled,
    ) {
    }

    /**
     * @param string $logicalPath full logical asset path including the leading slash
     * @param int|null $modificationTimestamp the asset's modificationDate; when it is later than
     *                                        the covering row's creation the asset was written
     *                                        during the pending window - writes always target
     *                                        literal keys, so the logical path is the valid one
     */
    public function resolvePhysicalPath(string $logicalPath, ?int $modificationTimestamp = null): string
    {
        if (!$this->enabled) {
            return $logicalPath;
        }

        $storagePath = ltrim($logicalPath, '/');
        if ($storagePath === '' || !$this->repository->hasOperations('asset')) {
            return $logicalPath;
        }

        $covering = $this->repository->findCovering('asset', $storagePath);
        if ($covering === []) {
            return $logicalPath;
        }

        $row = $covering[0];
        if ($modificationTimestamp !== null && $modificationTimestamp > $row->getCreatedAt()->getTimestamp()) {
            return $logicalPath; // written during the window - lives at its literal key
        }

        $target = $row->getTargetPrefix();
        if ($storagePath === $target) {
            return '/' . $row->getSourcePrefix();
        }

        return '/' . $row->getSourcePrefix() . mb_substr($storagePath, mb_strlen($target));
    }
}
