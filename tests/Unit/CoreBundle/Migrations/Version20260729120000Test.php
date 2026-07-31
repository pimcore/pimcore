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

namespace Pimcore\Tests\Unit\CoreBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Pimcore\Bundle\CoreBundle\Migrations\Version20260729120000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Regression test for pimcore/internal-improvements#16 — verifies the migration that
 * upgrades already-installed databases still carrying the deprecated `utf8`/`utf8_bin`/
 * `utf8_general_ci` names to the schema mirrored in install.sql: real `utf8mb4` for columns
 * with index headroom (verified against a live MariaDB instance), and the explicit `utf8mb3`
 * name only for `assets`.`filename`/`path`, `documents`.`key`/`path` and `objects`.`key`/`path`,
 * whose composite `fullpath` index is already at the 3072-byte InnoDB limit at 3 bytes/char —
 * MySQL has deprecated `utf8mb3` too, so this is a documented stopgap, not a clean target state.
 * `objects` is included even though a 2022 migration already renamed o_key/o_path to utf8mb3,
 * because fresh installs mark migrations as done without running them, so any install created
 * before this PR fixed install.sql never actually got that charset change.
 *
 * Also covers the two safety nets requested in code review: `up()` only touches a column when
 * its current collation/length still match the stock legacy definition (skipping, with a log
 * line, anything that looks customized), and `down()` refuses to run instead of silently
 * corrupting 4-byte characters on the way back to utf8/utf8mb3.
 */
class Version20260729120000Test extends TestCase
{
    /** Stock legacy (pre-migration) collation/length per table/column, as declared in install.sql. */
    private const STOCK_LEGACY_COLUMNS = [
        'assets' => ['filename' => ['utf8_bin', 255], 'path' => ['utf8_bin', 765]],
        'assets_image_thumbnail_cache' => ['filename' => ['utf8_bin', 190]],
        'documents' => ['key' => ['utf8_bin', 255], 'path' => ['utf8_bin', 765]],
        'objects' => ['key' => ['utf8_bin', 255], 'path' => ['utf8_bin', 765]],
        'lock_keys' => ['key_id' => ['utf8_general_ci', 64], 'key_token' => ['utf8_general_ci', 44]],
        'properties' => ['cpath' => ['utf8_general_ci', 765]],
        'tags' => ['name' => ['utf8_bin', 255]],
        'users_workspaces_asset' => ['cpath' => ['utf8_bin', 765]],
        'users_workspaces_document' => ['cpath' => ['utf8_bin', 765]],
        'users_workspaces_object' => ['cpath' => ['utf8_bin', 765]],
        'search_backend_data' => ['key' => ['utf8_bin', 255]],
    ];

    private const OPTIONAL_TABLES = ['assets_image_thumbnail_cache', 'search_backend_data'];

    private function createMigration(?LoggerInterface $logger = null): Version20260729120000
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $schemaManager = $this->createStub(AbstractSchemaManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        return new Version20260729120000($connection, $logger ?? new NullLogger());
    }

    private function planSql(Version20260729120000 $migration): array
    {
        return array_map(fn ($query) => $query->getStatement(), $migration->getSql());
    }

    /**
     * Builds a schema double reporting the given collation/length for every table/column
     * (defaulting to the stock legacy values), so tests can override a single column to
     * simulate a customized installation.
     */
    private function schemaFromColumns(array $columnsByTable, bool $hasOptionalTables): Schema
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('hasTable')->willReturnCallback(
            fn (string $table) => !in_array($table, self::OPTIONAL_TABLES, true) || $hasOptionalTables
        );
        $schema->method('getTable')->willReturnCallback(function (string $table) use ($columnsByTable) {
            $columns = $columnsByTable[$table] ?? [];

            $tableMock = $this->createMock(Table::class);
            $tableMock->method('hasColumn')->willReturnCallback(fn (string $column) => isset($columns[$column]));
            $tableMock->method('getColumn')->willReturnCallback(function (string $column) use ($columns) {
                [$collation, $length] = $columns[$column];

                $columnStub = $this->createStub(Column::class);
                $columnStub->method('getCollation')->willReturn($collation);
                $columnStub->method('getLength')->willReturn($length);

                return $columnStub;
            });

            return $tableMock;
        });

        return $schema;
    }

    private function stockSchema(bool $hasOptionalTables): Schema
    {
        return $this->schemaFromColumns(self::STOCK_LEGACY_COLUMNS, $hasOptionalTables);
    }

    public function testUpModernizesRequiredColumns(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->stockSchema(true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringContainsString('`assets` MODIFY `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`assets` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`documents` MODIFY `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`documents` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`objects` MODIFY `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`objects` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`lock_keys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci', $sql);
        $this->assertStringContainsString('`properties` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci', $sql);
        $this->assertStringContainsString('`tags` MODIFY `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`users_workspaces_asset` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`users_workspaces_document` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`users_workspaces_object` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);

        // no deprecated bare utf8/utf8_bin/utf8_general_ci should remain in the up() path
        $this->assertDoesNotMatchRegularExpression('/utf8(?!mb[34])/i', $sql);

        // utf8mb3 must stay confined to the three index-width-constrained tables, not creep elsewhere
        $utf8mb3Statements = array_values(array_filter($this->planSql($migration), fn (string $s) => str_contains($s, 'utf8mb3')));
        $this->assertCount(6, $utf8mb3Statements);
        foreach ($utf8mb3Statements as $statement) {
            $this->assertMatchesRegularExpression('/`(assets|documents|objects)` MODIFY `(filename|key|path)`/', $statement);
        }
    }

    public function testUpModernizesOptionalTablesWhenPresent(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->stockSchema(true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringContainsString('`assets_image_thumbnail_cache` MODIFY `filename` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`search_backend_data` MODIFY `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
    }

    public function testUpSkipsOptionalTablesWhenAbsent(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->stockSchema(false));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringNotContainsString('assets_image_thumbnail_cache', $sql);
        $this->assertStringNotContainsString('search_backend_data', $sql);
    }

    public function testUpSkipsColumnWidenedByAProject(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('notice')
            ->with($this->stringContains('`tags`.`name`'));

        $columns = self::STOCK_LEGACY_COLUMNS;
        $columns['tags']['name'] = ['utf8_bin', 500]; // project widened it beyond the stock varchar(255)

        $migration = $this->createMigration($logger);
        $migration->up($this->schemaFromColumns($columns, true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringNotContainsString('`tags` MODIFY `name`', $sql);
    }

    public function testUpSkipsColumnWithCustomCollation(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('notice')
            ->with($this->stringContains('`properties`.`cpath`'));

        $columns = self::STOCK_LEGACY_COLUMNS;
        $columns['properties']['cpath'] = ['utf8mb4_unicode_ci', 765]; // already modernized differently

        $migration = $this->createMigration($logger);
        $migration->up($this->schemaFromColumns($columns, true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringNotContainsString('`properties` MODIFY `cpath`', $sql);
    }

    public function testUpSkipsLockKeysWhenCustomized(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('notice')
            ->with($this->stringContains('`lock_keys`'));

        $columns = self::STOCK_LEGACY_COLUMNS;
        $columns['lock_keys']['key_id'] = ['utf8_general_ci', 128]; // project widened key_id

        $migration = $this->createMigration($logger);
        $migration->up($this->schemaFromColumns($columns, true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringNotContainsString('`lock_keys` CONVERT', $sql);
    }

    public function testDownIsIrreversible(): void
    {
        $migration = $this->createMigration();

        $this->expectException(IrreversibleMigration::class);

        $migration->down($this->stockSchema(true));
    }
}
