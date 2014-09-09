<?php

class OnlineShop_Framework_IndexService_Tenant_DefaultConfig extends OnlineShop_Framework_IndexService_Tenant_AbstractConfig {

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
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     */
    public function updateSubTenantEntries(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subObjectId = null) {
        return;
    }

}