<?php

abstract class OnlineShop_Framework_IndexService_Tenant_Config_AbstractConfig implements OnlineShop_Framework_IndexService_Tenant_IConfig {

    protected $tenantName;
    protected $attributeConfig;
    protected $searchAttributeConfig;

    /**
     * @var Zend_Config
     */
    protected $filterTypeConfig;

    /**
     * @param string $tenantName
     * @param $tenantConfigXml
     * @param null $totalConfigXml
     */
    public function __construct($tenantName, $tenantConfigXml, $totalConfigXml = null) {
        $this->tenantName = $tenantName;
        $this->attributeConfig = $tenantConfigXml->columns;
        $this->filterTypeConfig = $tenantConfigXml->filtertypes;

        $this->searchAttributeConfig = array();
        if($tenantConfigXml->generalSearchColumns->column) {
            foreach($tenantConfigXml->generalSearchColumns->column as $c) {
                $this->searchAttributeConfig[] = $c->name;
            }
        }
    }

    /**
     * @return string
     */
    public function getTenantName() {
        return $this->tenantName;
    }

    /**
     * returns column configuration for product index
     *
     * @return mixed
     */
    public function getAttributeConfig() {
        return $this->attributeConfig;
    }

    /**
     * return search index column names for product index
     *
     * @return array
     */
    public function getSearchAttributeConfig() {
        return $this->searchAttributeConfig;
    }

    /**
     * return all supported filter types for product index
     *
     * @return array|null
     */
    public function getFilterTypeConfig()
    {
        return $this->filterTypeConfig;
    }


    /**
     * @return bool
     */
    public function isActive(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        return true;
    }

    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return OnlineShop_Framework_ProductInterfaces_IIndexable[]
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
     * always returns object itself - see als getObjectMockupById
     *
     * @param $objectId
     * @return Object_Abstract | array
     */
    public function getObjectById($objectId) {
        return Object_Abstract::getById($objectId);
    }


    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return OnlineShop_Framework_ProductInterfaces_IIndexable | array
     */
    public function getObjectMockupById($objectId) {
        return $this->getObjectById($objectId);
    }

}