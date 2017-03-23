<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Config\Config;

class IndexService {

    /**
     * @var IWorker
     */
    protected $defaultWorker;

    /**
     * @var IWorker[]
     */
    protected $tenantWorkers;

    public function __construct($config) {
        if(!(string)$config->disableDefaultTenant) {
            $this->defaultWorker = new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\DefaultMysql(new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\DefaultMysql("default", $config));
        }

        $this->tenantWorkers = array();
        if($config->tenants && $config->tenants instanceof Config) {
            foreach($config->tenants as $name => $tenant) {
                $tenantConfigClass = (string) $tenant->class;
                $tenantConfig = $tenant;
                if($tenant->file) {
                    $tenantConfig = new Config(require PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . ((string)$tenant->file), true);
                    $tenantConfig = $tenantConfig->tenant;
                }

                /**
                 * @var $tenantConfig \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IConfig
                 */
                $tenantConfig = new $tenantConfigClass($name, $tenantConfig, $config);
                $worker = $tenantConfig->getTenantWorker();
                $this->tenantWorkers[$name] = $worker;
            }
        }
    }

    /**
     * Returns a specific Tenant Worker
     *
     * @param string $name
     *
     * @return IWorker
     */
    public function getTenantWorker($name){
        return $this->tenantWorkers[$name];
    }

    /**
     * @deprecated
     *
     * @param null $tenant
     * @return array
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getGeneralSearchColumns($tenant = null) {
        return $this->getGeneralSearchAttributes($tenant);
    }

    /**
     * returns all attributes marked as general search attributes for full text search
     *
     * @param string $tenant
     * @return array
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getGeneralSearchAttributes($tenant = null) {
        if(empty($tenant)) {
            $tenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getGeneralSearchAttributes();
            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getGeneralSearchAttributes();
        } else {
            return array();
        }
    }

    /**
     * @deprecated
     */
    public function createOrUpdateTable() {
        $this->createOrUpdateIndexStructures();
    }

    /**
     *  creates or updates necessary index structures (like database tables and so on)
     */
    public function createOrUpdateIndexStructures() {
        if($this->defaultWorker) {
            $this->defaultWorker->createOrUpdateIndexStructures();
        }

        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->createOrUpdateIndexStructures();
        }
    }

    /**
     * deletes given element from index
     *
     * @param IIndexable $object
     */
    public function deleteFromIndex(IIndexable $object){
        if($this->defaultWorker) {
            $this->defaultWorker->deleteFromIndex($object);
        }
        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->deleteFromIndex($object);
        }
    }

    /**
     * updates given element in index
     *
     * @param IIndexable $object
     */
    public function updateIndex(IIndexable $object) {
        if($this->defaultWorker) {
            $this->defaultWorker->updateIndex($object);
        }
        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->updateIndex($object);
        }
    }

    /**
     * returns all index attributes
     *
     * @param bool $considerHideInFieldList
     * @param string $tenant
     * @return array
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getIndexAttributes($considerHideInFieldList = false, $tenant = null) {
        if(empty($tenant)) {
            $tenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getIndexAttributes($considerHideInFieldList);
            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getIndexAttributes($considerHideInFieldList);
        } else {
            return array();
        }
    }

    /**
     * @deprecated
     *
     * @param bool $considerHideInFieldList
     * @param null $tenant
     * @return mixed
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getIndexColumns($considerHideInFieldList = false, $tenant = null) {
        return $this->getIndexAttributes($considerHideInFieldList, $tenant);
    }

    /**
     * returns all filter groups
     *
     * @param string $tenant
     * @return array
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getAllFilterGroups($tenant = null) {
        if(empty($tenant)) {
            $tenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getAllFilterGroups();
            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getAllFilterGroups();
        } else {
            return array();
        }
    }


    /**
     * retruns all index attributes for a given filter group
     *
     * @param $filterType
     * @param string $tenant
     * @return array
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getIndexAttributesByFilterGroup($filterType, $tenant = null) {
        if(empty($tenant)) {
            $tenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getIndexAttributesByFilterGroup($filterType);
            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getIndexAttributesByFilterGroup($filterType);
        } else {
            return array();
        }
    }

    /**
     * @deprecated
     *
     * @param $filterType
     * @param null $tenant
     * @return mixed
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getIndexColumnsByFilterGroup($filterType, $tenant = null) {
        return $this->getIndexAttributesByFilterGroup($filterType, $tenant);
    }


    /**
     * returns current tenant configuration
     *
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IConfig
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getCurrentTenantConfig() {
        return $this->getCurrentTenantWorker()->getTenantConfig();
    }

    /**
     * @return IWorker
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getCurrentTenantWorker() {
        $tenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant];
            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        } else {
            return $this->defaultWorker;
        }
    }

    /**
     * @return IProductList
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getProductListForCurrentTenant() {
        return $this->getCurrentTenantWorker()->getProductList();
    }


    /**
     * @return IProductList
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getProductListForTenant($tenant) {
        if($tenant) {
            if (array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getProductList();
            } else {
                throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        } else {
            return $this->defaultWorker->getProductList();
        }
    }
}