<?php

class OnlineShop_Framework_IndexService_Tenant_Worker_OptimizedMysql extends OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql implements OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker {
    use OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing;
    use OnlineShop_Framework_IndexService_Tenant_Worker_Traits_MockupCache;

    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup";

    /**
     * @var OnlineShop_Framework_IndexService_Tenant_Config_OptimizedMysql
     */
    protected $tenantConfig;

    public function __construct(OnlineShop_Framework_IndexService_Tenant_Config_OptimizedMysql $tenantConfig) {
        parent::__construct($tenantConfig);
    }


    public function createOrUpdateIndexStructures() {
        parent::createOrUpdateIndexStructures();

        $this->createOrUpdateStoreTable();
    }

    public function deleteFromIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object){
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId);
        }

    }

    protected function doDeleteFromIndex($objectId) {
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
        } catch(Exception $e) {
            $this->db->rollBack();
            Logger::warn("Error during deleting from index tables for object $objectId: " . $e->getMessage(), $e);
        }
    }



    public function updateIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $this->prepareDataForIndex($object);

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

                //insert index data
                $quotedData = array();
                $updateStatement = array();
                foreach($data['data'] as $key => $d) {
                    $quotedData[$this->db->quoteIdentifier($key)] = $this->db->quote($d);
                    $updateStatement[] = $this->db->quoteIdentifier($key) . "=" . $this->db->quote($d);
                }

                $insert = "INSERT INTO " . $this->tenantConfig->getTablename() . " (" . implode(",", array_keys($quotedData)) . ") VALUES (" . implode("," , $quotedData) . ")"
                    . " ON DUPLICATE KEY UPDATE " . implode(",", $updateStatement);

                $this->db->query($insert);

                //insert relation data
                $this->db->delete($this->tenantConfig->getRelationTablename(), "src = " . $this->db->quote($objectId));
                foreach($data['relations'] as $rd) {
                    $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                }


                //insert sub tenant data
                $this->tenantConfig->updateSubTenantEntries($objectId, $data['subtenants'], $data['data']['o_id']);

                $this->db->commit();
            } catch(Exception $e) {
                $this->db->rollBack();
                Logger::warn("Error during updating index table for object $objectId: " . $e->getMessage(), $e);
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

