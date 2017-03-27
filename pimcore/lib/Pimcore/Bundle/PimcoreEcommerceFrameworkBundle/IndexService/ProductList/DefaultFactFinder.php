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

use Monolog\Logger;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IIndexable;
use Psr\Http\Message\ResponseInterface;
use Zend\Paginator\Adapter\AdapterInterface;

class DefaultFactFinder implements IProductList
{
    /**
     * @var IIndexable[]
     */
    protected $products = null;

    /**
     * @var string
     */
    protected $tenantName;

    /**
     * @var bool
     */
    protected $transmitSessionId = true;

    /**
     * contains a mapping from productId => array Index
     * useful when you have to merge child products to there parent and you don't want to iterate each time over the list
     *
     * @var array
     */
    protected $productPositionMap = [];

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IFactFinderConfig
     */
    protected $tenantConfig;

    /**
     * @var bool
     */
    protected $useAsn = true;

    /**
     * @var null
     */
    protected $followSearchParam = null;

    /**
     * @var array
     */
    protected $defaultParams = [];

    /**
     * @var null|int
     */
    protected $totalCount = null;

    /**
     * @var string
     */
    protected $variantMode = IProductList::VARIANT_MODE_INCLUDE;

    /**
     * @var integer
     */
    protected $limit = 10;

    /**
     * @var integer
     */
    protected $offset = 0;

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory
     */
    protected $category;

    /**
     * @var bool
     */
    protected $inProductList = true;

    /**
     * json result from factfinder
     * @var string[]
     */
    protected $searchResult;

    /**
     * @var string[]
     */
    protected $groupedValues = [];


    /**
     * @var string[]
     */
    protected $conditions = array();

    /**
     * @var string[]
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
     * @return boolean
     */
    public function getTransmitSessionId()
    {
        return $this->transmitSessionId;
    }

    /**
     * @param boolean $transmitSessionId
     *
     * @return $this
     */
    public function setTransmitSessionId($transmitSessionId)
    {
        $this->transmitSessionId = $transmitSessionId;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getUseAsn()
    {
        return $this->useAsn;
    }

    /**
     * @param boolean $useAsn
     *
     * @return $this
     */
    public function setUseAsn($useAsn)
    {
        $this->useAsn = $useAsn;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductPositionMap()
    {
        return $this->productPositionMap;
    }

    /**
     * @return array
     */
    public function getDefaultParams()
    {
        return $this->defaultParams;
    }

    /**
     * @param array $defaultParams
     *
     * @return $this
     */
    public function setDefaultParams($defaultParams)
    {
        $this->defaultParams = $defaultParams;
        return $this;
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
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IConfig $tenantConfig
     */
    public function __construct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IConfig $tenantConfig)
    {
        $this->tenantName = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;

        // init logger
        $this->logger = \Pimcore::getContainer()->get("monolog.logger.pimcore_ecommerce_factfinder");
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct[]
     */
    public function getProducts()
    {
        if ($this->products === null)
        {
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
        $this->conditions[ $fieldname ][] = $condition;
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
     * resets all conditions of product list
     */
    public function resetConditions()
    {
        $this->conditions = array();
        $this->queryConditions = array();
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
     * @param boolean $inProductList
     */
    public function setInProductList($inProductList)
    {
        $this->products = null;
        $this->inProductList = (bool)$inProductList;
    }

    /**
     * @return boolean
     */
    public function getInProductList() {
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


    public function setCategory(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory $category)
    {
        $this->products = null;
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setVariantMode($variantMode)
    {
        $this->products = null;
        $this->variantMode = $variantMode;
    }

    public function getVariantMode() {
        return $this->variantMode;
    }


    /**
     * @return IIndexable[]
     */
    public function load()
    {
        // send request
        $data = $this->sendRequest();

        if(!is_array($data)){
            throw new \Exception("Got no data from Factfinder " .print_r($data,true));
        }

        if(array_key_exists('error', $data))
        {
            throw new Exception($data['error']);
        }
        $searchResult = $data['searchResult'];


        // load products found
        $this->products = $this->productPositionMap= [];
        $i = 0;
        foreach($searchResult['records'] as $item)
        {
            $id = null;

            // variant handling
            switch($this->getVariantMode())
            {
                case self::VARIANT_MODE_INCLUDE:
                case self::VARIANT_MODE_HIDE:
                    $id = $item['id'];
                    break;

                case self::VARIANT_MODE_INCLUDE_PARENT_OBJECT:
                    $id = $item['record']['MasterProductID'];
                    break;
            }

            if($id)
            {
                $product = $this->tenantConfig->getObjectMockupById( $id );
                if($product)
                {
                    $this->products[] = $product;
                    $this->productPositionMap[$product->getId()] = $i;
                    $i++;
                }
            }
            else
            {
                $this->getLogger()->err(sprintf('object "%s" not found', $id));
            }
        }


        // extract grouped values
        $this->groupedValues = [];
        $elements = $searchResult['groups'];
        foreach($elements as $item)
        {
            // add selected
            if($item['filterStyle'] == 'MULTISELECT' || $item['filterStyle'] == 'DEFAULT')
            {
                foreach($item['selectedElements'] as $selected)
                {
                    if($item['selectionType'] == 'singleHideUnselected')
                    {
                        $selected['recordCount'] = (int)$searchResult['resultCount'];
                    }
                    array_unshift($item['elements'], $selected);
                }
            }

            $this->groupedValues[ $item['name'] ] = $item;
        }


        // save request
        $this->totalCount = (int)$searchResult['resultCount'];
        $this->searchResult = $searchResult;


        return $this->products;
    }


    /**
     * builds system conditions
     *
     * @param array $filter
     * @return array
     */
    protected function buildSystemConditions(array $filter)
    {
        // add sub tenant filter
        $tenantCondition = $this->tenantConfig->getSubTenantCondition();
        if($tenantCondition)
        {
            foreach($tenantCondition as $key => $value)
            {
                $filter[$key] = $value;
            }
        }


        // variant handling
        switch($this->getVariantMode())
        {
            case self::VARIANT_MODE_HIDE:
                $filter['duplicateFilter'] = 'NONE';
                break;

            case self::VARIANT_MODE_INCLUDE:
                $filter['duplicateFilter'] = 'NONE';
                break;

            default:
            case self::VARIANT_MODE_INCLUDE_PARENT_OBJECT:
                // nothing todo, default factfinder
                break;
        }


        return $filter;
    }

    /**
     * builds filter condition of user specific conditions
     *
     * @param array $params
     * @return array
     */
    protected function buildFilterConditions(array $params)
    {
        foreach ($this->conditions as $fieldname => $condition)
        {
            $value = '';
            if(is_array($condition))
            {
                $and = [];
                foreach($condition as $or)
                {
                    $and[] = is_array($or)
                        ? implode('~~~', $or)   // OR
                        : $or
                    ;
                }

                $value = implode('___', $and);  // AND
            }
            else
            {
                $value = $condition;
            }

            $params[ 'filter' . $fieldname ] = $value;
        }


        if($this->conditionPriceFrom || $this->conditionPriceTo)
        {
            // Format: 0+-+175
            if(!$this->conditionPriceTo)
            {
                $params['filterGRUNDPREIS'] = $this->conditionPriceFrom;
            }
            else
            {
                $params['filterGRUNDPREIS'] = sprintf('%d - %d'
                    , $this->conditionPriceFrom
                    , $this->conditionPriceTo
                );
            }
        }


        return $params;
    }

    /**
     * builds query condition of query filters
     *
     * @param array $params
     * @return array
     */
    protected function buildQueryConditions(array $params)
    {
        $query = '';

        foreach ($this->queryConditions as $fieldname => $condition)
        {
            $query .= is_array($condition)
                ? implode(' ', $condition)
                : $condition
            ;
        }

        if($query){
            $params['query'] = $query;
        }else{
            $params['navigation'] = 'true';
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
        if($this->getOrderKey())
        {
            $appendSort = function ($field, $order = null) use(&$params) {

                $field = $field === self::ORDERKEY_PRICE
                    ? 'GRUNDPREIS'
                    : $field
                ;

                $params['sort' . $field] = $order ?: 'asc';
            };


            if(is_array($this->getOrderKey()))
            {
                foreach($this->getOrderKey() as $orderKey)
                {
                    $appendSort($orderKey[0], $orderKey[1]);
                }
            }
            else
            {
                $appendSort($this->getOrderKey(), $this->getOrder());
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
        throw new \Exception('not yet implemented');
    }


    /**
     * resets all set prepared group by values
     *
     * @return void
     */
    public function resetPreparedGroupByValues()
    {
        // throw new Exception('not yet implemented');
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
        // TODO: Implement prepareGroupByRelationValues() method.
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
        // TODO: Implement prepareGroupBySystemValues() method.
    }

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
    public function getGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        // TODO: Implement getGroupBySystemValues() method.
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

        if(array_key_exists($fieldname, $this->groupedValues))
        {
            $field = $this->groupedValues[ $fieldname ];

            foreach($field['elements'] as $item)
            {
                $groups[] = [
                    'value' => $item['name']
                    , 'count' => $item['recordCount']
                ];
            }
        }


        return $groups;
    }


    public function getGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true)
    {
        return $this->getGroupByValues($fieldname, $countValues, $fieldnameShouldBeExcluded);
    }

/**
     * Do a FactFinder request and consider the timeout header
     * If a timeout occures it will retry 4 times to get the data (delay of 0.25 Seconds per request)
     *
     * @param string $url The URL to call
     * @param int $trys internal counter - don't pass a value!
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function doRequest($url,$trys = 0){
        // start request
        $this->getLogger()->info('Request: ' . $url);

        $client = \Pimcore::getContainer()->get("pimcore.http_client");
        $response = $client->request('GET', $url);

        $factFinderTimeout = $response->getHeader('X-FF-Timeout');
        if($factFinderTimeout === 'true'){
            $errorMessage = "FactFinder Read timeout:" . $url.' X-FF-RefKey: ' . $response->getHeader('X-FF-RefKey').' Tried: ' . ($trys+1);
            $this->getLogger()->err($errorMessage);
            $trys++;
            if($trys > 2){
                $this->getLogger()->err('FactFinder Read timeout: Max tries of 3 reached. Gave up.');
                return $response;
            }
            sleep(1);
            $response = $this->doRequest($url,$trys);
        }
        return $response;
    }

    /**
     * returns the search url
     *
     * @return string
     */
    protected function getSearchUrl(){
        return sprintf('http://%s/%s/Search.ff?'
            , $this->tenantConfig->getClientConfig('host')
            , $this->tenantConfig->getClientConfig('customer')
        );
    }

    public function getSearchParams(){
        $params = [];
        if($data = $this->getLastResultData()){
            $url = str_replace('/FACT-Finder/Search.ff?','',$data['searchResult']['searchParams']);
            parse_str($url,$params);
        }
        return $params;
    }

    /**
     * returns the Fact-Finder query
     * @return string
     */
    public function getQuery(){
        // init
        $params = $this->getDefaultParams();


        // add conditions
        $params = $this->buildSystemConditions( $params );

        $params = $this->buildFilterConditions( $params );

        $params = $this->buildQueryConditions( $params );

        $params = $this->buildSorting( $params );


        // add paging
        if($this->getOffset() == 0){
            $params['page']=1;
        }else{
            $params['page'] = ceil($this->getOffset() / $this->getLimit())+1;

        }
        $params['productsPerPage'] = $this->getLimit();
        $params['idsOnly'] = 'true';
        # $params['navigation'] = 'true';
        $params['useAsn'] = $this->getUseAsn() ? 'true' : 'false';
        if($this->getFollowSearchParam()){
            $params['followSearch'] = $this->getFollowSearchParam();
        }

        if($this->getTransmitSessionId()){
            $params['sid'] = session_id();
        }
        $url = $this->getSearchUrl().'?';
        $url .= http_build_query($params);
        $url .= '&format=json';

        $internalIPAddresses = explode_and_trim(',',$this->tenantConfig->getClientConfig('internalIPAddresses'));
        if(!empty($internalIPAddresses)){
            if(in_array(\Pimcore\Tool::getClientIp(),$internalIPAddresses)){
                $url .= '&log=internal';
            }
        }

        return $url;
    }


    /**
     * @return null
     */
    public function getFollowSearchParam()
    {
        return $this->followSearchParam;
    }

    /**
     * @param null $followSearchParam
     *
     * @return $this
     */
    public function setFollowSearchParam($followSearchParam)
    {
        $this->followSearchParam = $followSearchParam;
        return $this;
    }

    /**
     * @param $params
     *
     * @return array
     */
    protected function sendRequest()
    {

        $url  = $this->getQuery();
        $this->requestUrl = $url;
        $response = $this->doRequest($url);
        $data = json_decode((string)$response->getBody(), true);

        if(!$data) {
            throw new \Exception('Request didn\'t return anything');
        }

        if($data['searchResult']['timedOut']){
            throw new \Exception('FactFinder Read timeout in response JSON: ' . $url);
        }
        $this->resultData = $data;

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
     * @link http://php.net/manual/en/countable.count.php
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
     * @link http://php.net/manual/en/iterator.current.php
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
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
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
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar on success, integer
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
        next($this->products);
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

    public function getSearchResult() {
        return $this->searchResult;
    }
}