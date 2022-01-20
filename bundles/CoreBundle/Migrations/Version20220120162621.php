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
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `users_workspaces_document`
            ADD CONSTRAINT `fk_users_workspaces_document_documents`
            FOREIGN KEY (`cid`)
            REFERENCES `documents` (`id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE `users_workspaces_object`
            ADD CONSTRAINT `fk_users_workspaces_object_objects`
            FOREIGN KEY (`cid`)
            REFERENCES `objects` (`o_id`)
            ON UPDATE NO ACTION
            ON DELETE CASCADE;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users_workspaces_asset` DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_asset_assets`');

        $this->addSql('ALTER TABLE `users_workspaces_document` DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_document_documents`');

        $this->addSql('ALTER TABLE `users_workspaces_object` DROP FOREIGN KEY IF EXISTS `fk_users_workspaces_object_objects`');
    }
}
