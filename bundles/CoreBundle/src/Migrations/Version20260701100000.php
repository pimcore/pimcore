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

final class Version20260701100000 extends AbstractMigration
{
    const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Add coauthorType and coauthor columns to versions table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('versions')->hasColumn('coauthorType')) {
            $this->addSql(
                'ALTER TABLE `versions` '
                . 'ADD COLUMN `coauthorType` VARCHAR(50) NULL DEFAULT NULL, '
                . 'ADD COLUMN `coauthor` VARCHAR(255) NULL DEFAULT NULL;'
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('versions');
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('versions')->hasColumn('coauthorType')) {
            $this->addSql('ALTER TABLE `versions` DROP COLUMN `coauthorType`, DROP COLUMN `coauthor`;');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('versions');
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
