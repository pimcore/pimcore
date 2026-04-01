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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fixes table and column collation for all utf8mb4 tables to use utf8mb4_unicode_520_ci.
 *
 * Background: install.sql and Dao CREATE TABLE statements used `DEFAULT CHARSET=utf8mb4`
 * without an explicit COLLATE clause. When MySQL/MariaDB sees an explicit charset without
 * a collation, it uses the charset's built-in default (utf8mb4_general_ci) rather than
 * the database default collation. This causes collation mismatches that prevent foreign
 * key creation between tables.
 */
final class Version20260401120000 extends AbstractMigration
{
    private const TARGET_COLLATION = 'utf8mb4_unicode_520_ci';

    public function getDescription(): string
    {
        return 'Fix collation for all utf8mb4 tables and columns to use utf8mb4_unicode_520_ci';
    }

    public function up(Schema $schema): void
    {
        // Step 1: Fix table default collation
        $tables = $this->connection->fetchFirstColumn(
            'SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_COLLATION LIKE \'utf8mb4_%\'
               AND TABLE_COLLATION != ?',
            [self::TARGET_COLLATION]
        );

        foreach ($tables as $table) {
            $this->write(sprintf('  Fixing table default collation: %s', $table));
            $this->connection->executeStatement(
                sprintf(
                    'ALTER TABLE `%s` DEFAULT COLLATE = %s',
                    $table,
                    self::TARGET_COLLATION
                )
            );
        }

        // Step 2: Collect affected columns (utf8mb4 with wrong collation, base tables only)
        $columns = $this->connection->fetchAllAssociative(
            'SELECT c.TABLE_NAME, c.COLUMN_NAME, c.COLUMN_TYPE, c.IS_NULLABLE, c.COLUMN_DEFAULT, c.EXTRA
             FROM information_schema.COLUMNS c
             JOIN information_schema.TABLES t
               ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND t.TABLE_TYPE = \'BASE TABLE\'
               AND c.CHARACTER_SET_NAME = \'utf8mb4\'
               AND c.COLLATION_NAME != ?
               AND c.COLLATION_NAME != \'utf8mb4_bin\'
             ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION',
            [self::TARGET_COLLATION]
        );

        if (empty($columns)) {
            $this->write('  No columns need collation changes.');

            return;
        }

        // Build a set of affected table.column pairs for quick lookup
        $affectedColumns = [];
        foreach ($columns as $col) {
            $affectedColumns[$col['TABLE_NAME'] . '.' . $col['COLUMN_NAME']] = true;
        }

        // Step 3: Temporarily drop FULLTEXT indexes that reference affected columns
        $fulltextIndexes = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT s.TABLE_NAME, s.INDEX_NAME
             FROM information_schema.STATISTICS s
             WHERE s.TABLE_SCHEMA = DATABASE()
               AND s.INDEX_TYPE = \'FULLTEXT\'
               AND EXISTS (
                   SELECT 1 FROM information_schema.STATISTICS s2
                   JOIN information_schema.COLUMNS c
                     ON s2.TABLE_SCHEMA = c.TABLE_SCHEMA
                     AND s2.TABLE_NAME = c.TABLE_NAME
                     AND s2.COLUMN_NAME = c.COLUMN_NAME
                   WHERE s2.TABLE_SCHEMA = s.TABLE_SCHEMA
                     AND s2.TABLE_NAME = s.TABLE_NAME
                     AND s2.INDEX_NAME = s.INDEX_NAME
                     AND c.CHARACTER_SET_NAME = \'utf8mb4\'
                     AND c.COLLATION_NAME != ?
                     AND c.COLLATION_NAME != \'utf8mb4_bin\'
               )',
            [self::TARGET_COLLATION]
        );

        $fulltextDefinitions = [];
        foreach ($fulltextIndexes as $idx) {
            $indexColumns = $this->connection->fetchFirstColumn(
                'SELECT COLUMN_NAME
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND INDEX_NAME = ?
                 ORDER BY SEQ_IN_INDEX',
                [$idx['TABLE_NAME'], $idx['INDEX_NAME']]
            );
            $key = $idx['TABLE_NAME'] . '.' . $idx['INDEX_NAME'];
            $fulltextDefinitions[$key] = [
                'table' => $idx['TABLE_NAME'],
                'index' => $idx['INDEX_NAME'],
                'columns' => $indexColumns,
            ];

            $this->write(sprintf('  Dropping FULLTEXT index: %s.%s', $idx['TABLE_NAME'], $idx['INDEX_NAME']));
            $this->connection->executeStatement(
                sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $idx['TABLE_NAME'], $idx['INDEX_NAME'])
            );
        }

        // Step 4: Temporarily drop FK constraints that reference affected columns
        // This is necessary because MariaDB refuses ALTER TABLE MODIFY on columns
        // involved in foreign key relationships, even with FOREIGN_KEY_CHECKS=0
        $foreignKeys = $this->connection->fetchAllAssociative(
            'SELECT rc.TABLE_NAME, rc.CONSTRAINT_NAME,
                    rc.REFERENCED_TABLE_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
             FROM information_schema.REFERENTIAL_CONSTRAINTS rc
             JOIN information_schema.KEY_COLUMN_USAGE kcu
               ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
               AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
               AND rc.TABLE_NAME = kcu.TABLE_NAME
             WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
               AND (
                   CONCAT(kcu.TABLE_NAME, \'.\', kcu.COLUMN_NAME) IN ('
                       . implode(',', array_map(
                           fn () => '?',
                           array_keys($affectedColumns)
                       )) . ')
                   OR CONCAT(kcu.REFERENCED_TABLE_NAME, \'.\', kcu.REFERENCED_COLUMN_NAME) IN ('
                       . implode(',', array_map(
                           fn () => '?',
                           array_keys($affectedColumns)
                       )) . ')
               )',
            array_merge(
                array_keys($affectedColumns),
                array_keys($affectedColumns)
            )
        );

        $fkDefinitions = [];
        foreach ($foreignKeys as $fk) {
            $key = $fk['TABLE_NAME'] . '.' . $fk['CONSTRAINT_NAME'];
            if (isset($fkDefinitions[$key])) {
                continue;
            }

            // Get the columns for this FK
            $fkColumns = $this->connection->fetchAllAssociative(
                'SELECT COLUMN_NAME, REFERENCED_COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                 ORDER BY ORDINAL_POSITION',
                [$fk['TABLE_NAME'], $fk['CONSTRAINT_NAME']]
            );

            $cols = array_column($fkColumns, 'COLUMN_NAME');
            $refCols = array_column($fkColumns, 'REFERENCED_COLUMN_NAME');

            $fkDefinitions[$key] = [
                'table' => $fk['TABLE_NAME'],
                'constraint' => $fk['CONSTRAINT_NAME'],
                'columns' => $cols,
                'refTable' => $fk['REFERENCED_TABLE_NAME'],
                'refColumns' => $refCols,
                'onUpdate' => $fk['UPDATE_RULE'],
                'onDelete' => $fk['DELETE_RULE'],
            ];

            $this->write(sprintf('  Dropping FK constraint: %s.%s', $fk['TABLE_NAME'], $fk['CONSTRAINT_NAME']));
            $this->connection->executeStatement(
                sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $fk['TABLE_NAME'], $fk['CONSTRAINT_NAME'])
            );
        }

        // Step 5: Fix individual column collations
        foreach ($columns as $column) {
            $this->write(sprintf('  Fixing column collation: %s.%s', $column['TABLE_NAME'], $column['COLUMN_NAME']));

            $nullClause = $column['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
            $defaultClause = $this->buildDefaultClause($column);
            $extraClause = !empty($column['EXTRA']) ? ' ' . $column['EXTRA'] : '';

            $this->connection->executeStatement(
                sprintf(
                    'ALTER TABLE `%s` MODIFY `%s` %s COLLATE %s %s%s%s',
                    $column['TABLE_NAME'],
                    $column['COLUMN_NAME'],
                    $column['COLUMN_TYPE'],
                    self::TARGET_COLLATION,
                    $nullClause,
                    $defaultClause,
                    $extraClause
                )
            );
        }

        // Step 6: Recreate dropped FK constraints
        foreach ($fkDefinitions as $def) {
            $colList = '`' . implode('`, `', $def['columns']) . '`';
            $refColList = '`' . implode('`, `', $def['refColumns']) . '`';

            $this->write(sprintf('  Recreating FK constraint: %s.%s', $def['table'], $def['constraint']));
            $this->connection->executeStatement(
                sprintf(
                    'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (%s) REFERENCES `%s` (%s) ON DELETE %s ON UPDATE %s',
                    $def['table'],
                    $def['constraint'],
                    $colList,
                    $def['refTable'],
                    $refColList,
                    $def['onDelete'],
                    $def['onUpdate']
                )
            );
        }

        // Step 7: Recreate dropped FULLTEXT indexes
        foreach ($fulltextDefinitions as $def) {
            $columnList = '`' . implode('`, `', $def['columns']) . '`';
            $this->write(sprintf('  Recreating FULLTEXT index: %s.%s', $def['table'], $def['index']));
            $this->connection->executeStatement(
                sprintf(
                    'ALTER TABLE `%s` ADD FULLTEXT INDEX `%s` (%s)',
                    $def['table'],
                    $def['index'],
                    $columnList
                )
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('This migration cannot be reversed automatically. The previous collation values are not recorded.');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    /**
     * Builds the DEFAULT clause for an ALTER TABLE MODIFY statement.
     *
     * Handles differences between MySQL and MariaDB information_schema:
     * - MariaDB: COLUMN_DEFAULT for DEFAULT NULL returns the string 'NULL' (not PHP null).
     *   String defaults are returned already single-quoted (e.g. "'document'", "''").
     * - MySQL: COLUMN_DEFAULT for DEFAULT NULL returns PHP null (indistinguishable from no default).
     *   String defaults are returned unquoted.
     * - Both: PHP null in COLUMN_DEFAULT means no default was defined.
     */
    private function buildDefaultClause(array $column): string
    {
        $default = $column['COLUMN_DEFAULT'];

        // PHP null = no default defined (or DEFAULT NULL on MySQL 8)
        if ($default === null) {
            if ($column['IS_NULLABLE'] === 'YES') {
                return ' DEFAULT NULL';
            }

            return '';
        }

        // MariaDB returns the unquoted string 'NULL' for DEFAULT NULL
        if ($default === 'NULL') {
            return ' DEFAULT NULL';
        }

        // EXTRA contains 'DEFAULT_GENERATED' for expression defaults in MariaDB/MySQL 8+
        if (str_contains($column['EXTRA'] ?? '', 'DEFAULT_GENERATED')) {
            return ' DEFAULT (' . $default . ')';
        }

        // MariaDB returns string defaults already quoted (e.g. "'document'", "''")
        // MySQL returns them without quotes (e.g. "document", "")
        if (str_starts_with($default, "'") && str_ends_with($default, "'")) {
            // MariaDB: already quoted, use as-is
            return ' DEFAULT ' . $default;
        }

        // MySQL: needs quoting
        return " DEFAULT '" . addslashes($default) . "'";
    }
}
