<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210330132354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `users_workspaces_asset` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_bin' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_document` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_bin' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_object` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_bin' NULL AFTER `cid`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `users_workspaces_asset` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_general_ci' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_document` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_general_ci' NULL AFTER `cid`;");
        $this->addSql("ALTER TABLE `users_workspaces_object` CHANGE `cpath` `cpath` varchar(765) COLLATE 'utf8_general_ci' NULL AFTER `cid`;");
    }
}
