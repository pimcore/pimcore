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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

/**
 * Shared rewriteIds() implementation for the element-referencing metadata data types
 * (Asset, Document, DataObject), whose raw stored data is the referenced element's id.
 */
trait IdRewriterTrait
{
    public function rewriteIds(mixed $data, array $rewriteConfig, array $params = []): mixed
    {
        $elementType = $params['type'] ?? null;
        if ($elementType === null || !is_numeric($data)) {
            return $data;
        }

        return $rewriteConfig[$elementType][(int) $data] ?? $data;
    }
}
