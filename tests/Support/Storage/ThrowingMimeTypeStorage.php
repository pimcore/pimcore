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

namespace Pimcore\Tests\Support\Storage;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;

/**
 * FilesystemOperator decorator that throws on mimeType() to simulate a storage
 * adapter (e.g. S3, GCS) where MIME detection is unavailable.
 * All other operations are delegated to the wrapped real storage.
 *
 * @internal test support only
 */
final class ThrowingMimeTypeStorage implements FilesystemOperator
{
    public function __construct(private readonly FilesystemOperator $inner)
    {
    }

    public function mimeType(string $path): string
    {
        throw UnableToRetrieveMetadata::mimeType($path, 'Simulated mimeType failure for tests');
    }

    public function fileExists(string $location): bool
    {
        return $this->inner->fileExists($location);
    }

    public function directoryExists(string $location): bool
    {
        return $this->inner->directoryExists($location);
    }

    public function has(string $location): bool
    {
        return $this->inner->has($location);
    }

    public function read(string $location): string
    {
        return $this->inner->read($location);
    }

    public function readStream(string $location)
    {
        return $this->inner->readStream($location);
    }

    /**
     * @return DirectoryListing<StorageAttributes>
     */
    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        return $this->inner->listContents($location, $deep);
    }

    public function lastModified(string $path): int
    {
        return $this->inner->lastModified($path);
    }

    public function fileSize(string $path): int
    {
        return $this->inner->fileSize($path);
    }

    public function visibility(string $path): string
    {
        return $this->inner->visibility($path);
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->inner->write($location, $contents, $config);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $this->inner->writeStream($location, $contents, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->inner->setVisibility($path, $visibility);
    }

    public function delete(string $location): void
    {
        $this->inner->delete($location);
    }

    public function deleteDirectory(string $location): void
    {
        $this->inner->deleteDirectory($location);
    }

    public function createDirectory(string $location, array $config = []): void
    {
        $this->inner->createDirectory($location, $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->inner->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->inner->copy($source, $destination, $config);
    }
}
