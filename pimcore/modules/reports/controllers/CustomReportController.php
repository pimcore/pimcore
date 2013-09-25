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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Reports_CustomReportController extends Pimcore_Controller_Action_Admin_Reports {

    public function init() {
        parent::init();

        $this->checkPermission("reports");
    }

    public function treeAction () {

        $reports = Tool_CustomReport_Config::getReportsList();

        if($this->getParam("portlet")) {
            $this->_helper->json(array("data" => $reports));
        } else {
            $this->_helper->json($reports);
        }


    }

    public function addAction () {

        try {
            Tool_CustomReport_Config::getByName($this->getParam("name"));
            $alreadyExist = true;
        } catch (Exception $e) {
            $alreadyExist = false;
        }

        if(!$alreadyExist) {
            $report = new Tool_CustomReport_Config();
            $report->setName($this->getParam("name"));
            $report->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $report->getName()));
    }

    public function deleteAction () {

        $report = Tool_CustomReport_Config::getByName($this->getParam("name"));
        $report->delete();

        $this->_helper->json(array("success" => true));
    }


    public function getAction () {

        $report = Tool_CustomReport_Config::getByName($this->getParam("name"));
        $this->_helper->json($report);
    }


    public function updateAction () {

        $report = Tool_CustomReport_Config::getByName($this->getParam("name"));
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

    public function columnConfigAction() {

        $configuration = json_decode($this->getParam("configuration"));
        $configuration = $configuration[0];

        $success = false;
        $columns = null;
        $errorMessage = null;

        try {

            $adapter = Tool_CustomReport_Config::getAdapter($configuration);
            $columns = $adapter->getColumns($configuration);
            $success = true;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $this->_helper->json(array(
                                  "success" => $success,
                                  "columns" => $columns,
                                  "errorMessage" => $errorMessage
                             ));
    }


    public function getReportConfigAction() {
        $dir = Tool_CustomReport_Config::getWorkingDir();

        $reports = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $name = str_replace(".xml", "", $file);
                $report = Tool_CustomReport_Config::getByName($name);
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

        $offset = $this->getParam("start", 0);
        $limit = $this->getParam("limit", 40);
        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filters = ($this->getParam("filter") ? json_decode($this->getParam("filter"), true) : null);

        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = Tool_CustomReport_Config::getByName($this->getParam("name"));
        $configuration = $config->getDataSourceConfig();
        $configuration = $configuration[0];

        $adapter = Tool_CustomReport_Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, $offset, $limit, null, $drillDownFilters, $config);


        $this->_helper->json(array(
                                  "success" => true,
                                  "data" => $result['data'],
                                  "total" => $result['total']
                             ));
    }

    public function drillDownOptionsAction() {

        $field = $this->getParam("field");
        $filters = ($this->getParam("filter") ? json_decode($this->getParam("filter"), true) : null);
        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = Tool_CustomReport_Config::getByName($this->getParam("name"));
        $configuration = $config->getDataSourceConfig();
        $configuration = $configuration[0];

        $adapter = Tool_CustomReport_Config::getAdapter($configuration, $config);
        $result = $adapter->getAvailableOptions($filters, $field, $drillDownFilters);
        $this->_helper->json(array(
            "success" => true,
            "data" => $result['data'],
        ));
    }

    public function chartAction() {
        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filters = ($this->_getParam("filter") ? json_decode($this->getParam("filter"), true) : null);
        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = Tool_CustomReport_Config::getByName($this->getParam("name"));

        $configuration = $config->getDataSourceConfig();
        $configuration = $configuration[0];
        $adapter = Tool_CustomReport_Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, null, null, null, $drillDownFilters);

        $this->_helper->json(array(
                                  "success" => true,
                                  "data" => $result['data'],
                                  "total" => $result['total']
                             ));
    }

    public function downloadCsvAction() {
        set_time_limit(300);

        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filters = ($this->_getParam("filter") ? json_decode($this->getParam("filter"), true) : null);
        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = Tool_CustomReport_Config::getByName($this->getParam("name"));

        $columns = $config->getColumnConfiguration();
        $fields = array();
        foreach($columns as $column) {
            if($column['export']) {
                $fields[] = $column['name'];
            }
        }

        $configuration = $config->getDataSourceConfig();
        $configuration = $configuration[0];
        $adapter = Tool_CustomReport_Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, null, null, $fields, $drillDownFilters);

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/report-export-" . uniqid() . ".csv";
        @unlink($exportFile);

        $fp = fopen($exportFile, 'w');

        foreach ($result['data'] as $row) {
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


}

