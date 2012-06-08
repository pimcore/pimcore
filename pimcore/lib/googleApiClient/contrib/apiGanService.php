<?php
/*
 * Copyright (c) 2010 Google Inc.
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

require_once 'service/apiModel.php';
require_once 'service/apiService.php';
require_once 'service/apiServiceRequest.php';


  /**
   * The "advertisers" collection of methods.
   * Typical usage is:
   *  <code>
   *   $ganService = new apiGanService(...);
   *   $advertisers = $ganService->advertisers;
   *  </code>
   */
  class AdvertisersServiceResource extends apiServiceResource {


    /**
     * Retrieves data about all advertisers that the requesting advertiser/publisher has access to.
     * (advertisers.list)
     *
     * @param string $role The role of the requester. Valid values: 'advertisers' or 'publishers'.
     * @param string $roleId The ID of the requesting advertiser or publisher.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string relationshipStatus Filters out all advertisers for which do not have the given relationship status with the requesting publisher.
     * @opt_param double minSevenDayEpc Filters out all advertisers that have a seven day EPC average lower than the given value (inclusive). Min value: 0.0. Optional.
     * @opt_param string advertiserCategory Caret(^) delimted list of advertiser categories. Valid categories are defined here: http://www.google.com/support/affiliatenetwork/advertiser/bin/answer.py?hl=en=107581. Filters out all advertisers not in one of the given advertiser categories. Optional.
     * @opt_param double minNinetyDayEpc Filters out all advertisers that have a ninety day EPC average lower than the given value (inclusive). Min value: 0.0. Optional.
     * @opt_param string pageToken The value of 'nextPageToken' from the previous page. Optional.
     * @opt_param string maxResults Max number of items to return in this page. Optional. Defaults to 20.
     * @opt_param int minPayoutRank A value between 1 and 4, where 1 represents the quartile of advertisers with the lowest ranks and 4 represents the quartile of advertisers with the highest ranks. Filters out all advertisers with a lower rank than the given quartile. For example if a 2 was given only advertisers with a payout rank of 25 or higher would be included. Optional.
     * @return Advertisers
     */
    public function listAdvertisers($role, $roleId, $optParams = array()) {
      $params = array('role' => $role, 'roleId' => $roleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Advertisers($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves data about a single advertiser if that the requesting advertiser/publisher has access
     * to it. Only publishers can lookup advertisers. Advertisers can request information about
     * themselves by omitting the advertiserId query parameter. (advertisers.get)
     *
     * @param string $role The role of the requester. Valid values: 'advertisers' or 'publishers'.
     * @param string $roleId The ID of the requesting advertiser or publisher.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string advertiserId The ID of the advertiser to look up. Optional.
     * @return Advertiser
     */
    public function get($role, $roleId, $optParams = array()) {
      $params = array('role' => $role, 'roleId' => $roleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Advertiser($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "ccOffers" collection of methods.
   * Typical usage is:
   *  <code>
   *   $ganService = new apiGanService(...);
   *   $ccOffers = $ganService->ccOffers;
   *  </code>
   */
  class CcOffersServiceResource extends apiServiceResource {


    /**
     * Retrieves credit card offers for the given publisher. (ccOffers.list)
     *
     * @param string $publisher The ID of the publisher in question.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string advertiser The advertiser ID of a card issuer whose offers to include. Optional, may be repeated.
     * @opt_param string projection The set of fields to return.
     * @return CcOffers
     */
    public function listCcOffers($publisher, $optParams = array()) {
      $params = array('publisher' => $publisher);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new CcOffers($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "events" collection of methods.
   * Typical usage is:
   *  <code>
   *   $ganService = new apiGanService(...);
   *   $events = $ganService->events;
   *  </code>
   */
  class EventsServiceResource extends apiServiceResource {


    /**
     * Retrieves event data for a given advertiser/publisher. (events.list)
     *
     * @param string $role The role of the requester. Valid values: 'advertisers' or 'publishers'.
     * @param string $roleId The ID of the requesting advertiser or publisher.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string orderId Caret(^) delimited list of order IDs. Filters out all events that do not reference one of the given order IDs. Optional.
     * @opt_param string sku Caret(^) delimited list of SKUs. Filters out all events that do not reference one of the given SKU. Optional.
     * @opt_param string eventDateMax Filters out all events later than given date. Optional. Defaults to 24 hours after eventMin.
     * @opt_param string type Filters out all events that are not of the given type. Valid values: 'action', 'transaction', 'charge'. Optional.
     * @opt_param string linkId Caret(^) delimited list of link IDs. Filters out all events that do not reference one of the given link IDs. Optional.
     * @opt_param string status Filters out all events that do not have the given status. Valid values: 'active', 'canceled'. Optional.
     * @opt_param string eventDateMin Filters out all events earlier than given date. Optional. Defaults to 24 hours from current date/time.
     * @opt_param string memberId Caret(^) delimited list of member IDs. Filters out all events that do not reference one of the given member IDs. Optional.
     * @opt_param string maxResults Max number of offers to return in this page. Optional. Defaults to 20.
     * @opt_param string advertiserId Caret(^) delimited list of advertiser IDs. Filters out all events that do not reference one of the given advertiser IDs. Only used when under publishers role. Optional.
     * @opt_param string pageToken The value of 'nextPageToken' from the previous page. Optional.
     * @opt_param string productCategory Caret(^) delimited list of product categories. Filters out all events that do not reference a product in one of the given product categories. Optional.
     * @opt_param string chargeType Filters out all charge events that are not of the given charge type. Valid values: 'other', 'slotting_fee', 'monthly_minimum', 'tier_bonus', 'credit', 'debit'. Optional.
     * @opt_param string publisherId Caret(^) delimited list of publisher IDs. Filters out all events that do not reference one of the given publishers IDs. Only used when under advertiser role. Optional.
     * @return Events
     */
    public function listEvents($role, $roleId, $optParams = array()) {
      $params = array('role' => $role, 'roleId' => $roleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Events($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "publishers" collection of methods.
   * Typical usage is:
   *  <code>
   *   $ganService = new apiGanService(...);
   *   $publishers = $ganService->publishers;
   *  </code>
   */
  class PublishersServiceResource extends apiServiceResource {


    /**
     * Retrieves data about all publishers that the requesting advertiser/publisher has access to.
     * (publishers.list)
     *
     * @param string $role The role of the requester. Valid values: 'advertisers' or 'publishers'.
     * @param string $roleId The ID of the requesting advertiser or publisher.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string publisherCategory Caret(^) delimted list of publisher categories. Valid categories: (unclassified|community_and_content|shopping_and_promotion|loyalty_and_rewards|network|search_specialist|comparison_shopping|email). Filters out all publishers not in one of the given advertiser categories. Optional.
     * @opt_param string relationshipStatus Filters out all publishers for which do not have the given relationship status with the requesting publisher.
     * @opt_param double minSevenDayEpc Filters out all publishers that have a seven day EPC average lower than the given value (inclusive). Min value 0.0. Optional.
     * @opt_param double minNinetyDayEpc Filters out all publishers that have a ninety day EPC average lower than the given value (inclusive). Min value: 0.0. Optional.
     * @opt_param string pageToken The value of 'nextPageToken' from the previous page. Optional.
     * @opt_param string maxResults Max number of items to return in this page. Optional. Defaults to 20.
     * @opt_param int minPayoutRank A value between 1 and 4, where 1 represents the quartile of publishers with the lowest ranks and 4 represents the quartile of publishers with the highest ranks. Filters out all publishers with a lower rank than the given quartile. For example if a 2 was given only publishers with a payout rank of 25 or higher would be included. Optional.
     * @return Publishers
     */
    public function listPublishers($role, $roleId, $optParams = array()) {
      $params = array('role' => $role, 'roleId' => $roleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Publishers($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves data about a single advertiser if that the requesting advertiser/publisher has access
     * to it. Only advertisers can look up publishers. Publishers can request information about
     * themselves by omitting the publisherId query parameter. (publishers.get)
     *
     * @param string $role The role of the requester. Valid values: 'advertisers' or 'publishers'.
     * @param string $roleId The ID of the requesting advertiser or publisher.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string publisherId The ID of the publisher to look up. Optional.
     * @return Publisher
     */
    public function get($role, $roleId, $optParams = array()) {
      $params = array('role' => $role, 'roleId' => $roleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Publisher($data);
      } else {
        return $data;
      }
    }
  }



/**
 * Service definition for Gan (v1beta1).
 *
 * <p>
 * Lets you have programmatic access to your Google Affiliate Network data
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://code.google.com/apis/gan/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiGanService extends apiService {
  public $advertisers;
  public $ccOffers;
  public $events;
  public $publishers;
  /**
   * Constructs the internal representation of the Gan service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->rpcPath = '/rpc';
    $this->restBasePath = '/gan/v1beta1/';
    $this->version = 'v1beta1';
    $this->serviceName = 'gan';

    $apiClient->addService($this->serviceName, $this->version);
    $this->advertisers = new AdvertisersServiceResource($this, $this->serviceName, 'advertisers', json_decode('{"methods": {"list": {"parameters": {"relationshipStatus": {"enum": ["approved", "available", "deactivated", "declined", "pending"], "type": "string", "location": "query"}, "minSevenDayEpc": {"format": "double", "type": "number", "location": "query"}, "advertiserCategory": {"type": "string", "location": "query"}, "minNinetyDayEpc": {"format": "double", "type": "number", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "role": {"required": true, "enum": ["advertisers", "publishers"], "location": "path", "type": "string"}, "maxResults": {"format": "uint32", "maximum": "100", "minimum": "0", "location": "query", "type": "integer"}, "roleId": {"required": true, "type": "string", "location": "path"}, "minPayoutRank": {"format": "int32", "maximum": "4", "minimum": "1", "location": "query", "type": "integer"}}, "id": "gan.advertisers.list", "httpMethod": "GET", "path": "{role}/{roleId}/advertisers", "response": {"$ref": "Advertisers"}}, "get": {"parameters": {"advertiserId": {"type": "string", "location": "query"}, "roleId": {"required": true, "type": "string", "location": "path"}, "role": {"required": true, "enum": ["advertisers", "publishers"], "location": "path", "type": "string"}}, "id": "gan.advertisers.get", "httpMethod": "GET", "path": "{role}/{roleId}/advertiser", "response": {"$ref": "Advertiser"}}}}', true));
    $this->ccOffers = new CcOffersServiceResource($this, $this->serviceName, 'ccOffers', json_decode('{"methods": {"list": {"parameters": {"advertiser": {"repeated": true, "type": "string", "location": "query"}, "projection": {"enum": ["full", "summary"], "type": "string", "location": "query"}, "publisher": {"required": true, "type": "string", "location": "path"}}, "id": "gan.ccOffers.list", "httpMethod": "GET", "path": "publishers/{publisher}/ccOffers", "response": {"$ref": "CcOffers"}}}}', true));
    $this->events = new EventsServiceResource($this, $this->serviceName, 'events', json_decode('{"methods": {"list": {"parameters": {"orderId": {"type": "string", "location": "query"}, "sku": {"type": "string", "location": "query"}, "eventDateMax": {"type": "string", "location": "query"}, "linkId": {"type": "string", "location": "query"}, "eventDateMin": {"type": "string", "location": "query"}, "memberId": {"type": "string", "location": "query"}, "maxResults": {"format": "uint32", "maximum": "100", "minimum": "0", "location": "query", "type": "integer"}, "advertiserId": {"type": "string", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "publisherId": {"type": "string", "location": "query"}, "status": {"enum": ["active", "canceled"], "type": "string", "location": "query"}, "productCategory": {"type": "string", "location": "query"}, "chargeType": {"enum": ["credit", "debit", "monthly_minimum", "other", "slotting_fee", "tier_bonus"], "type": "string", "location": "query"}, "roleId": {"required": true, "type": "string", "location": "path"}, "role": {"required": true, "enum": ["advertisers", "publishers"], "location": "path", "type": "string"}, "type": {"enum": ["action", "charge", "transaction"], "type": "string", "location": "query"}}, "id": "gan.events.list", "httpMethod": "GET", "path": "{role}/{roleId}/events", "response": {"$ref": "Events"}}}}', true));
    $this->publishers = new PublishersServiceResource($this, $this->serviceName, 'publishers', json_decode('{"methods": {"list": {"parameters": {"publisherCategory": {"type": "string", "location": "query"}, "relationshipStatus": {"enum": ["approved", "available", "deactivated", "declined", "pending"], "type": "string", "location": "query"}, "minSevenDayEpc": {"format": "double", "type": "number", "location": "query"}, "minNinetyDayEpc": {"format": "double", "type": "number", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "role": {"required": true, "enum": ["advertisers", "publishers"], "location": "path", "type": "string"}, "maxResults": {"format": "uint32", "maximum": "100", "minimum": "0", "location": "query", "type": "integer"}, "roleId": {"required": true, "type": "string", "location": "path"}, "minPayoutRank": {"format": "int32", "maximum": "4", "minimum": "1", "location": "query", "type": "integer"}}, "id": "gan.publishers.list", "httpMethod": "GET", "path": "{role}/{roleId}/publishers", "response": {"$ref": "Publishers"}}, "get": {"parameters": {"publisherId": {"type": "string", "location": "query"}, "role": {"required": true, "enum": ["advertisers", "publishers"], "location": "path", "type": "string"}, "roleId": {"required": true, "type": "string", "location": "path"}}, "id": "gan.publishers.get", "httpMethod": "GET", "path": "{role}/{roleId}/publisher", "response": {"$ref": "Publisher"}}}}', true));
  }
}

class Advertiser extends apiModel {
  public $category;
  public $productFeedsEnabled;
  public $kind;
  public $siteUrl;
  public $contactPhone;
  public $description;
  public $payoutRank;
  protected $__epcSevenDayAverageType = 'Money';
  protected $__epcSevenDayAverageDataType = '';
  public $epcSevenDayAverage;
  public $commissionDuration;
  public $status;
  protected $__epcNinetyDayAverageType = 'Money';
  protected $__epcNinetyDayAverageDataType = '';
  public $epcNinetyDayAverage;
  public $contactEmail;
  protected $__itemType = 'Advertiser';
  protected $__itemDataType = '';
  public $item;
  public $joinDate;
  public $logoUrl;
  public $id;
  public $name;
  public function setCategory($category) {
    $this->category = $category;
  }
  public function getCategory() {
    return $this->category;
  }
  public function setProductFeedsEnabled($productFeedsEnabled) {
    $this->productFeedsEnabled = $productFeedsEnabled;
  }
  public function getProductFeedsEnabled() {
    return $this->productFeedsEnabled;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setSiteUrl($siteUrl) {
    $this->siteUrl = $siteUrl;
  }
  public function getSiteUrl() {
    return $this->siteUrl;
  }
  public function setContactPhone($contactPhone) {
    $this->contactPhone = $contactPhone;
  }
  public function getContactPhone() {
    return $this->contactPhone;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setPayoutRank($payoutRank) {
    $this->payoutRank = $payoutRank;
  }
  public function getPayoutRank() {
    return $this->payoutRank;
  }
  public function setEpcSevenDayAverage(Money $epcSevenDayAverage) {
    $this->epcSevenDayAverage = $epcSevenDayAverage;
  }
  public function getEpcSevenDayAverage() {
    return $this->epcSevenDayAverage;
  }
  public function setCommissionDuration($commissionDuration) {
    $this->commissionDuration = $commissionDuration;
  }
  public function getCommissionDuration() {
    return $this->commissionDuration;
  }
  public function setStatus($status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setEpcNinetyDayAverage(Money $epcNinetyDayAverage) {
    $this->epcNinetyDayAverage = $epcNinetyDayAverage;
  }
  public function getEpcNinetyDayAverage() {
    return $this->epcNinetyDayAverage;
  }
  public function setContactEmail($contactEmail) {
    $this->contactEmail = $contactEmail;
  }
  public function getContactEmail() {
    return $this->contactEmail;
  }
  public function setItem(Advertiser $item) {
    $this->item = $item;
  }
  public function getItem() {
    return $this->item;
  }
  public function setJoinDate($joinDate) {
    $this->joinDate = $joinDate;
  }
  public function getJoinDate() {
    return $this->joinDate;
  }
  public function setLogoUrl($logoUrl) {
    $this->logoUrl = $logoUrl;
  }
  public function getLogoUrl() {
    return $this->logoUrl;
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

class Advertisers extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Advertiser';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Advertiser) */ $items) {
    $this->assertIsArray($items, 'Advertiser', __METHOD__);
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

class CcOffer extends apiModel {
  public $rewardsHaveBlackoutDates;
  public $introPurchasePeriodLength;
  public $introBalanceTransferRate;
  public $cardBenefits;
  public $introBalanceTransferAprDisplay;
  public $issuer;
  public $introBalanceTransferFeeRate;
  public $cashAdvanceFeeDisplay;
  public $annualFeeDisplay;
  public $minimumFinanceCharge;
  public $balanceTransferFeeDisplay;
  public $landingPageUrl;
  public $introPurchaseRate;
  public $cashAdvanceAprDisplay;
  public $maxCashAdvanceRate;
  public $firstYearAnnualFee;
  public $introBalanceTransferPeriodUnits;
  public $variableRatesUpdateFrequency;
  public $overLimitFee;
  public $rewardsExpire;
  public $additionalCardBenefits;
  public $ageMinimum;
  public $balanceTransferAprDisplay;
  public $introBalanceTransferPeriodLength;
  public $network;
  public $introPurchaseRateType;
  public $introAprDisplay;
  public $flightAccidentInsurance;
  public $annualRewardMaximum;
  public $disclaimer;
  public $creditLimitMin;
  public $creditLimitMax;
  public $gracePeriodDisplay;
  public $cashAdvanceFeeAmount;
  public $travelInsurance;
  public $existingCustomerOnly;
  public $initialSetupAndProcessingFee;
  public $issuerId;
  public $rewardUnit;
  public $prohibitedCategories;
  protected $__defaultFeesType = 'CcOfferDefaultFees';
  protected $__defaultFeesDataType = 'array';
  public $defaultFees;
  public $introPurchasePeriodUnits;
  public $trackingUrl;
  public $latePaymentFee;
  public $statementCopyFee;
  public $variableRatesLastUpdated;
  public $minBalanceTransferRate;
  public $emergencyInsurance;
  public $introPurchasePeriodEndDate;
  public $cardName;
  public $returnedPaymentFee;
  public $cashAdvanceAdditionalDetails;
  public $cashAdvanceLimit;
  public $balanceTransferFeeRate;
  public $balanceTransferRateType;
  protected $__rewardsType = 'CcOfferRewards';
  protected $__rewardsDataType = 'array';
  public $rewards;
  public $extendedWarranty;
  public $carRentalInsurance;
  public $cashAdvanceFeeRate;
  public $rewardPartner;
  public $foreignCurrencyTransactionFee;
  public $kind;
  public $introBalanceTransferFeeAmount;
  public $creditRatingDisplay;
  public $additionalCardHolderFee;
  public $purchaseRateType;
  public $cashAdvanceRateType;
  protected $__bonusRewardsType = 'CcOfferBonusRewards';
  protected $__bonusRewardsDataType = 'array';
  public $bonusRewards;
  public $balanceTransferLimit;
  public $luggageInsurance;
  public $minCashAdvanceRate;
  public $offerId;
  public $minPurchaseRate;
  public $offersImmediateCashReward;
  public $fraudLiability;
  public $cardType;
  public $approvedCategories;
  public $annualFee;
  public $maxBalanceTransferRate;
  public $maxPurchaseRate;
  public $issuerWebsite;
  public $introBalanceTransferRateType;
  public $aprDisplay;
  public $imageUrl;
  public $ageMinimumDetails;
  public $balanceTransferFeeAmount;
  public $introBalanceTransferPeriodEndDate;
  public function setRewardsHaveBlackoutDates($rewardsHaveBlackoutDates) {
    $this->rewardsHaveBlackoutDates = $rewardsHaveBlackoutDates;
  }
  public function getRewardsHaveBlackoutDates() {
    return $this->rewardsHaveBlackoutDates;
  }
  public function setIntroPurchasePeriodLength($introPurchasePeriodLength) {
    $this->introPurchasePeriodLength = $introPurchasePeriodLength;
  }
  public function getIntroPurchasePeriodLength() {
    return $this->introPurchasePeriodLength;
  }
  public function setIntroBalanceTransferRate($introBalanceTransferRate) {
    $this->introBalanceTransferRate = $introBalanceTransferRate;
  }
  public function getIntroBalanceTransferRate() {
    return $this->introBalanceTransferRate;
  }
  public function setCardBenefits(/* array(string) */ $cardBenefits) {
    $this->assertIsArray($cardBenefits, 'string', __METHOD__);
    $this->cardBenefits = $cardBenefits;
  }
  public function getCardBenefits() {
    return $this->cardBenefits;
  }
  public function setIntroBalanceTransferAprDisplay($introBalanceTransferAprDisplay) {
    $this->introBalanceTransferAprDisplay = $introBalanceTransferAprDisplay;
  }
  public function getIntroBalanceTransferAprDisplay() {
    return $this->introBalanceTransferAprDisplay;
  }
  public function setIssuer($issuer) {
    $this->issuer = $issuer;
  }
  public function getIssuer() {
    return $this->issuer;
  }
  public function setIntroBalanceTransferFeeRate($introBalanceTransferFeeRate) {
    $this->introBalanceTransferFeeRate = $introBalanceTransferFeeRate;
  }
  public function getIntroBalanceTransferFeeRate() {
    return $this->introBalanceTransferFeeRate;
  }
  public function setCashAdvanceFeeDisplay($cashAdvanceFeeDisplay) {
    $this->cashAdvanceFeeDisplay = $cashAdvanceFeeDisplay;
  }
  public function getCashAdvanceFeeDisplay() {
    return $this->cashAdvanceFeeDisplay;
  }
  public function setAnnualFeeDisplay($annualFeeDisplay) {
    $this->annualFeeDisplay = $annualFeeDisplay;
  }
  public function getAnnualFeeDisplay() {
    return $this->annualFeeDisplay;
  }
  public function setMinimumFinanceCharge($minimumFinanceCharge) {
    $this->minimumFinanceCharge = $minimumFinanceCharge;
  }
  public function getMinimumFinanceCharge() {
    return $this->minimumFinanceCharge;
  }
  public function setBalanceTransferFeeDisplay($balanceTransferFeeDisplay) {
    $this->balanceTransferFeeDisplay = $balanceTransferFeeDisplay;
  }
  public function getBalanceTransferFeeDisplay() {
    return $this->balanceTransferFeeDisplay;
  }
  public function setLandingPageUrl($landingPageUrl) {
    $this->landingPageUrl = $landingPageUrl;
  }
  public function getLandingPageUrl() {
    return $this->landingPageUrl;
  }
  public function setIntroPurchaseRate($introPurchaseRate) {
    $this->introPurchaseRate = $introPurchaseRate;
  }
  public function getIntroPurchaseRate() {
    return $this->introPurchaseRate;
  }
  public function setCashAdvanceAprDisplay($cashAdvanceAprDisplay) {
    $this->cashAdvanceAprDisplay = $cashAdvanceAprDisplay;
  }
  public function getCashAdvanceAprDisplay() {
    return $this->cashAdvanceAprDisplay;
  }
  public function setMaxCashAdvanceRate($maxCashAdvanceRate) {
    $this->maxCashAdvanceRate = $maxCashAdvanceRate;
  }
  public function getMaxCashAdvanceRate() {
    return $this->maxCashAdvanceRate;
  }
  public function setFirstYearAnnualFee($firstYearAnnualFee) {
    $this->firstYearAnnualFee = $firstYearAnnualFee;
  }
  public function getFirstYearAnnualFee() {
    return $this->firstYearAnnualFee;
  }
  public function setIntroBalanceTransferPeriodUnits($introBalanceTransferPeriodUnits) {
    $this->introBalanceTransferPeriodUnits = $introBalanceTransferPeriodUnits;
  }
  public function getIntroBalanceTransferPeriodUnits() {
    return $this->introBalanceTransferPeriodUnits;
  }
  public function setVariableRatesUpdateFrequency($variableRatesUpdateFrequency) {
    $this->variableRatesUpdateFrequency = $variableRatesUpdateFrequency;
  }
  public function getVariableRatesUpdateFrequency() {
    return $this->variableRatesUpdateFrequency;
  }
  public function setOverLimitFee($overLimitFee) {
    $this->overLimitFee = $overLimitFee;
  }
  public function getOverLimitFee() {
    return $this->overLimitFee;
  }
  public function setRewardsExpire($rewardsExpire) {
    $this->rewardsExpire = $rewardsExpire;
  }
  public function getRewardsExpire() {
    return $this->rewardsExpire;
  }
  public function setAdditionalCardBenefits(/* array(string) */ $additionalCardBenefits) {
    $this->assertIsArray($additionalCardBenefits, 'string', __METHOD__);
    $this->additionalCardBenefits = $additionalCardBenefits;
  }
  public function getAdditionalCardBenefits() {
    return $this->additionalCardBenefits;
  }
  public function setAgeMinimum($ageMinimum) {
    $this->ageMinimum = $ageMinimum;
  }
  public function getAgeMinimum() {
    return $this->ageMinimum;
  }
  public function setBalanceTransferAprDisplay($balanceTransferAprDisplay) {
    $this->balanceTransferAprDisplay = $balanceTransferAprDisplay;
  }
  public function getBalanceTransferAprDisplay() {
    return $this->balanceTransferAprDisplay;
  }
  public function setIntroBalanceTransferPeriodLength($introBalanceTransferPeriodLength) {
    $this->introBalanceTransferPeriodLength = $introBalanceTransferPeriodLength;
  }
  public function getIntroBalanceTransferPeriodLength() {
    return $this->introBalanceTransferPeriodLength;
  }
  public function setNetwork($network) {
    $this->network = $network;
  }
  public function getNetwork() {
    return $this->network;
  }
  public function setIntroPurchaseRateType($introPurchaseRateType) {
    $this->introPurchaseRateType = $introPurchaseRateType;
  }
  public function getIntroPurchaseRateType() {
    return $this->introPurchaseRateType;
  }
  public function setIntroAprDisplay($introAprDisplay) {
    $this->introAprDisplay = $introAprDisplay;
  }
  public function getIntroAprDisplay() {
    return $this->introAprDisplay;
  }
  public function setFlightAccidentInsurance($flightAccidentInsurance) {
    $this->flightAccidentInsurance = $flightAccidentInsurance;
  }
  public function getFlightAccidentInsurance() {
    return $this->flightAccidentInsurance;
  }
  public function setAnnualRewardMaximum($annualRewardMaximum) {
    $this->annualRewardMaximum = $annualRewardMaximum;
  }
  public function getAnnualRewardMaximum() {
    return $this->annualRewardMaximum;
  }
  public function setDisclaimer($disclaimer) {
    $this->disclaimer = $disclaimer;
  }
  public function getDisclaimer() {
    return $this->disclaimer;
  }
  public function setCreditLimitMin($creditLimitMin) {
    $this->creditLimitMin = $creditLimitMin;
  }
  public function getCreditLimitMin() {
    return $this->creditLimitMin;
  }
  public function setCreditLimitMax($creditLimitMax) {
    $this->creditLimitMax = $creditLimitMax;
  }
  public function getCreditLimitMax() {
    return $this->creditLimitMax;
  }
  public function setGracePeriodDisplay($gracePeriodDisplay) {
    $this->gracePeriodDisplay = $gracePeriodDisplay;
  }
  public function getGracePeriodDisplay() {
    return $this->gracePeriodDisplay;
  }
  public function setCashAdvanceFeeAmount($cashAdvanceFeeAmount) {
    $this->cashAdvanceFeeAmount = $cashAdvanceFeeAmount;
  }
  public function getCashAdvanceFeeAmount() {
    return $this->cashAdvanceFeeAmount;
  }
  public function setTravelInsurance($travelInsurance) {
    $this->travelInsurance = $travelInsurance;
  }
  public function getTravelInsurance() {
    return $this->travelInsurance;
  }
  public function setExistingCustomerOnly($existingCustomerOnly) {
    $this->existingCustomerOnly = $existingCustomerOnly;
  }
  public function getExistingCustomerOnly() {
    return $this->existingCustomerOnly;
  }
  public function setInitialSetupAndProcessingFee($initialSetupAndProcessingFee) {
    $this->initialSetupAndProcessingFee = $initialSetupAndProcessingFee;
  }
  public function getInitialSetupAndProcessingFee() {
    return $this->initialSetupAndProcessingFee;
  }
  public function setIssuerId($issuerId) {
    $this->issuerId = $issuerId;
  }
  public function getIssuerId() {
    return $this->issuerId;
  }
  public function setRewardUnit($rewardUnit) {
    $this->rewardUnit = $rewardUnit;
  }
  public function getRewardUnit() {
    return $this->rewardUnit;
  }
  public function setProhibitedCategories(/* array(string) */ $prohibitedCategories) {
    $this->assertIsArray($prohibitedCategories, 'string', __METHOD__);
    $this->prohibitedCategories = $prohibitedCategories;
  }
  public function getProhibitedCategories() {
    return $this->prohibitedCategories;
  }
  public function setDefaultFees(/* array(CcOfferDefaultFees) */ $defaultFees) {
    $this->assertIsArray($defaultFees, 'CcOfferDefaultFees', __METHOD__);
    $this->defaultFees = $defaultFees;
  }
  public function getDefaultFees() {
    return $this->defaultFees;
  }
  public function setIntroPurchasePeriodUnits($introPurchasePeriodUnits) {
    $this->introPurchasePeriodUnits = $introPurchasePeriodUnits;
  }
  public function getIntroPurchasePeriodUnits() {
    return $this->introPurchasePeriodUnits;
  }
  public function setTrackingUrl($trackingUrl) {
    $this->trackingUrl = $trackingUrl;
  }
  public function getTrackingUrl() {
    return $this->trackingUrl;
  }
  public function setLatePaymentFee($latePaymentFee) {
    $this->latePaymentFee = $latePaymentFee;
  }
  public function getLatePaymentFee() {
    return $this->latePaymentFee;
  }
  public function setStatementCopyFee($statementCopyFee) {
    $this->statementCopyFee = $statementCopyFee;
  }
  public function getStatementCopyFee() {
    return $this->statementCopyFee;
  }
  public function setVariableRatesLastUpdated($variableRatesLastUpdated) {
    $this->variableRatesLastUpdated = $variableRatesLastUpdated;
  }
  public function getVariableRatesLastUpdated() {
    return $this->variableRatesLastUpdated;
  }
  public function setMinBalanceTransferRate($minBalanceTransferRate) {
    $this->minBalanceTransferRate = $minBalanceTransferRate;
  }
  public function getMinBalanceTransferRate() {
    return $this->minBalanceTransferRate;
  }
  public function setEmergencyInsurance($emergencyInsurance) {
    $this->emergencyInsurance = $emergencyInsurance;
  }
  public function getEmergencyInsurance() {
    return $this->emergencyInsurance;
  }
  public function setIntroPurchasePeriodEndDate($introPurchasePeriodEndDate) {
    $this->introPurchasePeriodEndDate = $introPurchasePeriodEndDate;
  }
  public function getIntroPurchasePeriodEndDate() {
    return $this->introPurchasePeriodEndDate;
  }
  public function setCardName($cardName) {
    $this->cardName = $cardName;
  }
  public function getCardName() {
    return $this->cardName;
  }
  public function setReturnedPaymentFee($returnedPaymentFee) {
    $this->returnedPaymentFee = $returnedPaymentFee;
  }
  public function getReturnedPaymentFee() {
    return $this->returnedPaymentFee;
  }
  public function setCashAdvanceAdditionalDetails($cashAdvanceAdditionalDetails) {
    $this->cashAdvanceAdditionalDetails = $cashAdvanceAdditionalDetails;
  }
  public function getCashAdvanceAdditionalDetails() {
    return $this->cashAdvanceAdditionalDetails;
  }
  public function setCashAdvanceLimit($cashAdvanceLimit) {
    $this->cashAdvanceLimit = $cashAdvanceLimit;
  }
  public function getCashAdvanceLimit() {
    return $this->cashAdvanceLimit;
  }
  public function setBalanceTransferFeeRate($balanceTransferFeeRate) {
    $this->balanceTransferFeeRate = $balanceTransferFeeRate;
  }
  public function getBalanceTransferFeeRate() {
    return $this->balanceTransferFeeRate;
  }
  public function setBalanceTransferRateType($balanceTransferRateType) {
    $this->balanceTransferRateType = $balanceTransferRateType;
  }
  public function getBalanceTransferRateType() {
    return $this->balanceTransferRateType;
  }
  public function setRewards(/* array(CcOfferRewards) */ $rewards) {
    $this->assertIsArray($rewards, 'CcOfferRewards', __METHOD__);
    $this->rewards = $rewards;
  }
  public function getRewards() {
    return $this->rewards;
  }
  public function setExtendedWarranty($extendedWarranty) {
    $this->extendedWarranty = $extendedWarranty;
  }
  public function getExtendedWarranty() {
    return $this->extendedWarranty;
  }
  public function setCarRentalInsurance($carRentalInsurance) {
    $this->carRentalInsurance = $carRentalInsurance;
  }
  public function getCarRentalInsurance() {
    return $this->carRentalInsurance;
  }
  public function setCashAdvanceFeeRate($cashAdvanceFeeRate) {
    $this->cashAdvanceFeeRate = $cashAdvanceFeeRate;
  }
  public function getCashAdvanceFeeRate() {
    return $this->cashAdvanceFeeRate;
  }
  public function setRewardPartner($rewardPartner) {
    $this->rewardPartner = $rewardPartner;
  }
  public function getRewardPartner() {
    return $this->rewardPartner;
  }
  public function setForeignCurrencyTransactionFee($foreignCurrencyTransactionFee) {
    $this->foreignCurrencyTransactionFee = $foreignCurrencyTransactionFee;
  }
  public function getForeignCurrencyTransactionFee() {
    return $this->foreignCurrencyTransactionFee;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setIntroBalanceTransferFeeAmount($introBalanceTransferFeeAmount) {
    $this->introBalanceTransferFeeAmount = $introBalanceTransferFeeAmount;
  }
  public function getIntroBalanceTransferFeeAmount() {
    return $this->introBalanceTransferFeeAmount;
  }
  public function setCreditRatingDisplay($creditRatingDisplay) {
    $this->creditRatingDisplay = $creditRatingDisplay;
  }
  public function getCreditRatingDisplay() {
    return $this->creditRatingDisplay;
  }
  public function setAdditionalCardHolderFee($additionalCardHolderFee) {
    $this->additionalCardHolderFee = $additionalCardHolderFee;
  }
  public function getAdditionalCardHolderFee() {
    return $this->additionalCardHolderFee;
  }
  public function setPurchaseRateType($purchaseRateType) {
    $this->purchaseRateType = $purchaseRateType;
  }
  public function getPurchaseRateType() {
    return $this->purchaseRateType;
  }
  public function setCashAdvanceRateType($cashAdvanceRateType) {
    $this->cashAdvanceRateType = $cashAdvanceRateType;
  }
  public function getCashAdvanceRateType() {
    return $this->cashAdvanceRateType;
  }
  public function setBonusRewards(/* array(CcOfferBonusRewards) */ $bonusRewards) {
    $this->assertIsArray($bonusRewards, 'CcOfferBonusRewards', __METHOD__);
    $this->bonusRewards = $bonusRewards;
  }
  public function getBonusRewards() {
    return $this->bonusRewards;
  }
  public function setBalanceTransferLimit($balanceTransferLimit) {
    $this->balanceTransferLimit = $balanceTransferLimit;
  }
  public function getBalanceTransferLimit() {
    return $this->balanceTransferLimit;
  }
  public function setLuggageInsurance($luggageInsurance) {
    $this->luggageInsurance = $luggageInsurance;
  }
  public function getLuggageInsurance() {
    return $this->luggageInsurance;
  }
  public function setMinCashAdvanceRate($minCashAdvanceRate) {
    $this->minCashAdvanceRate = $minCashAdvanceRate;
  }
  public function getMinCashAdvanceRate() {
    return $this->minCashAdvanceRate;
  }
  public function setOfferId($offerId) {
    $this->offerId = $offerId;
  }
  public function getOfferId() {
    return $this->offerId;
  }
  public function setMinPurchaseRate($minPurchaseRate) {
    $this->minPurchaseRate = $minPurchaseRate;
  }
  public function getMinPurchaseRate() {
    return $this->minPurchaseRate;
  }
  public function setOffersImmediateCashReward($offersImmediateCashReward) {
    $this->offersImmediateCashReward = $offersImmediateCashReward;
  }
  public function getOffersImmediateCashReward() {
    return $this->offersImmediateCashReward;
  }
  public function setFraudLiability($fraudLiability) {
    $this->fraudLiability = $fraudLiability;
  }
  public function getFraudLiability() {
    return $this->fraudLiability;
  }
  public function setCardType($cardType) {
    $this->cardType = $cardType;
  }
  public function getCardType() {
    return $this->cardType;
  }
  public function setApprovedCategories(/* array(string) */ $approvedCategories) {
    $this->assertIsArray($approvedCategories, 'string', __METHOD__);
    $this->approvedCategories = $approvedCategories;
  }
  public function getApprovedCategories() {
    return $this->approvedCategories;
  }
  public function setAnnualFee($annualFee) {
    $this->annualFee = $annualFee;
  }
  public function getAnnualFee() {
    return $this->annualFee;
  }
  public function setMaxBalanceTransferRate($maxBalanceTransferRate) {
    $this->maxBalanceTransferRate = $maxBalanceTransferRate;
  }
  public function getMaxBalanceTransferRate() {
    return $this->maxBalanceTransferRate;
  }
  public function setMaxPurchaseRate($maxPurchaseRate) {
    $this->maxPurchaseRate = $maxPurchaseRate;
  }
  public function getMaxPurchaseRate() {
    return $this->maxPurchaseRate;
  }
  public function setIssuerWebsite($issuerWebsite) {
    $this->issuerWebsite = $issuerWebsite;
  }
  public function getIssuerWebsite() {
    return $this->issuerWebsite;
  }
  public function setIntroBalanceTransferRateType($introBalanceTransferRateType) {
    $this->introBalanceTransferRateType = $introBalanceTransferRateType;
  }
  public function getIntroBalanceTransferRateType() {
    return $this->introBalanceTransferRateType;
  }
  public function setAprDisplay($aprDisplay) {
    $this->aprDisplay = $aprDisplay;
  }
  public function getAprDisplay() {
    return $this->aprDisplay;
  }
  public function setImageUrl($imageUrl) {
    $this->imageUrl = $imageUrl;
  }
  public function getImageUrl() {
    return $this->imageUrl;
  }
  public function setAgeMinimumDetails($ageMinimumDetails) {
    $this->ageMinimumDetails = $ageMinimumDetails;
  }
  public function getAgeMinimumDetails() {
    return $this->ageMinimumDetails;
  }
  public function setBalanceTransferFeeAmount($balanceTransferFeeAmount) {
    $this->balanceTransferFeeAmount = $balanceTransferFeeAmount;
  }
  public function getBalanceTransferFeeAmount() {
    return $this->balanceTransferFeeAmount;
  }
  public function setIntroBalanceTransferPeriodEndDate($introBalanceTransferPeriodEndDate) {
    $this->introBalanceTransferPeriodEndDate = $introBalanceTransferPeriodEndDate;
  }
  public function getIntroBalanceTransferPeriodEndDate() {
    return $this->introBalanceTransferPeriodEndDate;
  }
}

class CcOfferBonusRewards extends apiModel {
  public $amount;
  public $details;
  public function setAmount($amount) {
    $this->amount = $amount;
  }
  public function getAmount() {
    return $this->amount;
  }
  public function setDetails($details) {
    $this->details = $details;
  }
  public function getDetails() {
    return $this->details;
  }
}

class CcOfferDefaultFees extends apiModel {
  public $category;
  public $maxRate;
  public $minRate;
  public $rateType;
  public function setCategory($category) {
    $this->category = $category;
  }
  public function getCategory() {
    return $this->category;
  }
  public function setMaxRate($maxRate) {
    $this->maxRate = $maxRate;
  }
  public function getMaxRate() {
    return $this->maxRate;
  }
  public function setMinRate($minRate) {
    $this->minRate = $minRate;
  }
  public function getMinRate() {
    return $this->minRate;
  }
  public function setRateType($rateType) {
    $this->rateType = $rateType;
  }
  public function getRateType() {
    return $this->rateType;
  }
}

class CcOfferRewards extends apiModel {
  public $category;
  public $minRewardTier;
  public $maxRewardTier;
  public $expirationMonths;
  public $amount;
  public $additionalDetails;
  public function setCategory($category) {
    $this->category = $category;
  }
  public function getCategory() {
    return $this->category;
  }
  public function setMinRewardTier($minRewardTier) {
    $this->minRewardTier = $minRewardTier;
  }
  public function getMinRewardTier() {
    return $this->minRewardTier;
  }
  public function setMaxRewardTier($maxRewardTier) {
    $this->maxRewardTier = $maxRewardTier;
  }
  public function getMaxRewardTier() {
    return $this->maxRewardTier;
  }
  public function setExpirationMonths($expirationMonths) {
    $this->expirationMonths = $expirationMonths;
  }
  public function getExpirationMonths() {
    return $this->expirationMonths;
  }
  public function setAmount($amount) {
    $this->amount = $amount;
  }
  public function getAmount() {
    return $this->amount;
  }
  public function setAdditionalDetails($additionalDetails) {
    $this->additionalDetails = $additionalDetails;
  }
  public function getAdditionalDetails() {
    return $this->additionalDetails;
  }
}

class CcOffers extends apiModel {
  protected $__itemsType = 'CcOffer';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(CcOffer) */ $items) {
    $this->assertIsArray($items, 'CcOffer', __METHOD__);
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

class Event extends apiModel {
  protected $__networkFeeType = 'Money';
  protected $__networkFeeDataType = '';
  public $networkFee;
  public $advertiserName;
  public $kind;
  public $modifyDate;
  public $type;
  public $orderId;
  public $publisherName;
  public $memberId;
  public $advertiserId;
  public $status;
  public $chargeId;
  protected $__productsType = 'EventProducts';
  protected $__productsDataType = 'array';
  public $products;
  protected $__earningsType = 'Money';
  protected $__earningsDataType = '';
  public $earnings;
  public $chargeType;
  protected $__publisherFeeType = 'Money';
  protected $__publisherFeeDataType = '';
  public $publisherFee;
  protected $__commissionableSalesType = 'Money';
  protected $__commissionableSalesDataType = '';
  public $commissionableSales;
  public $publisherId;
  public $eventDate;
  public function setNetworkFee(Money $networkFee) {
    $this->networkFee = $networkFee;
  }
  public function getNetworkFee() {
    return $this->networkFee;
  }
  public function setAdvertiserName($advertiserName) {
    $this->advertiserName = $advertiserName;
  }
  public function getAdvertiserName() {
    return $this->advertiserName;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setModifyDate($modifyDate) {
    $this->modifyDate = $modifyDate;
  }
  public function getModifyDate() {
    return $this->modifyDate;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setOrderId($orderId) {
    $this->orderId = $orderId;
  }
  public function getOrderId() {
    return $this->orderId;
  }
  public function setPublisherName($publisherName) {
    $this->publisherName = $publisherName;
  }
  public function getPublisherName() {
    return $this->publisherName;
  }
  public function setMemberId($memberId) {
    $this->memberId = $memberId;
  }
  public function getMemberId() {
    return $this->memberId;
  }
  public function setAdvertiserId($advertiserId) {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId() {
    return $this->advertiserId;
  }
  public function setStatus($status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setChargeId($chargeId) {
    $this->chargeId = $chargeId;
  }
  public function getChargeId() {
    return $this->chargeId;
  }
  public function setProducts(/* array(EventProducts) */ $products) {
    $this->assertIsArray($products, 'EventProducts', __METHOD__);
    $this->products = $products;
  }
  public function getProducts() {
    return $this->products;
  }
  public function setEarnings(Money $earnings) {
    $this->earnings = $earnings;
  }
  public function getEarnings() {
    return $this->earnings;
  }
  public function setChargeType($chargeType) {
    $this->chargeType = $chargeType;
  }
  public function getChargeType() {
    return $this->chargeType;
  }
  public function setPublisherFee(Money $publisherFee) {
    $this->publisherFee = $publisherFee;
  }
  public function getPublisherFee() {
    return $this->publisherFee;
  }
  public function setCommissionableSales(Money $commissionableSales) {
    $this->commissionableSales = $commissionableSales;
  }
  public function getCommissionableSales() {
    return $this->commissionableSales;
  }
  public function setPublisherId($publisherId) {
    $this->publisherId = $publisherId;
  }
  public function getPublisherId() {
    return $this->publisherId;
  }
  public function setEventDate($eventDate) {
    $this->eventDate = $eventDate;
  }
  public function getEventDate() {
    return $this->eventDate;
  }
}

class EventProducts extends apiModel {
  protected $__networkFeeType = 'Money';
  protected $__networkFeeDataType = '';
  public $networkFee;
  public $sku;
  public $categoryName;
  public $skuName;
  protected $__publisherFeeType = 'Money';
  protected $__publisherFeeDataType = '';
  public $publisherFee;
  protected $__earningsType = 'Money';
  protected $__earningsDataType = '';
  public $earnings;
  protected $__unitPriceType = 'Money';
  protected $__unitPriceDataType = '';
  public $unitPrice;
  public $categoryId;
  public $quantity;
  public function setNetworkFee(Money $networkFee) {
    $this->networkFee = $networkFee;
  }
  public function getNetworkFee() {
    return $this->networkFee;
  }
  public function setSku($sku) {
    $this->sku = $sku;
  }
  public function getSku() {
    return $this->sku;
  }
  public function setCategoryName($categoryName) {
    $this->categoryName = $categoryName;
  }
  public function getCategoryName() {
    return $this->categoryName;
  }
  public function setSkuName($skuName) {
    $this->skuName = $skuName;
  }
  public function getSkuName() {
    return $this->skuName;
  }
  public function setPublisherFee(Money $publisherFee) {
    $this->publisherFee = $publisherFee;
  }
  public function getPublisherFee() {
    return $this->publisherFee;
  }
  public function setEarnings(Money $earnings) {
    $this->earnings = $earnings;
  }
  public function getEarnings() {
    return $this->earnings;
  }
  public function setUnitPrice(Money $unitPrice) {
    $this->unitPrice = $unitPrice;
  }
  public function getUnitPrice() {
    return $this->unitPrice;
  }
  public function setCategoryId($categoryId) {
    $this->categoryId = $categoryId;
  }
  public function getCategoryId() {
    return $this->categoryId;
  }
  public function setQuantity($quantity) {
    $this->quantity = $quantity;
  }
  public function getQuantity() {
    return $this->quantity;
  }
}

class Events extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Event';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Event) */ $items) {
    $this->assertIsArray($items, 'Event', __METHOD__);
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

class Money extends apiModel {
  public $amount;
  public $currencyCode;
  public function setAmount($amount) {
    $this->amount = $amount;
  }
  public function getAmount() {
    return $this->amount;
  }
  public function setCurrencyCode($currencyCode) {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode() {
    return $this->currencyCode;
  }
}

class Publisher extends apiModel {
  public $status;
  public $kind;
  public $name;
  public $classification;
  protected $__epcSevenDayAverageType = 'Money';
  protected $__epcSevenDayAverageDataType = '';
  public $epcSevenDayAverage;
  public $payoutRank;
  protected $__epcNinetyDayAverageType = 'Money';
  protected $__epcNinetyDayAverageDataType = '';
  public $epcNinetyDayAverage;
  protected $__itemType = 'Publisher2';
  protected $__itemDataType = '';
  public $item;
  public $joinDate;
  public $sites;
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
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setClassification($classification) {
    $this->classification = $classification;
  }
  public function getClassification() {
    return $this->classification;
  }
  public function setEpcSevenDayAverage(Money $epcSevenDayAverage) {
    $this->epcSevenDayAverage = $epcSevenDayAverage;
  }
  public function getEpcSevenDayAverage() {
    return $this->epcSevenDayAverage;
  }
  public function setPayoutRank($payoutRank) {
    $this->payoutRank = $payoutRank;
  }
  public function getPayoutRank() {
    return $this->payoutRank;
  }
  public function setEpcNinetyDayAverage(Money $epcNinetyDayAverage) {
    $this->epcNinetyDayAverage = $epcNinetyDayAverage;
  }
  public function getEpcNinetyDayAverage() {
    return $this->epcNinetyDayAverage;
  }
  public function setItem(Publisher2 $item) {
    $this->item = $item;
  }
  public function getItem() {
    return $this->item;
  }
  public function setJoinDate($joinDate) {
    $this->joinDate = $joinDate;
  }
  public function getJoinDate() {
    return $this->joinDate;
  }
  public function setSites(/* array(string) */ $sites) {
    $this->assertIsArray($sites, 'string', __METHOD__);
    $this->sites = $sites;
  }
  public function getSites() {
    return $this->sites;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class Publishers extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Publisher';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Publisher) */ $items) {
    $this->assertIsArray($items, 'Publisher', __METHOD__);
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
