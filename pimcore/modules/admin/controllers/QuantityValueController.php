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
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Controller\Action\Admin;

class Admin_QuantityValueController extends Admin
{
    public function unitProxyAction()
    {
        if ($this->getParam("data")) {
            if ($this->getParam("xaction") == "destroy") {
                $data = Zend_Json::decode($this->getParam("data"));
                $id = $data["id"];
                $unit = \Pimcore\Model\Object\QuantityValue\Unit::getById($id);
                if (!empty($unit)) {
                    $unit->delete();
                    $this->_helper->json(["data" => [], "success" => true]);
                } else {
                    throw new \Exception("Unit with id " . $id . " not found.");
                }
            } elseif ($this->getParam("xaction") == "update") {
                $data = Zend_Json::decode($this->getParam("data"));
                $unit = Pimcore\Model\Object\QuantityValue\Unit::getById($data['id']);
                if (!empty($unit)) {
                    $unit->setValues($data);
                    $unit->save();
                    $this->_helper->json(["data" => get_object_vars($unit), "success" => true]);
                } else {
                    throw new \Exception("Unit with id " . $data['id'] . " not found.");
                }
            } elseif ($this->getParam("xaction") == "create") {
                $data = Zend_Json::decode($this->getParam("data"));
                unset($data['id']);
                $unit = new Pimcore\Model\Object\QuantityValue\Unit();
                $unit->setValues($data);
                $unit->save();
                $this->_helper->json(["data" => get_object_vars($unit), "success" => true]);
            }
        } else {
            $list = new Pimcore\Model\Object\QuantityValue\Unit\Listing();


            $orderKey = "abbreviation";
            $order = "asc";


            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }

            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $condition = "1 = 1";
            if ($this->getParam("filter")) {
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);
                $db = \Pimcore\Db::get();
                foreach ($filters as $f) {
                    if ($f->type == "string") {
                        $condition .= " AND " . $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } elseif ($f->type == "numeric") {
                        $operator = $this->getOperator($f->comparison);
                        $condition .= " AND " . $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " " . $operator . " " . $db->quote($f->value);
                    }
                }
                $list->setCondition($condition);
            }
            $list->load();

            $units = [];
            foreach ($list->getUnits() as $u) {
                $units[] = get_object_vars($u);
            }

            $this->_helper->json(["data" => $units, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    /**
     * @param $comparison
     * @return mixed
     */
    private function getOperator($comparison)
    {
        $mapper = [
            "lt" => "<",
            "gt" => ">",
            "eq" => "="
        ];

        return $mapper[$comparison];
    }


    public function unitListAction()
    {
        $list = new \Pimcore\Model\Object\QuantityValue\Unit\Listing();
        $list->setOrderKey("abbreviation");
        $list->setOrder("ASC");
        if ($this->getParam("filter")) {
            $array = explode(",", $this->getParam("filter"));
            $quotedArray = [];
            $db = \Pimcore\Db::get();
            foreach ($array as $a) {
                $quotedArray[] = $db->quote($a);
            }
            $string = implode(",", $quotedArray);
            $list->setCondition("id IN (" . $string . ")");
        }

        $units = $list->getUnits();
        $this->_helper->json(["data" => $units, "success" => true, "total" => $list->getTotalCount()]);
    }
}
