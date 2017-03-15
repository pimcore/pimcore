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

use Pimcore\Logger;

class DefaultElasticSearch extends AbstractMockupCacheWorker implements IBatchProcessingWorker {

    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store_elastic";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup_elastic";

    /**
     * Default value for the mapping of custom attributes
     * @var bool
     */
    protected $storeCustomAttributes = true;

    /**
     * @var \Elasticsearch\Client
     */
    protected $elasticSearchClient = null;

    /**
     * index name of elastic search must be lower case
     * the index name is an alias to indexname-versionnumber
     * @var string
     */
    protected $indexName;

    /**
     * The Version number of the Index (we increas the Version number if the mapping cant be changed (reindexing process))
     * @var int
     */
    protected $indexVersion = 0;

    /**
     * @var \OnlineShop\Framework\IndexService\Config\ElasticSearch
     */
    protected $tenantConfig;


    public function __construct(\OnlineShop\Framework\IndexService\Config\ElasticSearch $tenantConfig) {
        parent::__construct($tenantConfig);
        $this->indexName = ($tenantConfig->getClientConfig('indexName')) ? strtolower($tenantConfig->getClientConfig('indexName')) : strtolower($this->name);
        $this->determineAndSetCurrentIndexVersion();
    }

    /**
     * should custom attributes be stored separately
     * @return boolean
     */
    public function getStoreCustomAttributes()
    {
        return $this->storeCustomAttributes;
    }

    /**
     * Do store custom attributes
     *
     * @param boolean $storeCustomAttributes
     */
    public function setStoreCustomAttributes($storeCustomAttributes)
    {
        $this->storeCustomAttributes = $storeCustomAttributes;
    }

    protected function getVersionFile(){
        return PIMCORE_WEBSITE_VAR.'/plugins/EcommerceFramework/elasticsearch-index-version-' . $this->indexName.'.txt';
    }

    /**
     * determines and sets the current index version
     */
    protected function determineAndSetCurrentIndexVersion(){
        $version = $this->getIndexVersion();
        if(is_readable($this->getVersionFile())){
            $version = (int)trim(file_get_contents($this->getVersionFile()));
        }else{
            file_put_contents($this->getVersionFile(),$this->getIndexVersion());
        }
        $this->indexVersion = $version;
    }

    /**
     * the versioned index-name
     * @return string
     */
    public function getIndexNameVersion(){
        return $this->indexName . '-' . $this->getIndexVersion();
    }

    /**
     * @return int
     */
    public function getIndexVersion()
    {
        return $this->indexVersion;
    }

    /**
     * @param int $indexVersion
     *
     * @return $this
     */
    public function setIndexVersion($indexVersion)
    {
        $this->indexVersion = $indexVersion;
        return $this;
    }

    /**
     * @return \Elasticsearch\Client|null
     */
    public function getElasticSearchClient() {
        if(empty($this->elasticSearchClient)) {
            $builder =  \Elasticsearch\ClientBuilder::create();
            if($this->tenantConfig->getClientConfig('logging')){
                $logger = \Elasticsearch\ClientBuilder::defaultLogger(PIMCORE_LOG_DIRECTORY . '/elasticsearch.log', \Monolog\Logger::DEBUG);
                $builder->setLogger($logger);
            }
            $builder->setHosts($this->tenantConfig->getElasticSearchClientParams()['hosts']);
            $this->elasticSearchClient = $builder->build();
        }
        return $this->elasticSearchClient;
    }



    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures() {
        $this->doCreateOrUpdateIndexStructures();
    }

    protected function getMappingParams($type)
    {

        if($type == \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_OBJECT) {
            $params = [
                'index' => $this->getIndexNameVersion(),
                'type'  => \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_OBJECT,
                'body'  => [
                    \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_OBJECT => [
                        'properties' => $this->createMappingAttributes()
                    ]
                ]
            ];
            return $params;
        } else if ($type == \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_VARIANT) {
            $params = [
                'index' => $this->getIndexNameVersion(),
                'type'  => \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_VARIANT,
                'body'  => [
                    \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_VARIANT => [
                        '_parent'    => ['type' => \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_OBJECT],
                        'properties' => $this->createMappingAttributes()
                    ]
                ]
            ];
            return $params;
        }

        throw new \Exception("Unknown Type for mapping params");
    }

    protected function doCreateOrUpdateIndexStructures($exceptionOnFailure = false) {
        $this->createOrUpdateStoreTable();

        $esClient = $this->getElasticSearchClient();

        $result = $esClient->indices()->exists(['index' => $this->getIndexNameVersion()]);
        if(!$result) {
            $result = $esClient->indices()->create(['index' => $this->getIndexNameVersion(), 'body' => ['settings' => $this->tenantConfig->getIndexSettings()]]);
            Logger::info('Index-Actions - creating new Index. Name: ' . $this->getIndexNameVersion());
            if(!$result['acknowledged']) {
                throw new \Exception("Index creation failed. IndexName: " . $this->getIndexNameVersion());
            }

            //index didn't exist -> reset index queue to make sure all products get reindexed
            $this->resetIndexingQueue();


            //create alias for new index if alias doesn't exist so far
            $aliasExists = $esClient->indices()->existsAlias(['name' => $this->indexName]);
            if(!$aliasExists) {
                Logger::info("Index-Actions - create alias for index since it doesn't exist at all. Name: " . $this->indexName);
                $params['body'] = [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $this->getIndexNameVersion(),
                                'alias' => $this->indexName,
                            ]
                        ]
                    ]
                ];
                $result = $esClient->indices()->updateAliases($params);
            }
        }


        foreach([\OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_VARIANT, \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_OBJECT] as $mappingType){
            $params = $this->getMappingParams($mappingType);

            try {
                $result = $esClient->indices()->putMapping($params);
                Logger::info('Index-Actions - updated Mapping for Index: ' . $this->getIndexNameVersion());
            } catch(\Exception $e) {
                Logger::info($e->getMessage());
                if($exceptionOnFailure){
                    throw new \Exception("Can't create Mapping - Exiting to prevent infinite loop");
                } else {
                    //when update mapping fails, start reindex mode
                    $this->startReindexMode();
                    $this->doCreateOrUpdateIndexStructures(true);
                }
            }
        }

        // index created return "true" and mapping creation returns array
        if((is_array($result) && !$result['acknowledged']) || (is_bool($result) && !$result)) {
            throw new \Exception("Index creation failed");
        }
    }

    /**
     * creates mapping attributes based on system attributes, in product index defined attributes and relations
     * can be overwritten in order to consider additional mappings for sub tenants
     *
     * @return array
     */
    protected function createMappingAttributes() {
        $mappingAttributes = array();
        //add system attributes
        $systemAttributesMapping = array();
        foreach($this->getSystemAttributes(true) as $name => $type) {
            $systemAttributesMapping[$name] = ["type" => $type, "store" => true, "index" => "not_analyzed"];
        }
        $mappingAttributes['system'] = ['type' => 'object', 'dynamic' => false, 'properties' => $systemAttributesMapping];


        //add custom defined attributes and relation attributes
        $customAttributesMapping = array();
        $relationAttributesMapping = array();

        $attributesConfig = $this->columnConfig;
        if(!empty($attributesConfig->name)) {
            $attributesConfig = array($attributesConfig);
        }
        if($attributesConfig) {
            foreach ($attributesConfig as $attribute) {
                //if configuration json is given, no other configuration is considered for mapping
                if(!empty($attribute->json)) {
                    $customAttributesMapping[$attribute->name] = json_decode($attribute->json, true);
                } else {

                    $isRelation = false;
                    $type = $attribute->type;

                    //check, if interpreter is set and if this interpreter is instance of relation interpreter
                    // -> then set type to long
                    if(!empty($attribute->interpreter)) {
                        $interpreter = $attribute->interpreter;
                        $interpreterObject = new $interpreter();
                        if($interpreterObject instanceof \OnlineShop\Framework\IndexService\Interpreter\IRelationInterpreter) {
                            $type = "long";
                            $isRelation = true;
                        }
                    }

                    if($attribute->mapper) {
                        $mapper = new $attribute->mapper();
                        $mapping = $mapper->getMapping();
                    } else {
                        $mapping = ["type" => $type, "store" => $this->getStoreCustomAttributes(), "index" => 'not_analyzed'];
                        if($attribute->analyzer) {
                            $mapping['index'] = 'analyzed';
                            $mapping['analyzer'] = $attribute->analyzer;
                        }
                    }

                    if($type == 'object') { //object doesn't support index or store
                        $mapping = ["type" => $type];
                    }

                    if($attribute->store == 'false' || $attribute->store == '0'){
                        $mapping['store'] = false;
                    }

                    if($isRelation) {
                        $relationAttributesMapping[$attribute->name] = $mapping;
                    } else {
                        $customAttributesMapping[$attribute->name] = $mapping;
                    }
                }

            }
        }

        $mappingAttributes['attributes'] = ['type' => 'object', 'dynamic' => true, 'properties' => $customAttributesMapping];
        $mappingAttributes['relations'] = ['type' => 'object', 'dynamic' => false, 'properties' => $relationAttributesMapping];
        $mappingAttributes['subtenants'] = ['type' => 'object', 'dynamic' => true];

        return $mappingAttributes;
    }


    public function getSystemAttributes($includeTypes = false) {
        $systemAttributes = array(
            "o_id" => "long",
            "o_classId" => "string",
            "o_parentId" => "long",
            "o_virtualProductId" => "long",
            "o_virtualProductActive" => "boolean",
            "o_type" => "string",
            "categoryIds" => "long",
            "categoryPaths" => "string",
            "parentCategoryIds" => "long",
            "priceSystemName" => "string",
            "active" => "boolean",
            "inProductList" => "boolean");

        if($includeTypes) {
            return $systemAttributes;
        } else {
            return array_keys($systemAttributes);
        }

    }

    /**
     * deletes given element from index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return void
     */
    public function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId, $object);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);

    }

    protected function doDeleteFromIndex($objectId, \OnlineShop\Framework\Model\IIndexable $object = null) {
        $esClient = $this->getElasticSearchClient();

        if($object) {
            try {
                $params = ['index' => $this->getIndexNameVersion(), 'type' => $object->getOSIndexType(), 'id' => $objectId];
                if ($object->getOSIndexType() == \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_VARIANT) {
                    $params['parent'] = $object->getOSParentId();
                }
                $esClient->delete($params);

                $this->deleteFromStoreTable($objectId);
                $this->deleteFromMockupCache($objectId);

            } catch(\Exception $e) {
                $check = \Zend_Json::decode($e->getMessage());
                if(!$check['found']){ //not in es index -> we can delete it from store table
                    $this->deleteFromStoreTable($objectId);
                    $this->deleteFromMockupCache($objectId);
                } else {
                    Logger::emergency('Could not delete item form ES index: ID: ' . $objectId.' Message: ' . $e->getMessage());
                }
            }
        } else {
            //object is empty so the object does not exist in pimcore any more. therefore it has to be deleted from the index, store table and mockup table
            try {
                $esClient->delete(['index' => $this->getIndexNameVersion(), 'type' => \OnlineShop\Framework\IndexService\ProductList\IProductList::PRODUCT_TYPE_OBJECT, 'id' => $objectId]);
            } catch(\Exception $e) {
                Logger::warn('Could not delete item form ES index: ID: ' . $objectId.' Message: ' . $e->getMessage());
            }

            // TODO NOTE: we cannot delete variants from ES when we don't know their parents anymore.
            // Delete won't work w/o a parent specified, as there is a parent-child-relationship.
            // So this might produce an invalid index.

            $this->deleteFromStoreTable($objectId);
            $this->deleteFromMockupCache($objectId);
        }
    }

    /**
     * updates given element in index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return void
     */
    public function updateIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $this->prepareDataForIndex($object);

        //updates data for all subentries
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach($subObjectIds as $subObjectId => $object) {
            $this->doUpdateIndex($subObjectId);
        }

        $this->commitUpdateIndex();

        $this->fillupPreparationQueue($object);
    }


    protected $bulkIndexData = array();

    /**
     * only prepare data for updating index
     *
     * @param $objectId
     * @param null $data
     */
    protected function doUpdateIndex($objectId, $data = null) {

        if(empty($data)) {
            $data = $this->db->fetchOne("SELECT data FROM " . $this->getStoreTableName() . " WHERE o_id = ? AND tenant = ?", array($objectId, $this->name));
            $data = json_decode($data, true);
        }

        if($data) {
            $systemAttributeKeys = $this->getSystemAttributes(true);

            $indexSystemData = array();
            $indexAttributeData = array();
            $indexRelationData = array();

            //add system and index attributes
            foreach($data['data'] as $dataKey => $dataEntry) {
                if(array_key_exists($dataKey, $systemAttributeKeys)) {
                    //add this key to system attributes
                    $indexSystemData[$dataKey] = $dataEntry;
                } else {
                    //add this key to custom attributes
                    $indexAttributeData[$dataKey] = $dataEntry;
                }
            }

            //fix categories to array
            $indexSystemData['categoryIds'] = array_values(array_filter(explode(",", $indexSystemData['categoryIds'])));
            $indexSystemData['parentCategoryIds'] = array_values(array_filter(explode(",", $indexSystemData['parentCategoryIds'])));


            //add relation attributes
            foreach($data['relations'] as $relation) {
                $indexRelationData[$relation['fieldname']][] = $relation['dest'];
            }

            $data = $this->doPreIndexDataModification($data);

            //check if parent should exist and if so, consider parent relation at indexing
            if(!empty($indexSystemData['o_virtualProductId']) && $indexSystemData['o_id'] != $indexSystemData['o_virtualProductId']) {
                $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_type' => $indexSystemData['o_type'], '_id' => $objectId, '_parent' => $indexSystemData['o_virtualProductId']]];
            } else {
                $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_type' => $indexSystemData['o_type'], '_id' => $objectId]];
            }
            $this->bulkIndexData[] = array_filter(['system' => array_filter($indexSystemData),'type' => $indexSystemData['o_type'], 'attributes' => array_filter($indexAttributeData), 'relations' => $indexRelationData, 'subtenants' => $data['subtenants']]);

            //save new indexed element to mockup cache
            $this->saveToMockupCache($objectId, $data);
        }

    }

    /**
     * override this method if you need to add custom data
     * which should not be stored in the store data
     *
     * @param $data
     * @return mixed
     */
    protected function doPreIndexDataModification($data){
        return $data;
    }


    /**
     * actually sending data to elastic search
     */
    protected function commitUpdateIndex() {
        if(sizeof($this->bulkIndexData)) {
            $esClient = $this->getElasticSearchClient();
            $responses = $esClient->bulk([
                "body" => $this->bulkIndexData
            ]);

            // save update status
            foreach($responses['items'] as $response) {
                $data = [
                    'update_status' => $response['index']['status'],
                    'update_error' => null,
                ];
                if($response['index']['error']) {
                    $data['update_error'] = \Zend_Json::encode($response['index']['error']);
                    $data['crc_index'] = 0;
                    Logger::error('Failed to Index Object with Id:' . $response['index']['_id']);
                }

                $this->db->updateWhere($this->getStoreTableName(), $data, ['o_id = ?' => $response['index']['_id']]);
            }    
        }

        // reset
        $this->bulkIndexData = [];

        //check for eventual resetting re-index mode
        $this->completeReindexMode();
    }

    /**
     * first run processUpdateIndexQueue of trait and then commit updated entries if there are some
     *
     * @param int $limit
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 100) {
        $entriesUpdated = parent::processUpdateIndexQueue($limit);
        Logger::info('Entries updated:' . $entriesUpdated);
        if($entriesUpdated) {
            $this->commitUpdateIndex();
        }
        return $entriesUpdated;
    }


    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return mixed
     */
    public function getProductList() {
        return new \OnlineShop\Framework\IndexService\ProductList\DefaultElasticSearch($this->tenantConfig);
    }


    protected function getStoreTableName() {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix() {
        return self::MOCKUP_CACHE_PREFIX;
    }


    /**
     * starts reindex mode for index
     * - new index with new version is created
     * - complete store table for current tenant is resetted in order to recreate a new index version
     *
     * while in reindex mode
     * - all index updates are stored into the new index version
     * - no index structure updates are allowed
     *
     */
    public function startReindexMode() {
        //make sure reindex mode can only be started once
        if ($this->isInReindexMode()) {
            throw new \Exception("For given tenant " . $this->name . " system is already in reindex mode - cannot be started once more.");
        }

        // increment version and recreate index structures
        $this->indexVersion++;
        Logger::info("Index-Actions - Start Reindex Mode - Version Number: " . $this->indexVersion.' Index Name: ' . $this->getIndexNameVersion());

        //set the new version here so other processes write in the new index
        $result = file_put_contents($this->getVersionFile(),$this->indexVersion);
        if(!$result){
            throw new \Exception("Can't write version file: " . $this->getVersionFile());
        }
        // reset indexing queue in order to initiate a full re-index to the new index version
        $this->resetIndexingQueue();
    }

    /**
     * checks if system is in reindex mode based on index version and ES alias
     *
     * @return bool
     */
    protected function isInReindexMode() {
        $esClient = $this->getElasticSearchClient();
        try {
            $result = $esClient->indices()->getAlias(['index' => $this->indexName]);
        } catch (\Exception $e) {
            Logger::error($e);
            throw new \Exception("Index alias with name " . $this->indexName . " not found! " . $e);
        }

        reset($result);
        $currentIndexName = key($result);
        $currentIndexVersion = str_replace($this->indexName . "-", "", $currentIndexName);

        if($currentIndexVersion < $this->getIndexVersion()) {
            Logger::info("Index-Actions - currently in reindex mode for " . $this->indexName);
            return true;
        } else if($currentIndexVersion == $this->getIndexVersion()) {
            Logger::info("Index-Actions - currently NOT in reindex mode for " . $this->indexName);
            return false;
        } else {
            throw new \Exception("Index-Actions - something weird happened - CurrentIndexVersion of Alias is bigger than IndexVersion in File: " . $currentIndexVersion . " vs. " . $this->getIndexVersion());
        }
    }

    /**
     * resets the store table to initiate a re-indexing
     */
    public function resetIndexingQueue() {
        Logger::info('Index-Actions - Resetting index queue');
        $query = 'UPDATE '. $this->getStoreTableName() .' SET worker_timestamp = null,
                        worker_id = null,
                        preparation_worker_timestamp = 0,
                        preparation_worker_id = null,
                        crc_index = 0 WHERE tenant = ?';
        $this->db->query($query, array($this->name));
    }


    /**
     * checks if there are some entries in the store table left for indexing
     * if not -> re-index is finished
     *
     * @throws \Exception
     */
    protected function completeReindexMode() {
        if($this->isInReindexMode()) {
            Logger::info('Index-Actions - in completeReindexMode');

            // check if all entries are updated
            $query = "SELECT EXISTS(SELECT 1 FROM " . $this->getStoreTableName() . " WHERE tenant = ? AND (in_preparation_queue = 1 OR crc_current != crc_index) LIMIT 1);";
            $result = $this->db->fetchOne($query, array($this->name));

            if($result == 0) {
                //no entries left --> re-index is finished
                $this->switchIndexAlias();
            } else {
                //there are entries left --> re-index not finished yet
                Logger::info("Index-Actions - Re-Indexing is not finished, still re-indexing for version number: " . $this->indexVersion);
            }
        }
    }

    /**
     * Sets the alias to the current index-version and deletes the old indices
     *
     * @throws \Exception
     */
    public function switchIndexAlias(){
        Logger::info('Index-Actions - Switching Alias');
        $esClient = $this->getElasticSearchClient();

        $params['body'] = array(
            'actions' => array(
                array(
                    'remove' => [
                        'index' => '*',
                        'alias' => $this->indexName,
                    ],
                    'add' => array(
                        'index' => $this->getIndexNameVersion(),
                        'alias' => $this->indexName,
                    )
                )
            )
        );
        $result = $esClient->indices()->updateAliases($params);
        if(!$result['acknowledged']) {
            //set current index version
            throw new \Exception("Switching Alias failed for " . $this->getIndexNameVersion());
        }

        //delete old indices
        $stats = $esClient->indices()->stats();
        foreach($stats['indices'] as $key => $data){
            preg_match("/".$this->indexName.'-(\d+)/',$key,$matches);
            if(!is_null($matches[1])){
                $version = (int)$matches[1];
                if($version != $this->indexVersion){
                    Logger::info("Index-Actions - Delete old Index " . $this->indexName.'-'.$version);
                    $esClient->indices()->delete(['index' => $this->indexName.'-'.$version]);
                }
            }
        }
    }


    /**
     * Checks if given data is array and returns converted data suitable for search backend.
     *
     * return array in this case
     *
     * @param $data
     * @return string
     */
    protected function convertArray($data) {
        return $data;
    }
}
