<?php

/**
 * Interface for IndexService workers which support patch processing of index data preparation and index updating
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker
 */
interface OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker extends OnlineShop_Framework_IndexService_Tenant_IWorker {

    /**
     * fills queue based on path
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     */
    public function fillupPreparationQueue(OnlineShop_Framework_ProductInterfaces_IIndexable $object);


    /**
     * processes elements in the queue for preparation of index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     * @return int number of entries
     */
    public function processPreparationQueue($limit = 200);



    /**
     * processes the update index queue - updates all elements where current_crc != index_crc
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     * @return $int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200);

}