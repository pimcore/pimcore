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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Reports;

use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Tool\CustomReport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/custom-report")
 */
class CustomReportController extends ReportsControllerBase implements EventedControllerInterface
{
    /**
     * @Route("/tree")
     * @param Request $request
     * @return JsonResponse
     */
    public function treeAction(Request $request)
    {
        $reports = CustomReport\Config::getReportsList();

        if ($request->get("portlet")) {
            return $this->json(["data" => $reports]);
        } else {
            return $this->json($reports);
        }
    }

    /**
     * @Route("/add")
     * @param Request $request
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $success = false;

        $report = CustomReport\Config::getByName($request->get("name"));

        if (!$report) {
            $report = new CustomReport\Config();
            $report->setName($request->get("name"));
            $report->save();

            $success = true;
        }

        return $this->json(["success" => $success, "id" => $report->getName()]);
    }

    /**
     * @Route("/delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        $report = CustomReport\Config::getByName($request->get("name"));
        $report->delete();

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $report = CustomReport\Config::getByName($request->get("name"));

        return $this->json($report);
    }

    /**
     * @Route("/update")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $report = CustomReport\Config::getByName($request->get("name"));
        $data = $this->decodeJson($request->get("configuration"));

        if (!is_array($data["yAxis"])) {
            $data["yAxis"] = strlen($data["yAxis"]) ? [$data["yAxis"]] : [];
        }

        foreach ($data as $key => $value) {
            $setter = "set" . ucfirst($key);
            if (method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/column-config")
     * @param Request $request
     * @return JsonResponse
     */
    public function columnConfigAction(Request $request)
    {
        $report = CustomReport\Config::getByName($request->get("name"));
        $columnConfiguration = $report->getColumnConfiguration();
        if (!is_array($columnConfiguration)) {
            $columnConfiguration = [];
        }

        $configuration = json_decode($request->get("configuration"));
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

        return $this->json([
            "success" => $success,
            "columns" => $result,
            "errorMessage" => $errorMessage
        ]);
    }


    /**
     * @Route("/get-report-config")
     * @param Request $request
     * @return JsonResponse
     */
    public function getReportConfigAction(Request $request)
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

        return $this->json([
            "success" => true,
            "reports" => $reports
        ]);
    }

    /**
     * @Route("/data")
     * @param Request $request
     * @return JsonResponse
     */
    public function dataAction(Request $request)
    {
        $offset = $request->get("start", 0);
        $limit = $request->get("limit", 40);
        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
        if ($sortingSettings['orderKey']) {
            $sort = $sortingSettings['orderKey'];
            $dir = $sortingSettings['order'];
        }

        $filters = ($request->get("filter") ? json_decode($request->get("filter"), true) : null);

        $drillDownFilters = $request->get("drillDownFilters", null);

        $config = CustomReport\Config::getByName($request->get("name"));
        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, $offset, $limit, null, $drillDownFilters, $config);


        return $this->json([
            "success" => true,
            "data" => $result['data'],
            "total" => $result['total']
        ]);
    }

    /**
     * @Route("/drill-down-options")
     * @param Request $request
     * @return JsonResponse
     */
    public function drillDownOptionsAction(Request $request)
    {
        $field = $request->get("field");
        $filters = ($request->get("filter") ? json_decode($request->get("filter"), true) : null);
        $drillDownFilters = $request->get("drillDownFilters", null);

        $config = CustomReport\Config::getByName($request->get("name"));
        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);
        $result = $adapter->getAvailableOptions($filters, $field, $drillDownFilters);

        return $this->json([
            "success" => true,
            "data" => $result['data'],
        ]);
    }

    /**
     * @Route("/chart")
     * @param Request $request
     * @return JsonResponse
     */
    public function chartAction(Request $request)
    {
        $sort = $request->get("sort");
        $dir = $request->get("dir");
        $filters = ($request->get("filter") ? json_decode($request->get("filter"), true) : null);
        $drillDownFilters = $request->get("drillDownFilters", null);

        $config = CustomReport\Config::getByName($request->get("name"));

        $configuration = $config->getDataSourceConfig();

        $adapter = CustomReport\Config::getAdapter($configuration, $config);

        $result = $adapter->getData($filters, $sort, $dir, null, null, null, $drillDownFilters);

        return $this->json([
            "success" => true,
            "data" => $result['data'],
            "total" => $result['total']
        ]);
    }

    /**
     * @Route("/download-csv")
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function downloadCsvAction(Request $request)
    {
        set_time_limit(300);

        $sort = $request->get("sort");
        $dir = $request->get("dir");
        $filters = ($request->get("filter") ? json_decode($request->get("filter"), true) : null);
        $drillDownFilters = $request->get("drillDownFilters", null);
        $includeHeaders = $request->get('headers', false);

        $config = CustomReport\Config::getByName($request->get("name"));

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
            fputcsv($fp, array_values($row), ';');
        }

        fclose($fp);

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set("Content-Type", "text/csv; charset=UTF-8");
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "export.csv");
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $this->checkPermission("reports");
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
