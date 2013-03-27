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

class Reports_SqlController extends Pimcore_Controller_Action_Admin_Reports {

    public function init() {
        parent::init();

        $this->checkPermission("reports");
    }

    public function treeAction () {

        $dir = Tool_SqlReport_Config::getWorkingDir();

        $reports = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $reports[] = array(
                    "id" => $name,
                    "text" => $name
                );
            }
        }

        $this->_helper->json($reports);
    }

    public function addAction () {

        try {
            Tool_SqlReport_Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $report = new Tool_SqlReport_Config();
            $report->setName($this->getParam("name"));
            $report->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $report->getName()));
    }

    public function deleteAction () {

        $report = Tool_SqlReport_Config::getByName($this->getParam("name"));
        $report->delete();

        $this->_helper->json(array("success" => true));
    }


    public function getAction () {

        $report = Tool_SqlReport_Config::getByName($this->getParam("name"));
        $this->_helper->json($report);
    }


    public function updateAction () {

        $report = Tool_SqlReport_Config::getByName($this->getParam("name"));
        $data = Zend_Json::decode($this->getParam("configuration"));
        $data = array_htmlspecialchars($data);

        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if(method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        $this->_helper->json(array("success" => true));
    }

    public function sqlConfigAction() {

        $sql = $this->getParam("sql");
        $success = false;
        $res = null;
        $errorMessage = null;
        $columns = null;

        try {
            if(!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {

                $sql .= " LIMIT 0,1";

                $db = Pimcore_Resource::get();
                $res = $db->fetchRow($sql);
                $columns = array_keys($res);
                $success = true;
            } else {
                $errorMessage = "Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'";
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $this->_helper->json(array(
            "success" => $success,
            "columns" => $columns,
            "errorMessage" => $errorMessage
        ));
    }

    public function getReportConfigAction() {
        $dir = Tool_SqlReport_Config::getWorkingDir();

        $reports = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $report = Tool_SqlReport_Config::getByName($name);
                $reports[] = array(
                    "name" => $report->getName(),
                    "niceName" => $report->getNiceName(),
                    "iconClass" => $report->getIconClass(),
                    "group" => $report->getGroup(),
                    "groupIconClass" => $report->getGroupIconClass(),
                    "menuShortcut" => $report->getMenuShortcut()
                );
            }
        }

        $this->_helper->json(array(
            "success" => true,
            "reports" => $reports
        ));
    }

    public function dataAction() {

        $db = Pimcore_Resource::get();
        $data = array();
        $offset = $this->getParam("start", 0);
        $limit = $this->getParam("limit", 40);
        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");

        $baseQuery = $this->getBaseQuery();

        if($baseQuery) {
            $total = $db->fetchOne($baseQuery["count"]);

            $order = "";
            if($sort && dir) {
                $order = " ORDER BY " . $db->quoteIdentifier($sort) . " " . $dir;
            }

            $sql = $baseQuery["data"] . $order . " LIMIT $offset,$limit";
            $data = $db->fetchAll($sql);
        }

        $this->_helper->json(array(
            "success" => true,
            "data" => $data,
            "total" => $total
        ));
    }

    public function downloadCsvAction() {

        set_time_limit(300);

        $db = Pimcore_Resource::get();
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/report-export-" . uniqid() . ".csv";
        @unlink($exportFile);

        $baseQuery = $this->getBaseQuery();
        $result = $db->fetchAll($baseQuery["data"]);

        $fp = fopen($exportFile, 'w');

        foreach ($result as $row) {
            fputcsv($fp, array_values($row));
        }

        fclose($fp);

        header("Content-type: text/plain");
        header("Content-Length: " . filesize($exportFile));
        header("Content-Disposition: attachment; filename=\"export.csv\"");

        while(@ob_end_flush());
        flush();
        readfile($exportFile);

        exit;
    }

    protected function getBaseQuery() {
        $db = Pimcore_Resource::get();
        $condition = array("1 = 1");

        $report = Tool_SqlReport_Config::getByName($this->getParam("name"));
        $sql = $report->getSql();
        $data = "";

        if($this->getParam("filter")) {
            $filters = Zend_Json::decode($this->getParam("filter"));

            if(is_array($filters)) {
                foreach ($filters as $filter) {
                    if($filter["type"] == "string") {
                        $condition[] = $db->quoteIdentifier($filter["field"]) . " LIKE " . $db->quote("%" . $filter["value"] . "%");
                    } else if($filter["type"] == "numeric") {
                        $compMapping = array(
                            "lt" => "<",
                            "gt" => ">",
                            "eq" => "="
                        );
                        if($compMapping[$filter["comparison"]]) {
                            $condition[] = $db->quoteIdentifier($filter["field"]) . " " . $compMapping[$filter["comparison"]] . " " . $db->quote($filter["value"]);
                        }
                    } else if ($filter["type"] == "boolean") {
                        $condition[] = $db->quoteIdentifier($filter["field"]) . " = " . $db->quote((int)$filter["value"]);
                    } else if ($filter["type"] == "date") {

                    }
                }
            }
        }

        if(!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {

            $condition = implode(" AND ", $condition);

            $total = "SELECT COUNT(*) FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            $data = "SELECT * FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
        } else {
            return;
        }

        return array(
            "data" => $data,
            "count" => $total
        );
    }
}

