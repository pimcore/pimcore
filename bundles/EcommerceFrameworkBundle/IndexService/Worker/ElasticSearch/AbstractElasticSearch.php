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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IElasticSearchConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Db\Connection;
use Pimcore\Logger;

/**
 * @property ElasticSearch $tenantConfig
 */
abstract class AbstractElasticSearch extends Worker\AbstractMockupCacheWorker implements Worker\IBatchProcessingWorker
{
    const STORE_TABLE_NAME = 'ecommerceframework_productindex_store_elastic';
    const MOCKUP_CACHE_PREFIX = 'ecommerce_mockup_elastic';

    const RELATION_FIELD = 'parentchildrelation';

    /**
     * Default value for the mapping of custom attributes
     *
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
     *
     * @var string
     */
    protected $indexName;

    /**
     * The Version number of the Index (we increas the Version number if the mapping cant be changed (reindexing process))
     *
     * @var int
     */
    protected $indexVersion = 0;

    /**
     * @var array
     */
    protected $bulkIndexData = [];

    /**
     * @param ElasticSearch|IElasticSearchConfig $tenantConfig
     * @param Connection $db
     */
    public function __construct(IElasticSearchConfig $tenantConfig, Connection $db)
    {
        parent::__construct($tenantConfig, $db);

        $this->indexName = ($tenantConfig->getClientConfig('indexName')) ? strtolower($tenantConfig->getClientConfig('indexName')) : strtolower($this->name);
        $this->determineAndSetCurrentIndexVersion();
    }

    /**
     * should custom attributes be stored separately
     *
     * @return bool
     */
    public function getStoreCustomAttributes()
    {
        return $this->storeCustomAttributes;
    }

    /**
     * Do store custom attributes
     *
     * @param bool $storeCustomAttributes
     */
    public function setStoreCustomAttributes($storeCustomAttributes)
    {
        $this->storeCustomAttributes = $storeCustomAttributes;
    }

    protected function getVersionFile()
    {
        return PIMCORE_PRIVATE_VAR . '/ecommerce/elasticsearch-index-version-' . $this->indexName.'.txt';
    }

    /**
     * determines and sets the current index version
     */
    protected function determineAndSetCurrentIndexVersion()
    {
        $version = $this->getIndexVersion();
        if (is_readable($this->getVersionFile())) {
            $version = (int)trim(file_get_contents($this->getVersionFile()));
        } else {
            \Pimcore\File::mkdir(dirname($this->getVersionFile()));
            file_put_contents($this->getVersionFile(), $this->getIndexVersion());
        }
        $this->indexVersion = $version;
    }

    /**
     * the versioned index-name
     *
     * @return string
     */
    public function getIndexNameVersion()
    {
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
    public function getElasticSearchClient()
    {
        if (empty($this->elasticSearchClient)) {
            $builder = \Elasticsearch\ClientBuilder::create();
            if ($this->tenantConfig->getClientConfig('logging')) {
                $logger = \Pimcore::getContainer()->get('monolog.logger.pimcore_ecommerce_es');
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
    public function createOrUpdateIndexStructures()
    {
        $this->doCreateOrUpdateIndexStructures();
    }

    protected function createMappingAttributes()
    {
        $mappingAttributes = [];
        //add system attributes
        $systemAttributesMapping = [];
        foreach ($this->getSystemAttributes(true) as $name => $type) {
            $systemAttributesMapping[$name] = ['type' => $type, 'store' => true];
        }
        $mappingAttributes['system'] = ['type' => 'object', 'dynamic' => false, 'properties' => $systemAttributesMapping];

        //add custom defined attributes and relation attributes
        $customAttributesMapping = [];
        $relationAttributesMapping = [];

        foreach ($this->tenantConfig->getAttributes() as $attribute) {
            // if option "mapping" is set (array), no other configuration is considered for mapping
            if (!empty($attribute->getOption('mapping'))) {
                $customAttributesMapping[$attribute->getName()] = $attribute->getOption('mapping');
            } else {
                $isRelation = false;
                $type = $attribute->getType();

                //check, if interpreter is set and if this interpreter is instance of relation interpreter
                // -> then set type to long
                if (null !== $attribute->getInterpreter()) {
                    if ($attribute->getInterpreter() instanceof IRelationInterpreter) {
                        $type = 'long';
                        $isRelation = true;
                    }
                }

                if (!empty($attribute->getOption('mapper'))) {
                    $mapperClass = $attribute->getOption('mapper');

                    $mapper = new $mapperClass();
                    $mapping = $mapper->getMapping();
                } else {
                    $mapping = [
                        'type' => $type,
                        'store' => $this->getStoreCustomAttributes()
                    ];

                    if (!empty($attribute->getOption('analyzer'))) {
                        $mapping['index'] = 'analyzed';
                        $mapping['analyzer'] = $attribute->getOption('analyzer');
                    }
                }

                if ($type == 'object') { //object doesn't support index or store
                    $mapping = ['type' => $type];
                }

                if (!$attribute->getOption('store')) {
                    $mapping['store'] = false;
                }

                if ($type == 'object') {
                    unset($mapping['store']);
                }

                if ($isRelation) {
                    $relationAttributesMapping[$attribute->getName()] = $mapping;
                } else {
                    $customAttributesMapping[$attribute->getName()] = $mapping;
                }
            }
        }

        $mappingAttributes['attributes'] = ['type' => 'object', 'dynamic' => true, 'properties' => $customAttributesMapping];
        $mappingAttributes['relations'] = ['type' => 'object', 'dynamic' => false, 'properties' => $relationAttributesMapping];
        $mappingAttributes['subtenants'] = ['type' => 'object', 'dynamic' => true];
        //has to be at top -> join field [system.relation] cannot be added inside an object or in a multi-field
        $mappingAttributes[static::RELATION_FIELD] = ['type' => 'join', 'relations' => ['object' => 'variant']];

        return $mappingAttributes;
    }

    /**
     * creates mapping attributes based on system attributes, in product index defined attributes and relations
     * can be overwritten in order to consider additional mappings for sub tenants
     *
     * @return array
     */
    public function getSystemAttributes($includeTypes = false)
    {
        $systemAttributes = [
            'o_id' => 'long',
            'o_classId' => 'keyword',
            'o_parentId' => 'long',
            'o_virtualProductId' => 'long',
            'o_virtualProductActive' => 'boolean',
            'o_type' => 'keyword',
            'categoryIds' => 'long',
            'categoryPaths' => 'keyword',
            'parentCategoryIds' => 'long',
            'priceSystemName' => 'keyword',
            'active' => 'boolean',
            'inProductList' => 'boolean'];

        if ($includeTypes) {
            return $systemAttributes;
        } else {
            return array_keys($systemAttributes);
        }
    }

    /**
     * deletes given element from index
     *
     * @param IIndexable $object
     *
     * @return void
     */
    public function deleteFromIndex(IIndexable $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach ($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId, $object);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    /**
     * updates given element in index
     *
     * @param IIndexable $object
     *
     * @return void
     */
    public function updateIndex(IIndexable $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $this->prepareDataForIndex($object);

        //updates data for all subentries
        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);
        foreach ($subObjectIds as $subObjectId => $object) {
            $this->doUpdateIndex($subObjectId);
        }

        $this->commitUpdateIndex();

        $this->fillupPreparationQueue($object);
    }

    /**
     * If a variant is moved from one parent to another one the original document needs to be deleted as otherwise the variant will be stored twice in the index
     *
     * @param array $indexSystemData
     * @param int $objectid
     */
    protected function deleteMovedParentRelations($indexSystemData)
    {
        $esClient = $this->getElasticSearchClient();

        $variants = $esClient->search([
            'index' => $this->getIndexNameVersion(),
            'type' => IProductList::PRODUCT_TYPE_VARIANT,
            'body' => [
                '_source' => false,
                'query' => [
                    'bool' => [
                        'must' => [
                            'term' => [
                                'system.o_id' => $indexSystemData['o_id']
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $hits = $variants['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            if ($hit['_parent'] != $indexSystemData['o_virtualProductId']) {
                $params = [
                    'index' => $this->getIndexNameVersion(),
                    'type' => IProductList::PRODUCT_TYPE_VARIANT,
                    'id' => $indexSystemData['o_id'],
                    'parent' => $hit['_parent']
                ];
                $esClient->delete($params);
            }
        }
    }

    protected function doUpdateIndex($objectId, $data = null)
    {
        if (empty($data)) {
            $data = $this->db->fetchOne('SELECT data FROM ' . $this->getStoreTableName() . ' WHERE o_id = ? AND tenant = ?', [$objectId, $this->name]);
            $data = json_decode($data, true);
        }

        if ($data) {
            $systemAttributeKeys = $this->getSystemAttributes(true);

            $indexSystemData = [];
            $indexAttributeData = [];
            $indexRelationData = [];

            //add system and index attributes
            foreach ($data['data'] as $dataKey => $dataEntry) {
                if (array_key_exists($dataKey, $systemAttributeKeys)) {
                    //add this key to system attributes
                    $indexSystemData[$dataKey] = $dataEntry;
                } else {
                    //add this key to custom attributes
                    $indexAttributeData[$dataKey] = $dataEntry;
                }
            }

            //fix categories to array
            $indexSystemData['categoryIds'] = array_values(array_filter(explode(',', $indexSystemData['categoryIds'])));
            $indexSystemData['parentCategoryIds'] = array_values(array_filter(explode(',', $indexSystemData['parentCategoryIds'])));

            //add relation attributes
            foreach ($data['relations'] as $relation) {
                $indexRelationData[$relation['fieldname']][] = $relation['dest'];
            }

            $data = $this->doPreIndexDataModification($data);

            //check if parent should exist and if so, consider parent relation at indexing
            $routingId = $indexSystemData['o_type'] == IProductList::PRODUCT_TYPE_VARIANT ? $indexSystemData['o_virtualProductId'] : $indexSystemData['o_id'];

            $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_type' => $this->getTenantConfig()->getElasticSearchClientParams()['indexType'], '_id' => $objectId, '_routing' => $routingId]];
            $bulkIndexData = array_filter(['system' => array_filter($indexSystemData), 'type' => $indexSystemData['o_type'], 'attributes' => array_filter($indexAttributeData), 'relations' => $indexRelationData, 'subtenants' => $data['subtenants']]);

            if ($indexSystemData['o_type'] == IProductList::PRODUCT_TYPE_VARIANT) {
                $bulkIndexData[self::RELATION_FIELD] = ['name' => $indexSystemData['o_type'], 'parent' => $indexSystemData['o_virtualProductId']];
            } else {
                $bulkIndexData[self::RELATION_FIELD] = ['name' => $indexSystemData['o_type']];
            }
            $this->bulkIndexData[] = $bulkIndexData;

            //save new indexed element to mockup cache
            $this->saveToMockupCache($objectId, $data);
        }
    }

    /**
     * override this method if you need to add custom data
     * which should not be stored in the store data
     *
     * @param $data
     *
     * @return mixed
     */
    protected function doPreIndexDataModification($data)
    {
        return $data;
    }

    /**
     * actually sending data to elastic search
     */
    protected function commitUpdateIndex()
    {
        if (sizeof($this->bulkIndexData)) {
            $esClient = $this->getElasticSearchClient();
            $responses = $esClient->bulk([
                'body' => $this->bulkIndexData
            ]);

            // save update status
            foreach ($responses['items'] as $response) {
                $data = [
                    'update_status' => $response['index']['status'],
                    'update_error' => null,
                ];
                if (isset($response['index']['error']) && $response['index']['error']) {
                    $data['update_error'] = json_encode($response['index']['error']);
                    $data['crc_index'] = 0;
                    Logger::error('Failed to Index Object with Id:' . $response['index']['_id']);
                }

                $this->db->updateWhere($this->getStoreTableName(), $data, 'o_id = ' . $this->db->quote($response['index']['_id']));
            }
        }

        // reset
        $this->bulkIndexData = [];

        //check for eventual resetting re-index mode
        $this->completeReindexMode();
    }

    /**
     * first run processUpdateIndexQueue of trait and then commit updated entries
     *
     * @param int $limit
     *
     * @return int number of entries processed
     */
    public function processUpdateIndexQueue($limit = 100)
    {
        $entriesUpdated = parent::processUpdateIndexQueue($limit);
        Logger::info('Entries updated:' . $entriesUpdated);
        $this->commitUpdateIndex();

        return $entriesUpdated;
    }

    protected function getStoreTableName()
    {
        return self::STORE_TABLE_NAME;
    }

    protected function getMockupCachePrefix()
    {
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
    public function startReindexMode()
    {
        //make sure reindex mode can only be started once
        if ($this->isInReindexMode()) {
            throw new \Exception('For given tenant ' . $this->name . ' system is already in reindex mode - cannot be started once more.');
        }

        // increment version and recreate index structures
        $this->indexVersion++;
        Logger::info('Index-Actions - Start Reindex Mode - Version Number: ' . $this->indexVersion.' Index Name: ' . $this->getIndexNameVersion());

        //set the new version here so other processes write in the new index
        $result = file_put_contents($this->getVersionFile(), $this->indexVersion);
        if (!$result) {
            throw new \Exception("Can't write version file: " . $this->getVersionFile());
        }
        // reset indexing queue in order to initiate a full re-index to the new index version
        $this->resetIndexingQueue();
    }

    /**
     * checks if system is in reindex mode based on index version and ES alias
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function isInReindexMode()
    {
        $esClient = $this->getElasticSearchClient();
        try {
            $result = $esClient->indices()->getAlias(['index' => $this->indexName]);
        } catch (\Exception $e) {
            Logger::error($e);
            throw new \Exception('Index alias with name ' . $this->indexName . ' not found! ' . $e);
        }

        reset($result);
        $currentIndexName = key($result);
        $currentIndexVersion = str_replace($this->indexName . '-', '', $currentIndexName);

        if ($currentIndexVersion < $this->getIndexVersion()) {
            Logger::info('Index-Actions - currently in reindex mode for ' . $this->indexName);

            return true;
        } elseif ($currentIndexVersion == $this->getIndexVersion()) {
            Logger::info('Index-Actions - currently NOT in reindex mode for ' . $this->indexName);

            return false;
        } else {
            throw new \Exception('Index-Actions - something weird happened - CurrentIndexVersion of Alias is bigger than IndexVersion in File: ' . $currentIndexVersion . ' vs. ' . $this->getIndexVersion());
        }
    }

    /**
     * checks if there are some entries in the store table left for indexing
     * if not -> re-index is finished
     *
     * @throws \Exception
     */
    protected function completeReindexMode()
    {
        if ($this->isInReindexMode()) {
            Logger::info('Index-Actions - in completeReindexMode');

            // check if all entries are updated
            $query = 'SELECT EXISTS(SELECT 1 FROM ' . $this->getStoreTableName() . ' WHERE tenant = ? AND (in_preparation_queue = 1 OR crc_current != crc_index) LIMIT 1);';
            $result = $this->db->fetchOne($query, [$this->name]);

            if ($result == 0) {
                //no entries left --> re-index is finished
                $this->switchIndexAlias();
            } else {
                //there are entries left --> re-index not finished yet
                Logger::info('Index-Actions - Re-Indexing is not finished, still re-indexing for version number: ' . $this->indexVersion);
            }
        }
    }

    /**
     * Sets the alias to the current index-version and deletes the old indices
     *
     * @throws \Exception
     */
    public function switchIndexAlias()
    {
        Logger::info('Index-Actions - Switching Alias');
        $esClient = $this->getElasticSearchClient();

        $params['body'] = [
            'actions' => [
                [
                    'remove' => [
                        'index' => '*',
                        'alias' => $this->indexName,
                    ],
                ],
                [
                    'add' => [
                        'index' => $this->getIndexNameVersion(),
                        'alias' => $this->indexName,
                    ]
                ]
            ]
        ];
        $result = $esClient->indices()->updateAliases($params);
        if (!$result['acknowledged']) {
            //set current index version
            throw new \Exception('Switching Alias failed for ' . $this->getIndexNameVersion());
        }

        //delete old indices
        $stats = $esClient->indices()->stats();
        foreach ($stats['indices'] as $key => $data) {
            preg_match('/'.$this->indexName.'-(\d+)/', $key, $matches);
            if (!is_null($matches[1])) {
                $version = (int)$matches[1];
                if ($version != $this->indexVersion) {
                    Logger::info('Index-Actions - Delete old Index ' . $this->indexName.'-'.$version);
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
     *
     * @return string
     */
    protected function convertArray($data)
    {
        return $data;
    }

    protected function doDeleteFromIndex($objectId, IIndexable $object = null)
    {
        $esClient = $this->getElasticSearchClient();

        $storeEntry = \Pimcore\Db::get()->fetchRow('SELECT * FROM ' . $this->getStoreTableName() . ' WHERE  o_id=?', [$objectId]);
        if ($storeEntry) {
            $res = $esClient->delete(['index' => $this->getIndexNameVersion(), 'type' => '_doc', 'id' => $objectId, 'routing' => $storeEntry['o_virtualProductId']]);
            $this->deleteFromStoreTable($objectId);
            $this->deleteFromMockupCache($objectId);
        }
    }

    protected function doCreateOrUpdateIndexStructures($exceptionOnFailure = false)
    {
        $this->createOrUpdateStoreTable();

        $esClient = $this->getElasticSearchClient();

        $result = $esClient->indices()->exists(['index' => $this->getIndexNameVersion()]);

        if (!$result) {
            $result = $esClient->indices()->create(['index' => $this->getIndexNameVersion(), 'body' => ['settings' => $this->tenantConfig->getIndexSettings()]]);

            Logger::info('Index-Actions - creating new Index. Name: ' . $this->getIndexNameVersion());
            if (!$result['acknowledged']) {
                throw new \Exception('Index creation failed. IndexName: ' . $this->getIndexNameVersion());
            }

            //index didn't exist -> reset index queue to make sure all products get reindexed
            $this->resetIndexingQueue();

            //create alias for new index if alias doesn't exist so far
            $aliasExists = $esClient->indices()->existsAlias(['name' => $this->indexName]);
            if (!$aliasExists) {
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

        $params = $this->getMappingParams();

        try {
            $result = $esClient->indices()->putMapping($params);
            Logger::info('Index-Actions - updated Mapping for Index: ' . $this->getIndexNameVersion());
        } catch (\Exception $e) {
            Logger::info($e->getMessage());

            if ($exceptionOnFailure) {
                throw new \Exception("Can't create Mapping - Exiting to prevent infinite loop. Message: " . $e->getMessage());
            } else {
                //when update mapping fails, start reindex mode
                $this->startReindexMode();
                $this->doCreateOrUpdateIndexStructures(true);
            }
        }

        // index created return "true" and mapping creation returns array
        if ((is_array($result) && !$result['acknowledged']) || (is_bool($result) && !$result)) {
            throw new \Exception('Index creation failed');
        }
    }

    // type will be removed in ES 7
    protected function getMappingParams($type = null)
    {
        $params = [
            'index' => $this->getIndexNameVersion(),
            'type' => $this->getTenantConfig()->getElasticSearchClientParams()['indexType'],
            'body' => [
                'properties' => $this->createMappingAttributes()
            ]
        ];

        return $params;
    }
}
