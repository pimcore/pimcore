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

/**
 * Optional addition to VersionStorageAdapterInterface for adapters whose storage type depends on the element type
 * (asset, document, object) of the version, see ElementDelegateVersionStorageAdapter. Version::save() uses it
 * instead of VersionStorageAdapterInterface::getStorageType() if the adapter implements it.
 */
interface ElementTypeAwareStorageTypeInterface
{
    public function getStorageTypeForElementType(
        string $elementType,
        ?int $metaDataSize = null,
        ?int $binaryDataSize = null
    ): string;
}
