<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20201014101437 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'tag_snippet_management'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`) VALUES('tag_snippet_management');");
    }
}
