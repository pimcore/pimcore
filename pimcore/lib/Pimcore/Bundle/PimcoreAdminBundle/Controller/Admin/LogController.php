<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Db;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AdminController
{

    /**
     * @Route("/log/show")
     * @param Request $request
     * @return JsonResponse
     */
    public function showAction(Request $request)
    {
        $offset = $request->get("start");
        $limit = $request->get("limit");

        $orderby = "ORDER BY id DESC";
        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($request->request->all());
        if ($sortingSettings['orderKey']) {
            $orderby = "ORDER BY " . $sortingSettings['orderKey'] . " " . $sortingSettings['order'];
        }

        $queryString = " WHERE 1=1";

        if ($request->get("priority") != "-1" && ($request->get("priority") == "0" || $request->get("priority"))) {
            $levels = [];
            foreach (["emergency", "alert", "critical", "error", "warning", "notice", "info", "debug"] as $level) {
                $levels[] = "priority = '" . $level . "'";
                
                if ($request->get("priority") == $level) {
                    break;
                }
            }

            $queryString .= " AND (" . implode(" OR ", $levels) . ")";
        }

        if ($request->get("fromDate")) {
            $datetime = $request->get("fromDate");
            if ($request->get("fromTime")) {
                $datetime =  substr($datetime, 0, 11) . substr($request->get("fromTime"), strpos($request->get("fromTime"), 'T')+1, strlen($request->get("fromTime")));
            }
            $queryString .= " AND timestamp >= '" . $datetime . "'";
        }

        if ($request->get("toDate")) {
            $datetime = $request->get("toDate");
            if ($request->get("toTime")) {
                $datetime =  substr($datetime, 0, 11) . substr($request->get("toTime"), strpos($request->get("toTime"), 'T')+1, strlen($request->get("toTime")));
            }
            $queryString .= " AND timestamp <= '" . $datetime . "'";
        }
        
        if ($request->get("component")) {
            $queryString .= " AND component =  '" . addslashes($request->get("component")) . "'";
        }
         
        if ($request->get("relatedobject")) {
            $queryString .= " AND relatedobject = " . $request->get("relatedobject");
        }

        if ($request->get("message")) {
            $queryString .= " AND message like '%" . $request->get("message") ."%'";
        }


        $db = Db::get();
        $count = $db->fetchCol("SELECT count(*) FROM " . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . $queryString);
        $total = $count[0];


        $result = $db->fetchAll("SELECT * FROM " . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . $queryString . " $orderby LIMIT $offset, $limit");

        $errorDataList = [];
        if (!empty($result)) {
            foreach ($result as $r) {
                $parts = explode("/", $r['filelink']);
                $filename = $parts[count($parts)-1];
                $fileobject = str_replace(PIMCORE_DOCUMENT_ROOT, "", $r['fileobject']);

                $errorData =  ["id"=>$r['id'],
                                    "pid" => $r['pid'],
                                    "message"=>$r['message'],
                                    "timestamp"=>$r['timestamp'],
                                    "priority"=>$this->getPriorityName($r['priority']),
                                    "filename" => $filename,
                                    "fileobject" => $fileobject,
                                    "relatedobject" => $r['relatedobject'],
                                    "component" => $r['component'],
                                    "source" => $r['source']];
                $errorDataList[] = $errorData;
            }
        }

        return $this->json(["p_totalCount"=>$total, "p_results"=>$errorDataList]);
    }

    /**
     * @param $priority
     * @return mixed
     */
    private function getPriorityName($priority)
    {
        $p = ApplicationLoggerDb::getPriorities();

        return $p[$priority];
    }

    /**
     * @Route("/log/priority-json")
     * @param Request $request
     * @return JsonResponse
     */
    public function priorityJsonAction(Request $request)
    {
        $priorities[] = ["key" => "-1", "value" => "-"];
        foreach (ApplicationLoggerDb::getPriorities() as $key => $p) {
            $priorities[] = ["key" => $key, "value" => $p];
        }

        return $this->json(["priorities" => $priorities]);
    }

    /**
     * @Route("/log/component-json")
     * @param Request $request
     * @return JsonResponse
     */
    public function componentJsonAction(Request $request)
    {
        $components[] = ["key" => "", "value" => "-"];
        foreach (ApplicationLoggerDb::getComponents() as $p) {
            $components[] = ["key" => $p, "value" => $p];
        }

        return $this->json(["components" => $components]);
    }

    /**
     * @Route("/log/show-file-object-action")
     * @param Request $request
     * @return Response
     */
    public function showFileObjectAction(Request $request)
    {
        $filePath = $request->get("filePath");
        $filePath = PIMCORE_PROJECT_ROOT . "/" . $filePath;

        $response = new Response();
        $response->headers->set("Content-Type", "text/plain");

        if(file_exists($filePath)) {
            $response->setContent(file_get_contents($filePath));
        } else {
            $response->setContent("Path `" . $filePath . "` not found.");
        }

        return $response;
    }
}
