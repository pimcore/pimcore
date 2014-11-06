<?php

/**
 * Interface for IndexService Tenant Configurations using elastic search as index
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IElasticSearchConfig
 */
interface OnlineShop_Framework_IndexService_Tenant_IElasticSearchConfig extends OnlineShop_Framework_IndexService_Tenant_IConfig {

    /**
     * returns elastic search client parameters defined in the tenant config
     *
     * @return array
     */
    public function getElasticSearchClientParams();


    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition();


    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch
     */
    public function getTenantWorker();

}