<?php

abstract class OnlineShop_Framework_IndexService_Tenant_AbstractConfig {

    protected $columnConfig;
    protected $searchColumnConfig;

    public function __construct($tenantConfigXml, $totalConfigXml = null) {
        $this->columnConfig = $tenantConfigXml->columns;

        $this->searchColumnConfig = array();
        if($tenantConfigXml->generalSearchColumns->column) {
            foreach($tenantConfigXml->generalSearchColumns->column as $c) {
                $this->searchColumnConfig[] = $c->name;
            }
        }
    }

    /**
     * returns column configuration for product index
     *
     * @return mixed
     */
    public function getColumnConfig() {
        return $this->columnConfig;
    }

    /**
     * return search index column names for product index
     *
     * @return array
     */
    public function getSearchColumnConfig() {
        return $this->searchColumnConfig;
    }

    /**
     * @return bool
     */
    public function isActive(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        return true;
    }


    /**
     * returns table name of product index
     *h
     * @return string
     */
    public abstract function getTablename();

    /**
     * returns table name of product index reations
     *
     * @return string
     */
    public abstract function getRelationTablename();

    /**
     * return table name of product index tenant relations for subtenants
     *
     * @return string
     */
    public abstract function getTenantRelationTablename();

    /**
     * return join statement in case of subtenants
     *
     * @return string
     */
    public abstract function getJoins();

    /**
     * returns additional condition in case of subtenants
     *
     * @return string
     */
    public abstract function getCondition();

    /**
     * checks, if product should be in index for current tenant
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return bool
     */
    public abstract function inIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object);

    /**
     * populates table for tenant reations in case of subtenants
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     */
    public abstract function updateSubTenantEntries(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subObjectId = null);


    /**
     * @return OnlineShop_Framework_IndexService_Tenant_Worker
     */
    public function createWorker() {
        return new OnlineShop_Framework_IndexService_Tenant_Worker($this);
    }

    /**
     * returns column type for id
     *
     * @param $isPrimary
     * @return string
     */
    public function getIdColumnType($isPrimary) {
        if($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return "int(11) NOT NULL";
        }
    }

    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return array
     */
    public function createSubIdsForObject(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        return array($object->getId() => $object);
    }

    /**
     * creates virtual parent id for given sub id
     * default is getOSParentId
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param $subId
     * @return mixed
     */
    public function createVirtualParentIdForSubId(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subId) {
        return $object->getOSParentId();
    }

    /**
     * Gets object by id, can consider subIds and therefore return e.g. an array of values
     *
     * @param $objectId
     * @return Object_Abstract | array
     */
    public function getObjectById($objectId) {
        return Object_Abstract::getById($objectId);
    }
}