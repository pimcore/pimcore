<?php

class OnlineShop_Framework_IndexService_Tenant_DefaultSubTenantConfig extends OnlineShop_Framework_IndexService_Tenant_AbstractConfig {

    public function getTablename() {
        return "plugin_onlineshop_productindex2";
    }

    public function getRelationTablename() {
        return "plugin_onlineshop_productindex_relations2";
    }

    public function getTenantRelationTablename() {
        return "plugin_onlineshop_productindex_tenant_relations";
    }



    public function getJoins() {
        $currentSubTenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentSubTenant();
        if($currentSubTenant) {
            return " INNER JOIN " . $this->getTenantRelationTablename() . " b ON a.o_id = b.o_id ";
        } else {
            return "";
        }

    }

    public function getCondition() {
        $currentSubTenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentSubTenant();
        if($currentSubTenant) {
            return "b.subtenant_id = " . $currentSubTenant;
        } else {
            return "";
        }
    }

    public function inIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        $tenants = $object->getTenants();
        return !empty($tenants);
    }

    public function updateSubTenantEntries(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        $db = Pimcore_Resource::get();
        $db->delete($this->getTenantRelationTablename(), "o_id = " . $db->quote($object->getId()));

        if($this->inIndex($object)) {
            //implementation specific tenant get logic
            foreach($object->getTenants() as $tenant) {
                $db->insert($this->getTenantRelationTablename(), array("o_id" => $object->getId(), "subtenant_id" => $tenant->getId()));
            }
        }
    }
}