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

/**
 * Reports one entry without a modification time, both in listings and on direct lookup - a
 * backend whose listing metadata is unusable for that object. The processor treats such an entry
 * as undated and never destructive, which leaves a move over it incomplete without any error.
 */
final class UndatedEntryAdapterDecorator implements FilesystemAdapter
{
    public function __construct(
        private readonly FilesystemAdapter $inner,
        private readonly string $undatedPath,
    ) {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->inner->copy($source, $destination, $config);
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
        if ($path === $this->undatedPath) {
            return new FileAttributes($path);
        }

        return $this->inner->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->inner->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        foreach ($this->inner->listContents($path, $deep) as $item) {
            yield $item->path() === $this->undatedPath && $item instanceof FileAttributes
                ? new FileAttributes($item->path(), $item->fileSize(), $item->visibility(), null, $item->mimeType())
                : $item;
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->inner->move($source, $destination, $config);
    }
}
