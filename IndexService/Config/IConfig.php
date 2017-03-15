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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

/**
 * Interface for IndexService Tenant Configurations
 *
 * Interface \OnlineShop\Framework\IndexService\Config\IConfig
 */
interface IConfig {

    /**
     * returns tenant name
     *
     * @return string
     */
    public function getTenantName();


    /**
     * returns attribute configuration for product index
     *
     * @return array
     */
    public function getAttributeConfig();


    /**
     * return full text search index attribute names for product index
     *
     * @return array
     */
    public function getSearchAttributeConfig();


    /**
     * return all supported filter types for product index
     *
     * @return array
     */
    public function getFilterTypeConfig();


    /**
     * returns if given product is active for this tenant
     *
     * @return bool
     */
    public function isActive(\OnlineShop\Framework\Model\IIndexable $object);


    /**
     * checks, if product should be in index for current tenant
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return bool
     */
    public function inIndex(\OnlineShop\Framework\Model\IIndexable $object);


    /**
     * Returns categories for given object in context of the current tenant.
     * Possible hook to filter categories for specific tenants.
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return \OnlineShop\Framework\Model\AbstractCategory[]
     */
    public function getCategories(\OnlineShop\Framework\Model\IIndexable $object);


    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(\OnlineShop\Framework\Model\IIndexable $object, $subObjectId = null);


    /**
     * populates index for tenant relations based on gived data
     *
     * @param mixed $objectId
     * @param mixed $subTenantData
     * @param mixed $subObjectId
     * @return void
     */
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null);


    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \OnlineShop\Framework\IndexService\Worker\IWorker
     */
    public function getTenantWorker();


    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return \OnlineShop\Framework\Model\IIndexable[]
     */
    public function createSubIdsForObject(\OnlineShop\Framework\Model\IIndexable $object);


    /**
     * checks if there are some zombie subIds around and returns them for cleanup
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param array $subIds
     * @return mixed
     */
    public function getSubIdsToCleanup(\OnlineShop\Framework\Model\IIndexable $object, array $subIds);


    /**
     * creates virtual parent id for given sub id
     * default is getOSParentId
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param $subId
     * @return mixed
     */
    public function createVirtualParentIdForSubId(\OnlineShop\Framework\Model\IIndexable $object, $subId);


    /**
     * Gets object by id, can consider subIds and therefore return e.g. an array of values
     * always returns object itself - see also getObjectMockupById
     *
     * @param $objectId
     * @param $onlyMainObject - only returns main object
     * @return mixed
     */
    public function getObjectById($objectId, $onlyMainObject = false);


    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return \OnlineShop\Framework\Model\IIndexable | array
     */
    public function getObjectMockupById($objectId);
}