<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200531184332 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach ($this->getStoreTableNames($schema) as $storeTableName) {

            $table = $schema->getTable($storeTableName);
            $table
                ->dropColumn('worker_timestamp')
                ->dropColumn('worker_id')
                ->dropColumn('preparation_worker_timestamp')
                ->dropColumn('preparation_worker_id')
                ;
            ;
            $table->dropIndex('worker_id_index');
            $table->dropIndex('update_worker_index');
            $table->addIndex(['crc_current','crc_index'], 'update_index_status_index');

        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        foreach ($this->getStoreTableNames($schema) as $storeTableName) {
            $table = $schema->getTable($storeTableName);
            $table
                ->addColumn('worker_timestamp', 'int(11)')
                ->addColumn('worker_id', 'varchar(20)')
                ->addColumn('preparation_worker_timestamp', 'int(11)')
                ->addColumn('preparation_worker_id', 'varchar(20)')
            ;

            $table->addIndex(['tenant', 'crc_current', 'crc_index', 'worker_timestamp'], 'worker_id_index');
            $table->addIndex(['worker_id'], 'update_worker_index');
            $table->dropIndex('update_index_status_index');

        }
    }

    /**
     * @param Schema $schema
     * @return string[]
     */
    private function getStoreTableNames(Schema $schema) : array {
        $result = [];
        foreach ($schema->getTableNames() as $name) {
            if (strpos($name, 'ecommerceframework_productindex_store_') === 0) {
                $result[] = $name;
            }
        }
        return $result;
    }
}
