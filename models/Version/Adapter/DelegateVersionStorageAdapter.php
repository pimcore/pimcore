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
    public function __construct(protected array $adapters,
                                protected int $byte_threshold,
                                protected string $defaultAdapter,
                                protected string $fallbackAdapter)
    {

    }

    protected function getAdapter(string $storageType = null): VersionStorageAdapterInterface
    {
        if(empty($storageType) === true) {
            $adapter = $this->adapters[$this->defaultAdapter];
        }
        else {
            $adapter = $this->adapters[$storageType] ?? null;
        }
        if(isset($adapter) === false || isset($adapter['class']) === false) {
            throw new \Exception("no adapter for storage type" . $storageType . " found.");
        }
        return $adapter['class'];
    }

    protected function getStorageTypeForAdapter(VersionStorageAdapterInterface $adapter = null): string
    {
        if(isset($adapter) === false)
            return $this->adapters[$this->defaultAdapter]['storageType'];
        else {
            foreach($this->adapters as $key => $value) {
                if($value['class'] === $adapter) {
                    return $key;
                }
            }
        }
        throw new \Exception("no storage type for adapter found.");
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

    public function getStorageType(string $metaData,
                                   mixed $binaryDataStream = null): string {
        if(empty($this->fallbackAdapter) === false) {
            $binarySize = 0;
            $metaDataSize = strlen($metaData);

            if (isset($binaryDataStream) === true) {
                $stats = fstat($binaryDataStream);
                $binarySize = $stats['size'];
            }

            if ($metaDataSize > $this->byte_threshold ||
                $binarySize > $this->byte_threshold) {
                return $this->fallbackAdapter;
            }
        }
        return $this->defaultAdapter;
    }

    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null) : void {

        $adapter = $this->getAdapter($this->getStorageType($metaData, $binaryDataStream));
        $adapter->save($id,
                        $cId,
                        $cType,
                        $metaData,
                        $binaryDataStream,
                        $binaryFileHash,
                        $binaryFileId);
    }

    public function delete(int $id,
                           int $cId,
                           string $cType,
                           bool $isBinaryHashInUse,
                           int $binaryFileId = null): void {

        $this->getAdapter()->delete($id,
                                $cId,
                                $cType,
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
