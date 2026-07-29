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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Pimcore\Bundle\CoreBundle\Migrations\Version20260729120000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * Regression test for pimcore/internal-improvements#16 — verifies the migration that
 * upgrades already-installed databases still carrying the deprecated `utf8`/`utf8_bin`/
 * `utf8_general_ci` names to the schema mirrored in install.sql: real `utf8mb4` for columns
 * with index headroom (verified against a live MariaDB instance), and the explicit `utf8mb3`
 * name only for `assets`.`filename`/`path` and `documents`.`key`/`path`, whose composite
 * `fullpath` index is already at the 3072-byte InnoDB limit at 3 bytes/char — MySQL has
 * deprecated `utf8mb3` too, so this is a documented stopgap, not a clean target state.
 */
class Version20260729120000Test extends TestCase
{
    private function createMigration(): Version20260729120000
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $schemaManager = $this->createStub(AbstractSchemaManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        return new Version20260729120000($connection, new NullLogger());
    }

    private function planSql(Version20260729120000 $migration): array
    {
        return array_map(fn ($query) => $query->getStatement(), $migration->getSql());
    }

    private function schemaWithOptionalTables(bool $hasOptionalTables): Schema
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('hasTable')->willReturn($hasOptionalTables);

        if ($hasOptionalTables) {
            $table = $this->createMock(Table::class);
            $table->method('hasColumn')->willReturn(true);
            $schema->method('getTable')->willReturn($table);
        }

        return $schema;
    }

    public function testUpModernizesRequiredColumns(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWithOptionalTables(true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringContainsString('`assets` MODIFY `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`assets` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`documents` MODIFY `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`documents` MODIFY `path` varchar(765) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin', $sql);
        $this->assertStringContainsString('`lock_keys` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci', $sql);
        $this->assertStringContainsString('`properties` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci', $sql);
        $this->assertStringContainsString('`tags` MODIFY `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`users_workspaces_asset` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`users_workspaces_document` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`users_workspaces_object` MODIFY `cpath` varchar(765) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);

        // no deprecated bare utf8/utf8_bin/utf8_general_ci should remain in the up() path
        $this->assertDoesNotMatchRegularExpression('/utf8(?!mb[34])/i', $sql);

        // utf8mb3 must stay confined to the two index-width-constrained tables, not creep elsewhere
        $utf8mb3Statements = array_values(array_filter($this->planSql($migration), fn (string $s) => str_contains($s, 'utf8mb3')));
        $this->assertCount(4, $utf8mb3Statements);
        foreach ($utf8mb3Statements as $statement) {
            $this->assertMatchesRegularExpression('/`(assets|documents)` MODIFY `(filename|key|path)`/', $statement);
        }
    }

    public function testUpModernizesOptionalTablesWhenPresent(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWithOptionalTables(true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringContainsString('`assets_image_thumbnail_cache` MODIFY `filename` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
        $this->assertStringContainsString('`search_backend_data` MODIFY `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin', $sql);
    }

    public function testUpSkipsOptionalTablesWhenAbsent(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWithOptionalTables(false));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringNotContainsString('assets_image_thumbnail_cache', $sql);
        $this->assertStringNotContainsString('search_backend_data', $sql);
    }

    public function testDownRevertsToOriginalDeprecatedNames(): void
    {
        $migration = $this->createMigration();
        $migration->down($this->schemaWithOptionalTables(true));

        $sql = implode("\n", $this->planSql($migration));

        $this->assertStringContainsString('`assets` MODIFY `filename` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin', $sql);
        $this->assertStringContainsString('`lock_keys` CONVERT TO CHARACTER SET utf8', $sql);
        $this->assertStringContainsString('`tags` MODIFY `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin', $sql);
    }
}
