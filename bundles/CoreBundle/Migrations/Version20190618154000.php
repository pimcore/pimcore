<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190618154000 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            'ALTER TABLE `users` 
            CHANGE COLUMN `classes` `classes` TEXT NULL DEFAULT NULL;'
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            'ALTER TABLE `users` 
            CHANGE COLUMN `classes` `classes` VARCHAR(255) NULL DEFAULT NULL;'
        );
    }
}
