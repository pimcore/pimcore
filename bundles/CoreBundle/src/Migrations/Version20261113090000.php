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

/**
 * Adds the `users_responsibles` table: the users and roles who answer for a service user.
 * `principalId` points at a user or a role depending on `principalType`, so only the service user side is a
 * foreign key; rows of a deleted person or role are removed by the model.
 */
final class Version20261113090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the users_responsibles table (responsible users and roles of a service user)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('users_responsibles')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE `users_responsibles` (
  `serviceUserId` int(11) unsigned NOT NULL,
  `principalType` enum('user','role') NOT NULL,
  `principalId` int(11) unsigned NOT NULL,
  PRIMARY KEY (`serviceUserId`,`principalType`,`principalId`),
  KEY `principal` (`principalType`,`principalId`),
  CONSTRAINT `fk_users_responsibles_users` FOREIGN KEY (`serviceUserId`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci
SQL);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('users_responsibles')) {
            $this->addSql('DROP TABLE `users_responsibles`');
        }
    }
}
