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
 * Scopes the uniqueness of user/role folder names to their parent folder, while user and role
 * names stay globally unique (they are used as login/security identifiers).
 *
 * The previous UNIQUE KEY `type_name` (`type`, `name`) enforced globally unique names for all
 * types, including `userfolder` and `rolefolder`. It is replaced by a unique index on a stored
 * generated column which resolves to the plain name for users/roles and to `<parentId>/<name>`
 * for folder types. The `/` separator cannot occur in names (see the name validation in
 * \Pimcore\Model\User\AbstractUser::save()), so the scoped values cannot collide.
 *
 * The new constraint is identical to the old one for users/roles and strictly weaker for
 * folders, so this migration cannot fail on existing data.
 */
final class Version20261008090000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Scope user/role folder name uniqueness to the parent folder instead of globally';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('users');

        if (!$table->hasColumn('uniqueName')) {
            $this->addSql(
                'ALTER TABLE `users` ADD COLUMN `uniqueName` varchar(64) GENERATED ALWAYS AS ' .
                "(IF(`type` IN ('userfolder', 'rolefolder'), CONCAT(IFNULL(`parentId`, 0), '/', `name`), `name`)) " .
                'STORED AFTER `name`'
            );
        }

        // add the new unique index before dropping the old one, so there is no window
        // in which user/role name uniqueness is unenforced
        if (!$table->hasIndex('type_uniqueName')) {
            $this->addSql('ALTER TABLE `users` ADD UNIQUE KEY `type_uniqueName` (`type`, `uniqueName`)');
        }

        if ($table->hasIndex('type_name')) {
            $this->addSql('ALTER TABLE `users` DROP KEY `type_name`');
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('users');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');

        if (!$table->hasIndex('type_name')) {
            $duplicates = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM (
                    SELECT `type`, `name` FROM `users` WHERE `name` IS NOT NULL GROUP BY `type`, `name` HAVING COUNT(*) > 1
                ) duplicates'
            );
            $this->abortIf(
                $duplicates > 0,
                'Cannot restore the global unique index users.type_name: folders with the same name exist' .
                ' under different parents. Rename them so that all names are globally unique per type, then retry.'
            );

            $this->addSql('ALTER TABLE `users` ADD UNIQUE KEY `type_name` (`type`, `name`)');
        }

        if ($table->hasIndex('type_uniqueName')) {
            $this->addSql('ALTER TABLE `users` DROP KEY `type_uniqueName`');
        }

        if ($table->hasColumn('uniqueName')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `uniqueName`');
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('users');
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
