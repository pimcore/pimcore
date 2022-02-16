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

interface VersionStorageAdapterInterface
{
    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @param string $metaData
     * @param mixed|null $binaryDataStream
     * @param string|null $binaryFileHash
     * @param int|null $binaryFileId
     * @return string|null
     */
    public function save(int $id,
                         int $cId,
                         string $cType,
                         string $metaData,
                         mixed $binaryDataStream = null,
                         string $binaryFileHash = null,
                         int $binaryFileId = null) : ?string;

    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @param string|null $storageType
     * @return ?string
     */
    public function loadMetaData(int $id,
                                 int $cId,
                                 string $cType,
                                 string $storageType = null) : ?string;

    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @param string|null $storageType
     * @param int|null $binaryFileId
     * @return mixed
     */
    public function loadBinaryData(int    $id,
                                   int    $cId,
                                   string $cType,
                                   string $storageType = null,
                                   int    $binaryFileId = null): mixed;

    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @param int|null $binaryFileId
     * @return mixed
     */
    public function getBinaryFileStream(int    $id,
                                        int    $cId,
                                        string $cType,
                                        int    $binaryFileId = null): mixed;

    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @return mixed
     */
    public function getFileStream(int    $id,
                                  int    $cId,
                                  string $cType): mixed;

    /**
     * @param int $id
     * @param int $cId
     * @param string $cType
     * @param bool $isBinaryHashInUse
     * @param int|null $binaryFileId
     */
    public function delete(int $id,
                           int $cId,
                           string $cType,
                           bool $isBinaryHashInUse,
                           int $binaryFileId = null): void;
}
