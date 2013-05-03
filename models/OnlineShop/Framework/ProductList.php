<?php

/**
 * Implementation of product list which works based on the product index of the online shop framework
 */
class OnlineShop_Framework_ProductList implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

    const ORDERKEY_PRICE = "orderkey_price";

    /**
     * does not consider variants in search results
     */
    const VARIANT_MODE_HIDE = "hide";

    /**
     * considers variants in search results and returns objects and variants
     */
    const VARIANT_MODE_INCLUDE = "include";

    /**
     * considers variants in search results but only returns corresponding objects in search results
     */
    const VARIANT_MODE_INCLUDE_PARENT_OBJECT = "include_parent_object";

    /**
     * @var null|OnlineShop_Framework_AbstractProduct[]
     */
    protected $products = null;

    /**
     * @var null|OnlineShop_Framework_IndexService
     */
    protected $indexService = null;

    /**
     * @var null|int
     */
    protected $totalCount = null;

    /**
     * @var string
     */
    protected $variantMode = self::VARIANT_MODE_INCLUDE;

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
     * @var OnlineShop_Framework_ProductList_Resource
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $inProductList = true;


    public function __construct() {
        $this->indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
        $this->resource = new OnlineShop_Framework_ProductList_Resource($this);
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
        $this->conditions[$fieldname][] = $condition;
    }

    /**
     * @param string $fieldname
     * @param string $condition
     */
    public function addRelationCondition($fieldname, $condition) {
        $this->relationConditions[$fieldname][] = "`fieldname` = " . $this->quote($fieldname) . " AND "  . $condition;
    }

    /**
     * resets all conditions of product list
     */
    public function resetConditions() {
        $this->conditions = array();
        $this->relationConditions = array();
        $this->conditionPriceFrom = null;
        $this->conditionPriceTo = null;
    }

    /**
     * @param null|float $from
     * @param null|float $to
     */
    public function addPriceCondition($from = null, $to = null) {
        $this->conditionPriceFrom = $from;
        $this->conditionPriceTo = $to;
    }


    /**
     * @param boolean $inProductList
     */
    public function setInProductList($inProductList) {
        $this->inProductList = $inProductList;
    }

    /**
     * @return boolean
     */
    public function getInProductList() {
        return $this->inProductList;
    }
    

    private $order;
    /**
     * @var string | array
     */
    private $orderKey;

    private $orderByPrice = false;

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getOrder() {
        return $this->order;
    }

    /**
     * @param $orderKey string | array  - either single field name, or array of field names or array of arrays (field name, direction)
     */
    public function setOrderKey($orderKey) {
        if($orderKey == self::ORDERKEY_PRICE) {
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
        $this->limit = $limit;
    }

    public function getLimit() {
        return $this->limit;
    }

    public function setOffset($offset) {
        $this->offset = $offset;
    }

    public function getOffset() {
        return $this->offset;
    }

    
    public function setCategory($category) {
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setVariantMode($variantMode) {
        $this->variantMode = $variantMode;
    }

    public function getVariantMode() {
        return $this->variantMode;
    }


    public function load() {

        $objectRaws = array();

        //First case: no price filtering and no price sorting
        if(!$this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->resource->load($this->buildQueryFromConditions(), $this->buildOrderBy(), $this->getLimit(), $this->getOffset());
            $this->totalCount = $this->resource->getLastRecordCount();
        }

        //Second case: no price filtering but price sorting
        else if($this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->resource->load($this->buildQueryFromConditions());
            $this->totalCount = $this->resource->getLastRecordCount();

            $priceSystemArrays = array();
            foreach($objectRaws as $raw) {
                $priceSystemArrays[$raw['priceSystemName']][] = $raw['o_id'];
            }
            if(count($priceSystemArrays) == 1) {
                $priceSystemName = key($priceSystemArrays);
                $priceSystem = OnlineShop_Framework_Factory::getInstance()->getPriceSystem($priceSystemName);
                $objectRaws = $priceSystem->filterProductIds($priceSystemArrays[$raw['priceSystemName']], null, null, $this->order, $this->getOffset(), $this->getLimit());
            } else {
                throw new Exception("Not implemented yet - multiple pricing systems are not supported yet");
                foreach($priceSystemArrays as $priceSystemName => $priceSystemArray) {

                }
            }
        }

        //Third case: price filtering but no price sorting
        else if(!$this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null)) {
            //TODO check number of price systems

            //TODO set $this->totalCount
            throw new Exception("Not implemented yet");
        }

        //Forth case: price filtering and price sorting
        else if($this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null)) {
            //TODO check number of price systems

            //TODO set $this->totalCount
            throw new Exception("Not implemented yet");
        }


        $this->products = array();
        foreach($objectRaws as $raw) {
            $product = Object_Concrete::getById($raw['o_id']);
            if($product) {
                $this->products[] = $product;
            }
        }

        return $this->products;
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
            return $this->resource->loadGroupByValues($fieldname, $this->buildQueryFromConditions(false, $excludedFieldName, OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE), $countValues);

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
            return $this->resource->loadGroupByRelationValues($fieldname, $this->buildQueryFromConditions(false, $excludedFieldName, OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE), $countValues);

        } else {
            throw new Exception("Not supported yet");
        }
    }



    private function buildQueryFromConditions($excludeConditions = false, $excludedFieldname = null, $variantMode = null) {
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

        if($variantMode == self::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            if(!$excludeConditions) {
                $userspecific = $this->buildUserspecificConditions($excludedFieldname);
                if($userspecific) {
                    $condition .= " AND " . $userspecific;
                }
            }

        } else {
            if($variantMode == self::VARIANT_MODE_HIDE) {
                $condition .= " AND o_type != 'variant'";
            }

            if(!$excludeConditions) {
                $userspecific = $this->buildUserspecificConditions($excludedFieldname);
                if($userspecific) {
                    $condition .= " AND " . $userspecific;
                }
            }
        }

        OnlineShop_Plugin::getSQLLogger()->log("Total Condition: " . $condition, Zend_Log::INFO);
        return $condition;
    }


    private function buildUserspecificConditions($excludedFieldname = null) {
        $condition = "";
        foreach($this->relationConditions as $fieldname => $condArray) {
            if($fieldname !== $excludedFieldname) {
                foreach($condArray as $cond) {
                    if($condition) {
                        $condition .= " AND ";
                    }

                    $condition .= "o_id IN (SELECT DISTINCT src FROM " . $this->getCurrentTenantConfig()->getRelationTablename() . " WHERE " . $cond . ")";
                }
            }
        }

        foreach($this->conditions as $fieldname => $condArray) {
            if($fieldname !== $excludedFieldname) {
                foreach($condArray as $cond) {
                    if($condition) {
                        $condition .= " AND ";
                    }
                    $condition .= $cond;
                }
            }
        }

        OnlineShop_Plugin::getSQLLogger()->log("User specific Condition Part: " . $cond, Zend_Log::INFO);
        return $condition;
    }

    private function buildOrderBy() {
        if(!empty($this->orderKey) && $this->orderKey !== self::ORDERKEY_PRICE) {

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
                $direction = $keyDirection[1];

                if($this->getVariantMode() == self::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
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
     * @return OnlineShop_Framework_IndexService_Tenant_DefaultConfig
     */
    public function getCurrentTenantConfig() {
        return $this->indexService->getCurrentTenantConfig();
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
            $this->load();
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
        return $this->load();
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
