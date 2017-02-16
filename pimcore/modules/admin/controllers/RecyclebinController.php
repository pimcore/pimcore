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

use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Element;

class Admin_RecyclebinController extends \Pimcore\Controller\Action\Admin
{
    public function init()
    {
        parent::init();

        // recyclebin actions might take some time (save & restore)
        $timeout = 600; // 10 minutes
        @ini_set("max_execution_time", $timeout);
        set_time_limit($timeout);

        // check permissions
        $notRestrictedActions = ["add"];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("recyclebin");
        }
    }

    public function listAction()
    {
        if ($this->getParam("xaction") == "destroy") {
            $item = Recyclebin\Item::getById(\Pimcore\Admin\Helper\QueryParams::getRecordIdForGridRequest($this->getParam("data")));
            $item->delete();

            $this->_helper->json(["success" => true, "data" => []]);
        } else {
            $db = \Pimcore\Db::get();

            $list = new Recyclebin\Item\Listing();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $list->setOrderKey("date");
            $list->setOrder("DESC");

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }


            $conditionFilters = [];

            if ($this->getParam("filterFullText")) {
                $conditionFilters[] = "path LIKE " . $list->quote("%".$this->getParam("filterFullText")."%");
            }

            $filters = $this->getParam("filter");
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

            $this->_helper->json(["data" => $items, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    public function restoreAction()
    {
        $item = Recyclebin\Item::getById($this->getParam("id"));
        $item->restore();

        $this->_helper->json(["success" => true]);
    }

    public function flushAction()
    {
        $bin = new Element\Recyclebin();
        $bin->flush();

        $this->_helper->json(["success" => true]);
    }

    public function addAction()
    {
        $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));

        if ($element) {
            $type = Element\Service::getElementType($element);
            $listClass = "\\Pimcore\\Model\\" . ucfirst($type) . "\\Listing";
            $list = new $listClass();
            $list->setCondition((($type == "object") ? "o_" : "") . "path LIKE '" . $element->getRealFullPath() . "/%'");
            $children = $list->getTotalCount();

            if ($children <= 100) {
                Recyclebin\Item::create($element, $this->getUser());
            }

            $this->_helper->json(["success" => true]);
        } else {
            $this->_helper->json(["success" => false]);
        }
    }
}
