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

final class Version20260623090000 extends AbstractMigration
{
    const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Add theme column to users table';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('users')->hasColumn('theme')) {
            $this->addSql("ALTER TABLE `users` ADD COLUMN `theme` varchar(255) NOT NULL DEFAULT 'default';");
            $this->resetValidTableColumnsCache('users');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('users')->hasColumn('theme')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `theme`;');
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
