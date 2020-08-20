<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200820151104 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        try {
            $this->addSql('ALTER TABLE `tags` CHANGE COLUMN `name` `name` varchar(255) DEFAULT NULL COLLATE utf8_bin AFTER `idPath`');
            $this->addSql('ALTER TABLE `tags` ADD UNIQUE INDEX `idPath_name` (`idPath`,`name`)');
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        try {
            $this->addSql('ALTER TABLE `tags` CHANGE COLUMN `name` `name` varchar(255) DEFAULT NULL AFTER `idPath`');
            $this->addSql('ALTER TABLE `tags` DROP INDEX `idPath_name`');
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
