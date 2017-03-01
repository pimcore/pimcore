<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

class RecyclebinController extends AdminController implements EventedControllerInterface
{

    /**
     * @Route("/recyclebin/list")
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {

        if ($request->get("xaction") == "destroy") {
            $item = Recyclebin\Item::getById(\Pimcore\Admin\Helper\QueryParams::getRecordIdForGridRequest($request->get("data")));
            $item->delete();

            return new JsonResponse(["success" => true, "data" => []]);
        } else {
            $db = \Pimcore\Db::get();

            $list = new Recyclebin\Item\Listing();
            $list->setLimit($request->get("limit"));
            $list->setOffset($request->get("start"));

            $list->setOrderKey("date");
            $list->setOrder("DESC");

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($request->request->all());
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }


            $conditionFilters = [];

            if ($request->get("filterFullText")) {
                $conditionFilters[] = "path LIKE " . $list->quote("%".$request->get("filterFullText")."%");
            }

            $filters = $request->get("filter");
            if ($filters) {
                $filters = \Zend_Json::decode($filters);

                foreach ($filters as $filter) {
                    $operator = "=";

                    $filterField = $filter["property"];
                    $filterOperator = $filter["operator"];

                    if ($filter["type"] == "string") {
                        $operator = "LIKE";
                    } elseif ($filter["type"] == "numeric") {
                        if ($filterOperator == "lt") {
                            $operator = "<";
                        } elseif ($filterOperator == "gt") {
                            $operator = ">";
                        } elseif ($filterOperator == "eq") {
                            $operator = "=";
                        }
                    } elseif ($filter["type"] == "date") {
                        if ($filterOperator == "lt") {
                            $operator = "<";
                        } elseif ($filterOperator == "gt") {
                            $operator = ">";
                        } elseif ($filterOperator == "eq") {
                            $operator = "=";
                        }
                        $filter["value"] = strtotime($filter["value"]);
                    } elseif ($filter["type"] == "list") {
                        $operator = "=";
                    } elseif ($filter["type"] == "boolean") {
                        $operator = "=";
                        $filter["value"] = (int) $filter["value"];
                    }
                    // system field
                    $value = $filter["value"];
                    if ($operator == "LIKE") {
                        $value = "%" . $value . "%";
                    }

                    $field = "`" . $filterField . "` ";
                    if ($filter["field"] == "fullpath") {
                        $field = "CONCAT(path,filename)";
                    }

                    if ($filter["type"] == "date" && $operator == "=") {
                        $maxTime = $value + (86400 - 1); //specifies the top point of the range used in the condition
                        $condition =  $field . " BETWEEN " . $db->quote($value) . " AND " . $db->quote($maxTime);
                        $conditionFilters[] = $condition;
                    } else {
                        $conditionFilters[] = $field . $operator . " '" . $value . "' ";
                    }
                }
            }

            if (!empty($conditionFilters)) {
                $condition = implode(" AND ", $conditionFilters);
                $list->setCondition($condition);
            }

            $items = $list->load();

            return $this->json(["data" => $items, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    /**
     * @Route("/recyclebin/restore")
     * @param Request $request
     * @return JsonResponse
     */
    public function restoreAction(Request $request)
    {
        $item = Recyclebin\Item::getById($request->get("id"));
        $item->restore();

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/recyclebin/flush")
     * @param Request $request
     * @return JsonResponse
     */
    public function flushAction()
    {
        $bin = new Element\Recyclebin();
        $bin->flush();

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/recyclebin/add")
     * @param Request $request
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $element = Element\Service::getElementById($request->get("type"), $request->get("id"));

        if ($element) {
            $type = Element\Service::getElementType($element);
            $listClass = "\\Pimcore\\Model\\" . ucfirst($type) . "\\Listing";
            $list = new $listClass();
            $list->setCondition((($type == "object") ? "o_" : "") . "path LIKE '" . $element->getRealFullPath() . "/%'");
            $children = $list->getTotalCount();

            if ($children <= 100) {
                Recyclebin\Item::create($element, $this->getUser());
            }

            return $this->json(["success" => true]);
        } else {
            return $this->json(["success" => false]);
        }
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

        // recyclebin actions might take some time (save & restore)
        $timeout = 600; // 10 minutes
        @ini_set("max_execution_time", $timeout);
        set_time_limit($timeout);

        $request = $event->getRequest();

        // check permissions
        $notRestrictedActions = ["add"];
        if (!in_array($request->get("action"), $notRestrictedActions)) {
            $this->checkPermission("recyclebin");
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }

}
