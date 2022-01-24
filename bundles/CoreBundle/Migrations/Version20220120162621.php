<?php

declare(strict_types=1);

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
