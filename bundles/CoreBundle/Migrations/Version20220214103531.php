<?php

declare(strict_types=1);

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220214103531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update the db table for redirects to match the new schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `redirects` ADD `sourceStatusCode` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `sourceSite`");
        $this->addSql("ALTER TABLE `redirects` CHANGE `type` `type` ENUM('entire_uri','path_query','path','auto_create','status_code') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `redirects` CHANGE `type` `type` ENUM('entire_uri','path_query','path','auto_create') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
        $this->addSql("ALTER TABLE `redirects` DROP `sourceStatusCode`");
    }
}
