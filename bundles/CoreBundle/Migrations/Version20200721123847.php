<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200721123847 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Exception
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `search_backend_data` CHANGE `published` `published` tinyint(1) unsigned NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `search_backend_data` CHANGE `published` `published` int(11) unsigned NULL');
    }
}
