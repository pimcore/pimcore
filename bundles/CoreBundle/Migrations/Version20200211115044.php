<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200211115044 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $db = Db::get();
        // remove index from primary key in relations tables
        $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
        foreach ($relationTables as $table) {
            try {
                $relationTable = current($table);

                if (!$schema->getTable($relationTable)->hasIndex('forward_lookup')) {
                    $this->addSql('ALTER TABLE `' . $relationTable . '`
                        DROP PRIMARY KEY,
                        DROP INDEX `index`,
                        DROP INDEX `dest_id`,
                        DROP INDEX `fieldname`,
                        DROP INDEX `position`,
                        DROP INDEX `ownertype`,
                        DROP INDEX `type`,
                        DROP INDEX `ownername`,
                        ADD INDEX `forward_lookup` (`src_id`, `ownertype`, `ownername`, `position`),
                        ADD INDEX `reverse_lookup` (`dest_id`, `type`);');
                }
            } catch (\Exception $e) {
                $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $db = Db::get();
        // remove index from primary key in relations tables
        $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
        foreach ($relationTables as $table) {
            try {
                $relationTable = current($table);

                if ($schema->getTable($relationTable)->hasIndex('forward_lookup')) {
                    $this->addSql('ALTER TABLE `' . $relationTable . '`
                    DROP INDEX `forward_lookup`,
                    DROP INDEX `reverse_lookup`,
                    ADD PRIMARY KEY (`src_id`, `dest_id`, `ownertype`, `ownername`, `fieldname`, `type`, `position`, `index`),
                    ADD INDEX `index` (`index`),
                    ADD INDEX `dest_id` (`dest_id`),
                    ADD INDEX `fieldname` (`fieldname`),
                    ADD INDEX `position` (`position`),
                    ADD INDEX `ownertype` (`ownertype`),
                    ADD INDEX `type` (`type`),
                    ADD INDEX `ownername` (`ownername`);');
                }
            } catch (\Exception $e) {
                $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
            }
        }
    }
}
