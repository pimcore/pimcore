<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181214145532 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->writeMessage('Changing Database schema for notifications.');

        $this->addSql("DROP TABLE IF EXISTS `notifications`;
            CREATE TABLE `notifications` (
                `id` INT(11)  AUTO_INCREMENT PRIMARY KEY,
              `type` VARCHAR(20) DEFAULT 'info' NOT NULL,
              `title` VARCHAR(250) DEFAULT '' NOT NULL,
              `message` TEXT NOT NULL,
              `sender` INT(11) NULL,
              `recipient` INT(11) NOT NULL,
              `read` TINYINT(1) default '0' NOT NULL,
              `creationDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `modificationDate` TIMESTAMP NULL,
              `linkedElementType` ENUM('document', 'asset', 'object') NULL,
              `linkedElement` INT(11) NULL
            )
            DEFAULT CHARSET=utf8mb4;"
        );
        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES ('notifications'), ('notifications_send');");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE `notifications`;');
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'notifications' OR `key` = 'notifications_send';");
    }
}
