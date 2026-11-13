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
use Pimcore\Bundle\CoreBundle\Migrations\Version20261113090000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * The migration creating `users_responsibles`: planned SQL on the old schema, and a no-op when the table
 * already exists (restored dumps, forward-merged release lines).
 */
class Version20261113090000Test extends TestCase
{
    private function createMigration(): Version20261113090000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));

        return new Version20261113090000($connection, new NullLogger());
    }

    private function schemaWithTable(bool $hasTable): Schema
    {
        $schema = $this->createStub(Schema::class);
        $schema->method('hasTable')->willReturnCallback(
            static fn (string $table): bool => $table === 'users_responsibles' && $hasTable
        );

        return $schema;
    }

    /**
     * @return string[]
     */
    private function planSql(Version20261113090000 $migration): array
    {
        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    public function testUpCreatesTable(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWithTable(false));

        $sql = $this->planSql($migration);

        $this->assertCount(1, $sql);
        $this->assertStringContainsString('CREATE TABLE `users_responsibles`', $sql[0]);
        $this->assertStringContainsString("`principalType` enum('user','role') NOT NULL", $sql[0]);
        $this->assertStringContainsString('PRIMARY KEY (`serviceUserId`,`principalType`,`principalId`)', $sql[0]);
        $this->assertStringContainsString('KEY `principal` (`principalType`,`principalId`)', $sql[0]);
    }

    public function testUpIsIdempotent(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWithTable(true));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testDownDropsTableOnlyWhenPresent(): void
    {
        $migration = $this->createMigration();
        $migration->down($this->schemaWithTable(true));
        $this->assertSame(['DROP TABLE `users_responsibles`'], $this->planSql($migration));

        $migration = $this->createMigration();
        $migration->down($this->schemaWithTable(false));
        $this->assertSame([], $this->planSql($migration));
    }
}
