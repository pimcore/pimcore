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
     * Returns every actual (case-preserved) collection key from $collectionNames that could own the
     * given table identifier (the part of the table name following the type prefix), longest key
     * first, or an empty array if none could. A key is a candidate owner only when it is a full,
     * underscore-delimited prefix of the identifier. Because both keys and class ids may contain
     * underscores, several keys can claim the same identifier - the caller must probe each candidate
     * parse (via {@see cleanupTable()}) and may treat the table as orphaned only when no candidate
     * resolves to a live class, so live tables are never mistaken for orphans.
     *
     * @param array<string, string> $collectionNames lowercased => actual collection key
     *
     * @return string[]
     */
    public function matchCollectionKeys(string $tableIdentifier, array $collectionNames): array;

    /**
     * Drops a table that no longer belongs to any existing definition.
     */
    public function dropOrphanedTable(string $tableName): void;

    /**
     * Cleans up stale field columns/rows of a brick/fieldcollection table that belongs to the class
     * identified by $classId.
     *
     * Returns false when no class definition exists for $classId, i.e. this particular parse of the
     * table name does not correspond to a live class. The caller should then try the remaining
     * candidate parses from {@see matchCollectionKeys()} and treat the table as an orphan only when
     * every parse fails to resolve.
     */
    public function cleanupTable(
        string $tableName,
        string $classId,
        bool $isLocalized = true
    ): bool;
}
