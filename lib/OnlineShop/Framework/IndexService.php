<?php

class OnlineShop_Framework_IndexService {

    /**
     * @var OnlineShop_Framework_IndexService_Tenant_Worker
     */
    private $defaultWorker;

    /**
     * @var OnlineShop_Framework_IndexService_Tenant_Worker[]
     */
    private $tenantWorkers;

    public function __construct($config) {
        $this->defaultWorker = new OnlineShop_Framework_IndexService_Tenant_Worker(new OnlineShop_Framework_IndexService_Tenant_DefaultConfig($config));

        $this->tenantWorkers = array();
        if($config->tenants && $config->tenants instanceof Zend_Config) {
            foreach($config->tenants as $name => $tenant) {
                $tenantConfigClass = (string) $tenant->class;

                $tenantConfig = $tenant;
                if($tenant->file) {
                    if(!$tenantConfig = Pimcore_Model_Cache::load("onlineshop_config_tenant_" . $tenantConfigClass)) {
                        $tenantConfig = new Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . ((string)$tenant->file), null, true);
                        $tenantConfig = $tenantConfig->tenant;
                        Pimcore_Model_Cache::save($tenantConfig, "onlineshop_config_tenant_" . $tenantConfigClass, array("output"), 9999);
                    }
                }

                $worker = new OnlineShop_Framework_IndexService_Tenant_Worker(new $tenantConfigClass($tenantConfig, $config));
                $this->tenantWorkers[$name] = $worker;
            }
        }
    }

    public function getGeneralSearchColumns($tenant = null) {
        if(empty($tenant)) {
            $tenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getGeneralSearchColumns();
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        return $this->defaultWorker->getGeneralSearchColumns();
    }

    public function createOrUpdateTable() {
        $this->defaultWorker->createOrUpdateTable();

        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->createOrUpdateTable();
        }

    }


    public function deleteFromIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object){
        $this->defaultWorker->deleteFromIndex($object);
        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->deleteFromIndex($object);
        }
    }

    public function updateIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        $this->defaultWorker->updateIndex($object);
        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->updateIndex($object);
        }
    }

    public function getIndexColumns($considerHideInFieldList = false, $tenant = null) {
        if(empty($tenant)) {
            $tenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getIndexColumns($considerHideInFieldList);
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        return $this->defaultWorker->getIndexColumns($considerHideInFieldList);
    }

    /**
     * @return OnlineShop_Framework_IndexService_Tenant_AbstractConfig
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getCurrentTenantConfig() {
        $tenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentTenant();

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getTenantConfig();
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        } else {
            return $this->defaultWorker->getTenantConfig();
        }
    }
}

