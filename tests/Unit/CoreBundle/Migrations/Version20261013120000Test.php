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
use Doctrine\Migrations\Exception\AbortMigration;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261013120000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * Regression test for the migration adding the `date` member to the `properties`.`type` enum.
 *
 * Without it the value is coerced to '' on save (sql_mode='' is the Pimcore default), so a date
 * property loses its type and comes back as the raw serialized string. The migration extends the
 * column's *current* definition instead of replacing it, so members added by a project survive,
 * and it must be a no-op on installs that already have the member (fresh installs, forward merges).
 */
class Version20261013120000Test extends TestCase
{
    private const LEGACY_TYPE = "enum('text','document','asset','object','bool','select')";

    /**
     * @param array<string, string>|false $column  result of the information_schema lookup
     */
    private function createMigration(array|false $column, string $dateRows = '0'): Version20261013120000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('fetchAssociative')->willReturn($column);
        $connection->method('fetchOne')->willReturn($dateRows);
        $connection->method('quote')->willReturnCallback(
            static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'"
        );

        return new Version20261013120000($connection, new NullLogger());
    }

    /**
     * @return string[]
     */
    private function planSql(Version20261013120000 $migration): array
    {
        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    public function testUpAppendsDateToTheExistingEnum(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => self::LEGACY_TYPE, 'IS_NULLABLE' => 'YES', 'COLUMN_DEFAULT' => null]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `properties` MODIFY `type` enum('text','document','asset','object','bool','select','date') DEFAULT NULL"],
            $this->planSql($migration)
        );
    }

    public function testUpTreatsTheMariaDbNullDefaultAsNoDefault(): void
    {
        // MariaDB reports `DEFAULT NULL` as the string 'NULL', MySQL as SQL NULL
        $migration = $this->createMigration(['COLUMN_TYPE' => self::LEGACY_TYPE, 'IS_NULLABLE' => 'YES', 'COLUMN_DEFAULT' => 'NULL']);
        $migration->up($this->createStub(Schema::class));

        $this->assertCount(1, $this->planSql($migration));
    }

    public function testUpLeavesAColumnWithACustomDefaultUntouched(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => self::LEGACY_TYPE, 'IS_NULLABLE' => 'YES', 'COLUMN_DEFAULT' => "'text'"]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpAddsDateWhenAnotherMemberOnlyContainsItAsQuotedFragment(): void
    {
        // the member `prefix'date` is reported as 'prefix''date' and contains the token 'date'
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','prefix''date')",
            'IS_NULLABLE' => 'YES',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `properties` MODIFY `type` enum('text','prefix''date','date') DEFAULT NULL"],
            $this->planSql($migration)
        );
    }

    public function testUpKeepsMembersAddedByAProject(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','document','asset','object','bool','select','custom')",
            'IS_NULLABLE' => 'YES',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `properties` MODIFY `type` enum('text','document','asset','object','bool','select','custom','date') DEFAULT NULL"],
            $this->planSql($migration)
        );
    }

    public function testUpKeepsCharsetCollationAndCommentOfTheColumn(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'YES',
            'CHARACTER_SET_NAME' => 'utf8mb4',
            'COLLATION_NAME' => 'utf8mb4_bin',
            'COLUMN_COMMENT' => "owner's note",
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `properties` MODIFY `type` enum('text','document','asset','object','bool','select','date') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'owner''s note'"],
            $this->planSql($migration)
        );
    }

    public function testUpNeverInterpolatesAnUnexpectedCharsetName(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'YES',
            'CHARACTER_SET_NAME' => 'utf8mb4; DROP TABLE `users`',
            'COLLATION_NAME' => 'utf8mb4_bin',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertStringNotContainsString('DROP TABLE', $this->planSql($migration)[0]);
        $this->assertStringNotContainsString('CHARACTER SET', $this->planSql($migration)[0]);
    }

    public function testUpKeepsNotNullColumns(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => self::LEGACY_TYPE, 'IS_NULLABLE' => 'NO']);
        $migration->up($this->createStub(Schema::class));

        $this->assertStringEndsWith(' NOT NULL', $this->planSql($migration)[0]);
    }

    public function testUpIsIdempotentWhenDateIsAlreadyAMember(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','document','asset','object','bool','select','date')",
            'IS_NULLABLE' => 'YES',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpIgnoresAColumnThatIsNotAnEnum(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => 'varchar(32)', 'IS_NULLABLE' => 'YES']);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpIgnoresAMissingColumn(): void
    {
        $migration = $this->createMigration(false);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testDownRemovesTheDateMemberWhenNoRowUsesIt(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','document','asset','object','bool','select','date')",
            'IS_NULLABLE' => 'YES',
        ]);
        $migration->down($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `properties` MODIFY `type` enum('text','document','asset','object','bool','select') DEFAULT NULL"],
            $this->planSql($migration)
        );
    }

    public function testDownOnlyRemovesTheExactDateMember(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','prefix''date','date')",
            'IS_NULLABLE' => 'YES',
        ]);
        $migration->down($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `properties` MODIFY `type` enum('text','prefix''date') DEFAULT NULL"],
            $this->planSql($migration)
        );
    }

    public function testDownAbortsWhenDateIsTheOnlyMember(): void
    {
        // an enum without members is invalid SQL
        $migration = $this->createMigration(['COLUMN_TYPE' => "enum('date')", 'IS_NULLABLE' => 'YES']);

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessageMatches('/only member/i');

        $migration->down($this->createStub(Schema::class));
    }

    public function testDownLeavesAColumnWithACustomDefaultUntouched(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','date')",
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => "'text'",
        ]);
        $migration->down($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testDownAbortsInsteadOfCoercingExistingDateRows(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('text','document','asset','object','bool','select','date')",
            'IS_NULLABLE' => 'YES',
        ], dateRows: '3');

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessageMatches('/date/i');

        $migration->down($this->createStub(Schema::class));
    }

    public function testDownIsIdempotentWhenAlreadyReverted(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => self::LEGACY_TYPE, 'IS_NULLABLE' => 'YES']);
        $migration->down($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }
}
