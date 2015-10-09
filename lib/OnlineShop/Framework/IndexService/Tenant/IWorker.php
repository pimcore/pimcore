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
 * Interface for IndexService workers
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IWorker
 */
interface OnlineShop_Framework_IndexService_Tenant_IWorker {

    const MULTISELECT_DELIMITER = "#;#";

    /**
     * returns all attributes marked as general search attributes for full text search
     *
     * @return array
     */
    function getGeneralSearchAttributes();

    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    function createOrUpdateIndexStructures();

    /**
     * deletes given element from index
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return void
     */
    function deleteFromIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object);

    /**
     * updates given element in index
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return void
     */
    function updateIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object);

    /**
     * returns all index attributes
     *
     * @param bool $considerHideInFieldList
     * @return array
     */
    function getIndexAttributes($considerHideInFieldList = false);

    /**
     * returns all filter groups
     *
     * @return array
     */
    function getAllFilterGroups();

    /**
     * retruns all index attributes for a given filter group
     *
     * @param string $filterGroup
     * @return array
     */
    function getIndexAttributesByFilterGroup($filterGroup);

    /**
     * returns current tenant configuration
     *
     * @return OnlineShop_Framework_IndexService_Tenant_IConfig
     */
    function getTenantConfig();


    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return OnlineShop_Framework_IProductList
     */
    function getProductList();

}