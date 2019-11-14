<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20191114132014 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `documents_hardlink` ADD INDEX `sourceId` (`sourceId`)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // no need to revert that change (it's kind of a bug that this index is missing)
    }
}
