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
        //disable foreign key checks
        $this->addSql('SET foreign_key_checks = 0');

        if (!$schema->getTable('users_workspaces_asset')->hasForeignKey('fk_users_workspaces_asset_assets')) {
            $this->addSql(
                'ALTER TABLE `users_workspaces_asset`
                ADD CONSTRAINT `fk_users_workspaces_asset_assets`
                FOREIGN KEY (`cid`)
                REFERENCES `assets` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        $this->addSql(
            'ALTER TABLE `users_workspaces_asset`
            CHANGE `userId` `userId` int(11) unsigned NOT NULL DEFAULT \'0\';'
        );

        if (!$schema->getTable('users_workspaces_asset')->hasForeignKey('fk_users_workspaces_asset_users')) {
            $this->addSql(
                'ALTER TABLE `users_workspaces_asset`
                ADD CONSTRAINT `fk_users_workspaces_asset_users`
                FOREIGN KEY (`userId`)
                REFERENCES `users` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        if (!$schema->getTable('users_workspaces_document')->hasForeignKey('fk_users_workspaces_document_documents')) {
            $this->addSql(
                'ALTER TABLE `users_workspaces_document`
                ADD CONSTRAINT `fk_users_workspaces_document_documents`
                FOREIGN KEY (`cid`)
                REFERENCES `documents` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        $this->addSql(
            'ALTER TABLE `users_workspaces_document`
            CHANGE `userId` `userId` int(11) unsigned NOT NULL DEFAULT \'0\';');

        if (!$schema->getTable('users_workspaces_document')->hasForeignKey('fk_users_workspaces_document_users')) {
            $this->addSql(
                'ALTER TABLE `users_workspaces_document`
                ADD CONSTRAINT `fk_users_workspaces_document_users`
                FOREIGN KEY (`userId`)
                REFERENCES `users` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        if (!$schema->getTable('users_workspaces_object')->hasForeignKey('fk_users_workspaces_object_objects')) {
            $this->addSql(
                'ALTER TABLE `users_workspaces_object`
                ADD CONSTRAINT `fk_users_workspaces_object_objects`
                FOREIGN KEY (`cid`)
                REFERENCES `objects` (`o_id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        $this->addSql(
            'ALTER TABLE `users_workspaces_object`
            CHANGE `userId` `userId` int(11) unsigned NOT NULL DEFAULT \'0\';');

        if (!$schema->getTable('users_workspaces_object')->hasForeignKey('fk_users_workspaces_object_users')) {
            $this->addSql(
                'ALTER TABLE `users_workspaces_object`
                ADD CONSTRAINT `fk_users_workspaces_object_users`
                FOREIGN KEY (`userId`)
                REFERENCES `users` (`id`)
                ON UPDATE NO ACTION
                ON DELETE CASCADE;'
            );
        }

        //enable foreign key checks
        $this->addSql('SET foreign_key_checks = 1');
    }

    public function down(Schema $schema): void
    {
        foreach (['asset', 'document', 'object'] as $elementType) {
            if ($schema->getTable('users_workspaces_'.$elementType)->hasForeignKey('fk_users_workspaces_'.$elementType.'_'.$elementType.'s')) {
                $this->addSql('ALTER TABLE `users_workspaces_'.$elementType.'` DROP FOREIGN KEY `fk_users_workspaces_'.$elementType.'_'.$elementType.'s`');
            }

            if ($schema->getTable('users_workspaces_'.$elementType)->hasForeignKey('fk_users_workspaces_'.$elementType.'_users')) {
                $this->addSql('ALTER TABLE `users_workspaces_'.$elementType.'` DROP FOREIGN KEY `fk_users_workspaces_'.$elementType.'_users`');
            }

            $this->addSql('ALTER TABLE `users_workspaces_'.$elementType.'` CHANGE `userId` `userId` int(11) NOT NULL DEFAULT \'0\'');
        }
    }
}
