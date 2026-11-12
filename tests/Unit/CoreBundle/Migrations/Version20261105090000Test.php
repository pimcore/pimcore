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
use Pimcore\Bundle\CoreBundle\Migrations\Version20261105090000;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the migration adding the `serviceuser` member to the `users`.`type` enum.
 *
 * The migration extends the column's current definition, so members a project added survive, re-states the
 * column's default, character set, collation and comment, is a no-op when the member exists, and refuses to
 * remove the member while service users exist.
 */
class Version20261105090000Test extends TestCase
{
    private const LEGACY_TYPE = "enum('user','userfolder','role','rolefolder')";

    /**
     * @param array<string, string|null>|false $column result of the information_schema lookup
     */
    private function createMigration(array|false $column, string $serviceUsers = '0'): Version20261105090000
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($this->createStub(AbstractPlatform::class));
        $connection->method('createSchemaManager')->willReturn($this->createStub(AbstractSchemaManager::class));
        $connection->method('fetchAssociative')->willReturn($column);
        $connection->method('fetchOne')->willReturn($serviceUsers);
        $connection->method('quote')->willReturnCallback(
            static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'"
        );

        return new Version20261105090000($connection, new NullLogger());
    }

    /**
     * @return string[]
     */
    private function planSql(Version20261105090000 $migration): array
    {
        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    public function testUpAppendsServiceUserAndRestatesNotNullAndTheMariaDbDefault(): void
    {
        // MariaDB reports a string default quoted
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => "'user'",
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `users` MODIFY `type` enum('user','userfolder','role','rolefolder','serviceuser')"
                . " NOT NULL DEFAULT 'user'"],
            $this->planSql($migration)
        );
    }

    public function testUpRestatesTheMySqlDefault(): void
    {
        // MySQL reports the bare default value
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => 'user',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertStringEndsWith(" NOT NULL DEFAULT 'user'", $this->planSql($migration)[0]);
    }

    public function testUpTreatsTheMariaDbNullDefaultAsNoDefault(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => 'NULL',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertStringEndsWith(' NULL DEFAULT NULL', $this->planSql($migration)[0]);
    }

    public function testUpKeepsMembersAddedByAProject(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('user','userfolder','role','rolefolder','custom','it''s')",
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => "'user'",
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `users` MODIFY `type` enum('user','userfolder','role','rolefolder','custom','it''s',"
                . "'serviceuser') NOT NULL DEFAULT 'user'"],
            $this->planSql($migration)
        );
    }

    public function testUpKeepsCharsetCollationAndCommentOfTheColumn(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => "'user'",
            'CHARACTER_SET_NAME' => 'utf8mb4',
            'COLLATION_NAME' => 'utf8mb4_bin',
            'COLUMN_COMMENT' => "owner's note",
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `users` MODIFY `type` enum('user','userfolder','role','rolefolder','serviceuser')"
                . " CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT 'user' COMMENT 'owner''s note'"],
            $this->planSql($migration)
        );
    }

    public function testUpNeverInterpolatesAnUnexpectedCollationName(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => self::LEGACY_TYPE,
            'IS_NULLABLE' => 'NO',
            'CHARACTER_SET_NAME' => 'utf8mb4',
            'COLLATION_NAME' => 'utf8mb4_bin; DROP TABLE `users`',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertStringNotContainsString('DROP TABLE', $this->planSql($migration)[0]);
        $this->assertStringNotContainsString('COLLATE', $this->planSql($migration)[0]);
    }

    public function testUpAddsServiceUserWhenAnotherMemberOnlyContainsItAsQuotedFragment(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('user','x''serviceuser')",
            'IS_NULLABLE' => 'NO',
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `users` MODIFY `type` enum('user','x''serviceuser','serviceuser') NOT NULL"],
            $this->planSql($migration)
        );
    }

    public function testUpIsIdempotentWhenServiceUserIsAlreadyAMember(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('user','userfolder','role','rolefolder','serviceuser')",
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => "'user'",
        ]);
        $migration->up($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }

    public function testUpIgnoresAMissingOrNonEnumColumn(): void
    {
        $missing = $this->createMigration(false);
        $missing->up($this->createStub(Schema::class));
        $varchar = $this->createMigration(['COLUMN_TYPE' => 'varchar(20)', 'IS_NULLABLE' => 'NO']);
        $varchar->up($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($missing));
        $this->assertSame([], $this->planSql($varchar));
    }

    public function testDownRemovesOnlyTheServiceUserMember(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('user','x''serviceuser','serviceuser','custom')",
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => "'user'",
        ]);
        $migration->down($this->createStub(Schema::class));

        $this->assertSame(
            ["ALTER TABLE `users` MODIFY `type` enum('user','x''serviceuser','custom') NOT NULL DEFAULT 'user'"],
            $this->planSql($migration)
        );
    }

    public function testDownAbortsWhileServiceUsersExist(): void
    {
        $migration = $this->createMigration([
            'COLUMN_TYPE' => "enum('user','userfolder','role','rolefolder','serviceuser')",
            'IS_NULLABLE' => 'NO',
            'COLUMN_DEFAULT' => "'user'",
        ], serviceUsers: '2');

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessageMatches('/2 service user/');

        $migration->down($this->createStub(Schema::class));
    }

    public function testDownAbortsWhenServiceUserIsTheOnlyMember(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => "enum('serviceuser')", 'IS_NULLABLE' => 'YES']);

        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessageMatches('/only member/');

        $migration->down($this->createStub(Schema::class));
    }

    public function testDownIsIdempotentWhenAlreadyReverted(): void
    {
        $migration = $this->createMigration(['COLUMN_TYPE' => self::LEGACY_TYPE, 'IS_NULLABLE' => 'NO']);
        $migration->down($this->createStub(Schema::class));

        $this->assertSame([], $this->planSql($migration));
    }
}
