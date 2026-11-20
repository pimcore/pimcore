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
use Pimcore\Bundle\CoreBundle\Migrations\Version20261102120000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * The migration that replaces the single-column `parentId` index of `objects` with the composite
 * `parentId_type_published` index: the planned SQL for the old schema, for the new one (idempotency)
 * and for a partially applied state, in both directions.
 */
class Version20261102120000Test extends TestCase
{
    private function createMigration(): Version20261102120000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));

        return new Version20261102120000($connection, new NullLogger());
    }

    private function schemaWith(bool $hasCompositeIndex, bool $hasParentIdIndex): Schema
    {
        $table = $this->createStub(Table::class);
        $table->method('hasIndex')->willReturnCallback(
            static fn (string $index): bool => match ($index) {
                'parentId_type_published' => $hasCompositeIndex,
                'parentId' => $hasParentIdIndex,
                default => false,
            }
        );

        $schema = $this->createStub(Schema::class);
        $schema->method('getTable')->willReturnCallback(
            static function (string $tableName) use ($table): Table {
                self::assertSame('objects', $tableName);

                return $table;
            }
        );

        return $schema;
    }

    /**
     * @return string[]
     */
    private function planSql(Version20261102120000 $migration): array
    {
        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    public function testIsNotTransactional(): void
    {
        // DDL commits implicitly in MySQL, so a transaction around it would only fail at commit
        $this->assertFalse($this->createMigration()->isTransactional());
    }

    public function testUpReplacesParentIdIndexInOneStatement(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(false, true));

        // one ALTER TABLE, so lookups by parentId are never left without an index in between
        $this->assertSame(
            ['ALTER TABLE `objects` ADD INDEX `parentId_type_published` (`parentId`, `type`, `published`), DROP INDEX `parentId`'],
            $this->planSql($migration)
        );
    }

    public function testUpIsIdempotentWhenAlreadyApplied(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(true, false));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpDropsParentIdIndexWhenCompositeIndexExists(): void
    {
        // e.g. an installation that added the composite index by hand
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(true, true));

        $this->assertSame(['ALTER TABLE `objects` DROP INDEX `parentId`'], $this->planSql($migration));
    }

    public function testUpAddsCompositeIndexWhenParentIdIndexIsGone(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(false, false));

        $this->assertSame(
            ['ALTER TABLE `objects` ADD INDEX `parentId_type_published` (`parentId`, `type`, `published`)'],
            $this->planSql($migration)
        );
    }

    public function testDownRestoresParentIdIndexInOneStatement(): void
    {
        $migration = $this->createMigration();
        $migration->down($this->schemaWith(true, false));

        $this->assertSame(
            ['ALTER TABLE `objects` ADD INDEX `parentId` (`parentId`), DROP INDEX `parentId_type_published`'],
            $this->planSql($migration)
        );
    }

    public function testDownIsIdempotentWhenAlreadyReverted(): void
    {
        $migration = $this->createMigration();
        $migration->down($this->schemaWith(false, true));

        $this->assertSame([], $this->planSql($migration));
    }
}
