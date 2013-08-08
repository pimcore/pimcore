<?php
/*
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */


  /**
   * The "datasets" collection of methods.
   * Typical usage is:
   *  <code>
   *   $datastoreService = new Google_DatastoreService(...);
   *   $datasets = $datastoreService->datasets;
   *  </code>
   */
  class Google_DatasetsServiceResource extends Google_ServiceResource {

    /**
     * Allocate IDs for incomplete keys (useful for referencing an entity before it is inserted).
     * (datasets.allocateIds)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_AllocateIdsRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_AllocateIdsResponse
     */
    public function allocateIds($datasetId, Google_AllocateIdsRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('allocateIds', array($params));
      if ($this->useObjects()) {
        return new Google_AllocateIdsResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Begin a new transaction. (datasets.beginTransaction)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_BeginTransactionRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_BeginTransactionResponse
     */
    public function beginTransaction($datasetId, Google_BeginTransactionRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('beginTransaction', array($params));
      if ($this->useObjects()) {
        return new Google_BeginTransactionResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Create, delete or modify some entities outside a transaction. (datasets.blindWrite)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_BlindWriteRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_BlindWriteResponse
     */
    public function blindWrite($datasetId, Google_BlindWriteRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('blindWrite', array($params));
      if ($this->useObjects()) {
        return new Google_BlindWriteResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Commit a transaction, optionally creating, deleting or modifying some entities. (datasets.commit)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_CommitRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_CommitResponse
     */
    public function commit($datasetId, Google_CommitRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('commit', array($params));
      if ($this->useObjects()) {
        return new Google_CommitResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Look up some entities by key. (datasets.lookup)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_LookupRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_LookupResponse
     */
    public function lookup($datasetId, Google_LookupRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('lookup', array($params));
      if ($this->useObjects()) {
        return new Google_LookupResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Roll back a transaction. (datasets.rollback)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_RollbackRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_RollbackResponse
     */
    public function rollback($datasetId, Google_RollbackRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('rollback', array($params));
      if ($this->useObjects()) {
        return new Google_RollbackResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Query for entities. (datasets.runQuery)
     *
     * @param string $datasetId Identifies the dataset.
     * @param Google_RunQueryRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_RunQueryResponse
     */
    public function runQuery($datasetId, Google_RunQueryRequest $postBody, $optParams = array()) {
      $params = array('datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('runQuery', array($params));
      if ($this->useObjects()) {
        return new Google_RunQueryResponse($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Datastore (v1beta1).
 *
 * <p>
 * API for accessing Google Cloud Datastore.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/datastore/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_DatastoreService extends Google_Service {
  public $datasets;
  /**
   * Constructs the internal representation of the Datastore service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'datastore/v1beta1/datasets/';
    $this->version = 'v1beta1';
    $this->serviceName = 'datastore';

    $client->addService($this->serviceName, $this->version);
    $this->datasets = new Google_DatasetsServiceResource($this, $this->serviceName, 'datasets', json_decode('{"methods": {"allocateIds": {"id": "datastore.datasets.allocateIds", "path": "{datasetId}/allocateIds", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "AllocateIdsRequest"}, "response": {"$ref": "AllocateIdsResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}, "beginTransaction": {"id": "datastore.datasets.beginTransaction", "path": "{datasetId}/beginTransaction", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "BeginTransactionRequest"}, "response": {"$ref": "BeginTransactionResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}, "blindWrite": {"id": "datastore.datasets.blindWrite", "path": "{datasetId}/blindWrite", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "BlindWriteRequest"}, "response": {"$ref": "BlindWriteResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}, "commit": {"id": "datastore.datasets.commit", "path": "{datasetId}/commit", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "CommitRequest"}, "response": {"$ref": "CommitResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}, "lookup": {"id": "datastore.datasets.lookup", "path": "{datasetId}/lookup", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "LookupRequest"}, "response": {"$ref": "LookupResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}, "rollback": {"id": "datastore.datasets.rollback", "path": "{datasetId}/rollback", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "RollbackRequest"}, "response": {"$ref": "RollbackResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}, "runQuery": {"id": "datastore.datasets.runQuery", "path": "{datasetId}/runQuery", "httpMethod": "POST", "parameters": {"datasetId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "RunQueryRequest"}, "response": {"$ref": "RunQueryResponse"}, "scopes": ["https://www.googleapis.com/auth/userinfo.email"]}}}', true));

  }
}



class Google_AllocateIdsRequest extends Google_Model {
  protected $__keysType = 'Google_Key';
  protected $__keysDataType = 'array';
  public $keys;
  public function setKeys(/* array(Google_Key) */ $keys) {
    $this->assertIsArray($keys, 'Google_Key', __METHOD__);
    $this->keys = $keys;
  }
  public function getKeys() {
    return $this->keys;
  }
}

class Google_AllocateIdsResponse extends Google_Model {
  protected $__keysType = 'Google_Key';
  protected $__keysDataType = 'array';
  public $keys;
  public $kind;
  public function setKeys(/* array(Google_Key) */ $keys) {
    $this->assertIsArray($keys, 'Google_Key', __METHOD__);
    $this->keys = $keys;
  }
  public function getKeys() {
    return $this->keys;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Google_BeginTransactionRequest extends Google_Model {
  public $isolationLevel;
  public function setIsolationLevel( $isolationLevel) {
    $this->isolationLevel = $isolationLevel;
  }
  public function getIsolationLevel() {
    return $this->isolationLevel;
  }
}

class Google_BeginTransactionResponse extends Google_Model {
  public $kind;
  public $transaction;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setTransaction( $transaction) {
    $this->transaction = $transaction;
  }
  public function getTransaction() {
    return $this->transaction;
  }
}

class Google_BlindWriteRequest extends Google_Model {
  protected $__mutationType = 'Google_Mutation';
  protected $__mutationDataType = '';
  public $mutation;
  public function setMutation(Google_Mutation $mutation) {
    $this->mutation = $mutation;
  }
  public function getMutation() {
    return $this->mutation;
  }
}

class Google_BlindWriteResponse extends Google_Model {
  public $kind;
  protected $__mutationResultType = 'Google_MutationResult';
  protected $__mutationResultDataType = '';
  public $mutationResult;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMutationResult(Google_MutationResult $mutationResult) {
    $this->mutationResult = $mutationResult;
  }
  public function getMutationResult() {
    return $this->mutationResult;
  }
}

class Google_CommitRequest extends Google_Model {
  protected $__mutationType = 'Google_Mutation';
  protected $__mutationDataType = '';
  public $mutation;
  public $transaction;
  public function setMutation(Google_Mutation $mutation) {
    $this->mutation = $mutation;
  }
  public function getMutation() {
    return $this->mutation;
  }
  public function setTransaction( $transaction) {
    $this->transaction = $transaction;
  }
  public function getTransaction() {
    return $this->transaction;
  }
}

class Google_CommitResponse extends Google_Model {
  public $kind;
  protected $__mutationResultType = 'Google_MutationResult';
  protected $__mutationResultDataType = '';
  public $mutationResult;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMutationResult(Google_MutationResult $mutationResult) {
    $this->mutationResult = $mutationResult;
  }
  public function getMutationResult() {
    return $this->mutationResult;
  }
}

class Google_CompositeFilter extends Google_Model {
  protected $__filtersType = 'Google_Filter';
  protected $__filtersDataType = 'array';
  public $filters;
  public $operator;
  public function setFilters(/* array(Google_Filter) */ $filters) {
    $this->assertIsArray($filters, 'Google_Filter', __METHOD__);
    $this->filters = $filters;
  }
  public function getFilters() {
    return $this->filters;
  }
  public function setOperator( $operator) {
    $this->operator = $operator;
  }
  public function getOperator() {
    return $this->operator;
  }
}

class Google_Entity extends Google_Model {
  protected $__keyType = 'Google_Key';
  protected $__keyDataType = '';
  public $key;
  protected $__propertiesType = 'Google_Property';
  protected $__propertiesDataType = 'map';
  public $properties;
  public function setKey(Google_Key $key) {
    $this->key = $key;
  }
  public function getKey() {
    return $this->key;
  }
  /* lynchb@ Made a modification here to remove the typing
   * allow for an array of properties to be set
   * on an entity. Otherwise the file is unchanged
   * from the generator.
   */
  public function setProperties($properties) {
    $this->properties = $properties;
  }
  public function getProperties() {
    return $this->properties;
  }
}

class Google_EntityResult extends Google_Model {
  protected $__entityType = 'Google_Entity';
  protected $__entityDataType = '';
  public $entity;
  public function setEntity(Google_Entity $entity) {
    $this->entity = $entity;
  }
  public function getEntity() {
    return $this->entity;
  }
}

class Google_Filter extends Google_Model {
  protected $__compositeFilterType = 'Google_CompositeFilter';
  protected $__compositeFilterDataType = '';
  public $compositeFilter;
  protected $__propertyFilterType = 'Google_PropertyFilter';
  protected $__propertyFilterDataType = '';
  public $propertyFilter;
  public function setCompositeFilter(Google_CompositeFilter $compositeFilter) {
    $this->compositeFilter = $compositeFilter;
  }
  public function getCompositeFilter() {
    return $this->compositeFilter;
  }
  public function setPropertyFilter(Google_PropertyFilter $propertyFilter) {
    $this->propertyFilter = $propertyFilter;
  }
  public function getPropertyFilter() {
    return $this->propertyFilter;
  }
}

class Google_Key extends Google_Model {
  protected $__partitionIdType = 'Google_PartitionId';
  protected $__partitionIdDataType = '';
  public $partitionId;
  protected $__pathType = 'Google_KeyPathElement';
  protected $__pathDataType = 'array';
  public $path;
  public function setPartitionId(Google_PartitionId $partitionId) {
    $this->partitionId = $partitionId;
  }
  public function getPartitionId() {
    return $this->partitionId;
  }
  public function setPath(/* array(Google_KeyPathElement) */ $path) {
    $this->assertIsArray($path, 'Google_KeyPathElement', __METHOD__);
    $this->path = $path;
  }
  public function getPath() {
    return $this->path;
  }
}

class Google_KeyPathElement extends Google_Model {
  public $id;
  public $kind;
  public $name;
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class Google_KindExpression extends Google_Model {
  public $name;
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class Google_LookupRequest extends Google_Model {
  protected $__keysType = 'Google_Key';
  protected $__keysDataType = 'array';
  public $keys;
  protected $__readOptionsType = 'Google_ReadOptions';
  protected $__readOptionsDataType = '';
  public $readOptions;
  public function setKeys(/* array(Google_Key) */ $keys) {
    $this->assertIsArray($keys, 'Google_Key', __METHOD__);
    $this->keys = $keys;
  }
  public function getKeys() {
    return $this->keys;
  }
  public function setReadOptions(Google_ReadOptions $readOptions) {
    $this->readOptions = $readOptions;
  }
  public function getReadOptions() {
    return $this->readOptions;
  }
}

class Google_LookupResponse extends Google_Model {
  protected $__deferredType = 'Google_Key';
  protected $__deferredDataType = 'array';
  public $deferred;
  protected $__foundType = 'Google_EntityResult';
  protected $__foundDataType = 'array';
  public $found;
  public $kind;
  protected $__missingType = 'Google_EntityResult';
  protected $__missingDataType = 'array';
  public $missing;
  public function setDeferred(/* array(Google_Key) */ $deferred) {
    $this->assertIsArray($deferred, 'Google_Key', __METHOD__);
    $this->deferred = $deferred;
  }
  public function getDeferred() {
    return $this->deferred;
  }
  public function setFound(/* array(Google_EntityResult) */ $found) {
    $this->assertIsArray($found, 'Google_EntityResult', __METHOD__);
    $this->found = $found;
  }
  public function getFound() {
    return $this->found;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMissing(/* array(Google_EntityResult) */ $missing) {
    $this->assertIsArray($missing, 'Google_EntityResult', __METHOD__);
    $this->missing = $missing;
  }
  public function getMissing() {
    return $this->missing;
  }
}

class Google_Mutation extends Google_Model {
  protected $__deleteType = 'Google_Key';
  protected $__deleteDataType = 'array';
  public $delete;
  public $force;
  protected $__insertType = 'Google_Entity';
  protected $__insertDataType = 'array';
  public $insert;
  protected $__insertAutoIdType = 'Google_Entity';
  protected $__insertAutoIdDataType = 'array';
  public $insertAutoId;
  protected $__updateType = 'Google_Entity';
  protected $__updateDataType = 'array';
  public $update;
  protected $__upsertType = 'Google_Entity';
  protected $__upsertDataType = 'array';
  public $upsert;
  public function setDelete(/* array(Google_Key) */ $delete) {
    $this->assertIsArray($delete, 'Google_Key', __METHOD__);
    $this->delete = $delete;
  }
  public function getDelete() {
    return $this->delete;
  }
  public function setForce( $force) {
    $this->force = $force;
  }
  public function getForce() {
    return $this->force;
  }
  public function setInsert(/* array(Google_Entity) */ $insert) {
    $this->assertIsArray($insert, 'Google_Entity', __METHOD__);
    $this->insert = $insert;
  }
  public function getInsert() {
    return $this->insert;
  }
  public function setInsertAutoId(/* array(Google_Entity) */ $insertAutoId) {
    $this->assertIsArray($insertAutoId, 'Google_Entity', __METHOD__);
    $this->insertAutoId = $insertAutoId;
  }
  public function getInsertAutoId() {
    return $this->insertAutoId;
  }
  public function setUpdate(/* array(Google_Entity) */ $update) {
    $this->assertIsArray($update, 'Google_Entity', __METHOD__);
    $this->update = $update;
  }
  public function getUpdate() {
    return $this->update;
  }
  public function setUpsert(/* array(Google_Entity) */ $upsert) {
    $this->assertIsArray($upsert, 'Google_Entity', __METHOD__);
    $this->upsert = $upsert;
  }
  public function getUpsert() {
    return $this->upsert;
  }
}

class Google_MutationResult extends Google_Model {
  public $indexUpdates;
  protected $__insertAutoIdKeysType = 'Google_Key';
  protected $__insertAutoIdKeysDataType = 'array';
  public $insertAutoIdKeys;
  public function setIndexUpdates( $indexUpdates) {
    $this->indexUpdates = $indexUpdates;
  }
  public function getIndexUpdates() {
    return $this->indexUpdates;
  }
  public function setInsertAutoIdKeys(/* array(Google_Key) */ $insertAutoIdKeys) {
    $this->assertIsArray($insertAutoIdKeys, 'Google_Key', __METHOD__);
    $this->insertAutoIdKeys = $insertAutoIdKeys;
  }
  public function getInsertAutoIdKeys() {
    return $this->insertAutoIdKeys;
  }
}

class Google_PartitionId extends Google_Model {
  public $datasetId;
  public $namespace;
  public function setDatasetId( $datasetId) {
    $this->datasetId = $datasetId;
  }
  public function getDatasetId() {
    return $this->datasetId;
  }
  public function setNamespace( $namespace) {
    $this->namespace = $namespace;
  }
  public function getNamespace() {
    return $this->namespace;
  }
}

class Google_Property extends Google_Model {
  public $multi;
  protected $__valuesType = 'Google_Value';
  protected $__valuesDataType = 'array';
  public $values;
  public function setMulti( $multi) {
    $this->multi = $multi;
  }
  public function getMulti() {
    return $this->multi;
  }
  public function setValues(/* array(Google_Value) */ $values) {
    $this->assertIsArray($values, 'Google_Value', __METHOD__);
    $this->values = $values;
  }
  public function getValues() {
    return $this->values;
  }
}

class Google_PropertyExpression extends Google_Model {
  public $aggregationFunction;
  protected $__propertyType = 'Google_PropertyReference';
  protected $__propertyDataType = '';
  public $property;
  public function setAggregationFunction( $aggregationFunction) {
    $this->aggregationFunction = $aggregationFunction;
  }
  public function getAggregationFunction() {
    return $this->aggregationFunction;
  }
  public function setProperty(Google_PropertyReference $property) {
    $this->property = $property;
  }
  public function getProperty() {
    return $this->property;
  }
}

class Google_PropertyFilter extends Google_Model {
  public $operator;
  protected $__propertyType = 'Google_PropertyReference';
  protected $__propertyDataType = '';
  public $property;
  protected $__valueType = 'Google_Value';
  protected $__valueDataType = '';
  public $value;
  public function setOperator( $operator) {
    $this->operator = $operator;
  }
  public function getOperator() {
    return $this->operator;
  }
  public function setProperty(Google_PropertyReference $property) {
    $this->property = $property;
  }
  public function getProperty() {
    return $this->property;
  }
  public function setValue(Google_Value $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_PropertyOrder extends Google_Model {
  public $direction;
  protected $__propertyType = 'Google_PropertyReference';
  protected $__propertyDataType = '';
  public $property;
  public function setDirection( $direction) {
    $this->direction = $direction;
  }
  public function getDirection() {
    return $this->direction;
  }
  public function setProperty(Google_PropertyReference $property) {
    $this->property = $property;
  }
  public function getProperty() {
    return $this->property;
  }
}

class Google_PropertyReference extends Google_Model {
  public $name;
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class Google_Query extends Google_Model {
  public $endCursor;
  protected $__filterType = 'Google_Filter';
  protected $__filterDataType = '';
  public $filter;
  protected $__groupByType = 'Google_PropertyReference';
  protected $__groupByDataType = 'array';
  public $groupBy;
  protected $__kindsType = 'Google_KindExpression';
  protected $__kindsDataType = 'array';
  public $kinds;
  public $limit;
  public $offset;
  protected $__orderType = 'Google_PropertyOrder';
  protected $__orderDataType = 'array';
  public $order;
  protected $__projectionType = 'Google_PropertyExpression';
  protected $__projectionDataType = 'array';
  public $projection;
  public $startCursor;
  public function setEndCursor( $endCursor) {
    $this->endCursor = $endCursor;
  }
  public function getEndCursor() {
    return $this->endCursor;
  }
  public function setFilter(Google_Filter $filter) {
    $this->filter = $filter;
  }
  public function getFilter() {
    return $this->filter;
  }
  public function setGroupBy(/* array(Google_PropertyReference) */ $groupBy) {
    $this->assertIsArray($groupBy, 'Google_PropertyReference', __METHOD__);
    $this->groupBy = $groupBy;
  }
  public function getGroupBy() {
    return $this->groupBy;
  }
  public function setKinds(/* array(Google_KindExpression) */ $kinds) {
    $this->assertIsArray($kinds, 'Google_KindExpression', __METHOD__);
    $this->kinds = $kinds;
  }
  public function getKinds() {
    return $this->kinds;
  }
  public function setLimit( $limit) {
    $this->limit = $limit;
  }
  public function getLimit() {
    return $this->limit;
  }
  public function setOffset( $offset) {
    $this->offset = $offset;
  }
  public function getOffset() {
    return $this->offset;
  }
  public function setOrder(/* array(Google_PropertyOrder) */ $order) {
    $this->assertIsArray($order, 'Google_PropertyOrder', __METHOD__);
    $this->order = $order;
  }
  public function getOrder() {
    return $this->order;
  }
  public function setProjection(/* array(Google_PropertyExpression) */ $projection) {
    $this->assertIsArray($projection, 'Google_PropertyExpression', __METHOD__);
    $this->projection = $projection;
  }
  public function getProjection() {
    return $this->projection;
  }
  public function setStartCursor( $startCursor) {
    $this->startCursor = $startCursor;
  }
  public function getStartCursor() {
    return $this->startCursor;
  }
}

class Google_QueryResultBatch extends Google_Model {
  public $endCursor;
  public $entityResultType;
  protected $__entityResultsType = 'Google_EntityResult';
  protected $__entityResultsDataType = 'array';
  public $entityResults;
  public $moreResults;
  public $skippedResults;
  public function setEndCursor( $endCursor) {
    $this->endCursor = $endCursor;
  }
  public function getEndCursor() {
    return $this->endCursor;
  }
  public function setEntityResultType( $entityResultType) {
    $this->entityResultType = $entityResultType;
  }
  public function getEntityResultType() {
    return $this->entityResultType;
  }
  public function setEntityResults(/* array(Google_EntityResult) */ $entityResults) {
    $this->assertIsArray($entityResults, 'Google_EntityResult', __METHOD__);
    $this->entityResults = $entityResults;
  }
  public function getEntityResults() {
    return $this->entityResults;
  }
  public function setMoreResults( $moreResults) {
    $this->moreResults = $moreResults;
  }
  public function getMoreResults() {
    return $this->moreResults;
  }
  public function setSkippedResults( $skippedResults) {
    $this->skippedResults = $skippedResults;
  }
  public function getSkippedResults() {
    return $this->skippedResults;
  }
}

class Google_ReadOptions extends Google_Model {
  public $readConsistency;
  public $transaction;
  public function setReadConsistency( $readConsistency) {
    $this->readConsistency = $readConsistency;
  }
  public function getReadConsistency() {
    return $this->readConsistency;
  }
  public function setTransaction( $transaction) {
    $this->transaction = $transaction;
  }
  public function getTransaction() {
    return $this->transaction;
  }
}

class Google_RollbackRequest extends Google_Model {
  public $transaction;
  public function setTransaction( $transaction) {
    $this->transaction = $transaction;
  }
  public function getTransaction() {
    return $this->transaction;
  }
}

class Google_RollbackResponse extends Google_Model {
  public $kind;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Google_RunQueryRequest extends Google_Model {
  protected $__partitionIdType = 'Google_PartitionId';
  protected $__partitionIdDataType = '';
  public $partitionId;
  protected $__queryType = 'Google_Query';
  protected $__queryDataType = '';
  public $query;
  protected $__readOptionsType = 'Google_ReadOptions';
  protected $__readOptionsDataType = '';
  public $readOptions;
  public function setPartitionId(Google_PartitionId $partitionId) {
    $this->partitionId = $partitionId;
  }
  public function getPartitionId() {
    return $this->partitionId;
  }
  public function setQuery(Google_Query $query) {
    $this->query = $query;
  }
  public function getQuery() {
    return $this->query;
  }
  public function setReadOptions(Google_ReadOptions $readOptions) {
    $this->readOptions = $readOptions;
  }
  public function getReadOptions() {
    return $this->readOptions;
  }
}

class Google_RunQueryResponse extends Google_Model {
  protected $__batchType = 'Google_QueryResultBatch';
  protected $__batchDataType = '';
  public $batch;
  public $kind;
  public function setBatch(Google_QueryResultBatch $batch) {
    $this->batch = $batch;
  }
  public function getBatch() {
    return $this->batch;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Google_Value extends Google_Model {
  public $blobKeyValue;
  public $blobValue;
  public $booleanValue;
  public $dateTimeValue;
  public $doubleValue;
  protected $__entityValueType = 'Google_Entity';
  protected $__entityValueDataType = '';
  public $entityValue;
  public $indexed;
  public $integerValue;
  protected $__keyValueType = 'Google_Key';
  protected $__keyValueDataType = '';
  public $keyValue;
  public $meaning;
  public $stringValue;
  public function setBlobKeyValue( $blobKeyValue) {
    $this->blobKeyValue = $blobKeyValue;
  }
  public function getBlobKeyValue() {
    return $this->blobKeyValue;
  }
  public function setBlobValue( $blobValue) {
    $this->blobValue = $blobValue;
  }
  public function getBlobValue() {
    return $this->blobValue;
  }
  public function setBooleanValue( $booleanValue) {
    $this->booleanValue = $booleanValue;
  }
  public function getBooleanValue() {
    return $this->booleanValue;
  }
  public function setDateTimeValue( $dateTimeValue) {
    $this->dateTimeValue = $dateTimeValue;
  }
  public function getDateTimeValue() {
    return $this->dateTimeValue;
  }
  public function setDoubleValue( $doubleValue) {
    $this->doubleValue = $doubleValue;
  }
  public function getDoubleValue() {
    return $this->doubleValue;
  }
  public function setEntityValue(Google_Entity $entityValue) {
    $this->entityValue = $entityValue;
  }
  public function getEntityValue() {
    return $this->entityValue;
  }
  public function setIndexed( $indexed) {
    $this->indexed = $indexed;
  }
  public function getIndexed() {
    return $this->indexed;
  }
  public function setIntegerValue( $integerValue) {
    $this->integerValue = $integerValue;
  }
  public function getIntegerValue() {
    return $this->integerValue;
  }
  public function setKeyValue(Google_Key $keyValue) {
    $this->keyValue = $keyValue;
  }
  public function getKeyValue() {
    return $this->keyValue;
  }
  public function setMeaning( $meaning) {
    $this->meaning = $meaning;
  }
  public function getMeaning() {
    return $this->meaning;
  }
  public function setStringValue( $stringValue) {
    $this->stringValue = $stringValue;
  }
  public function getStringValue() {
    return $this->stringValue;
  }
}
