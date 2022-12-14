<?php

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

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearchConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Logger;

/**
 *  Use this for ES Version = 8
 */
class DefaultElasticSearch8 extends AbstractElasticSearch
{
    public function getProductList(): \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\DefaultElasticSearch8
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\DefaultElasticSearch8($this->tenantConfig);
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
     * @param Client|null $elasticSearchClient
     */
    public function setElasticSearchClient(?Client $elasticSearchClient): void
    {
        $this->elasticSearchClient = $elasticSearchClient;
    }

    public function getElasticSearchClient(): ?\Elasticsearch\Client
    {
        return $this->elasticSearchClient;
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
                            ['o_id' => $response[$operation]['_id'], 'tenant' => $this->name]
                        );
                    } else {
                        //update crc sums in store table to mark element as indexed
                        $this->db->executeQuery(
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
     * @param int $objectId
     * @param IndexableInterface|null $object
     *
     * @throws \Exception
     */
    protected function doDeleteFromIndex($objectId, IndexableInterface $object = null)
    {
        $esClient = $this->getElasticSearchClient();

        $storeEntry = \Pimcore\Db::get()->fetchAssociative('SELECT * FROM ' . $this->getStoreTableName() . ' WHERE  o_id=? AND tenant=? ', [$objectId, $this->getTenantConfig()->getTenantName()]);
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
                    'type' => $tenantConfig->getElasticSearchClientParams()['indexType'],
                    'id' => $objectId,
                    $this->routingParamName => $storeEntry['o_virtualProductId'],
                ]);
            } catch (ClientResponseException $e) {
                if ($e->getCode() !== 404) {
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
    protected function createEsAliasIfMissing()
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
    protected function putIndexMapping(string $indexName)
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
    protected function deleteEsIndexIfExisting(string $indexName)
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
    protected function blockIndexWrite(string $indexName)
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
    protected function unblockIndexWrite(string $indexName)
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
}
