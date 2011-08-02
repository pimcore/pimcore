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
    
    
    public function init () {
        parent::init();
        
        $credentials = $this->getAnalyticsCredentials();
        if(!$credentials) {
            die("Analytics not configured");
        }
    }
    
    protected function getQuery($site) {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($site);

        if(!$config) {
            die("Analytics not configured");
        }
        
        $credentials = $this->getAnalyticsCredentials();
        $query = $this->getService()->newDataQuery()->setProfileId($config->profile);
        
        return $query;
    }
    
    protected function getService () {
        
        $credentials = $this->getAnalyticsCredentials();

        $client = Zend_Gdata_ClientLogin::getHttpClient($credentials["username"], $credentials["password"], Zend_Gdata_Analytics::AUTH_SERVICE_NAME, Pimcore_Tool::getHttpClient("Zend_Gdata_HttpClient"));
		$service = new Zend_Gdata_Analytics($client, "pimcore-open-source-CMS-framework");
        
        return $service;
    }
    
    private function getSite () {
        $siteId = $this->_getParam("site");
        
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
        
        if($this->_getParam("dateFrom") && $this->_getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->_getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->_getParam("dateTo"))); 
        }
        
        $metrics = array("ga:pageviews");
        if($this->_getParam("metric")) {
            $metrics = array();
            
            if(is_array($this->_getParam("metric"))) {
                foreach ($this->_getParam("metric") as $m) {
                    $metrics[] = "ga:" . $m;
                }
            }
            else {
                $metrics[] = "ga:" . $this->_getParam("metric");
            }
            
        }
       
		$query = $this->getQuery($this->getSite());
	
		$query->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE)
			->setStartDate($startDate)
			->setEndDate($endDate);
                
        
        // add metrics 
        foreach ($metrics as $metric) {
	       $query->addMetric($metric);
        }
        
        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
        
		$result = $this->getService()->getDataFeed($query);
        
        $data = array();
        
		foreach($result as $row){

            $date = new Zend_Date(strtotime($row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE)));
            
            $tmpData = array(
                "timestamp" => $date->getTimestamp(),
                "datetext" => $this->formatDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE,(string) $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE))
            );
            
            foreach ($metrics as $metric) {
                if(!$this->_getParam("dataField")) {
                    $tmpData[str_replace("ga:","",$metric)] = (int) $row->getMetric($metric)->getValue();
                } else {
                    $tmpData[$this->_getParam("dataField")] = (int) $row->getMetric($metric)->getValue();
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
        
        if($this->_getParam("dateFrom") && $this->_getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->_getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->_getParam("dateTo"))); 
        }

        $query = $this->getQuery($this->getSite());
        
        $query->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE)
			->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_UNIQUE_PAGEVIEWS)
			->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)
			->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_EXITS)
			->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES)
			->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES)
			->setStartDate($startDate)
			->setEndDate($endDate);
        

        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
		$result = $this->getService()->getDataFeed($query);
        
        $data = array();
        $dailyData = array();
        $dailyDataGrouped = array();

        
		foreach($result as $row){

            $date = $this->formatDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE, $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE));
            
            $dailyDataGrouped[Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS][] = (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)->getValue();
            $dailyDataGrouped[Zend_Gdata_Analytics_DataQuery::METRIC_UNIQUE_PAGEVIEWS][] = (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_UNIQUE_PAGEVIEWS)->getValue();
            $dailyDataGrouped[Zend_Gdata_Analytics_DataQuery::METRIC_EXITS][] = (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_EXITS)->getValue();          
            $dailyDataGrouped[Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES][] = (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES)->getValue(); 
            $dailyDataGrouped[Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES][] = (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES)->getValue();
            
                       
            $data[Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES] += (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES)->getValue();
            $data[Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES] += (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES)->getValue();
            $data[Zend_Gdata_Analytics_DataQuery::METRIC_EXITS] += (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_EXITS)->getValue();
            $data[Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS] += (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)->getValue();
            $data[Zend_Gdata_Analytics_DataQuery::METRIC_UNIQUE_PAGEVIEWS] += (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_UNIQUE_PAGEVIEWS)->getValue();
        }
                
        //$data[Zend_Gdata_Analytics_DataQuery::METRIC_EXITS] = ($data[Zend_Gdata_Analytics_DataQuery::METRIC_EXITS]/$data[Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS]) * 100;
        //$data[Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES] = $data["bounces"] / $data[Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES] * 100;
        
        //unset($data["bounces"]);
        
        $order = array(
            Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS => 0,
            Zend_Gdata_Analytics_DataQuery::METRIC_UNIQUE_PAGEVIEWS => 1,
            Zend_Gdata_Analytics_DataQuery::METRIC_EXITS => 2,
            Zend_Gdata_Analytics_DataQuery::METRIC_ENTRANCES => 3,
            Zend_Gdata_Analytics_DataQuery::METRIC_BOUNCES => 4
        );
        
        $outputData = array();
        foreach ($data as $key => $value) {
            $outputData[$order[$key]] = array(
                "label" => str_replace("ga:","",$key),
                "value" => round($value,2),
                "chart" => Pimcore_Report_ImageChart::lineSmall($dailyDataGrouped[$key]),
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
        
        if($this->_getParam("dateFrom") && $this->_getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->_getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->_getParam("dateTo"))); 
        }
        
		$query = $this->getQuery($this->getSite());
	
		$query->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_SOURCE)
            ->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)
			->setStartDate($startDate)
			->setEndDate($endDate)
            ->setSort(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS,true)
            ->setMaxResults(10);
 
        
        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
        
		$result = $this->getService()->getDataFeed($query);
        
        $data = array();
        
		foreach($result as $row){
            $data[] = array(
                "pageviews" => (int) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)->getValue(),
                "source" => $this->formatDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_SOURCE, (string) $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_SOURCE))
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
        
        if($this->_getParam("dateFrom") && $this->_getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->_getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->_getParam("dateTo"))); 
        }
        if($this->_getParam("dimension")) {
            $dimension = $this->_getParam("dimension");
        }
        if($this->_getParam("metric")) {
            $metric = $this->_getParam("metric");
        }
        if($this->_getParam("sort")) {
            if($this->_getParam("sort") == "asc") {
                $descending = false;
            }
        }
        if($this->_getParam("limit")) {
            $limit = $this->_getParam("limit");
        }
        
       
		$query = $this->getQuery($this->getSite());
	
		$query->addDimension($dimension)
            ->addMetric($metric)
			->setStartDate($startDate)
			->setEndDate($endDate)
            ->setSort($metric,$descending)
            ->setMaxResults($limit);
  
        
        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
        
		$result = $this->getService()->getDataFeed($query);
        
        $data = array();
        
		foreach($result as $row){
		  
            $data[] = array(
                "dimension" => $this->formatDimension($dimension,(string) $row->getDimension($dimension)),
                "metric" => (double) $row->getMetric($metric)->getValue()
            );
        }
        
        $this->_helper->json(array("data" => $data));
    }
    
    public function navigationAction () {
        
        $config = Pimcore_Google_Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");
        
        if($this->_getParam("dateFrom") && $this->_getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->_getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->_getParam("dateTo"))); 
        }
        
        // all pageviews
        $query0 = $this->getQuery($this->getSite());
	
		$query0->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH)
            ->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)
			->setStartDate($startDate)
			->setEndDate($endDate)
            ->setSort(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS,true)
            ->setMaxResults(1);
  
        
        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query0->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query0->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
		$result0 = $this->getService()->getDataFeed($query0);
       
        $totalViews = (int) $result0[0]->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)->getValue();
       
       
        // ENTRANCES
		$query1 = $this->getQuery($this->getSite());
	
		$query1->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PREV_PAGE_PATH)
            ->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)
			->setStartDate($startDate)
			->setEndDate($endDate)
            ->setSort(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS,true)
            ->setMaxResults(10);
  
        
        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query1->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query1->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
		$result1 = $this->getService()->getDataFeed($query1);
        
        
        $prev = array();
		foreach($result1 as $row){
            $d =  array(
                "path" => $this->formatDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PREV_PAGE_PATH,(string) $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PREV_PAGE_PATH)),
                "pageviews" => (double) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)->getValue()
            );
            
            $document = Document::getByPath((string) $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PREV_PAGE_PATH));
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
		$query2 = $this->getQuery($this->getSite());
	
		$query2->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH)
            ->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)
			->setStartDate($startDate)
			->setEndDate($endDate)
            ->setSort(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS,true)
            ->setMaxResults(10);
  
        
        if($config->advanced) {
            if($this->_getParam("id") && $this->_getParam("type")) {
                $url = "/pimcoreanalytics/" . $this->_getParam("type") . "/" . $this->_getParam("id");
                $query2->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PREV_PAGE_PATH."==".$url);
            }
        }
        else {
            if($this->_getParam("path")) {
                $query2->setFilter(Zend_Gdata_Analytics_DataQuery::DIMENSION_PREV_PAGE_PATH."==".$this->_getParam("path"));
            }
        }
        
		$result2 = $this->getService()->getDataFeed($query2);
        
        
        $next = array();
		foreach($result2 as $row){
            $d =  array(
                "path" => $this->formatDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH,(string) $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH)),
                "pageviews" => (double) $row->getMetric(Zend_Gdata_Analytics_DataQuery::METRIC_PAGEVIEWS)->getValue()
            );
            
            $document = Document::getByPath((string) $row->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_PAGE_PATH));
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
        $this->view->path = $this->_getParam("path");
        
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
        
        if($type == Zend_Gdata_Analytics_DataQuery::DIMENSION_DATE) {
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
