<?php
/*
 * Copyright (c) 2012 Google Inc.
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
   * The "urlchannels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adsensehostService = new apiAdsensehostService(...);
   *   $urlchannels = $adsensehostService->urlchannels;
   *  </code>
   */
  class UrlchannelsServiceResource extends apiServiceResource {


    /**
     * List all host URL channels in this AdSense account. (urlchannels.list)
     *
     * @param string $adClientId Ad client for which to list URL channels.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken A continuation token, used to page through URL channels. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param int maxResults The maximum number of URL channels to include in the response, used for paging.
     * @return UrlChannels
     */
    public function listUrlchannels($adClientId, $optParams = array()) {
      $params = array('adClientId' => $adClientId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new UrlChannels($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "adclients" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adsensehostService = new apiAdsensehostService(...);
   *   $adclients = $adsensehostService->adclients;
   *  </code>
   */
  class AdclientsServiceResource extends apiServiceResource {


    /**
     * List all host ad clients in this AdSense account. (adclients.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken A continuation token, used to page through ad clients. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param int maxResults The maximum number of ad clients to include in the response, used for paging.
     * @return AdClients
     */
    public function listAdclients($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new AdClients($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "reports" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adsensehostService = new apiAdsensehostService(...);
   *   $reports = $adsensehostService->reports;
   *  </code>
   */
  class ReportsServiceResource extends apiServiceResource {


    /**
     * Generate an AdSense report based on the report request sent in the query parameters. Returns the
     * result as JSON; to retrieve output in CSV format specify "alt=csv" as a query parameter.
     * (reports.generate)
     *
     * @param string $startDate Start of the date range to report on in "YYYY-MM-DD" format, inclusive.
     * @param string $endDate End of the date range to report on in "YYYY-MM-DD" format, inclusive.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string sort The name of a dimension or metric to sort the resulting report on, optionally prefixed with "+" to sort ascending or "-" to sort descending. If no prefix is specified, the column is sorted ascending.
     * @opt_param string locale Optional locale to use for translating report output to a local language. Defaults to "en_US" if not specified.
     * @opt_param string metric Numeric columns to include in the report.
     * @opt_param int maxResults The maximum number of rows of report data to return.
     * @opt_param string filter Filters to be run on the report.
     * @opt_param string currency Optional currency to use when reporting on monetary metrics. Defaults to the account's currency if not set.
     * @opt_param int startIndex Index of the first row of report data to return.
     * @opt_param string dimension Dimensions to base the report on.
     * @return AdsensehostReportsGenerateResponse
     */
    public function generate($startDate, $endDate, $optParams = array()) {
      $params = array('startDate' => $startDate, 'endDate' => $endDate);
      $params = array_merge($params, $optParams);
      $data = $this->__call('generate', array($params));
      if ($this->useObjects()) {
        return new AdsensehostReportsGenerateResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "customchannels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adsensehostService = new apiAdsensehostService(...);
   *   $customchannels = $adsensehostService->customchannels;
   *  </code>
   */
  class CustomchannelsServiceResource extends apiServiceResource {


    /**
     * List all host custom channels in this AdSense account. (customchannels.list)
     *
     * @param string $adClientId Ad client for which to list custom channels.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken A continuation token, used to page through custom channels. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param int maxResults The maximum number of custom channels to include in the response, used for paging.
     * @return CustomChannels
     */
    public function listCustomchannels($adClientId, $optParams = array()) {
      $params = array('adClientId' => $adClientId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new CustomChannels($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Adsensehost (v4).
 *
 * <p>
 * Gives AdSense Hosts access to report generation, ad code generation, and publisher management capabilities.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://code.google.com/apis/adsense/host/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiAdsensehostService extends apiService {
  public $urlchannels;
  public $adclients;
  public $reports;
  public $customchannels;
  /**
   * Constructs the internal representation of the Adsensehost service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->rpcPath = '/rpc';
    $this->restBasePath = '/adsensehost/v4/';
    $this->version = 'v4';
    $this->serviceName = 'adsensehost';

    $apiClient->addService($this->serviceName, $this->version);
    $this->urlchannels = new UrlchannelsServiceResource($this, $this->serviceName, 'urlchannels', json_decode('{"methods": {"list": {"parameters": {"pageToken": {"type": "string", "location": "query"}, "adClientId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "int32", "maximum": "10000", "minimum": "0", "location": "query", "type": "integer"}}, "id": "adsensehost.urlchannels.list", "httpMethod": "GET", "path": "adclients/{adClientId}/urlchannels", "response": {"$ref": "UrlChannels"}}}}', true));
    $this->adclients = new AdclientsServiceResource($this, $this->serviceName, 'adclients', json_decode('{"methods": {"list": {"parameters": {"pageToken": {"type": "string", "location": "query"}, "maxResults": {"format": "int32", "maximum": "10000", "minimum": "0", "location": "query", "type": "integer"}}, "id": "adsensehost.adclients.list", "httpMethod": "GET", "path": "adclients", "response": {"$ref": "AdClients"}}}}', true));
    $this->reports = new ReportsServiceResource($this, $this->serviceName, 'reports', json_decode('{"methods": {"generate": {"parameters": {"sort": {"repeated": true, "type": "string", "location": "query"}, "startDate": {"required": true, "type": "string", "location": "query"}, "endDate": {"required": true, "type": "string", "location": "query"}, "locale": {"type": "string", "location": "query"}, "metric": {"repeated": true, "type": "string", "location": "query"}, "maxResults": {"format": "int32", "maximum": "50000", "minimum": "0", "location": "query", "type": "integer"}, "filter": {"repeated": true, "type": "string", "location": "query"}, "currency": {"type": "string", "location": "query"}, "startIndex": {"format": "int32", "maximum": "5000", "minimum": "0", "location": "query", "type": "integer"}, "dimension": {"repeated": true, "type": "string", "location": "query"}}, "id": "adsensehost.reports.generate", "httpMethod": "GET", "path": "reports", "response": {"$ref": "AdsensehostReportsGenerateResponse"}}}}', true));
    $this->customchannels = new CustomchannelsServiceResource($this, $this->serviceName, 'customchannels', json_decode('{"methods": {"list": {"parameters": {"pageToken": {"type": "string", "location": "query"}, "adClientId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "int32", "maximum": "10000", "minimum": "0", "location": "query", "type": "integer"}}, "id": "adsensehost.customchannels.list", "httpMethod": "GET", "path": "adclients/{adClientId}/customchannels", "response": {"$ref": "CustomChannels"}}}}', true));

  }
}

class AdClient extends apiModel {
  public $productCode;
  public $kind;
  public $id;
  public $supportsReporting;
  public function setProductCode($productCode) {
    $this->productCode = $productCode;
  }
  public function getProductCode() {
    return $this->productCode;
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
  public function setSupportsReporting($supportsReporting) {
    $this->supportsReporting = $supportsReporting;
  }
  public function getSupportsReporting() {
    return $this->supportsReporting;
  }
}

class AdClients extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'AdClient';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $etag;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(AdClient) */ $items) {
    $this->assertIsArray($items, 'AdClient', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
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
}

class AdsensehostReportsGenerateResponse extends apiModel {
  public $rows;
  public $warnings;
  public $totals;
  protected $__headersType = 'AdsensehostReportsGenerateResponseHeaders';
  protected $__headersDataType = 'array';
  public $headers;
  public $totalMatchedRows;
  public $averages;
  public function setRows(/* array(string) */ $rows) {
    $this->assertIsArray($rows, 'string', __METHOD__);
    $this->rows = $rows;
  }
  public function getRows() {
    return $this->rows;
  }
  public function setWarnings(/* array(string) */ $warnings) {
    $this->assertIsArray($warnings, 'string', __METHOD__);
    $this->warnings = $warnings;
  }
  public function getWarnings() {
    return $this->warnings;
  }
  public function setTotals(/* array(string) */ $totals) {
    $this->assertIsArray($totals, 'string', __METHOD__);
    $this->totals = $totals;
  }
  public function getTotals() {
    return $this->totals;
  }
  public function setHeaders(/* array(AdsensehostReportsGenerateResponseHeaders) */ $headers) {
    $this->assertIsArray($headers, 'AdsensehostReportsGenerateResponseHeaders', __METHOD__);
    $this->headers = $headers;
  }
  public function getHeaders() {
    return $this->headers;
  }
  public function setTotalMatchedRows($totalMatchedRows) {
    $this->totalMatchedRows = $totalMatchedRows;
  }
  public function getTotalMatchedRows() {
    return $this->totalMatchedRows;
  }
  public function setAverages(/* array(string) */ $averages) {
    $this->assertIsArray($averages, 'string', __METHOD__);
    $this->averages = $averages;
  }
  public function getAverages() {
    return $this->averages;
  }
}

class AdsensehostReportsGenerateResponseHeaders extends apiModel {
  public $currency;
  public $type;
  public $name;
  public function setCurrency($currency) {
    $this->currency = $currency;
  }
  public function getCurrency() {
    return $this->currency;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class CustomChannel extends apiModel {
  public $kind;
  public $code;
  public $id;
  public $name;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setCode($code) {
    $this->code = $code;
  }
  public function getCode() {
    return $this->code;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class CustomChannels extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'CustomChannel';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $etag;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(CustomChannel) */ $items) {
    $this->assertIsArray($items, 'CustomChannel', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
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
}

class UrlChannel extends apiModel {
  public $kind;
  public $id;
  public $urlPattern;
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
  public function setUrlPattern($urlPattern) {
    $this->urlPattern = $urlPattern;
  }
  public function getUrlPattern() {
    return $this->urlPattern;
  }
}

class UrlChannels extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'UrlChannel';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $etag;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(UrlChannel) */ $items) {
    $this->assertIsArray($items, 'UrlChannel', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
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
}
