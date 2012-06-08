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
   * The "url" collection of methods.
   * Typical usage is:
   *  <code>
   *   $urlshortenerService = new apiUrlshortenerService(...);
   *   $url = $urlshortenerService->url;
   *  </code>
   */
  class UrlServiceResource extends apiServiceResource {


    /**
     * Creates a new short URL. (url.insert)
     *
     * @param Url $postBody
     * @return Url
     */
    public function insert(Url $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Url($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves a list of URLs shortened by a user. (url.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string start-token Token for requesting successive pages of results.
     * @opt_param string projection Additional information to return.
     * @return UrlHistory
     */
    public function listUrl($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new UrlHistory($data);
      } else {
        return $data;
      }
    }
    /**
     * Expands a short URL or gets creation time and analytics. (url.get)
     *
     * @param string $shortUrl The short URL, including the protocol.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string projection Additional information to return.
     * @return Url
     */
    public function get($shortUrl, $optParams = array()) {
      $params = array('shortUrl' => $shortUrl);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Url($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Urlshortener (v1).
 *
 * <p>
 * Lets you create, inspect, and manage goo.gl short URLs
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/urlshortener/v1/getting_started.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiUrlshortenerService extends apiService {
  public $url;
  /**
   * Constructs the internal representation of the Urlshortener service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/urlshortener/v1/';
    $this->version = 'v1';
    $this->serviceName = 'urlshortener';

    $apiClient->addService($this->serviceName, $this->version);
    $this->url = new UrlServiceResource($this, $this->serviceName, 'url', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/urlshortener"], "request": {"$ref": "Url"}, "response": {"$ref": "Url"}, "httpMethod": "POST", "path": "url", "id": "urlshortener.url.insert"}, "list": {"scopes": ["https://www.googleapis.com/auth/urlshortener"], "parameters": {"start-token": {"type": "string", "location": "query"}, "projection": {"enum": ["ANALYTICS_CLICKS", "FULL"], "type": "string", "location": "query"}}, "response": {"$ref": "UrlHistory"}, "httpMethod": "GET", "path": "url/history", "id": "urlshortener.url.list"}, "get": {"parameters": {"shortUrl": {"required": true, "type": "string", "location": "query"}, "projection": {"enum": ["ANALYTICS_CLICKS", "ANALYTICS_TOP_STRINGS", "FULL"], "type": "string", "location": "query"}}, "id": "urlshortener.url.get", "httpMethod": "GET", "path": "url", "response": {"$ref": "Url"}}}}', true));

  }
}

class AnalyticsSnapshot extends apiModel {
  public $shortUrlClicks;
  protected $__countriesType = 'StringCount';
  protected $__countriesDataType = 'array';
  public $countries;
  protected $__platformsType = 'StringCount';
  protected $__platformsDataType = 'array';
  public $platforms;
  protected $__browsersType = 'StringCount';
  protected $__browsersDataType = 'array';
  public $browsers;
  protected $__referrersType = 'StringCount';
  protected $__referrersDataType = 'array';
  public $referrers;
  public $longUrlClicks;
  public function setShortUrlClicks($shortUrlClicks) {
    $this->shortUrlClicks = $shortUrlClicks;
  }
  public function getShortUrlClicks() {
    return $this->shortUrlClicks;
  }
  public function setCountries(/* array(StringCount) */ $countries) {
    $this->assertIsArray($countries, 'StringCount', __METHOD__);
    $this->countries = $countries;
  }
  public function getCountries() {
    return $this->countries;
  }
  public function setPlatforms(/* array(StringCount) */ $platforms) {
    $this->assertIsArray($platforms, 'StringCount', __METHOD__);
    $this->platforms = $platforms;
  }
  public function getPlatforms() {
    return $this->platforms;
  }
  public function setBrowsers(/* array(StringCount) */ $browsers) {
    $this->assertIsArray($browsers, 'StringCount', __METHOD__);
    $this->browsers = $browsers;
  }
  public function getBrowsers() {
    return $this->browsers;
  }
  public function setReferrers(/* array(StringCount) */ $referrers) {
    $this->assertIsArray($referrers, 'StringCount', __METHOD__);
    $this->referrers = $referrers;
  }
  public function getReferrers() {
    return $this->referrers;
  }
  public function setLongUrlClicks($longUrlClicks) {
    $this->longUrlClicks = $longUrlClicks;
  }
  public function getLongUrlClicks() {
    return $this->longUrlClicks;
  }
}

class AnalyticsSummary extends apiModel {
  protected $__weekType = 'AnalyticsSnapshot';
  protected $__weekDataType = '';
  public $week;
  protected $__allTimeType = 'AnalyticsSnapshot';
  protected $__allTimeDataType = '';
  public $allTime;
  protected $__twoHoursType = 'AnalyticsSnapshot';
  protected $__twoHoursDataType = '';
  public $twoHours;
  protected $__dayType = 'AnalyticsSnapshot';
  protected $__dayDataType = '';
  public $day;
  protected $__monthType = 'AnalyticsSnapshot';
  protected $__monthDataType = '';
  public $month;
  public function setWeek(AnalyticsSnapshot $week) {
    $this->week = $week;
  }
  public function getWeek() {
    return $this->week;
  }
  public function setAllTime(AnalyticsSnapshot $allTime) {
    $this->allTime = $allTime;
  }
  public function getAllTime() {
    return $this->allTime;
  }
  public function setTwoHours(AnalyticsSnapshot $twoHours) {
    $this->twoHours = $twoHours;
  }
  public function getTwoHours() {
    return $this->twoHours;
  }
  public function setDay(AnalyticsSnapshot $day) {
    $this->day = $day;
  }
  public function getDay() {
    return $this->day;
  }
  public function setMonth(AnalyticsSnapshot $month) {
    $this->month = $month;
  }
  public function getMonth() {
    return $this->month;
  }
}

class StringCount extends apiModel {
  public $count;
  public $id;
  public function setCount($count) {
    $this->count = $count;
  }
  public function getCount() {
    return $this->count;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class Url extends apiModel {
  public $status;
  public $kind;
  public $created;
  protected $__analyticsType = 'AnalyticsSummary';
  protected $__analyticsDataType = '';
  public $analytics;
  public $longUrl;
  public $id;
  public function setStatus($status) {
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
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setAnalytics(AnalyticsSummary $analytics) {
    $this->analytics = $analytics;
  }
  public function getAnalytics() {
    return $this->analytics;
  }
  public function setLongUrl($longUrl) {
    $this->longUrl = $longUrl;
  }
  public function getLongUrl() {
    return $this->longUrl;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class UrlHistory extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Url';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $itemsPerPage;
  public $totalItems;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Url) */ $items) {
    $this->assertIsArray($items, 'Url', __METHOD__);
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
  public function setItemsPerPage($itemsPerPage) {
    $this->itemsPerPage = $itemsPerPage;
  }
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}
