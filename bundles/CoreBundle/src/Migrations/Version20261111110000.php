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
 * Makes the name of a user unique across users and service users at the database level.
 *
 * The unique key (`type`, `uniqueName`) allows one user and one service user with the same name.
 * \Pimcore\Model\User::save() refuses that in PHP, but two concurrent saves can both pass that check.
 * The stored generated column `accountName` is the name for the types `user` and `serviceuser` and NULL for
 * roles and folders (NULL values never collide in a unique key), and its unique key is the atomic guard:
 * \Pimcore\Model\User::save() turns its violation into a DuplicateUserNameException.
 *
 * The migration aborts while a user and a service user share a name; rename one of them, then retry.
 */
final class Version20261111110000 extends AbstractMigration
{
    private const CACHEKEY = 'system_resource_columns_';

    public function getDescription(): string
    {
        return 'Make user names unique across users and service users with a unique key on users.accountName';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('users');

        if (!$table->hasColumn('accountName')) {
            $duplicates = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM (
                    SELECT `name` FROM `users` WHERE `type` IN (\'user\', \'serviceuser\') AND `name` IS NOT NULL
                    GROUP BY `name` HAVING COUNT(DISTINCT `type`) > 1
                ) duplicates'
            );
            $this->abortIf(
                $duplicates > 0,
                'Cannot add the unique key users.accountName: a user and a service user share a name.' .
                ' Rename one of them, then retry.'
            );

            $this->addSql(
                'ALTER TABLE `users` ADD COLUMN `accountName` varchar(50) GENERATED ALWAYS AS ' .
                "(IF(`type` IN ('user', 'serviceuser'), `name`, NULL)) STORED AFTER `uniqueName`"
            );
        }

        if (!$table->hasIndex('accountName')) {
            $this->addSql('ALTER TABLE `users` ADD UNIQUE KEY `accountName` (`accountName`)');
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->resetValidTableColumnsCache('users');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');

        if ($table->hasIndex('accountName')) {
            $this->addSql('ALTER TABLE `users` DROP KEY `accountName`');
        }

        if ($table->hasColumn('accountName')) {
            $this->addSql('ALTER TABLE `users` DROP COLUMN `accountName`');
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
