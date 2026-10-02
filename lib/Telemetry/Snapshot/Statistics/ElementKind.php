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

namespace Pimcore\Telemetry\Snapshot\Statistics;

/**
 * The three top-level Pimcore element kinds a statistics query can target. The enum keeps the SQL
 * table name in one place; a search-index-backed provider maps the same kinds to its own index
 * aliases. Content-never - these are Pimcore's own structural identifiers.
 *
 * @internal
 */
enum ElementKind: string
{
    case DataObject = 'object';
    case Asset = 'asset';
    case Document = 'document';

    /**
     * The element-index table backing this kind.
     */
    public function table(): string
    {
        return match ($this) {
            self::DataObject => 'objects',
            self::Asset => 'assets',
            self::Document => 'documents',
        };
    }
}
