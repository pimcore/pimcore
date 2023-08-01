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

interface VersionStorageAdapterInterface
{
    public function getStorageType(int $metaDataSize = null,
        int $binaryDataSize = null): string;

    /**
     * @param resource|null $binaryDataStream
     *
     */
    public function save(Version $version, string $metaData, mixed $binaryDataStream): void;

    public function loadMetaData(Version $version): ?string;

    public function loadBinaryData(Version $version): mixed;

    public function getBinaryFileStream(Version $version): mixed;

    public function getFileStream(Version $version): mixed;

    public function delete(Version $version, bool $isBinaryHashInUse): void;
}
