<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractBatchProcessingWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191121150326 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $factory = Factory::getInstance();
            $indexService = $factory->getIndexService();
            $tenants = $indexService->getTenants();

            foreach ($tenants as $tenant) {
                $tenantWorker = $indexService->getTenantWorker($tenant);
                if ($tenantWorker instanceof AbstractBatchProcessingWorker) {
                    $method = new \ReflectionMethod(get_class($tenantWorker), 'getStoreTableName');
                    $method->setAccessible(true);
                    $tableName = $method->invoke($tenantWorker);

                    $table = $schema->getTable($tableName);
                    if (!$table->hasColumn('preparation_status')) {
                        $table->addColumn('preparation_status', 'smallint', ['unsigned' => true, 'length' => 5, 'notnull' => false, 'default' => 'null']);
                    }
                    if (!$table->hasColumn('preparation_error')) {
                        $table->addColumn('preparation_error', 'string', [ 'length' => 255, 'notnull' => false, 'default' => 'null']);
                    }
                    if (!$table->hasColumn('trigger_info')) {
                        $table->addColumn('trigger_info', 'string', ['length' => 255, 'notnull' => false, 'default' => 'null']);
                    }

                    if (!$table->hasIndex('update_worker_index')) {
                        $table->addIndex(['tenant', 'crc_current', 'crc_index', 'worker_timestamp'], 'update_worker_index');
                    }

                    if (!$table->hasIndex('preparation_status_index')) {
                        $table->addIndex(['tenant', 'preparation_status'], 'preparation_status_index');
                    }

                    if (!$table->hasIndex('worker_id_index')) {
                        $table->addIndex(['worker_id'], 'worker_id_index');
                    }
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if (PimcoreEcommerceFrameworkBundle::isEnabled()) {
            $factory = Factory::getInstance();
            $indexService = $factory->getIndexService();
            $tenants = $indexService->getTenants();

            foreach ($tenants as $tenant) {
                $tenantWorker = $indexService->getTenantWorker($tenant);
                if ($tenantWorker instanceof AbstractBatchProcessingWorker) {
                    $method = new \ReflectionMethod(get_class($tenantWorker), 'getStoreTableName');
                    $method->setAccessible(true);
                    $tableName = $method->invoke($tenantWorker);

                    $this->addSql("DROP INDEX `update_worker_index` ON `$tableName`;");
                    $this->addSql("DROP INDEX `preparation_status_index` ON `$tableName`;");
                    $this->addSql("DROP INDEX `worker_id_index` ON `$tableName`;");

                    $this->addSql("ALTER TABLE `$tableName`
                    DROP COLUMN preparation_status,
                    DROP COLUMN preparation_error,
                    DROP COLUMN trigger_info;"
                    );
                }
            }
        }
    }
}
