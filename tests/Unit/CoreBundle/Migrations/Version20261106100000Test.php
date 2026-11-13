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
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261106100000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * The migration that makes idx_users_workspaces_list_permission unique on upgraded installations.
 */
class Version20261106100000Test extends TestCase
{
    private const TABLES = ['users_workspaces_asset', 'users_workspaces_document', 'users_workspaces_object'];

    private function createMigration(): Version20261106100000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));

        return new Version20261106100000($connection, new NullLogger());
    }

    /**
     * @param bool|null $unique null: the table has no such index
     */
    private function schemaWith(?bool $unique, bool $hasTables = true): Schema
    {
        $index = $this->createStub(Index::class);
        $index->method('isUnique')->willReturn((bool) $unique);

        $table = $this->createStub(Table::class);
        $table->method('hasIndex')->willReturnCallback(
            static fn (string $name): bool => $name === 'idx_users_workspaces_list_permission' && $unique !== null
        );
        $table->method('getIndex')->willReturn($index);

        $schema = $this->createStub(Schema::class);
        $schema->method('hasTable')->willReturnCallback(
            static fn (string $name): bool => $hasTables && in_array($name, self::TABLES, true)
        );
        $schema->method('getTable')->willReturn($table);

        return $schema;
    }

    /**
     * @return string[]
     */
    private function planSql(Version20261106100000 $migration): array
    {
        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    public function testIsNotTransactional(): void
    {
        $this->assertFalse($this->createMigration()->isTransactional());
    }

    public function testUpMakesPlainIndexUniqueInOneStatementPerTable(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(false));

        $this->assertSame([
            'ALTER TABLE `users_workspaces_asset` DROP INDEX `idx_users_workspaces_list_permission`, ADD UNIQUE INDEX `idx_users_workspaces_list_permission` (`userId`, `cpath`, `list`)',
            'ALTER TABLE `users_workspaces_document` DROP INDEX `idx_users_workspaces_list_permission`, ADD UNIQUE INDEX `idx_users_workspaces_list_permission` (`userId`, `cpath`, `list`)',
            'ALTER TABLE `users_workspaces_object` DROP INDEX `idx_users_workspaces_list_permission`, ADD UNIQUE INDEX `idx_users_workspaces_list_permission` (`userId`, `cpath`, `list`)',
        ], $this->planSql($migration));
    }

    public function testUpLeavesFreshInstallationAlone(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(true));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpAddsMissingIndexAsUnique(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(null));

        $this->assertSame(
            'ALTER TABLE `users_workspaces_asset` ADD UNIQUE INDEX `idx_users_workspaces_list_permission` (`userId`, `cpath`, `list`)',
            $this->planSql($migration)[0]
        );
        $this->assertCount(3, $this->planSql($migration));
    }

    public function testUpSkipsMissingTables(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(false, false));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testDownLeavesUniqueIndexInPlaceOnFreshInstallation(): void
    {
        $migration = $this->createMigration();
        $migration->down($this->schemaWith(true));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testDownLeavesPlainIndexAlone(): void
    {
        $migration = $this->createMigration();
        $migration->down($this->schemaWith(false));

        $this->assertSame([], $this->planSql($migration));
    }
}
