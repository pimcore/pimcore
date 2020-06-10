<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\OptimizedMysql as OptimizedMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Logger;
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

    /**
     * @var Helper\MySql
     */
    protected $mySqlHelper;

    /**
     * @param OptimizedMysqlConfig $tenantConfig
     * @param ConnectionInterface $db
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null $workerMode
     */
    public function __construct(OptimizedMysqlConfig $tenantConfig, ConnectionInterface $db, EventDispatcherInterface $eventDispatcher, string $workerMode = null)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher, $workerMode);

        $this->mySqlHelper = new Helper\MySql($tenantConfig, $db);
    }

    public function createOrUpdateIndexStructures()
    {
        $this->mySqlHelper->createOrUpdateIndexStructures();
        $this->createOrUpdateStoreTable();
    }

    public function deleteFromIndex(IndexableInterface $object)
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

    protected function doDeleteFromIndex($objectId, IndexableInterface $object = null)
    {
        try {
            $this->db->beginTransaction();
            $this->db->deleteWhere($this->tenantConfig->getTablename(), 'o_id = ' . $this->db->quote($objectId));
            $this->db->deleteWhere($this->tenantConfig->getRelationTablename(), 'src = ' . $this->db->quote($objectId));
            if ($this->tenantConfig->getTenantRelationTablename()) {
                $this->db->deleteWhere($this->tenantConfig->getTenantRelationTablename(), 'o_id = ' . $this->db->quote($objectId));
            }

            $this->deleteFromMockupCache($objectId);
            $this->deleteFromStoreTable($objectId);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            Logger::warn("Error during deleting from index tables for object $objectId: " . $e);
        }
    }

    public function updateIndex(IndexableInterface $object)
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
    public function doUpdateIndex($objectId, $data = null, $metadata = null)
    {
        if (empty($data)) {
            $data = $this->db->fetchOne('SELECT data FROM ' . self::STORE_TABLE_NAME . ' WHERE o_id = ? AND tenant = ?', [$objectId, $this->name]);
            $data = json_decode($data, true);
        }

        if ($data) {
            try {
                $this->db->beginTransaction();

                $this->mySqlHelper->doInsertData($data['data']);

                //insert relation data
                $this->db->deleteWhere($this->tenantConfig->getRelationTablename(), 'src = ' . $this->db->quote($objectId));
                foreach ($data['relations'] as $rd) {
                    $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                }

                //insert sub tenant data
                $this->tenantConfig->updateSubTenantEntries($objectId, $data['subtenants'], $data['data']['o_id']);

                //save new indexed element to mockup cache
                $this->saveToMockupCache($objectId, $data);

                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                Logger::warn("Error during updating index table for object $objectId: " . $e->getMessage());
            }
        }
    }

    protected function getValidTableColumns($table)
    {
        return $this->mySqlHelper->getValidTableColumns($table);
    }

    protected function getSystemAttributes()
    {
        return $this->mySqlHelper->getSystemAttributes();
    }

    protected function getStoreTableName()
    {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix()
    {
        return self::MOCKUP_CACHE_PREFIX;
    }

    public function __destruct()
    {
        $this->mySqlHelper->__destruct();
    }

    /**
     * Returns product list implementation valid and configured for this worker/tenant
     *
     * @return mixed
     */
    public function getProductList()
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql($this->getTenantConfig());
    }
}
