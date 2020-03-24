<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190701141857 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `documents_hardlink` CHANGE COLUMN `childsFromSource` `childrenFromSource` TINYINT(1) NULL DEFAULT NULL AFTER `propertiesFromSource`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `documents_hardlink` CHANGE COLUMN `childrenFromSource` `childsFromSource` TINYINT(1) NULL DEFAULT NULL AFTER `propertiesFromSource`;');
    }
}
