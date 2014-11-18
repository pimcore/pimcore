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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Model\Object;
use Pimcore\Model\Element;

class Admin_VariantsController extends \Pimcore\Controller\Action\Admin {


    public function updateKeyAction() {
        $id = $this->getParam("id");
        $key = $this->getParam("key");
            $object = Object\Concrete::getById($id);

        try {
            if(!empty($object)) {
                $object->setKey($key);
                $object->save();
                $this->_helper->json(array("success" => true));
            } else {
                throw new \Exception("No Object found for given id.");
            }

        } catch(\Exception $e) {
            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
        }
    }

    public function getVariantsAction() {
        // get list of variants

        if($this->getParam("language")) {
            $this->setLanguage($this->getParam("language"), true);
        }

        if ($this->getParam("xaction") == "update") {

            $data = \Zend_Json::decode($this->getParam("data"));

            // save
            $object = Object::getById($data["id"]);

            if($object->isAllowed("publish")) {
                $objectData = array();
                foreach($data as $key => $value) {
                    $parts = explode("~", $key);
                    if (substr($key, 0, 1) == "~") {
                        $type = $parts[1];
                        $field = $parts[2];
                        $keyid = $parts[3];

                        $getter = "get" . ucfirst($field);
                        $setter = "set" . ucfirst($field);
                        $keyValuePairs = $object->$getter();

                        if (!$keyValuePairs) {
                            $keyValuePairs = new Object\Data\KeyValue();
                            $keyValuePairs->setObjectId($object->getId());
                            $keyValuePairs->setClass($object->getClass());
                        }

                        $keyValuePairs->setPropertyWithId($keyid, $value, true);
                        $object->$setter($keyValuePairs);
                    } else if(count($parts) > 1) {
                        $brickType = $parts[0];
                        $brickKey = $parts[1];
                        $brickField = Object\Service::getFieldForBrickType($object->getClass(), $brickType);

                        $fieldGetter = "get" . ucfirst($brickField);
                        $brickGetter = "get" . ucfirst($brickType);
                        $valueSetter = "set" . ucfirst($brickKey);

                        $brick = $object->$fieldGetter()->$brickGetter();
                        if(empty($brick)) {
                            $classname = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($brickType);
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
                    $this->_helper->json(array("data" => Object\Service::gridObjectData($object, $this->getParam("fields")), "success" => true));
                } catch (\Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            } else {
                throw new \Exception("Permission denied");
            }

        } else {

            $parentObject = Object\Concrete::getById($this->getParam("objectId"));

            if(empty($parentObject)) {
                throw new \Exception("No Object found with id " . $this->getParam("objectId"));
            }

            if($parentObject->isAllowed("view")) {
                $class = $parentObject->getClass();
                $className = $parentObject->getClass()->getName();

                $start = 0;
                $limit = 15;
                $orderKey = "o_id";
                $order = "ASC";

                $fields = array();
                $bricks = array();
                if($this->getParam("fields")) {
                    $fields = $this->getParam("fields");

                    foreach($fields as $f) {
                        $parts = explode("~", $f);
                        if(count($parts) > 1) {
                            $bricks[$parts[0]] = $parts[0];
                        }
                    }
                }

                if ($this->getParam("limit")) {
                    $limit = $this->getParam("limit");
                }
                if ($this->getParam("start")) {
                    $start = $this->getParam("start");
                }
                if ($this->getParam("sort")) {
                    if ($this->getParam("sort") == "fullpath") {
                        $orderKey = array("o_path", "o_key");
                    } else if ($this->getParam("sort") == "id") {
                        $orderKey = "o_id";
                    } else if ($this->getParam("sort") == "published") {
                        $orderKey = "o_published";
                    } else if ($this->getParam("sort") == "modificationDate") {
                        $orderKey = "o_modificationDate";
                    } else if ($this->getParam("sort") == "creationDate") {
                        $orderKey = "o_creationDate";
                    } else {
                        $orderKey = $this->getParam("sort");
                    }
                }
                if ($this->getParam("dir")) {
                    $order = $this->getParam("dir");
                }

                $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

                $conditionFilters = array("o_parentId = " . $parentObject->getId());
                // create filter condition
                if ($this->getParam("filter")) {
                    $conditionFilters[] =  Object\Service::getFilterCondition($this->getParam("filter"), $class);
                }
                if ($this->getParam("condition")) {
                    $conditionFilters[] = "(" . $this->getParam("condition") . ")";
                }

                $list = new $listClass();
                if(!empty($bricks)) {
                    foreach($bricks as $b) {
                        $list->addObjectbrick($b);
                    }
                }
                $list->setCondition(implode(" AND ", $conditionFilters));
                $list->setLimit($limit);
                $list->setOffset($start);
                $list->setOrder($order);
                $list->setOrderKey($orderKey);
                $list->setObjectTypes(array(Object\AbstractObject::OBJECT_TYPE_VARIANT));

                $list->load();

                $objects = array();
                foreach ($list->getObjects() as $object) {
                    if($object->isAllowed("view")) {
                        $o = Object\Service::gridObjectData($object, $fields);
                        $objects[] = $o;
                    }
                }

                $this->_helper->json(array("data" => $objects, "success" => true, "total" => $list->getTotalCount()));
            } else {
                throw new \Exception("Permission denied");
            }
        }
    }
}


