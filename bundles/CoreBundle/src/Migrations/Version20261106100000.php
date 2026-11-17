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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Makes `idx_users_workspaces_list_permission` on the three workspace tables unique, as
 * install.sql has declared it since it was introduced.
 *
 * Version20241114142759 added the index as a plain index, while install.sql (same change, #17847)
 * creates it as UNIQUE, so every upgraded installation differs from a fresh one. Making it unique
 * cannot fail on existing data: each table also has the unique key `cpath_userId` (`cpath`,
 * `userId`), which already makes (`userId`, `cpath`, `list`) unique.
 *
 * @internal
 */
final class Version20261106100000 extends AbstractMigration
{
    private const TABLES = ['users_workspaces_asset', 'users_workspaces_document', 'users_workspaces_object'];

    private const INDEX_NAME = 'idx_users_workspaces_list_permission';

    private const INDEX_COLUMNS = '`userId`, `cpath`, `list`';

    public function getDescription(): string
    {
        return 'Make idx_users_workspaces_list_permission unique on the workspace tables, as on a fresh install';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);
            if (!$table->hasIndex(self::INDEX_NAME)) {
                $this->addSql(sprintf('ALTER TABLE `%s` ADD UNIQUE INDEX `%s` (%s)', $tableName, self::INDEX_NAME, self::INDEX_COLUMNS));
            } elseif (!$table->getIndex(self::INDEX_NAME)->isUnique()) {
                // One statement, so permission lookups are never left without the index.
                $this->addSql(sprintf(
                    'ALTER TABLE `%1$s` DROP INDEX `%2$s`, ADD UNIQUE INDEX `%2$s` (%3$s)',
                    $tableName,
                    self::INDEX_NAME,
                    self::INDEX_COLUMNS
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Nothing to revert: install.sql has declared the index as unique since it was introduced, so the
        // unique index is the correct state on every installation. Fresh installations only mark this
        // migration as executed without running up(), so converting the index back to a plain one here
        // would change a schema up() never touched.
        $this->write('Nothing to revert: idx_users_workspaces_list_permission stays unique, as install.sql declares it.');
    }
}
