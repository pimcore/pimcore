<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Reports_AnalyticsController extends Pimcore_Controller_Action_Admin_Reports {

    /**
     * @var apiAnalyticsService
     */
    protected $service;
    
    public function init () {
        parent::init();
        
        $client = Pimcore_Google_Api::getServiceClient();
        if(!$client) {
            die("Google Analytics is not configured");
        }

        $this->service = new apiAnalyticsService($client);
    }

    public function deeplinkAction () {

        $config = Pimcore_Google_Analytics::getSiteConfig();

        $url = $this->getParam("url");
        $url = str_replace(array("{accountId}", "{internalWebPropertyId}", "{id}"), array($config->accountid, $config->internalid, $config->profile), $url);
        $url = "https://www.google.com/analytics/web/" . $url;

        $this->redirect($url);
    }

    public function getProfilesAction () {

        try {
            $data = array("data" => array());
            $result = $this->service->management_webproperties->listManagementWebproperties("~all");


            foreach ($result["items"] as $entry) {

                $details = $this->service->management_profiles->listManagementProfiles($entry["accountId"], $entry["id"]);

                if (is_array($details["items"])) {
                    foreach ($details["items"] as $detail) {
                        $data["data"][] = array(
                            "id" => $detail["id"],
                            "name" => $detail["name"],
                            "trackid" => $detail["webPropertyId"],
                            "internalid" => $detail["internalWebPropertyId"],
                            "accountid" => $detail["accountId"]
                        );
                    }
                }
            }

            $this->_helper->json($data);
        }
        catch (Exception $e) {
            $this->_helper->json(false);
        }
    }

    
    private function getSite () {
        $siteId = $this->getParam("site");
        
        try {
           $site = Site::getById($siteId); 
        }
        catch (Exception $e) {
            return;
        }
        
        return $site;
    }
    
    
    public function chartmetricdataAction () {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");
        
        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }
        
        $metrics = array("ga:pageviews");
        if($this->getParam("metric")) {
            $metrics = array();
            
            if(is_array($this->getParam("metric"))) {
                foreach ($this->getParam("metric") as $m) {
                    $metrics[] = "ga:" . $m;
                }
            }
            else {
                $metrics[] = "ga:" . $this->getParam("metric");
            }
            
        }

        $filters = array();

        if($config->advanced) {
            if($this->getParam("id") && $this->getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->getParam("type") . "/" . $this->getParam("id");
                $filters[] = "ga:pagePath==".$url;
            }
        }
        else {
            if($this->getParam("path")) {
                $filters[] = "ga:pagePath==".$this->getParam("path");
            }
        }

        if($this->getParam("filters")) {
            $filters[] = $this->getParam("filters");
        }

        $opts = array(
            "dimensions" => "ga:date"
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            implode(",",$metrics),
            $opts
        );


        $data = array();

		foreach($result["rows"] as $row){

            $date = $row[0];
            
            $tmpData = array(
                "timestamp" => strtotime($date),
                "datetext" => $this->formatDimension("date", $date)
            );
            
            foreach ($result["columnHeaders"] as $index => $metric) {
                if(!$this->getParam("dataField")) {
                    $tmpData[str_replace("ga:","",$metric["name"])] = $row[$index];
                } else {
                    $tmpData[$this->getParam("dataField")] = $row[$index];
                }
            }
            
            $data[] = $tmpData;
        }
        
        $this->_helper->json(array("data" => $data));
    }
    
    
    public function summaryAction () {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");
        
        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }

        if($config->advanced) {
            if($this->getParam("id") && $this->getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->getParam("type") . "/" . $this->getParam("id");
                $filters[] = "ga:pagePath==".$url;
            }
        }
        else {
            if($this->getParam("path")) {
                $filters[] = "ga:pagePath==".$this->getParam("path");
            }
        }


        $opts = array(
            "dimensions" => "ga:date"
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            "ga:uniquePageviews,ga:pageviews,ga:exits,ga:bounces,ga:entrances",
            $opts
        );

        $data = array();
        $dailyDataGrouped = array();

		foreach($result["rows"] as $row){
            foreach ($result["columnHeaders"] as $index => $metric) {
                if($index) {
                    $dailyDataGrouped[$metric["name"]][] = $row[$index];
                    $data[$metric["name"]] += $row[$index];
                }
            }
        }

        
        $order = array(
            "ga:pageviews"=> 0,
            "ga:uniquePageviews" => 1,
            "ga:exits" => 2,
            "ga:entrances" => 3,
            "ga:bounces" => 4
        );
        
        $outputData = array();
        foreach ($data as $key => $value) {
            $outputData[$order[$key]] = array(
                "label" => str_replace("ga:","",$key),
                "value" => round($value,2),
                "chart" => Pimcore_Helper_ImageChart::lineSmall($dailyDataGrouped[$key]),
                "metric" => str_replace("ga:","",$key)
            );
        }
        
        ksort($outputData);
        
        $this->_helper->json(array("data" => $outputData));
    }
    
    
    
    public function sourceAction () {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");
        
        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }

        if($config->advanced) {
            if($this->getParam("id") && $this->getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->getParam("type") . "/" . $this->getParam("id");
                $filters[] = "ga:pagePath==".$url;
            }
        }
        else {
            if($this->getParam("path")) {
                $filters[] = "ga:pagePath==".$this->getParam("path");
            }
        }


        $opts = array(
            "dimensions" => "ga:source",
            "max-results" => "10",
            "sort" => "-ga:pageviews"
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            "ga:pageviews",
            $opts
        );

        $data = array();
        
		foreach($result["rows"] as $row){
            $data[] = array(
                "pageviews" => $row[1],
                "source" => $row[0]
            );
        }
        
        $this->_helper->json(array("data" => $data));
    }
    
    public function dataExplorerAction () {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");
        $metric = "ga:pageviews";
        $dimension = "ga:date";
        $descending = true;
        $limit = 10;
        
        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }
        if($this->getParam("dimension")) {
            $dimension = $this->getParam("dimension");
        }
        if($this->getParam("metric")) {
            $metric = $this->getParam("metric");
        }
        if($this->getParam("sort")) {
            if($this->getParam("sort") == "asc") {
                $descending = false;
            }
        }
        if($this->getParam("limit")) {
            $limit = $this->getParam("limit");
        }


        if($config->advanced) {
            if($this->getParam("id") && $this->getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->getParam("type") . "/" . $this->getParam("id");
                $filters[] = "ga:pagePath==".$url;
            }
        }
        else {
            if($this->getParam("path")) {
                $filters[] = "ga:pagePath==".$this->getParam("path");
            }
        }


        $opts = array(
            "dimensions" => $dimension,
            "max-results" => $limit,
            "sort" => ($descending ? "-" : "") . $metric
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            $metric,
            $opts
        );

        $data = array();
		foreach($result["rows"] as $row){
		  
            $data[] = array(
                "dimension" => $this->formatDimension($dimension, $row[0]),
                "metric" => (double) $row[1]
            );
        }
        
        $this->_helper->json(array("data" => $data));
    }
    
    public function navigationAction () {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");
        
        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }
        
        // all pageviews
        if($config->advanced) {
            if($this->getParam("id") && $this->getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->getParam("type") . "/" . $this->getParam("id");
                $filters[] = "ga:pagePath==".$url;
            }
        }
        else {
            if($this->getParam("path")) {
                $filters[] = "ga:pagePath==".$this->getParam("path");
            }
        }


        $opts = array(
            "dimensions" => "ga:pagePath",
            "max-results" => 1,
            "sort" => "-ga:pageViews"
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result0 = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            "ga:pageViews",
            $opts
        );

        $totalViews = (int) $result0["totalsForAllResults"]["ga:pageviews"];
       
       
        // ENTRANCES
        $opts = array(
            "dimensions" => "ga:previousPagePath",
            "max-results" => 10,
            "sort" => "-ga:pageViews"
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result1 = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            "ga:pageViews",
            $opts
        );
        
        
        $prev = array();
		foreach($result1["rows"] as $row){
            $d =  array(
                "path" => $this->formatDimension("ga:previousPagePath", $row[0]),
                "pageviews" => $row[1]
            );
            
            $document = Document::getByPath($row[0]);
            if($document) {
                $d["id"] = $document->getId();
            }
            
            $d["percent"] = round($d["pageviews"] / $totalViews * 100);
            
            $d["weight"] = 100;
            if($prev[0]["weight"]) {
                $d["weight"] = round($d["percent"] / $prev[0]["percent"] * 100);
            }
            
            $prev[] = $d;
        }
        
        
        
        // EXITS
        $opts = array(
            "dimensions" => "ga:pagePath",
            "max-results" => 10,
            "sort" => "-ga:pageViews"
        );

        if(!empty($filters)) {
            $opts["filters"] = implode(";", $filters);
        }

        $result2 = $this->service->data_ga->get(
            "ga:" . $config->profile,
            $startDate,
            $endDate,
            "ga:pageViews",
            $opts
        );
        
        
        $next = array();
		foreach($result2["rows"] as $row){
            $d =  array(
                "path" => $this->formatDimension("ga:previousPagePath", $row[0]),
                "pageviews" => $row[1]
            );
            
            $document = Document::getByPath($row[0]);
            if($document) {
                $d["id"] = $document->getId();
            }
            
            $d["percent"] = round($d["pageviews"] / $totalViews * 100);
            
            $d["weight"] = 100;
            if($next[0]["weight"]) {
                $d["weight"] = round($d["percent"] / $next[0]["percent"] * 100);
            }
            
            
            $next[] = $d;
        }
        
        
        $this->view->next = $next;
        $this->view->prev = $prev;
        $this->view->path = $this->getParam("path");
        
        $this->getResponse()->setHeader("Content-Type","application/xml",true);
    }
    
    
    public function getDimensionsAction () {
        
        $t = Zend_Registry::get("Zend_Translate");
        $def = array(
            "ga:browser",
            "ga:browserVersion",
            "ga:city",
            "ga:connectionSpeed",
            "ga:continent",
            "ga:country",
            "ga:date",
            "ga:day",
            "ga:daysSinceLastVisit",
            "ga:flashVersion",
            "ga:hostname",
            "ga:hour",
            "ga:javaEnabled",
            "ga:language",
            "ga:latitude",
            "ga:longitude",
            "ga:month",
            "ga:networkDomain",
            "ga:networkLocation",
            "ga:operatingSystem",
            "ga:operatingSystemVersion",
            "ga:pageDepth",
            "ga:region",
            "ga:screenColors",
            "ga:screenResolution",
            "ga:subContinent",
            "ga:userDefinedValue",
            "ga:visitCount",
            "ga:visitLength",
            "ga:visitorType",
            "ga:week",
            "ga:year",
            
            "ga:adContent",
            "ga:adGroup",
            "ga:adSlot",
            "ga:adSlotPosition",
            "ga:campaign",
            "ga:keyword",
            "ga:medium",
            "ga:referralPath",
            "ga:source",
            
            "ga:exitPagePath",
            "ga:landingPagePath",
            "ga:landingPagePath",
            "ga:pagePath",
            "ga:pageTitle",
            "ga:secondPagePath",
            
            "ga:affiliation",
            "ga:daysToTransaction",
            "ga:productCategory",
            "ga:productName",
            "ga:productSku",
            "ga:transactionId",
            "ga:visitsToTransaction",
            
            "ga:searchCategory",
            "ga:searchDestinationPage",
            "ga:searchKeyword",
            "ga:searchKeywordRefinement",
            "ga:searchStartPage",
            "ga:searchUsed",
            
            "ga:previousPagePath",
            "ga:nextPagePath",
            
            "ga:eventCategory",
            "ga:eventAction",
            "ga:eventLabel",
            
            "ga:customVarName1",
            "ga:customVarName2",
            "ga:customVarName3",
            "ga:customVarName4",
            "ga:customVarName5",
            "ga:customVarValue1",
            "ga:customVarValue2",
            "ga:customVarValue3",
            "ga:customVarValue4",
            "ga:customVarValue5"
        );
        
        foreach ($def as $dimension) {
            $data[] = array(
                "id" => $dimension,
                "name" => $t->translate(str_replace("ga:","",$dimension))
            );
        }
        
        $this->_helper->json(array("data" => $data));
    }
    
    
    public function getMetricsAction () {
        
        $t = Zend_Registry::get("Zend_Translate");
        $def = array(
            "ga:bounces",
            "ga:entrances",
            "ga:exits",
            "ga:newVisits",
            "ga:pageviews",
            "ga:timeOnPage",
            "ga:timeOnSite",
            "ga:visitors",
            "ga:visits",
            "ga:adClicks",
            "ga:adCost",
            "ga:CPC",
            "ga:CPM",
            "ga:CTR",
            "ga:impressions",
            "ga:uniquePageviews",
            "ga:itemRevenue",
            "ga:itemQuantity",
            "ga:transactions",
            "ga:transactionRevenue",
            "ga:transactionShipping",
            "ga:transactionTax",
            "ga:uniquePurchases",
            "ga:searchDepth",
            "ga:searchDuration",
            "ga:searchExits",
            "ga:searchRefinements",
            "ga:searchUniques",
            "ga:searchVisits",
            "ga:goalCompletionsAll",
            "ga:goalStartsAll",
            "ga:goalValueAll",
            "ga:goal1Completions",
            "ga:goal1Starts",
            "ga:goal1Value",
            "ga:totalEvents",
            "ga:uniqueEvents",
            "ga:eventValue"
        );
        
        foreach ($def as $metric) {
            $data[] = array(
                "id" => $metric,
                "name" => $t->translate(str_replace("ga:","",$metric))
            );
        }
        
        $this->_helper->json(array("data" => $data));
    }
    
    
    protected function formatDimension ($type, $value) {
        
        if(strpos($type,"date") !== false) {
            $date = new Zend_Date(strtotime($value));
            return $date->get(Zend_Date::DATE_MEDIUM);
        }
        
        return $value;
    }
    
    private function formatDuration ($sec) {

        $minutes = intval(($sec / 60) % 60);
        $seconds = intval($sec % 60);
        return str_pad($minutes,2,"0", STR_PAD_LEFT).":".str_pad($seconds,2,"0", STR_PAD_LEFT);
    }
}
