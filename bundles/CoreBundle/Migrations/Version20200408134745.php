<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200408134745 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $db = Db::get();

        $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
        foreach ($relationTables as $table) {
            $relationTableName = current($table);
            $table = $schema->getTable($relationTableName);
            if ($table->getPrimaryKey()) {
                $table->dropPrimaryKey();
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $db = Db::get();

        $relationTables = $db->fetchAll("SHOW TABLES LIKE 'object\_relations\_%'");
        foreach ($relationTables as $table) {
            $relationTableName = current($table);
            $table = $schema->getTable($relationTableName);
            if (!$table->getPrimaryKey()) {
                $table->setPrimaryKey(['`src_id`', '`dest_id`', '`type`', '`fieldname`', '`index`', '`ownertype`', '`ownername`', '`position`']);
            }
        }
    }
}
