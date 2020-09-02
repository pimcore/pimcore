<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200902111642 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Exception
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `element_workflow_state` CHANGE `place` `place` text NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `element_workflow_state` CHANGE `place` `place` varchar(255) NULL;');
    }
}
