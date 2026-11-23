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

final class Version20261028100000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const COLUMN = 'identifier';

    private const TABLE_STORES = 'classificationstore_stores';

    private const STORE_SCOPED_TABLES = [
        'classificationstore_collections',
        'classificationstore_groups',
        'classificationstore_keys',
    ];

    public function getDescription(): string
    {
        return 'Add identifier column to the classification store configuration tables';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable(self::TABLE_STORES)->hasColumn(self::COLUMN)) {
            $this->addSql(
                'ALTER TABLE `' . self::TABLE_STORES . '` '
                . 'ADD COLUMN `identifier` VARCHAR(190) NULL DEFAULT NULL, '
                . 'ADD UNIQUE INDEX `identifier` (`identifier`);'
            );
        }

        foreach (self::STORE_SCOPED_TABLES as $table) {
            if (!$schema->getTable($table)->hasColumn(self::COLUMN)) {
                $this->addSql(
                    'ALTER TABLE `' . $table . '` '
                    . 'ADD COLUMN `identifier` VARCHAR(190) NULL DEFAULT NULL, '
                    . 'ADD UNIQUE INDEX `storeId_identifier` (`storeId`, `identifier`);'
                );
            }
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCaches();
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable(self::TABLE_STORES)->hasColumn(self::COLUMN)) {
            $this->addSql(
                'ALTER TABLE `' . self::TABLE_STORES . '` '
                . 'DROP INDEX `identifier`, DROP COLUMN `identifier`;'
            );
        }

        foreach (self::STORE_SCOPED_TABLES as $table) {
            if ($schema->getTable($table)->hasColumn(self::COLUMN)) {
                $this->addSql(
                    'ALTER TABLE `' . $table . '` '
                    . 'DROP INDEX `storeId_identifier`, DROP COLUMN `identifier`;'
                );
            }
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCaches();
    }

    private function resetValidTableColumnsCaches(): void
    {
        foreach ([self::TABLE_STORES, ...self::STORE_SCOPED_TABLES] as $table) {
            $cacheKey = self::CACHEKEY . $table;
            if (RuntimeCache::isRegistered($cacheKey)) {
                RuntimeCache::getInstance()->offsetUnset($cacheKey);
            }
        }

        Cache::clearTags(['system', 'resource']);
    }
}
