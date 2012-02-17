<?php

class OnlineShop_Framework_ProductList implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

    const ORDERKEY_PRICE = "orderkey_price";

    const VARIANT_MODE_HIDE = "hide";
    const VARIANT_MODE_INCLUDE = "include";
    const VARIANT_MODE_INCLUDE_PARENT_OBJECT = "include_parent_object";

    private $products = null;
    private $indexService = null;
    private $totalCount = null;
    private $variantMode = self::VARIANT_MODE_INCLUDE;

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
     * @return array
     */
    public function getProducts() {
        if ($this->products === null) {
            $this->load();
        }
        return $this->products;
    }


    private $conditions = array();
    private $relationConditions = array();
    private $conditionPriceFrom = null;
    private $conditionPriceTo = null;

    public function addCondition($condition, $fieldname = "") {
        $this->conditions[$fieldname][] = $condition;
    }

    public function addRelationCondition($fieldname, $condition) {
        $this->relationConditions[$fieldname][] = "`fieldname` = " . $this->quote($fieldname) . " AND "  . $condition;
    }

    public function resetConditions() {
        $this->conditions = array();
        $this->relationConditions = array();
        $this->conditionPriceFrom = null;
        $this->conditionPriceTo = null;
    }

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
    private $orderKey;

    private $orderByPrice = false;

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getOrder() {
        return $this->order;
    }

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

        $objectIds = array();

        //First case: no price filtering and no price sorting
        if(!$this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->resource->load($this->buildQueryFromConditions(), $this->buildOrderBy(), $this->getLimit(), $this->getOffset());
            $this->totalCount = $this->resource->getCount($this->buildQueryFromConditions());
        }

        //Second case: no price filtering but price sorting
        else if($this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->resource->load($this->buildQueryFromConditions());
            $this->totalCount = $this->resource->getCount($this->buildQueryFromConditions());

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

    public function getGroupByValues($fieldname, $countValues = false) {
        if($this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            return $this->resource->loadGroupByValues($fieldname, $this->buildQueryFromConditions(false, $fieldname, OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE), $countValues);

        } else {
            throw new Exception("Not supported yet");
        }
    }

    public function getGroupByRelationValues($fieldname, $countValues = false) {
        if($this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            return $this->resource->loadGroupByRelationValues($fieldname, $this->buildQueryFromConditions(false, $fieldname, OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE), $countValues);

        } else {
            throw new Exception("Not supported yet");
        }
    }


    private function buildQueryFromConditions($excludeConditions = false, $excludedFieldname = null, $variantMode = null) {
        if($variantMode == null) {
            $variantMode = $this->getVariantMode();
        }
        $preCondition = "active = 1";
        if($this->inProductList) {
            $preCondition .= " AND inProductList = 1";
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

//            $userspecific = $this->buildUserspecificConditions($excludedFieldname);
//
//            $condition .= " AND o_type != 'variant'";
//            $condition .= " AND (";
//            if($userspecific) {
//                $condition .= "(" . $userspecific . ")";
//                $condition .= " OR ";
//            } else {
//                $condition .= "(1=1)";
//                $condition .= " OR ";
//            }
//            $condition .= "( o_id IN (SELECT DISTINCT o_parentId FROM " . OnlineShop_Framework_IndexService::TABLENAME . " WHERE " . $preCondition . " AND o_type = 'variant'";
//            if($userspecific) {
//                $condition .= " AND " . $this->buildUserspecificConditions($excludedFieldname);
//            }
//            $condition .= "))";
//            $condition .= ")";

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
        Logger::log("Total Condition: " . $condition, Zend_Log::INFO);
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

                    $condition .= "o_id IN (SELECT DISTINCT src FROM " . OnlineShop_Framework_IndexService::RELATIONTABLENAME . " WHERE " . $cond . ")";
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

        Logger::log("User specific Condition Part: " . $cond, Zend_Log::INFO);
        return $condition;
    }

    private function buildOrderBy() {
        if(!empty($this->orderKey) && $this->orderKey !== self::ORDERKEY_PRICE) {
            return $this->orderKey . " " . $this->order;
        }
        return null;
    }

    public function quote($value) {
        return $this->resource->quote($value);
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
