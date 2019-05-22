<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190515130651 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('
            ALTER TABLE `assets_metadata` CHANGE `language` `language` varchar(10) COLLATE \'ascii_general_ci\' NULL AFTER `cid`;
            ALTER TABLE `assets_metadata` ADD PRIMARY KEY `cid_name_language` (`cid`, `name`, `language`);
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('
            ALTER TABLE `assets_metadata` DROP INDEX `PRIMARY`;
            ALTER TABLE `assets_metadata` CHANGE `language` `language` varchar(190) COLLATE \'utf8mb4_general_ci\' NOT NULL AFTER `cid`;
        ');
    }
}
