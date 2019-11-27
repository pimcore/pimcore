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

                    $this->addSql("ALTER TABLE `$tableName`
                    ADD preparation_status SMALLINT(5) UNSIGNED NULL DEFAULT NULL,
                    ADD preparation_error VARCHAR(255) NULL DEFAULT NULL,
                    ADD trigger_info VARCHAR(255) NULL DEFAULT NULL;"
                    );

                    $this->addSql("CREATE INDEX `update_worker_index` 	ON `$tableName` (`tenant`,`crc_current`,`crc_index`,`worker_timestamp`);");
                    $this->addSql("CREATE INDEX `preparation_status_index` ON `$tableName` (`tenant`,`preparation_status`);");
                    $this->addSql("CREATE INDEX `worker_id_index` ON `$tableName` (`worker_id`);");
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
