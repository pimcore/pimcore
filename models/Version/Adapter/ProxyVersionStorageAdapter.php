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

class ProxyVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected int $serializedDataThreshold;
    protected int $binaryDataThreshold;

    private string $defaultAdapter;
    private string $fallbackAdapter;

    public function __construct(protected array $adapters)
    {
        $container = \Pimcore::getContainer();
        $this->serializedDataThreshold = $container->getParameter('pimcore.config')['assets']['versions']['serialized_data_character_threshold'];
        $this->binaryDataThreshold = $container->getParameter('pimcore.config')['assets']['versions']['binary_data_byte_threshold'];
        $this->defaultAdapter = $container->getParameter('pimcore.config')['assets']['versions']['default_version_storage_adapter'] ?? "fs";
        $this->fallbackAdapter = $container->getParameter('pimcore.config')['assets']['versions']['fallback_version_storage_adapter'] ?? "";
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
                                 string $storageType) : ?string {

        return $this->getAdapter($storageType)->loadMetaData($id,
                                                            $cId,
                                                            $cType,
                                                            $storageType);
    }

    public function loadBinaryData(int $id,
                                   int $cId,
                                   string $cType,
                                   string $storageType,
                                   int $binaryFileId = null): mixed
    {
        return $this->getAdapter($storageType)->loadBinaryData($id,
                                                                $cId,
                                                                $cType,
                                                                $storageType,
                                                                $binaryFileId);
    }

    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null) : string {

        $size = 0;
        $adapter = $this->getAdapter();

        //switch to fallback adapter if one of the thresholds was reached
        if(empty($this->fallbackAdapter) === false) {
            if(isset($binaryDataStream) === true) {
                $stats = fstat($binaryDataStream);
                $size = $stats['size'];
            }
            if(strlen($metaData) >= $this->serializedDataThreshold ||
                $size >= $this->binaryDataThreshold) {
                $adapter = $this->getAdapter($this->fallbackAdapter);
            }
        }

        $adapter->save($id,
                        $cId,
                        $cType,
                        $metaData,
                        $binaryDataStream,
                        $binaryFileHash,
                        $binaryFileId);

        //set the storage type based on the used adapter
        return $this->getStorageTypeForAdapter($adapter);
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
}
