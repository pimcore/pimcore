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

namespace Pimcore\Tests\Unit\Db;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Pimcore\Db;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for the shipped `doctrine.dbal.connections.default.default_table_options`.
 *
 * Doctrine DBAL 4 only honours the `collation` key when it renders table options (DBAL 3 still
 * accepted the deprecated `collate` spelling). Core shipped `collate`, so every table created
 * through the Doctrine schema API (bundle installers, ORM schema tool, migrations working on the
 * Schema object) got `DEFAULT CHARACTER SET utf8mb4` without a COLLATE clause and therefore the
 * charset's built-in default collation (utf8mb4_general_ci on MariaDB, utf8mb4_0900_ai_ci on
 * MySQL 8) instead of the configured utf8mb4_unicode_520_ci.
 */
final class DefaultTableCollationTest extends TestCase
{
    private const EXPECTED_COLLATION = 'utf8mb4_unicode_520_ci';

    protected function needsDb(): bool
    {
        return true;
    }

    public function testDefaultTableOptionsUseTheCollationKeyDbalActuallyReads(): void
    {
        $options = Db::get()->getParams()['defaultTableOptions'] ?? [];

        $this->assertArrayHasKey('collation', $options);
        $this->assertSame(self::EXPECTED_COLLATION, $options['collation']);
        $this->assertArrayNotHasKey('collate', $options, 'DBAL 4 ignores the deprecated "collate" key');
    }

    public function testTablesCreatedThroughTheSchemaApiGetTheConfiguredCollation(): void
    {
        $connection = Db::get();
        $schema = new Schema([], [], $connection->createSchemaManager()->createSchemaConfig());

        $table = $schema->createTable('pimcore_test_default_collation');
        $table->addColumn('id', Types::INTEGER);
        $table->addColumn('name', Types::STRING, ['length' => 190]);

        $sql = implode(";\n", $connection->getDatabasePlatform()->getCreateTableSQL($table));

        // DBAL quotes the collation identifier, so match with and without backticks
        $this->assertMatchesRegularExpression(
            '/COLLATE `?' . preg_quote(self::EXPECTED_COLLATION, '/') . '`?/',
            $sql,
            'CREATE TABLE rendered without the configured collation: ' . $sql
        );
    }
}
