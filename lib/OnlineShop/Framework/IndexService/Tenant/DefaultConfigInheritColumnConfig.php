<?php

class OnlineShop_Framework_IndexService_Tenant_DefaultConfigInheritColumnConfig extends OnlineShop_Framework_IndexService_Tenant_DefaultConfig {

    public function __construct($tenantConfigXml, $totalConfigXml = null) {
        $this->columnConfig = $totalConfigXml->columns;

        $this->searchColumnConfig = array();
        if($totalConfigXml->generalSearchColumns->column) {
            foreach($totalConfigXml->generalSearchColumns->column as $c) {
                $this->searchColumnConfig[] = $c->name;
            }
        }
    }

    public function getTablename() {
        return "plugin_onlineshop_productindex3";
    }

    public function getRelationTablename() {
        return "plugin_onlineshop_productindex_relations3";
    }
}