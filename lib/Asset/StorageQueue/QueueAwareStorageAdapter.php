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

use DateTimeImmutable;
use DateTimeInterface;
use League\Flysystem\CalculateChecksumFromStream;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

/**
 * Wraps a storage adapter so that pending storage operations (folder moves/deletes recorded in
 * the asset storage operation queue) stay transparent: reads resolve to the current physical
 * location, writes always target the literal key, and prefix moves/deletes become queue rows
 * instead of physical per-object work.
 *
 * @internal
 */
final class QueueAwareStorageAdapter implements FilesystemAdapter, PublicUrlGenerator, TemporaryUrlGenerator, ChecksumProvider
{
    use CalculateChecksumFromStream;

    public function __construct(
        private readonly FilesystemAdapter $inner,
        private readonly StorageOperationQueueRepositoryInterface $repository,
        private readonly string $storageName,
        private readonly bool $regeneratesOnMove = false,
    ) {
    }

    public function fileExists(string $path): bool
    {
        return $this->inner->fileExists($this->resolveFilePath($path));
    }

    public function directoryExists(string $path): bool
    {
        return $this->inner->directoryExists($this->resolveDirectoryPath($path));
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->inner->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->inner->writeStream($path, $contents, $config);
    }

    // M3: resolveFilePath() picks a candidate path (literal or mapped) that exists at the time
    // of the check, but the processor run can drain that exact candidate between this check and
    // the read/readStream call below - an accepted, transient TOCTOU. It is never destructive
    // (the processor only removes a source once its copy at the target is verified) and a
    // retried read succeeds via the literal target once the row is fully applied.
    public function read(string $path): string
    {
        return $this->inner->read($this->resolveFilePath($path));
    }

    public function readStream(string $path)
    {
        return $this->inner->readStream($this->resolveFilePath($path));
    }

    public function delete(string $path): void
    {
        if (!$this->repository->hasOperations($this->storageName)) {
            $this->inner->delete($path);

            return;
        }

        if ($this->inner->fileExists($path)) {
            $this->inner->delete($path);
        }

        // A stale mapped candidate left in place would resurrect once the literal is gone.
        foreach ($this->repository->findCovering($this->storageName, $path) as $operation) {
            $candidate = $this->mapToSource($path, $operation);
            if ($this->inner->fileExists($candidate)) {
                $this->inner->delete($candidate);
            }
        }
    }

    public function deleteDirectory(string $path): void
    {
        $hasContent = $this->inner->directoryExists($path)
            || ($this->repository->hasOperations($this->storageName)
                && $this->inner->directoryExists($this->resolveDirectoryPath($path)));

        if (!$hasContent) {
            $this->inner->deleteDirectory($path); // contract-preserving no-op

            return;
        }

        // Always deferred when the feature is enabled - there is no "native failed" signal
        // for deletes, and deferring a local delete until the processor run is harmless.
        $this->tombstone($path);
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
        return $this->inner->visibility($this->resolveFilePath($path));
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->inner->mimeType($this->resolveFilePath($path));
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->inner->lastModified($this->resolveFilePath($path));
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->inner->fileSize($this->resolveFilePath($path));
    }

    public function listContents(string $path, bool $deep): iterable
    {
        if (!$this->repository->hasOperations($this->storageName)) {
            yield from $this->inner->listContents($path, $deep);

            return;
        }

        $seenLogicalPaths = [];
        foreach ($this->inner->listContents($path, $deep) as $item) {
            $seenLogicalPaths[$item->path()] = true;
            yield $item;
        }

        foreach ($this->repository->findCovering($this->storageName, $path) as $operation) {
            $physicalPrefix = $this->mapToSource($path, $operation);
            if (!$this->inner->directoryExists($physicalPrefix)) {
                continue;
            }
            foreach ($this->inner->listContents($physicalPrefix, $deep) as $item) {
                $logicalPath = (string) $operation->getTargetPrefix()
                    . mb_substr($item->path(), mb_strlen($operation->getSourcePrefix()));
                if (isset($seenLogicalPaths[$logicalPath])) {
                    continue; // literal wins
                }
                $seenLogicalPaths[$logicalPath] = true;
                yield $item->withPath($logicalPath);
            }
        }

        // Ancestor synthesis: a pending move's target may be a descendant of the listed path
        // even though nothing physically exists there yet (e.g. A -> Archive/A, listing
        // 'Archive'). Rows whose target equals $path exactly are already surfaced by the
        // covering-merge above and are skipped here.
        foreach ($this->repository->findWithTargetUnder($this->storageName, $path) as $operation) {
            $target = (string) $operation->getTargetPrefix();
            if ($target === $path) {
                continue;
            }

            $relative = $path === '' ? $target : mb_substr($target, mb_strlen($path) + 1);
            $segments = explode('/', $relative);

            if ($deep) {
                $sourcePrefix = $operation->getSourcePrefix();
                if ($this->inner->directoryExists($sourcePrefix)) {
                    foreach ($this->inner->listContents($sourcePrefix, true) as $item) {
                        $logicalPath = $target . mb_substr($item->path(), mb_strlen($sourcePrefix));
                        if (isset($seenLogicalPaths[$logicalPath])) {
                            continue; // literal wins
                        }
                        $seenLogicalPaths[$logicalPath] = true;
                        yield $item->withPath($logicalPath);
                    }
                }

                $ancestor = $path;
                foreach ($segments as $segment) {
                    $ancestor = $ancestor === '' ? $segment : $ancestor . '/' . $segment;
                    if (isset($seenLogicalPaths[$ancestor])) {
                        continue; // literal wins
                    }
                    $seenLogicalPaths[$ancestor] = true;
                    yield new DirectoryAttributes($ancestor);
                }
            } else {
                $firstSegment = $path === '' ? $segments[0] : $path . '/' . $segments[0];
                if (!isset($seenLogicalPaths[$firstSegment])) {
                    $seenLogicalPaths[$firstSegment] = true;
                    yield new DirectoryAttributes($firstSegment);
                }
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $resolvedSource = $this->resolveFilePath($source);
        if ($this->inner->fileExists($resolvedSource)) {
            // single file: normal move, source possibly at its legacy location
            $this->inner->move($resolvedSource, $destination, $config);
            $this->cleanupOtherCandidates($source, $resolvedSource);

            return;
        }

        // prefix (directory) move
        $hasOperations = $this->repository->hasOperations($this->storageName);
        $literalDirectoryExists = $this->inner->directoryExists($source);
        // Only resolve via the queue when the literal doesn't already exist; resolveDirectoryPath
        // returns the input unchanged when nothing covers it, so a change in value is itself
        // proof that a covering candidate physically exists - no second directoryExists() needed.
        $resolvedDirectorySource = $literalDirectoryExists || !$hasOperations
            ? $source
            : $this->resolveDirectoryPath($source);
        $directoryExists = $literalDirectoryExists || $resolvedDirectorySource !== $source;

        if (!$directoryExists) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }

        $movedNatively = false;
        if ($literalDirectoryExists) {
            try {
                $this->inner->move($source, $destination, $config);
                $movedNatively = true;
            } catch (UnableToMoveFile) {
                // backend cannot rename directories - fall through to queueing
            }
        }

        if ($movedNatively) {
            if ($hasOperations) {
                // Legacy rows still pointing at this prefix must follow along so lookups stay
                // flat; a genuinely empty destination would drop to a self-mapping and vanish.
                $this->repository->repointMoves($this->storageName, $source, $destination);
            }

            return; // native rename moved everything physically - never insert a row
        }

        if ($this->regeneratesOnMove) {
            // Derived, regenerable content (thumbnails, asset_cache): don't translate reads for
            // the pending window at all - tombstone the source prefix so the processor sweeps
            // the stale renditions, and let fresh ones regenerate on demand at their new,
            // literal paths through the standard deferred-thumbnail mechanism.
            $this->tombstone($source);

            return;
        }

        // Queue the operation: the backend could not (or did not attempt to) move the prefix
        // physically, so reads/writes must be translated until the processor catches up.
        $this->repository->add(new StorageOperation(
            null,
            $this->storageName,
            StorageOperationType::Move,
            $source,
            $destination,
            new DateTimeImmutable(),
        ));
    }

    /**
     * Enqueues a Delete tombstone for $prefix on this storage.
     */
    private function tombstone(string $prefix): void
    {
        $this->repository->add(new StorageOperation(
            null,
            $this->storageName,
            StorageOperationType::Delete,
            $prefix,
            null,
            new DateTimeImmutable(),
        ));
    }

    /**
     * After a single-file move has vacated $logicalPath, remove any other physical objects that
     * would still resolve to it (a shadowed literal, or another covering move's candidate) so the
     * vacated logical path doesn't resurrect stale content.
     */
    private function cleanupOtherCandidates(string $logicalPath, string $resolvedPath): void
    {
        if (!$this->repository->hasOperations($this->storageName)) {
            return;
        }

        if ($resolvedPath !== $logicalPath && $this->inner->fileExists($logicalPath)) {
            $this->inner->delete($logicalPath);
        }

        foreach ($this->repository->findCovering($this->storageName, $logicalPath) as $operation) {
            $candidate = $this->mapToSource($logicalPath, $operation);
            if ($candidate !== $resolvedPath && $this->inner->fileExists($candidate)) {
                $this->inner->delete($candidate);
            }
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->inner->copy($this->resolveFilePath($source), $destination, $config);
    }

    public function publicUrl(string $path, Config $config): string
    {
        if (!$this->inner instanceof PublicUrlGenerator) {
            // mirror what League\Flysystem\Filesystem does when no generator is configured
            throw UnableToGeneratePublicUrl::noGeneratorConfigured($path);
        }

        return $this->inner->publicUrl($this->resolveFilePath($path), $config);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        if (!$this->inner instanceof TemporaryUrlGenerator) {
            // mirror League\Flysystem\Filesystem::temporaryUrl's no-generator throw
            throw UnableToGenerateTemporaryUrl::noGeneratorConfigured($path);
        }

        return $this->inner->temporaryUrl($this->resolveFilePath($path), $expiresAt, $config);
    }

    public function checksum(string $path, Config $config): string
    {
        if (!$this->inner instanceof ChecksumProvider) {
            // mirror League\Flysystem\Filesystem::checksum's fallback when the adapter doesn't
            // implement ChecksumProvider: hash the stream ourselves instead of throwing.
            return $this->calculateChecksumFromStream($this->resolveFilePath($path), $config);
        }

        return $this->inner->checksum($this->resolveFilePath($path), $config);
    }

    /**
     * Resolves a logical file path to its current physical location: the literal path wins if it
     * physically exists (a fresh write always shadows a stale legacy object), otherwise the
     * pending move operations covering this path are tried, most specific target first, mapping
     * back to the operation's source prefix.
     */
    private function resolveFilePath(string $path): string
    {
        if (!$this->repository->hasOperations($this->storageName)) {
            return $path;
        }
        if ($this->inner->fileExists($path)) {
            return $path;
        }
        foreach ($this->repository->findCovering($this->storageName, $path) as $operation) {
            $candidate = $this->mapToSource($path, $operation);
            if ($this->inner->fileExists($candidate)) {
                return $candidate;
            }
        }

        return $path;
    }

    private function resolveDirectoryPath(string $path): string
    {
        if (!$this->repository->hasOperations($this->storageName)) {
            return $path;
        }
        if ($this->inner->directoryExists($path)) {
            return $path;
        }
        foreach ($this->repository->findCovering($this->storageName, $path) as $operation) {
            $candidate = $this->mapToSource($path, $operation);
            if ($this->inner->directoryExists($candidate)) {
                return $candidate;
            }
        }

        return $path;
    }

    private function mapToSource(string $logicalPath, StorageOperation $operation): string
    {
        return $operation->getSourcePrefix() . mb_substr($logicalPath, mb_strlen((string) $operation->getTargetPrefix()));
    }
}
