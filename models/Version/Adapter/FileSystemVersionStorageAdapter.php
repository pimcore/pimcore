<?php

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
use Pimcore\File;
use Pimcore\Tool\Storage;

class FileSystemVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected FilesystemOperator $storage;

    public function __construct()
    {
        $this->storage = Storage::get('version');
    }

    public function loadMetaData(int $id,
                                 int $cId,
                                 string $cType,
                                 string $storageType = null) : ?string
    {

        try {
            $data = $this->storage->read($this->getStorageFilename($id, $cId, $cType));
        } catch (UnableToReadFile $e) {
            $data = null;
        }

        return $data;
    }

    public function loadBinaryData(int $id,
                                   int $cId,
                                   string $cType,
                                   string $storageType = null,
                                   int $binaryFileId = null): mixed
    {
        $binaryStoragePath = $this->getBinaryStoragePath($id,
                                                        $cId,
                                                        $cType,
                                                        $binaryFileId);

        if($this->storage->fileExists($binaryStoragePath)) {
            return $this->getBinaryFileStream($id,
                                                $cId,
                                                $cType,
                                                $binaryFileId);
        }
        return null;
    }

    public function getBinaryFileStream(int $id,
                                        int $cId,
                                        string $cType,
                                        int $binaryFileId = null): mixed
    {
        return $this->storage->readStream($this->getBinaryStoragePath($id, $cId, $cType, $binaryFileId));
    }

    public function getFileStream(int $id,
                                  int $cId,
                                  string $cType): mixed
    {
        return $this->storage->readStream($this->getStorageFilename($id, $cId, $cType));
    }

    public function getStorageFilename(int $id,
                                       int $cId,
                                       string $cType): string
    {
        $group = floor($cId / 10000) * 10000;

        return $cType . '/g' . $group . '/' . $cId . '/' . $id;
    }

    public function getBinaryStoragePath(int $id,
                                          int $cId,
                                          string $cType,
                                          int $binaryFileId = null): string
    {
        if(isset($binaryFileId) === false) {
            $binaryFileId = $id;
        }
        return $this->getStorageFilename($binaryFileId, $cId, $cType) . '.bin';
    }

    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null): void {

        $this->storage->write($this->getStorageFilename($id, $cId, $cType), $metaData);
        $binaryStoragePath = $this->getBinaryStoragePath($id, $cId, $cType, $binaryFileId);

        // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
        if (isset($binaryDataStream) === true &&
            !$this->storage->fileExists($binaryStoragePath)) {
            $linked = false;

            // we always try to create a hardlink onto the original file, the asset ensures that not the actual
            // inodes get overwritten but creates new inodes if the content changes. This is done by deleting the
            // old file first before opening a new stream -> see Asset::update()
            $useHardlinks = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['versions']['use_hardlinks'];
            $this->storage->write($binaryStoragePath, '1'); // temp file to determine if stream is local or not
            if ($useHardlinks && stream_is_local($this->getBinaryFileStream($id, $cId, $cType)) && stream_is_local($binaryDataStream)) {
                $linkPath = stream_get_meta_data($this->getBinaryFileStream($id, $cId, $cType))['uri'];
                $this->storage->delete($binaryStoragePath);
                $linked = @link(stream_get_meta_data($binaryDataStream)['uri'], $linkPath);
            }

            if (!$linked) {
                $this->storage->writeStream($binaryStoragePath, $binaryDataStream);
            }
        }
    }

    public function delete(int $id,
                            int $cId,
                            string $cType,
                            bool $isBinaryHashInUse,
                            int $binaryFileId = null): void {

        $binaryStoragePath = $this->getBinaryStoragePath($id, $cId, $cType, $binaryFileId);
        $storageFileName = $this->getStorageFilename($id, $cId, $cType);

        $storagePath = dirname($storageFileName);
        if ($this->storage->fileExists($storageFileName)) {
            $this->storage->delete($storageFileName);
            File::recursiveDeleteEmptyDirs($this->storage, $storagePath);
        }

        if ($this->storage->fileExists($binaryStoragePath) && !$isBinaryHashInUse) {
            $this->storage->delete($binaryStoragePath);
        }
    }

    public function getStorageType(string $metaData, mixed $binaryDataStream = null): string
    {
        return "fs";
    }
}
