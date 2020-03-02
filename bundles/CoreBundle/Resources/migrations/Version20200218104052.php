<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200218104052 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `redirects` CHANGE COLUMN `type` `type` ENUM('entire_uri','path_query','path','auto_create') NOT NULL AFTER `id`;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `redirects` CHANGE COLUMN `type` `type` VARCHAR(100) NOT NULL AFTER `id`;');
    }
}
