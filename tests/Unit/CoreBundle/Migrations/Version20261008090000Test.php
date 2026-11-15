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
use Doctrine\Migrations\Exception\AbortMigration;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261008090000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * Regression test for the migration scoping user/role folder name uniqueness to the parent
 * folder (PEES-1660): the UNIQUE KEY `type_name` on `users` is replaced by a unique index over
 * the stored generated column `uniqueName`, which resolves to the plain name for users/roles
 * (so their global, security-relevant uniqueness stays database-enforced) and to
 * `<parentId>/<name>` for folder types.
 *
 * Covers the planned SQL for the pre-migration schema, idempotency and partially-applied states
 * (both run on forward-merged release lines and on restored dumps), the ordering guarantee that
 * the new unique index exists before the old one is dropped, and the duplicate-folder abort
 * that keeps down() from failing halfway through.
 */
class Version20261008090000Test extends TestCase
{
    private function createMigration(?Connection $connection = null): Version20261008090000
    {
        return new Version20261008090000($connection ?? $this->createConnection(), new NullLogger());
    }

    private function createConnection(?string $duplicateCount = null): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        if ($duplicateCount !== null) {
            $connection->method('fetchOne')->willReturn($duplicateCount);
        }

        return $connection;
    }

    private function schemaWith(bool $hasUniqueNameColumn, bool $hasNewIndex, bool $hasOldIndex): Schema
    {
        $table = $this->createStub(Table::class);
        $table->method('hasColumn')->willReturnCallback(
            static fn (string $column): bool => $column === 'uniqueName' && $hasUniqueNameColumn
        );
        $table->method('hasIndex')->willReturnCallback(
            static fn (string $index): bool => match ($index) {
                'type_uniqueName' => $hasNewIndex,
                'type_name' => $hasOldIndex,
                default => false,
            }
        );

        $schema = $this->createStub(Schema::class);
        $schema->method('getTable')->willReturnCallback(
            static function (string $tableName) use ($table): Table {
                self::assertSame('users', $tableName);

                return $table;
            }
        );

        return $schema;
    }

    /**
     * @return string[]
     */
    private function planSql(Version20261008090000 $migration): array
    {
        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    public function testUpAddsGeneratedColumnAndSwapsUniqueIndex(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(false, false, true));

        $sql = $this->planSql($migration);

        $this->assertCount(3, $sql);
        $this->assertStringContainsString('ADD COLUMN `uniqueName`', $sql[0]);
        $this->assertStringContainsString('GENERATED ALWAYS AS', $sql[0]);
        $this->assertStringContainsString('STORED', $sql[0]);
        // the scoped value only differs for folder types; users/roles keep the plain name
        $this->assertStringContainsString("IF(`type` IN ('userfolder', 'rolefolder')", $sql[0]);
        $this->assertStringContainsString('ADD UNIQUE KEY `type_uniqueName` (`type`, `uniqueName`)', $sql[1]);
        // the new unique index must be in place before the old one is dropped, so user/role
        // name uniqueness is never unenforced
        $this->assertStringContainsString('DROP KEY `type_name`', $sql[2]);
    }

    public function testUpIsIdempotentWhenAlreadyApplied(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(true, true, false));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpCompletesPartiallyAppliedState(): void
    {
        $migration = $this->createMigration();
        $migration->up($this->schemaWith(true, false, true));

        $sql = $this->planSql($migration);

        $this->assertCount(2, $sql);
        $this->assertStringContainsString('ADD UNIQUE KEY `type_uniqueName`', $sql[0]);
        $this->assertStringContainsString('DROP KEY `type_name`', $sql[1]);
    }

    public function testDownRestoresOriginalSchemaWhenNamesAreUnique(): void
    {
        $migration = $this->createMigration($this->createConnection(duplicateCount: '0'));
        $migration->down($this->schemaWith(true, true, false));

        $sql = $this->planSql($migration);

        $this->assertCount(3, $sql);
        $this->assertStringContainsString('ADD UNIQUE KEY `type_name` (`type`, `name`)', $sql[0]);
        $this->assertStringContainsString('DROP KEY `type_uniqueName`', $sql[1]);
        $this->assertStringContainsString('DROP COLUMN `uniqueName`', $sql[2]);
    }

    public function testDownAbortsOnDuplicateNamesInsteadOfFailingHalfway(): void
    {
        $migration = $this->createMigration($this->createConnection(duplicateCount: '2'));

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessageMatches('/duplicate|same name/i');

        $migration->down($this->schemaWith(true, true, false));
    }

    public function testDownIsIdempotentWhenAlreadyReverted(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->expects($this->never())->method('fetchOne');

        $migration = $this->createMigration($connection);
        $migration->down($this->schemaWith(false, false, true));

        $this->assertSame([], $this->planSql($migration));
    }
}
