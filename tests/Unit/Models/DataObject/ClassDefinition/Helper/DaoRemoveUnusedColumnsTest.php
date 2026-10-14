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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

/**
 * Ensures removeUnusedColumns()/removeIndices() from the Helper\Dao trait quote the column/index
 * name via quoteSingleIdentifier() instead of concatenating it with raw backticks (GHSA-2rmm-27mv-jwg5).
 * Without this, a column name reaching this path with an embedded backtick (e.g. a StructuredTable
 * key that bypassed validation) could inject an additional DDL clause into the ALTER TABLE statement.
 * quoteSingleIdentifier() (rather than the deprecated quoteIdentifier()) is required specifically
 * because it does not split the value on '.' as a qualified-name separator - StructuredTable keys are
 * still allowed to contain a literal '.', which quoteIdentifier() would otherwise misquote as two
 * separate, unquoted-dot-joined identifier parts.
 */
class DaoRemoveUnusedColumnsTest extends TestCase
{
    private const MALICIOUS_KEY = 'x`, DROP COLUMN `oo_classname';

    public function testMaliciousColumnKeyIsQuotedInDropColumnStatement(): void
    {
        $mockDb = $this->createMock(Connection::class);
        $mockDb->method('quoteSingleIdentifier')->willReturnCallback(
            static fn (string $id): string => '`' . str_replace('`', '``', $id) . '`'
        );
        // indexExists()/foreignKeyExists() => not found, so removeIndices() executes no extra query
        $mockDb->method('fetchFirstColumn')->willReturn([0]);

        $executedQueries = [];
        $mockDb->method('executeQuery')->willReturnCallback(function (string $sql) use (&$executedQueries) {
            $executedQueries[] = $sql;

            return $this->createMock(Result::class);
        });

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callRemoveUnusedColumns('object_query_1', [self::MALICIOUS_KEY], []);

        $this->assertCount(1, $executedQueries);
        $alterTableQuery = $executedQueries[0];
        $this->assertStringStartsWith('ALTER TABLE', $alterTableQuery);
        // The malicious key's own payload contains the literal text "DROP COLUMN", so a plain
        // substring/occurrence count can't tell a neutralized clause apart from an injected one.
        // What matters is that quoteIdentifier() escaped (doubled) every backtick, so the whole
        // malicious value ends up inside a single quoted identifier - assert the exact resulting
        // clause to prove that, rather than counting substring occurrences.
        $this->assertSame(
            'ALTER TABLE `object_query_1` DROP COLUMN `x``, DROP COLUMN ``oo_classname`;',
            $alterTableQuery
        );
        $this->assertStringNotContainsString('DROP COLUMN `oo_classname`', $alterTableQuery);
    }

    public function testMaliciousIndexKeyIsQuotedInDropIndexStatement(): void
    {
        $mockDb = $this->createMock(Connection::class);
        $mockDb->method('quoteSingleIdentifier')->willReturnCallback(
            static fn (string $id): string => '`' . str_replace('`', '``', $id) . '`'
        );
        // indexExists() => found, so the DROP INDEX statement is actually built and executed
        $mockDb->method('fetchFirstColumn')->willReturn([1]);

        $executedQueries = [];
        $mockDb->method('executeQuery')->willReturnCallback(function (string $sql) use (&$executedQueries) {
            $executedQueries[] = $sql;

            return $this->createMock(Result::class);
        });

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callRemoveIndices('object_query_1', [self::MALICIOUS_KEY], []);

        $this->assertCount(1, $executedQueries);
        $dropIndexQuery = $executedQueries[0];
        $this->assertStringStartsWith('ALTER TABLE', $dropIndexQuery);
        // The malicious key injects the literal text "DROP COLUMN", not "DROP INDEX", so counting
        // "DROP INDEX" occurrences would pass even against the vulnerable pre-fix concatenation
        // (it produces a second, executable "DROP COLUMN" clause, leaving "DROP INDEX" at 1 either
        // way). Assert the exact resulting clause instead, which only matches once quoteIdentifier()
        // has escaped every backtick in the malicious value.
        $this->assertSame(
            'ALTER TABLE `object_query_1` DROP INDEX `u_index_x``, DROP COLUMN ``oo_classname`;',
            $dropIndexQuery
        );
    }

    public function testLegitimateColumnKeyStillDropsCorrectColumn(): void
    {
        $mockDb = $this->createMock(Connection::class);
        $mockDb->method('quoteSingleIdentifier')->willReturnCallback(
            static fn (string $id): string => '`' . str_replace('`', '``', $id) . '`'
        );
        $mockDb->method('fetchFirstColumn')->willReturn([0]);

        $executedQueries = [];
        $mockDb->method('executeQuery')->willReturnCallback(function (string $sql) use (&$executedQueries) {
            $executedQueries[] = $sql;

            return $this->createMock(Result::class);
        });

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callRemoveUnusedColumns('object_query_1', ['legacyfield'], []);

        $this->assertCount(1, $executedQueries);
        $this->assertStringContainsString('DROP COLUMN `legacyfield`', $executedQueries[0]);
    }

    /**
     * StructuredTable::validateKey() deliberately still accepts a literal '.' in a key (only
     * backticks are rejected). quoteIdentifier() treats '.' as a qualified-name separator and would
     * quote "row.withdot" as two parts joined by an unquoted dot; quoteSingleIdentifier() must keep
     * it as a single physical identifier instead.
     */
    public function testDottedLegacyColumnKeyIsQuotedAsASingleIdentifier(): void
    {
        $mockDb = $this->createMock(Connection::class);
        $mockDb->method('quoteSingleIdentifier')->willReturnCallback(
            static fn (string $id): string => '`' . str_replace('`', '``', $id) . '`'
        );
        $mockDb->method('fetchFirstColumn')->willReturn([0]);

        $executedQueries = [];
        $mockDb->method('executeQuery')->willReturnCallback(function (string $sql) use (&$executedQueries) {
            $executedQueries[] = $sql;

            return $this->createMock(Result::class);
        });

        $dao = $this->createDaoWithDb($mockDb);
        $dao->callRemoveUnusedColumns('object_query_1', ['row.withdot#col'], []);

        $this->assertCount(1, $executedQueries);
        $this->assertSame(
            'ALTER TABLE `object_query_1` DROP COLUMN `row.withdot#col`;',
            $executedQueries[0]
        );
    }

    /**
     * Creates a test double that exposes the trait's removeUnusedColumns()/removeIndices() methods.
     */
    private function createDaoWithDb(Connection $db): object
    {
        return new class($db) {
            use \Pimcore\Model\DataObject\ClassDefinition\Helper\Dao {
                removeUnusedColumns as public callRemoveUnusedColumns;
                removeIndices as public callRemoveIndices;
            }

            protected Connection $db;

            public function __construct(Connection $db)
            {
                $this->db = $db;
            }

            // Stubs required by the trait's other methods
            protected function getValidTableColumns(string $table, bool $cache = true): array
            {
                return [];
            }

            protected function resetValidTableColumnsCache(string $table): void
            {
            }

            public static function getForeignKeyName(string $table, string $column): string
            {
                return 'fk_' . $table . '__' . $column;
            }
        };
    }
}
