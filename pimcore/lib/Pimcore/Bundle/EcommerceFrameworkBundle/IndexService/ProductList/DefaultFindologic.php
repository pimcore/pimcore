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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList;

use Monolog\Logger;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic\SelectCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Zend\Paginator\Adapter\AdapterInterface;

class DefaultFindologic implements IProductList
{
    /**
     * @var string
     */
    protected $userIp = '';

    /**
     * @var string
     */
    protected $referer = '';

    /**
     * @var string
     */
    protected $revision = '0.1';

    /**
     * @var IIndexable[]
     */
    protected $products = null;

    /**
     * @var string
     */
    protected $tenantName;

    /**
     * @var \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IFindologicConfig
     */
    protected $tenantConfig;

    /**
     * @var null|int
     */
    protected $totalCount = null;

    /**
     * @var string
     */
    protected $variantMode = IProductList::VARIANT_MODE_INCLUDE;

    /**
     * @var int
     */
    protected $limit = 10;

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
    protected $inProductList = true;

    /**
     * json result from findologic
     *
     * @var string[]
     */
    protected $response;

    /**
     * @var string[]
     */
    protected $groupedValues;

    /**
     * @var string[]
     */
    protected $conditions = [];

    /**
     * @var string[]
     */
    protected $queryConditions = [];

    /**
     * @var float
     */
    protected $conditionPriceFrom = null;

    /**
     * @var float
     */
    protected $conditionPriceTo = null;

    /**
     * @var string
     */
    protected $order;

    /**
     * @var string | array
     */
    protected $orderKey;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $supportedOrderKeys = ['label', 'price', 'salesFrequency', 'dateAdded'];

    /**
     * @var int
     */
    protected $timeout = 3;

    /**
     * @param IConfig $tenantConfig
     */
    public function __construct(IConfig $tenantConfig)
    {
        $this->tenantName = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;

        // init logger
        $this->logger = \Pimcore::getContainer()->get('monolog.logger.pimcore_ecommerce_findologic');

        // set defaults for required params
        $this->userIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['REMOTE_ADDR'];
        $this->referer = $_SERVER['HTTP_REFERER'];
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct[]
     */
    public function getProducts()
    {
        if ($this->products === null) {
            $this->load();
        }

        return $this->products;
    }

    /**
     * @param string $condition
     * @param string $fieldname
     */
    public function addCondition($condition, $fieldname = '')
    {
        $this->products = null;
        $this->conditions[$fieldname][] = $condition;
    }

    /**
     * @param string $fieldname
     *
     * @return void
     */
    public function resetCondition($fieldname)
    {
        $this->products = null;
        unset($this->conditions[$fieldname]);
    }

    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results
     *
     * @param $condition
     * @param string $fieldname
     */
    public function addQueryCondition($condition, $fieldname = '')
    {
        $this->products = null;
        $this->queryConditions[$fieldname][] = $condition;
    }

    /**
     * Reset query condition for fieldname
     *
     * @param $fieldname
     *
     * @return mixed
     */
    public function resetQueryCondition($fieldname)
    {
        $this->products = null;
        unset($this->queryConditions[$fieldname]);
    }

    /**
     * resets all conditions of product list
     */
    public function resetConditions()
    {
        $this->conditions = [];
        $this->queryConditions = [];
        $this->conditionPriceFrom = null;
        $this->conditionPriceTo = null;
        $this->products = null;
    }

    /**
     * @param string $fieldname
     * @param string $condition
     */
    public function addRelationCondition($fieldname, $condition)
    {
        $this->products = null;
        $this->addCondition($condition, $fieldname);
    }

    /**
     * @param null|float $from
     * @param null|float $to
     */
    public function addPriceCondition($from = null, $to = null)
    {
        $this->products = null;
        $this->conditionPriceFrom = $from;
        $this->conditionPriceTo = $to;
    }

    /**
     * @param bool $inProductList
     */
    public function setInProductList($inProductList)
    {
        $this->products = null;
        $this->inProductList = (bool)$inProductList;
    }

    /**
     * @return bool
     */
    public function getInProductList()
    {
        return $this->inProductList;
    }

    /**
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->products = null;
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param $orderKey string | array  - either single field name, or array of field names or array of arrays (field name, direction)
     */
    public function setOrderKey($orderKey)
    {
        $this->products = null;
        $this->orderKey = $orderKey;
    }

    public function getOrderKey()
    {
        return $this->orderKey;
    }

    public function setLimit($limit)
    {
        if ($this->limit != $limit) {
            $this->products = null;
        }
        $this->limit = $limit;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setOffset($offset)
    {
        if ($this->offset != $offset) {
            $this->products = null;
        }
        $this->offset = $offset;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setCategory(AbstractCategory $category)
    {
        $this->products = null;
        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setVariantMode($variantMode)
    {
        $this->products = null;
        $this->variantMode = $variantMode;
    }

    public function getVariantMode()
    {
        return $this->variantMode;
    }

    /**
     * @return IIndexable[]
     */
    public function load()
    {
        // init
        $params = [];

        // add conditions
        $params = $this->buildSystemConditions($params);
        $params = $this->buildFilterConditions($params);
        $params = $this->buildQueryConditions($params);
        $params = $this->buildSorting($params);

        // add paging
        $params['first'] = $this->getOffset();
        $params['count'] = $this->getLimit();

        // send request
        $data = $this->sendRequest($params);

        // TODO error handling

        // load products found
        $this->products = [];
        foreach ($data->products->children() as $item) {
            $id = null;

            // variant handling
            switch ($this->getVariantMode()) {
                case self::VARIANT_MODE_INCLUDE:
                case self::VARIANT_MODE_HIDE:
                    $id = $item['id'];
                    break;

                case self::VARIANT_MODE_INCLUDE_PARENT_OBJECT:
                    // NOT POSSIBLE
                    break;
            }

            if ($id) {
                $product = $this->tenantConfig->getObjectMockupById($id);
                if ($product) {
                    $this->products[] = $product;
                }
            } else {
                $this->getLogger()->err(sprintf('object "%s" not found', $id));
            }
        }

        // extract grouped values
        $this->groupedValues = [];
        $filters = json_encode($data->filters);
        $filters = json_decode($filters);
        if ($filters->filter) {
            foreach ($filters->filter as $filter) {
                $this->groupedValues[$filter->name] = $filter;
            }
        }

        // save request
        $this->totalCount = (int)$data->results->count;
        $this->response = $data;

        return $this->products;
    }

    /**
     * builds system conditions
     *
     * @param array $filter
     *
     * @return array
     */
    protected function buildSystemConditions(array $filter)
    {
        // add sub tenant filter
        $tenantCondition = $this->tenantConfig->getSubTenantCondition();
        if ($tenantCondition) {
            $filter['usergrouphash'] = $tenantCondition;
        }

        // variant handling
        switch ($this->getVariantMode()) {
            case self::VARIANT_MODE_HIDE:
                break;

            case self::VARIANT_MODE_INCLUDE:
                break;

            default:
            case self::VARIANT_MODE_INCLUDE_PARENT_OBJECT:
                break;
        }

        return $filter;
    }

    /**
     * builds filter condition of user specific conditions
     *
     * @param array $params
     *
     * @return array
     */
    protected function buildFilterConditions(array $params)
    {
        foreach ($this->conditions as $fieldname => $condition) {
            if (is_array($condition)) {
                foreach ($condition as $cond) {
                    $params['attrib'][$fieldname] = array_merge($params['attrib'][$fieldname] ?: [], $cond);
                }
            } else {
                $params['attrib'][$fieldname] = $condition;
            }
        }

        if ($this->getCategory()) {
            $params['attrib']['cat'][] = $this->buildCategoryTree($this->getCategory());
        }

        if ($this->conditionPriceTo) {
            $params['attrib']['price']['max'] = $this->conditionPriceTo;
        }

        if ($this->conditionPriceFrom) {
            $params['attrib']['price']['min'] = $this->conditionPriceFrom;
        }

        return $params;
    }

    /**
     * create category path
     *
     * @param AbstractCategory $currentCat
     *
     * @return string
     */
    public function buildCategoryTree(AbstractCategory $currentCat)
    {
        $catTree = $currentCat->getId();
        while ($currentCat->getParent() instanceof $currentCat) {
            $catTree = $currentCat->getParentId() . '_' . $catTree;
            $currentCat = $currentCat->getParent();
        }

        return $catTree;
    }

    /**
     * builds query condition of query filters
     *
     * @param array $params
     *
     * @return array
     */
    protected function buildQueryConditions(array $params)
    {
        $query = '';

        foreach ($this->queryConditions as $fieldname => $condition) {
            $query .= is_array($condition)
                ? implode(' ', $condition)
                : $condition
            ;
        }

        if ($query) {
            $params['query'] = $query;
        }

        return $params;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function buildSorting(array $params)
    {
        // add sorting
        if ($this->getOrderKey()) {
            if (is_array($this->getOrderKey())) {
                $orderKey = $this->getOrderKey();
                $order = reset($orderKey);
                if (true === in_array($order[0], $this->supportedOrderKeys)) {
                    $params['order'] = $order[0] . ($order[1] ? ' ' . $order[1] : '');
                }
            } else {
                if (true === in_array($this->getOrderKey(), $this->supportedOrderKeys)) {
                    $params['order'] = $this->getOrderKey() . ($this->getOrder() ? ' ' . $this->getOrder() : '');
                }
            }
        }

        return $params;
    }

    /**
     * prepares all group by values for given field names and cache them in local variable
     * considers both - normal values and relation values
     *
     * @param string $fieldname
     * @param bool   $countValues
     * @param bool   $fieldnameShouldBeExcluded
     *
     * @throws \Exception
     */
    public function prepareGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // nothing todo
    }

    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues()
    {
        // nothing todo
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
        // nothing todo
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
        // nothing todo
    }

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index
     *
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        return $this->getGroupByValues($fieldname, $countValues, $fieldnameShouldBeExcluded);
    }

    /**
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded
     *
     * @return array|void
     */
    public function getGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // init
        $groups = [];

        // load values
        if ($this->groupedValues === null) {
            $this->doLoadGroupByValues();
        }

        if (array_key_exists($fieldname, $this->groupedValues)) {
            $field = $this->groupedValues[$fieldname];

            // special handling for nested category filters
            if ($this->getCategory() && $fieldname === SelectCategory::FIELDNAME) {
                $catTree = $this->buildCategoryTree($this->getCategory());

                $categories = explode('_', $catTree);

                foreach (array_slice($categories, 0, -1) as $cat) {
                    $field = $field->items->item;
                }

            }
            else if($fieldname === 'price')
            {
                $field = $this->groupedValues[ $fieldname ];

                $groups[] = [
                    'value' => null
                    , 'label' => null
                    , 'count' => null
                    , 'parameter' => $field->attributes->totalRange
                ];

            }
            else if($fieldname === \OnlineShop\Framework\FilterService\FilterType\Findologic\SelectCategory::FIELDNAME)
            {
                $rec = function (array $items) use (&$rec, &$groups) {
                    foreach ($items as $item) {
                        $groups[$item->name] = [
                            'value' => $item->name, 'label' => $item->name, 'count' => $item->frequency, 'parameter' => $item->parameters
                        ];

                        if ($item->items) {
                            $list = is_array($item->items->item)
                                ? $item->items->item
                                : [$item->items->item]
                            ;
                            $rec($list);
                        }
                    }
                };

                $rec($field->items->item);
            }

            $hits = $field->items->item instanceof \stdClass
                ? [$field->items->item]
                : $field->items->item
            ;

            foreach ($hits as $item) {
                $groups[] = [
                    'value' => $item->name, 'label' => $item->name, 'count' => $item->frequency, 'parameter' => $item->parameters
                ];
            }
        }

        return $groups;
    }

    /**
     * @param string $fieldname
     * @param bool   $countValues
     * @param bool   $fieldnameShouldBeExcluded
     *
     * @return array|void
     */
    public function getGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // init
        $relations = [];

        // load and resort data
        $values = $this->getGroupByValues($fieldname, $countValues, $fieldnameShouldBeExcluded);
        foreach ($values as $item) {
            $relations[] = $item['value'];
        }

        return $relations;
    }

    /**
     * @return IIndexable[]
     */
    protected function doLoadGroupByValues()
    {
        // init
        $params = [];

        // add conditions
        $params = $this->buildSystemConditions($params);
        $params = $this->buildFilterConditions($params);
        $params = $this->buildQueryConditions($params);
        $params = $this->buildSorting($params);

        // add paging
        $params['first'] = $this->getOffset();
        $params['count'] = $this->getLimit();

        // send request
        $data = $this->sendRequest($params);

        // TODO error handling
        //        if(array_key_exists('error', $data))
        //        {
        //            throw new Exception($data['error']);
        //        }
        //        $searchResult = $data->searchResult;

        // extract grouped values
        $this->groupedValues = [];
        $filters = json_encode($data->filters);
        $filters = json_decode($filters);
        foreach ($filters->filter as $item) {
            $this->groupedValues[$item->name] = $item;
        }

        // save request
        $this->totalCount = (int)$data->results->count;
    }

    /**
     * @param array $params
     *
     * @return \SimpleXMLElement
     *
     * @throws \Exception
     */
    protected function sendRequest(array $params)
    {
        // add system params
        $params = [
            'shopkey' => $this->tenantConfig->getClientConfig('shopKey'), 'shopurl' => $this->tenantConfig->getClientConfig('shopUrl'), 'userip' => $this->userIp, 'referer' => $this->referer, 'revision' => $this->revision
        ] + $params;

        // we have different end points for search and navigation
        $endpoint = array_key_exists('query', $params)
            ? 'index.php'
            : 'selector.php'
        ;

        // create url
        $url = sprintf(
            'http://%1$s/ps/xml_2.0/%2$s?',
            $this->tenantConfig->getClientConfig('serviceUrl'),
            $endpoint
        );
        $url .= http_build_query($params);

        $this->getLogger()->info('Request: ' . $url);

        // start request
        $start = microtime(true);
        $client = \Pimcore::getContainer()->get('pimcore.http_client');
        $response = $client->request('GET', $url, [
            'timeout' => $this->timeout
        ]);
        $this->getLogger()->info('Duration: ' . number_format(microtime(true) - $start, 3));

        if ($response->getStatusCode() != 200) {
            throw new \Exception((string)$response->getBody());
        }

        $data = simplexml_load_string((string)$response->getBody());

        return $data;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

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
     * @return AdapterInterface
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
     * @return scalar on success, integer
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
        next($this->products);
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
}
