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

class DelegateVersionStorageAdapter implements VersionStorageAdapterInterface
{
    private array $adapters = [];
    public function __construct(protected int $byte_threshold,
                                protected VersionStorageAdapterInterface $defaultAdapter,
                                protected VersionStorageAdapterInterface $fallbackAdapter)
    {
        $this->adapters[$defaultAdapter->getStorageType(null, null)] = $defaultAdapter;
        $this->adapters[$fallbackAdapter->getStorageType(null, null)] = $fallbackAdapter;
    }

    protected function getAdapter(string $storageType = null): VersionStorageAdapterInterface
    {
        if(empty($storageType) === true) {
            return $this->defaultAdapter;
        }
        else {
            $adapter = $this->adapters[$storageType] ?? null;
        }
        if(isset($adapter) === false) {
            throw new \Exception("no adapter for storage type " . $storageType . " found.");
        }
        return $adapter;
    }

    public function loadMetaData(int $id,
                                 int $cId,
                                 string $cType,
                                 string $storageType = null) : ?string {

        $storageType = $storageType ?? $this->defaultAdapter;
        return $this->getAdapter($storageType)->loadMetaData($id,
                                                            $cId,
                                                            $cType,
                                                            $storageType);
    }

    public function loadBinaryData(int $id,
                                   int $cId,
                                   string $cType,
                                   string $storageType = null,
                                   int $binaryFileId = null): mixed
    {
        $storageType = $storageType ?? $this->defaultAdapter;
        return $this->getAdapter($storageType)->loadBinaryData($id,
                                                                $cId,
                                                                $cType,
                                                                $storageType,
                                                                $binaryFileId);
    }

    public function getStorageType(int $metaDataSize = null,
                                   int $binaryDataSize = null): string {

        if(empty($this->fallbackAdapter) === false) {
            if ($metaDataSize > $this->byte_threshold ||
                $binaryDataSize > $this->byte_threshold) {
                return $this->fallbackAdapter->getStorageType($metaDataSize, $binaryDataSize);
            }
        }
        return $this->defaultAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $storageType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null) : void {

        $adapter = $this->getAdapter($storageType);
        $adapter->save($id,
                        $cId,
                        $cType,
                        $storageType,
                        $metaData,
                        $binaryDataStream,
                        $binaryFileHash,
                        $binaryFileId);
    }

    public function delete(int $id,
                           int $cId,
                           string $cType,
                           string $storageType,
                           bool $isBinaryHashInUse,
                           int $binaryFileId = null): void {

        $this->getAdapter($storageType)->delete($id,
                                                $cId,
                                                $cType,
                                                $storageType,
                                                $isBinaryHashInUse,
                                                $binaryFileId);
    }

    public function getBinaryFileStream(int $id, int $cId, string $cType, int $binaryFileId = null): mixed
    {
        return $this->getAdapter()->getBinaryFileStream($id,
                                                        $cId,
                                                        $cType,
                                                        $binaryFileId);
    }

    public function getFileStream(int $id, int $cId, string $cType): mixed
    {
        return $this->getAdapter()->getFileStream($id,
                                                  $cId,
                                                  $cType);
    }
}
