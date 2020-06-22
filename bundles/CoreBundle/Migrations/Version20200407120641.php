<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200407120641 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $db = Db::get();

        $dataTables = $db->fetchAll("SHOW TABLES LIKE 'object\_classificationstore\_data\_%'");
        foreach ($dataTables as $table) {
            try {
                $dataTable = current($table);

                $currentTable = $schema->getTable($dataTable);
                if ($currentTable->getPrimaryKey()) {
                    $currentTable->dropPrimaryKey();
                }

                if ($currentTable->hasIndex('o_id')) {
                    $currentTable->dropIndex('o_id');
                }

                if ($currentTable->hasIndex('fieldname')) {
                    $currentTable->dropIndex('fieldname');
                }

                $currentTable->setPrimaryKey(['o_id', 'fieldname', 'groupId', 'keyId', 'language']);
            } catch (\Exception $e) {
                $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
            }
        }

        $db = Db::get();

        $groupsTables = $db->fetchAll("SHOW TABLES LIKE 'object\_classificationstore\_groups\_%'");
        foreach ($groupsTables as $table) {
            try {
                $groupTable = current($table);

                $currentTable = $schema->getTable($groupTable);
                if ($currentTable->getPrimaryKey()) {
                    $currentTable->dropPrimaryKey();
                }

                if ($currentTable->hasIndex('o_id')) {
                    $currentTable->dropIndex('o_id');
                }

                if ($currentTable->hasIndex('fieldname')) {
                    $currentTable->dropIndex('fieldname');
                }

                $currentTable->setPrimaryKey(['o_id', 'fieldname', 'groupId']);
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

        $dataTables = $db->fetchAll("SHOW TABLES LIKE 'object\_classificationstore\_data\_%'");
        foreach ($dataTables as $table) {
            try {
                $dataTable = current($table);

                $currentTable = $schema->getTable($dataTable);
                if ($currentTable->getPrimaryKey()) {
                    $currentTable->dropPrimaryKey();
                }

                $currentTable->setPrimaryKey(['groupId', 'keyId', 'o_id', 'fieldname', 'language']);

                if (!$currentTable->hasIndex('o_id')) {
                    $currentTable->addIndex(['o_id'], 'o_id');
                }

                if (!$currentTable->hasIndex('fieldname')) {
                    $currentTable->addIndex(['fieldname'], 'fieldname');
                }
            } catch (\Exception $e) {
                $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
            }
        }

        $groupsTables = $db->fetchAll("SHOW TABLES LIKE 'object\_classificationstore\_groups\_%'");
        foreach ($groupsTables as $table) {
            try {
                $groupTable = current($table);

                $currentTable = $schema->getTable($groupTable);
                if ($currentTable->getPrimaryKey()) {
                    $currentTable->dropPrimaryKey();
                }

                if (!$currentTable->hasIndex('o_id')) {
                    $currentTable->addIndex(['o_id'], 'o_id');
                }

                if (!$currentTable->hasIndex('fieldname')) {
                    $currentTable->addIndex(['fieldname'], 'fieldname');
                }

                $currentTable->setPrimaryKey(['groupId', 'o_id', 'fieldname']);
            } catch (\Exception $e) {
                $this->writeMessage('An error occurred while performing migrations: ' . $e->getMessage());
            }
        }
    }
}
