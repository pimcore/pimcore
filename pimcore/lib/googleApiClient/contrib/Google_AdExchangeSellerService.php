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
   * The "accounts" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $accounts = $adexchangesellerService->accounts;
   *  </code>
   */
  class google_AccountsServiceResource extends Google_ServiceResource {

    /**
     * Get information about the selected Ad Exchange account. (accounts.get)
     *
     * @param string $accountId Account to get information about. Tip: 'myaccount' is a valid ID.
     * @param array $optParams Optional parameters.
     * @return google_Account
     */
    public function get($accountId, $optParams = array()) {
      $params = array('accountId' => $accountId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new google_Account($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "adclients" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $adclients = $adexchangesellerService->adclients;
   *  </code>
   */
  class google_AdclientsServiceResource extends Google_ServiceResource {

    /**
     * List all ad clients in this Ad Exchange account. (adclients.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of ad clients to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through ad clients. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_AdClients
     */
    public function listAdclients($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_AdClients($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "adunits" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $adunits = $adexchangesellerService->adunits;
   *  </code>
   */
  class google_AdunitsServiceResource extends Google_ServiceResource {

    /**
     * Gets the specified ad unit in the specified ad client. (adunits.get)
     *
     * @param string $adClientId Ad client for which to get the ad unit.
     * @param string $adUnitId Ad unit to retrieve.
     * @param array $optParams Optional parameters.
     * @return google_AdUnit
     */
    public function get($adClientId, $adUnitId, $optParams = array()) {
      $params = array('adClientId' => $adClientId, 'adUnitId' => $adUnitId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new google_AdUnit($data);
      } else {
        return $data;
      }
    }
    /**
     * List all ad units in the specified ad client for this Ad Exchange account.
     * (adunits.list)
     *
     * @param string $adClientId Ad client for which to list ad units.
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool includeInactive Whether to include inactive ad units. Default: true.
     * @opt_param string maxResults The maximum number of ad units to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through ad units. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_AdUnits
     */
    public function listAdunits($adClientId, $optParams = array()) {
      $params = array('adClientId' => $adClientId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_AdUnits($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "customchannels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $customchannels = $adexchangesellerService->customchannels;
   *  </code>
   */
  class google_AdunitsCustomchannelsServiceResource extends Google_ServiceResource {

    /**
     * List all custom channels which the specified ad unit belongs to.
     * (customchannels.list)
     *
     * @param string $adClientId Ad client which contains the ad unit.
     * @param string $adUnitId Ad unit for which to list custom channels.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of custom channels to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through custom channels. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_CustomChannels
     */
    public function listAdunitsCustomchannels($adClientId, $adUnitId, $optParams = array()) {
      $params = array('adClientId' => $adClientId, 'adUnitId' => $adUnitId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_CustomChannels($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "alerts" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $alerts = $adexchangesellerService->alerts;
   *  </code>
   */
  class google_AlertsServiceResource extends Google_ServiceResource {

    /**
     * List the alerts for this Ad Exchange account. (alerts.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string locale The locale to use for translating alert messages. The account locale will be used if this is not supplied. The AdSense default (English) will be used if the supplied locale is invalid or unsupported.
     * @return google_Alerts
     */
    public function listAlerts($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_Alerts($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "customchannels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $customchannels = $adexchangesellerService->customchannels;
   *  </code>
   */
  class google_CustomchannelsServiceResource extends Google_ServiceResource {

    /**
     * Get the specified custom channel from the specified ad client.
     * (customchannels.get)
     *
     * @param string $adClientId Ad client which contains the custom channel.
     * @param string $customChannelId Custom channel to retrieve.
     * @param array $optParams Optional parameters.
     * @return google_CustomChannel
     */
    public function get($adClientId, $customChannelId, $optParams = array()) {
      $params = array('adClientId' => $adClientId, 'customChannelId' => $customChannelId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new google_CustomChannel($data);
      } else {
        return $data;
      }
    }
    /**
     * List all custom channels in the specified ad client for this Ad Exchange
     * account. (customchannels.list)
     *
     * @param string $adClientId Ad client for which to list custom channels.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of custom channels to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through custom channels. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_CustomChannels
     */
    public function listCustomchannels($adClientId, $optParams = array()) {
      $params = array('adClientId' => $adClientId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_CustomChannels($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "adunits" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $adunits = $adexchangesellerService->adunits;
   *  </code>
   */
  class google_CustomchannelsAdunitsServiceResource extends Google_ServiceResource {

    /**
     * List all ad units in the specified custom channel. (adunits.list)
     *
     * @param string $adClientId Ad client which contains the custom channel.
     * @param string $customChannelId Custom channel for which to list ad units.
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool includeInactive Whether to include inactive ad units. Default: true.
     * @opt_param string maxResults The maximum number of ad units to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through ad units. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_AdUnits
     */
    public function listCustomchannelsAdunits($adClientId, $customChannelId, $optParams = array()) {
      $params = array('adClientId' => $adClientId, 'customChannelId' => $customChannelId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_AdUnits($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "metadata" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $metadata = $adexchangesellerService->metadata;
   *  </code>
   */
  class google_MetadataServiceResource extends Google_ServiceResource {

  }

  /**
   * The "dimensions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $dimensions = $adexchangesellerService->dimensions;
   *  </code>
   */
  class google_MetadataDimensionsServiceResource extends Google_ServiceResource {

    /**
     * List the metadata for the dimensions available to this AdExchange account.
     * (dimensions.list)
     *
     * @param array $optParams Optional parameters.
     * @return google_Metadata
     */
    public function listMetadataDimensions($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_Metadata($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "metrics" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $metrics = $adexchangesellerService->metrics;
   *  </code>
   */
  class google_MetadataMetricsServiceResource extends Google_ServiceResource {

    /**
     * List the metadata for the metrics available to this AdExchange account.
     * (metrics.list)
     *
     * @param array $optParams Optional parameters.
     * @return google_Metadata
     */
    public function listMetadataMetrics($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_Metadata($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "preferreddeals" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $preferreddeals = $adexchangesellerService->preferreddeals;
   *  </code>
   */
  class google_PreferreddealsServiceResource extends Google_ServiceResource {

    /**
     * Get information about the selected Ad Exchange Preferred Deal.
     * (preferreddeals.get)
     *
     * @param string $dealId Preferred deal to get information about.
     * @param array $optParams Optional parameters.
     * @return google_PreferredDeal
     */
    public function get($dealId, $optParams = array()) {
      $params = array('dealId' => $dealId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new google_PreferredDeal($data);
      } else {
        return $data;
      }
    }
    /**
     * List the preferred deals for this Ad Exchange account. (preferreddeals.list)
     *
     * @param array $optParams Optional parameters.
     * @return google_PreferredDeals
     */
    public function listPreferreddeals($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_PreferredDeals($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "reports" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $reports = $adexchangesellerService->reports;
   *  </code>
   */
  class google_ReportsServiceResource extends Google_ServiceResource {

    /**
     * Generate an Ad Exchange report based on the report request sent in the query
     * parameters. Returns the result as JSON; to retrieve output in CSV format
     * specify "alt=csv" as a query parameter. (reports.generate)
     *
     * @param string $startDate Start of the date range to report on in "YYYY-MM-DD" format, inclusive.
     * @param string $endDate End of the date range to report on in "YYYY-MM-DD" format, inclusive.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string dimension Dimensions to base the report on.
     * @opt_param string filter Filters to be run on the report.
     * @opt_param string locale Optional locale to use for translating report output to a local language. Defaults to "en_US" if not specified.
     * @opt_param string maxResults The maximum number of rows of report data to return.
     * @opt_param string metric Numeric columns to include in the report.
     * @opt_param string sort The name of a dimension or metric to sort the resulting report on, optionally prefixed with "+" to sort ascending or "-" to sort descending. If no prefix is specified, the column is sorted ascending.
     * @opt_param string startIndex Index of the first row of report data to return.
     * @return google_Report
     */
    public function generate($startDate, $endDate, $optParams = array()) {
      $params = array('startDate' => $startDate, 'endDate' => $endDate);
      $params = array_merge($params, $optParams);
      $data = $this->__call('generate', array($params));
      if ($this->useObjects()) {
        return new google_Report($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "saved" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $saved = $adexchangesellerService->saved;
   *  </code>
   */
  class google_ReportsSavedServiceResource extends Google_ServiceResource {

    /**
     * Generate an Ad Exchange report based on the saved report ID sent in the query
     * parameters. (saved.generate)
     *
     * @param string $savedReportId The saved report to retrieve.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string locale Optional locale to use for translating report output to a local language. Defaults to "en_US" if not specified.
     * @opt_param int maxResults The maximum number of rows of report data to return.
     * @opt_param int startIndex Index of the first row of report data to return.
     * @return google_Report
     */
    public function generate($savedReportId, $optParams = array()) {
      $params = array('savedReportId' => $savedReportId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('generate', array($params));
      if ($this->useObjects()) {
        return new google_Report($data);
      } else {
        return $data;
      }
    }
    /**
     * List all saved reports in this Ad Exchange account. (saved.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param int maxResults The maximum number of saved reports to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through saved reports. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_SavedReports
     */
    public function listReportsSaved($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_SavedReports($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "urlchannels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $adexchangesellerService = new google_AdExchangeSellerService(...);
   *   $urlchannels = $adexchangesellerService->urlchannels;
   *  </code>
   */
  class google_UrlchannelsServiceResource extends Google_ServiceResource {

    /**
     * List all URL channels in the specified ad client for this Ad Exchange
     * account. (urlchannels.list)
     *
     * @param string $adClientId Ad client for which to list URL channels.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of URL channels to include in the response, used for paging.
     * @opt_param string pageToken A continuation token, used to page through URL channels. To retrieve the next page, set this parameter to the value of "nextPageToken" from the previous response.
     * @return google_UrlChannels
     */
    public function listUrlchannels($adClientId, $optParams = array()) {
      $params = array('adClientId' => $adClientId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new google_UrlChannels($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for google_AdExchangeSeller (v1.1).
 *
 * <p>
 * Gives Ad Exchange seller users access to their inventory and the ability to generate reports
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/ad-exchange/seller-rest/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class google_AdExchangeSellerService extends Google_Service {
  public $accounts;
  public $adclients;
  public $adunits;
  public $adunits_customchannels;
  public $alerts;
  public $customchannels;
  public $customchannels_adunits;
  public $metadata_dimensions;
  public $metadata_metrics;
  public $preferreddeals;
  public $reports;
  public $reports_saved;
  public $urlchannels;
  /**
   * Constructs the internal representation of the AdExchangeSeller service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'adexchangeseller/v1.1/';
    $this->version = 'v1.1';
    $this->serviceName = 'adexchangeseller';

    $client->addService($this->serviceName, $this->version);
    $this->accounts = new google_AccountsServiceResource($this, $this->serviceName, 'accounts', json_decode('{"methods": {"get": {"id": "adexchangeseller.accounts.get", "path": "accounts/{accountId}", "httpMethod": "GET", "parameters": {"accountId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Account"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->adclients = new google_AdclientsServiceResource($this, $this->serviceName, 'adclients', json_decode('{"methods": {"list": {"id": "adexchangeseller.adclients.list", "path": "adclients", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "10000", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "AdClients"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->adunits = new google_AdunitsServiceResource($this, $this->serviceName, 'adunits', json_decode('{"methods": {"get": {"id": "adexchangeseller.adunits.get", "path": "adclients/{adClientId}/adunits/{adUnitId}", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "adUnitId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "AdUnit"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}, "list": {"id": "adexchangeseller.adunits.list", "path": "adclients/{adClientId}/adunits", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "includeInactive": {"type": "boolean", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "10000", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "AdUnits"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->adunits_customchannels = new google_AdunitsCustomchannelsServiceResource($this, $this->serviceName, 'customchannels', json_decode('{"methods": {"list": {"id": "adexchangeseller.adunits.customchannels.list", "path": "adclients/{adClientId}/adunits/{adUnitId}/customchannels", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "adUnitId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "10000", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "CustomChannels"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->alerts = new google_AlertsServiceResource($this, $this->serviceName, 'alerts', json_decode('{"methods": {"list": {"id": "adexchangeseller.alerts.list", "path": "alerts", "httpMethod": "GET", "parameters": {"locale": {"type": "string", "location": "query"}}, "response": {"$ref": "Alerts"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->customchannels = new google_CustomchannelsServiceResource($this, $this->serviceName, 'customchannels', json_decode('{"methods": {"get": {"id": "adexchangeseller.customchannels.get", "path": "adclients/{adClientId}/customchannels/{customChannelId}", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "customChannelId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CustomChannel"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}, "list": {"id": "adexchangeseller.customchannels.list", "path": "adclients/{adClientId}/customchannels", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "10000", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "CustomChannels"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->customchannels_adunits = new google_CustomchannelsAdunitsServiceResource($this, $this->serviceName, 'adunits', json_decode('{"methods": {"list": {"id": "adexchangeseller.customchannels.adunits.list", "path": "adclients/{adClientId}/customchannels/{customChannelId}/adunits", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "customChannelId": {"type": "string", "required": true, "location": "path"}, "includeInactive": {"type": "boolean", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "10000", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "AdUnits"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->metadata_dimensions = new google_MetadataDimensionsServiceResource($this, $this->serviceName, 'dimensions', json_decode('{"methods": {"list": {"id": "adexchangeseller.metadata.dimensions.list", "path": "metadata/dimensions", "httpMethod": "GET", "response": {"$ref": "Metadata"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->metadata_metrics = new google_MetadataMetricsServiceResource($this, $this->serviceName, 'metrics', json_decode('{"methods": {"list": {"id": "adexchangeseller.metadata.metrics.list", "path": "metadata/metrics", "httpMethod": "GET", "response": {"$ref": "Metadata"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->preferreddeals = new google_PreferreddealsServiceResource($this, $this->serviceName, 'preferreddeals', json_decode('{"methods": {"get": {"id": "adexchangeseller.preferreddeals.get", "path": "preferreddeals/{dealId}", "httpMethod": "GET", "parameters": {"dealId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "PreferredDeal"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}, "list": {"id": "adexchangeseller.preferreddeals.list", "path": "preferreddeals", "httpMethod": "GET", "response": {"$ref": "PreferredDeals"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->reports = new google_ReportsServiceResource($this, $this->serviceName, 'reports', json_decode('{"methods": {"generate": {"id": "adexchangeseller.reports.generate", "path": "reports", "httpMethod": "GET", "parameters": {"dimension": {"type": "string", "repeated": true, "location": "query"}, "endDate": {"type": "string", "required": true, "location": "query"}, "filter": {"type": "string", "repeated": true, "location": "query"}, "locale": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "50000", "location": "query"}, "metric": {"type": "string", "repeated": true, "location": "query"}, "sort": {"type": "string", "repeated": true, "location": "query"}, "startDate": {"type": "string", "required": true, "location": "query"}, "startIndex": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "5000", "location": "query"}}, "response": {"$ref": "Report"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"], "supportsMediaDownload": true}}}', true));
    $this->reports_saved = new google_ReportsSavedServiceResource($this, $this->serviceName, 'saved', json_decode('{"methods": {"generate": {"id": "adexchangeseller.reports.saved.generate", "path": "reports/{savedReportId}", "httpMethod": "GET", "parameters": {"locale": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "0", "maximum": "50000", "location": "query"}, "savedReportId": {"type": "string", "required": true, "location": "path"}, "startIndex": {"type": "integer", "format": "int32", "minimum": "0", "maximum": "5000", "location": "query"}}, "response": {"$ref": "Report"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}, "list": {"id": "adexchangeseller.reports.saved.list", "path": "reports/saved", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "format": "int32", "minimum": "0", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "SavedReports"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));
    $this->urlchannels = new google_UrlchannelsServiceResource($this, $this->serviceName, 'urlchannels', json_decode('{"methods": {"list": {"id": "adexchangeseller.urlchannels.list", "path": "adclients/{adClientId}/urlchannels", "httpMethod": "GET", "parameters": {"adClientId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "maximum": "10000", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "UrlChannels"}, "scopes": ["https://www.googleapis.com/auth/adexchange.seller", "https://www.googleapis.com/auth/adexchange.seller.readonly"]}}}', true));

  }
}



class google_Account extends Google_Model {
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

class google_AdClient extends Google_Model {
  public $arcOptIn;
  public $id;
  public $kind;
  public $productCode;
  public $supportsReporting;
  public function setArcOptIn( $arcOptIn) {
    $this->arcOptIn = $arcOptIn;
  }
  public function getArcOptIn() {
    return $this->arcOptIn;
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
  public function setProductCode( $productCode) {
    $this->productCode = $productCode;
  }
  public function getProductCode() {
    return $this->productCode;
  }
  public function setSupportsReporting( $supportsReporting) {
    $this->supportsReporting = $supportsReporting;
  }
  public function getSupportsReporting() {
    return $this->supportsReporting;
  }
}

class google_AdClients extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_AdClient';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(google_AdClient) */ $items) {
    $this->assertIsArray($items, 'google_AdClient', __METHOD__);
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

class google_AdUnit extends Google_Model {
  public $code;
  public $id;
  public $kind;
  public $name;
  public $status;
  public function setCode( $code) {
    $this->code = $code;
  }
  public function getCode() {
    return $this->code;
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
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
}

class google_AdUnits extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_AdUnit';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(google_AdUnit) */ $items) {
    $this->assertIsArray($items, 'google_AdUnit', __METHOD__);
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

class google_Alert extends Google_Model {
  public $id;
  public $kind;
  public $message;
  public $severity;
  public $type;
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
  public function setMessage( $message) {
    $this->message = $message;
  }
  public function getMessage() {
    return $this->message;
  }
  public function setSeverity( $severity) {
    $this->severity = $severity;
  }
  public function getSeverity() {
    return $this->severity;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class google_Alerts extends Google_Model {
  protected $__itemsType = 'Google_Alert';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(google_Alert) */ $items) {
    $this->assertIsArray($items, 'google_Alert', __METHOD__);
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

class google_CustomChannel extends Google_Model {
  public $code;
  public $id;
  public $kind;
  public $name;
  protected $__targetingInfoType = 'Google_CustomChannelTargetingInfo';
  protected $__targetingInfoDataType = '';
  public $targetingInfo;
  public function setCode( $code) {
    $this->code = $code;
  }
  public function getCode() {
    return $this->code;
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
  public function setTargetingInfo(Google_CustomChannelTargetingInfo $targetingInfo) {
    $this->targetingInfo = $targetingInfo;
  }
  public function getTargetingInfo() {
    return $this->targetingInfo;
  }
}

class google_CustomChannelTargetingInfo extends Google_Model {
  public $adsAppearOn;
  public $description;
  public $location;
  public $siteLanguage;
  public function setAdsAppearOn( $adsAppearOn) {
    $this->adsAppearOn = $adsAppearOn;
  }
  public function getAdsAppearOn() {
    return $this->adsAppearOn;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setLocation( $location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setSiteLanguage( $siteLanguage) {
    $this->siteLanguage = $siteLanguage;
  }
  public function getSiteLanguage() {
    return $this->siteLanguage;
  }
}

class google_CustomChannels extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_CustomChannel';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(google_CustomChannel) */ $items) {
    $this->assertIsArray($items, 'google_CustomChannel', __METHOD__);
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

class google_Metadata extends Google_Model {
  protected $__itemsType = 'Google_ReportingMetadataEntry';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(google_ReportingMetadataEntry) */ $items) {
    $this->assertIsArray($items, 'google_ReportingMetadataEntry', __METHOD__);
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

class google_PreferredDeal extends Google_Model {
  public $advertiserName;
  public $buyerNetworkName;
  public $currencyCode;
  public $endTime;
  public $fixedCpm;
  public $id;
  public $kind;
  public $startTime;
  public function setAdvertiserName( $advertiserName) {
    $this->advertiserName = $advertiserName;
  }
  public function getAdvertiserName() {
    return $this->advertiserName;
  }
  public function setBuyerNetworkName( $buyerNetworkName) {
    $this->buyerNetworkName = $buyerNetworkName;
  }
  public function getBuyerNetworkName() {
    return $this->buyerNetworkName;
  }
  public function setCurrencyCode( $currencyCode) {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode() {
    return $this->currencyCode;
  }
  public function setEndTime( $endTime) {
    $this->endTime = $endTime;
  }
  public function getEndTime() {
    return $this->endTime;
  }
  public function setFixedCpm( $fixedCpm) {
    $this->fixedCpm = $fixedCpm;
  }
  public function getFixedCpm() {
    return $this->fixedCpm;
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

class google_PreferredDeals extends Google_Model {
  protected $__itemsType = 'Google_PreferredDeal';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(google_PreferredDeal) */ $items) {
    $this->assertIsArray($items, 'google_PreferredDeal', __METHOD__);
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

class google_Report extends Google_Model {
  public $averages;
  protected $__headersType = 'Google_ReportHeaders';
  protected $__headersDataType = 'array';
  public $headers;
  public $kind;
  public $rows;
  public $totalMatchedRows;
  public $totals;
  public $warnings;
  public function setAverages(/* array(google_string) */ $averages) {
    $this->assertIsArray($averages, 'google_string', __METHOD__);
    $this->averages = $averages;
  }
  public function getAverages() {
    return $this->averages;
  }
  public function setHeaders(/* array(google_ReportHeaders) */ $headers) {
    $this->assertIsArray($headers, 'google_ReportHeaders', __METHOD__);
    $this->headers = $headers;
  }
  public function getHeaders() {
    return $this->headers;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRows(/* array(google_string) */ $rows) {
    $this->assertIsArray($rows, 'google_string', __METHOD__);
    $this->rows = $rows;
  }
  public function getRows() {
    return $this->rows;
  }
  public function setTotalMatchedRows( $totalMatchedRows) {
    $this->totalMatchedRows = $totalMatchedRows;
  }
  public function getTotalMatchedRows() {
    return $this->totalMatchedRows;
  }
  public function setTotals(/* array(google_string) */ $totals) {
    $this->assertIsArray($totals, 'google_string', __METHOD__);
    $this->totals = $totals;
  }
  public function getTotals() {
    return $this->totals;
  }
  public function setWarnings(/* array(google_string) */ $warnings) {
    $this->assertIsArray($warnings, 'google_string', __METHOD__);
    $this->warnings = $warnings;
  }
  public function getWarnings() {
    return $this->warnings;
  }
}

class google_ReportHeaders extends Google_Model {
  public $currency;
  public $name;
  public $type;
  public function setCurrency( $currency) {
    $this->currency = $currency;
  }
  public function getCurrency() {
    return $this->currency;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class google_ReportingMetadataEntry extends Google_Model {
  public $compatibleDimensions;
  public $compatibleMetrics;
  public $id;
  public $kind;
  public $requiredDimensions;
  public $requiredMetrics;
  public $supportedProducts;
  public function setCompatibleDimensions(/* array(google_string) */ $compatibleDimensions) {
    $this->assertIsArray($compatibleDimensions, 'google_string', __METHOD__);
    $this->compatibleDimensions = $compatibleDimensions;
  }
  public function getCompatibleDimensions() {
    return $this->compatibleDimensions;
  }
  public function setCompatibleMetrics(/* array(google_string) */ $compatibleMetrics) {
    $this->assertIsArray($compatibleMetrics, 'google_string', __METHOD__);
    $this->compatibleMetrics = $compatibleMetrics;
  }
  public function getCompatibleMetrics() {
    return $this->compatibleMetrics;
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
  public function setRequiredDimensions(/* array(google_string) */ $requiredDimensions) {
    $this->assertIsArray($requiredDimensions, 'google_string', __METHOD__);
    $this->requiredDimensions = $requiredDimensions;
  }
  public function getRequiredDimensions() {
    return $this->requiredDimensions;
  }
  public function setRequiredMetrics(/* array(google_string) */ $requiredMetrics) {
    $this->assertIsArray($requiredMetrics, 'google_string', __METHOD__);
    $this->requiredMetrics = $requiredMetrics;
  }
  public function getRequiredMetrics() {
    return $this->requiredMetrics;
  }
  public function setSupportedProducts(/* array(google_string) */ $supportedProducts) {
    $this->assertIsArray($supportedProducts, 'google_string', __METHOD__);
    $this->supportedProducts = $supportedProducts;
  }
  public function getSupportedProducts() {
    return $this->supportedProducts;
  }
}

class google_SavedReport extends Google_Model {
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

class google_SavedReports extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_SavedReport';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(google_SavedReport) */ $items) {
    $this->assertIsArray($items, 'google_SavedReport', __METHOD__);
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

class google_UrlChannel extends Google_Model {
  public $id;
  public $kind;
  public $urlPattern;
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
  public function setUrlPattern( $urlPattern) {
    $this->urlPattern = $urlPattern;
  }
  public function getUrlPattern() {
    return $this->urlPattern;
  }
}

class google_UrlChannels extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_UrlChannel';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(google_UrlChannel) */ $items) {
    $this->assertIsArray($items, 'google_UrlChannel', __METHOD__);
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
