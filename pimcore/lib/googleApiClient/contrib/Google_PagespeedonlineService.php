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
   * The "pagespeedapi" collection of methods.
   * Typical usage is:
   *  <code>
   *   $pagespeedonlineService = new Google_PagespeedonlineService(...);
   *   $pagespeedapi = $pagespeedonlineService->pagespeedapi;
   *  </code>
   */
  class Google_PagespeedapiServiceResource extends Google_ServiceResource {

    /**
     * Runs Page Speed analysis on the page at the specified URL, and returns a Page Speed score, a list
     * of suggestions to make that page faster, and other information. (pagespeedapi.runpagespeed)
     *
     * @param string $url The URL to fetch and analyze
     * @param array $optParams Optional parameters.
     *
     * @opt_param string locale The locale used to localize formatted results
     * @opt_param string rule A Page Speed rule to run; if none are given, all rules are run
     * @opt_param bool screenshot Indicates if binary data containing a screenshot should be included
     * @opt_param string strategy The analysis strategy to use
     * @return Google_Result
     */
    public function runpagespeed($url, $optParams = array()) {
      $params = array('url' => $url);
      $params = array_merge($params, $optParams);
      $data = $this->__call('runpagespeed', array($params));
      if ($this->useObjects()) {
        return new Google_Result($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Pagespeedonline (v1).
 *
 * <p>
 * Lets you analyze the performance of a web page and get tailored suggestions to make that page faster.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/speed/docs/insights/v1/getting_started" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_PagespeedonlineService extends Google_Service {
  public $pagespeedapi;
  /**
   * Constructs the internal representation of the Pagespeedonline service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'pagespeedonline/v1/';
    $this->version = 'v1';
    $this->serviceName = 'pagespeedonline';

    $client->addService($this->serviceName, $this->version);
    $this->pagespeedapi = new Google_PagespeedapiServiceResource($this, $this->serviceName, 'pagespeedapi', json_decode('{"methods": {"runpagespeed": {"id": "pagespeedonline.pagespeedapi.runpagespeed", "path": "runPagespeed", "httpMethod": "GET", "parameters": {"locale": {"type": "string", "location": "query"}, "rule": {"type": "string", "repeated": true, "location": "query"}, "screenshot": {"type": "boolean", "default": "false", "location": "query"}, "strategy": {"type": "string", "enum": ["desktop", "mobile"], "location": "query"}, "url": {"type": "string", "required": true, "location": "query"}}, "response": {"$ref": "Result"}}}}', true));

  }
}



class Google_Result extends Google_Model {
  protected $__formattedResultsType = 'Google_ResultFormattedResults';
  protected $__formattedResultsDataType = '';
  public $formattedResults;
  public $id;
  public $invalidRules;
  public $kind;
  protected $__pageStatsType = 'Google_ResultPageStats';
  protected $__pageStatsDataType = '';
  public $pageStats;
  public $responseCode;
  public $score;
  protected $__screenshotType = 'Google_ResultScreenshot';
  protected $__screenshotDataType = '';
  public $screenshot;
  public $title;
  protected $__versionType = 'Google_ResultVersion';
  protected $__versionDataType = '';
  public $version;
  public function setFormattedResults(Google_ResultFormattedResults $formattedResults) {
    $this->formattedResults = $formattedResults;
  }
  public function getFormattedResults() {
    return $this->formattedResults;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setInvalidRules(/* array(Google_string) */ $invalidRules) {
    $this->assertIsArray($invalidRules, 'Google_string', __METHOD__);
    $this->invalidRules = $invalidRules;
  }
  public function getInvalidRules() {
    return $this->invalidRules;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPageStats(Google_ResultPageStats $pageStats) {
    $this->pageStats = $pageStats;
  }
  public function getPageStats() {
    return $this->pageStats;
  }
  public function setResponseCode( $responseCode) {
    $this->responseCode = $responseCode;
  }
  public function getResponseCode() {
    return $this->responseCode;
  }
  public function setScore( $score) {
    $this->score = $score;
  }
  public function getScore() {
    return $this->score;
  }
  public function setScreenshot(Google_ResultScreenshot $screenshot) {
    $this->screenshot = $screenshot;
  }
  public function getScreenshot() {
    return $this->screenshot;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setVersion(Google_ResultVersion $version) {
    $this->version = $version;
  }
  public function getVersion() {
    return $this->version;
  }
}

class Google_ResultFormattedResults extends Google_Model {
  public $locale;
  protected $__ruleResultsType = 'Google_ResultFormattedResultsRuleResultsElement';
  protected $__ruleResultsDataType = 'map';
  public $ruleResults;
  public function setLocale( $locale) {
    $this->locale = $locale;
  }
  public function getLocale() {
    return $this->locale;
  }
  public function setRuleResults(Google_ResultFormattedResultsRuleResultsElement $ruleResults) {
    $this->ruleResults = $ruleResults;
  }
  public function getRuleResults() {
    return $this->ruleResults;
  }
}

class Google_ResultFormattedResultsRuleResultsElement extends Google_Model {
  public $localizedRuleName;
  public $ruleImpact;
  public $ruleScore;
  protected $__urlBlocksType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocks';
  protected $__urlBlocksDataType = 'array';
  public $urlBlocks;
  public function setLocalizedRuleName( $localizedRuleName) {
    $this->localizedRuleName = $localizedRuleName;
  }
  public function getLocalizedRuleName() {
    return $this->localizedRuleName;
  }
  public function setRuleImpact( $ruleImpact) {
    $this->ruleImpact = $ruleImpact;
  }
  public function getRuleImpact() {
    return $this->ruleImpact;
  }
  public function setRuleScore( $ruleScore) {
    $this->ruleScore = $ruleScore;
  }
  public function getRuleScore() {
    return $this->ruleScore;
  }
  public function setUrlBlocks(/* array(Google_ResultFormattedResultsRuleResultsElementUrlBlocks) */ $urlBlocks) {
    $this->assertIsArray($urlBlocks, 'Google_ResultFormattedResultsRuleResultsElementUrlBlocks', __METHOD__);
    $this->urlBlocks = $urlBlocks;
  }
  public function getUrlBlocks() {
    return $this->urlBlocks;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocks extends Google_Model {
  protected $__headerType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeader';
  protected $__headerDataType = '';
  public $header;
  protected $__urlsType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrls';
  protected $__urlsDataType = 'array';
  public $urls;
  public function setHeader(Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeader $header) {
    $this->header = $header;
  }
  public function getHeader() {
    return $this->header;
  }
  public function setUrls(/* array(Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrls) */ $urls) {
    $this->assertIsArray($urls, 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrls', __METHOD__);
    $this->urls = $urls;
  }
  public function getUrls() {
    return $this->urls;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeader extends Google_Model {
  protected $__argsType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeaderArgs';
  protected $__argsDataType = 'array';
  public $args;
  public $format;
  public function setArgs(/* array(Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeaderArgs) */ $args) {
    $this->assertIsArray($args, 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeaderArgs', __METHOD__);
    $this->args = $args;
  }
  public function getArgs() {
    return $this->args;
  }
  public function setFormat( $format) {
    $this->format = $format;
  }
  public function getFormat() {
    return $this->format;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksHeaderArgs extends Google_Model {
  public $type;
  public $value;
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setValue( $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrls extends Google_Model {
  protected $__detailsType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetails';
  protected $__detailsDataType = 'array';
  public $details;
  protected $__resultType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResult';
  protected $__resultDataType = '';
  public $result;
  public function setDetails(/* array(Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetails) */ $details) {
    $this->assertIsArray($details, 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetails', __METHOD__);
    $this->details = $details;
  }
  public function getDetails() {
    return $this->details;
  }
  public function setResult(Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResult $result) {
    $this->result = $result;
  }
  public function getResult() {
    return $this->result;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetails extends Google_Model {
  protected $__argsType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetailsArgs';
  protected $__argsDataType = 'array';
  public $args;
  public $format;
  public function setArgs(/* array(Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetailsArgs) */ $args) {
    $this->assertIsArray($args, 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetailsArgs', __METHOD__);
    $this->args = $args;
  }
  public function getArgs() {
    return $this->args;
  }
  public function setFormat( $format) {
    $this->format = $format;
  }
  public function getFormat() {
    return $this->format;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsDetailsArgs extends Google_Model {
  public $type;
  public $value;
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setValue( $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResult extends Google_Model {
  protected $__argsType = 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResultArgs';
  protected $__argsDataType = 'array';
  public $args;
  public $format;
  public function setArgs(/* array(Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResultArgs) */ $args) {
    $this->assertIsArray($args, 'Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResultArgs', __METHOD__);
    $this->args = $args;
  }
  public function getArgs() {
    return $this->args;
  }
  public function setFormat( $format) {
    $this->format = $format;
  }
  public function getFormat() {
    return $this->format;
  }
}

class Google_ResultFormattedResultsRuleResultsElementUrlBlocksUrlsResultArgs extends Google_Model {
  public $type;
  public $value;
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setValue( $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_ResultPageStats extends Google_Model {
  public $cssResponseBytes;
  public $flashResponseBytes;
  public $htmlResponseBytes;
  public $imageResponseBytes;
  public $javascriptResponseBytes;
  public $numberCssResources;
  public $numberHosts;
  public $numberJsResources;
  public $numberResources;
  public $numberStaticResources;
  public $otherResponseBytes;
  public $textResponseBytes;
  public $totalRequestBytes;
  public function setCssResponseBytes( $cssResponseBytes) {
    $this->cssResponseBytes = $cssResponseBytes;
  }
  public function getCssResponseBytes() {
    return $this->cssResponseBytes;
  }
  public function setFlashResponseBytes( $flashResponseBytes) {
    $this->flashResponseBytes = $flashResponseBytes;
  }
  public function getFlashResponseBytes() {
    return $this->flashResponseBytes;
  }
  public function setHtmlResponseBytes( $htmlResponseBytes) {
    $this->htmlResponseBytes = $htmlResponseBytes;
  }
  public function getHtmlResponseBytes() {
    return $this->htmlResponseBytes;
  }
  public function setImageResponseBytes( $imageResponseBytes) {
    $this->imageResponseBytes = $imageResponseBytes;
  }
  public function getImageResponseBytes() {
    return $this->imageResponseBytes;
  }
  public function setJavascriptResponseBytes( $javascriptResponseBytes) {
    $this->javascriptResponseBytes = $javascriptResponseBytes;
  }
  public function getJavascriptResponseBytes() {
    return $this->javascriptResponseBytes;
  }
  public function setNumberCssResources( $numberCssResources) {
    $this->numberCssResources = $numberCssResources;
  }
  public function getNumberCssResources() {
    return $this->numberCssResources;
  }
  public function setNumberHosts( $numberHosts) {
    $this->numberHosts = $numberHosts;
  }
  public function getNumberHosts() {
    return $this->numberHosts;
  }
  public function setNumberJsResources( $numberJsResources) {
    $this->numberJsResources = $numberJsResources;
  }
  public function getNumberJsResources() {
    return $this->numberJsResources;
  }
  public function setNumberResources( $numberResources) {
    $this->numberResources = $numberResources;
  }
  public function getNumberResources() {
    return $this->numberResources;
  }
  public function setNumberStaticResources( $numberStaticResources) {
    $this->numberStaticResources = $numberStaticResources;
  }
  public function getNumberStaticResources() {
    return $this->numberStaticResources;
  }
  public function setOtherResponseBytes( $otherResponseBytes) {
    $this->otherResponseBytes = $otherResponseBytes;
  }
  public function getOtherResponseBytes() {
    return $this->otherResponseBytes;
  }
  public function setTextResponseBytes( $textResponseBytes) {
    $this->textResponseBytes = $textResponseBytes;
  }
  public function getTextResponseBytes() {
    return $this->textResponseBytes;
  }
  public function setTotalRequestBytes( $totalRequestBytes) {
    $this->totalRequestBytes = $totalRequestBytes;
  }
  public function getTotalRequestBytes() {
    return $this->totalRequestBytes;
  }
}

class Google_ResultScreenshot extends Google_Model {
  public $data;
  public $height;
  public $mime_type;
  public $width;
  public function setData( $data) {
    $this->data = $data;
  }
  public function getData() {
    return $this->data;
  }
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setMime_type( $mime_type) {
    $this->mime_type = $mime_type;
  }
  public function getMime_type() {
    return $this->mime_type;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_ResultVersion extends Google_Model {
  public $major;
  public $minor;
  public function setMajor( $major) {
    $this->major = $major;
  }
  public function getMajor() {
    return $this->major;
  }
  public function setMinor( $minor) {
    $this->minor = $minor;
  }
  public function getMinor() {
    return $this->minor;
  }
}
