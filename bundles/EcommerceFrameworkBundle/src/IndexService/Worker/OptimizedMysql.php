<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\OptimizedMysql as OptimizedMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method OptimizedMysqlConfig getTenantConfig()
 *
 * @property OptimizedMysqlConfig $tenantConfig
 */
class OptimizedMysql extends AbstractMockupCacheWorker implements BatchProcessingWorkerInterface
{
    const STORE_TABLE_NAME = 'ecommerceframework_productindex_store';

    const MOCKUP_CACHE_PREFIX = 'ecommerce_mockup';

    protected Helper\MySql $mySqlHelper;

    protected LoggerInterface $logger;

    public function __construct(OptimizedMysqlConfig $tenantConfig, Connection $db, EventDispatcherInterface $eventDispatcher, LoggerInterface $pimcoreEcommerceSqlLogger)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher);

        $this->logger = $pimcoreEcommerceSqlLogger;
        $this->mySqlHelper = new Helper\MySql($tenantConfig, $db);
    }

    public function createOrUpdateIndexStructures(): void
    {
        $this->mySqlHelper->createOrUpdateIndexStructures();
        $this->createOrUpdateStoreTable();
    }

    public function deleteFromIndex(IndexableInterface $object): void
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach ($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId, $object);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    protected function doDeleteFromIndex(int $subObjectId, IndexableInterface $object = null): void
    {
        try {
            $this->db->beginTransaction();
            $this->db->delete($this->tenantConfig->getTablename(), ['id' => $subObjectId]);
            $this->db->delete($this->tenantConfig->getRelationTablename(), ['src' => $subObjectId]);
            if ($this->tenantConfig->getTenantRelationTablename()) {
                $this->db->delete($this->tenantConfig->getTenantRelationTablename(), ['id' => $subObjectId]);
            }

            $this->deleteFromMockupCache($subObjectId);
            $this->deleteFromStoreTable($subObjectId);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            Logger::warn("Error during deleting from index tables for object $subObjectId: " . $e);
        }
    }

    public function updateIndex(IndexableInterface $object): void
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->prepareDataForIndex($object);

        //updates data for all subentries
        foreach ($subObjectIds as $subObjectId => $object) {
            $this->doUpdateIndex($subObjectId);
        }

        if (count($subObjectIds) > 0) {
            $this->fillupPreparationQueue($object);
        }
    }

    /**
     * updates all index tables, delegates subtenant updates to tenant config and updates mockup cache
     *
     * @param int $objectId
     * @param array|null $data
     * @param array|null $metadata
     */
    public function doUpdateIndex(int $objectId, array $data = null, array $metadata = null): void
    {
        if (empty($data)) {
            $data = $this->db->fetchOne('SELECT data FROM ' . self::STORE_TABLE_NAME . ' WHERE id = ? AND tenant = ?', [$objectId, $this->name]);
            $data = json_decode($data, true);
        }

        if ($data) {
            try {
                $this->db->beginTransaction();

                $this->mySqlHelper->doInsertData($data['data']);

                //insert relation data
                $this->db->delete($this->tenantConfig->getRelationTablename(), ['src' => $objectId]);
                foreach ($data['relations'] as $rd) {
                    $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                }

                //insert sub tenant data
                $this->tenantConfig->updateSubTenantEntries($objectId, $data['subtenants'], $data['data']['id']);

                //save new indexed element to mockup cache
                $this->saveToMockupCache($objectId, $data);

                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                Logger::warn("Error during updating index table for object $objectId: " . $e->getMessage());
            }
        }
    }

    /**
     * @return string[]
     */
    protected function getValidTableColumns(string $table): array
    {
        return $this->mySqlHelper->getValidTableColumns($table);
    }

    /**
     * @return string[]
     */
    protected function getSystemAttributes(): array
    {
        return $this->mySqlHelper->getSystemAttributes();
    }

    protected function getStoreTableName(): string
    {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix(): string
    {
        return self::MOCKUP_CACHE_PREFIX;
    }

    public function __destruct()
    {
        $this->mySqlHelper->__destruct();
    }

    public function getProductList(): \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql($this->getTenantConfig(), $this->logger);
    }
}
