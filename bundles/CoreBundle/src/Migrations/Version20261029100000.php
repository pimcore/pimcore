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
 * Adds the per assignment columns of a classification store key/group relation.
 *
 * All three are nullable and carry no value for an existing row, so nothing changes for an
 * installation that does not write them.
 */
final class Version20261029100000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const TABLE = 'classificationstore_relations';

    private const COLUMN_SUB_GROUP = 'subGroup';

    private const COLUMNS = [
        'metadata' => 'JSON NULL',
        'definitionOverride' => 'JSON NULL',
        self::COLUMN_SUB_GROUP => 'VARCHAR(190) NULL DEFAULT NULL',
    ];

    public function getDescription(): string
    {
        return 'Add the metadata, definitionOverride and subGroup columns to classificationstore_relations';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE);
        $changes = [];

        foreach (self::COLUMNS as $column => $type) {
            if (!$table->hasColumn($column)) {
                $changes[] = 'ADD COLUMN `' . $column . '` ' . $type;
            }
        }

        // the column exists instead of a key inside the JSON so that a sub-group can be looked up by index
        if (!$table->hasIndex(self::COLUMN_SUB_GROUP)) {
            $changes[] = 'ADD INDEX `subGroup` (`subGroup`)';
        }

        if ($changes !== []) {
            $this->addSql('ALTER TABLE `' . self::TABLE . '` ' . implode(', ', $changes) . ';');
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE);
        $changes = [];

        if ($table->hasIndex(self::COLUMN_SUB_GROUP)) {
            $changes[] = 'DROP INDEX `subGroup`';
        }

        foreach (array_keys(self::COLUMNS) as $column) {
            if ($table->hasColumn($column)) {
                $changes[] = 'DROP COLUMN `' . $column . '`';
            }
        }

        if ($changes !== []) {
            $this->addSql('ALTER TABLE `' . self::TABLE . '` ' . implode(', ', $changes) . ';');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    private function resetValidTableColumnsCache(): void
    {
        $cacheKey = self::CACHEKEY . self::TABLE;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }

        Cache::clearTags(['system', 'resource']);
    }
}
