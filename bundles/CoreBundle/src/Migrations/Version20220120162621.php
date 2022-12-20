<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220120162621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schema->hasExplicitForeignKeyIndexes();

        //disable foreign key checks
        $this->addSql('SET foreign_key_checks = 0');

        $this->addSql('ALTER TABLE `users_workspaces_asset`
            ADD CONSTRAINT `fk_users_workspaces_asset_assets`
            FOREIGN KEY (`cid`)
            REFERENCES `assets` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE,
            CHANGE `userId` `userId` int(11) unsigned NOT NULL DEFAULT \'0\',
            ADD CONSTRAINT `fk_users_workspaces_asset_users`
            FOREIGN KEY (`userId`)
            REFERENCES `users` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `users_workspaces_document`
            ADD CONSTRAINT `fk_users_workspaces_document_documents`
            FOREIGN KEY (`cid`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE,
            CHANGE `userId` `userId` int(11) unsigned NOT NULL DEFAULT \'0\',
            ADD CONSTRAINT `fk_users_workspaces_document_users`
            FOREIGN KEY (`userId`)
            REFERENCES `users` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;;');

        $this->addSql('ALTER TABLE `users_workspaces_object`
            ADD CONSTRAINT `fk_users_workspaces_object_objects`
            FOREIGN KEY (`cid`)
            REFERENCES `objects` (`o_id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE,
            CHANGE `userId` `userId` int(11) unsigned NOT NULL DEFAULT \'0\',
            ADD CONSTRAINT `fk_users_workspaces_object_users`
            FOREIGN KEY (`userId`)
            REFERENCES `users` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;;');

        //enable foreign key checks
        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users_workspaces_asset`
                CHANGE `userId` `userId` int(11) NOT NULL DEFAULT \'0\',
                DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_asset_assets`,
                DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_asset_users`');

        $this->addSql('ALTER TABLE `users_workspaces_document`
                CHANGE `userId` `userId` int(11) NOT NULL DEFAULT \'0\',
                DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_document_documents`,
                DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_document_users`');

        $this->addSql('ALTER TABLE `users_workspaces_object`
                CHANGE `userId` `userId` int(11) NOT NULL DEFAULT \'0\',
                DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_object_objects`,
                DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_object_users`');
    }
}
