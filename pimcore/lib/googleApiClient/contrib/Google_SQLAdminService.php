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
   * The "backupRuns" collection of methods.
   * Typical usage is:
   *  <code>
   *   $sqladminService = new Google_SQLAdminService(...);
   *   $backupRuns = $sqladminService->backupRuns;
   *  </code>
   */
  class Google_BackupRunsServiceResource extends Google_ServiceResource {

    /**
     * Retrieves a resource containing information about a backup run. (backupRuns.get)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param string $backupConfiguration Identifier for the backup configuration. This gets generated automatically when a backup configuration is created.
     * @param string $dueTime The time when this run is due to start in RFC 3339 format, for example 2012-11-15T16:19:00.094Z.
     * @param array $optParams Optional parameters.
     * @return Google_BackupRun
     */
    public function get($project, $instance, $backupConfiguration, $dueTime, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'backupConfiguration' => $backupConfiguration, 'dueTime' => $dueTime);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_BackupRun($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all backup runs associated with a given instance and configuration in the reverse
     * chronological order of the enqueued time. (backupRuns.list)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param string $backupConfiguration Identifier for the backup configuration. This gets generated automatically when a backup configuration is created.
     * @param array $optParams Optional parameters.
     *
     * @opt_param int maxResults Maximum number of backup runs per response.
     * @opt_param string pageToken A previously-returned page token representing part of the larger set of results to view.
     * @return Google_BackupRunsListResponse
     */
    public function listBackupRuns($project, $instance, $backupConfiguration, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'backupConfiguration' => $backupConfiguration);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_BackupRunsListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "instances" collection of methods.
   * Typical usage is:
   *  <code>
   *   $sqladminService = new Google_SQLAdminService(...);
   *   $instances = $sqladminService->instances;
   *  </code>
   */
  class Google_InstancesServiceResource extends Google_ServiceResource {

    /**
     * Deletes a database instance. (instances.delete)
     *
     * @param string $project Project ID of the project that contains the instance to be deleted. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param array $optParams Optional parameters.
     * @return Google_InstancesDeleteResponse
     */
    public function delete($project, $instance, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesDeleteResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Exports data from a database instance to a Google Cloud Storage bucket as a MySQL dump file.
     * (instances.export)
     *
     * @param string $project Project ID of the project that contains the instance to be exported. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param Google_InstancesExportRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_InstancesExportResponse
     */
    public function export($project, $instance, Google_InstancesExportRequest $postBody, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('export', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesExportResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves a resource containing information about a database instance. (instances.get)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param array $optParams Optional parameters.
     * @return Google_DatabaseInstance
     */
    public function get($project, $instance, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_DatabaseInstance($data);
      } else {
        return $data;
      }
    }
    /**
     * Imports data into a database instance from a MySQL dump file in Google Cloud Storage.
     * (instances.import)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param Google_InstancesImportRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_InstancesImportResponse
     */
    public function import($project, $instance, Google_InstancesImportRequest $postBody, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('import', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesImportResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Creates a new database instance. (instances.insert)
     *
     * @param string $project Project ID of the project to which the newly created database instances should belong. You can find this on the project summary page of the Google APIs Console.
     * @param Google_DatabaseInstance $postBody
     * @param array $optParams Optional parameters.
     * @return Google_InstancesInsertResponse
     */
    public function insert($project, Google_DatabaseInstance $postBody, $optParams = array()) {
      $params = array('project' => $project, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesInsertResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists instances under a given project in the alphabetical order of the instance name.
     * (instances.list)
     *
     * @param string $project Project ID of the project for which to list database instances. You can find this on the project summary page of the Google APIs Console.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of results to return per response.
     * @opt_param string pageToken A previously-returned page token representing part of the larger set of results to view.
     * @return Google_InstancesListResponse
     */
    public function listInstances($project, $optParams = array()) {
      $params = array('project' => $project);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesListResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates settings of a database instance. Caution: This is not a partial update, so you must
     * include values for all the settings that you want to retain. For partial updates, use patch..
     * This method supports patch semantics. (instances.patch)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param Google_DatabaseInstance $postBody
     * @param array $optParams Optional parameters.
     * @return Google_InstancesUpdateResponse
     */
    public function patch($project, $instance, Google_DatabaseInstance $postBody, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesUpdateResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Restarts a database instance. (instances.restart)
     *
     * @param string $project Project ID of the project that contains the instance to be restarted. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param array $optParams Optional parameters.
     * @return Google_InstancesRestartResponse
     */
    public function restart($project, $instance, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance);
      $params = array_merge($params, $optParams);
      $data = $this->__call('restart', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesRestartResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Restores a backup of a database instance. (instances.restoreBackup)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param string $backupConfiguration The identifier of the backup configuration. This gets generated automatically when a backup configuration is created.
     * @param string $dueTime The time when this run is due to start in RFC 3339 format, for example 2012-11-15T16:19:00.094Z.
     * @param array $optParams Optional parameters.
     * @return Google_InstancesRestoreBackupResponse
     */
    public function restoreBackup($project, $instance, $backupConfiguration, $dueTime, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'backupConfiguration' => $backupConfiguration, 'dueTime' => $dueTime);
      $params = array_merge($params, $optParams);
      $data = $this->__call('restoreBackup', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesRestoreBackupResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates settings of a database instance. Caution: This is not a partial update, so you must
     * include values for all the settings that you want to retain. For partial updates, use patch.
     * (instances.update)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param Google_DatabaseInstance $postBody
     * @param array $optParams Optional parameters.
     * @return Google_InstancesUpdateResponse
     */
    public function update($project, $instance, Google_DatabaseInstance $postBody, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_InstancesUpdateResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "operations" collection of methods.
   * Typical usage is:
   *  <code>
   *   $sqladminService = new Google_SQLAdminService(...);
   *   $operations = $sqladminService->operations;
   *  </code>
   */
  class Google_OperationsServiceResource extends Google_ServiceResource {

    /**
     * Retrieves an instance operation that has been performed on an instance. (operations.get)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param string $operation Instance operation ID.
     * @param array $optParams Optional parameters.
     * @return Google_InstanceOperation
     */
    public function get($project, $instance, $operation, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance, 'operation' => $operation);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_InstanceOperation($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all instance operations that have been performed on the given database instance in the
     * reverse chronological order of the start time. (operations.list)
     *
     * @param string $project Project ID of the project that contains the instance. You can find this on the project summary page of the Google APIs Console.
     * @param string $instance Database instance ID. This does not include the project ID.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults Maximum number of operations per response.
     * @opt_param string pageToken A previously-returned page token representing part of the larger set of results to view.
     * @return Google_OperationsListResponse
     */
    public function listOperations($project, $instance, $optParams = array()) {
      $params = array('project' => $project, 'instance' => $instance);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_OperationsListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "tiers" collection of methods.
   * Typical usage is:
   *  <code>
   *   $sqladminService = new Google_SQLAdminService(...);
   *   $tiers = $sqladminService->tiers;
   *  </code>
   */
  class Google_TiersServiceResource extends Google_ServiceResource {

    /**
     * Lists all available service tiers for Google Cloud SQL, for example D1, D2. For related
     * information, see Pricing. (tiers.list)
     *
     * @param array $optParams Optional parameters.
     * @return Google_TiersListResponse
     */
    public function listTiers($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_TiersListResponse($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_SQLAdmin (v1beta1).
 *
 * <p>
 * API for Cloud SQL database instance management.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/cloud-sql/docs/admin-api/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_SQLAdminService extends Google_Service {
  public $backupRuns;
  public $instances;
  public $operations;
  public $tiers;
  /**
   * Constructs the internal representation of the SQLAdmin service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'sql/v1beta1/';
    $this->version = 'v1beta1';
    $this->serviceName = 'sqladmin';

    $client->addService($this->serviceName, $this->version);
    $this->backupRuns = new Google_BackupRunsServiceResource($this, $this->serviceName, 'backupRuns', json_decode('{"methods": {"get": {"id": "sql.backupRuns.get", "path": "projects/{project}/instances/{instance}/backupRuns/{backupConfiguration}", "httpMethod": "GET", "parameters": {"backupConfiguration": {"type": "string", "required": true, "location": "path"}, "dueTime": {"type": "string", "required": true, "location": "query"}, "instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "BackupRun"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "list": {"id": "sql.backupRuns.list", "path": "projects/{project}/instances/{instance}/backupRuns", "httpMethod": "GET", "parameters": {"backupConfiguration": {"type": "string", "required": true, "location": "query"}, "instance": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "int32", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "BackupRunsListResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}}}', true));
    $this->instances = new Google_InstancesServiceResource($this, $this->serviceName, 'instances', json_decode('{"methods": {"delete": {"id": "sql.instances.delete", "path": "projects/{project}/instances/{instance}", "httpMethod": "DELETE", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "InstancesDeleteResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "export": {"id": "sql.instances.export", "path": "projects/{project}/instances/{instance}/export", "httpMethod": "POST", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "InstancesExportRequest"}, "response": {"$ref": "InstancesExportResponse"}, "scopes": ["https://www.googleapis.com/auth/cloud-platform"]}, "get": {"id": "sql.instances.get", "path": "projects/{project}/instances/{instance}", "httpMethod": "GET", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "DatabaseInstance"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "import": {"id": "sql.instances.import", "path": "projects/{project}/instances/{instance}/import", "httpMethod": "POST", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "InstancesImportRequest"}, "response": {"$ref": "InstancesImportResponse"}, "scopes": ["https://www.googleapis.com/auth/cloud-platform"]}, "insert": {"id": "sql.instances.insert", "path": "projects/{project}/instances", "httpMethod": "POST", "parameters": {"project": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "DatabaseInstance"}, "response": {"$ref": "InstancesInsertResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "list": {"id": "sql.instances.list", "path": "projects/{project}/instances", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "format": "uint32", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "InstancesListResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "patch": {"id": "sql.instances.patch", "path": "projects/{project}/instances/{instance}", "httpMethod": "PATCH", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "DatabaseInstance"}, "response": {"$ref": "InstancesUpdateResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "restart": {"id": "sql.instances.restart", "path": "projects/{project}/instances/{instance}/restart", "httpMethod": "POST", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "InstancesRestartResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "restoreBackup": {"id": "sql.instances.restoreBackup", "path": "projects/{project}/instances/{instance}/restoreBackup", "httpMethod": "POST", "parameters": {"backupConfiguration": {"type": "string", "required": true, "location": "query"}, "dueTime": {"type": "string", "required": true, "location": "query"}, "instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "InstancesRestoreBackupResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "update": {"id": "sql.instances.update", "path": "projects/{project}/instances/{instance}", "httpMethod": "PUT", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "etagRequired": true, "request": {"$ref": "DatabaseInstance"}, "response": {"$ref": "InstancesUpdateResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}}}', true));
    $this->operations = new Google_OperationsServiceResource($this, $this->serviceName, 'operations', json_decode('{"methods": {"get": {"id": "sql.operations.get", "path": "projects/{project}/instances/{instance}/operations/{operation}", "httpMethod": "GET", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "operation": {"type": "string", "required": true, "location": "path"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "InstanceOperation"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}, "list": {"id": "sql.operations.list", "path": "projects/{project}/instances/{instance}/operations", "httpMethod": "GET", "parameters": {"instance": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "uint32", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "project": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "OperationsListResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}}}', true));
    $this->tiers = new Google_TiersServiceResource($this, $this->serviceName, 'tiers', json_decode('{"methods": {"list": {"id": "sql.tiers.list", "path": "tiers", "httpMethod": "GET", "response": {"$ref": "TiersListResponse"}, "scopes": ["https://www.googleapis.com/auth/sqlservice.admin"]}}}', true));

  }
}



class Google_BackupConfiguration extends Google_Model {
  public $enabled;
  public $id;
  public $kind;
  public $startTime;
  public function setEnabled( $enabled) {
    $this->enabled = $enabled;
  }
  public function getEnabled() {
    return $this->enabled;
  }
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
  public function setStartTime( $startTime) {
    $this->startTime = $startTime;
  }
  public function getStartTime() {
    return $this->startTime;
  }
}

class Google_BackupRun extends Google_Model {
  public $backupConfiguration;
  public $dueTime;
  public $endTime;
  public $enqueuedTime;
  protected $__errorType = 'Google_OperationError';
  protected $__errorDataType = '';
  public $error;
  public $instance;
  public $kind;
  public $startTime;
  public $status;
  public function setBackupConfiguration( $backupConfiguration) {
    $this->backupConfiguration = $backupConfiguration;
  }
  public function getBackupConfiguration() {
    return $this->backupConfiguration;
  }
  public function setDueTime( $dueTime) {
    $this->dueTime = $dueTime;
  }
  public function getDueTime() {
    return $this->dueTime;
  }
  public function setEndTime( $endTime) {
    $this->endTime = $endTime;
  }
  public function getEndTime() {
    return $this->endTime;
  }
  public function setEnqueuedTime( $enqueuedTime) {
    $this->enqueuedTime = $enqueuedTime;
  }
  public function getEnqueuedTime() {
    return $this->enqueuedTime;
  }
  public function setError(Google_OperationError $error) {
    $this->error = $error;
  }
  public function getError() {
    return $this->error;
  }
  public function setInstance( $instance) {
    $this->instance = $instance;
  }
  public function getInstance() {
    return $this->instance;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setStartTime( $startTime) {
    $this->startTime = $startTime;
  }
  public function getStartTime() {
    return $this->startTime;
  }
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
}

class Google_BackupRunsListResponse extends Google_Model {
  protected $__itemsType = 'Google_BackupRun';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_BackupRun) */ $items) {
    $this->assertIsArray($items, 'Google_BackupRun', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
}

class Google_DatabaseInstance extends Google_Model {
  public $currentDiskSize;
  public $databaseVersion;
  public $etag;
  public $instance;
  public $kind;
  public $maxDiskSize;
  public $project;
  public $region;
  protected $__settingsType = 'Google_Settings';
  protected $__settingsDataType = '';
  public $settings;
  public $state;
  public function setCurrentDiskSize( $currentDiskSize) {
    $this->currentDiskSize = $currentDiskSize;
  }
  public function getCurrentDiskSize() {
    return $this->currentDiskSize;
  }
  public function setDatabaseVersion( $databaseVersion) {
    $this->databaseVersion = $databaseVersion;
  }
  public function getDatabaseVersion() {
    return $this->databaseVersion;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setInstance( $instance) {
    $this->instance = $instance;
  }
  public function getInstance() {
    return $this->instance;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMaxDiskSize( $maxDiskSize) {
    $this->maxDiskSize = $maxDiskSize;
  }
  public function getMaxDiskSize() {
    return $this->maxDiskSize;
  }
  public function setProject( $project) {
    $this->project = $project;
  }
  public function getProject() {
    return $this->project;
  }
  public function setRegion( $region) {
    $this->region = $region;
  }
  public function getRegion() {
    return $this->region;
  }
  public function setSettings(Google_Settings $settings) {
    $this->settings = $settings;
  }
  public function getSettings() {
    return $this->settings;
  }
  public function setState( $state) {
    $this->state = $state;
  }
  public function getState() {
    return $this->state;
  }
}

class Google_ExportContext extends Google_Model {
  public $database;
  public $kind;
  public $table;
  public $uri;
  public function setDatabase(/* array(Google_string) */ $database) {
    $this->assertIsArray($database, 'Google_string', __METHOD__);
    $this->database = $database;
  }
  public function getDatabase() {
    return $this->database;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setTable(/* array(Google_string) */ $table) {
    $this->assertIsArray($table, 'Google_string', __METHOD__);
    $this->table = $table;
  }
  public function getTable() {
    return $this->table;
  }
  public function setUri( $uri) {
    $this->uri = $uri;
  }
  public function getUri() {
    return $this->uri;
  }
}

class Google_ImportContext extends Google_Model {
  public $database;
  public $kind;
  public $uri;
  public function setDatabase( $database) {
    $this->database = $database;
  }
  public function getDatabase() {
    return $this->database;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setUri(/* array(Google_string) */ $uri) {
    $this->assertIsArray($uri, 'Google_string', __METHOD__);
    $this->uri = $uri;
  }
  public function getUri() {
    return $this->uri;
  }
}

class Google_InstanceOperation extends Google_Model {
  public $endTime;
  public $enqueuedTime;
  protected $__errorType = 'Google_OperationError';
  protected $__errorDataType = 'array';
  public $error;
  protected $__exportContextType = 'Google_ExportContext';
  protected $__exportContextDataType = '';
  public $exportContext;
  protected $__importContextType = 'Google_ImportContext';
  protected $__importContextDataType = '';
  public $importContext;
  public $instance;
  public $kind;
  public $operation;
  public $operationType;
  public $startTime;
  public $state;
  public $userEmailAddress;
  public function setEndTime( $endTime) {
    $this->endTime = $endTime;
  }
  public function getEndTime() {
    return $this->endTime;
  }
  public function setEnqueuedTime( $enqueuedTime) {
    $this->enqueuedTime = $enqueuedTime;
  }
  public function getEnqueuedTime() {
    return $this->enqueuedTime;
  }
  public function setError(/* array(Google_OperationError) */ $error) {
    $this->assertIsArray($error, 'Google_OperationError', __METHOD__);
    $this->error = $error;
  }
  public function getError() {
    return $this->error;
  }
  public function setExportContext(Google_ExportContext $exportContext) {
    $this->exportContext = $exportContext;
  }
  public function getExportContext() {
    return $this->exportContext;
  }
  public function setImportContext(Google_ImportContext $importContext) {
    $this->importContext = $importContext;
  }
  public function getImportContext() {
    return $this->importContext;
  }
  public function setInstance( $instance) {
    $this->instance = $instance;
  }
  public function getInstance() {
    return $this->instance;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
  public function setOperationType( $operationType) {
    $this->operationType = $operationType;
  }
  public function getOperationType() {
    return $this->operationType;
  }
  public function setStartTime( $startTime) {
    $this->startTime = $startTime;
  }
  public function getStartTime() {
    return $this->startTime;
  }
  public function setState( $state) {
    $this->state = $state;
  }
  public function getState() {
    return $this->state;
  }
  public function setUserEmailAddress( $userEmailAddress) {
    $this->userEmailAddress = $userEmailAddress;
  }
  public function getUserEmailAddress() {
    return $this->userEmailAddress;
  }
}

class Google_InstancesDeleteResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_InstancesExportRequest extends Google_Model {
  protected $__exportContextType = 'Google_ExportContext';
  protected $__exportContextDataType = '';
  public $exportContext;
  public function setExportContext(Google_ExportContext $exportContext) {
    $this->exportContext = $exportContext;
  }
  public function getExportContext() {
    return $this->exportContext;
  }
}

class Google_InstancesExportResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_InstancesImportRequest extends Google_Model {
  protected $__importContextType = 'Google_ImportContext';
  protected $__importContextDataType = '';
  public $importContext;
  public function setImportContext(Google_ImportContext $importContext) {
    $this->importContext = $importContext;
  }
  public function getImportContext() {
    return $this->importContext;
  }
}

class Google_InstancesImportResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_InstancesInsertResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_InstancesListResponse extends Google_Model {
  protected $__itemsType = 'Google_DatabaseInstance';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_DatabaseInstance) */ $items) {
    $this->assertIsArray($items, 'Google_DatabaseInstance', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
}

class Google_InstancesRestartResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_InstancesRestoreBackupResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_InstancesUpdateResponse extends Google_Model {
  public $kind;
  public $operation;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOperation( $operation) {
    $this->operation = $operation;
  }
  public function getOperation() {
    return $this->operation;
  }
}

class Google_OperationError extends Google_Model {
  public $code;
  public $kind;
  public function setCode( $code) {
    $this->code = $code;
  }
  public function getCode() {
    return $this->code;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Google_OperationsListResponse extends Google_Model {
  protected $__itemsType = 'Google_InstanceOperation';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_InstanceOperation) */ $items) {
    $this->assertIsArray($items, 'Google_InstanceOperation', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
}

class Google_Settings extends Google_Model {
  public $activationPolicy;
  public $authorizedGaeApplications;
  protected $__backupConfigurationType = 'Google_BackupConfiguration';
  protected $__backupConfigurationDataType = 'array';
  public $backupConfiguration;
  public $kind;
  public $pricingPlan;
  public $replicationType;
  public $tier;
  public function setActivationPolicy( $activationPolicy) {
    $this->activationPolicy = $activationPolicy;
  }
  public function getActivationPolicy() {
    return $this->activationPolicy;
  }
  public function setAuthorizedGaeApplications(/* array(Google_string) */ $authorizedGaeApplications) {
    $this->assertIsArray($authorizedGaeApplications, 'Google_string', __METHOD__);
    $this->authorizedGaeApplications = $authorizedGaeApplications;
  }
  public function getAuthorizedGaeApplications() {
    return $this->authorizedGaeApplications;
  }
  public function setBackupConfiguration(/* array(Google_BackupConfiguration) */ $backupConfiguration) {
    $this->assertIsArray($backupConfiguration, 'Google_BackupConfiguration', __METHOD__);
    $this->backupConfiguration = $backupConfiguration;
  }
  public function getBackupConfiguration() {
    return $this->backupConfiguration;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPricingPlan( $pricingPlan) {
    $this->pricingPlan = $pricingPlan;
  }
  public function getPricingPlan() {
    return $this->pricingPlan;
  }
  public function setReplicationType( $replicationType) {
    $this->replicationType = $replicationType;
  }
  public function getReplicationType() {
    return $this->replicationType;
  }
  public function setTier( $tier) {
    $this->tier = $tier;
  }
  public function getTier() {
    return $this->tier;
  }
}

class Google_Tier extends Google_Model {
  public $DiskQuota;
  public $RAM;
  public $kind;
  public $region;
  public $tier;
  public function setDiskQuota( $DiskQuota) {
    $this->DiskQuota = $DiskQuota;
  }
  public function getDiskQuota() {
    return $this->DiskQuota;
  }
  public function setRAM( $RAM) {
    $this->RAM = $RAM;
  }
  public function getRAM() {
    return $this->RAM;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRegion(/* array(Google_string) */ $region) {
    $this->assertIsArray($region, 'Google_string', __METHOD__);
    $this->region = $region;
  }
  public function getRegion() {
    return $this->region;
  }
  public function setTier( $tier) {
    $this->tier = $tier;
  }
  public function getTier() {
    return $this->tier;
  }
}

class Google_TiersListResponse extends Google_Model {
  protected $__itemsType = 'Google_Tier';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_Tier) */ $items) {
    $this->assertIsArray($items, 'Google_Tier', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}
