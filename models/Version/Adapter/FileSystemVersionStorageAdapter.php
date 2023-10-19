<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Version\Adapter;

use League\Flysystem\FilesystemOperator;
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
            if ($useHardlinks && stream_is_local($this->getBinaryFileStream($version)) && stream_is_local($binaryDataStream)) {
                $linkPath = stream_get_meta_data($this->getBinaryFileStream($version))['uri'];
                $this->storage->delete($binaryStoragePath);
                $linked = @link(stream_get_meta_data($binaryDataStream)['uri'], $linkPath);
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
        if ($this->storage->fileExists($storageFileName)) {
            $this->storage->delete($storageFileName);
            File::recursiveDeleteEmptyDirs($this->storage, $storagePath);
        }

        if ($this->storage->fileExists($binaryStoragePath) && !$isBinaryHashInUse) {
            $this->storage->delete($binaryStoragePath);
        }
    }

    public function getStorageType(int $metaDataSize = null,
        int $binaryDataSize = null): string
    {
        return 'fs';
    }
}
