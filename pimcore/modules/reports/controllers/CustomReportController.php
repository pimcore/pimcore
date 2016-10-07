<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Model\Tool\CustomReport;

class Reports_CustomReportController extends \Pimcore\Controller\Action\Admin\Reports
{
    public function init()
    {
        parent::init();

        $this->checkPermission("reports");
    }

    public function treeAction()
    {
        $reports = CustomReport\Config::getReportsList();

        if ($this->getParam("portlet")) {
            $this->_helper->json(["data" => $reports]);
        } else {
            $this->_helper->json($reports);
        }
    }

    public function addAction()
    {
        $success = false;

        $report = CustomReport\Config::getByName($this->getParam("name"));

        if (!$report) {
            $report = new CustomReport\Config();
            $report->setName($this->getParam("name"));
            $report->save();

            $success = true;
        }

        $this->_helper->json(["success" => $success, "id" => $report->getName()]);
    }

    public function deleteAction()
    {
        $report = CustomReport\Config::getByName($this->getParam("name"));
        $report->delete();

        $this->_helper->json(["success" => true]);
    }


    public function getAction()
    {
        $report = CustomReport\Config::getByName($this->getParam("name"));
        $this->_helper->json($report);
    }


    public function updateAction()
    {
        $report = CustomReport\Config::getByName($this->getParam("name"));
        $data = \Zend_Json::decode($this->getParam("configuration"));

        if (\Pimcore\Tool\Admin::isExtJS6() && !is_array($data["yAxis"])) {
            $data["yAxis"] = strlen($data["yAxis"]) ? [$data["yAxis"]] : [];
        }


        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        $this->_helper->json(["success" => true]);
    }

    public function columnConfigAction()
    {
        $report = CustomReport\Config::getByName($this->getParam("name"));
        $columnConfiguration = $report->getColumnConfiguration();
        if (!is_array($columnConfiguration)) {
            $columnConfiguration = [];
        }

        $configuration = json_decode($this->getParam("configuration"));
        $configuration = $configuration[0];

        $success = false;
        $columns = null;
        $errorMessage = null;

        $result = [];

        try {
            $adapter = CustomReport\Config::getAdapter($configuration);
            $columns = $adapter->getColumns($configuration);
            if (!is_array($columns)) {
                $columns = [];
            }

            foreach ($columnConfiguration as $item) {
                $name = $item["name"];
                if (in_array($name, $columns)) {
                    $result[] = $name;
                    array_splice($columns, array_search($name, $columns), 1);
                }
            }
            foreach ($columns as $remainingColumn) {
                $result[] = $remainingColumn;
            }

            $success = true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $this->_helper->json([
            "success" => $success,
            "columns" => $result,
            "errorMessage" => $errorMessage
        ]);
    }


    public function getReportConfigAction()
    {
        $reports = [];

        $list = new CustomReport\Config\Listing();
        $items = $list->load();

        /** @var  $report CustomReport\Config */
        foreach ($items as $report) {
            $reports[] = [
                "name" => $report->getName(),
                "niceName" => $report->getNiceName(),
                "iconClass" => $report->getIconClass(),
                "group" => $report->getGroup(),
                "groupIconClass" => $report->getGroupIconClass(),
                "menuShortcut" => $report->getMenuShortcut(),
                "reportClass" => $report->getReportClass()
            ];
        }

        $this->_helper->json([
            "success" => true,
            "reports" => $reports
        ]);
    }

    public function dataAction()
    {
        $offset = $this->getParam("start", 0);
        $limit = $this->getParam("limit", 40);
        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
        if ($sortingSettings['orderKey']) {
            $sort = $sortingSettings['orderKey'];
            $dir = $sortingSettings['order'];
        }

        $filters = ($this->getParam("filter") ? json_decode($this->getParam("filter"), true) : null);

        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = CustomReport\Config::getByName($this->getParam("name"));
        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, $offset, $limit, null, $drillDownFilters, $config);


        $this->_helper->json([
            "success" => true,
            "data" => $result['data'],
            "total" => $result['total']
        ]);
    }

    public function drillDownOptionsAction()
    {
        $field = $this->getParam("field");
        $filters = ($this->getParam("filter") ? json_decode($this->getParam("filter"), true) : null);
        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = CustomReport\Config::getByName($this->getParam("name"));
        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);
        $result = $adapter->getAvailableOptions($filters, $field, $drillDownFilters);
        $this->_helper->json([
            "success" => true,
            "data" => $result['data'],
        ]);
    }

    public function chartAction()
    {
        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filters = ($this->getParam("filter") ? json_decode($this->getParam("filter"), true) : null);
        $drillDownFilters = $this->getParam("drillDownFilters", null);

        $config = CustomReport\Config::getByName($this->getParam("name"));

        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, null, null, null, $drillDownFilters);

        $this->_helper->json([
            "success" => true,
            "data" => $result['data'],
            "total" => $result['total']
        ]);
    }

    public function downloadCsvAction()
    {
        set_time_limit(300);

        $sort = $this->getParam("sort");
        $dir = $this->getParam("dir");
        $filters = ($this->getParam("filter") ? json_decode($this->getParam("filter"), true) : null);
        $drillDownFilters = $this->getParam("drillDownFilters", null);
        $includeHeaders = $this->getParam('headers', false);

        $config = CustomReport\Config::getByName($this->getParam("name"));

        $columns = $config->getColumnConfiguration();
        $fields = [];
        foreach ($columns as $column) {
            if ($column['export']) {
                $fields[] = $column['name'];
            }
        }

        $configuration = $config->getDataSourceConfig();
        //if many rows returned as an array than use the first row. Fixes: #782
        $configuration = is_array($configuration)
            ? $configuration[0]
            : $configuration;

        $adapter = CustomReport\Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, null, null, $fields, $drillDownFilters);

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/report-export-" . uniqid() . ".csv";
        @unlink($exportFile);

        $fp = fopen($exportFile, 'w');

        if ($includeHeaders) {
            fputcsv($fp, $fields);
        }

        foreach ($result['data'] as $row) {
            fputcsv($fp, array_values($row));
        }

        fclose($fp);

        header('Content-type: text/csv; charset=UTF-8');
        header("Content-Length: " . filesize($exportFile));
        header("Content-Disposition: attachment; filename=\"export.csv\""); while (@ob_end_flush());
        flush();
        readfile($exportFile);

        exit;
    }
}
