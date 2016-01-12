<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use Pimcore\Google;
use Pimcore\Model\Document;

class Reports_AnalyticsController extends \Pimcore\Controller\Action\Admin\Reports {

    /**
     * @var \Google_Client
     */
    protected $service;


    public function init () {
        parent::init();

        $client = Google\Api::getServiceClient();
        if(!$client) {
            die("Google Analytics is not configured");
        }

        $this->service = new Google_Service_Analytics($client);
    }

    public function deeplinkAction () {

        $config = Google\Analytics::getSiteConfig();

        $url = $this->getParam("url");
        $url = str_replace(array("{accountId}", "{internalWebPropertyId}", "{id}"), array($config->accountid, $config->internalid, $config->profile), $url);
        $url = "https://www.google.com/analytics/web/" . $url;

        $this->redirect($url);
    }

    public function getProfilesAction () {

        try {
            $data = array("data" => array());
            $result = $this->service->management_accounts->listManagementAccounts();

            $accountIds = array();
            if (is_array($result['items'])) {
                foreach($result['items'] as $account) {
                    $accountIds[] = $account['id'];

                }
            }

            foreach($accountIds as $accountId) {
                $details = $this->service->management_profiles->listManagementProfiles($accountId, "~all");

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
        catch (\Exception $e) {

            $this->_helper->json(false);
        }
    }


    private function getSite () {
        $siteId = $this->getParam("site");

        try {
           $site = Site::getById($siteId);
        }
        catch (\Exception $e) {
            return;
        }

        return $site;
    }

    protected function getFilterPath() {
        if($this->getParam("type") == "document" && $this->getParam("id")) {
            $doc = Document::getById($this->getParam("id"));
            $path = $doc->getFullPath();

            if($doc instanceof Document\Page && $doc->getPrettyUrl()) {
                $path = $doc->getPrettyUrl();
            }

            if($this->getParam("site")) {
                $site = Site::getById($this->getParam("site"));
                $path = preg_replace("@^" . preg_quote($site->getRootPath(), "@") . "/@", "/", $path);
            }
            return $path;
        }

        return $this->getParam("path");
    }


    public function chartmetricdataAction () {

        $config = Google\Analytics::getSiteConfig($this->getSite());
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

        if($filterPath = $this->getFilterPath()) {
            $filters[] = "ga:pagePath==".$filterPath;
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

        $config = Google\Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");

        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }


        if($filterPath = $this->getFilterPath()) {
            $filters[] = "ga:pagePath==".$filterPath;
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
                "chart" => \Pimcore\Helper\ImageChart::lineSmall($dailyDataGrouped[$key]),
                "metric" => str_replace("ga:","",$key)
            );
        }

        ksort($outputData);

        $this->_helper->json(array("data" => $outputData));
    }



    public function sourceAction () {

        $config = Google\Analytics::getSiteConfig($this->getSite());
        $startDate = date("Y-m-d",(time()-(86400*31)));
		$endDate = date("Y-m-d");

        if($this->getParam("dateFrom") && $this->getParam("dateTo")) {
            $startDate = date("Y-m-d",strtotime($this->getParam("dateFrom")));
            $endDate = date("Y-m-d",strtotime($this->getParam("dateTo")));
        }

        if($filterPath = $this->getFilterPath()) {
            $filters[] = "ga:pagePath==".$filterPath;
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

		foreach((array) $result["rows"] as $row){
            $data[] = array(
                "pageviews" => $row[1],
                "source" => $row[0]
            );
        }

        $this->_helper->json(array("data" => $data));
    }

    public function dataExplorerAction () {

        $config = Google\Analytics::getSiteConfig($this->getSite());
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

        if($filterPath = $this->getFilterPath()) {
            $filters[] = "ga:pagePath==".$filterPath;
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


    public function getDimensionsAction () {

        $this->_helper->json(array("data" => Google\Api::getAnalyticsDimensions()));
    }


    public function getMetricsAction () {

        $this->_helper->json(array("data" => Google\Api::getAnalyticsMetrics()));
    }

    public function getSegmentsAction() {
        $result = $this->service->management_segments->listManagementSegments();

        $data = array();

        foreach($result['items'] as $row) {
            $data[] = array(
                "id" => $row['segmentId'],
                "name" => $row['name']
            );
        }

        $this->_helper->json(array("data" => $data));
    }


    protected function formatDimension ($type, $value) {

        if(strpos($type,"date") !== false) {
            $date = new \Zend_Date(strtotime($value));
            return $date->get(\Zend_Date::DATE_MEDIUM);
        }

        return $value;
    }

    private function formatDuration ($sec) {

        $minutes = intval(($sec / 60) % 60);
        $seconds = intval($sec % 60);
        return str_pad($minutes,2,"0", STR_PAD_LEFT).":".str_pad($seconds,2,"0", STR_PAD_LEFT);
    }
}
