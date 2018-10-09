<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20181008132414 extends AbstractPimcoreMigration
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
        $db = Db::get();
        $db->query('ALTER TABLE `objects` ADD COLUMN `o_versionCount` INT UNSIGNED NULL DEFAULT \'0\' AFTER `o_childrenSortBy`;');
        $db->query('ALTER TABLE `documents`ADD COLUMN `versionCount` INT UNSIGNED NULL DEFAULT \'0\' AFTER `userModification`;');
        $db->query('ALTER TABLE `assets` ADD COLUMN `versionCount` INT UNSIGNED NOT NULL DEFAULT \'0\' AFTER `hasMetaData`;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $db = Db::get();
        $db->query('ALTER TABLE `objects` DROP COLUMN `o_versionCount`;');
        $db->query('ALTER TABLE `documents` DROP COLUMN `versionCount`;');
        $db->query('ALTER TABLE `assets` DROP COLUMN `versionCount`;');

    }
}
