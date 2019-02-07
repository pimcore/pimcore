<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181126144341 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE documents_elements SET type = 'relation' WHERE type = 'href'");
        $this->addSql("UPDATE documents_elements SET type = 'relations' WHERE type = 'multihref'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("UPDATE documents_elements SET type = 'href' WHERE type = 'relation'");
        $this->addSql("UPDATE documents_elements SET type = 'multihref' WHERE type = 'relations'");
    }
}
