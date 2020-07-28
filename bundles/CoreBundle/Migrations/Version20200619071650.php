<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200619071650 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `assets_metadata` CHANGE COLUMN `data` `data` LONGTEXT NULL DEFAULT NULL AFTER `type`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `assets_metadata` CHANGE COLUMN `data` `data` TEXT NULL DEFAULT NULL AFTER `type`;');
    }
}
