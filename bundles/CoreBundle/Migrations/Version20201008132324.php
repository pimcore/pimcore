<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20201008132324 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('RENAME TABLE locks TO PLEASE_DELETE__locks;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('RENAME TABLE PLEASE_DELETE__locks TO locks;');
    }
}
