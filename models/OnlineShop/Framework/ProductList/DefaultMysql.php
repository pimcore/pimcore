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
 * Implementation of product list which works based on the product index of the online shop framework
 */
class OnlineShop_Framework_ProductList_DefaultMysql implements OnlineShop_Framework_IProductList
{

    /**
     * @var null|OnlineShop_Framework_ProductInterfaces_IIndexable[]
     */
    protected $products = null;

    /**
     * @var string
     */
    protected $tenantName;

    /**
     * @var OnlineShop_Framework_IndexService_Tenant_IMysqlConfig
     */
    protected $tenantConfig;

    /**
     * @var null|int
     */
    protected $totalCount = null;

    /**
     * @var string
     */
    protected $variantMode = OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE;

    /**
     * @var integer
     */
    protected $limit;

    /**
     * @var integer
     */
    protected $offset;

    /**
     * @var OnlineShop_Framework_AbstractCategory
     */
    protected $category;

    /**
     * @var OnlineShop_Framework_ProductList_DefaultMysql_Resource
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $inProductList = true;


    public function __construct(OnlineShop_Framework_IndexService_Tenant_IMysqlConfig $tenantConfig) {
        $this->tenantName = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;
        $this->resource = new OnlineShop_Framework_ProductList_DefaultMysql_Resource($this);
    }

    /**
     * @return OnlineShop_Framework_AbstractProduct[]
     */
    public function getProducts() {
        if ($this->products === null) {
            $this->load();
        }
        return $this->products;
    }


    /**
     * @var string[]
     */
    protected $conditions = array();

    /**
     * @var string[]
     */
    protected $relationConditions = array();

    /**
     * @var string[][]
     */
    protected $queryConditions = array();

    /**
     * @var float
     */
    protected $conditionPriceFrom = null;

    /**
     * @var float
     */
    protected $conditionPriceTo = null;

    /**
     * @param string $condition
     * @param string $fieldname
     */
    public function addCondition($condition, $fieldname = "") {
        $this->products = null;
        $this->conditions[$fieldname][] = $condition;
    }

    public function resetCondition($fieldname) {
        $this->products = null;
        unset($this->conditions[$fieldname]);
    }

    /**
     * @param string $fieldname
     * @param string $condition
     */
    public function addRelationCondition($fieldname, $condition) {
        $this->products = null;
        $this->relationConditions[$fieldname][] = "`fieldname` = " . $this->quote($fieldname) . " AND "  . $condition;
    }

    /**
     * resets all conditions of product list
     */
    public function resetConditions() {
        $this->conditions = array();
        $this->relationConditions = array();
        $this->queryConditions = array();
        $this->conditionPriceFrom = null;
        $this->conditionPriceTo = null;
        $this->products = null;
    }


    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param $condition
     * @param string $fieldname
     */
    public function addQueryCondition($condition, $fieldname = "")
    {
        $this->products = null;
        $this->queryConditions[$fieldname][] = $condition;
    }

    /**
     * Reset query condition for fieldname
     *
     * @param $fieldname
     * @return mixed
     */
    public function resetQueryCondition($fieldname)
    {
        $this->products = null;
        unset($this->queryConditions[$fieldname]);
    }

    /**
     * @param null|float $from
     * @param null|float $to
     */
    public function addPriceCondition($from = null, $to = null) {
        $this->products = null;
        $this->conditionPriceFrom = $from;
        $this->conditionPriceTo = $to;
    }


    /**
     * @param boolean $inProductList
     */
    public function setInProductList($inProductList) {
        $this->products = null;
        $this->inProductList = $inProductList;
    }

    /**
     * @return boolean
     */
    public function getInProductList() {
        return $this->inProductList;
    }

    protected $order;
    /**
     * @var string | array
     */
    protected $orderKey;

    protected $orderByPrice = false;

    public function setOrder($order) {
        $this->products = null;
        $this->order = $order;
    }

    public function getOrder() {
        return $this->order;
    }

    /**
     * @param $orderKey string | array  - either single field name, or array of field names or array of arrays (field name, direction)
     */
    public function setOrderKey($orderKey) {
        $this->products = null;
        if($orderKey == OnlineShop_Framework_IProductList::ORDERKEY_PRICE) {
            $this->orderByPrice = true;
        } else {
            $this->orderByPrice = false;
        }

        $this->orderKey = $orderKey;
    }

    public function getOrderKey() {
        return $this->orderKey;
    }

    public function setLimit($limit) {
        if($this->limit != $limit) {
            $this->products = null;
        }
        $this->limit = $limit;
    }

    public function getLimit() {
        return $this->limit;
    }

    public function setOffset($offset) {
        if($this->offset != $offset) {
            $this->products = null;
        }
        $this->offset = $offset;
    }

    public function getOffset() {
        return $this->offset;
    }


    public function setCategory(OnlineShop_Framework_AbstractCategory $category) {
        $this->products = null;
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setVariantMode($variantMode) {
        $this->products = null;
        $this->variantMode = $variantMode;
    }

    public function getVariantMode() {
        return $this->variantMode;
    }


    public function load() {

        $objectRaws = array();

        //First case: no price filtering and no price sorting
        if(!$this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null)
        {
            $objectRaws = $this->loadWithoutPriceFilterWithoutPriceSorting();
        }

        //Second case: no price filtering but price sorting
        else if($this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null)
        {
            $objectRaws = $this->loadWithoutPriceFilterWithPriceSorting();
        }

        //Third case: price filtering but no price sorting
        else if(!$this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null))
        {
            $objectRaws = $this->loadWithPriceFilterWithoutPriceSorting();
        }

        //Forth case: price filtering and price sorting
        else if($this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null))
        {
            $objectRaws = $this->loadWithPriceFilterWithPriceSorting();
        }


        $this->products = array();
        foreach($objectRaws as $raw) {
            $product = $this->loadElementById($raw['o_id']);
            if($product) {
                $this->products[] = $product;
            }
        }

        return $this->products;
    }



    /**
     * First case: no price filtering and no price sorting
     *
     * @return array
     */
    protected function loadWithoutPriceFilterWithoutPriceSorting()
    {
        $objectRaws = $this->resource->load($this->buildQueryFromConditions(), $this->buildOrderBy(), $this->getLimit(), $this->getOffset());
        $this->totalCount = $this->resource->getLastRecordCount();

        return $objectRaws;
    }


    /**
     * Second case: no price filtering but price sorting
     *
     * @return array
     * @throws Exception
     * @todo Not implemented yet
     */
    protected function loadWithoutPriceFilterWithPriceSorting()
    {
        $objectRaws = $this->resource->load($this->buildQueryFromConditions());
        $this->totalCount = $this->resource->getLastRecordCount();

        $priceSystemArrays = array();
        foreach($objectRaws as $raw) {
            $priceSystemArrays[$raw['priceSystemName']][] = $raw['o_id'];
        }
        if(count($priceSystemArrays) == 1) {
            $priceSystemName = key($priceSystemArrays);
            $priceSystem = \OnlineShop\Framework\Factory::getInstance()->getPriceSystem($priceSystemName);
            $objectRaws = $priceSystem->filterProductIds($priceSystemArrays[$raw['priceSystemName']], null, null, $this->order, $this->getOffset(), $this->getLimit());
        } else if(count($priceSystemArrays) == 0) {
            //nothing to do
        } else {
            throw new Exception("Not implemented yet - multiple pricing systems are not supported yet");
            foreach($priceSystemArrays as $priceSystemName => $priceSystemArray) {

            }
        }


        return $objectRaws;
    }


    /**
     * Third case: price filtering but no price sorting
     *
     * @return array
     * @throws Exception
     * @todo Not implemented yet
     */
    protected function loadWithPriceFilterWithoutPriceSorting()
    {
        //TODO check number of price systems

        //TODO set $this->totalCount
        throw new Exception("Not implemented yet");
    }


    /**
     * Forth case: price filtering and price sorting
     *
     * @return array
     * @throws Exception
     * @todo Not implemented yet
     */
    protected function loadWithPriceFilterWithPriceSorting()
    {
        //TODO check number of price systems

        //TODO set $this->totalCount
        throw new Exception("Not implemented yet");
    }




    /**
     * loads element by id
     *
     * @param $elementId
     * @return array|Object_Abstract
     */
    protected function loadElementById($elementId) {
        return $this->getCurrentTenantConfig()->getObjectMockupById($elementId);
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @return void
     */
    public function prepareGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // not supported with mysql tables
    }


    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues()
    {
        // not supported with mysql tables
    }


    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @return void
     */
    public function prepareGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // not supported with mysql tables
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @return void
     */
    public function prepareGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // not supported with mysql tables
    }

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     * @throws Exception
     */
    public function getGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // not supported with mysql tables
    }


    /**
     * @param $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     * @return array
     * @throws Exception
     */
    public function getGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true) {
        $excludedFieldName = $fieldname;
        if (!$fieldnameShouldBeExcluded){
            $excludedFieldName=null;
        }
        if($this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            return $this->resource->loadGroupByValues($fieldname, $this->buildQueryFromConditions(false, $excludedFieldName, OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE), $countValues);

        } else {
            throw new Exception("Not supported yet");
        }
    }

    /**
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     * @throws Exception
     */
    public function getGroupByRelationValues($fieldname, $countValues = false,$fieldnameShouldBeExcluded=true) {
        $excludedFieldName=$fieldname;
        if (!$fieldnameShouldBeExcluded){
            $excludedFieldName=null;
        }
        if($this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            return $this->resource->loadGroupByRelationValues($fieldname, $this->buildQueryFromConditions(false, $excludedFieldName, OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE), $countValues);

        } else {
            throw new Exception("Not supported yet");
        }
    }


    protected function buildQueryFromConditions($excludeConditions = false, $excludedFieldname = null, $variantMode = null) {
        if($variantMode == null) {
            $variantMode = $this->getVariantMode();
        }
        $preCondition = "active = 1 AND o_virtualProductActive = 1";
        if($this->inProductList) {
            $preCondition .= " AND inProductList = 1";
        }

        $tenantCondition = $this->getCurrentTenantConfig()->getCondition();
        if($tenantCondition) {
            $preCondition .= " AND " . $tenantCondition;
        }

        if($this->getCategory()) {
            $preCondition .= " AND parentCategoryIds LIKE '%," . $this->getCategory()->getId() . ",%'";
        }

        $condition = $preCondition;

        //variant handling and userspecific conditions

        if($variantMode == OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            if(!$excludeConditions) {
                $userspecific = $this->buildUserspecificConditions($excludedFieldname);
                if($userspecific) {
                    $condition .= " AND " . $userspecific;
                }
            }

        } else {
            if($variantMode == OnlineShop_Framework_IProductList::VARIANT_MODE_HIDE) {
                $condition .= " AND o_type != 'variant'";
            }

            if(!$excludeConditions) {
                $userspecific = $this->buildUserspecificConditions($excludedFieldname);
                if($userspecific) {
                    $condition .= " AND " . $userspecific;
                }
            }
        }


        if($this->queryConditions) {
            $searchstring = "";
            foreach($this->queryConditions as $queryConditionPartArray) {
                foreach($queryConditionPartArray as $queryConditionPart) {
                    $searchstring .= "+" . $queryConditionPart . "* ";
                }
            }

            $condition .= " AND " . $this->resource->buildFulltextSearchWhere($this->tenantConfig->getSearchAttributeConfig(), $searchstring);
        }

        \OnlineShop\Plugin::getSQLLogger()->log("Total Condition: " . $condition, Zend_Log::INFO);
        return $condition;
    }


    protected function buildUserspecificConditions($excludedFieldname = null) {
        $condition = "";
        foreach($this->relationConditions as $fieldname => $condArray) {
            if($fieldname !== $excludedFieldname) {
                foreach($condArray as $cond) {
                    if($condition) {
                        $condition .= " AND ";
                    }

                    $condition .= "a.o_id IN (SELECT DISTINCT src FROM " . $this->getCurrentTenantConfig()->getRelationTablename() . " WHERE " . $cond . ")";
                }
            }
        }

        foreach($this->conditions as $fieldname => $condArray) {
            if($fieldname !== $excludedFieldname) {
                foreach($condArray as $cond) {
                    if($condition) {
                        $condition .= " AND ";
                    }
                    
                    $condition .= is_array($cond)
                        ? sprintf(' ( %1$s IN (%2$s) )', $fieldname, implode(',', $cond))
                        : '(' . $cond . ')'
                    ;
                }
            }
        }

        \OnlineShop\Plugin::getSQLLogger()->log("User specific Condition Part: " . $condition, Zend_Log::INFO);
        return $condition;
    }

    protected function buildOrderBy() {
        if(!empty($this->orderKey) && $this->orderKey !== OnlineShop_Framework_IProductList::ORDERKEY_PRICE) {

            $orderKeys = $this->orderKey;
            if(!is_array($orderKeys)) {
                $orderKeys = array($orderKeys);
            }

            $directionOrderKeys = array();
            foreach($orderKeys as $key) {
                if(is_array($key)) {
                    $directionOrderKeys[] = $key;
                } else {
                    $directionOrderKeys[] = array($key, $this->order);
                }
            }


            $orderByStringArray = array();
            foreach($directionOrderKeys as $keyDirection) {
                $key = $keyDirection[0];
                if($key instanceof \Pimcore\Model\Object\Data\IndexFieldSelection) {
                    $key = $key->getField();
                }
                $direction = $keyDirection[1];

                if($this->getVariantMode() == OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                    if(strtoupper($this->order) == "DESC") {
                        $orderByStringArray[] = "max(" . $key . ") " . $direction;
                    } else {
                        $orderByStringArray[] = "min(" . $key . ") " . $direction;
                    }
                } else {
                    $orderByStringArray[] = $key . " " . $direction;
                }
            }

            return implode(",", $orderByStringArray);

        }
        return null;
    }

    public function quote($value) {
        return $this->resource->quote($value);
    }

    /**
     * @return OnlineShop_Framework_IndexService_Tenant_IMysqlConfig
     */
    public function getCurrentTenantConfig() {
        return $this->tenantConfig;
    }

    /**
     * returns order by statement for simularity calculations based on given fields and object ids
     * returns cosine simularity calculation
     *
     * @param $fields
     * @param $objectId
     */
    public function buildSimularityOrderBy($fields, $objectId) {

        return $this->resource->buildSimularityOrderBy($fields, $objectId);

    }


    /**
     * returns where statement for fulltext search index
     *
     * @param $fields
     * @param $searchstring
     */
    public function buildFulltextSearchWhere($fields, $searchstring) {
        return $this->resource->buildFulltextSearchWhere($fields, $searchstring);
    }


    /**
     *  -----------------------------------------------------------------------------------------
     *   Methods for Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator
     *  -----------------------------------------------------------------------------------------
     */

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count() {
        if($this->totalCount === null) {
            $this->totalCount = $this->resource->getCount($this->buildQueryFromConditions());
        }
        return $this->totalCount;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current() {
        $this->getProducts();
        $var = current($this->products);
        return $var;
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage) {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);
        return $this->getProducts();
    }

    /**
     * Return a fully configured Paginator Adapter from this method.
     *
     * @return Zend_Paginator_Adapter_Interface
     */
    public function getPaginatorAdapter() {
        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar scalar on success, integer
     * 0 on failure.
     */
    public function key() {
        $this->getProducts();
        $var = key($this->products);
        return $var;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next() {
        $this->getProducts();
        $var = next($this->products);
        return $var;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->getProducts();
        reset($this->products);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }

}
