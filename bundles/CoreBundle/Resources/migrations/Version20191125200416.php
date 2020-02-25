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
class Version20191125200416 extends AbstractPimcoreMigration
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
                    if (!$table->hasColumn('metadata')) {
                        $table->addColumn('metadata', 'text', ['notnull' => false, 'default' => 'null', 'length' => 65535]);
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

                    $this->addSql("ALTER TABLE `$tableName`
                    DROP COLUMN metadata;
                ");
                }
            }
        }
    }
}
