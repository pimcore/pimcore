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

namespace Pimcore\Model\Version\Adapter;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Model\Version;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
class FileSystemVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected FilesystemOperator $storage;

    public function __construct()
    {
        $this->storage = Storage::get('version');
    }

    public function loadMetaData(Version $version): ?string
    {
        try {
            $data = $this->storage->read($this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype()));
        } catch (UnableToReadFile $e) {
            $data = null;
        }

        return $data;
    }

    public function loadBinaryData(Version $version): mixed
    {
        $binaryStoragePath = $this->getBinaryStoragePath($version);

        if ($this->storage->fileExists($binaryStoragePath)) {
            return $this->getBinaryFileStream($version);
        }

        return null;
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->storage->readStream($this->getBinaryStoragePath($version));
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->storage->readStream($this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype()));
    }

    public function getStorageFilename(int $id,
        int $cId,
        string $cType): string
    {
        $group = floor($cId / 10000) * 10000;

        return $cType . '/g' . $group . '/' . $cId . '/' . $id;
    }

    public function getBinaryStoragePath(Version $version): string
    {
        $binaryFileId = $version->getBinaryFileId() ?? $version->getId();

        return $this->getStorageFilename($binaryFileId, $version->getCid(), $version->getCtype()) . '.bin';
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->storage->write($this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype()), $metaData);
        $binaryStoragePath = $this->getBinaryStoragePath($version);

        // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
        if (isset($binaryDataStream) === true &&
            !$this->storage->fileExists($binaryStoragePath)) {
            $linked = false;

            // we always try to create a hardlink onto the original file, the asset ensures that not the actual
            // inodes get overwritten but creates new inodes if the content changes. This is done by deleting the
            // old file first before opening a new stream -> see Asset::update()
            $useHardlinks = Config::getSystemConfiguration('assets')['versions']['use_hardlinks'];
            $this->storage->write($binaryStoragePath, '1'); // temp file to determine if stream is local or not

            $existingFilePath = $this->resolveLocalFilePath($this->getBinaryFileStream($version));
            $dataFilePath = $this->resolveLocalFilePath($binaryDataStream);

            if ($useHardlinks && $existingFilePath !== null && $dataFilePath !== null) {
                $this->storage->delete($binaryStoragePath);
                $linked = @link($dataFilePath, $existingFilePath);
            }

            if (!$linked) {
                $this->storage->writeStream($binaryStoragePath, $binaryDataStream);
            }
        }
    }

    public function delete(Version $version,
        bool $isBinaryHashInUse): void
    {
        $binaryStoragePath = $this->getBinaryStoragePath($version);
        $storageFileName = $this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype());

        $storagePath = dirname($storageFileName);
        $this->deleteFileIfExists($storageFileName);
        File::recursiveDeleteEmptyDirs($this->storage, $storagePath);

        if (!$isBinaryHashInUse) {
            $this->deleteFileIfExists($binaryStoragePath);
        }
    }

    /**
     * resolveLocalFilePath returns the real filesystem path backing the given stream, or null when the
     * stream isn't backed by an actual file. stream_is_local() alone isn't enough here: it also returns
     * true for in-memory wrappers like php://temp, which have no real path on disk and make link() fail
     * with "No such file or directory".
     */
    private function resolveLocalFilePath(mixed $stream): ?string
    {
        if (!is_resource($stream) || !stream_is_local($stream)) {
            return null;
        }

        $uri = stream_get_meta_data($stream)['uri'] ?? null;

        return $uri !== null && is_file($uri) ? $uri : null;
    }

    private function deleteFileIfExists(string $path): void
    {
        try {
            $this->storage->delete($path);
        } catch (UnableToDeleteFile $e) {
            // Tolerate races where the file was deleted concurrently between
            // our intent to delete it and the actual unlink. If it is gone
            // either way, treat the outcome as a successful deletion so the
            // caller can still sweep empty parent directories.
            if ($this->storage->fileExists($path)) {
                throw $e;
            }
        }
    }

    public function getStorageType(
        ?int $metaDataSize = null,
        ?int $binaryDataSize = null): string
    {
        return 'fs';
    }
}
