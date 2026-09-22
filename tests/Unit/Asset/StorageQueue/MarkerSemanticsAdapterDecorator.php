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

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToMoveFile;

/**
 * Decorates a real adapter but simulates an S3-compatible backend that materializes an
 * explicitly created directory as a zero-byte object at the BARE key (no trailing slash)
 * and lets a single-object copy/move of that key succeed. Observed in the wild on such
 * gateways (e.g. Upsun blob-storage, s3fs-created markers): moving a directory path
 * "succeeds" by relocating only the marker object while every child stays at the old
 * prefix. A directory/prefix move without a marker throws, like any object storage.
 */
final class MarkerSemanticsAdapterDecorator implements FilesystemAdapter
{
    /**
     * @var array<string, true>
     */
    private array $markers = [];

    public function __construct(private readonly FilesystemAdapter $inner)
    {
    }

    public function addMarker(string $path): void
    {
        $this->markers[trim($path, '/')] = true;
    }

    public function hasMarker(string $path): bool
    {
        return isset($this->markers[trim($path, '/')]);
    }

    public function fileExists(string $path): bool
    {
        return $this->hasMarker($path) || $this->inner->fileExists($path);
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
        unset($this->markers[trim($path, '/')]);
        if ($this->inner->fileExists($path)) {
            $this->inner->delete($path);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $this->inner->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        // this is the backend divergence: an explicit createDirectory() materializes a
        // plain object at the bare key
        $this->addMarker($path);
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
        if ($this->hasMarker($source)) {
            // the spurious "success": the marker object moves, the subtree does not
            unset($this->markers[trim($source, '/')]);
            $this->addMarker($destination);

            return;
        }

        if (!$this->inner->fileExists($source)) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }

        $this->inner->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        if ($this->hasMarker($source)) {
            $this->addMarker($destination);

            return;
        }

        $this->inner->copy($source, $destination, $config);
    }
}
