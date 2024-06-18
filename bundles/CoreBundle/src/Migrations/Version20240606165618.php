<?php

declare(strict_types=1);

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
