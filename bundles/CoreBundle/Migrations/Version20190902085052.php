<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190902085052 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (!$schema->getTable('users_permission_definitions')->hasColumn('category')) {
            $this->addSql("ALTER TABLE users_permission_definitions ADD `category` varchar(50) NOT NULL DEFAULT '';");
        } else {
            $this->addSql("ALTER TABLE users_permission_definitions CHANGE COLUMN `category` `category` varchar(50) NOT NULL DEFAULT '';");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // no downgrade necessary / bugfix
    }
}
