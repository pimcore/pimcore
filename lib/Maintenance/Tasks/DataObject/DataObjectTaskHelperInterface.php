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

namespace Pimcore\Maintenance\Tasks\DataObject;

/**
 * @internal
 */
interface DataObjectTaskHelperInterface
{
    /**
     * Returns a map of lowercased => actual object-brick key for every brick definition known to
     * Pimcore, loaded from all supported definition directories (the primary class definition
     * directory and the custom configuration directory). Used as the authoritative ownership list
     * before any table is considered orphaned.
     *
     * @return array<string, string>
     */
    public function getObjectBrickCollectionNames(): array;

    /**
     * Returns a map of lowercased => actual fieldcollection key for every fieldcollection definition
     * known to Pimcore, loaded from all supported definition directories.
     *
     * @return array<string, string>
     */
    public function getFieldcollectionCollectionNames(): array;

    /**
     * Returns the actual (case-preserved) collection key from $collectionNames that owns the given
     * table identifier (the part of the table name following the type prefix), or null if none does.
     * A table is only considered owned when a known key is a full, underscore-delimited prefix of the
     * identifier - so keys containing underscores are matched correctly and live tables are never
     * mistaken for orphans.
     */
    public function matchCollectionKey(string $tableIdentifier, array $collectionNames): ?string;

    /**
     * Drops a table that no longer belongs to any existing definition.
     */
    public function dropOrphanedTable(string $tableName): void;

    public function cleanupTable(
        string $tableName,
        string $classId,
        bool $isLocalized = true
    ): void;
}
