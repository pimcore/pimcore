<?php

/**
 * Interface for IndexService Tenant Configurations using factfinder as index
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IFactFinderConfig
 */
interface OnlineShop_Framework_IndexService_Tenant_IFindologicConfig extends OnlineShop_Framework_IndexService_Tenant_IConfig
{
    /**
     * returns factfinder client parameters defined in the tenant config
     *
     * @param string $setting
     *
     * @return array|string
     */
    public function getClientConfig($setting = null);

    /**
     * returns condition for current subtenant
     *
     * @return string
     */
    public function getSubTenantCondition();


    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch
     */
    public function getTenantWorker();

}