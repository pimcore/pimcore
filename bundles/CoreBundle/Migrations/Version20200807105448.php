<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200807105448 extends AbstractPimcoreMigration
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
        $this->addSql('ALTER TABLE `quantityvalue_units`
	            DROP FOREIGN KEY `fk_baseunit`;');

        $this->addSql('ALTER TABLE `quantityvalue_units`
                DROP INDEX `fk_baseunit`;');
        $this->addSql('ALTER TABLE `quantityvalue_units`
                CHANGE COLUMN `id` `id` VARCHAR(50) NOT NULL,
                CHANGE COLUMN `baseunit` `baseunit` VARCHAR(50) NULL DEFAULT NULL;
            ');

        $this->addSql('ALTER TABLE `quantityvalue_units`
        	ADD CONSTRAINT `fk_baseunit` FOREIGN KEY (`baseunit`) REFERENCES `quantityvalue_units` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;');

        $this->addSql('ALTER TABLE `quantityvalue_units`
	        CHANGE COLUMN `abbreviation` `abbreviation` VARCHAR(20) NULL DEFAULT NULL AFTER `group`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `quantityvalue_units`
	            DROP FOREIGN KEY `fk_baseunit`;');

        $this->addSql('ALTER TABLE `quantityvalue_units`
                DROP INDEX `fk_baseunit`;');
        $this->addSql('ALTER TABLE `quantityvalue_units`
                            CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                            CHANGE COLUMN `baseunit` `baseunit` INT(11) UNSIGNED NULL DEFAULT NULL;
            ');

        $this->addSql('ALTER TABLE `quantityvalue_units`
        	ADD CONSTRAINT `fk_baseunit` FOREIGN KEY (`baseunit`) REFERENCES `quantityvalue_units` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;');

        $this->addSql('ALTER TABLE `quantityvalue_units`
        	ALTER `abbreviation` DROP DEFAULT;
        ');

        $this->addSql('ALTER TABLE `quantityvalue_units`
	        CHANGE COLUMN `abbreviation` `abbreviation` VARCHAR(20) NOT NULL AFTER `group`;
        ');
    }
}
