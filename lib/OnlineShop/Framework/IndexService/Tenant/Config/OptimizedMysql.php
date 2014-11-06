<?php

class OnlineShop_Framework_IndexService_Tenant_Config_OptimizedMysql extends OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql implements OnlineShop_Framework_IndexService_Tenant_IMockupConfig {

    /**
     * @var OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return OnlineShop_Framework_IndexService_Tenant_Worker_OptimizedMysql
     */
    public function getTenantWorker() {
        if(empty($this->tenantWorker)) {
            $this->tenantWorker = new OnlineShop_Framework_IndexService_Tenant_Worker_OptimizedMysql($this);
        }
        return $this->tenantWorker;
    }

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     * @return OnlineShop_Framework_ProductList_DefaultMockup
     */
    public function createMockupObject($objectId, $data, $relations) {
        return new OnlineShop_Framework_ProductList_DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return OnlineShop_Framework_ProductInterfaces_IIndexable | array
     */
    public function getObjectMockupById($objectId) {
        $mockup = $this->getTenantWorker()->getMockupFromCache($objectId);

        if(empty($mockup)) {
            Logger::warn("Could not load element with ID $objectId as mockup, loading complete object");
            return $this->getObjectById($objectId);
        } else {
            return $mockup;
        }

    }



}