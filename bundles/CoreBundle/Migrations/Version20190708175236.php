<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190708175236 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        //cleanup incorrect language records from documents_translations table
        $this->addSql("DELETE FROM documents_translations WHERE language = ''");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
