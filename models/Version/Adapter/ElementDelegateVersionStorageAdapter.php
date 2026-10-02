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

use InvalidArgumentException;
use Pimcore\Model\Version;

/**
 * Routes version data to a storage adapter per element type (asset, document, object), e.g. asset versions to an
 * object storage and document and object versions to the local filesystem. Element types without a configured
 * adapter use the default adapter. There is no fallback between the adapters: moving the versions of an element type
 * to another storage is a project-specific migration (see the Versioning documentation).
 */
final class ElementDelegateVersionStorageAdapter implements VersionStorageAdapterInterface, ElementTypeAwareStorageTypeInterface
{
    private const ELEMENT_TYPES = ['asset', 'document', 'object'];

    /**
     * @var array<string, VersionStorageAdapterInterface>
     */
    private readonly array $adapters;

    /**
     * @param array<mixed> $adapters storage adapters by element type (`asset`, `document`, `object`)
     */
    public function __construct(
        array $adapters,
        private readonly VersionStorageAdapterInterface $defaultAdapter
    ) {
        $validatedAdapters = [];

        foreach ($adapters as $elementType => $adapter) {
            if (!in_array($elementType, self::ELEMENT_TYPES, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown element type "%s" for a version storage adapter, expected one of: %s',
                    $elementType,
                    implode(', ', self::ELEMENT_TYPES)
                ));
            }

            if (!$adapter instanceof VersionStorageAdapterInterface) {
                throw new InvalidArgumentException(sprintf(
                    'The version storage adapter for element type "%s" has to implement %s, %s given',
                    $elementType,
                    VersionStorageAdapterInterface::class,
                    get_debug_type($adapter)
                ));
            }

            $validatedAdapters[$elementType] = $adapter;
        }

        $this->adapters = $validatedAdapters;
    }

    public function getStorageTypeForElementType(
        string $elementType,
        ?int $metaDataSize = null,
        ?int $binaryDataSize = null
    ): string {
        $adapter = $this->getAdapter($elementType);

        if ($adapter instanceof ElementTypeAwareStorageTypeInterface) {
            return $adapter->getStorageTypeForElementType($elementType, $metaDataSize, $binaryDataSize);
        }

        return $adapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    /**
     * Without an element type, the storage type of the default adapter is returned. Version::save() uses
     * getStorageTypeForElementType() instead.
     */
    public function getStorageType(
        ?int $metaDataSize = null,
        ?int $binaryDataSize = null
    ): string {
        return $this->defaultAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->getAdapter($version->getCtype())->save($version, $metaData, $binaryDataStream);
    }

    public function loadMetaData(Version $version): ?string
    {
        return $this->getAdapter($version->getCtype())->loadMetaData($version);
    }

    public function loadBinaryData(Version $version): mixed
    {
        return $this->getAdapter($version->getCtype())->loadBinaryData($version);
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->getAdapter($version->getCtype())->getBinaryFileStream($version);
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->getAdapter($version->getCtype())->getFileStream($version);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->getAdapter($version->getCtype())->delete($version, $isBinaryHashInUse);
    }

    private function getAdapter(string $elementType): VersionStorageAdapterInterface
    {
        return $this->adapters[$elementType] ?? $this->defaultAdapter;
    }
}
