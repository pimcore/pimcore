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

final class Version20240606165618 extends AbstractMigration
{
    const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Adds the Date Time Locale column to the Users table.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('users')->hasColumn('datetimeLocale')) {
            $this->addSql('ALTER TABLE `users` ADD COLUMN `datetimeLocale` varchar(10) AFTER `language`;');
            $this->resetValidTableColumnsCache('users');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('users')->hasColumn('datetimeLocale')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `datetimeLocale`;');
            $this->resetValidTableColumnsCache('users');
        }
    }

    public function resetValidTableColumnsCache(string $table): void
    {
        $cacheKey = self::CACHEKEY . $table;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
        Cache::clearTags(['system', 'resource']);
    }
}
