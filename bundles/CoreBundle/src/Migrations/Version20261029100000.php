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
 * Adds the table holding the translated titles and descriptions of classification store entities.
 *
 * `entityId` is a signed INT, not the unsigned one the specification sketched: the column addresses
 * four tables, and `classificationstore_stores`.`id` is a signed INT(11) while groups, keys and
 * collections are unsigned - one unsigned column cannot faithfully hold every id it points at. No
 * foreign key is possible from a polymorphic column either way.
 *
 * `subgroup` is a member of the enum although no sub-group entity exists yet, so that labels
 * imported for sub-groups are stored rather than refused.
 */
final class Version20261029100000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const TABLE = 'classificationstore_labels';

    public function getDescription(): string
    {
        return 'Add the classificationstore_labels table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE)) {
            return;
        }

        $this->addSql(
            'CREATE TABLE `classificationstore_labels` ('
            . '`entityType` ENUM(\'store\',\'collection\',\'group\',\'subgroup\',\'key\') NOT NULL, '
            . '`entityId` INT(11) NOT NULL, '
            . '`language` VARCHAR(10) NOT NULL, '
            . '`title` VARCHAR(255) NULL DEFAULT NULL, '
            . '`description` TEXT NULL, '
            . 'PRIMARY KEY (`entityType`, `entityId`, `language`)'
            . ') DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci'
        );
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache(self::TABLE);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE)) {
            $this->addSql('DROP TABLE `classificationstore_labels`');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache(self::TABLE);
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
