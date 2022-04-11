<?php

namespace Pimcore\Model\Version\Adapter;

class ProxyVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected VersionStorageAdapterInterface $storageAdapter;

    /**
     * @inheritDoc
     */
    public function getStorageType(int $metaDataSize = null, int $binaryDataSize = null): string
    {
        return $this->storageAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    /**
     * @inheritDoc
     */
    public function save(int $id, int $cId, string $cType, string $storageType, string $metaData, mixed $binaryDataStream = null, string $binaryFileHash = null, int $binaryFileId = null): void
    {
        $this->storageAdapter->save($id, $cId, $cType, $storageType, $metaData, $binaryDataStream, $binaryFileHash, $binaryFileId);
    }

    /**
     * @inheritDoc
     */
    public function loadMetaData(int $id, int $cId, string $cType, string $storageType = null): ?string
    {
        return $this->storageAdapter->loadMetaData($id, $cId, $cType, $storageType);
    }

    /**
     * @inheritDoc
     */
    public function loadBinaryData(int $id, int $cId, string $cType, string $storageType = null, int $binaryFileId = null): mixed
    {
        return $this->storageAdapter->loadBinaryData($id, $cId, $cType, $storageType, $binaryFileId);
    }

    /**
     * @inheritDoc
     */
    public function getBinaryFileStream(int $id, int $cId, string $cType, int $binaryFileId = null): mixed
    {
        return $this->storageAdapter->getBinaryFileStream($id, $cId, $cType, $binaryFileId);
    }

    /**
     * @inheritDoc
     */
    public function getFileStream(int $id, int $cId, string $cType): mixed
    {
        return $this->storageAdapter->getFileStream($id, $cId, $cType);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id, int $cId, string $cType, string $storageType, bool $isBinaryHashInUse, int $binaryFileId = null): void
    {
        $this->storageAdapter->delete($id, $cId, $cType, $storageType, $isBinaryHashInUse, $binaryFileId);
    }

    public function setStorageAdapter(VersionStorageAdapterInterface $adapter) {
        $this->storageAdapter = $adapter;
    }
}
