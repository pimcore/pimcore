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
 * Adds the per key localization flag.
 *
 * The column is nullable with a NULL default, which is the state every existing key keeps: NULL
 * means the key inherits the `localized` flag of the class definition field it is rendered in, so
 * no backfill is needed and nothing changes for an installation that writes no flag. A
 * `NOT NULL DEFAULT 0` column could not express that inheritance.
 */
final class Version20261105100000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const COLUMN = 'localized';

    private const TABLE_KEYS = 'classificationstore_keys';

    public function getDescription(): string
    {
        return 'Add the localized column to the classification store keys';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable(self::TABLE_KEYS)->hasColumn(self::COLUMN)) {
            $this->addSql(
                'ALTER TABLE `' . self::TABLE_KEYS . '` '
                . 'ADD COLUMN `localized` TINYINT(1) NULL DEFAULT NULL;'
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable(self::TABLE_KEYS)->hasColumn(self::COLUMN)) {
            $this->addSql('ALTER TABLE `' . self::TABLE_KEYS . '` DROP COLUMN `localized`;');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache();
    }

    private function resetValidTableColumnsCache(): void
    {
        $cacheKey = self::CACHEKEY . self::TABLE_KEYS;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }

        Cache::clearTags(['system', 'resource']);
    }
}
