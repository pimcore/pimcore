<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch;

use Doctrine\DBAL\Connection;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Logger;
use Pimcore\Model\Tool\TmpStore;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @property ElasticSearch $tenantConfig
 */
abstract class AbstractElasticSearch extends Worker\ProductCentricBatchProcessingWorker implements Worker\BatchProcessingWorkerInterface
{
    const STORE_TABLE_NAME = 'ecommerceframework_productindex_store_elastic';

    const RELATION_FIELD = 'parentchildrelation';

    const REINDEXING_LOCK_KEY = 'elasticsearch_reindexing_lock';

    const DEFAULT_TIMEOUT_MS_FRONTEND = 20000; // 20 seconds

    const DEFAULT_TIMEOUT_MS_BACKEND =  120000; // 2 minutes

    /**
     * Default value for the mapping of custom attributes
     *
     * @var bool
     */
    protected bool $storeCustomAttributes = true;

    protected ?Client $elasticSearchClient = null;

    /**
     * index name of elastic search must be lower case
     * the index name is an alias to indexname-versionnumber
     *
     * @var string
     */
    protected string $indexName;

    /**
     * The Version number of the Index (we increase the Version number if the mapping cant be changed (reindexing process))
     *
     * @var int|null
     */
    protected ?int $indexVersion = null;

    protected array $bulkIndexData = [];

    protected array $indexStoreMetaData = [];

    protected int $lastLockLogTimestamp = 0;

    /**
     * name for routing param for ES bulk requests
     *
     * @var string
     */
    protected string $routingParamName = 'routing';

    protected LoggerInterface $logger;

    public function __construct(ElasticSearchConfigInterface $tenantConfig, Connection $db, EventDispatcherInterface $eventDispatcher, LoggerInterface $pimcoreEcommerceEsLogger)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher);
        $this->logger = $pimcoreEcommerceEsLogger;
        $this->indexName = ($tenantConfig->getClientConfig('indexName')) ? strtolower($tenantConfig->getClientConfig('indexName')) : strtolower($this->name);
    }

    /**
     * should custom attributes be stored separately
     *
     * @return bool
     */
    public function getStoreCustomAttributes(): bool
    {
        return $this->storeCustomAttributes;
    }

    /**
     * Do store custom attributes
     *
     * @param bool $storeCustomAttributes
     */
    public function setStoreCustomAttributes(bool $storeCustomAttributes): void
    {
        $this->storeCustomAttributes = $storeCustomAttributes;
    }

    /**
     * the versioned index-name
     *
     * @param int|null $indexVersionOverride if set, then the index name for a specific index version is built. example. 13
     *
     * @return string the name of the index, such as at_de_elastic_13
     */
    public function getIndexNameVersion(int $indexVersionOverride = null): string
    {
        $indexVersion = $indexVersionOverride ?? $this->getIndexVersion();

        return $this->indexName . '-' . $indexVersion;
    }

    public function getIndexVersion(): int
    {
        if ($this->indexVersion === null) {
            $this->indexVersion = 0;
            $esClient = $this->getElasticSearchClient();

            try {
                $result = $esClient->indices()->getAlias([
                    'name' => $this->indexName,
                ])->asArray();

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
            } catch (ClientResponseException $e) {
                if ($e->getCode() === 404) {
                    $this->indexVersion = 0;
                } else {
                    throw $e;
                }
            }
        }

        return $this->indexVersion;
    }

    /**
     * @return $this
     */
    public function setIndexVersion(int $indexVersion): static
    {
        $this->indexVersion = $indexVersion;

        return $this;
    }

    /**
     * @param Client|null $elasticSearchClient
     */
    public function setElasticSearchClient(?Client $elasticSearchClient): void
    {
        $this->elasticSearchClient = $elasticSearchClient;
    }

    public function getElasticSearchClient(): ?Client
    {
        return $this->elasticSearchClient;
    }

    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @throws \Exception
     */
    public function createOrUpdateIndexStructures(): void
    {
        $this->doCreateOrUpdateIndexStructures();
    }

    protected function createMappingAttributes(): array
    {
        $mappingAttributes = [];
        //add system attributes
        $systemAttributesMapping = [];
        foreach ($this->getSystemAttributes(true) as $name => $type) {
            $systemAttributesMapping[$name] = ['type' => $type, 'store' => true];
        }
        $mappingAttributes['system'] = ['type' => ProductListInterface::PRODUCT_TYPE_OBJECT, 'dynamic' => false, 'properties' => $systemAttributesMapping];

        //add custom defined attributes and relation attributes
        $customAttributesMapping = [];
        $relationAttributesMapping = [];

        foreach ($this->tenantConfig->getAttributes() as $attribute) {
            if (empty($attribute->getType())
                && (empty($attribute->getInterpreter()) || !($attribute->getInterpreter() instanceof RelationInterpreterInterface))
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

                if ($type == ProductListInterface::PRODUCT_TYPE_OBJECT) { //object doesn't support index or store
                    $mapping = ['type' => $type];
                }

                if (!$attribute->getOption('store')) {
                    $mapping['store'] = false;
                }

                if ($type == ProductListInterface::PRODUCT_TYPE_OBJECT || $type == 'nested') {
                    unset($mapping['store']);
                }

                if ($isRelation) {
                    $relationAttributesMapping[$attribute->getName()] = $mapping;
                } else {
                    $customAttributesMapping[$attribute->getName()] = $mapping;
                }
            }
        }

        $mappingAttributes['attributes'] = ['type' => ProductListInterface::PRODUCT_TYPE_OBJECT, 'dynamic' => true, 'properties' => $customAttributesMapping];
        $mappingAttributes['relations'] = ['type' => ProductListInterface::PRODUCT_TYPE_OBJECT, 'dynamic' => false, 'properties' => $relationAttributesMapping];
        $mappingAttributes['subtenants'] = ['type' => ProductListInterface::PRODUCT_TYPE_OBJECT, 'dynamic' => true];
        //has to be at top -> join field [system.relation] cannot be added inside an object or in a multi-field
        $mappingAttributes[static::RELATION_FIELD] = ['type' => 'join', 'relations' => ['object' => ProductListInterface::PRODUCT_TYPE_VARIANT]];

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
    public function getSystemAttributes(bool $includeTypes = false): array
    {
        $systemAttributes = [
            'id' => 'long',
            'classId' => 'keyword',
            'parentId' => 'long',
            'virtualProductId' => 'long',
            'virtualProductActive' => 'boolean',
            'type' => 'keyword',
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
    public function deleteFromIndex(IndexableInterface $object): void
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
    public function updateIndex(IndexableInterface $object): void
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

    protected function doUpdateIndex(int $objectId, array $data = null, array $metadata = null): void
    {
        $isLocked = $this->checkIndexLock(false);

        if ($isLocked) {
            return;
        }

        if (empty($data)) {
            $dataEntry = $this->db->fetchAssociative('SELECT data, metadata FROM ' . $this->getStoreTableName() . ' WHERE id = ? AND tenant = ?', [$objectId, $this->name]);
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
            $routingId = $indexSystemData['type'] == ProductListInterface::PRODUCT_TYPE_VARIANT ? $indexSystemData['virtualProductId'] : $indexSystemData['id'];

            if ($metadata !== null && $routingId != $metadata) {
                //routing has changed, need to delete old ES entry
                $this->bulkIndexData[] = ['delete' => ['_index' => $this->getIndexNameVersion(), '_id' => $objectId, $this->routingParamName => $metadata]];
            }

            $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_id' => $objectId, $this->routingParamName => $routingId]];
            $bulkIndexData = array_filter(['system' => array_filter($indexSystemData), 'type' => $indexSystemData['type'], 'attributes' => array_filter($indexAttributeData, function ($value) {
                return $value !== null;
            }), 'relations' => $indexRelationData, 'subtenants' => $data['subtenants']]);

            if ($indexSystemData['type'] == ProductListInterface::PRODUCT_TYPE_VARIANT) {
                $bulkIndexData[self::RELATION_FIELD] = ['name' => $indexSystemData['type'], 'parent' => $indexSystemData['virtualProductId']];
            } else {
                $bulkIndexData[self::RELATION_FIELD] = ['name' => $indexSystemData['type']];
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
     * @return array|string
     */
    protected function doPreIndexDataModification(array|string $data): array|string
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
            ])->asArray();

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

                        $this->db->update(
                            $this->getStoreTableName(),
                            $data,
                            ['id' => $response[$operation]['_id'], 'tenant' => $this->name]
                        );
                    } else {
                        //update crc sums in store table to mark element as indexed
                        $this->db->executeQuery(
                            'UPDATE ' . $this->getStoreTableName() . ' SET crc_index = crc_current, update_status = ?, update_error = ?, metadata = ? WHERE id = ? and tenant = ?',
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

    protected function getStoreTableName(): string
    {
        return self::STORE_TABLE_NAME;
    }

    /**
     * Sets the alias to the current index-version and deletes the old indices
     *
     * @throws \Exception
     */
    public function switchIndexAlias(): void
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
        $result = $esClient->indices()->updateAliases($params)->asArray();
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
        $stats = $esClient->indices()->stats()->asArray();
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
     * @return array|string
     */
    protected function convertArray(array|string $data): array|string
    {
        return $data;
    }

    /**
     * @param int $objectId
     * @param IndexableInterface|null $object
     *
     * @throws \Exception
     */
    protected function doDeleteFromIndex(int $objectId, IndexableInterface $object = null): void
    {
        $esClient = $this->getElasticSearchClient();

        $storeEntry = \Pimcore\Db::get()->fetchAssociative('SELECT * FROM ' . $this->getStoreTableName() . ' WHERE  id=? AND tenant=? ', [$objectId, $this->getTenantConfig()->getTenantName()]);
        if ($storeEntry) {
            $isLocked = $this->checkIndexLock(false);
            if ($isLocked) {
                throw new \Exception('Delete not possible due to product index lock. Please re-try later.');
            }

            try {
                $tenantConfig = $this->getTenantConfig();
                if (!$tenantConfig instanceof ElasticSearchConfigInterface) {
                    throw new \Exception('Expected a ElasticSearchConfigInterface');
                }
                $esClient->delete([
                    'index' => $this->getIndexNameVersion(),
                    'id' => $objectId,
                    $this->routingParamName => $storeEntry['virtualProductId'],
                ]);
            } catch (ClientResponseException $e) {
                if ($e->getCode() !== 404) {
                    throw $e;
                }
            }
            $this->deleteFromStoreTable($objectId);
        }
    }

    protected function doCreateOrUpdateIndexStructures(): void
    {
        $this->checkIndexLock(true);

        $this->createOrUpdateStoreTable();

        $esClient = $this->getElasticSearchClient();

        $result = $esClient->indices()->exists(['index' => $this->getIndexNameVersion()])->asBool();

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
            ])->asArray();
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

    protected function getMappingParams(): array
    {
        $params = [
            'index' => $this->getIndexNameVersion(),
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
            $result = $esClient->indices()->getAlias(['index' => $this->indexName])->asArray();
        } catch (\Exception $e) {
            Logger::error((string) $e);

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
    protected function createEsAliasIfMissing(): void
    {
        $esClient = $this->getElasticSearchClient();
        //create alias for new index if alias doesn't exist so far
        $aliasExists = $esClient->indices()->existsAlias(['name' => $this->indexName])->asBool();
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
            $result = $esClient->indices()->updateAliases($params)->asArray();
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
    protected function createEsIndex(string $indexName): void
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
        ])->asArray();

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
    protected function putIndexMapping(string $indexName): void
    {
        $esClient = $this->getElasticSearchClient();

        $params = $this->getMappingParams();
        $params['index'] = $indexName;
        $result = $esClient->indices()->putMapping($params)->asArray();

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
    protected function deleteEsIndexIfExisting(string $indexName): void
    {
        $esClient = $this->getElasticSearchClient();
        $result = $esClient->indices()->exists(['index' => $indexName])->asBool();
        if ($result) {
            Logger::info('Deleted index '.$indexName.'.');
            $result = $esClient->indices()->delete(['index' => $indexName])->asArray();
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
    protected function blockIndexWrite(string $indexName): void
    {
        $esClient = $this->getElasticSearchClient();
        $result = $esClient->indices()->exists(['index' => $indexName])->asBool();
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
    protected function unblockIndexWrite(string $indexName): void
    {
        $esClient = $this->getElasticSearchClient();
        $result = $esClient->indices()->exists(['index' => $indexName])->asBool();
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
     */
    protected function performReindex(string $sourceIndexName, string $targetIndexName): void
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
     * - all index updates are stored into store table only, and transferred with next ecommerce:indexservice:process-update-queue
     * - no index structure updates are allowed
     *
     * @throws \Exception
     */
    public function startReindexMode(): void
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
    public function updateSynonyms(string $indexNameOverride = '', bool $skipComparison = false, bool $skipLocking = true): void
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
                $settings = $esClient->indices()->getSettings(['index' => $indexName])->asArray();
                $indexSettingsCurrentEs = $settings[$indexName]['settings']['index'];
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
            ])->asArray();

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

    protected function activateIndexLock(): void
    {
        TmpStore::set(self::REINDEXING_LOCK_KEY, 1, null, 60 * 10);
    }

    protected function releaseIndexLock(): void
    {
        TmpStore::delete(self::REINDEXING_LOCK_KEY);
        $this->lastLockLogTimestamp = 0;
    }
}
