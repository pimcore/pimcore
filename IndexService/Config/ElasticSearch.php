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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

/**
 * Class \OnlineShop\Framework\IndexService\Config\ElasticSearch
 *
 * Default configuration for elastic search as product index implementation.
 */
class ElasticSearch extends AbstractConfig implements IMockupConfig, IElasticSearchConfig {

    protected $clientConfig = [];

    /**
     * contains the mapping for the fields in Elasticsearch
     *
     * @var array
     */
    protected $fieldMapping = [
        'o_id' => 'system.o_id',
        'o_classId' => 'system.o_classId',
        'o_virtualProductId'=> 'system.o_virtualProductId',
        'o_virtualProductActive'=> 'system.o_virtualProductActive',
        'o_parentId'=> 'system.o_parentId',
        'o_type'=> 'system.o_type',
        'categoryIds'=> 'system.categoryIds',
        'parentCategoryIds'=> 'system.parentCategoryIds',
        'categoryPaths'=> 'system.categoryPaths',
        'priceSystemName'=> 'system.priceSystemName',
        'active'=> 'system.active',
        'inProductList'=> 'system.inProductList',
    ];

    /**
     * @var array
     */
    protected $indexSettings = null;

    /**
     * @var array
     */
    protected $elasticSearchClientParams = null;

    /**
     * @param string $tenantName
     * @param $tenantConfig
     * @param null $totalConfig
     */
    public function __construct($tenantName, $tenantConfig, $totalConfig = null) {
        parent::__construct($tenantName, $tenantConfig, $totalConfig);

        $this->indexSettings = json_decode($tenantConfig->indexSettingsJson, true);
        $this->elasticSearchClientParams = json_decode($tenantConfig->elasticSearchClientParamsJson, true);

        if($tenantConfig->clientConfig){
            $this->clientConfig = $tenantConfig->clientConfig->toArray();
        }

        $config = $tenantConfig->toArray();
        foreach($config['columns'] as $col){
            $attributeType = 'attributes';
            if($col['interpreter']){
                $class = $col['interpreter'];
                $tmp = new $class;
                if($tmp instanceof \OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter){
                    $attributeType = 'relations';
                }
            }
            $this->fieldMapping[$col['name']] = $attributeType.'.'.$col['name'];
        }
    }

    /**
     * returns the full field name
     *
     * @param $fieldName
     * @return string
     */
    public function getFieldNameMapped($fieldName){
        return $this->fieldMapping[$fieldName] ?: $fieldName;
    }

    /**
     * @param string $property
     *
     * @return array|string
     */
    public function getClientConfig($property = null)
    {
        return $property
            ? $this->clientConfig[$property]
            : $this->clientConfig
            ;
    }

    /**
     * @return array
     */
    public function getIndexSettings() {
        return $this->indexSettings;
    }

    /**
     * @return array
     */
    public function getElasticSearchClientParams() {
        return $this->elasticSearchClientParams;
    }


    /**
     * checks, if product should be in index for current tenant
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return bool
     */
    public function inIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        return true;
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(\OnlineShop\Framework\Model\IIndexable $object, $subObjectId = null) {
        return null;
    }

    /**
     * populates index for tenant relations based on gived data
     *
     * @param mixed $objectId
     * @param mixed $subTenantData
     * @param mixed $subObjectId
     * @return void
     */
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null) {
        // nothing to do
        return;
    }

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition() {
        return;
    }


    /**
     * @var \OnlineShop\Framework\IndexService\Worker\ElasticSearch
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \OnlineShop\Framework\IndexService\Worker\ElasticSearch
     */
    public function getTenantWorker() {
        if(empty($this->tenantWorker)) {
            $this->tenantWorker = new \OnlineShop\Framework\IndexService\Worker\DefaultElasticSearch($this);
        }
        return $this->tenantWorker;
    }

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations) {
        return new \OnlineShop\Framework\Model\DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return \OnlineShop\Framework\Model\IIndexable | array
     */
    public function getObjectMockupById($objectId) {
        return $this->getTenantWorker()->getMockupFromCache($objectId);
    }
}
