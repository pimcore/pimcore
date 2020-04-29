<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractBatchProcessingWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\PimcoreEcommerceFrameworkBundle;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20200310122412 extends AbstractPimcoreMigration
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
                    if (!$table->hasIndex('in_preparation_queue_index')) {
                        $table->addIndex(['tenant', 'in_preparation_queue'], 'in_preparation_queue_index');
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

                    $this->addSql("DROP INDEX `in_preparation_queue_index` ON `$tableName`;");
                }
            }
        }
    }
}
