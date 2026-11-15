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
 * Adds the change token of a data object: the database sets a new value on every UPDATE that changes the objects row
 * (including plain SQL writes such as moving the children of a renamed folder), and every save renews it explicitly.
 * A save compares it against the token the object was loaded with to detect outdated data,
 * see \Pimcore\Model\DataObject\HydrationLock.
 *
 * Existing rows get the time of the migration. Objects cached before the migration carry no token and are not checked.
 * The listing views of the classes never include the column, so rolling back does not invalidate them.
 */
final class Version20261109120000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Add the database-maintained change token to the objects table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('objects')->hasColumn('changeToken')) {
            $this->addSql(
                'ALTER TABLE `objects` ADD COLUMN `changeToken` DATETIME(6) NOT NULL ' .
                'DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) AFTER `versionCount`'
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('objects');
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('objects')->hasColumn('changeToken')) {
            $this->addSql('ALTER TABLE `objects` DROP COLUMN `changeToken`');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('objects');
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
