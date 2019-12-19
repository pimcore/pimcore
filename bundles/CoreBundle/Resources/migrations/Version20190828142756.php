<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190828142756 extends AbstractPimcoreMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('http_error_log');

        if (!$table->hasColumn('id')) {
            $this->addSql('ALTER TABLE `http_error_log` ADD `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;');
        }
    }

    public function down(Schema $schema)
    {
        $table = $schema->getTable('http_error_log');

        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE `http_error_log` DROP `id`;');
        }
    }
}
