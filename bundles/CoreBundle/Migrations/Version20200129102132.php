<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200129102132 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `tags` ROW_FORMAT=DYNAMIC;');
        $this->addSql('ALTER TABLE `tags` ADD INDEX `name` (`name`);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `tags` DROP INDEX `name`;');
        $this->addSql('ALTER TABLE `tags` ROW_FORMAT=DEFAULT;');
    }
}
