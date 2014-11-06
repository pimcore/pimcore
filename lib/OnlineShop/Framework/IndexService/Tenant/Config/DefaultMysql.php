<?php

class OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql extends OnlineShop_Framework_IndexService_Tenant_Config_AbstractConfig implements OnlineShop_Framework_IndexService_Tenant_IMysqlConfig {

    /**
     * @return string
     */
    public function getTablename() {
        return "plugin_onlineshop_productindex";
    }

    /**
     * @return string
     */
    public function getRelationTablename() {
        return "plugin_onlineshop_productindex_relations";
    }

    /**
     * @return string
     */
    public function getTenantRelationTablename() {
        return "";
    }

    /**
     * @return string
     */
    public function getJoins() {
        return "";
    }

    /**
     * @return string
     */
    public function getCondition() {
        return "";
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return bool
     */
    public function inIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        return true;
    }


    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subObjectId = null)
    {
        return null;
    }

    /**
     * populates index for tenant relations based on gived data
     *
     * @param mixed $objectId
     * @param mixed $subTenantData
     * @param mixed $subObjectId
     * @return void
     */
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null)
    {
        return;
    }

    /**
     * returns column type for id
     *
     * @param $isPrimary
     * @return string
     */
    public function getIdColumnType($isPrimary)
    {
        if($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return "int(11) NOT NULL";
        }
    }


    /**
     * @var OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return OnlineShop_Framework_IndexService_Tenant_IWorker
     */
    public function getTenantWorker() {
        if(empty($this->tenantWorker)) {
            $this->tenantWorker = new OnlineShop_Framework_IndexService_Tenant_Worker_DefaultMysql($this);
        }
        return $this->tenantWorker;
    }
}