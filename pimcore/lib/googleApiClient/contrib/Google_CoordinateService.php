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
   * The "customFieldDef" collection of methods.
   * Typical usage is:
   *  <code>
   *   $coordinateService = new Google_CoordinateService(...);
   *   $customFieldDef = $coordinateService->customFieldDef;
   *  </code>
   */
  class Google_CustomFieldDefServiceResource extends Google_ServiceResource {

    /**
     * Retrieves a list of custom field definitions for a team. (customFieldDef.list)
     *
     * @param string $teamId Team ID
     * @param array $optParams Optional parameters.
     * @return Google_CustomFieldDefListResponse
     */
    public function listCustomFieldDef($teamId, $optParams = array()) {
      $params = array('teamId' => $teamId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CustomFieldDefListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "jobs" collection of methods.
   * Typical usage is:
   *  <code>
   *   $coordinateService = new Google_CoordinateService(...);
   *   $jobs = $coordinateService->jobs;
   *  </code>
   */
  class Google_JobsServiceResource extends Google_ServiceResource {

    /**
     * Retrieves a job, including all the changes made to the job. (jobs.get)
     *
     * @param string $teamId Team ID
     * @param string $jobId Job number
     * @param array $optParams Optional parameters.
     * @return Google_Job
     */
    public function get($teamId, $jobId, $optParams = array()) {
      $params = array('teamId' => $teamId, 'jobId' => $jobId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Job($data);
      } else {
        return $data;
      }
    }
    /**
     * Inserts a new job. Only the state field of the job should be set. (jobs.insert)
     *
     * @param string $teamId Team ID
     * @param string $address Job address as newline (Unix) separated string
     * @param double $lat The latitude coordinate of this job's location.
     * @param double $lng The longitude coordinate of this job's location.
     * @param string $title Job title
     * @param Google_Job $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string assignee Assignee email address, or empty string to unassign.
     * @opt_param string customField Map from custom field id (from /team//custom_fields) to the field value. For example '123=Alice'
     * @opt_param string customerName Customer name
     * @opt_param string customerPhoneNumber Customer phone number
     * @opt_param string note Job note as newline (Unix) separated string
     * @return Google_Job
     */
    public function insert($teamId, $address, $lat, $lng, $title, Google_Job $postBody, $optParams = array()) {
      $params = array('teamId' => $teamId, 'address' => $address, 'lat' => $lat, 'lng' => $lng, 'title' => $title, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Job($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves jobs created or modified since the given timestamp. (jobs.list)
     *
     * @param string $teamId Team ID
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults Maximum number of results to return in one page.
     * @opt_param string minModifiedTimestampMs Minimum time a job was modified in milliseconds since epoch.
     * @opt_param string pageToken Continuation token
     * @return Google_JobListResponse
     */
    public function listJobs($teamId, $optParams = array()) {
      $params = array('teamId' => $teamId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_JobListResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a job. Fields that are set in the job state will be updated. This method supports patch
     * semantics. (jobs.patch)
     *
     * @param string $teamId Team ID
     * @param string $jobId Job number
     * @param Google_Job $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string address Job address as newline (Unix) separated string
     * @opt_param string assignee Assignee email address, or empty string to unassign.
     * @opt_param string customField Map from custom field id (from /team//custom_fields) to the field value. For example '123=Alice'
     * @opt_param string customerName Customer name
     * @opt_param string customerPhoneNumber Customer phone number
     * @opt_param double lat The latitude coordinate of this job's location.
     * @opt_param double lng The longitude coordinate of this job's location.
     * @opt_param string note Job note as newline (Unix) separated string
     * @opt_param string progress Job progress
     * @opt_param string title Job title
     * @return Google_Job
     */
    public function patch($teamId, $jobId, Google_Job $postBody, $optParams = array()) {
      $params = array('teamId' => $teamId, 'jobId' => $jobId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_Job($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a job. Fields that are set in the job state will be updated. (jobs.update)
     *
     * @param string $teamId Team ID
     * @param string $jobId Job number
     * @param Google_Job $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string address Job address as newline (Unix) separated string
     * @opt_param string assignee Assignee email address, or empty string to unassign.
     * @opt_param string customField Map from custom field id (from /team//custom_fields) to the field value. For example '123=Alice'
     * @opt_param string customerName Customer name
     * @opt_param string customerPhoneNumber Customer phone number
     * @opt_param double lat The latitude coordinate of this job's location.
     * @opt_param double lng The longitude coordinate of this job's location.
     * @opt_param string note Job note as newline (Unix) separated string
     * @opt_param string progress Job progress
     * @opt_param string title Job title
     * @return Google_Job
     */
    public function update($teamId, $jobId, Google_Job $postBody, $optParams = array()) {
      $params = array('teamId' => $teamId, 'jobId' => $jobId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_Job($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "location" collection of methods.
   * Typical usage is:
   *  <code>
   *   $coordinateService = new Google_CoordinateService(...);
   *   $location = $coordinateService->location;
   *  </code>
   */
  class Google_LocationServiceResource extends Google_ServiceResource {

    /**
     * Retrieves a list of locations for a worker. (location.list)
     *
     * @param string $teamId Team ID
     * @param string $workerEmail Worker email address.
     * @param string $startTimestampMs Start timestamp in milliseconds since the epoch.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults Maximum number of results to return in one page.
     * @opt_param string pageToken Continuation token
     * @return Google_LocationListResponse
     */
    public function listLocation($teamId, $workerEmail, $startTimestampMs, $optParams = array()) {
      $params = array('teamId' => $teamId, 'workerEmail' => $workerEmail, 'startTimestampMs' => $startTimestampMs);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_LocationListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "schedule" collection of methods.
   * Typical usage is:
   *  <code>
   *   $coordinateService = new Google_CoordinateService(...);
   *   $schedule = $coordinateService->schedule;
   *  </code>
   */
  class Google_ScheduleServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the schedule for a job. (schedule.get)
     *
     * @param string $teamId Team ID
     * @param string $jobId Job number
     * @param array $optParams Optional parameters.
     * @return Google_Schedule
     */
    public function get($teamId, $jobId, $optParams = array()) {
      $params = array('teamId' => $teamId, 'jobId' => $jobId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Schedule($data);
      } else {
        return $data;
      }
    }
    /**
     * Replaces the schedule of a job with the provided schedule. This method supports patch semantics.
     * (schedule.patch)
     *
     * @param string $teamId Team ID
     * @param string $jobId Job number
     * @param Google_Schedule $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool allDay Whether the job is scheduled for the whole day. Time of day in start/end times is ignored if this is true.
     * @opt_param string duration Job duration in milliseconds.
     * @opt_param string endTime Scheduled end time in milliseconds since epoch.
     * @opt_param string startTime Scheduled start time in milliseconds since epoch.
     * @return Google_Schedule
     */
    public function patch($teamId, $jobId, Google_Schedule $postBody, $optParams = array()) {
      $params = array('teamId' => $teamId, 'jobId' => $jobId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_Schedule($data);
      } else {
        return $data;
      }
    }
    /**
     * Replaces the schedule of a job with the provided schedule. (schedule.update)
     *
     * @param string $teamId Team ID
     * @param string $jobId Job number
     * @param Google_Schedule $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool allDay Whether the job is scheduled for the whole day. Time of day in start/end times is ignored if this is true.
     * @opt_param string duration Job duration in milliseconds.
     * @opt_param string endTime Scheduled end time in milliseconds since epoch.
     * @opt_param string startTime Scheduled start time in milliseconds since epoch.
     * @return Google_Schedule
     */
    public function update($teamId, $jobId, Google_Schedule $postBody, $optParams = array()) {
      $params = array('teamId' => $teamId, 'jobId' => $jobId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_Schedule($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "worker" collection of methods.
   * Typical usage is:
   *  <code>
   *   $coordinateService = new Google_CoordinateService(...);
   *   $worker = $coordinateService->worker;
   *  </code>
   */
  class Google_WorkerServiceResource extends Google_ServiceResource {

    /**
     * Retrieves a list of workers in a team. (worker.list)
     *
     * @param string $teamId Team ID
     * @param array $optParams Optional parameters.
     * @return Google_WorkerListResponse
     */
    public function listWorker($teamId, $optParams = array()) {
      $params = array('teamId' => $teamId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_WorkerListResponse($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Coordinate (v1).
 *
 * <p>
 * Lets you view and manage jobs in a Coordinate team.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/coordinate/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_CoordinateService extends Google_Service {
  public $customFieldDef;
  public $jobs;
  public $location;
  public $schedule;
  public $worker;
  /**
   * Constructs the internal representation of the Coordinate service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'coordinate/v1/teams/';
    $this->version = 'v1';
    $this->serviceName = 'coordinate';

    $client->addService($this->serviceName, $this->version);
    $this->customFieldDef = new Google_CustomFieldDefServiceResource($this, $this->serviceName, 'customFieldDef', json_decode('{"methods": {"list": {"id": "coordinate.customFieldDef.list", "path": "{teamId}/custom_fields", "httpMethod": "GET", "parameters": {"teamId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CustomFieldDefListResponse"}, "scopes": ["https://www.googleapis.com/auth/coordinate", "https://www.googleapis.com/auth/coordinate.readonly"]}}}', true));
    $this->jobs = new Google_JobsServiceResource($this, $this->serviceName, 'jobs', json_decode('{"methods": {"get": {"id": "coordinate.jobs.get", "path": "{teamId}/jobs/{jobId}", "httpMethod": "GET", "parameters": {"jobId": {"type": "string", "required": true, "format": "uint64", "location": "path"}, "teamId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Job"}, "scopes": ["https://www.googleapis.com/auth/coordinate", "https://www.googleapis.com/auth/coordinate.readonly"]}, "insert": {"id": "coordinate.jobs.insert", "path": "{teamId}/jobs", "httpMethod": "POST", "parameters": {"address": {"type": "string", "required": true, "location": "query"}, "assignee": {"type": "string", "location": "query"}, "customField": {"type": "string", "repeated": true, "location": "query"}, "customerName": {"type": "string", "location": "query"}, "customerPhoneNumber": {"type": "string", "location": "query"}, "lat": {"type": "number", "required": true, "format": "double", "location": "query"}, "lng": {"type": "number", "required": true, "format": "double", "location": "query"}, "note": {"type": "string", "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}, "title": {"type": "string", "required": true, "location": "query"}}, "request": {"$ref": "Job"}, "response": {"$ref": "Job"}, "scopes": ["https://www.googleapis.com/auth/coordinate"]}, "list": {"id": "coordinate.jobs.list", "path": "{teamId}/jobs", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "format": "uint32", "location": "query"}, "minModifiedTimestampMs": {"type": "string", "format": "uint64", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "JobListResponse"}, "scopes": ["https://www.googleapis.com/auth/coordinate", "https://www.googleapis.com/auth/coordinate.readonly"]}, "patch": {"id": "coordinate.jobs.patch", "path": "{teamId}/jobs/{jobId}", "httpMethod": "PATCH", "parameters": {"address": {"type": "string", "location": "query"}, "assignee": {"type": "string", "location": "query"}, "customField": {"type": "string", "repeated": true, "location": "query"}, "customerName": {"type": "string", "location": "query"}, "customerPhoneNumber": {"type": "string", "location": "query"}, "jobId": {"type": "string", "required": true, "format": "uint64", "location": "path"}, "lat": {"type": "number", "format": "double", "location": "query"}, "lng": {"type": "number", "format": "double", "location": "query"}, "note": {"type": "string", "location": "query"}, "progress": {"type": "string", "enum": ["COMPLETED", "IN_PROGRESS", "NOT_ACCEPTED", "NOT_STARTED", "OBSOLETE"], "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}, "title": {"type": "string", "location": "query"}}, "request": {"$ref": "Job"}, "response": {"$ref": "Job"}, "scopes": ["https://www.googleapis.com/auth/coordinate"]}, "update": {"id": "coordinate.jobs.update", "path": "{teamId}/jobs/{jobId}", "httpMethod": "PUT", "parameters": {"address": {"type": "string", "location": "query"}, "assignee": {"type": "string", "location": "query"}, "customField": {"type": "string", "repeated": true, "location": "query"}, "customerName": {"type": "string", "location": "query"}, "customerPhoneNumber": {"type": "string", "location": "query"}, "jobId": {"type": "string", "required": true, "format": "uint64", "location": "path"}, "lat": {"type": "number", "format": "double", "location": "query"}, "lng": {"type": "number", "format": "double", "location": "query"}, "note": {"type": "string", "location": "query"}, "progress": {"type": "string", "enum": ["COMPLETED", "IN_PROGRESS", "NOT_ACCEPTED", "NOT_STARTED", "OBSOLETE"], "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}, "title": {"type": "string", "location": "query"}}, "request": {"$ref": "Job"}, "response": {"$ref": "Job"}, "scopes": ["https://www.googleapis.com/auth/coordinate"]}}}', true));
    $this->location = new Google_LocationServiceResource($this, $this->serviceName, 'location', json_decode('{"methods": {"list": {"id": "coordinate.location.list", "path": "{teamId}/workers/{workerEmail}/locations", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "format": "uint32", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "startTimestampMs": {"type": "string", "required": true, "format": "uint64", "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}, "workerEmail": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "LocationListResponse"}, "scopes": ["https://www.googleapis.com/auth/coordinate", "https://www.googleapis.com/auth/coordinate.readonly"]}}}', true));
    $this->schedule = new Google_ScheduleServiceResource($this, $this->serviceName, 'schedule', json_decode('{"methods": {"get": {"id": "coordinate.schedule.get", "path": "{teamId}/jobs/{jobId}/schedule", "httpMethod": "GET", "parameters": {"jobId": {"type": "string", "required": true, "format": "uint64", "location": "path"}, "teamId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Schedule"}, "scopes": ["https://www.googleapis.com/auth/coordinate", "https://www.googleapis.com/auth/coordinate.readonly"]}, "patch": {"id": "coordinate.schedule.patch", "path": "{teamId}/jobs/{jobId}/schedule", "httpMethod": "PATCH", "parameters": {"allDay": {"type": "boolean", "location": "query"}, "duration": {"type": "string", "format": "uint64", "location": "query"}, "endTime": {"type": "string", "format": "uint64", "location": "query"}, "jobId": {"type": "string", "required": true, "format": "uint64", "location": "path"}, "startTime": {"type": "string", "format": "uint64", "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Schedule"}, "response": {"$ref": "Schedule"}, "scopes": ["https://www.googleapis.com/auth/coordinate"]}, "update": {"id": "coordinate.schedule.update", "path": "{teamId}/jobs/{jobId}/schedule", "httpMethod": "PUT", "parameters": {"allDay": {"type": "boolean", "location": "query"}, "duration": {"type": "string", "format": "uint64", "location": "query"}, "endTime": {"type": "string", "format": "uint64", "location": "query"}, "jobId": {"type": "string", "required": true, "format": "uint64", "location": "path"}, "startTime": {"type": "string", "format": "uint64", "location": "query"}, "teamId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Schedule"}, "response": {"$ref": "Schedule"}, "scopes": ["https://www.googleapis.com/auth/coordinate"]}}}', true));
    $this->worker = new Google_WorkerServiceResource($this, $this->serviceName, 'worker', json_decode('{"methods": {"list": {"id": "coordinate.worker.list", "path": "{teamId}/workers", "httpMethod": "GET", "parameters": {"teamId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "WorkerListResponse"}, "scopes": ["https://www.googleapis.com/auth/coordinate", "https://www.googleapis.com/auth/coordinate.readonly"]}}}', true));

  }
}



class Google_CustomField extends Google_Model {
  public $customFieldId;
  public $kind;
  public $value;
  public function setCustomFieldId( $customFieldId) {
    $this->customFieldId = $customFieldId;
  }
  public function getCustomFieldId() {
    return $this->customFieldId;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setValue( $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_CustomFieldDef extends Google_Model {
  public $enabled;
  public $id;
  public $kind;
  public $name;
  public $requiredForCheckout;
  public $type;
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
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setRequiredForCheckout( $requiredForCheckout) {
    $this->requiredForCheckout = $requiredForCheckout;
  }
  public function getRequiredForCheckout() {
    return $this->requiredForCheckout;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Google_CustomFieldDefListResponse extends Google_Model {
  protected $__itemsType = 'Google_CustomFieldDef';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_CustomFieldDef) */ $items) {
    $this->assertIsArray($items, 'Google_CustomFieldDef', __METHOD__);
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

class Google_CustomFields extends Google_Model {
  protected $__customFieldType = 'Google_CustomField';
  protected $__customFieldDataType = 'array';
  public $customField;
  public $kind;
  public function setCustomField(/* array(Google_CustomField) */ $customField) {
    $this->assertIsArray($customField, 'Google_CustomField', __METHOD__);
    $this->customField = $customField;
  }
  public function getCustomField() {
    return $this->customField;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Google_Job extends Google_Model {
  public $id;
  protected $__jobChangeType = 'Google_JobChange';
  protected $__jobChangeDataType = 'array';
  public $jobChange;
  public $kind;
  protected $__stateType = 'Google_JobState';
  protected $__stateDataType = '';
  public $state;
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setJobChange(/* array(Google_JobChange) */ $jobChange) {
    $this->assertIsArray($jobChange, 'Google_JobChange', __METHOD__);
    $this->jobChange = $jobChange;
  }
  public function getJobChange() {
    return $this->jobChange;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setState(Google_JobState $state) {
    $this->state = $state;
  }
  public function getState() {
    return $this->state;
  }
}

class Google_JobChange extends Google_Model {
  public $kind;
  protected $__stateType = 'Google_JobState';
  protected $__stateDataType = '';
  public $state;
  public $timestamp;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setState(Google_JobState $state) {
    $this->state = $state;
  }
  public function getState() {
    return $this->state;
  }
  public function setTimestamp( $timestamp) {
    $this->timestamp = $timestamp;
  }
  public function getTimestamp() {
    return $this->timestamp;
  }
}

class Google_JobListResponse extends Google_Model {
  protected $__itemsType = 'Google_Job';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_Job) */ $items) {
    $this->assertIsArray($items, 'Google_Job', __METHOD__);
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

class Google_JobState extends Google_Model {
  public $assignee;
  protected $__customFieldsType = 'Google_CustomFields';
  protected $__customFieldsDataType = '';
  public $customFields;
  public $customerName;
  public $customerPhoneNumber;
  public $kind;
  protected $__locationType = 'Google_Location';
  protected $__locationDataType = '';
  public $location;
  public $note;
  public $progress;
  public $title;
  public function setAssignee( $assignee) {
    $this->assignee = $assignee;
  }
  public function getAssignee() {
    return $this->assignee;
  }
  public function setCustomFields(Google_CustomFields $customFields) {
    $this->customFields = $customFields;
  }
  public function getCustomFields() {
    return $this->customFields;
  }
  public function setCustomerName( $customerName) {
    $this->customerName = $customerName;
  }
  public function getCustomerName() {
    return $this->customerName;
  }
  public function setCustomerPhoneNumber( $customerPhoneNumber) {
    $this->customerPhoneNumber = $customerPhoneNumber;
  }
  public function getCustomerPhoneNumber() {
    return $this->customerPhoneNumber;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLocation(Google_Location $location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setNote(/* array(Google_string) */ $note) {
    $this->assertIsArray($note, 'Google_string', __METHOD__);
    $this->note = $note;
  }
  public function getNote() {
    return $this->note;
  }
  public function setProgress( $progress) {
    $this->progress = $progress;
  }
  public function getProgress() {
    return $this->progress;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
}

class Google_Location extends Google_Model {
  public $addressLine;
  public $kind;
  public $lat;
  public $lng;
  public function setAddressLine(/* array(Google_string) */ $addressLine) {
    $this->assertIsArray($addressLine, 'Google_string', __METHOD__);
    $this->addressLine = $addressLine;
  }
  public function getAddressLine() {
    return $this->addressLine;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLat( $lat) {
    $this->lat = $lat;
  }
  public function getLat() {
    return $this->lat;
  }
  public function setLng( $lng) {
    $this->lng = $lng;
  }
  public function getLng() {
    return $this->lng;
  }
}

class Google_LocationListResponse extends Google_Model {
  protected $__itemsType = 'Google_LocationRecord';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  protected $__tokenPaginationType = 'Google_TokenPagination';
  protected $__tokenPaginationDataType = '';
  public $tokenPagination;
  public function setItems(/* array(Google_LocationRecord) */ $items) {
    $this->assertIsArray($items, 'Google_LocationRecord', __METHOD__);
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
  public function setTokenPagination(Google_TokenPagination $tokenPagination) {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination() {
    return $this->tokenPagination;
  }
}

class Google_LocationRecord extends Google_Model {
  public $collectionTime;
  public $confidenceRadius;
  public $kind;
  public $latitude;
  public $longitude;
  public function setCollectionTime( $collectionTime) {
    $this->collectionTime = $collectionTime;
  }
  public function getCollectionTime() {
    return $this->collectionTime;
  }
  public function setConfidenceRadius( $confidenceRadius) {
    $this->confidenceRadius = $confidenceRadius;
  }
  public function getConfidenceRadius() {
    return $this->confidenceRadius;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLatitude( $latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLongitude( $longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
}

class Google_Schedule extends Google_Model {
  public $allDay;
  public $duration;
  public $endTime;
  public $kind;
  public $startTime;
  public function setAllDay( $allDay) {
    $this->allDay = $allDay;
  }
  public function getAllDay() {
    return $this->allDay;
  }
  public function setDuration( $duration) {
    $this->duration = $duration;
  }
  public function getDuration() {
    return $this->duration;
  }
  public function setEndTime( $endTime) {
    $this->endTime = $endTime;
  }
  public function getEndTime() {
    return $this->endTime;
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

class Google_TokenPagination extends Google_Model {
  public $kind;
  public $nextPageToken;
  public $previousPageToken;
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
  public function setPreviousPageToken( $previousPageToken) {
    $this->previousPageToken = $previousPageToken;
  }
  public function getPreviousPageToken() {
    return $this->previousPageToken;
  }
}

class Google_Worker extends Google_Model {
  public $id;
  public $kind;
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
}

class Google_WorkerListResponse extends Google_Model {
  protected $__itemsType = 'Google_Worker';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_Worker) */ $items) {
    $this->assertIsArray($items, 'Google_Worker', __METHOD__);
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
