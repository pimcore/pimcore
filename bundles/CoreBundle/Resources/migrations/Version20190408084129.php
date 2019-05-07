<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190408084129 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `quantityvalue_units`
            CHANGE `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            CHANGE `baseunit` `baseunit` INT(11) UNSIGNED DEFAULT NULL,
            ADD `converter` VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE `quantityvalue_units`
            ADD CONSTRAINT `fk_baseunit`
            FOREIGN KEY (`baseunit`)
            REFERENCES `quantityvalue_units` (`id`)
            ON DELETE SET NULL
            ON UPDATE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `quantityvalue_units`
            DROP FOREIGN KEY `fk_baseunit`,
            DROP `converter`,
            CHANGE `baseunit` `baseunit` VARCHAR(10)');
    }
}
