<?php

/**
 * Interface for IndexService Tenant Configurations with mockup implementations
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IMysqlConfig
 */
interface OnlineShop_Framework_IndexService_Tenant_IMockupConfig {

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations);

}