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

final class Version20250908143245 extends AbstractMigration
{
    const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Add lastPasswordReset column with default CURRENT_TIMESTAMP to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD lastPasswordReset INT(11) UNSIGNED NULL');
        $this->resetValidTableColumnsCache('users');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP lastPasswordReset');
        $this->resetValidTableColumnsCache('users');
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
