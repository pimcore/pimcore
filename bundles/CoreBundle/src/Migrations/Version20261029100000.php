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
 * `entityId` is a signed BIGINT, neither the unsigned INT the specification sketched nor a signed
 * one: the column addresses four tables, and `classificationstore_stores`.`id` is a signed INT(11)
 * while groups, keys and collections are INT UNSIGNED. No INT holds both - an unsigned one cannot
 * carry the signed range, a signed one loses the unsigned range above 2147483647 - so a signed
 * BIGINT is the only type covering every id this column points at. Strict SQL mode is off in the
 * default connection, so an id the column cannot hold would be clamped rather than refused and the
 * label would attach to the wrong entity or collide under the primary key. No foreign key is
 * possible from a polymorphic column whichever type it takes.
 *
 * `subgroup` is a member of the enum although no sub-group entity exists yet, so that labels
 * imported for sub-groups are stored rather than refused.
 *
 * `language` is 16 characters wide, which holds the realistic BCP 47 forms - `en_US_POSIX` and
 * `zh_Hant_TW` among them - rather than the 10 the specification sketched; the stored bytes of the
 * ordinary two and five character codes are unchanged.
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
            . '`entityId` BIGINT(20) NOT NULL, '
            . '`language` VARCHAR(16) NOT NULL, '
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
