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

use DateTimeInterface;
use League\Flysystem\CalculateChecksumFromStream;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
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
        $this->inner->delete($this->resolveFilePath($path));
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
        return $this->inner->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->inner->move($this->resolveFilePath($source), $destination, $config);
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
