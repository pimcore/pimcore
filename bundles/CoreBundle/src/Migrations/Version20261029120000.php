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
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;

/**
 * Adds the sub group vocabulary of a classification store and the column that tags a key assignment
 * with one of its entries.
 *
 * The column is nullable and defaults to NULL, which means no sub group, so every existing key
 * assignment keeps rendering exactly as it does today.
 */
final class Version20261029120000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const TABLE_SUBGROUPS = 'classificationstore_subgroups';

    private const TABLE_RELATIONS = 'classificationstore_relations';

    private const COLUMN = 'subGroup';

    public function getDescription(): string
    {
        return 'Add the classificationstore_subgroups table and the subGroup column on classificationstore_relations';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE_SUBGROUPS)) {
            $this->addSql(
                'CREATE TABLE `' . self::TABLE_SUBGROUPS . '` ('
                . '`id` INT(11) unsigned NOT NULL AUTO_INCREMENT, '
                . '`storeId` INT NULL DEFAULT NULL, '
                . "`name` VARCHAR(190) NOT NULL DEFAULT '', "
                . '`description` VARCHAR(255) NULL DEFAULT NULL, '
                . "`creationDate` INT(11) UNSIGNED NULL DEFAULT '0', "
                . "`modificationDate` INT(11) UNSIGNED NULL DEFAULT '0', "
                . '`identifier` VARCHAR(190) NULL DEFAULT NULL, '
                . 'PRIMARY KEY (`id`), '
                . 'INDEX `storeId` (`storeId`), '
                . 'INDEX `name` (`name`), '
                . 'UNIQUE INDEX `storeId_identifier` (`storeId`, `identifier`)'
                . ') DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;'
            );
        }

        if (!$schema->getTable(self::TABLE_RELATIONS)->hasColumn(self::COLUMN)) {
            $this->addSql(
                'ALTER TABLE `' . self::TABLE_RELATIONS . '` '
                . 'ADD COLUMN `subGroup` VARCHAR(190) NULL DEFAULT NULL, '
                . 'ADD INDEX `subGroup` (`subGroup`);'
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache(self::TABLE_RELATIONS);
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable(self::TABLE_RELATIONS)->hasColumn(self::COLUMN)) {
            $this->addSql(
                'ALTER TABLE `' . self::TABLE_RELATIONS . '` '
                . 'DROP INDEX `subGroup`, DROP COLUMN `subGroup`;'
            );
        }

        if ($schema->hasTable(self::TABLE_SUBGROUPS)) {
            $this->addSql('DROP TABLE `' . self::TABLE_SUBGROUPS . '`;');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache(self::TABLE_RELATIONS);
    }

    private function resetValidTableColumnsCache(string $table): void
    {
        $cacheKey = self::CACHEKEY . $table;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }

        Cache::clearTags(['system', 'resource']);
    }
}
