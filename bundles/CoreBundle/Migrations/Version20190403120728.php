<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20190403120728 extends AbstractPimcoreMigration
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
        try {
            $db = Db::get();

            // add index to primary key in relations tables
            $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
            foreach ($relationTables as $table) {
                $relationTable = current($table);
                $this->addSql('ALTER TABLE `' . $relationTable . '` DROP PRIMARY KEY');
                $this->addSql('ALTER TABLE `' . $relationTable . '` ADD PRIMARY KEY (`src_id`, `dest_id`, `ownertype`, `ownername`, `fieldname`, `type`, `position`, `index`)');
            }

            $metaTables = $db->fetchAll("SHOW TABLES LIKE 'object\_metadata\_%'");
            foreach ($metaTables as $table) {
                $metaTable = current($table);

                // add index column to metadata tables
                if (!$schema->getTable($metaTable)->hasColumn('index')) {
                    $this->addSql('ALTER TABLE `' . $metaTable . '` ADD COLUMN `index` int(11) unsigned NOT NULL DEFAULT \'0\'');
                    $this->addSql('ALTER TABLE `' . $metaTable . '` DROP PRIMARY KEY');
                    $this->addSql('ALTER TABLE `' . $metaTable . '` ADD PRIMARY KEY (`o_id`, `dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`, `index`)');

                    //update index in metadata tables from relations tables
                    $relTable = explode('_', $metaTable);
                    $relTable = 'object_relations_' . $relTable[2];

                    // Don't use INNER JOIN to update table as we could hita MySQL Issue with it.
                    // Details: https://github.com/pimcore/pimcore/issues/4233
                    $this->addSql('UPDATE `' . $metaTable . '` mt
                      SET mt.index = (SELECT rl.index FROM `' . $relTable . '` rl
                        WHERE  mt.o_id = rl.src_id
                            AND mt.dest_id = rl.dest_id
                            AND mt.type = rl.type
                            AND mt.fieldname = rl.fieldname
                            AND mt.ownertype = rl.ownertype
                            AND mt.ownername = rl.ownername
                            AND mt.position = rl.position)');
                }
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        try {
            $db = Db::get();
            // remove index from primary key in relations tables
            $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
            foreach ($relationTables as $table) {
                $relationTable = current($table);
                $this->addSql('ALTER TABLE `' . $relationTable . '` DROP PRIMARY KEY');
                $this->addSql('ALTER TABLE `' . $relationTable . '` ADD PRIMARY KEY (`src_id`, `dest_id`, `ownertype`, `ownername`, `fieldname`, `type`, `position`)');
            }

            // drop index column from metadata tables
            $metaTables = $db->fetchAll("SHOW TABLES LIKE 'object\_metadata\_%'");
            foreach ($metaTables as $table) {
                $metaTable = current($table);
                if ($schema->getTable($metaTable)->hasColumn('index')) {
                    $this->addSql('ALTER TABLE `' . $metaTable . '` DROP COLUMN `index`');
                    $this->addSql('ALTER TABLE `' . $metaTable . '` DROP PRIMARY KEY');
                    $this->addSql('ALTER TABLE `' . $metaTable . '` ADD PRIMARY KEY (`o_id`, `dest_id`, `type`, `fieldname`, `column`, `ownertype`, `ownername`, `position`)');
                }
            }
        } catch (\Exception $e) {
            $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
        }
    }
}
