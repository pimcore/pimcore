<?php
/*
 * Copyright 2010 Google Inc.
 *
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
   * The "tables" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bigqueryService = new apiBigqueryService(...);
   *   $tables = $bigqueryService->tables;
   *  </code>
   */
  class TablesServiceResource extends apiServiceResource {


    /**
     * Creates a new, empty table in the dataset. (tables.insert)
     *
     * @param string $projectId Project ID of the new table
     * @param string $datasetId Dataset ID of the new table
     * @param Table $postBody
     * @return Table
     */
    public function insert($projectId, $datasetId, Table $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Table($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets the specified table resource by table ID. This method does not return the data in the table,
     * it only returns the table resource, which describes the structure of this table. (tables.get)
     *
     * @param string $projectId Project ID of the requested table
     * @param string $datasetId Dataset ID of the requested table
     * @param string $tableId Table ID of the requested table
     * @return Table
     */
    public function get($projectId, $datasetId, $tableId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Table($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all tables in the specified dataset. (tables.list)
     *
     * @param string $projectId Project ID of the tables to list
     * @param string $datasetId Dataset ID of the tables to list
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken Page token, returned by a previous call, to request the next page of results
     * @opt_param string maxResults Maximum number of results to return
     * @return TableList
     */
    public function listTables($projectId, $datasetId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new TableList($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates information in an existing table, specified by tableId. (tables.update)
     *
     * @param string $projectId Project ID of the table to update
     * @param string $datasetId Dataset ID of the table to update
     * @param string $tableId Table ID of the table to update
     * @param Table $postBody
     * @return Table
     */
    public function update($projectId, $datasetId, $tableId, Table $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Table($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates information in an existing table, specified by tableId. This method supports patch
     * semantics. (tables.patch)
     *
     * @param string $projectId Project ID of the table to update
     * @param string $datasetId Dataset ID of the table to update
     * @param string $tableId Table ID of the table to update
     * @param Table $postBody
     * @return Table
     */
    public function patch($projectId, $datasetId, $tableId, Table $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Table($data);
      } else {
        return $data;
      }
    }
    /**
     * Deletes the table specified by tableId from the dataset. If the table contains data, all the data
     * will be deleted. (tables.delete)
     *
     * @param string $projectId Project ID of the table to delete
     * @param string $datasetId Dataset ID of the table to delete
     * @param string $tableId Table ID of the table to delete
     */
    public function delete($projectId, $datasetId, $tableId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

  /**
   * The "datasets" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bigqueryService = new apiBigqueryService(...);
   *   $datasets = $bigqueryService->datasets;
   *  </code>
   */
  class DatasetsServiceResource extends apiServiceResource {


    /**
     * Creates a new empty dataset. (datasets.insert)
     *
     * @param string $projectId Project ID of the new dataset
     * @param Dataset $postBody
     * @return Dataset
     */
    public function insert($projectId, Dataset $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Dataset($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns the dataset specified by datasetID. (datasets.get)
     *
     * @param string $projectId Project ID of the requested dataset
     * @param string $datasetId Dataset ID of the requested dataset
     * @return Dataset
     */
    public function get($projectId, $datasetId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Dataset($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all the datasets in the specified project to which the caller has read access; however, a
     * project owner can list (but not necessarily get) all datasets in his project. (datasets.list)
     *
     * @param string $projectId Project ID of the datasets to be listed
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken Page token, returned by a previous call, to request the next page of results
     * @opt_param string maxResults The maximum number of results to return
     * @return DatasetList
     */
    public function listDatasets($projectId, $optParams = array()) {
      $params = array('projectId' => $projectId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new DatasetList($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates information in an existing dataset, specified by datasetId. Properties not included in
     * the submitted resource will not be changed. If you include the access property without any values
     * assigned, the request will fail as you must specify at least one owner for a dataset.
     * (datasets.update)
     *
     * @param string $projectId Project ID of the dataset being updated
     * @param string $datasetId Dataset ID of the dataset being updated
     * @param Dataset $postBody
     * @return Dataset
     */
    public function update($projectId, $datasetId, Dataset $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Dataset($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates information in an existing dataset, specified by datasetId. Properties not included in
     * the submitted resource will not be changed. If you include the access property without any values
     * assigned, the request will fail as you must specify at least one owner for a dataset. This method
     * supports patch semantics. (datasets.patch)
     *
     * @param string $projectId Project ID of the dataset being updated
     * @param string $datasetId Dataset ID of the dataset being updated
     * @param Dataset $postBody
     * @return Dataset
     */
    public function patch($projectId, $datasetId, Dataset $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Dataset($data);
      } else {
        return $data;
      }
    }
    /**
     * Deletes the dataset specified by datasetId value. Before you can delete a dataset, you must
     * delete all its tables, either manually or by specifying deleteContents. Immediately after
     * deletion, you can create another dataset with the same name. (datasets.delete)
     *
     * @param string $projectId Project ID of the dataset being deleted
     * @param string $datasetId Dataset ID of dataset being deleted
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool deleteContents If True, delete all the tables in the dataset. If False and the dataset contains tables, the request will fail. Default is False
     */
    public function delete($projectId, $datasetId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

  /**
   * The "jobs" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bigqueryService = new apiBigqueryService(...);
   *   $jobs = $bigqueryService->jobs;
   *  </code>
   */
  class JobsServiceResource extends apiServiceResource {


    /**
     * Starts a new asynchronous job. (jobs.insert)
     *
     * @param string $projectId Project ID of the project that will be billed for the job
     * @param Job $postBody
     * @return Job
     */
    public function insert($projectId, Job $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Job($data);
      } else {
        return $data;
      }
    }
    /**
     * Runs a BigQuery SQL query synchronously and returns query results if the query completes within a
     * specified timeout. (jobs.query)
     *
     * @param string $projectId Project ID of the project billed for the query
     * @param QueryRequest $postBody
     * @return QueryResponse
     */
    public function query($projectId, QueryRequest $postBody, $optParams = array()) {
      $params = array('projectId' => $projectId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('query', array($params));
      if ($this->useObjects()) {
        return new QueryResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all the Jobs in the specified project that were started by the user. (jobs.list)
     *
     * @param string $projectId Project ID of the jobs to list
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string projection Restrict information returned to a set of selected fields
     * @opt_param string stateFilter Filter for job state
     * @opt_param bool allUsers Whether to display jobs owned by all users in the project. Default false
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string pageToken Page token, returned by a previous call, to request the next page of results
     * @return JobList
     */
    public function listJobs($projectId, $optParams = array()) {
      $params = array('projectId' => $projectId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new JobList($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the results of a query job. (jobs.getQueryResults)
     *
     * @param string $projectId Project ID of the query job
     * @param string $jobId Job ID of the query job
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string timeoutMs How long to wait for the query to complete, in milliseconds, before returning. Default is to return immediately. If the timeout passes before the job completes, the request will fail with a TIMEOUT error
     * @opt_param string startIndex Zero-based index of the starting row
     * @opt_param string maxResults Maximum number of results to read
     * @return GetQueryResultsResponse
     */
    public function getQueryResults($projectId, $jobId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'jobId' => $jobId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('getQueryResults', array($params));
      if ($this->useObjects()) {
        return new GetQueryResultsResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the specified job by ID. (jobs.get)
     *
     * @param string $projectId Project ID of the requested job
     * @param string $jobId Job ID of the requested job
     * @return Job
     */
    public function get($projectId, $jobId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'jobId' => $jobId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Job($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "tabledata" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bigqueryService = new apiBigqueryService(...);
   *   $tabledata = $bigqueryService->tabledata;
   *  </code>
   */
  class TabledataServiceResource extends apiServiceResource {


    /**
     * Retrieves table data from a specified set of rows. (tabledata.list)
     *
     * @param string $projectId Project ID of the table to read
     * @param string $datasetId Dataset ID of the table to read
     * @param string $tableId Table ID of the table to read
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string startIndex Zero-based index of the starting row to read
     * @opt_param string maxResults Maximum number of results to return
     * @return TableDataList
     */
    public function listTabledata($projectId, $datasetId, $tableId, $optParams = array()) {
      $params = array('projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new TableDataList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "projects" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bigqueryService = new apiBigqueryService(...);
   *   $projects = $bigqueryService->projects;
   *  </code>
   */
  class ProjectsServiceResource extends apiServiceResource {


    /**
     * Lists the projects to which you have at least read access. (projects.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken Page token, returned by a previous call, to request the next page of results
     * @opt_param string maxResults Maximum number of results to return
     * @return ProjectList
     */
    public function listProjects($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new ProjectList($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Bigquery (v2).
 *
 * <p>
 * A data platform for customers to create, manage, share and query data.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://code.google.com/apis/bigquery/docs/v2/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiBigqueryService extends apiService {
  public $tables;
  public $datasets;
  public $jobs;
  public $tabledata;
  public $projects;
  /**
   * Constructs the internal representation of the Bigquery service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/bigquery/v2/';
    $this->version = 'v2';
    $this->serviceName = 'bigquery';

    $apiClient->addService($this->serviceName, $this->version);
    $this->tables = new TablesServiceResource($this, $this->serviceName, 'tables', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Table"}, "id": "bigquery.tables.insert", "httpMethod": "POST", "path": "projects/{projectId}/datasets/{datasetId}/tables", "response": {"$ref": "Table"}}, "get": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "tableId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "id": "bigquery.tables.get", "httpMethod": "GET", "path": "projects/{projectId}/datasets/{datasetId}/tables/{tableId}", "response": {"$ref": "Table"}}, "list": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "datasetId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}, "projectId": {"required": true, "type": "string", "location": "path"}}, "id": "bigquery.tables.list", "httpMethod": "GET", "path": "projects/{projectId}/datasets/{datasetId}/tables", "response": {"$ref": "TableList"}}, "update": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "tableId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Table"}, "id": "bigquery.tables.update", "httpMethod": "PUT", "path": "projects/{projectId}/datasets/{datasetId}/tables/{tableId}", "response": {"$ref": "Table"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "tableId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Table"}, "id": "bigquery.tables.patch", "httpMethod": "PATCH", "path": "projects/{projectId}/datasets/{datasetId}/tables/{tableId}", "response": {"$ref": "Table"}}, "delete": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "tableId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "httpMethod": "DELETE", "path": "projects/{projectId}/datasets/{datasetId}/tables/{tableId}", "id": "bigquery.tables.delete"}}}', true));
    $this->datasets = new DatasetsServiceResource($this, $this->serviceName, 'datasets', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Dataset"}, "id": "bigquery.datasets.insert", "httpMethod": "POST", "path": "projects/{projectId}/datasets", "response": {"$ref": "Dataset"}}, "get": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "id": "bigquery.datasets.get", "httpMethod": "GET", "path": "projects/{projectId}/datasets/{datasetId}", "response": {"$ref": "Dataset"}}, "list": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}, "projectId": {"required": true, "type": "string", "location": "path"}}, "id": "bigquery.datasets.list", "httpMethod": "GET", "path": "projects/{projectId}/datasets", "response": {"$ref": "DatasetList"}}, "update": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Dataset"}, "id": "bigquery.datasets.update", "httpMethod": "PUT", "path": "projects/{projectId}/datasets/{datasetId}", "response": {"$ref": "Dataset"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Dataset"}, "id": "bigquery.datasets.patch", "httpMethod": "PATCH", "path": "projects/{projectId}/datasets/{datasetId}", "response": {"$ref": "Dataset"}}, "delete": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"deleteContents": {"type": "boolean", "location": "query"}, "datasetId": {"required": true, "type": "string", "location": "path"}, "projectId": {"required": true, "type": "string", "location": "path"}}, "httpMethod": "DELETE", "path": "projects/{projectId}/datasets/{datasetId}", "id": "bigquery.datasets.delete"}}}', true));
    $this->jobs = new JobsServiceResource($this, $this->serviceName, 'jobs', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}}, "mediaUpload": {"accept": ["application/octet-stream"], "protocols": {"simple": {"path": "/upload/bigquery/v2/projects/{projectId}/jobs", "multipart": true}, "resumable": {"path": "/resumable/upload/bigquery/v2/projects/{projectId}/jobs", "multipart": true}}}, "request": {"$ref": "Job"}, "id": "bigquery.jobs.insert", "httpMethod": "POST", "path": "projects/{projectId}/jobs", "response": {"$ref": "Job"}}, "get": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "jobId": {"required": true, "type": "string", "location": "path"}}, "id": "bigquery.jobs.get", "httpMethod": "GET", "path": "projects/{projectId}/jobs/{jobId}", "response": {"$ref": "Job"}}, "list": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projection": {"enum": ["full", "minimal"], "type": "string", "location": "query"}, "stateFilter": {"enum": ["done", "pending", "running"], "repeated": true, "location": "query", "type": "string"}, "projectId": {"required": true, "type": "string", "location": "path"}, "allUsers": {"type": "boolean", "location": "query"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "id": "bigquery.jobs.list", "httpMethod": "GET", "path": "projects/{projectId}/jobs", "response": {"$ref": "JobList"}}, "getQueryResults": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"timeoutMs": {"format": "uint32", "type": "integer", "location": "query"}, "projectId": {"required": true, "type": "string", "location": "path"}, "startIndex": {"format": "uint64", "type": "string", "location": "query"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}, "jobId": {"required": true, "type": "string", "location": "path"}}, "id": "bigquery.jobs.getQueryResults", "httpMethod": "GET", "path": "projects/{projectId}/queries/{jobId}", "response": {"$ref": "GetQueryResultsResponse"}}, "query": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "QueryRequest"}, "id": "bigquery.jobs.query", "httpMethod": "POST", "path": "projects/{projectId}/queries", "response": {"$ref": "QueryResponse"}}}}', true));
    $this->tabledata = new TabledataServiceResource($this, $this->serviceName, 'tabledata', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"projectId": {"required": true, "type": "string", "location": "path"}, "startIndex": {"format": "uint64", "type": "string", "location": "query"}, "tableId": {"required": true, "type": "string", "location": "path"}, "datasetId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}}, "id": "bigquery.tabledata.list", "httpMethod": "GET", "path": "projects/{projectId}/datasets/{datasetId}/tables/{tableId}/data", "response": {"$ref": "TableDataList"}}}}', true));
    $this->projects = new ProjectsServiceResource($this, $this->serviceName, 'projects', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/bigquery"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}}, "response": {"$ref": "ProjectList"}, "httpMethod": "GET", "path": "projects", "id": "bigquery.projects.list"}}}', true));

  }
}

class Dataset extends apiModel {
  public $kind;
  public $description;
  protected $__datasetReferenceType = 'DatasetReference';
  protected $__datasetReferenceDataType = '';
  public $datasetReference;
  public $creationTime;
  protected $__accessType = 'DatasetAccess';
  protected $__accessDataType = 'array';
  public $access;
  public $etag;
  public $friendlyName;
  public $lastModifiedTime;
  public $id;
  public $selfLink;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setDatasetReference(DatasetReference $datasetReference) {
    $this->datasetReference = $datasetReference;
  }
  public function getDatasetReference() {
    return $this->datasetReference;
  }
  public function setCreationTime($creationTime) {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime() {
    return $this->creationTime;
  }
  public function setAccess(/* array(DatasetAccess) */ $access) {
    $this->assertIsArray($access, 'DatasetAccess', __METHOD__);
    $this->access = $access;
  }
  public function getAccess() {
    return $this->access;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setFriendlyName($friendlyName) {
    $this->friendlyName = $friendlyName;
  }
  public function getFriendlyName() {
    return $this->friendlyName;
  }
  public function setLastModifiedTime($lastModifiedTime) {
    $this->lastModifiedTime = $lastModifiedTime;
  }
  public function getLastModifiedTime() {
    return $this->lastModifiedTime;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class DatasetAccess extends apiModel {
  public $specialGroup;
  public $domain;
  public $role;
  public $groupByEmail;
  public $userByEmail;
  public function setSpecialGroup($specialGroup) {
    $this->specialGroup = $specialGroup;
  }
  public function getSpecialGroup() {
    return $this->specialGroup;
  }
  public function setDomain($domain) {
    $this->domain = $domain;
  }
  public function getDomain() {
    return $this->domain;
  }
  public function setRole($role) {
    $this->role = $role;
  }
  public function getRole() {
    return $this->role;
  }
  public function setGroupByEmail($groupByEmail) {
    $this->groupByEmail = $groupByEmail;
  }
  public function getGroupByEmail() {
    return $this->groupByEmail;
  }
  public function setUserByEmail($userByEmail) {
    $this->userByEmail = $userByEmail;
  }
  public function getUserByEmail() {
    return $this->userByEmail;
  }
}

class DatasetList extends apiModel {
  public $nextPageToken;
  public $kind;
  protected $__datasetsType = 'DatasetListDatasets';
  protected $__datasetsDataType = 'array';
  public $datasets;
  public $etag;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setDatasets(/* array(DatasetListDatasets) */ $datasets) {
    $this->assertIsArray($datasets, 'DatasetListDatasets', __METHOD__);
    $this->datasets = $datasets;
  }
  public function getDatasets() {
    return $this->datasets;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
}

class DatasetListDatasets extends apiModel {
  public $friendlyName;
  public $kind;
  public $id;
  protected $__datasetReferenceType = 'DatasetReference';
  protected $__datasetReferenceDataType = '';
  public $datasetReference;
  public function setFriendlyName($friendlyName) {
    $this->friendlyName = $friendlyName;
  }
  public function getFriendlyName() {
    return $this->friendlyName;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setDatasetReference(DatasetReference $datasetReference) {
    $this->datasetReference = $datasetReference;
  }
  public function getDatasetReference() {
    return $this->datasetReference;
  }
}

class DatasetReference extends apiModel {
  public $projectId;
  public $datasetId;
  public function setProjectId($projectId) {
    $this->projectId = $projectId;
  }
  public function getProjectId() {
    return $this->projectId;
  }
  public function setDatasetId($datasetId) {
    $this->datasetId = $datasetId;
  }
  public function getDatasetId() {
    return $this->datasetId;
  }
}

class ErrorProto extends apiModel {
  public $debugInfo;
  public $message;
  public $reason;
  public $location;
  public function setDebugInfo($debugInfo) {
    $this->debugInfo = $debugInfo;
  }
  public function getDebugInfo() {
    return $this->debugInfo;
  }
  public function setMessage($message) {
    $this->message = $message;
  }
  public function getMessage() {
    return $this->message;
  }
  public function setReason($reason) {
    $this->reason = $reason;
  }
  public function getReason() {
    return $this->reason;
  }
  public function setLocation($location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
}

class GetQueryResultsResponse extends apiModel {
  public $kind;
  protected $__rowsType = 'TableRow';
  protected $__rowsDataType = 'array';
  public $rows;
  protected $__jobReferenceType = 'JobReference';
  protected $__jobReferenceDataType = '';
  public $jobReference;
  public $jobComplete;
  public $totalRows;
  public $etag;
  protected $__schemaType = 'TableSchema';
  protected $__schemaDataType = '';
  public $schema;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRows(/* array(TableRow) */ $rows) {
    $this->assertIsArray($rows, 'TableRow', __METHOD__);
    $this->rows = $rows;
  }
  public function getRows() {
    return $this->rows;
  }
  public function setJobReference(JobReference $jobReference) {
    $this->jobReference = $jobReference;
  }
  public function getJobReference() {
    return $this->jobReference;
  }
  public function setJobComplete($jobComplete) {
    $this->jobComplete = $jobComplete;
  }
  public function getJobComplete() {
    return $this->jobComplete;
  }
  public function setTotalRows($totalRows) {
    $this->totalRows = $totalRows;
  }
  public function getTotalRows() {
    return $this->totalRows;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setSchema(TableSchema $schema) {
    $this->schema = $schema;
  }
  public function getSchema() {
    return $this->schema;
  }
}

class Job extends apiModel {
  protected $__statusType = 'JobStatus';
  protected $__statusDataType = '';
  public $status;
  public $kind;
  protected $__statisticsType = 'JobStatistics';
  protected $__statisticsDataType = '';
  public $statistics;
  protected $__jobReferenceType = 'JobReference';
  protected $__jobReferenceDataType = '';
  public $jobReference;
  public $etag;
  protected $__configurationType = 'JobConfiguration';
  protected $__configurationDataType = '';
  public $configuration;
  public $id;
  public $selfLink;
  public function setStatus(JobStatus $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setStatistics(JobStatistics $statistics) {
    $this->statistics = $statistics;
  }
  public function getStatistics() {
    return $this->statistics;
  }
  public function setJobReference(JobReference $jobReference) {
    $this->jobReference = $jobReference;
  }
  public function getJobReference() {
    return $this->jobReference;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setConfiguration(JobConfiguration $configuration) {
    $this->configuration = $configuration;
  }
  public function getConfiguration() {
    return $this->configuration;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class JobConfiguration extends apiModel {
  protected $__loadType = 'JobConfigurationLoad';
  protected $__loadDataType = '';
  public $load;
  protected $__linkType = 'JobConfigurationLink';
  protected $__linkDataType = '';
  public $link;
  protected $__queryType = 'JobConfigurationQuery';
  protected $__queryDataType = '';
  public $query;
  protected $__copyType = 'JobConfigurationTableCopy';
  protected $__copyDataType = '';
  public $copy;
  protected $__extractType = 'JobConfigurationExtract';
  protected $__extractDataType = '';
  public $extract;
  public $properties;
  public function setLoad(JobConfigurationLoad $load) {
    $this->load = $load;
  }
  public function getLoad() {
    return $this->load;
  }
  public function setLink(JobConfigurationLink $link) {
    $this->link = $link;
  }
  public function getLink() {
    return $this->link;
  }
  public function setQuery(JobConfigurationQuery $query) {
    $this->query = $query;
  }
  public function getQuery() {
    return $this->query;
  }
  public function setCopy(JobConfigurationTableCopy $copy) {
    $this->copy = $copy;
  }
  public function getCopy() {
    return $this->copy;
  }
  public function setExtract(JobConfigurationExtract $extract) {
    $this->extract = $extract;
  }
  public function getExtract() {
    return $this->extract;
  }
  public function setProperties($properties) {
    $this->properties = $properties;
  }
  public function getProperties() {
    return $this->properties;
  }
}

class JobConfigurationExtract extends apiModel {
  public $destinationUri;
  protected $__sourceTableType = 'TableReference';
  protected $__sourceTableDataType = '';
  public $sourceTable;
  public function setDestinationUri($destinationUri) {
    $this->destinationUri = $destinationUri;
  }
  public function getDestinationUri() {
    return $this->destinationUri;
  }
  public function setSourceTable(TableReference $sourceTable) {
    $this->sourceTable = $sourceTable;
  }
  public function getSourceTable() {
    return $this->sourceTable;
  }
}

class JobConfigurationLink extends apiModel {
  public $createDisposition;
  public $writeDisposition;
  protected $__destinationTableType = 'TableReference';
  protected $__destinationTableDataType = '';
  public $destinationTable;
  public $sourceUri;
  public function setCreateDisposition($createDisposition) {
    $this->createDisposition = $createDisposition;
  }
  public function getCreateDisposition() {
    return $this->createDisposition;
  }
  public function setWriteDisposition($writeDisposition) {
    $this->writeDisposition = $writeDisposition;
  }
  public function getWriteDisposition() {
    return $this->writeDisposition;
  }
  public function setDestinationTable(TableReference $destinationTable) {
    $this->destinationTable = $destinationTable;
  }
  public function getDestinationTable() {
    return $this->destinationTable;
  }
  public function setSourceUri(/* array(string) */ $sourceUri) {
    $this->assertIsArray($sourceUri, 'string', __METHOD__);
    $this->sourceUri = $sourceUri;
  }
  public function getSourceUri() {
    return $this->sourceUri;
  }
}

class JobConfigurationLoad extends apiModel {
  public $encoding;
  public $fieldDelimiter;
  protected $__destinationTableType = 'TableReference';
  protected $__destinationTableDataType = '';
  public $destinationTable;
  public $maxBadRecords;
  public $writeDisposition;
  public $sourceUris;
  public $skipLeadingRows;
  public $createDisposition;
  protected $__schemaType = 'TableSchema';
  protected $__schemaDataType = '';
  public $schema;
  public function setEncoding($encoding) {
    $this->encoding = $encoding;
  }
  public function getEncoding() {
    return $this->encoding;
  }
  public function setFieldDelimiter($fieldDelimiter) {
    $this->fieldDelimiter = $fieldDelimiter;
  }
  public function getFieldDelimiter() {
    return $this->fieldDelimiter;
  }
  public function setDestinationTable(TableReference $destinationTable) {
    $this->destinationTable = $destinationTable;
  }
  public function getDestinationTable() {
    return $this->destinationTable;
  }
  public function setMaxBadRecords($maxBadRecords) {
    $this->maxBadRecords = $maxBadRecords;
  }
  public function getMaxBadRecords() {
    return $this->maxBadRecords;
  }
  public function setWriteDisposition($writeDisposition) {
    $this->writeDisposition = $writeDisposition;
  }
  public function getWriteDisposition() {
    return $this->writeDisposition;
  }
  public function setSourceUris(/* array(string) */ $sourceUris) {
    $this->assertIsArray($sourceUris, 'string', __METHOD__);
    $this->sourceUris = $sourceUris;
  }
  public function getSourceUris() {
    return $this->sourceUris;
  }
  public function setSkipLeadingRows($skipLeadingRows) {
    $this->skipLeadingRows = $skipLeadingRows;
  }
  public function getSkipLeadingRows() {
    return $this->skipLeadingRows;
  }
  public function setCreateDisposition($createDisposition) {
    $this->createDisposition = $createDisposition;
  }
  public function getCreateDisposition() {
    return $this->createDisposition;
  }
  public function setSchema(TableSchema $schema) {
    $this->schema = $schema;
  }
  public function getSchema() {
    return $this->schema;
  }
}

class JobConfigurationQuery extends apiModel {
  public $createDisposition;
  public $query;
  public $writeDisposition;
  protected $__destinationTableType = 'TableReference';
  protected $__destinationTableDataType = '';
  public $destinationTable;
  protected $__defaultDatasetType = 'DatasetReference';
  protected $__defaultDatasetDataType = '';
  public $defaultDataset;
  public function setCreateDisposition($createDisposition) {
    $this->createDisposition = $createDisposition;
  }
  public function getCreateDisposition() {
    return $this->createDisposition;
  }
  public function setQuery($query) {
    $this->query = $query;
  }
  public function getQuery() {
    return $this->query;
  }
  public function setWriteDisposition($writeDisposition) {
    $this->writeDisposition = $writeDisposition;
  }
  public function getWriteDisposition() {
    return $this->writeDisposition;
  }
  public function setDestinationTable(TableReference $destinationTable) {
    $this->destinationTable = $destinationTable;
  }
  public function getDestinationTable() {
    return $this->destinationTable;
  }
  public function setDefaultDataset(DatasetReference $defaultDataset) {
    $this->defaultDataset = $defaultDataset;
  }
  public function getDefaultDataset() {
    return $this->defaultDataset;
  }
}

class JobConfigurationTableCopy extends apiModel {
  public $createDisposition;
  public $writeDisposition;
  protected $__destinationTableType = 'TableReference';
  protected $__destinationTableDataType = '';
  public $destinationTable;
  protected $__sourceTableType = 'TableReference';
  protected $__sourceTableDataType = '';
  public $sourceTable;
  public function setCreateDisposition($createDisposition) {
    $this->createDisposition = $createDisposition;
  }
  public function getCreateDisposition() {
    return $this->createDisposition;
  }
  public function setWriteDisposition($writeDisposition) {
    $this->writeDisposition = $writeDisposition;
  }
  public function getWriteDisposition() {
    return $this->writeDisposition;
  }
  public function setDestinationTable(TableReference $destinationTable) {
    $this->destinationTable = $destinationTable;
  }
  public function getDestinationTable() {
    return $this->destinationTable;
  }
  public function setSourceTable(TableReference $sourceTable) {
    $this->sourceTable = $sourceTable;
  }
  public function getSourceTable() {
    return $this->sourceTable;
  }
}

class JobList extends apiModel {
  public $nextPageToken;
  public $totalItems;
  public $kind;
  public $etag;
  protected $__jobsType = 'JobListJobs';
  protected $__jobsDataType = 'array';
  public $jobs;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setJobs(/* array(JobListJobs) */ $jobs) {
    $this->assertIsArray($jobs, 'JobListJobs', __METHOD__);
    $this->jobs = $jobs;
  }
  public function getJobs() {
    return $this->jobs;
  }
}

class JobListJobs extends apiModel {
  protected $__statusType = 'JobStatus';
  protected $__statusDataType = '';
  public $status;
  public $kind;
  protected $__statisticsType = 'JobStatistics';
  protected $__statisticsDataType = '';
  public $statistics;
  protected $__jobReferenceType = 'JobReference';
  protected $__jobReferenceDataType = '';
  public $jobReference;
  public $state;
  protected $__configurationType = 'JobConfiguration';
  protected $__configurationDataType = '';
  public $configuration;
  public $id;
  protected $__errorResultType = 'ErrorProto';
  protected $__errorResultDataType = '';
  public $errorResult;
  public function setStatus(JobStatus $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setStatistics(JobStatistics $statistics) {
    $this->statistics = $statistics;
  }
  public function getStatistics() {
    return $this->statistics;
  }
  public function setJobReference(JobReference $jobReference) {
    $this->jobReference = $jobReference;
  }
  public function getJobReference() {
    return $this->jobReference;
  }
  public function setState($state) {
    $this->state = $state;
  }
  public function getState() {
    return $this->state;
  }
  public function setConfiguration(JobConfiguration $configuration) {
    $this->configuration = $configuration;
  }
  public function getConfiguration() {
    return $this->configuration;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setErrorResult(ErrorProto $errorResult) {
    $this->errorResult = $errorResult;
  }
  public function getErrorResult() {
    return $this->errorResult;
  }
}

class JobReference extends apiModel {
  public $projectId;
  public $jobId;
  public function setProjectId($projectId) {
    $this->projectId = $projectId;
  }
  public function getProjectId() {
    return $this->projectId;
  }
  public function setJobId($jobId) {
    $this->jobId = $jobId;
  }
  public function getJobId() {
    return $this->jobId;
  }
}

class JobStatistics extends apiModel {
  public $endTime;
  public $totalBytesProcessed;
  public $startTime;
  public function setEndTime($endTime) {
    $this->endTime = $endTime;
  }
  public function getEndTime() {
    return $this->endTime;
  }
  public function setTotalBytesProcessed($totalBytesProcessed) {
    $this->totalBytesProcessed = $totalBytesProcessed;
  }
  public function getTotalBytesProcessed() {
    return $this->totalBytesProcessed;
  }
  public function setStartTime($startTime) {
    $this->startTime = $startTime;
  }
  public function getStartTime() {
    return $this->startTime;
  }
}

class JobStatus extends apiModel {
  public $state;
  protected $__errorsType = 'ErrorProto';
  protected $__errorsDataType = 'array';
  public $errors;
  protected $__errorResultType = 'ErrorProto';
  protected $__errorResultDataType = '';
  public $errorResult;
  public function setState($state) {
    $this->state = $state;
  }
  public function getState() {
    return $this->state;
  }
  public function setErrors(/* array(ErrorProto) */ $errors) {
    $this->assertIsArray($errors, 'ErrorProto', __METHOD__);
    $this->errors = $errors;
  }
  public function getErrors() {
    return $this->errors;
  }
  public function setErrorResult(ErrorProto $errorResult) {
    $this->errorResult = $errorResult;
  }
  public function getErrorResult() {
    return $this->errorResult;
  }
}

class ProjectList extends apiModel {
  public $nextPageToken;
  public $totalItems;
  public $kind;
  public $etag;
  protected $__projectsType = 'ProjectListProjects';
  protected $__projectsDataType = 'array';
  public $projects;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setProjects(/* array(ProjectListProjects) */ $projects) {
    $this->assertIsArray($projects, 'ProjectListProjects', __METHOD__);
    $this->projects = $projects;
  }
  public function getProjects() {
    return $this->projects;
  }
}

class ProjectListProjects extends apiModel {
  public $friendlyName;
  public $kind;
  public $id;
  protected $__projectReferenceType = 'ProjectReference';
  protected $__projectReferenceDataType = '';
  public $projectReference;
  public function setFriendlyName($friendlyName) {
    $this->friendlyName = $friendlyName;
  }
  public function getFriendlyName() {
    return $this->friendlyName;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setProjectReference(ProjectReference $projectReference) {
    $this->projectReference = $projectReference;
  }
  public function getProjectReference() {
    return $this->projectReference;
  }
}

class ProjectReference extends apiModel {
  public $projectId;
  public function setProjectId($projectId) {
    $this->projectId = $projectId;
  }
  public function getProjectId() {
    return $this->projectId;
  }
}

class QueryRequest extends apiModel {
  public $timeoutMs;
  public $query;
  public $kind;
  public $maxResults;
  protected $__defaultDatasetType = 'DatasetReference';
  protected $__defaultDatasetDataType = '';
  public $defaultDataset;
  public function setTimeoutMs($timeoutMs) {
    $this->timeoutMs = $timeoutMs;
  }
  public function getTimeoutMs() {
    return $this->timeoutMs;
  }
  public function setQuery($query) {
    $this->query = $query;
  }
  public function getQuery() {
    return $this->query;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMaxResults($maxResults) {
    $this->maxResults = $maxResults;
  }
  public function getMaxResults() {
    return $this->maxResults;
  }
  public function setDefaultDataset(DatasetReference $defaultDataset) {
    $this->defaultDataset = $defaultDataset;
  }
  public function getDefaultDataset() {
    return $this->defaultDataset;
  }
}

class QueryResponse extends apiModel {
  public $kind;
  protected $__rowsType = 'TableRow';
  protected $__rowsDataType = 'array';
  public $rows;
  protected $__jobReferenceType = 'JobReference';
  protected $__jobReferenceDataType = '';
  public $jobReference;
  public $jobComplete;
  public $totalRows;
  protected $__schemaType = 'TableSchema';
  protected $__schemaDataType = '';
  public $schema;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRows(/* array(TableRow) */ $rows) {
    $this->assertIsArray($rows, 'TableRow', __METHOD__);
    $this->rows = $rows;
  }
  public function getRows() {
    return $this->rows;
  }
  public function setJobReference(JobReference $jobReference) {
    $this->jobReference = $jobReference;
  }
  public function getJobReference() {
    return $this->jobReference;
  }
  public function setJobComplete($jobComplete) {
    $this->jobComplete = $jobComplete;
  }
  public function getJobComplete() {
    return $this->jobComplete;
  }
  public function setTotalRows($totalRows) {
    $this->totalRows = $totalRows;
  }
  public function getTotalRows() {
    return $this->totalRows;
  }
  public function setSchema(TableSchema $schema) {
    $this->schema = $schema;
  }
  public function getSchema() {
    return $this->schema;
  }
}

class Table extends apiModel {
  public $kind;
  public $description;
  public $creationTime;
  protected $__tableReferenceType = 'TableReference';
  protected $__tableReferenceDataType = '';
  public $tableReference;
  public $numRows;
  public $numBytes;
  public $etag;
  public $friendlyName;
  public $lastModifiedTime;
  public $id;
  public $selfLink;
  protected $__schemaType = 'TableSchema';
  protected $__schemaDataType = '';
  public $schema;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setCreationTime($creationTime) {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime() {
    return $this->creationTime;
  }
  public function setTableReference(TableReference $tableReference) {
    $this->tableReference = $tableReference;
  }
  public function getTableReference() {
    return $this->tableReference;
  }
  public function setNumRows($numRows) {
    $this->numRows = $numRows;
  }
  public function getNumRows() {
    return $this->numRows;
  }
  public function setNumBytes($numBytes) {
    $this->numBytes = $numBytes;
  }
  public function getNumBytes() {
    return $this->numBytes;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setFriendlyName($friendlyName) {
    $this->friendlyName = $friendlyName;
  }
  public function getFriendlyName() {
    return $this->friendlyName;
  }
  public function setLastModifiedTime($lastModifiedTime) {
    $this->lastModifiedTime = $lastModifiedTime;
  }
  public function getLastModifiedTime() {
    return $this->lastModifiedTime;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setSchema(TableSchema $schema) {
    $this->schema = $schema;
  }
  public function getSchema() {
    return $this->schema;
  }
}

class TableDataList extends apiModel {
  protected $__rowsType = 'TableRow';
  protected $__rowsDataType = 'array';
  public $rows;
  public $kind;
  public $etag;
  public $totalRows;
  public function setRows(/* array(TableRow) */ $rows) {
    $this->assertIsArray($rows, 'TableRow', __METHOD__);
    $this->rows = $rows;
  }
  public function getRows() {
    return $this->rows;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setTotalRows($totalRows) {
    $this->totalRows = $totalRows;
  }
  public function getTotalRows() {
    return $this->totalRows;
  }
}

class TableFieldSchema extends apiModel {
  protected $__fieldsType = 'TableFieldSchema';
  protected $__fieldsDataType = 'array';
  public $fields;
  public $type;
  public $mode;
  public $name;
  public function setFields(/* array(TableFieldSchema) */ $fields) {
    $this->assertIsArray($fields, 'TableFieldSchema', __METHOD__);
    $this->fields = $fields;
  }
  public function getFields() {
    return $this->fields;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setMode($mode) {
    $this->mode = $mode;
  }
  public function getMode() {
    return $this->mode;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class TableList extends apiModel {
  public $nextPageToken;
  protected $__tablesType = 'TableListTables';
  protected $__tablesDataType = 'array';
  public $tables;
  public $kind;
  public $etag;
  public $totalItems;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setTables(/* array(TableListTables) */ $tables) {
    $this->assertIsArray($tables, 'TableListTables', __METHOD__);
    $this->tables = $tables;
  }
  public function getTables() {
    return $this->tables;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class TableListTables extends apiModel {
  public $friendlyName;
  public $kind;
  public $id;
  protected $__tableReferenceType = 'TableReference';
  protected $__tableReferenceDataType = '';
  public $tableReference;
  public function setFriendlyName($friendlyName) {
    $this->friendlyName = $friendlyName;
  }
  public function getFriendlyName() {
    return $this->friendlyName;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setTableReference(TableReference $tableReference) {
    $this->tableReference = $tableReference;
  }
  public function getTableReference() {
    return $this->tableReference;
  }
}

class TableReference extends apiModel {
  public $projectId;
  public $tableId;
  public $datasetId;
  public function setProjectId($projectId) {
    $this->projectId = $projectId;
  }
  public function getProjectId() {
    return $this->projectId;
  }
  public function setTableId($tableId) {
    $this->tableId = $tableId;
  }
  public function getTableId() {
    return $this->tableId;
  }
  public function setDatasetId($datasetId) {
    $this->datasetId = $datasetId;
  }
  public function getDatasetId() {
    return $this->datasetId;
  }
}

class TableRow extends apiModel {
  protected $__fType = 'TableRowF';
  protected $__fDataType = 'array';
  public $f;
  public function setF(/* array(TableRowF) */ $f) {
    $this->assertIsArray($f, 'TableRowF', __METHOD__);
    $this->f = $f;
  }
  public function getF() {
    return $this->f;
  }
}

class TableRowF extends apiModel {
  public $v;
  public function setV($v) {
    $this->v = $v;
  }
  public function getV() {
    return $this->v;
  }
}

class TableSchema extends apiModel {
  protected $__fieldsType = 'TableFieldSchema';
  protected $__fieldsDataType = 'array';
  public $fields;
  public function setFields(/* array(TableFieldSchema) */ $fields) {
    $this->assertIsArray($fields, 'TableFieldSchema', __METHOD__);
    $this->fields = $fields;
  }
  public function getFields() {
    return $this->fields;
  }
}
