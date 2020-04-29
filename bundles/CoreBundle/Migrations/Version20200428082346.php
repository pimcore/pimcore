<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200428082346 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `http_error_log` ROW_FORMAT=DYNAMIC;');
        $this->addSql("ALTER TABLE `http_error_log` CHANGE `uri` `uri` varchar(1024) COLLATE 'utf8_bin' NULL AFTER `id`;");

        $table = $schema->getTable('http_error_log');
        if ($table->hasIndex('uri')) {
            $table->dropIndex('uri');
            $table->addIndex(['uri'], 'uri');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `http_error_log` CHANGE `uri` `uri` varchar(3000) COLLATE 'ascii_general_ci' NULL AFTER `id`;");
        // no need to rollback row format and the index
    }
}
