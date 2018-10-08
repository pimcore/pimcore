<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181005070500 extends AbstractPimcoreMigration
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
        $db->query('ALTER TABLE `objects` ADD COLUMN `o_modificationDateMicros` INT NULL DEFAULT \'0\' AFTER `o_childrenSortBy`;');
        $db->query('ALTER TABLE `documents`ADD COLUMN `modificationDateMicros` INT NULL DEFAULT \'0\' AFTER `userModification`;');
        $db->query('ALTER TABLE `assets` ADD COLUMN `modificationDateMicros` INT NOT NULL DEFAULT \'0\' AFTER `hasMetaData`;');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $db = Db::get();
        $db->query('ALTER TABLE `objects` DROP COLUMN `o_modificationDateMicros`;');
        $db->query('ALTER TABLE `documents` DROP COLUMN `modificationDateMicros`;');
        $db->query('ALTER TABLE `assets` DROP COLUMN `modificationDateMicros`;');

    }
}
