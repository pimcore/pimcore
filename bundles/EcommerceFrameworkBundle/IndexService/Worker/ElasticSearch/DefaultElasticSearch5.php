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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Logger;

/**
 *
 *  Use this for Adapter for ES Version >= 2 AND <= 5
 */
class DefaultElasticSearch5 extends AbstractElasticSearch
{
    public function getSystemAttributes($includeTypes = false)
    {
        $systemAttributes = [
            'o_id' => 'long',
            'o_classId' => 'string',
            'o_parentId' => 'long',
            'o_virtualProductId' => 'long',
            'o_virtualProductActive' => 'boolean',
            'o_type' => 'string',
            'categoryIds' => 'long',
            'categoryPaths' => 'string',
            'parentCategoryIds' => 'long',
            'priceSystemName' => 'string',
            'active' => 'boolean',
            'inProductList' => 'boolean', ];

        if ($includeTypes) {
            return $systemAttributes;
        } else {
            return array_keys($systemAttributes);
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
        foreach ([ProductListInterface::PRODUCT_TYPE_VARIANT, ProductListInterface::PRODUCT_TYPE_OBJECT] as $mappingType) {
            $params = $this->getMappingParams($mappingType);
            $params['index'] = $indexName;
            $result = $esClient->indices()->putMapping($params);

            if (!$result['acknowledged']) {
                throw new \Exception('Putting mapping to index failed. IndexName: ' . $indexName);
            }
        }

        Logger::info('Index-Actions - updated Mapping for Index: ' . $indexName);
    }

    protected function getMappingParams($type = null)
    {
        if ($type == ProductListInterface::PRODUCT_TYPE_OBJECT) {
            $params = [
                'index' => $this->getIndexNameVersion(),
                'type' => ProductListInterface::PRODUCT_TYPE_OBJECT,
                'body' => [
                    ProductListInterface::PRODUCT_TYPE_OBJECT => [
                        'properties' => $this->createMappingAttributes(),
                    ],
                ],
            ];

            return $params;
        } elseif ($type == ProductListInterface::PRODUCT_TYPE_VARIANT) {
            $params = [
                'index' => $this->getIndexNameVersion(),
                'type' => ProductListInterface::PRODUCT_TYPE_VARIANT,
                'body' => [
                    ProductListInterface::PRODUCT_TYPE_VARIANT => [
                        '_parent' => ['type' => ProductListInterface::PRODUCT_TYPE_OBJECT],
                        'properties' => $this->createMappingAttributes(),
                    ],
                ],
            ];

            return $params;
        }

        throw new \Exception('Unknown Type for mapping params');
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

            //index didn't exist -> reset index queue to make sure all products get reindexed
            $this->resetIndexingQueue();

            $this->createEsAliasIfMissing();
        }

        foreach ([ProductListInterface::PRODUCT_TYPE_VARIANT, ProductListInterface::PRODUCT_TYPE_OBJECT] as $mappingType) {
            $params = $this->getMappingParams($mappingType);

            try {
                $result = $esClient->indices()->putMapping($params);
                Logger::info('Index-Actions - updated Mapping for Index: ' . $this->getIndexNameVersion());
            } catch (\Exception $e) {
                Logger::info($e->getMessage());

                throw new \Exception("Can't create Mapping - Reindex might be necessary, see 'ecommerce:indexservice:elasticsearch-sync reindex' command. Message: " . $e->getMessage());
            }
        }

        // index created return "true" and mapping creation returns array
        if ((is_array($result) && !$result['acknowledged']) || (is_bool($result) && !$result)) {
            throw new \Exception('Index creation failed');
        }
    }

    protected function createMappingAttributes()
    {
        $mappingAttributes = [];
        //add system attributes
        $systemAttributesMapping = [];
        foreach ($this->getSystemAttributes(true) as $name => $type) {
            $systemAttributesMapping[$name] = ['type' => $type, 'store' => true, 'index' => 'not_analyzed'];
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
                        'index' => 'not_analyzed',
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

        return $mappingAttributes;
    }

    protected function doDeleteFromIndex($objectId, IndexableInterface $object = null)
    {
        $esClient = $this->getElasticSearchClient();

        if ($object) {
            try {
                $params = ['index' => $this->getIndexNameVersion(), 'type' => $object->getOSIndexType(), 'id' => $objectId];
                if ($object->getOSIndexType() == ProductListInterface::PRODUCT_TYPE_VARIANT) {
                    $params['parent'] = $this->tenantConfig->createVirtualParentIdForSubId($object, $objectId);
                }
                $esClient->delete($params);

                $this->deleteFromStoreTable($objectId);
            } catch (\Exception $e) {
                $check = json_decode($e->getMessage(), true);
                if (!$check['found']) { //not in es index -> we can delete it from store table
                    $this->deleteFromStoreTable($objectId);
                } else {
                    Logger::emergency('Could not delete item form ES index: ID: ' . $objectId.' Message: ' . $e->getMessage());
                }
            }
        } else {
            //object is empty so the object does not exist in pimcore any more. therefore it has to be deleted from the index, store table and mockup table
            try {
                $esClient->delete(['index' => $this->getIndexNameVersion(), 'type' => ProductListInterface::PRODUCT_TYPE_OBJECT, 'id' => $objectId]);
            } catch (\Exception $e) {
                Logger::warn('Could not delete item form ES index: ID: ' . $objectId.' Message: ' . $e->getMessage());
            }

            // we cannot delete variants from ES when we don't know their parents anymore.
            // Delete won't work w/o a parent specified, as there is a parent-child-relationship.
            // So this might produce an invalid index.

            $this->deleteFromStoreTable($objectId);
        }
    }

    /**
     * If a variant is moved from one parent to another one the original document needs to be deleted as otherwise the variant will be stored twice in the index
     *
     * @param array $indexSystemData
     */
    protected function deleteMovedParentRelations($indexSystemData)
    {
        $esClient = $this->getElasticSearchClient();

        $variants = $esClient->search([
            'index' => $this->getIndexNameVersion(),
            'type' => ProductListInterface::PRODUCT_TYPE_VARIANT,
            'body' => [
                '_source' => false,
                'query' => [
                    'bool' => [
                        'must' => [
                            'term' => [
                                'system.o_id' => $indexSystemData['o_id'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $hits = $variants['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            if ($hit['_parent'] != $indexSystemData['o_virtualProductId']) {
                $params = [
                    'index' => $this->getIndexNameVersion(),
                    'type' => ProductListInterface::PRODUCT_TYPE_VARIANT,
                    'id' => $indexSystemData['o_id'],
                    'parent' => $hit['_parent'],
                ];
                $esClient->delete($params);
            }
        }
    }

    /**
     * only prepare data for updating index
     *
     * @param int $objectId
     * @param array|null $data
     * @param array|null $metadata
     */
    protected function doUpdateIndex($objectId, $data = null, $metadata = null)
    {
        $isLocked = $this->checkIndexLock(false);

        if ($isLocked) {
            return;
        }

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
            if (!empty($indexSystemData['o_virtualProductId']) && $indexSystemData['o_id'] != $indexSystemData['o_virtualProductId']) {
                $this->deleteMovedParentRelations($indexSystemData);
                $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_type' => $indexSystemData['o_type'], '_id' => $objectId, '_parent' => $indexSystemData['o_virtualProductId']]];
            } else {
                $this->bulkIndexData[] = ['index' => ['_index' => $this->getIndexNameVersion(), '_type' => $indexSystemData['o_type'], '_id' => $objectId]];
            }
            $this->bulkIndexData[] = array_filter(['system' => array_filter($indexSystemData), 'type' => $indexSystemData['o_type'], 'attributes' => array_filter($indexAttributeData), 'relations' => $indexRelationData, 'subtenants' => $data['subtenants']]);
        }
    }

    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return ProductListInterface
     */
    public function getProductList()
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ElasticSearch\DefaultElasticSearch5($this->tenantConfig);
    }

    /**
     * Blocks all write operations
     *
     * @param string $indexName the name of the index.
     */
    protected function blockIndexWrite(string $indexName)
    {
        // Currently not tested in Elasticsearch < 6 and therefore disabled
    }

    /**
     * Blocks all write operations
     *
     * @param string $indexName the name of the index.
     */
    protected function unblockIndexWrite(string $indexName)
    {
        // Currently not tested in Elasticsearch < 6 and therefore disabled
    }
}
