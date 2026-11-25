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
 * Adds the storage engine of a classification store to its configuration.
 *
 * The column is nullable and defaults to NULL, which means the default (EAV) engine, so every
 * existing store keeps its current storage.
 */
final class Version20261028120000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    private const TABLE = 'classificationstore_stores';

    public function getDescription(): string
    {
        return 'Add the engine column to classificationstore_stores';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable(self::TABLE)->hasColumn('engine')) {
            $this->addSql('ALTER TABLE `classificationstore_stores` ADD COLUMN `engine` VARCHAR(32) NULL DEFAULT NULL');
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache(self::TABLE);
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable(self::TABLE)->hasColumn('engine')) {
            $this->addSql('ALTER TABLE `classificationstore_stores` DROP COLUMN `engine`');
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
