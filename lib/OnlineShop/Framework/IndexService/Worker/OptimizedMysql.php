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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop\Framework\IndexService\Worker;

class OptimizedMysql extends DefaultMysql implements IBatchProcessingWorker {
    use \OnlineShop\Framework\IndexService\Worker\WorkerTraits\BatchProcessing;
    use \OnlineShop\Framework\IndexService\Worker\WorkerTraits\MockupCache;

    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup";

    /**
     * @var \OnlineShop\Framework\IndexService\Config\OptimizedMysql
     */
    protected $tenantConfig;

    public function __construct(\OnlineShop\Framework\IndexService\Config\OptimizedMysql $tenantConfig) {
        parent::__construct($tenantConfig);
    }


    public function createOrUpdateIndexStructures() {
        parent::createOrUpdateIndexStructures();

        $this->createOrUpdateStoreTable();
    }

    public function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object){
        if(!$this->tenantConfig->isActive($object)) {
            \Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId, $object);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

    }

    protected function doDeleteFromIndex($objectId, \OnlineShop\Framework\Model\IIndexable $object = null) {
        try {
            $this->db->beginTransaction();
            $this->db->delete($this->tenantConfig->getTablename(), "o_id = " . $this->db->quote($objectId));
            $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($objectId));
            if($this->tenantConfig->getTenantRelationTablename()) {
                $this->db->delete($this->tenantConfig->getTenantRelationTablename(), "o_id = " . $this->db->quote($objectId));
            }

            $this->deleteFromMockupCache($objectId);
            $this->deleteFromStoreTable($objectId);
            $this->db->commit();
        } catch(\Exception $e) {
            $this->db->rollBack();
            \Logger::warn("Error during deleting from index tables for object $objectId: " . $e->getMessage(), $e);
        }
    }



    public function updateIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            \Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $this->prepareDataForIndex($object);

        //updates data for all subentries
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach($subObjectIds as $subObjectId => $object) {
            $this->doUpdateIndex($subObjectId);
        }

        $this->fillupPreparationQueue($object);
    }

    /**
     * updates all index tables, delegates subtenant updates to tenant config and updates mockup cache
     *
     * @param $objectId
     * @param null $data
     */
    public function doUpdateIndex($objectId, $data = null) {

        if(empty($data)) {
            $data = $this->db->fetchOne("SELECT data FROM " . self::STORE_TABLE_NAME . " WHERE id = ? AND tenant = ?", array($objectId, $this->name));
            $data = json_decode($data, true);
        }

        if($data) {
            try {
                $this->db->beginTransaction();

                $this->doInsertData($data['data']);

                //insert relation data
                $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($objectId));
                foreach($data['relations'] as $rd) {
                    $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                }


                //insert sub tenant data
                $this->tenantConfig->updateSubTenantEntries($objectId, $data['subtenants'], $data['data']['o_id']);

                //save new indexed element to mockup cache
                $this->saveToMockupCache($objectId, $data);

                $this->db->commit();
            } catch(\Exception $e) {
                $this->db->rollBack();
                \Logger::warn("Error during updating index table for object $objectId: " . $e->getMessage(), $e);
            }


        }
    }


    protected function getStoreTableName() {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix() {
        return self::MOCKUP_CACHE_PREFIX;
    }


}

