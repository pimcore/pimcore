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


        if ($this->_getParam("xaction") == "update") {

            $data = Zend_Json::decode($this->_getParam("data"));

            // save
            $object = Object_Abstract::getById($data["id"]);

            $objectData = array();
            foreach($data as $key => $value) {
                $parts = explode("~", $key);
                if(count($parts) > 1) {
                    $brickType = $parts[0];
                    $brickKey = $parts[1];
                    $brickField = Object_Service::getFieldForBrickType($object->getClass(), $brickType);

                    $fieldGetter = "get" . ucfirst($brickField);
                    $brickGetter = "get" . ucfirst($brickType);
                    $valueSetter = "set" . ucfirst($brickKey);

                    $brick = $object->$fieldGetter()->$brickGetter();
                    if(empty($brick)) {
                        $classname = "Object_Objectbrick_Data_" . ucfirst($brickType);
                        $brickSetter = "set" . ucfirst($brickType);
                        $brick = new $classname($object);
                        $object->$fieldGetter()->$brickSetter($brick);
                    }
                    $brick->$valueSetter($value);

                } else {
                    $objectData[$key] = $value;
                }
            }

            $object->setValues($objectData);

            try {
                $object->save();
                $this->_helper->json(array("data" => Object_Service::gridObjectData($object, $this->_getParam("fields")), "success" => true));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }


        } else {

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

            $fields = array();
            $bricks = array();
            if($this->_getParam("fields")) {
                $fields = $this->_getParam("fields");

                foreach($fields as $f) {
                    $parts = explode("~", $f);
                    if(count($parts) > 1) {
                        $bricks[$parts[0]] = $parts[0];
                    }
                }
            }

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
            if(!empty($bricks)) {
                foreach($bricks as $b) {
                    $list->addObjectbrick($b);
                }
            }
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

                $o = Object_Service::gridObjectData($object, $fields);

                $objects[] = $o;
            }
            $this->_helper->json(array("data" => $objects, "success" => true, "total" => $list->getTotalCount()));

            }

    }


}


