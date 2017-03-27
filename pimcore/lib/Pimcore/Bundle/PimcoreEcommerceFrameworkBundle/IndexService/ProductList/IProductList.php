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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IIndexable;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\AdapterAggregateInterface;

/**
 * Interface for product list which works based on the product index of the online shop framework
 */
interface IProductList extends \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator, AdapterInterface, AdapterAggregateInterface
{
    const ORDERKEY_PRICE = "orderkey_price";

    const PRODUCT_TYPE_OBJECT = 'object';
    const PRODUCT_TYPE_VARIANT = 'variant';

    /**
     * Variant mode defines how to consider variants in product list results
     * - does not consider variants in search results
     */
    const VARIANT_MODE_HIDE = "hide";

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results and returns objects and variants
     */
    const VARIANT_MODE_INCLUDE = "include";

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results but only returns corresponding objects in search results
     */
    const VARIANT_MODE_INCLUDE_PARENT_OBJECT = "include_parent_object";


    /**
     * Returns all products valid for this search
     *
     * @return IIndexable[]
     */
    public function getProducts();


    /**
     * Adds filter condition to product list
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param string $condition
     * @param string $fieldname
     */
    public function addCondition($condition, $fieldname = "");


    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param $condition
     * @param string $fieldname
     */
    public function addQueryCondition($condition, $fieldname = "");

    /**
     * Reset filter condition for fieldname
     *
     * @param $fieldname
     * @return mixed
     */
    public function resetCondition($fieldname);

    /**
     * Reset query condition for fieldname
     *
     * @param $fieldname
     * @return mixed
     */
    public function resetQueryCondition($fieldname);


    /**
     * Adds relation condition to product list
     *
     * @param string $fieldname
     * @param string $condition
     */
    public function addRelationCondition($fieldname, $condition);


    /**
     * Resets all conditions of product list
     */
    public function resetConditions();


    /**
     * Adds price condition to product list
     *
     * @param null|float $from
     * @param null|float $to
     */
    public function addPriceCondition($from = null, $to = null);


    /**
     * @param boolean $inProductList
     * @return void
     */
    public function setInProductList($inProductList);


    /**
     * @return boolean
     */
    public function getInProductList();


    /**
     * sets order direction
     *
     * @param $order
     * @return void
     */
    public function setOrder($order);

    /**
     * gets order direction
     *
     * @return string
     */
    public function getOrder();


    /**
     * sets order key
     *
     * @param $orderKey string | array  - either single field name, or array of field names or array of arrays (field name, direction)
     * @return void
     */
    public function setOrderKey($orderKey);

    /**
     * @return string | array
     */
    public function getOrderKey();

    /**
     * @param $limit int
     * @return void
     */
    public function setLimit($limit);

    /**
     * @return int
     */
    public function getLimit();

    /**
     * @param $offset int
     * @return void
     */
    public function setOffset($offset);

    /**
     * @return int
     */
    public function getOffset();

    /**
     * @param $category
     * @return void
     */
    public function setCategory(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory $category);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory
     */
    public function getCategory();

    /**
     * @param $variantMode
     * @return void
     */
    public function setVariantMode($variantMode);

    /**
     * @return string
     */
    public function getVariantMode();

    /**
     * loads search results from index and returns them
     *
     * @return IIndexable[]
     */
    public function load();


    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @return void
     */
    public function prepareGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);


    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @return void
     */
    public function prepareGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);


    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @return void
     */
    public function prepareGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);


    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues();


    /**
     * loads group by values based on fieldname either from local variable if prepared or directly from product index
     *
     * @param $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     * @throws \Exception
     */
    public function getGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);


    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     * @throws \Exception
     */
    public function getGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);


    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     * @throws \Exception
     */
    public function getGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);
}