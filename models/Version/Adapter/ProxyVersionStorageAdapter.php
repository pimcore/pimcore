<?php

namespace Pimcore\Model\Version\Adapter;

use Pimcore\Model\Version;

class ProxyVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected VersionStorageAdapterInterface $storageAdapter;

    public function __construct(FileSystemVersionStorageAdapter $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

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
    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->storageAdapter->save($version, $metaData, $binaryDataStream);
    }

    /**
     * @inheritDoc
     */
    public function loadMetaData(Version $version): ?string
    {
        return $this->storageAdapter->loadMetaData($version);
    }

    /**
     * @inheritDoc
     */
    public function loadBinaryData(Version $version): mixed
    {
        return $this->storageAdapter->loadBinaryData($version);
    }

    /**
     * @inheritDoc
     */
    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->storageAdapter->getBinaryFileStream($version);
    }

    /**
     * @inheritDoc
     */
    public function getFileStream(Version $version): mixed
    {
        return $this->storageAdapter->getFileStream($version);
    }

    /**
     * @inheritDoc
     */
    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->storageAdapter->delete($version, $isBinaryHashInUse);
    }

    public function setStorageAdapter(VersionStorageAdapterInterface $adapter) {
        $this->storageAdapter = $adapter;
    }
}
