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
     * parse (via the non-mutating {@see classExists()}) and may treat the table as orphaned only
     * when no candidate resolves to a live class, so live tables are never mistaken for orphans.
     *
     * @param array<string, string> $collectionNames lowercased => actual collection key
     *
     * @return string[]
     */
    public function matchCollectionKeys(string $tableIdentifier, array $collectionNames): array;

    /**
     * Non-mutating ownership probe: whether a class definition exists for the given class id
     * (case-insensitive). Used to decide which candidate parse of a table name is live before any
     * destructive or mutating operation is performed.
     */
    public function classExists(string $classId): bool;

    /**
     * Drops a table that no longer belongs to any existing definition.
     */
    public function dropOrphanedTable(string $tableName): void;

    /**
     * Cleans up stale field rows of a brick/fieldcollection table that belongs to the class
     * identified by $classId. This MUTATES the table (deletes rows whose fieldname no longer exists
     * on the class), so callers must only invoke it once ownership is unambiguous: exactly one
     * candidate parse of the table name resolves to a live class (see {@see classExists()}).
     *
     * Returns false (and touches nothing) when no class definition exists for $classId.
     */
    public function cleanupTable(
        string $tableName,
        string $classId,
        bool $isLocalized = true
    ): bool;
}
