<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Class Version20200922140344
 * @package Pimcore\Bundle\CoreBundle\Migrations
 */
class Version20200922140344 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT IGNORE INTO users_permission_definitions (`key`, `category`) VALUES ('direct_sql_query', '');");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'direct_sql_query';");
    }
}
