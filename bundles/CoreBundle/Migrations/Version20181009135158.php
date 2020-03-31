<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20181009135158 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return true;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `versions` ADD COLUMN `versionCount` INT UNSIGNED NULL DEFAULT \'0\' AFTER `serialized`;');
        $this->addSql('UPDATE `assets` SET versionCount = 0;');
        $this->addSql('UPDATE `documents` SET versionCount = 0;');
        $this->addSql('UPDATE `objects` SET o_versionCount = 0;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `versions` DROP COLUMN `versionCount`;');
    }
}
