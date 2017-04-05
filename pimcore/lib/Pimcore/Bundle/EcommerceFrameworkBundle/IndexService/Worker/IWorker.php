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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;

/**
 * Interface for IndexService workers
 *
 * Interface IWorker
 */
interface IWorker
{
    const MULTISELECT_DELIMITER = "#;#";

    /**
     * returns all attributes marked as general search attributes for full text search
     *
     * @return array
     */
    public function getGeneralSearchAttributes();

    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures();

    /**
     * deletes given element from index
     *
     * @param IIndexable $object
     * @return void
     */
    public function deleteFromIndex(IIndexable $object);

    /**
     * updates given element in index
     *
     * @param IIndexable $object
     * @return void
     */
    public function updateIndex(IIndexable $object);

    /**
     * returns all index attributes
     *
     * @param bool $considerHideInFieldList
     * @return array
     */
    public function getIndexAttributes($considerHideInFieldList = false);

    /**
     * returns all filter groups
     *
     * @return array
     */
    public function getAllFilterGroups();

    /**
     * retruns all index attributes for a given filter group
     *
     * @param string $filterGroup
     * @return array
     */
    public function getIndexAttributesByFilterGroup($filterGroup);

    /**
     * returns current tenant configuration
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IConfig
     */
    public function getTenantConfig();


    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return IProductList
     */
    public function getProductList();
}
