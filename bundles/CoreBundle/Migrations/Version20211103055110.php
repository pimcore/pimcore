<?php

namespace App\Migrations\SixNine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20211103055110 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $db = \Pimcore\Db::get();

        $classes = $db->fetchRow("SELECT id FROM classes");

        foreach ($classes as $class) {
            $objectDatastoreTableRelation = 'object_relations_' . $class;

            if ($schema->hasTable($objectDatastoreTableRelation)) {
                $this->addSql(
                    "ALTER TABLE $objectDatastoreTableRelation CHANGE COLUMN 
                        `type` `type` ENUM('object', 'asset', 'document') NOT NULL DEFAULT 'object' ;"
                );
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $db = \Pimcore\Db::get();

        $classes = $db->fetchRow("SELECT id FROM classes");

        foreach ($classes as $class) {
            $objectDatastoreTableRelation = 'object_relations_' . $class;

            if ($schema->hasTable($objectDatastoreTableRelation)) {
                $this->addSql(
                    "ALTER TABLE $objectDatastoreTableRelation CHANGE COLUMN 
                        `type` `type` VARCHAR(50) NOT NULL DEFAULT '' ;"
                );
            }
        }
    }
}

