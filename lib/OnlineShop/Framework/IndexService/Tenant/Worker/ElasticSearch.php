<?php

class OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch extends OnlineShop_Framework_IndexService_Tenant_Worker_Abstract implements OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker {
    use OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing {
        OnlineShop_Framework_IndexService_Tenant_Worker_Traits_BatchProcessing::processUpdateIndexQueue as traitProcessUpdateIndexQueue;
    }
    use OnlineShop_Framework_IndexService_Tenant_Worker_Traits_MockupCache;

    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store_elastic";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup_elastic";

    /**
     * @var Elasticsearch\Client
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
     * @var OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch
     */
    protected $tenantConfig;

    public function __construct(OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch $tenantConfig) {
        parent::__construct($tenantConfig);
        $generalSettings = $tenantConfig->getGeneralSettings();
        if($generalSettings['indexName']){
            $this->indexName = $generalSettings['indexName'];
        }else{
            $this->indexName = strtolower($this->name);
        }
        $this->determineAndSetCurrentIndexVersion();
    }

    protected function getVersionFile(){
        return PIMCORE_WEBSITE_VAR.'/plugins/OnlineShop/elasticsearch-index-version-' . $this->indexName.'.txt';
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
        return $this->indexName.'-'. $this->getIndexVersion();
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
            require_once PIMCORE_DOCUMENT_ROOT . '/plugins/OnlineShop/vendor/autoload.php';
            $this->elasticSearchClient = new Elasticsearch\Client($this->tenantConfig->getElasticSearchClientParams());
        }
        return $this->elasticSearchClient;
    }



    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures($breakOnFailure = false) {
        $this->createOrUpdateStoreTable();

        $esClient = $this->getElasticSearchClient();

        $result = $esClient->indices()->exists(['index' => $this->getIndexNameVersion()]);
        if(!$result) {
            $result = $esClient->indices()->create(['index' => $this->getIndexNameVersion(), 'body' => ['settings' => $this->tenantConfig->getIndexSettings()]]);
            if(!$result['acknowledged']) {
                throw new Exception("Index creation failed. IndexName: " . $this->getIndexNameVersion());
            }
        }

        $params = [
            'index' => $this->getIndexNameVersion(),
            'type' => 'product',
            'body' => [
                'product' => [
                    '_parent' => ['type' => 'product'],
                    '_routing' => ['path' => 'system.o_virtualProductId'],
                    'dynamic' => false,
                    //'_source' => ['enabled' => false ],
                    'properties' => $this->createMappingAttributes()
                ]
            ]
        ];
        try{
            $result = $esClient->indices()->putMapping($params);

        }catch(\Exception $e){
            if($breakOnFailure){
                throw new Exception("Can't create Mapping - Exiting to prevent infinit loop");
            }
            $this->indexVersion++;
            Logger::info("Could not put index Mapping -> creating new Index. Version Number: " . $this->indexVersion);
            $this->createOrUpdateIndexStructures(true);
            $this->resetPreparationQueue();
        }

        // index created return "true" and mapping creation returns array
        if((is_array($result) && !$result['acknowledged']) || (is_bool($result) && !$result)) {
            throw new Exception("Index creation failed");
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

        $attributesConfig = $this->columnConfig->column;
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
                        if($interpreterObject instanceof OnlineShop_Framework_IndexService_RelationInterpreter) {
                            $type = "long";
                            $isRelation = true;
                        }
                    }

                    if($isRelation) {
                        $relationAttributesMapping[$attribute->name] = ["type" => $type, "store" => true, "index" => "not_analyzed"];
                    } else {
                        $customAttributesMapping[$attribute->name] = ["type" => $type, "store" => true, "index" => "not_analyzed"];
                    }
                }

            }
        }

        $mappingAttributes['attributes'] = ['type' => 'object', 'dynamic' => false, 'properties' => $customAttributesMapping];
        $mappingAttributes['relations'] = ['type' => 'object', 'dynamic' => false, 'properties' => $relationAttributesMapping];
        $mappingAttributes['subtenants'] = ['type' => 'object', 'dynamic' => true];


        return $mappingAttributes;
    }

    public function getSystemAttributes($includeTypes) {
        $systemAttributes = array(
            "o_id" => "long",
            "o_classId" => "string",
            "o_parentId" => "long",
            "o_virtualProductId" => "long",
            "o_virtualProductActive" => "boolean",
            "o_type" => "string",
            "categoryIds" => "long",
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
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return void
     */
    public function deleteFromIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId);
        }

    }

    protected function doDeleteFromIndex($objectId) {

        $esClient = $this->getElasticSearchClient();
        try {
            $esClient->delete(['index' => $this->indexName, 'type' => "product", 'id' => $objectId]);
        } catch(Exception $e) {
            //TODO decide what to do with exceptions
        }

    }

    /**
     * updates given element in index
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @return void
     */
    public function updateIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        if(!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");
            return;
        }

        $this->prepareDataForIndex($object);
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
            $data = $this->db->fetchOne("SELECT data FROM " . $this->getStoreTableName() . " WHERE id = ? AND tenant = ?", array($objectId, $this->name));
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

            //check if parent should exist and if so, consider parent relation at indexing
            if(!empty($indexSystemData['o_virtualProductId']) && $indexSystemData['o_id'] != $indexSystemData['o_virtualProductId']) {
                $this->bulkIndexData[] = ['index' => ['index' => $this->indexName, 'type' => 'product', '_id' => $objectId, '_parent' => $indexSystemData['o_virtualProductId']]];
            } else {
                $this->bulkIndexData[] = ['index' => ['index' => $this->indexName, 'type' => 'product', '_id' => $objectId]];
            }
            $this->bulkIndexData[] = array_filter(['system' => array_filter($indexSystemData), 'attributes' => array_filter($indexAttributeData), 'relations' => $indexRelationData, 'subtenants' => $data['subtenants']]);

            //save new indexed element to mockup cache
            $this->saveToMockupCache($objectId, $data);
        }


    }


    /**
     * actually sending data to elastic search
     *
     * TODO error handling
     */
    protected function commitUpdateIndex() {

        $esClient = $this->getElasticSearchClient();
        $responses = $esClient->bulk([
            "index" => $this->getIndexNameVersion(),
            "type" => "product",
            "body" => $this->bulkIndexData
        ]);


        if($responses['errors']) {
            foreach($responses as $e){
                p_r($e);
            }
            throw new Exception("Indexing threw an Exception");
        }


        // reset
        $this->bulkIndexData = [];
    }

    /**
     * first run processUpdateIndexQueue of trait and then commit updated entries if there are some
     *
     * @param int $limit
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 200) {
        $entriesUpdated = $this->traitProcessUpdateIndexQueue($limit);
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
        return new OnlineShop_Framework_ProductList_DefaultElasticSearch($this->tenantConfig);
    }


    protected function getStoreTableName() {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix() {
        return self::MOCKUP_CACHE_PREFIX;
    }

    /**
     * Sets the alias to the current index-version and deletes the old indices
     *
     * @throws Exception
     */
    public function doPostIndexingWork(){
        $esClient = $this->getElasticSearchClient();
        //set alias to current index version
        $result = $esClient->indices()->putAlias(['index' => $this->getIndexNameVersion(),'name' => $this->indexName]);
        if(!$result['acknowledged']) {
            //set current index version
            throw new Exception("Can't create the Alias '". $this->indexName."' for the Index: " . $this->getIndexNameVersion());
        }else{
            $result = file_put_contents($this->getVersionFile(),$this->indexVersion);
            if(!$result){
                throw new Exception("Can't write version file: " . $this->getVersionFile());
            }
        }

        //delete old indices
        $stats = $esClient->indices()->stats();
        foreach($stats['indices'] as $key => $data){
            preg_match("/".$this->indexName.'-(\d+)/',$key,$matches);
            if(!is_null($matches[1])){
                $version = (int)$matches[1];
                if($version != $this->indexVersion){
                    Logger::info("Delete old Index " . $this->indexName.'-'.$version);
                    $esClient->indices()->delete(['index' => $this->indexName.'-'.$version]);
                }
            }
        }

    }

}

