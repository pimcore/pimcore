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

class Admin_VariantsController extends Pimcore_Controller_Action_Admin {

public function testAction() {

    $class = Object_Class::getById(230);
//    $class = Object_Class::getById(229);
    p_r($class);
    die("mneuin");

}

    public function updateKeyAction() {
        $id = $this->_getParam("id");
        $key = $this->_getParam("key");
        $object = Object_Concrete::getById($id);

        try {
            if(!empty($object)) {
                $object->setO_key($key);
                $object->save();
                $this->_helper->json(array("success" => true));
            } else {
                throw new Exception("No Object found for given id.");
            }

        } catch(Exception $e) {
            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
        }
    }

    public function getVariantsAction() {
        // get list of variants

        $parentObject = Object_Concrete::getById($this->_getParam("objectId"));

        if(empty($parentObject)) {
            throw new Exception("No Object found with id " . $this->_getParam("objectId"));
        }

        $class = $parentObject->getO_class();
        $className = $parentObject->getO_class()->getName();

        $start = 0;
        $limit = 15;
        $orderKey = "o_id";
        $order = "ASC";

        if ($this->_getParam("limit")) {
            $limit = $this->_getParam("limit");
        }
        if ($this->_getParam("start")) {
            $start = $this->_getParam("start");
        }
        if ($this->_getParam("sort")) {
            if ($this->_getParam("sort") == "fullpath") {
                $orderKey = array("o_path", "o_key");
            } else if ($this->_getParam("sort") == "id") {
                $orderKey = "o_id";
            } else if ($this->_getParam("sort") == "published") {
                $orderKey = "o_published";
            } else if ($this->_getParam("sort") == "modificationDate") {
                $orderKey = "o_modificationDate";
            } else if ($this->_getParam("sort") == "creationDate") {
                $orderKey = "o_creationDate";
            } else {
                $orderKey = $this->_getParam("sort");
            }
        }
        if ($this->_getParam("dir")) {
            $order = $this->_getParam("dir");
        }

        $listClass = "Object_" . ucfirst($className) . "_List";

        $conditionFilters = "o_parentId = " . $parentObject->getId();
        // create filter condition
        if ($this->_getParam("filter")) {
            $conditionFilters .=  Object_Service::getFilterCondition($this->_getParam("filter"), $class);
        }
        if ($this->_getParam("condition")) {
            $conditionFilters .= " AND (" . $this->_getParam("condition") . ")";
        }

        $list = new $listClass();
        $list->setCondition($conditionFilters);
        $list->setLimit($limit);
        $list->setOffset($start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);
        $list->setIgnoreLocale(true);
        $list->setObjectTypes(array(Object_Abstract::OBJECT_TYPE_VARIANT));

        $list->load();

        $objects = array();
        foreach ($list->getObjects() as $object) {

            $o = Object_Service::gridObjectData($object);

            $objects[] = $o;
        }
        $this->_helper->json(array("data" => $objects, "success" => true, "total" => $list->getTotalCount()));

    }


}


