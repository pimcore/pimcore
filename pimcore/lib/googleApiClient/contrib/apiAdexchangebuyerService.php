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
   * The "directDeals" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangebuyerService = new apiAdexchangebuyerService(...);
   *   $directDeals = $adexchangebuyerService->directDeals;
   *  </code>
   */
  class DirectDealsServiceResource extends apiServiceResource {


    /**
     * Retrieves the authenticated user's list of direct deals. (directDeals.list)
     *
     * @return DirectDealsList
     */
    public function listDirectDeals($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new DirectDealsList($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets one direct deal by ID. (directDeals.get)
     *
     * @param string $id The direct deal id
     * @return DirectDeal
     */
    public function get($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new DirectDeal($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "accounts" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangebuyerService = new apiAdexchangebuyerService(...);
   *   $accounts = $adexchangebuyerService->accounts;
   *  </code>
   */
  class AccountsServiceResource extends apiServiceResource {


    /**
     * Updates an existing account. This method supports patch semantics. (accounts.patch)
     *
     * @param int $id The account id
     * @param Account $postBody
     * @return Account
     */
    public function patch($id, Account $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Account($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the authenticated user's list of accounts. (accounts.list)
     *
     * @return AccountsList
     */
    public function listAccounts($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new AccountsList($data);
      } else {
        return $data;
      }
    }

    /**
     * Updates an existing account. (accounts.update)
     *
     * @param int $id The account id
     * @param Account $postBody
     * @param array $optParams
     * @return Account
     */
    public function update($id, Account $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Account($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets one account by ID. (accounts.get)
     *
     * @param int $id The account id
     * @return Account
     */
    public function get($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Account($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "creatives" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangebuyerService = new apiAdexchangebuyerService(...);
   *   $creatives = $adexchangebuyerService->creatives;
   *  </code>
   */
  class CreativesServiceResource extends apiServiceResource {


    /**
     * Submit a new creative. (creatives.insert)
     *
     * @param Creative $postBody
     * @return Creative
     */
    public function insert(Creative $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Creative($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets the status for a single creative. (creatives.get)
     *
     * @param int $accountId The id for the account that will serve this creative.
     * @param string $buyerCreativeId The buyer-specific id for this creative.
     * @param string $adgroupId The adgroup this creative belongs to.
     * @return Creative
     */
    public function get($accountId, $buyerCreativeId, $adgroupId, $optParams = array()) {
      $params = array('accountId' => $accountId, 'buyerCreativeId' => $buyerCreativeId, 'adgroupId' => $adgroupId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Creative($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Adexchangebuyer (v1).
 *
 * <p>
 * Lets you manage your Ad Exchange Buyer account
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/ad-exchange/buyer-rest" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiAdexchangebuyerService extends apiService {
  public $directDeals;
  public $accounts;
  public $creatives;
  /**
   * Constructs the internal representation of the Adexchangebuyer service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/adexchangebuyer/v1/';
    $this->version = 'v1';
    $this->serviceName = 'adexchangebuyer';

    $apiClient->addService($this->serviceName, $this->version);
    $this->directDeals = new DirectDealsServiceResource($this, $this->serviceName, 'directDeals', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "id": "adexchangebuyer.directDeals.list", "httpMethod": "GET", "path": "directdeals", "response": {"$ref": "DirectDealsList"}}, "get": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "parameters": {"id": {"format": "int64", "required": true, "type": "string", "location": "path"}}, "id": "adexchangebuyer.directDeals.get", "httpMethod": "GET", "path": "directdeals/{id}", "response": {"$ref": "DirectDeal"}}}}', true));
    $this->accounts = new AccountsServiceResource($this, $this->serviceName, 'accounts', json_decode('{"methods": {"get": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "parameters": {"id": {"format": "int32", "required": true, "type": "integer", "location": "path"}}, "id": "adexchangebuyer.accounts.get", "httpMethod": "GET", "path": "accounts/{id}", "response": {"$ref": "Account"}}, "list": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "id": "adexchangebuyer.accounts.list", "httpMethod": "GET", "path": "accounts", "response": {"$ref": "AccountsList"}}, "update": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "parameters": {"id": {"format": "int32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Account"}, "id": "adexchangebuyer.accounts.update", "httpMethod": "PUT", "path": "accounts/{id}", "response": {"$ref": "Account"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "parameters": {"id": {"format": "int32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Account"}, "id": "adexchangebuyer.accounts.patch", "httpMethod": "PATCH", "path": "accounts/{id}", "response": {"$ref": "Account"}}}}', true));
    $this->creatives = new CreativesServiceResource($this, $this->serviceName, 'creatives', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "request": {"$ref": "Creative"}, "response": {"$ref": "Creative"}, "httpMethod": "POST", "path": "creatives", "id": "adexchangebuyer.creatives.insert"}, "get": {"scopes": ["https://www.googleapis.com/auth/adexchange.buyer"], "parameters": {"adgroupId": {"format": "int64", "required": true, "type": "string", "location": "query"}, "buyerCreativeId": {"required": true, "type": "string", "location": "path"}, "accountId": {"format": "int32", "required": true, "type": "integer", "location": "path"}}, "id": "adexchangebuyer.creatives.get", "httpMethod": "GET", "path": "creatives/{accountId}/{buyerCreativeId}", "response": {"$ref": "Creative"}}}}', true));

  }
}

class Account extends apiModel {
  public $kind;
  public $maximumTotalQps;
  protected $__bidderLocationType = 'AccountBidderLocation';
  protected $__bidderLocationDataType = 'array';
  public $bidderLocation;
  public $cookieMatchingNid;
  public $id;
  public $cookieMatchingUrl;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMaximumTotalQps($maximumTotalQps) {
    $this->maximumTotalQps = $maximumTotalQps;
  }
  public function getMaximumTotalQps() {
    return $this->maximumTotalQps;
  }
  public function setBidderLocation(/* array(AccountBidderLocation) */ $bidderLocation) {
    $this->assertIsArray($bidderLocation, 'AccountBidderLocation', __METHOD__);
    $this->bidderLocation = $bidderLocation;
  }
  public function getBidderLocation() {
    return $this->bidderLocation;
  }
  public function setCookieMatchingNid($cookieMatchingNid) {
    $this->cookieMatchingNid = $cookieMatchingNid;
  }
  public function getCookieMatchingNid() {
    return $this->cookieMatchingNid;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setCookieMatchingUrl($cookieMatchingUrl) {
    $this->cookieMatchingUrl = $cookieMatchingUrl;
  }
  public function getCookieMatchingUrl() {
    return $this->cookieMatchingUrl;
  }
}

class AccountBidderLocation extends apiModel {
  public $url;
  public $maximumQps;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setMaximumQps($maximumQps) {
    $this->maximumQps = $maximumQps;
  }
  public function getMaximumQps() {
    return $this->maximumQps;
  }
}

class AccountsList extends apiModel {
  protected $__itemsType = 'Account';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Account) */ $items) {
    $this->assertIsArray($items, 'Account', __METHOD__);
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
}

class Creative extends apiModel {
  public $productCategories;
  public $advertiserName;
  public $adgroupId;
  public $videoURL;
  public $width;
  public $attribute;
  public $kind;
  public $height;
  public $advertiserId;
  public $HTMLSnippet;
  public $status;
  public $buyerCreativeId;
  public $clickThroughUrl;
  public $vendorType;
  public $disapprovalReasons;
  public $sensitiveCategories;
  public $accountId;
  public function setProductCategories(/* array(int) */ $productCategories) {
    $this->assertIsArray($productCategories, 'int', __METHOD__);
    $this->productCategories = $productCategories;
  }
  public function getProductCategories() {
    return $this->productCategories;
  }
  public function setAdvertiserName($advertiserName) {
    $this->advertiserName = $advertiserName;
  }
  public function getAdvertiserName() {
    return $this->advertiserName;
  }
  public function setAdgroupId($adgroupId) {
    $this->adgroupId = $adgroupId;
  }
  public function getAdgroupId() {
    return $this->adgroupId;
  }
  public function setVideoURL($videoURL) {
    $this->videoURL = $videoURL;
  }
  public function getVideoURL() {
    return $this->videoURL;
  }
  public function setWidth($width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
  public function setAttribute(/* array(int) */ $attribute) {
    $this->assertIsArray($attribute, 'int', __METHOD__);
    $this->attribute = $attribute;
  }
  public function getAttribute() {
    return $this->attribute;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setHeight($height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setAdvertiserId(/* array(string) */ $advertiserId) {
    $this->assertIsArray($advertiserId, 'string', __METHOD__);
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId() {
    return $this->advertiserId;
  }
  public function setHTMLSnippet($HTMLSnippet) {
    $this->HTMLSnippet = $HTMLSnippet;
  }
  public function getHTMLSnippet() {
    return $this->HTMLSnippet;
  }
  public function setStatus($status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setBuyerCreativeId($buyerCreativeId) {
    $this->buyerCreativeId = $buyerCreativeId;
  }
  public function getBuyerCreativeId() {
    return $this->buyerCreativeId;
  }
  public function setClickThroughUrl(/* array(string) */ $clickThroughUrl) {
    $this->assertIsArray($clickThroughUrl, 'string', __METHOD__);
    $this->clickThroughUrl = $clickThroughUrl;
  }
  public function getClickThroughUrl() {
    return $this->clickThroughUrl;
  }
  public function setVendorType(/* array(int) */ $vendorType) {
    $this->assertIsArray($vendorType, 'int', __METHOD__);
    $this->vendorType = $vendorType;
  }
  public function getVendorType() {
    return $this->vendorType;
  }
  public function setDisapprovalReasons(/* array(string) */ $disapprovalReasons) {
    $this->assertIsArray($disapprovalReasons, 'string', __METHOD__);
    $this->disapprovalReasons = $disapprovalReasons;
  }
  public function getDisapprovalReasons() {
    return $this->disapprovalReasons;
  }
  public function setSensitiveCategories(/* array(int) */ $sensitiveCategories) {
    $this->assertIsArray($sensitiveCategories, 'int', __METHOD__);
    $this->sensitiveCategories = $sensitiveCategories;
  }
  public function getSensitiveCategories() {
    return $this->sensitiveCategories;
  }
  public function setAccountId($accountId) {
    $this->accountId = $accountId;
  }
  public function getAccountId() {
    return $this->accountId;
  }
}

class DirectDeal extends apiModel {
  public $advertiser;
  public $kind;
  public $currencyCode;
  public $fixedCpm;
  public $startTime;
  public $endTime;
  public $sellerNetwork;
  public $id;
  public $accountId;
  public function setAdvertiser($advertiser) {
    $this->advertiser = $advertiser;
  }
  public function getAdvertiser() {
    return $this->advertiser;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setCurrencyCode($currencyCode) {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode() {
    return $this->currencyCode;
  }
  public function setFixedCpm($fixedCpm) {
    $this->fixedCpm = $fixedCpm;
  }
  public function getFixedCpm() {
    return $this->fixedCpm;
  }
  public function setStartTime($startTime) {
    $this->startTime = $startTime;
  }
  public function getStartTime() {
    return $this->startTime;
  }
  public function setEndTime($endTime) {
    $this->endTime = $endTime;
  }
  public function getEndTime() {
    return $this->endTime;
  }
  public function setSellerNetwork($sellerNetwork) {
    $this->sellerNetwork = $sellerNetwork;
  }
  public function getSellerNetwork() {
    return $this->sellerNetwork;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setAccountId($accountId) {
    $this->accountId = $accountId;
  }
  public function getAccountId() {
    return $this->accountId;
  }
}

class DirectDealsList extends apiModel {
  public $kind;
  protected $__directDealsType = 'DirectDeal';
  protected $__directDealsDataType = 'array';
  public $directDeals;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setDirectDeals(/* array(DirectDeal) */ $directDeals) {
    $this->assertIsArray($directDeals, 'DirectDeal', __METHOD__);
    $this->directDeals = $directDeals;
  }
  public function getDirectDeals() {
    return $this->directDeals;
  }
}
