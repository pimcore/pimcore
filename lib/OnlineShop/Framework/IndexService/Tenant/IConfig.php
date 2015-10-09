<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Interface for IndexService Tenant Configurations
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IConfig
 */
interface OnlineShop_Framework_IndexService_Tenant_IConfig {

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
    public function isActive(OnlineShop_Framework_ProductInterfaces_IIndexable $object);


    /**
     * checks, if product should be in index for current tenant
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return bool
     */
    public function inIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object);


    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subObjectId = null);


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
     * @return OnlineShop_Framework_IndexService_Tenant_IWorker
     */
    public function getTenantWorker();


    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return OnlineShop_Framework_ProductInterfaces_IIndexable[]
     */
    public function createSubIdsForObject(OnlineShop_Framework_ProductInterfaces_IIndexable $object);


    /**
     * checks if there are some zombie subIds around and returns them for cleanup
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param array $subIds
     * @return mixed
     */
    public function getSubIdsToCleanup(OnlineShop_Framework_ProductInterfaces_IIndexable $object, array $subIds);


    /**
     * creates virtual parent id for given sub id
     * default is getOSParentId
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param $subId
     * @return mixed
     */
    public function createVirtualParentIdForSubId(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subId);


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
     * @return OnlineShop_Framework_ProductInterfaces_IIndexable | array
     */
    public function getObjectMockupById($objectId);
}