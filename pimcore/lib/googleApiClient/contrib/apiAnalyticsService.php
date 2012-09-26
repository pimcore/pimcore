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
   * The "management" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $management = $analyticsService->management;
   *  </code>
   */
  class ManagementServiceResource extends apiServiceResource {


  }

  /**
   * The "webproperties" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $webproperties = $analyticsService->webproperties;
   *  </code>
   */
  class ManagementWebpropertiesServiceResource extends apiServiceResource {


    /**
     * Lists web properties to which the user has access. (webproperties.list)
     *
     * @param string $accountId Account ID to retrieve web properties for. Can either be a specific account ID or '~all', which refers to all the accounts that user has access to.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param int max-results The maximum number of web properties to include in this response.
     * @opt_param int start-index An index of the first entity to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
     * @return Webproperties
     */
    public function listManagementWebproperties($accountId, $optParams = array()) {
      $params = array('accountId' => $accountId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Webproperties($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "segments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $segments = $analyticsService->segments;
   *  </code>
   */
  class ManagementSegmentsServiceResource extends apiServiceResource {


    /**
     * Lists advanced segments to which the user has access. (segments.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param int max-results The maximum number of advanced segments to include in this response.
     * @opt_param int start-index An index of the first advanced segment to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
     * @return Segments
     */
    public function listManagementSegments($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Segments($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "accounts" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $accounts = $analyticsService->accounts;
   *  </code>
   */
  class ManagementAccountsServiceResource extends apiServiceResource {


    /**
     * Lists all accounts to which the user has access. (accounts.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param int max-results The maximum number of accounts to include in this response.
     * @opt_param int start-index An index of the first account to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
     * @return Accounts
     */
    public function listManagementAccounts($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Accounts($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "goals" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $goals = $analyticsService->goals;
   *  </code>
   */
  class ManagementGoalsServiceResource extends apiServiceResource {


    /**
     * Lists goals to which the user has access. (goals.list)
     *
     * @param string $accountId Account ID to retrieve goals for. Can either be a specific account ID or '~all', which refers to all the accounts that user has access to.
     * @param string $webPropertyId Web property ID to retrieve goals for. Can either be a specific web property ID or '~all', which refers to all the web properties that user has access to.
     * @param string $profileId Profile ID to retrieve goals for. Can either be a specific profile ID or '~all', which refers to all the profiles that user has access to.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param int max-results The maximum number of goals to include in this response.
     * @opt_param int start-index An index of the first goal to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
     * @return Goals
     */
    public function listManagementGoals($accountId, $webPropertyId, $profileId, $optParams = array()) {
      $params = array('accountId' => $accountId, 'webPropertyId' => $webPropertyId, 'profileId' => $profileId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Goals($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "profiles" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $profiles = $analyticsService->profiles;
   *  </code>
   */
  class ManagementProfilesServiceResource extends apiServiceResource {


    /**
     * Lists profiles to which the user has access. (profiles.list)
     *
     * @param string $accountId Account ID for the profiles to retrieve. Can either be a specific account ID or '~all', which refers to all the accounts to which the user has access.
     * @param string $webPropertyId Web property ID for the profiles to retrieve. Can either be a specific web property ID or '~all', which refers to all the web properties to which the user has access.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param int max-results The maximum number of profiles to include in this response.
     * @opt_param int start-index An index of the first entity to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
     * @return Profiles
     */
    public function listManagementProfiles($accountId, $webPropertyId, $optParams = array()) {
      $params = array('accountId' => $accountId, 'webPropertyId' => $webPropertyId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Profiles($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "data" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $data = $analyticsService->data;
   *  </code>
   */
  class DataServiceResource extends apiServiceResource {


  }

  /**
   * The "ga" collection of methods.
   * Typical usage is:
   *  <code>
   *   $analyticsService = new apiAnalyticsService(...);
   *   $ga = $analyticsService->ga;
   *  </code>
   */
  class DataGaServiceResource extends apiServiceResource {


    /**
     * Returns Analytics data for a profile. (ga.get)
     *
     * @param string $ids Unique table ID for retrieving Analytics data. Table ID is of the form ga:XXXX, where XXXX is the Analytics profile ID.
     * @param string $start_date Start date for fetching Analytics data. All requests should specify a start date formatted as YYYY-MM-DD.
     * @param string $end_date End date for fetching Analytics data. All requests should specify an end date formatted as YYYY-MM-DD.
     * @param string $metrics A comma-separated list of Analytics metrics. E.g., 'ga:visits,ga:pageviews'. At least one metric must be specified.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param int max-results The maximum number of entries to include in this feed.
     * @opt_param string sort A comma-separated list of dimensions or metrics that determine the sort order for Analytics data.
     * @opt_param string dimensions A comma-separated list of Analytics dimensions. E.g., 'ga:browser,ga:city'.
     * @opt_param int start-index An index of the first entity to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
     * @opt_param string segment An Analytics advanced segment to be applied to data.
     * @opt_param string filters A comma-separated list of dimension or metric filters to be applied to Analytics data.
     * @return GaData
     */
    public function get($ids, $start_date, $end_date, $metrics, $optParams = array()) {
      $params = array('ids' => $ids, 'start-date' => $start_date, 'end-date' => $end_date, 'metrics' => $metrics);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new GaData($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Analytics (v3).
 *
 * <p>
 * View and manage your Google Analytics data
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/analytics" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiAnalyticsService extends apiService {
  public $management_webproperties;
  public $management_segments;
  public $management_accounts;
  public $management_goals;
  public $management_profiles;
  public $data_ga;
  /**
   * Constructs the internal representation of the Analytics service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/analytics/v3/';
    $this->version = 'v3';
    $this->serviceName = 'analytics';

    $apiClient->addService($this->serviceName, $this->version);
    $this->management_webproperties = new ManagementWebpropertiesServiceResource($this, $this->serviceName, 'webproperties', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/analytics.readonly"], "parameters": {"max-results": {"format": "int32", "type": "integer", "location": "query"}, "start-index": {"format": "int32", "minimum": "1", "type": "integer", "location": "query"}, "accountId": {"required": true, "type": "string", "location": "path"}}, "id": "analytics.management.webproperties.list", "httpMethod": "GET", "path": "management/accounts/{accountId}/webproperties", "response": {"$ref": "Webproperties"}}}}', true));
    $this->management_segments = new ManagementSegmentsServiceResource($this, $this->serviceName, 'segments', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/analytics.readonly"], "parameters": {"max-results": {"format": "int32", "type": "integer", "location": "query"}, "start-index": {"format": "int32", "minimum": "1", "type": "integer", "location": "query"}}, "response": {"$ref": "Segments"}, "httpMethod": "GET", "path": "management/segments", "id": "analytics.management.segments.list"}}}', true));
    $this->management_accounts = new ManagementAccountsServiceResource($this, $this->serviceName, 'accounts', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/analytics.readonly"], "parameters": {"max-results": {"format": "int32", "type": "integer", "location": "query"}, "start-index": {"format": "int32", "minimum": "1", "type": "integer", "location": "query"}}, "response": {"$ref": "Accounts"}, "httpMethod": "GET", "path": "management/accounts", "id": "analytics.management.accounts.list"}}}', true));
    $this->management_goals = new ManagementGoalsServiceResource($this, $this->serviceName, 'goals', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/analytics.readonly"], "parameters": {"max-results": {"format": "int32", "type": "integer", "location": "query"}, "profileId": {"required": true, "type": "string", "location": "path"}, "start-index": {"format": "int32", "minimum": "1", "type": "integer", "location": "query"}, "accountId": {"required": true, "type": "string", "location": "path"}, "webPropertyId": {"required": true, "type": "string", "location": "path"}}, "id": "analytics.management.goals.list", "httpMethod": "GET", "path": "management/accounts/{accountId}/webproperties/{webPropertyId}/profiles/{profileId}/goals", "response": {"$ref": "Goals"}}}}', true));
    $this->management_profiles = new ManagementProfilesServiceResource($this, $this->serviceName, 'profiles', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/analytics.readonly"], "parameters": {"max-results": {"format": "int32", "type": "integer", "location": "query"}, "start-index": {"format": "int32", "minimum": "1", "type": "integer", "location": "query"}, "accountId": {"required": true, "type": "string", "location": "path"}, "webPropertyId": {"required": true, "type": "string", "location": "path"}}, "id": "analytics.management.profiles.list", "httpMethod": "GET", "path": "management/accounts/{accountId}/webproperties/{webPropertyId}/profiles", "response": {"$ref": "Profiles"}}}}', true));
    $this->data_ga = new DataGaServiceResource($this, $this->serviceName, 'ga', json_decode('{"methods": {"get": {"scopes": ["https://www.googleapis.com/auth/analytics.readonly"], "parameters": {"max-results": {"format": "int32", "type": "integer", "location": "query"}, "sort": {"type": "string", "location": "query"}, "dimensions": {"type": "string", "location": "query"}, "start-date": {"required": true, "type": "string", "location": "query"}, "start-index": {"format": "int32", "minimum": "1", "type": "integer", "location": "query"}, "end-date": {"required": true, "type": "string", "location": "query"}, "ids": {"required": true, "type": "string", "location": "query"}, "metrics": {"required": true, "type": "string", "location": "query"}, "filters": {"type": "string", "location": "query"}, "segment": {"type": "string", "location": "query"}}, "id": "analytics.data.ga.get", "httpMethod": "GET", "path": "data/ga", "response": {"$ref": "GaData"}}}}', true));

  }
}

class Account extends apiModel {
  public $kind;
  public $name;
  public $created;
  public $updated;
  protected $__childLinkType = 'AccountChildLink';
  protected $__childLinkDataType = '';
  public $childLink;
  public $id;
  public $selfLink;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setChildLink(AccountChildLink $childLink) {
    $this->childLink = $childLink;
  }
  public function getChildLink() {
    return $this->childLink;
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

class AccountChildLink extends apiModel {
  public $href;
  public $type;
  public function setHref($href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Accounts extends apiModel {
  public $username;
  public $kind;
  protected $__itemsType = 'Account';
  protected $__itemsDataType = 'array';
  public $items;
  public $itemsPerPage;
  public $previousLink;
  public $startIndex;
  public $nextLink;
  public $totalResults;
  public function setUsername($username) {
    $this->username = $username;
  }
  public function getUsername() {
    return $this->username;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setItems(/* array(Account) */ $items) {
    $this->assertIsArray($items, 'Account', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setPreviousLink($previousLink) {
    $this->previousLink = $previousLink;
  }
  public function getPreviousLink() {
    return $this->previousLink;
  }
  public function setStartIndex($startIndex) {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex() {
    return $this->startIndex;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults() {
    return $this->totalResults;
  }
}

class GaData extends apiModel {
  public $kind;
  public $rows;
  public $containsSampledData;
  public $totalResults;
  public $itemsPerPage;
  public $totalsForAllResults;
  public $nextLink;
  public $id;
  protected $__queryType = 'GaDataQuery';
  protected $__queryDataType = '';
  public $query;
  public $previousLink;
  protected $__profileInfoType = 'GaDataProfileInfo';
  protected $__profileInfoDataType = '';
  public $profileInfo;
  protected $__columnHeadersType = 'GaDataColumnHeaders';
  protected $__columnHeadersDataType = 'array';
  public $columnHeaders;
  public $selfLink;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRows(/* array(string) */ $rows) {
    $this->assertIsArray($rows, 'string', __METHOD__);
    $this->rows = $rows;
  }
  public function getRows() {
    return $this->rows;
  }
  public function setContainsSampledData($containsSampledData) {
    $this->containsSampledData = $containsSampledData;
  }
  public function getContainsSampledData() {
    return $this->containsSampledData;
  }
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults() {
    return $this->totalResults;
  }
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setTotalsForAllResults($totalsForAllResults) {
    $this->totalsForAllResults = $totalsForAllResults;
  }
  public function getTotalsForAllResults() {
    return $this->totalsForAllResults;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setQuery(GaDataQuery $query) {
    $this->query = $query;
  }
  public function getQuery() {
    return $this->query;
  }
  public function setPreviousLink($previousLink) {
    $this->previousLink = $previousLink;
  }
  public function getPreviousLink() {
    return $this->previousLink;
  }
  public function setProfileInfo(GaDataProfileInfo $profileInfo) {
    $this->profileInfo = $profileInfo;
  }
  public function getProfileInfo() {
    return $this->profileInfo;
  }
  public function setColumnHeaders(/* array(GaDataColumnHeaders) */ $columnHeaders) {
    $this->assertIsArray($columnHeaders, 'GaDataColumnHeaders', __METHOD__);
    $this->columnHeaders = $columnHeaders;
  }
  public function getColumnHeaders() {
    return $this->columnHeaders;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class GaDataColumnHeaders extends apiModel {
  public $dataType;
  public $columnType;
  public $name;
  public function setDataType($dataType) {
    $this->dataType = $dataType;
  }
  public function getDataType() {
    return $this->dataType;
  }
  public function setColumnType($columnType) {
    $this->columnType = $columnType;
  }
  public function getColumnType() {
    return $this->columnType;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class GaDataProfileInfo extends apiModel {
  public $webPropertyId;
  public $internalWebPropertyId;
  public $tableId;
  public $profileId;
  public $profileName;
  public $accountId;
  public function setWebPropertyId($webPropertyId) {
    $this->webPropertyId = $webPropertyId;
  }
  public function getWebPropertyId() {
    return $this->webPropertyId;
  }
  public function setInternalWebPropertyId($internalWebPropertyId) {
    $this->internalWebPropertyId = $internalWebPropertyId;
  }
  public function getInternalWebPropertyId() {
    return $this->internalWebPropertyId;
  }
  public function setTableId($tableId) {
    $this->tableId = $tableId;
  }
  public function getTableId() {
    return $this->tableId;
  }
  public function setProfileId($profileId) {
    $this->profileId = $profileId;
  }
  public function getProfileId() {
    return $this->profileId;
  }
  public function setProfileName($profileName) {
    $this->profileName = $profileName;
  }
  public function getProfileName() {
    return $this->profileName;
  }
  public function setAccountId($accountId) {
    $this->accountId = $accountId;
  }
  public function getAccountId() {
    return $this->accountId;
  }
}

class GaDataQuery extends apiModel {
  public $max_results;
  public $sort;
  public $dimensions;
  public $start_date;
  public $start_index;
  public $segment;
  public $ids;
  public $metrics;
  public $filters;
  public $end_date;
  public function setMax_results($max_results) {
    $this->max_results = $max_results;
  }
  public function getMax_results() {
    return $this->max_results;
  }
  public function setSort(/* array(string) */ $sort) {
    $this->assertIsArray($sort, 'string', __METHOD__);
    $this->sort = $sort;
  }
  public function getSort() {
    return $this->sort;
  }
  public function setDimensions($dimensions) {
    $this->dimensions = $dimensions;
  }
  public function getDimensions() {
    return $this->dimensions;
  }
  public function setStart_date($start_date) {
    $this->start_date = $start_date;
  }
  public function getStart_date() {
    return $this->start_date;
  }
  public function setStart_index($start_index) {
    $this->start_index = $start_index;
  }
  public function getStart_index() {
    return $this->start_index;
  }
  public function setSegment($segment) {
    $this->segment = $segment;
  }
  public function getSegment() {
    return $this->segment;
  }
  public function setIds($ids) {
    $this->ids = $ids;
  }
  public function getIds() {
    return $this->ids;
  }
  public function setMetrics(/* array(string) */ $metrics) {
    $this->assertIsArray($metrics, 'string', __METHOD__);
    $this->metrics = $metrics;
  }
  public function getMetrics() {
    return $this->metrics;
  }
  public function setFilters($filters) {
    $this->filters = $filters;
  }
  public function getFilters() {
    return $this->filters;
  }
  public function setEnd_date($end_date) {
    $this->end_date = $end_date;
  }
  public function getEnd_date() {
    return $this->end_date;
  }
}

class Goal extends apiModel {
  public $kind;
  protected $__visitTimeOnSiteDetailsType = 'GoalVisitTimeOnSiteDetails';
  protected $__visitTimeOnSiteDetailsDataType = '';
  public $visitTimeOnSiteDetails;
  public $name;
  public $created;
  protected $__urlDestinationDetailsType = 'GoalUrlDestinationDetails';
  protected $__urlDestinationDetailsDataType = '';
  public $urlDestinationDetails;
  public $updated;
  public $value;
  protected $__visitNumPagesDetailsType = 'GoalVisitNumPagesDetails';
  protected $__visitNumPagesDetailsDataType = '';
  public $visitNumPagesDetails;
  public $internalWebPropertyId;
  protected $__eventDetailsType = 'GoalEventDetails';
  protected $__eventDetailsDataType = '';
  public $eventDetails;
  public $webPropertyId;
  public $active;
  public $profileId;
  protected $__parentLinkType = 'GoalParentLink';
  protected $__parentLinkDataType = '';
  public $parentLink;
  public $type;
  public $id;
  public $selfLink;
  public $accountId;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setVisitTimeOnSiteDetails(GoalVisitTimeOnSiteDetails $visitTimeOnSiteDetails) {
    $this->visitTimeOnSiteDetails = $visitTimeOnSiteDetails;
  }
  public function getVisitTimeOnSiteDetails() {
    return $this->visitTimeOnSiteDetails;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setUrlDestinationDetails(GoalUrlDestinationDetails $urlDestinationDetails) {
    $this->urlDestinationDetails = $urlDestinationDetails;
  }
  public function getUrlDestinationDetails() {
    return $this->urlDestinationDetails;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setValue($value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
  public function setVisitNumPagesDetails(GoalVisitNumPagesDetails $visitNumPagesDetails) {
    $this->visitNumPagesDetails = $visitNumPagesDetails;
  }
  public function getVisitNumPagesDetails() {
    return $this->visitNumPagesDetails;
  }
  public function setInternalWebPropertyId($internalWebPropertyId) {
    $this->internalWebPropertyId = $internalWebPropertyId;
  }
  public function getInternalWebPropertyId() {
    return $this->internalWebPropertyId;
  }
  public function setEventDetails(GoalEventDetails $eventDetails) {
    $this->eventDetails = $eventDetails;
  }
  public function getEventDetails() {
    return $this->eventDetails;
  }
  public function setWebPropertyId($webPropertyId) {
    $this->webPropertyId = $webPropertyId;
  }
  public function getWebPropertyId() {
    return $this->webPropertyId;
  }
  public function setActive($active) {
    $this->active = $active;
  }
  public function getActive() {
    return $this->active;
  }
  public function setProfileId($profileId) {
    $this->profileId = $profileId;
  }
  public function getProfileId() {
    return $this->profileId;
  }
  public function setParentLink(GoalParentLink $parentLink) {
    $this->parentLink = $parentLink;
  }
  public function getParentLink() {
    return $this->parentLink;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
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
  public function setAccountId($accountId) {
    $this->accountId = $accountId;
  }
  public function getAccountId() {
    return $this->accountId;
  }
}

class GoalEventDetails extends apiModel {
  protected $__eventConditionsType = 'GoalEventDetailsEventConditions';
  protected $__eventConditionsDataType = 'array';
  public $eventConditions;
  public $useEventValue;
  public function setEventConditions(/* array(GoalEventDetailsEventConditions) */ $eventConditions) {
    $this->assertIsArray($eventConditions, 'GoalEventDetailsEventConditions', __METHOD__);
    $this->eventConditions = $eventConditions;
  }
  public function getEventConditions() {
    return $this->eventConditions;
  }
  public function setUseEventValue($useEventValue) {
    $this->useEventValue = $useEventValue;
  }
  public function getUseEventValue() {
    return $this->useEventValue;
  }
}

class GoalEventDetailsEventConditions extends apiModel {
  public $type;
  public $matchType;
  public $expression;
  public $comparisonType;
  public $comparisonValue;
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setMatchType($matchType) {
    $this->matchType = $matchType;
  }
  public function getMatchType() {
    return $this->matchType;
  }
  public function setExpression($expression) {
    $this->expression = $expression;
  }
  public function getExpression() {
    return $this->expression;
  }
  public function setComparisonType($comparisonType) {
    $this->comparisonType = $comparisonType;
  }
  public function getComparisonType() {
    return $this->comparisonType;
  }
  public function setComparisonValue($comparisonValue) {
    $this->comparisonValue = $comparisonValue;
  }
  public function getComparisonValue() {
    return $this->comparisonValue;
  }
}

class GoalParentLink extends apiModel {
  public $href;
  public $type;
  public function setHref($href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class GoalUrlDestinationDetails extends apiModel {
  public $url;
  public $caseSensitive;
  public $matchType;
  protected $__stepsType = 'GoalUrlDestinationDetailsSteps';
  protected $__stepsDataType = 'array';
  public $steps;
  public $firstStepRequired;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setCaseSensitive($caseSensitive) {
    $this->caseSensitive = $caseSensitive;
  }
  public function getCaseSensitive() {
    return $this->caseSensitive;
  }
  public function setMatchType($matchType) {
    $this->matchType = $matchType;
  }
  public function getMatchType() {
    return $this->matchType;
  }
  public function setSteps(/* array(GoalUrlDestinationDetailsSteps) */ $steps) {
    $this->assertIsArray($steps, 'GoalUrlDestinationDetailsSteps', __METHOD__);
    $this->steps = $steps;
  }
  public function getSteps() {
    return $this->steps;
  }
  public function setFirstStepRequired($firstStepRequired) {
    $this->firstStepRequired = $firstStepRequired;
  }
  public function getFirstStepRequired() {
    return $this->firstStepRequired;
  }
}

class GoalUrlDestinationDetailsSteps extends apiModel {
  public $url;
  public $name;
  public $number;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setNumber($number) {
    $this->number = $number;
  }
  public function getNumber() {
    return $this->number;
  }
}

class GoalVisitNumPagesDetails extends apiModel {
  public $comparisonType;
  public $comparisonValue;
  public function setComparisonType($comparisonType) {
    $this->comparisonType = $comparisonType;
  }
  public function getComparisonType() {
    return $this->comparisonType;
  }
  public function setComparisonValue($comparisonValue) {
    $this->comparisonValue = $comparisonValue;
  }
  public function getComparisonValue() {
    return $this->comparisonValue;
  }
}

class GoalVisitTimeOnSiteDetails extends apiModel {
  public $comparisonType;
  public $comparisonValue;
  public function setComparisonType($comparisonType) {
    $this->comparisonType = $comparisonType;
  }
  public function getComparisonType() {
    return $this->comparisonType;
  }
  public function setComparisonValue($comparisonValue) {
    $this->comparisonValue = $comparisonValue;
  }
  public function getComparisonValue() {
    return $this->comparisonValue;
  }
}

class Goals extends apiModel {
  public $username;
  public $kind;
  protected $__itemsType = 'Goal';
  protected $__itemsDataType = 'array';
  public $items;
  public $itemsPerPage;
  public $previousLink;
  public $startIndex;
  public $nextLink;
  public $totalResults;
  public function setUsername($username) {
    $this->username = $username;
  }
  public function getUsername() {
    return $this->username;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setItems(/* array(Goal) */ $items) {
    $this->assertIsArray($items, 'Goal', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setPreviousLink($previousLink) {
    $this->previousLink = $previousLink;
  }
  public function getPreviousLink() {
    return $this->previousLink;
  }
  public function setStartIndex($startIndex) {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex() {
    return $this->startIndex;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults() {
    return $this->totalResults;
  }
}

class Profile extends apiModel {
  public $defaultPage;
  public $kind;
  public $excludeQueryParameters;
  public $name;
  public $created;
  public $webPropertyId;
  public $updated;
  public $siteSearchQueryParameters;
  public $currency;
  public $internalWebPropertyId;
  protected $__childLinkType = 'ProfileChildLink';
  protected $__childLinkDataType = '';
  public $childLink;
  public $timezone;
  public $siteSearchCategoryParameters;
  protected $__parentLinkType = 'ProfileParentLink';
  protected $__parentLinkDataType = '';
  public $parentLink;
  public $id;
  public $selfLink;
  public $accountId;
  public function setDefaultPage($defaultPage) {
    $this->defaultPage = $defaultPage;
  }
  public function getDefaultPage() {
    return $this->defaultPage;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setExcludeQueryParameters($excludeQueryParameters) {
    $this->excludeQueryParameters = $excludeQueryParameters;
  }
  public function getExcludeQueryParameters() {
    return $this->excludeQueryParameters;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setWebPropertyId($webPropertyId) {
    $this->webPropertyId = $webPropertyId;
  }
  public function getWebPropertyId() {
    return $this->webPropertyId;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setSiteSearchQueryParameters($siteSearchQueryParameters) {
    $this->siteSearchQueryParameters = $siteSearchQueryParameters;
  }
  public function getSiteSearchQueryParameters() {
    return $this->siteSearchQueryParameters;
  }
  public function setCurrency($currency) {
    $this->currency = $currency;
  }
  public function getCurrency() {
    return $this->currency;
  }
  public function setInternalWebPropertyId($internalWebPropertyId) {
    $this->internalWebPropertyId = $internalWebPropertyId;
  }
  public function getInternalWebPropertyId() {
    return $this->internalWebPropertyId;
  }
  public function setChildLink(ProfileChildLink $childLink) {
    $this->childLink = $childLink;
  }
  public function getChildLink() {
    return $this->childLink;
  }
  public function setTimezone($timezone) {
    $this->timezone = $timezone;
  }
  public function getTimezone() {
    return $this->timezone;
  }
  public function setSiteSearchCategoryParameters($siteSearchCategoryParameters) {
    $this->siteSearchCategoryParameters = $siteSearchCategoryParameters;
  }
  public function getSiteSearchCategoryParameters() {
    return $this->siteSearchCategoryParameters;
  }
  public function setParentLink(ProfileParentLink $parentLink) {
    $this->parentLink = $parentLink;
  }
  public function getParentLink() {
    return $this->parentLink;
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
  public function setAccountId($accountId) {
    $this->accountId = $accountId;
  }
  public function getAccountId() {
    return $this->accountId;
  }
}

class ProfileChildLink extends apiModel {
  public $href;
  public $type;
  public function setHref($href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class ProfileParentLink extends apiModel {
  public $href;
  public $type;
  public function setHref($href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Profiles extends apiModel {
  public $username;
  public $kind;
  protected $__itemsType = 'Profile';
  protected $__itemsDataType = 'array';
  public $items;
  public $itemsPerPage;
  public $previousLink;
  public $startIndex;
  public $nextLink;
  public $totalResults;
  public function setUsername($username) {
    $this->username = $username;
  }
  public function getUsername() {
    return $this->username;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setItems(/* array(Profile) */ $items) {
    $this->assertIsArray($items, 'Profile', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setPreviousLink($previousLink) {
    $this->previousLink = $previousLink;
  }
  public function getPreviousLink() {
    return $this->previousLink;
  }
  public function setStartIndex($startIndex) {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex() {
    return $this->startIndex;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults() {
    return $this->totalResults;
  }
}

class Segment extends apiModel {
  public $definition;
  public $kind;
  public $segmentId;
  public $created;
  public $updated;
  public $id;
  public $selfLink;
  public $name;
  public function setDefinition($definition) {
    $this->definition = $definition;
  }
  public function getDefinition() {
    return $this->definition;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setSegmentId($segmentId) {
    $this->segmentId = $segmentId;
  }
  public function getSegmentId() {
    return $this->segmentId;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
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
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class Segments extends apiModel {
  public $username;
  public $kind;
  protected $__itemsType = 'Segment';
  protected $__itemsDataType = 'array';
  public $items;
  public $itemsPerPage;
  public $previousLink;
  public $startIndex;
  public $nextLink;
  public $totalResults;
  public function setUsername($username) {
    $this->username = $username;
  }
  public function getUsername() {
    return $this->username;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setItems(/* array(Segment) */ $items) {
    $this->assertIsArray($items, 'Segment', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setPreviousLink($previousLink) {
    $this->previousLink = $previousLink;
  }
  public function getPreviousLink() {
    return $this->previousLink;
  }
  public function setStartIndex($startIndex) {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex() {
    return $this->startIndex;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults() {
    return $this->totalResults;
  }
}

class Webproperties extends apiModel {
  public $username;
  public $kind;
  protected $__itemsType = 'Webproperty';
  protected $__itemsDataType = 'array';
  public $items;
  public $itemsPerPage;
  public $previousLink;
  public $startIndex;
  public $nextLink;
  public $totalResults;
  public function setUsername($username) {
    $this->username = $username;
  }
  public function getUsername() {
    return $this->username;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setItems(/* array(Webproperty) */ $items) {
    $this->assertIsArray($items, 'Webproperty', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setPreviousLink($previousLink) {
    $this->previousLink = $previousLink;
  }
  public function getPreviousLink() {
    return $this->previousLink;
  }
  public function setStartIndex($startIndex) {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex() {
    return $this->startIndex;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setTotalResults($totalResults) {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults() {
    return $this->totalResults;
  }
}

class Webproperty extends apiModel {
  public $kind;
  public $name;
  public $created;
  public $updated;
  public $websiteUrl;
  public $internalWebPropertyId;
  protected $__childLinkType = 'WebpropertyChildLink';
  protected $__childLinkDataType = '';
  public $childLink;
  protected $__parentLinkType = 'WebpropertyParentLink';
  protected $__parentLinkDataType = '';
  public $parentLink;
  public $id;
  public $selfLink;
  public $accountId;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setWebsiteUrl($websiteUrl) {
    $this->websiteUrl = $websiteUrl;
  }
  public function getWebsiteUrl() {
    return $this->websiteUrl;
  }
  public function setInternalWebPropertyId($internalWebPropertyId) {
    $this->internalWebPropertyId = $internalWebPropertyId;
  }
  public function getInternalWebPropertyId() {
    return $this->internalWebPropertyId;
  }
  public function setChildLink(WebpropertyChildLink $childLink) {
    $this->childLink = $childLink;
  }
  public function getChildLink() {
    return $this->childLink;
  }
  public function setParentLink(WebpropertyParentLink $parentLink) {
    $this->parentLink = $parentLink;
  }
  public function getParentLink() {
    return $this->parentLink;
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
  public function setAccountId($accountId) {
    $this->accountId = $accountId;
  }
  public function getAccountId() {
    return $this->accountId;
  }
}

class WebpropertyChildLink extends apiModel {
  public $href;
  public $type;
  public function setHref($href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class WebpropertyParentLink extends apiModel {
  public $href;
  public $type;
  public function setHref($href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}
