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

use Pimcore\Model\Version;

interface VersionStorageAdapterInterface
{
    /**
     * @param int|null $metaDataSize
     * @param int|null $binaryDataSize
     *
     * @return string
     */
    public function getStorageType(int $metaDataSize = null,
        int $binaryDataSize = null): string;

    /**
     * @param Version $version
     * @param string $metaData
     * @param resource|null $binaryDataStream
     *
     * @return void
     */
    public function save(Version $version, string $metaData, mixed $binaryDataStream): void;

    /**
     * @param Version $version
     *
     * @return string|null
     */
    public function loadMetaData(Version $version): ?string;

    /**
     * @param Version $version
     *
     * @return mixed
     */
    public function loadBinaryData(Version $version): mixed;

    /**
     * @param Version $version
     *
     * @return mixed
     */
    public function getBinaryFileStream(Version $version): mixed;

    /**
     * @param Version $version
     *
     * @return mixed
     */
    public function getFileStream(Version $version): mixed;

    /**
     * @param Version $version
     * @param bool $isBinaryHashInUse
     */
    public function delete(Version $version, bool $isBinaryHashInUse): void;
}
