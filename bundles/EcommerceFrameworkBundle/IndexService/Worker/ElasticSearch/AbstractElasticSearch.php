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

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Logger;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @property ElasticSearch $tenantConfig
 */
abstract class AbstractElasticSearch extends Worker\ProductCentricBatchProcessingWorker implements Worker\BatchProcessingWorkerInterface
{
    const STORE_TABLE_NAME = 'ecommerceframework_productindex_store_elastic';

    const RELATION_FIELD = 'parentchildrelation';

    const REINDEXING_LOCK_KEY = 'elasticsearch_reindexing_lock';

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
     * The Version number of the Index (we increase the Version number if the mapping cant be changed (reindexing process))
     *
     * @var int
     */
    protected $indexVersion = null;

    /**
     * @var array
     */
    protected $bulkIndexData = [];

    /**
     * @var array
     */
    protected $indexStoreMetaData = [];

    /**
     * @var int
     */
    protected $lastLockLogTimestamp = 0;

    /**
     * @param ElasticSearchConfigInterface $tenantConfig
     * @param ConnectionInterface $db
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null $workerMode
     */
    public function __construct(ElasticSearchConfigInterface $tenantConfig, ConnectionInterface $db, EventDispatcherInterface $eventDispatcher, string $workerMode = null)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher, $workerMode);

        $this->indexName = ($tenantConfig->getClientConfig('indexName')) ? strtolower($tenantConfig->getClientConfig('indexName')) : strtolower($this->name);
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

    /**
     * the versioned index-name
     *
     * @param int $indexVersionOverride if set, then the index name for a specific index version is built. example. 13
     *
     * @return string the name of the index, such as at_de_elastic_13
     */
    public function getIndexNameVersion(int $indexVersionOverride = null)
    {
        $indexVersion = $indexVersionOverride ?? $this->getIndexVersion();

        return $this->indexName . '-' . $indexVersion;
    }

    /**
     * @return int
     */
    public function getIndexVersion()
    {
        if ($this->indexVersion === null) {
            $this->indexVersion = 0;
            $esClient = $this->getElasticSearchClient();

            try {
                $result = $esClient->indices()->getAlias([
                    'name' => $this->indexName,
                ]);

                if (is_array($result)) {
                    $aliasIndexName = array_key_first($result);
                    preg_match('/'.$this->indexName.'-(\d+)/', $aliasIndexName, $matches);
                    if (is_array($matches) && count($matches) > 1) {
                        $version = (int)$matches[1];
                        if ($version > $this->indexVersion) {
                            $this->indexVersion = $version;
                        }
                    }
                }
            } catch (Missing404Exception $e) {
                $this->indexVersion = 0;
            }
        }

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
     * @throws \Exception
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
            if (empty($attribute->getType())
                && (empty($attribute->getInterpreter()) || ($attribute->getInterpreter() && !($attribute->getInterpreter() instanceof RelationInterpreterInterface)))
                && empty($attribute->getOption('mapping'))
                && empty($attribute->getOption('mapper'))
                && empty($attribute->getOption('analyzer'))
            ) {
                continue;
            }

            // if option "mapping" is set (array), no other configuration is considered for mapping
            if (!empty($attribute->getOption('mapping'))) {
                $customAttributesMapping[$attribute->getName()] = $attribute->getOption('mapping');
            } else {
                $isRelation = false;
                $type = $attribute->getType();

                //check, if interpreter is set and if this interpreter is instance of relation interpreter
                // -> then set type to long
                if (null !== $attribute->getInterpreter()) {
                    if ($attribute->getInterpreter() instanceof RelationInterpreterInterface) {
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
                        'store' => $this->getStoreCustomAttributes(),
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

                if ($type == 'object' || $type == 'nested') {
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
     * @param bool $includeTypes
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
            'inProductList' => 'boolean', ];

        if ($includeTypes) {
            return $systemAttributes;
        } else {
            return array_keys($systemAttributes);
        }
    }

    /**
     * deletes given element from index
     *
     * @param IndexableInterface $object
     *
     * @throws \Exception
     */
    public function deleteFromIndex(IndexableInterface $object)
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
     * @param IndexableInterface $object
     *
     * @throws \Throwable
     */
    public function updateIndex(IndexableInterface $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->prepareDataForIndex($object);

        //updates data for all subentries
        foreach ($subObjectIds as $subObjectId => $object) {
            $this->doUpdateIndex($subObjectId);
        }

        if (count($subObjectIds) > 0) {
            $this->commitBatchToIndex();
        }

        $this->fillupPreparationQueue($object);
    }

    protected function doUpdateIndex($objectId, $data = null, $metadata = null)
    {
        $isLocked = $this->checkIndexLock(false);

        if ($isLocked) {
            return;
        }

        if (empty($data)) {
            $dataEntry = $this->db->fetchRow('SELECT data, metadata FROM ' . $this->getStoreTableName() . ' WHERE o_id = ? AND tenant = ?', [$objectId, $this->name]);
            if ($dataEntry) {
                $data = json_decode($dataEntry['data'], true);
                $metadata = $dataEntry['metadata'];

                $jsonDecodeError = json_last_error();
                if ($jsonDecodeError !== JSON_ERROR_NONE) {
                    throw new \Exception("Could not decode store data for updating index - maybe there is invalid json data. Json decode error code was {$jsonDecodeError}, ObjectId was {$objectId}.");
                }
            }
        }

        if ($data) {
            $systemAttributeKeys = $this->getSystemAttributes(true);

            $indexSystemData = [];
            $indexAttributeData = [];
            $indexRelationData = [];

            $data = $this->doPreIndexDataModification($data);

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

            //check if parent should exist and if so, consider parent relation at indexing
            $routingId = $indexSystemData['o_type'] == ProductListInterface::PRODUCT_TYPE_VARIANT ? $indexSystemData['o_virtualProductId'] : $indexSystemData['o_id'];

            if ($metadata !== null && $routingId != $metadata) {
                //routing has changed, need to delete old ES entry
                $this->bulkIndexData[] = ['delete' => ['_index' => $this->getIndexNameVersion(), '_type' => $this->getTenantConfig()->getElasticSearchClientParams()['indexType'], '_id' => $objectId, '_routing' => $metadata]];
            }

            $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_type' => $this->getTenantConfig()->getElasticSearchClientParams()['indexType'], '_id' => $objectId, '_routing' => $routingId]];
            $bulkIndexData = array_filter(['system' => array_filter($indexSystemData), 'type' => $indexSystemData['o_type'], 'attributes' => array_filter($indexAttributeData, function ($value) {
                return $value !== null;
            }), 'relations' => $indexRelationData, 'subtenants' => $data['subtenants']]);

            if ($indexSystemData['o_type'] == ProductListInterface::PRODUCT_TYPE_VARIANT) {
                $bulkIndexData[self::RELATION_FIELD] = ['name' => $indexSystemData['o_type'], 'parent' => $indexSystemData['o_virtualProductId']];
            } else {
                $bulkIndexData[self::RELATION_FIELD] = ['name' => $indexSystemData['o_type']];
            }
            $this->bulkIndexData[] = $bulkIndexData;
            $this->indexStoreMetaData[$objectId] = $routingId;
        }
    }

    /**
     * override this method if you need to add custom data
     * which should not be stored in the store data
     *
     * @param array|string $data
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
    public function commitBatchToIndex(): void
    {
        if (count($this->bulkIndexData)) {
            $esClient = $this->getElasticSearchClient();
            $responses = $esClient->bulk([
                'body' => $this->bulkIndexData,
            ]);

            // save update status
            foreach ($responses['items'] as $response) {
                $operation = null;
                if (isset($response['index'])) {
                    $operation = 'index';
                } elseif (isset($response['delete'])) {
                    $operation = 'delete';
                }

                if ($operation) {
                    $data = [
                        'update_status' => $response[$operation]['status'],
                        'update_error' => null,
                        'metadata' => isset($this->indexStoreMetaData[$response[$operation]['_id']]) ? $this->indexStoreMetaData[$response[$operation]['_id']] : null,
                    ];
                    if (isset($response[$operation]['error']) && $response[$operation]['error']) {
                        $data['update_error'] = json_encode($response[$operation]['error']);
                        $data['crc_index'] = 0;
                        Logger::error(
                            'Failed to Index Object with Id:' . $response[$operation]['_id'],
                            json_decode($data['update_error'], true)
                        );

                        $this->db->updateWhere(
                            $this->getStoreTableName(),
                            $data,
                            'o_id = ' . $this->db->quote($response[$operation]['_id']) . ' AND tenant = ' . $this->db->quote($this->name)
                        );
                    } else {

                        //update crc sums in store table to mark element as indexed
                        $this->db->query(
                            'UPDATE ' . $this->getStoreTableName() . ' SET crc_index = crc_current, update_status = ?, update_error = ?, metadata = ? WHERE o_id = ? and tenant = ?',
                            [$data['update_status'], $data['update_error'], $data['metadata'], $response[$operation]['_id'], $this->name]
                        );
                    }
                } else {
                    throw new \Exception('Unkown operation in response: ' . print_r($response, true));
                }
            }
        }

        // reset
        $this->bulkIndexData = [];
        $this->indexStoreMetaData = [];
    }

    /**
     * @deprecated
     *
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
        $this->commitBatchToIndex();

        return $entriesUpdated;
    }

    protected function getStoreTableName()
    {
        return self::STORE_TABLE_NAME;
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
                    ],
                ],
            ],
        ];
        $result = $esClient->indices()->updateAliases($params);
        if (!$result['acknowledged']) {
            //set current index version
            throw new \Exception('Switching Alias failed for ' . $this->getIndexNameVersion());
        }

        //delete old indices
        $this->cleanupUnusedEsIndices();
    }

    protected function cleanupUnusedEsIndices(): void
    {
        $esClient = $this->getElasticSearchClient();
        $stats = $esClient->indices()->stats();
        foreach ($stats['indices'] as $key => $data) {
            preg_match('/'.$this->indexName.'-(\d+)/', $key, $matches);
            if (is_array($matches) && count($matches) > 1) {
                $version = (int)$matches[1];
                if ($version != $this->indexVersion) {
                    $indexNameVersion = $this->getIndexNameVersion($version);
                    Logger::info('Index-Actions - Delete old Index ' . $indexNameVersion);
                    $this->deleteEsIndexIfExisting($indexNameVersion);
                }
            }
        }
    }

    /**
     * Checks if given data is array and returns converted data suitable for search backend.
     *
     * return array in this case
     *
     * @param array|string $data
     *
     * @return string
     */
    protected function convertArray($data)
    {
        return $data;
    }

    /**
     * @param int $objectId
     * @param IndexableInterface|null $object
     *
     * @throws \Exception
     */
    protected function doDeleteFromIndex($objectId, IndexableInterface $object = null)
    {
        $esClient = $this->getElasticSearchClient();

        $storeEntry = \Pimcore\Db::get()->fetchRow('SELECT * FROM ' . $this->getStoreTableName() . ' WHERE  o_id=? AND tenant=? ', [$objectId, $this->getTenantConfig()->getTenantName()]);
        if ($storeEntry) {
            $isLocked = $this->checkIndexLock(false);
            if ($isLocked) {
                throw new \Exception('Delete not possible due to product index lock. Please re-try later.');
            }

            try {
                $esClient->delete([
                    'index' => $this->getIndexNameVersion(),
                    'type' => $this->getTenantConfig()->getElasticSearchClientParams()['indexType'],
                    'id' => $objectId,
                    'routing' => $storeEntry['o_virtualProductId'],
                ]);
            } catch (\Exception $e) {
                //if \Elasticsearch\Common\Exceptions\Missing404Exception <- the object is not in the index so its ok.
                if ($e instanceof Missing404Exception == false) {
                    throw $e;
                }
            }
            $this->deleteFromStoreTable($objectId);
        }
    }

    protected function doCreateOrUpdateIndexStructures()
    {
        $this->checkIndexLock(true);

        $this->createOrUpdateStoreTable();

        $esClient = $this->getElasticSearchClient();

        $result = $esClient->indices()->exists(['index' => $this->getIndexNameVersion()]);

        if (!$result) {
            $indexName = $this->getIndexNameVersion();
            $this->createEsIndex($indexName);

            //index didn't exist -> reset index queue to make sure all products get re-indexed
            $this->resetIndexingQueue();

            $this->createEsAliasIfMissing();
        }

        try {
            $this->putIndexMapping($this->getIndexNameVersion());

            $configuredSettings = $this->tenantConfig->getIndexSettings();
            $synonymSettings = $this->extractMinimalSynonymFiltersTreeFromTenantConfig();
            if (isset($synonymSettings['analysis'])) {
                $configuredSettings['analysis']['filter'] = array_replace_recursive($configuredSettings['analysis']['filter'], $synonymSettings['analysis']['filter']);
            }

            $currentSettings = $esClient->indices()->getSettings([
                'index' => $this->getIndexNameVersion(),
            ]);
            $currentSettings = $currentSettings[$this->getIndexNameVersion()]['settings']['index'];

            $settingsIntersection = array_intersect_key($currentSettings, $configuredSettings);
            if ($settingsIntersection != $configuredSettings) {
                $esClient->indices()->putSettings([
                    'index' => $this->getIndexNameVersion(),
                    'body' => [
                        'index' => $this->tenantConfig->getIndexSettings(),
                    ],
                ]);
                Logger::info('Index-Actions - updated settings for Index: ' . $this->getIndexNameVersion());
            } else {
                Logger::info('Index-Actions - no settings update necessary for Index: ' . $this->getIndexNameVersion());
            }
        } catch (\Exception $e) {
            Logger::info("Index-Actions - can't create Mapping - trying reindexing " . $e->getMessage());
            Logger::info('Index-Actions - Perform native reindexing for Index: ' . $this->getIndexNameVersion());

            $this->startReindexMode();
        }
    }

    // type will be removed in ES 7
    protected function getMappingParams($type = null)
    {
        $params = [
            'index' => $this->getIndexNameVersion(),
            'type' => $this->getTenantConfig()->getElasticSearchClientParams()['indexType'],
            'body' => [
                'properties' => $this->createMappingAttributes(),
            ],
        ];

        return $params;
    }

    /**
     * Retrieve the currently active index name from ES based on the alias.
     *
     * @return string|null null if no index is found.
     */
    public function fetchEsActiveIndex(): ?string
    {
        $esClient = $this->getElasticSearchClient();
        try {
            $result = $esClient->indices()->getAlias(['index' => $this->indexName]);
        } catch (\Exception $e) {
            Logger::error($e);

            return null;
        }

        reset($result);
        $currentIndexName = key($result);

        return $currentIndexName;
    }

    /**
     * Create the index alias on demand.
     *
     * @throws \Exception if alias could not be created.
     */
    protected function createEsAliasIfMissing()
    {
        $esClient = $this->getElasticSearchClient();
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
                        ],
                    ],
                ],
            ];
            $result = $esClient->indices()->updateAliases($params);
            if (!$result) {
                throw new \Exception('Alias '.$this->indexName.' could not be created.');
            }
        }
    }

    /**
     * Create an ES index with the specified version.
     *
     * @param string $indexName the name of the index.
     *
     * @throws \Exception is thrown if index cannot be created, for instance if connection fails or index is already existing.
     */
    protected function createEsIndex(string $indexName)
    {
        $esClient = $this->getElasticSearchClient();

        Logger::info('Index-Actions - creating new Index. Name: ' . $indexName);

        $configuredSettings = $this->tenantConfig->getIndexSettings();
        $synonymSettings = $this->extractMinimalSynonymFiltersTreeFromTenantConfig();
        if (isset($synonymSettings['analysis'])) {
            $configuredSettings['analysis']['filter'] = array_replace_recursive($configuredSettings['analysis']['filter'], $synonymSettings['analysis']['filter']);
        }

        $result = $esClient->indices()->create([
            'index' => $indexName,
            'body' => ['settings' => $configuredSettings],
        ]);

        if (!$result['acknowledged']) {
            throw new \Exception('Index creation failed. IndexName: ' . $indexName);
        }
    }

    /**
     * puts current mapping to index with given name
     *
     * @param string $indexName
     *
     * @throws \Exception
     */
    protected function putIndexMapping(string $indexName)
    {
        $esClient = $this->getElasticSearchClient();

        $params = $this->getMappingParams();
        $params['index'] = $indexName;
        $result = $esClient->indices()->putMapping($params);

        if (!$result['acknowledged']) {
            throw new \Exception('Putting mapping to index failed. IndexName: ' . $indexName);
        }

        Logger::info('Index-Actions - updated Mapping for Index: ' . $indexName);
    }

    /**
     * Delete an ES index if existing.
     *
     * @param string $indexName the name of the index.
     */
    protected function deleteEsIndexIfExisting(string $indexName)
    {
        $esClient = $this->getElasticSearchClient();
        $result = $esClient->indices()->exists(['index' => $indexName]);
        if ($result) {
            Logger::info('Deleted index '.$indexName.'.');
            $result = $esClient->indices()->delete(['index' => $indexName]);
            if (!array_key_exists('acknowledged', $result) && !$result['acknowledged']) {
                Logger::error("Could not delete index {$indexName} while cleanup. Please remove the index manually.");
            }
        }
    }

    /**
     * Blocks all write operations
     *
     * @param string $indexName the name of the index.
     */
    protected function blockIndexWrite(string $indexName)
    {
        $esClient = $this->getElasticSearchClient();
        $result = $esClient->indices()->exists(['index' => $indexName]);
        if ($result) {
            Logger::info('Block write index '.$indexName.'.');
            $esClient->indices()->putSettings([
                'index' => $indexName,
                'body' => [
                    'index.blocks.write' => true,
                ],
            ]);

            $esClient->indices()->refresh([
                'index' => $indexName,
            ]);
        }
    }

    /**
     * Unblocks all write operations
     *
     * @param string $indexName the name of the index.
     */
    protected function unblockIndexWrite(string $indexName)
    {
        $esClient = $this->getElasticSearchClient();
        $result = $esClient->indices()->exists(['index' => $indexName]);
        if ($result) {
            Logger::info('Unlock write index '.$indexName.'.');
            $esClient->indices()->putSettings([
                'index' => $indexName,
                'body' => [
                    'index.blocks.write' => false,
                ],
            ]);

            $esClient->indices()->refresh([
                'index' => $indexName,
            ]);
        }
    }

    /**
     * Get the next index version, e.g. if currently 13, then 14 will be returned.
     *
     * @return int
     */
    protected function getNextIndexVersion(): int
    {
        return $this->getIndexVersion() + 1;
    }

    /**
     * Copy an existing ES sourceIndex into an existing ES targetIndex.
     * Precondition: both ES indices must already exist.
     *
     * @param string $sourceIndexName the name of the source index in ES.
     * @param string $targetIndexName the name of the target index in ES. If existing, will be deleted
     *
     * @throws BadRequest400Exception
     * @throws NoNodesAvailableException
     */
    protected function performReindex(string $sourceIndexName, string $targetIndexName)
    {
        $esClient = $this->getElasticSearchClient();

        $sourceIndexName = strtolower($sourceIndexName);
        $targetIndexName = strtolower($targetIndexName);

        $body =
            [
                'source' => [
                    'index' => $sourceIndexName,

                ],
                'dest' => [
                    'index' => $targetIndexName,
                ],
            ];

        $startTime = time();
        Logger::info('Start reindexing process in Elastic Search...', [
            'sourceIndexName' => $sourceIndexName,
            'targetIndexName' => $targetIndexName,
            'method' => 'POST',
            'uri' => '/_reindex',
            'body' => $body,
        ]);

        $esClient->reindex([
            'body' => $body,
        ]);

        Logger::info(sprintf('Completed re-index in %.02f seconds.', (time() - $startTime)));
    }

    /**
     * Performs native reindexing in ES
     * - new index with new version is created
     * - data is copied from old index to new index
     *
     * While in reindex
     * - all index updates are stored into store table only, and transferred with next ecommerce:indexservice:process-queue update-index
     * - no index structure updates are allowed
     *
     * @throws BadRequest400Exception
     * @throws NoNodesAvailableException
     */
    public function startReindexMode()
    {
        try {
            $this->activateIndexLock(); //lock all other processes

            $currentIndexName = $this->getIndexNameVersion();
            $nextIndex = $this->getNextIndexVersion();
            $nextIndexName = $this->getIndexNameVersion($nextIndex);

            $this->deleteEsIndexIfExisting($nextIndexName);
            $this->createEsIndex($nextIndexName);
            $this->putIndexMapping($nextIndexName);

            $this->blockIndexWrite($currentIndexName);
            $this->performReindex($currentIndexName, $nextIndexName);
            $this->unblockIndexWrite($currentIndexName);

            $this->indexVersion = $nextIndex;

            $this->switchIndexAlias();
        } finally {
            $this->releaseIndexLock();
        }
    }

    /**
     *
     * Perform a synonym update on the currently selected ES index, if necessary.
     *
     * Attention: the current index will be closed and opened, so it won't be available for a tiny moment (typically some milliseconds).
     *
     * @param string $indexNameOverride if given, then that index will be used instead of the current index.
     * @param bool $skipComparison if explicitly set to true, then the comparison whether the synonyms between the current index settings
     *        and the local index settings vary, will be skipped, and the index settings will be updated regardless.
     * @param bool $skipLocking if explictly set to true, then no global lock will be activated / released.
     *
     * @throws \Exception is thrown if the synonym transmission fails.
     */
    public function updateSynonyms(string $indexNameOverride = '', bool $skipComparison = false, bool $skipLocking = true)
    {
        try {
            if (!$skipLocking) {
                $this->activateIndexLock(); //lock all other processes
            }

            $indexName = $indexNameOverride ?: $this->getIndexNameVersion();

            $indexSettingsSynonymPartLocalConfig = $this->extractMinimalSynonymFiltersTreeFromTenantConfig();
            if (empty($indexSettingsSynonymPartLocalConfig)) {
                Logger::info('No index update required, as no synonym providers are configured. '.
                    'If filters have been removed, then reindexing will help to get rid of old configurations.'
                );

                return;
            }

            $esClient = $this->getElasticSearchClient();

            if (!$skipComparison) {
                $indexSettingsCurrentEs = $esClient->indices()->getSettings(['index' => $indexName])[$indexName]['settings']['index'];
                $indexSettingsSynonymPartEs = $this->extractMinimalSynonymFiltersTreeFromIndexSettings($indexSettingsCurrentEs);

                if ($indexSettingsSynonymPartEs == $indexSettingsSynonymPartLocalConfig) {
                    Logger::info(sprintf('The synonyms in ES index "%s" are identical with those of the local configuration. '.
                        'No update required.', $indexName));

                    return;
                }
            }

            Logger::info(sprintf('Update synonyms in "%s"...', $indexName));
            $esClient->indices()->close(['index' => $indexName]);

            $result = $esClient->indices()->putSettings([
                'index' => $indexName,
                'body' => [
                    'index' => $indexSettingsSynonymPartLocalConfig,
                ],
            ]);

            $esClient->indices()->open(['index' => $indexName]);

            if (!$result['acknowledged']) {
                //exception must be thrown after re-opening the index!
                throw new \Exception('Index synonym settings update failed. IndexName: ' . $indexName);
            }
        } finally {
            if (!$skipLocking) {
                $this->releaseIndexLock();
            }
        }
    }

    /**
     * Extract the minimal synonym filters tree based on the tenant config's synonym provider configuration.
     *
     * @return array the index tree settings ready to be pushed into the index, or an empty array, if no configuration exists.
     */
    protected function extractMinimalSynonymFiltersTreeFromTenantConfig(): array
    {
        $indexPart = [];
        foreach ($this->tenantConfig->getSynonymProviders() as $filterName => $synonymProvider) {
            $synonymLines = $synonymProvider->getSynonyms();
            if (empty($indexPart)) {
                $indexPart = [
                    'analysis' =>
                        [
                            'filter' => [],
                        ],
                ];
            }

            $indexPart['analysis']['filter'][$filterName] = ['synonyms' => $synonymLines];
        }

        return $indexPart;
    }

    /**
     * Extract that part of the ES analysis index settings that are related to synonym (provider) filters.
     *
     * @param array $indexSettings the index settings
     *
     * @return array part of the index_settings that contains the synonym-related filters, including
     *  the parent elements:
     *      - analysis
     *          - filter
     *              - synonym_filter_1:
     *                  - type: synonym/synonym_graph
     *                  - ...
     */
    public function extractMinimalSynonymFiltersTreeFromIndexSettings(array $indexSettings): array
    {
        $filters = isset($indexSettings['analysis']['filter']) ? $indexSettings['analysis']['filter'] : [];
        $indexPart = [];
        if ($filters) {
            $synonymProviderMap = $this->tenantConfig->getSynonymProviders();
            foreach ($filters as $filterName => $filter) {
                if (array_key_exists($filterName, $synonymProviderMap)) {
                    if (empty($indexPart)) {
                        $indexPart = [
                            'analysis' =>
                                [
                                    'filter' => [],
                                ],
                        ];
                    }

                    $indexPart['analysis']['filter'][$filterName]['synonyms'] = $filter['synonyms'];
                }
            }
        }

        return $indexPart;
    }

    /**
     * Verify if the index is currently locked.
     *
     * @param bool $throwException if set to false then no exception will be thrown if index is locked
     *
     * @return bool returns true if no exception is thrown and the index is locked
     *
     * @throws \Exception
     */
    protected function checkIndexLock(bool $throwException = true): bool
    {
        if (TmpStore::get(self::REINDEXING_LOCK_KEY)) {
            $errorMessage = sprintf('Index is currently locked by "%s" as reindex is in progress.', self::REINDEXING_LOCK_KEY);
            if ($throwException) {
                throw new \Exception($errorMessage);
            } else {
                //only write log message once a minute to not spam up log file when running update index
                if ($this->lastLockLogTimestamp < time() - 60) {
                    $this->lastLockLogTimestamp = time();
                    Logger::warning($errorMessage . ' (will suppress subsequent log messages of same type for next 60 seconds)');
                }
            }

            return true;
        }

        return false;
    }

    protected function activateIndexLock()
    {
        TmpStore::set(self::REINDEXING_LOCK_KEY, 1, null, 60 * 10);
    }

    protected function releaseIndexLock()
    {
        TmpStore::delete(self::REINDEXING_LOCK_KEY);
        $this->lastLockLogTimestamp = 0;
    }
}
