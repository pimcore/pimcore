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

use Pimcore\Model\Version;

class ProxyVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected VersionStorageAdapterInterface $storageAdapter;

    public function __construct(FileSystemVersionStorageAdapter $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function getStorageType(int $metaDataSize = null, int $binaryDataSize = null): string
    {
        return $this->storageAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->storageAdapter->save($version, $metaData, $binaryDataStream);
    }

    public function loadMetaData(Version $version): ?string
    {
        return $this->storageAdapter->loadMetaData($version);
    }

    public function loadBinaryData(Version $version): mixed
    {
        return $this->storageAdapter->loadBinaryData($version);
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->storageAdapter->getBinaryFileStream($version);
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->storageAdapter->getFileStream($version);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->storageAdapter->delete($version, $isBinaryHashInUse);
    }

    public function setStorageAdapter(VersionStorageAdapterInterface $adapter): void
    {
        $this->storageAdapter = $adapter;
    }
}
