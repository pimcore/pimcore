<?php

class OnlineShop_Framework_IndexService_Tenant_Worker_DefaultFactFinder
    extends OnlineShop_Framework_IndexService_Tenant_Worker_Abstract
    implements OnlineShop_Framework_IndexService_Tenant_IWorker, OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker
{
    use OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing
    {
        OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing::processUpdateIndexQueue as traitProcessUpdateIndexQueue;
    }

    use OnlineShop_Framework_IndexService_Tenant_Worker_Traits_MockupCache;


    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store_factfinder";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup_factfinder";


    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures()
    {
        $this->createOrUpdateStoreTable();
    }

    /**
     * deletes given element from index
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     *
     * @return void
     */
    public function deleteFromIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object)
    {
        // TODO: Implement deleteFromIndex() method.
    }

    /**
     * updates given element in index
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     *
     * @return void
     */
    public function updateIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object)
    {
        if(!$this->tenantConfig->isActive($object))
        {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $this->prepareDataForIndex($object);
        $this->fillupPreparationQueue($object);
    }


    /**
     * first run processUpdateIndexQueue of trait and then commit updated entries if there are some
     *
     * @param int $limit
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200)
    {
        $entriesUpdated = $this->traitProcessUpdateIndexQueue($limit);
        if($entriesUpdated)
        {
            // TODO csv schreiben?
//            $this->commitUpdateIndex();
        }

        return $entriesUpdated;
    }



    /**
     * returns product list implementation valid and configured for this worker/tenant
     *d
     * @return mixed
     */
    public function getProductList()
    {
        return new OnlineShop_Framework_ProductList_DefaultFactFinder( $this->getTenantConfig() );
    }


    /**
     * only prepare data for updating index
     *
     * @param $objectId
     * @param null $data
     */
    protected function doUpdateIndex($objectId, $data = null)
    {

    }


    protected function getStoreTableName()
    {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix()
    {
        return self::MOCKUP_CACHE_PREFIX;
    }
}

