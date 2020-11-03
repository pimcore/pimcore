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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

abstract class AbstractElasticSearch implements ProductListInterface
{
    const LIMIT_UNLIMITED = 'unlimited';
    const INTEGER_MAX_VALUE = 2147483647;     // Elasticsearch Integer.MAX_VALUE is 2^31-1
    const ADVANCED_SORT = 'advanced_sort';

    /**
     * @var null|IndexableInterface[]
     */
    protected $products = null;

    /**
     * Timeout for a request in seconds
     *
     * @var int
     */
    protected $timeout = 10;

    /**
     * Name of the index
     *
     * @var string
     */
    protected $indexName = '';

    /**
     * @var string
     */
    protected $tenantName;

    /**
     * @var ElasticSearch
     */
    protected $tenantConfig;

    /**
     * @var null|int
     */
    protected $totalCount = null;

    /**
     * @var string
     */
    protected $variantMode = ProductListInterface::VARIANT_MODE_INCLUDE;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var string
     */
    protected $order;

    /**
     * @var string
     */
    protected $orderKey;

    /**
     * @var bool
     */
    protected $orderByPrice = false;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var AbstractCategory
     */
    protected $category;

    /**
     * @var bool
     */
    protected $inProductList;

    /**
     * @var string[][]
     */
    protected $filterConditions = [];

    /**
     * @var string[][]
     */
    protected $queryConditions = [];

    /**
     * @var string[][]
     */
    protected $relationConditions = [];

    /**
     * @var float
     */
    protected $conditionPriceFrom = null;

    /**
     * @var float
     */
    protected $conditionPriceTo = null;

    /**
     * @var array
     */
    protected $preparedGroupByValues = [];

    /**
     * @var array
     */
    protected $preparedGroupByValuesResults = [];

    /**
     * @var bool
     */
    protected $preparedGroupByValuesLoaded = false;

    /**
     * @var array
     */
    protected $searchAggregation = [];

    /**
     * contains a mapping from productId => array Index
     * useful when you have to merge child products to there parent and you don't want to iterate each time over the list
     *
     * @var array
     */
    protected $productPositionMap = [];

    protected $doScrollRequest = false;

    protected $scrollRequestKeepAlive = '30s';

    /**
     * @var array
     */
    protected $hitData = [];

    /**
     * @return array
     */
    public function getSearchAggregation()
    {
        return $this->searchAggregation;
    }

    /**
     * @param array $searchAggregation
     *
     * @return $this
     */
    public function setSearchAggregation(array $searchAggregation)
    {
        $this->searchAggregation = $searchAggregation;

        return $this;
    }

    public function __construct(ElasticSearchConfigInterface $tenantConfig)
    {
        $this->tenantName = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Returns all products valid for this search
     *
     * @return IndexableInterface[]
     */
    public function getProducts()
    {
        if ($this->products === null) {
            $this->load();
        }

        return $this->products;
    }

    /**
     * Returns the Mapping of the productId => position
     *
     * @return array
     */
    public function getProductPositionMap()
    {
        return $this->productPositionMap;
    }

    /**
     * @param array $productPositionMap
     *
     * @return $this
     */
    public function setProductPositionMap($productPositionMap)
    {
        $this->productPositionMap = $productPositionMap;

        return $this;
    }

    /**
     * Adds condition to product list
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param string $condition
     * @param string $fieldname - must be set for elastic search
     */
    public function addCondition($condition, $fieldname = '')
    {
        $this->filterConditions[$fieldname][] = $condition;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * Reset condition for fieldname
     *
     * @param string $fieldname
     */
    public function resetCondition($fieldname)
    {
        unset($this->filterConditions[$fieldname]);
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * Adds relation condition to product list
     *
     * @param string $fieldname
     * @param string $condition
     */
    public function addRelationCondition($fieldname, $condition)
    {
        $this->relationConditions[$fieldname][] = $condition;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * Resets all conditions of product list
     */
    public function resetConditions()
    {
        $this->relationConditions = [];
        $this->filterConditions = [];
        $this->queryConditions = [];
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param string $condition
     * @param string $fieldname - must be set for elastic search
     */
    public function addQueryCondition($condition, $fieldname = '')
    {
        $this->queryConditions[$fieldname][] = $condition;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * Reset query condition for fieldname
     *
     * @param string $fieldname
     */
    public function resetQueryCondition($fieldname)
    {
        unset($this->queryConditions[$fieldname]);
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * Adds price condition to product list
     *
     * @param null|float $from
     * @param null|float $to
     */
    public function addPriceCondition($from = null, $to = null)
    {
        $this->conditionPriceFrom = $from;
        $this->conditionPriceTo = $to;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * @param bool $inProductList
     *
     * @return void
     */
    public function setInProductList($inProductList)
    {
        $this->inProductList = $inProductList;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * @return bool
     */
    public function getInProductList()
    {
        return $this->inProductList;
    }

    /**
     * sets order direction
     *
     * @param string $order
     *
     * @return void
     */
    public function setOrder($order)
    {
        $this->order = strtolower($order);
        $this->products = null;
    }

    /**
     * gets order direction
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * sets order key
     *
     * @param string|array $orderKey either:
     * Single field name
     * Array of field names
     * Array of arrays (field name, direction)
     * Array containing your sort configuration [self::ADVANCED_SORT => <sort_config as array>]
     *
     * @return void
     */
    public function setOrderKey($orderKey)
    {
        $this->products = null;
        if ($orderKey == ProductListInterface::ORDERKEY_PRICE) {
            $this->orderByPrice = true;
        } else {
            $this->orderByPrice = false;
        }

        $this->orderKey = $orderKey;
    }

    /**
     * @return string
     */
    public function getOrderKey()
    {
        return $this->orderKey;
    }

    /**
     * Pass "unlimited" to do da Scroll Request
     *
     * @param int $limit
     *
     * @return void
     */
    public function setLimit($limit)
    {
        if ($this->limit != $limit) {
            $this->products = null;
        }

        if ($limit == static::LIMIT_UNLIMITED) {
            $this->limit = 100;
            $this->doScrollRequest = true;
        } else {
            $this->doScrollRequest = false;
            $this->limit = $limit;
        }
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    public function setOffset($offset)
    {
        if ($this->offset != $offset) {
            $this->products = null;
        }
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param AbstractCategory $category
     *
     * @return void
     */
    public function setCategory(AbstractCategory $category)
    {
        $this->category = $category;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * @return AbstractCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $variantMode
     *
     * @return void
     */
    public function setVariantMode($variantMode)
    {
        $this->variantMode = $variantMode;
        $this->preparedGroupByValuesLoaded = false;
        $this->products = null;
    }

    /**
     * @return string
     */
    public function getVariantMode()
    {
        return $this->variantMode;
    }

    /**
     * loads search results from index and returns them
     *
     * @return IndexableInterface[]
     */
    public function load()
    {
        $objectRaws = [];

        //First case: no price filtering and no price sorting
        if (!$this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->loadWithoutPriceFilterWithoutPriceSorting();
        }

        //Second case: no price filtering but price sorting
        elseif ($this->orderByPrice && $this->conditionPriceFrom === null && $this->conditionPriceTo === null) {
            $objectRaws = $this->loadWithoutPriceFilterWithPriceSorting();
        }

        //Third case: price filtering but no price sorting
        elseif (!$this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null)) {
            $objectRaws = $this->loadWithPriceFilterWithoutPriceSorting();
        }

        //Forth case: price filtering and price sorting
        elseif ($this->orderByPrice && ($this->conditionPriceFrom !== null || $this->conditionPriceTo !== null)) {
            $objectRaws = $this->loadWithPriceFilterWithPriceSorting();
        }

        // load elements
        $this->products = $this->productPositionMap = [];
        $i = 0;
        foreach ($objectRaws as $raw) {
            $product = $this->loadElementById($raw);
            if ($product) {
                $this->products[] = $product;
                $this->productPositionMap[$product->getId()] = $i;
                $i++;
            }
        }

        return $this->products;
    }

    /**
     * Returns the Elasticsearch query parameters
     *
     * @return array
     */
    public function getQuery()
    {
        $boolFilters = [];
        $queryFilters = [];

        //pre conditions
        $boolFilters = $this->buildSystemConditions($boolFilters);

        //user specific filters
        $boolFilters = $this->buildFilterConditions($boolFilters, []);

        //relation conditions
        $boolFilters = $this->buildRelationConditions($boolFilters, []);

        //query conditions
        $queryFilters = $this->buildQueryConditions($queryFilters, []);

        $params = [];
        $params['index'] = $this->getIndexName();
        $params['type'] = $this->getTenantConfig()->getElasticSearchClientParams()['indexType'];
        $params['body']['_source'] = true;

        if (is_int($this->getLimit())) { // null not allowed
            $params['body']['size'] = $this->getLimit();
        }
        $params['body']['from'] = $this->getOffset();

        if ($this->orderKey) {
            if (is_array($this->orderKey)) {
                if (!empty($this->orderKey[self::ADVANCED_SORT])) {
                    $params['body']['sort'] = $this->orderKey[self::ADVANCED_SORT];
                } else {
                    foreach ($this->orderKey as $orderKey) {
                        $params['body']['sort'][] = [$this->tenantConfig->getFieldNameMapped($orderKey[0]) => (strtolower($orderKey[1]) ?: 'asc')];
                    }
                }
            } else {
                $params['body']['sort'][] = [$this->tenantConfig->getFieldNameMapped($this->orderKey) => ($this->order ?: 'asc')];
            }
        }

        if ($aggs = $this->getSearchAggregation()) {
            foreach ($aggs as $name => $type) {
                $params['body']['aggs'][$name] = $type;
            }
        }

        // build query for request
        $params = $this->buildQuery($params, $boolFilters, $queryFilters);

        return $params;
    }

    /**
     * First case: no price filtering and no price sorting
     *
     * @return array
     */
    protected function loadWithoutPriceFilterWithoutPriceSorting()
    {
        $params = $this->getQuery();

        $this->hitData = [];

        // send request
        $result = $this->sendRequest($params);

        $objectRaws = [];
        if ($result['hits']) {
            $this->totalCount = $result['hits']['total'];
            foreach ($result['hits']['hits'] as $hit) {
                $objectRaws[] = $hit['_id'];
                $this->hitData[$hit['_id']] = $hit;
            }
        }

        return $objectRaws;
    }

    /**
     * Second case: no price filtering but price sorting
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function loadWithoutPriceFilterWithPriceSorting()
    {
        $params = $this->getQuery();
        $this->hitData = [];

        unset($params['body']['sort']);     // don't send the sort parameter, because it doesn't exist with offline sorting
        $params['body']['size'] = 10000;    // won't work with more than 10000 items in the result (elasticsearch limit)
        $params['body']['from'] = 0;
        $result = $this->sendRequest($params);
        $objectRaws = [];
        if ($result['hits']) {
            $this->totalCount = $result['hits']['total'];
            foreach ($result['hits']['hits'] as $hit) {
                $objectRaws[] = ['id' => $hit['_id'], 'priceSystemName' => $hit['_source']['system']['priceSystemName']];
                $this->hitData[$hit['_id']] = $hit;
            }
        }
        $priceSystemArrays = [];
        foreach ($objectRaws as $raw) {
            $priceSystemArrays[$raw['priceSystemName']][] = $raw['id'];
        }
        if (count($priceSystemArrays) == 1) {
            $priceSystemName = key($priceSystemArrays);
            $priceSystem = Factory::getInstance()->getPriceSystem($priceSystemName);
            $objectRaws = $priceSystem->filterProductIds($priceSystemArrays[$priceSystemName], null, null, $this->order, $this->getOffset(), $this->getLimit());
        } elseif (count($priceSystemArrays) == 0) {
            //nothing to do
        } else {
            throw new \Exception('Not implemented yet - multiple pricing systems are not supported yet');
        }

        $raws = [];

        foreach ($objectRaws as $raw) {
            $raws[] = $raw['o_id'];
        }

        return $raws;
    }

    /**
     * Third case: price filtering but no price sorting
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function loadWithPriceFilterWithoutPriceSorting()
    {
        throw new \Exception('Not implemented yet');
    }

    /**
     * Forth case: price filtering and price sorting
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function loadWithPriceFilterWithPriceSorting()
    {
        throw new \Exception('Not implemented yet');
    }

    /**
     * build the complete query
     *
     * @param array $params
     * @param array $boolFilters
     * @param array $queryFilters
     * @param string|null $variantMode
     *
     * @return array
     */
    protected function buildQuery(array $params, array $boolFilters, array $queryFilters, string $variantMode = null)
    {
        if (!$variantMode) {
            $variantMode = $this->getVariantMode();
        }

        if ($variantMode == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            $params['body']['query']['bool']['must']['has_child']['type'] = self::PRODUCT_TYPE_VARIANT;
            $params['body']['query']['bool']['must']['has_child']['score_mode'] = 'avg';
            $params['body']['query']['bool']['must']['has_child']['query']['bool']['must'] = $queryFilters;
            $params['body']['query']['bool']['must']['has_child']['query']['bool']['filter']['bool']['must'] = $boolFilters;

            //add matching variant Ids to the result
            $params['body']['query']['bool']['must']['has_child']['inner_hits'] = [
                'name' => 'variants',
                '_source' => false,
                'size' => 100,
            ];
        } else {
            if ($variantMode == ProductListInterface::VARIANT_MODE_VARIANTS_ONLY) {
                $boolFilters[] = [
                    'term' => ['type' => self::PRODUCT_TYPE_VARIANT],
                ];
            } elseif ($variantMode == ProductListInterface::VARIANT_MODE_HIDE) {
                $boolFilters[] = [
                    'term' => ['type' => self::PRODUCT_TYPE_OBJECT],
                ];
            }

            $params['body']['query']['bool']['must']['bool']['must'] = $queryFilters;
            $params['body']['query']['bool']['filter']['bool']['must'] = $boolFilters;
        }

        return $params;
    }

    /**
     * builds system conditions
     *
     * @param array $boolFilters
     *
     * @return array
     */
    protected function buildSystemConditions(array $boolFilters)
    {
        $boolFilters[] = ['term' => ['system.active' => true]];
        $boolFilters[] = ['term' => ['system.o_virtualProductActive' => true]];
        if ($this->inProductList) {
            $boolFilters[] = ['term' => ['system.inProductList' => true]];
        }

        $tenantCondition = $this->tenantConfig->getSubTenantCondition();
        if ($tenantCondition) {
            $boolFilters[] = $tenantCondition;
        }

        if ($this->getCategory()) {
            $boolFilters[] = ['term' => ['system.parentCategoryIds' => $this->getCategory()->getId()]];
        }

        return $boolFilters;
    }

    /**
     * builds relation conditions of user specific query conditions
     *
     * @param array $boolFilters
     * @param array $excludedFieldnames
     *
     * @return array
     */
    protected function buildRelationConditions($boolFilters, $excludedFieldnames)
    {
        foreach ($this->relationConditions as $fieldname => $relationConditionArray) {
            if (!array_key_exists($fieldname, $excludedFieldnames)) {
                foreach ($relationConditionArray as $relationCondition) {
                    if (is_array($relationCondition)) {
                        $boolFilters[] = $relationCondition;
                    } else {
                        $boolFilters[] = ['term' => [$this->tenantConfig->getFieldNameMapped($fieldname) => $relationCondition]];
                    }
                }
            }
        }

        return $boolFilters;
    }

    /**
     * builds filter condition of user specific conditions
     *
     * @param array $boolFilters
     * @param array $excludedFieldnames
     *
     * @return array
     */
    protected function buildFilterConditions($boolFilters, $excludedFieldnames)
    {
        foreach ($this->filterConditions as $fieldname => $filterConditionArray) {
            if (!array_key_exists($fieldname, $excludedFieldnames)) {
                foreach ($filterConditionArray as $filterCondition) {
                    if (is_array($filterCondition)) {
                        $boolFilters[] = $filterCondition;
                    } else {
                        $boolFilters[] = ['term' => [$this->tenantConfig->getFieldNameMapped($fieldname, true) => $filterCondition]];
                    }
                }
            }
        }

        return $boolFilters;
    }

    /**
     * builds query condition of query filters
     *
     * @param array $queryFilters
     * @param array $excludedFieldnames
     *
     * @return array
     */
    protected function buildQueryConditions($queryFilters, $excludedFieldnames)
    {
        foreach ($this->queryConditions as $fieldname => $queryConditionArray) {
            if (!array_key_exists($fieldname, $excludedFieldnames)) {
                foreach ($queryConditionArray as $queryCondition) {
                    if (is_array($queryCondition)) {
                        $queryFilters[] = $queryCondition;
                    } else {
                        if ($fieldname) {
                            $queryFilters[] = ['match' => [$this->tenantConfig->getFieldNameMapped($fieldname) => $queryCondition]];
                        } else {
                            $fieldnames = $this->tenantConfig->getSearchAttributes();
                            $mappedFieldnames = [];
                            foreach ($fieldnames as $searchFieldnames) {
                                $mappedFieldnames[] = $this->tenantConfig->getFieldNameMapped($searchFieldnames, true);
                            }

                            $queryFilters[] = ['multi_match' => [
                                'query' => $queryCondition,
                                'fields' => $mappedFieldnames,
                            ]];
                        }
                    }
                }
            }
        }

        return $queryFilters;
    }

    /**
     * loads element by id
     *
     * @param int $elementId
     *
     * @return array|IndexableInterface
     */
    protected function loadElementById($elementId)
    {
        /** @var ElasticSearch $tenantConfig */
        $tenantConfig = $this->getTenantConfig();
        $mockup = null;
        if (isset($this->hitData[$elementId])) {
            $hitData = $this->hitData[$elementId];
            $sourceData = $hitData['_source'];

            //mapping of relations
            $relationFormatPimcore = [];
            foreach ($sourceData['relations'] ?: [] as $name => $relation) {
                $relationFormatPimcore[] = ['fieldname' => $name, 'dest' => $relation[0], 'type' => 'object'];
            }
            $mergedAttributes = array_merge($sourceData['system'], $sourceData['attributes']);
            $mockup = $tenantConfig->createMockupObject($elementId, $mergedAttributes, $relationFormatPimcore);
        }

        return $mockup;
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     *
     * @return void
     */
    public function prepareGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        if ($fieldname) {
            $this->preparedGroupByValues[$this->tenantConfig->getFieldNameMapped($fieldname, true)] = ['countValues' => $countValues, 'fieldnameShouldBeExcluded' => $fieldnameShouldBeExcluded];
            $this->preparedGroupByValuesLoaded = false;
        }
    }

    /**
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded
     * @param array $aggregationConfig
     *
     * @throws \Exception
     */
    public function prepareGroupByValuesWithConfig($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true, array $aggregationConfig = [])
    {
        if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
            throw new \Exception('Custom sub aggregations are not supported for variant mode VARIANT_MODE_INCLUDE_PARENT_OBJECT');
        }

        if ($fieldname) {
            $this->preparedGroupByValues[$this->tenantConfig->getFieldNameMapped($fieldname, true)] = [
                'countValues' => $countValues,
                'fieldnameShouldBeExcluded' => $fieldnameShouldBeExcluded,
                'aggregationConfig' => $aggregationConfig,
            ];
            $this->preparedGroupByValuesLoaded = false;
        }
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     *
     * @return void
     */
    public function prepareGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        if ($fieldname) {
            $this->preparedGroupByValues[$this->tenantConfig->getFieldNameMapped($fieldname, true)] = ['countValues' => $countValues, 'fieldnameShouldBeExcluded' => $fieldnameShouldBeExcluded];
            $this->preparedGroupByValuesLoaded = false;
        }
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     *
     * @return void
     */
    public function prepareGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        $this->preparedGroupByValues[$this->tenantConfig->getFieldNameMapped($fieldname)] = ['countValues' => $countValues, 'fieldnameShouldBeExcluded' => $fieldnameShouldBeExcluded];
        $this->preparedGroupByValuesLoaded = false;
    }

    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues()
    {
        $this->preparedGroupByValuesLoaded = false;
        $this->preparedGroupByValues = [];
        $this->preparedGroupByValuesResults = [];
    }

    /**
     * loads group by values based on system either from local variable if prepared or directly from product index
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        return $this->doGetGroupByValues($this->tenantConfig->getFieldNameMapped($fieldname), $countValues, $fieldnameShouldBeExcluded);
    }

    /**
     * loads group by values based on fieldname either from local variable if prepared or directly from product index
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        return $this->doGetGroupByValues($this->tenantConfig->getFieldNameMapped($fieldname, true), $countValues, $fieldnameShouldBeExcluded);
    }

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        return $this->doGetGroupByValues($this->tenantConfig->getFieldNameMapped($fieldname, true), $countValues, $fieldnameShouldBeExcluded);
    }

    /**
     * checks if group by values are loaded and returns them
     *
     * @param string $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded
     *
     * @return array
     */
    protected function doGetGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        if (!$this->preparedGroupByValuesLoaded) {
            $this->doLoadGroupByValues();
        }

        $results = $this->preparedGroupByValuesResults[$fieldname];
        if ($results) {
            if ($countValues) {
                return $results;
            } else {
                $resultsWithoutCounts = [];
                foreach ($results as $result) {
                    $resultsWithoutCounts[] = $result['value'];
                }

                return $resultsWithoutCounts;
            }
        } else {
            return [];
        }
    }

    /**
     * loads all prepared group by values
     *   1 - get general filter (= filter of fields don't need to be considered in group by values or where fieldnameShouldBeExcluded set to false)
     *   2 - for each group by value create a own aggregation section with all other group by filters added
     *
     * @throws \Exception
     */
    protected function doLoadGroupByValues()
    {
        // create general filters and queries
        $toExcludeFieldnames = [];
        /** @var ElasticSearch $tenantConfig */
        $tenantConfig = $this->getTenantConfig();
        foreach ($this->preparedGroupByValues as $fieldname => $config) {
            if ($config['fieldnameShouldBeExcluded']) {
                $toExcludeFieldnames[$tenantConfig->getReverseMappedFieldName($fieldname)] = $fieldname;
            }
        }

        $boolFilters = [];
        $queryFilters = [];

        //pre conditions
        $boolFilters = $this->buildSystemConditions($boolFilters);

        //user specific filters
        $boolFilters = $this->buildFilterConditions($boolFilters, $toExcludeFieldnames);

        //relation conditions
        $boolFilters = $this->buildRelationConditions($boolFilters, $toExcludeFieldnames);

        //query conditions
        $queryFilters = $this->buildQueryConditions($queryFilters, []);

        $aggregations = [];

        //calculate already filtered attributes
        $filteredFieldnames = [];
        foreach ($this->filterConditions as $fieldname => $condition) {
            if (!array_key_exists($fieldname, $toExcludeFieldnames)) {
                $filteredFieldnames[$fieldname] = $fieldname;
            }
        }
        foreach ($this->relationConditions as $fieldname => $condition) {
            if (!array_key_exists($fieldname, $toExcludeFieldnames)) {
                $filteredFieldnames[$fieldname] = $fieldname;
            }
        }

        foreach ($this->preparedGroupByValues as $fieldname => $config) {

            //exclude all attributes that are already filtered
            $shortFieldname = $this->getTenantConfig()->getReverseMappedFieldName($fieldname);

            $specificFilters = [];
            //user specific filters
            $specificFilters = $this->buildFilterConditions($specificFilters, array_merge($filteredFieldnames, [$shortFieldname => $shortFieldname]));
            //relation conditions
            $specificFilters = $this->buildRelationConditions($specificFilters, array_merge($filteredFieldnames, [$shortFieldname => $shortFieldname]));

            if (!empty($config['aggregationConfig'])) {
                $aggregation = $config['aggregationConfig'];
            } else {
                $aggregation = [
                    'terms' => ['field' => $fieldname, 'size' => self::INTEGER_MAX_VALUE, 'order' => ['_key' => 'asc']],
                ];
            }

            if ($specificFilters) {
                $aggregations[$fieldname] = [
                    'filter' => [
                        'bool' => [
                            'must' => $specificFilters,
                        ],
                    ],
                    'aggs' => [
                        $fieldname => $aggregation,
                    ],
                ];

                //necessary to calculate correct counts of search results for filter values
                if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                    $aggregations[$fieldname]['aggs'][$fieldname]['aggs'] = [
                        'objectCount' => ['cardinality' => ['field' => 'system.o_virtualProductId']],
                    ];
                }
            } else {
                $aggregations[$fieldname] = $aggregation;

                //necessary to calculate correct counts of search results for filter values
                if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                    $aggregations[$fieldname]['aggs'] = [
                        'objectCount' => ['cardinality' => ['field' => 'system.o_virtualProductId']],
                    ];
                }
            }
        }

        if ($aggregations) {
            $params = [];
            $params['index'] = $this->getIndexName();
            $params['body']['_source'] = false;
            $params['body']['size'] = 0;
            $params['body']['from'] = $this->getOffset();
            $params['body']['aggs'] = $aggregations;

            // build query for request
            $variantModeForAggregations = $this->getVariantMode();
            if ($this->getVariantMode() == ProductListInterface::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                $variantModeForAggregations = ProductListInterface::VARIANT_MODE_VARIANTS_ONLY;
            }

            // build query for request
            $params = $this->buildQuery($params, $boolFilters, $queryFilters, $variantModeForAggregations);

            // send request
            $result = $this->sendRequest($params);

            if ($result['aggregations']) {
                foreach ($result['aggregations'] as $fieldname => $aggregation) {
                    $buckets = $this->searchForBuckets($aggregation);

                    $groupByValueResult = [];
                    if ($buckets) {
                        foreach ($buckets as $bucket) {
                            if ($this->getVariantMode() == self::VARIANT_MODE_INCLUDE_PARENT_OBJECT) {
                                $groupByValueResult[] = ['value' => $bucket['key'], 'count' => $bucket['objectCount']['value']];
                            } else {
                                $data = $this->convertBucketValues($bucket);
                                $groupByValueResult[] = $data;
                            }
                        }
                    }

                    $this->preparedGroupByValuesResults[$fieldname] = $groupByValueResult;
                }
            }
        } else {
            $this->preparedGroupByValuesResults = [];
        }

        $this->preparedGroupByValuesLoaded = true;
    }

    /**
     * Deep search for buckets in result aggregations array, as the structure of the result array
     * may differ dependent on the used aggregations (i.e. date filters, nested aggr, ...)
     *
     * @param array $aggregations
     *
     * @return array
     */
    protected function searchForBuckets(array $aggregations)
    {
        if (array_key_exists('buckets', $aggregations)) {
            return $aggregations['buckets'];
        }

        // usually the relevant key is at the very end of the array so we reverse the order
        $aggregations = array_reverse($aggregations, true);

        foreach ($aggregations as $aggregation) {
            if (!is_array($aggregation)) {
                continue;
            }
            $buckets = $this->searchForBuckets($aggregation);
            if (!empty($buckets)) {
                return $buckets;
            }
        }

        return [];
    }

    /**
     * Recursively convert aggregation data (sub-aggregations possible)
     *
     * @param array $bucket
     *
     * @return array
     */
    protected function convertBucketValues(array $bucket)
    {
        $data = [
            'value' => $bucket['key'],
            'count' => $bucket['doc_count'],
        ];

        unset($bucket['key']);
        unset($bucket['doc_count']);

        if (!empty($bucket)) {
            $subAggregationField = array_key_first($bucket);
            $subAggregationBuckets = $bucket[$subAggregationField];
            $reverseAggregationField = array_key_last($bucket);
            $reverseAggregationBucket = $bucket[$reverseAggregationField];

            if (array_key_exists('key_as_string', $bucket)) {          // date aggregations
                $data['key_as_string'] = $bucket['key_as_string'];
            } elseif (is_array($reverseAggregationBucket) && array_key_exists('doc_count', $reverseAggregationBucket)) { // reverse aggregation
                $data['reverse_count'] = $reverseAggregationBucket['doc_count'];
            } elseif (is_array($subAggregationBuckets['buckets'])) {        // sub aggregations
                foreach ($subAggregationBuckets['buckets'] as $bucket) {
                    $data[$subAggregationField][] = $this->convertBucketValues($bucket);
                }
            }
        }

        return $data;
    }

    /**
     * @return ElasticSearchConfigInterface
     */
    public function getTenantConfig()
    {
        return $this->tenantConfig;
    }

    /**
     * send a request to elasticsearch
     *
     * @param array $params
     *
     * @return array
     */
    protected function sendRequest(array $params)
    {
        /**
         * @var \Elasticsearch\Client $esClient
         */
        $esClient = $this->tenantConfig->getTenantWorker()->getElasticSearchClient();
        $result = [];

        if ($esClient instanceof \Elasticsearch\Client) {
            if ($this->doScrollRequest) {
                $params = array_merge(['scroll' => $this->scrollRequestKeepAlive], $params);
                //kind of dirty hack :/
                $params['body']['size'] = $this->getLimit();
            }

            $result = $esClient->search($params);

            if ($this->doScrollRequest) {
                $additionalHits = [];
                $scrollId = $result['_scroll_id'];

                while (true) {
                    $additionalResult = $esClient->scroll(['scroll_id' => $scrollId, 'scroll' => $this->scrollRequestKeepAlive]);

                    if (count($additionalResult['hits']['hits'])) {
                        $additionalHits = array_merge($additionalHits, $additionalResult['hits']['hits']);
                        $scrollId = $additionalResult['_scroll_id'];
                    } else {
                        break;
                    }
                }
                $result['hits']['hits'] = array_merge($result['hits']['hits'], $additionalHits);
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getIndexName()
    {
        if (!$this->indexName) {
            $this->indexName = ($this->tenantConfig->getClientConfig('indexName')) ? strtolower($this->tenantConfig->getClientConfig('indexName')) : strtolower($this->tenantConfig->getTenantName());
        }

        return $this->indexName;
    }

    /**
     *  -----------------------------------------------------------------------------------------
     *   Methods for Iterator
     *  -----------------------------------------------------------------------------------------
     */

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        $this->getProducts();

        return $this->totalCount;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return mixed Can return any type.
     */
    public function current()
    {
        $this->getProducts();
        $var = current($this->products);

        return $var;
    }

    /**
     * Returns an collection of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->getProducts();
    }

    /**
     * Return a fully configured Paginator Adapter from this method.
     *
     * @return self
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return scalar scalar on success, integer
     * 0 on failure.
     */
    public function key()
    {
        $this->getProducts();
        $var = key($this->products);

        return $var;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->getProducts();
        $var = next($this->products);

        return $var;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->getProducts();
        reset($this->products);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $var = $this->current() !== false;

        return $var;
    }

    /**
     * Get the score from a loaded product list based on a (Pimcore) product Id.
     *
     * @param int $productId the Pimcore product Id.
     *
     * @return float the score returned by Elastic Search.
     *
     * @throws \Exception if loadFromSource mode is not true.
     */
    public function getScoreFromLoadedList(int $productId): float
    {
        if (isset($this->hitData[$productId])) {
            return $this->hitData[$productId]['_score'];
        }

        return 0.0;
    }
}
