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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker;

/**
 * Interface for IndexService workers
 *
 * Interface \OnlineShop\Framework\IndexService\Worker\IWorker
 */
interface IWorker {

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
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return void
     */
    function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object);

    /**
     * updates given element in index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return void
     */
    function updateIndex(\OnlineShop\Framework\Model\IIndexable $object);

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
     * @return \OnlineShop\Framework\IndexService\Config\IConfig
     */
    function getTenantConfig();


    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return \OnlineShop\Framework\IndexService\ProductList\IProductList
     */
    function getProductList();

}