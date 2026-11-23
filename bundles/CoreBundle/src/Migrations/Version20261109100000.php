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
 * Adds the nullable customMetadata column to the four classification store configuration tables.
 *
 * It carries no value for an existing row, so nothing changes for an installation that does not write it.
 */
final class Version20261109100000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const COLUMN = 'customMetadata';

    private const TABLES = [
        'classificationstore_collections',
        'classificationstore_groups',
        'classificationstore_subgroups',
        'classificationstore_keys',
    ];

    public function getDescription(): string
    {
        return 'Add the customMetadata column to the classification store collections, groups, sub groups and keys';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            if (!$schema->getTable($table)->hasColumn(self::COLUMN)) {
                $this->addSql('ALTER TABLE `' . $table . '` ADD COLUMN `' . self::COLUMN . '` JSON NULL;');
            }
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            if ($schema->getTable($table)->hasColumn(self::COLUMN)) {
                $this->addSql('ALTER TABLE `' . $table . '` DROP COLUMN `' . self::COLUMN . '`;');
            }
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    private function resetValidTableColumnsCache(): void
    {
        foreach (self::TABLES as $table) {
            $cacheKey = self::CACHEKEY . $table;
            if (RuntimeCache::isRegistered($cacheKey)) {
                RuntimeCache::getInstance()->offsetUnset($cacheKey);
            }
        }

        Cache::clearTags(['system', 'resource']);
    }
}
