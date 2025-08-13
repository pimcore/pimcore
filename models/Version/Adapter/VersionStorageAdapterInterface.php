<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Version\Adapter;

use Pimcore\Model\Version;

interface VersionStorageAdapterInterface
{
    public function getStorageType(
        ?int $metaDataSize = null,
        ?int $binaryDataSize = null): string;

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
