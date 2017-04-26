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

use Pimcore\Model\Object;
use Pimcore\Model\Element;

class Admin_VariantsController extends \Pimcore\Controller\Action\Admin
{
    public function updateKeyAction()
    {
        $id = $this->getParam("id");
        $key = $this->getParam("key");
        $object = Object\Concrete::getById($id);

        try {
            if (!empty($object)) {
                $object->setKey($key);
                $object->save();
                $this->_helper->json(["success" => true]);
            } else {
                throw new \Exception("No Object found for given id.");
            }
        } catch (\Exception $e) {
            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function getVariantsAction()
    {
        // get list of variants

        if ($this->getParam("language")) {
            $this->setLanguage($this->getParam("language"), true);
        }

        if ($this->getParam("xaction") == "update") {
            $data = \Zend_Json::decode($this->getParam("data"));

            // save
            $object = Object::getById($data["id"]);

            if ($object->isAllowed("publish")) {
                $objectData = [];
                foreach ($data as $key => $value) {
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
                    } elseif (count($parts) > 1) {
                        $brickType = $parts[0];
                        $brickKey = $parts[1];
                        $brickField = Object\Service::getFieldForBrickType($object->getClass(), $brickType);

                        $fieldGetter = "get" . ucfirst($brickField);
                        $brickGetter = "get" . ucfirst($brickType);
                        $valueSetter = "set" . ucfirst($brickKey);

                        $brick = $object->$fieldGetter()->$brickGetter();
                        if (empty($brick)) {
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
                    $this->_helper->json(["data" => Object\Service::gridObjectData($object, $this->getParam("fields")), "success" => true]);
                } catch (\Exception $e) {
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                throw new \Exception("Permission denied");
            }
        } else {
            $parentObject = Object\Concrete::getById($this->getParam("objectId"));

            if (empty($parentObject)) {
                throw new \Exception("No Object found with id " . $this->getParam("objectId"));
            }

            if ($parentObject->isAllowed("view")) {
                $class = $parentObject->getClass();
                $className = $parentObject->getClass()->getName();

                $start = 0;
                $limit = 15;
                $orderKey = "oo_id";
                $order = "ASC";

                $fields = [];
                $bricks = [];
                if ($this->getParam("fields")) {
                    $fields = $this->getParam("fields");

                    foreach ($fields as $f) {
                        $parts = explode("~", $f);
                        if (count($parts) > 1) {
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

                $orderKey = "oo_id";
                $order = "ASC";

                $colMappings = [
                    "filename" => "o_key",
                    "fullpath" => ["o_path", "o_key"],
                    "id" => "oo_id",
                    "published" => "o_published",
                    "modificationDate" => "o_modificationDate",
                    "creationDate" => "o_creationDate"
                ];

                $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
                if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                    $orderKey = $sortingSettings['orderKey'];
                    if (array_key_exists($orderKey, $colMappings)) {
                        $orderKey = $colMappings[$orderKey];
                    }
                    $order = $sortingSettings['order'];
                }

                if ($this->getParam("dir")) {
                    $order = $this->getParam("dir");
                }

                $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

                $conditionFilters = ["o_parentId = " . $parentObject->getId()];
                // create filter condition
                if ($this->getParam("filter")) {
                    $conditionFilters[] =  Object\Service::getFilterCondition($this->getParam("filter"), $class);
                }
                if ($this->getParam("condition")) {
                    $conditionFilters[] = "(" . $this->getParam("condition") . ")";
                }

                $list = new $listClass();
                if (!empty($bricks)) {
                    foreach ($bricks as $b) {
                        $list->addObjectbrick($b);
                    }
                }
                $list->setCondition(implode(" AND ", $conditionFilters));
                $list->setLimit($limit);
                $list->setOffset($start);
                $list->setOrder($order);
                $list->setOrderKey($orderKey);
                $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_VARIANT]);

                $list->load();

                $objects = [];
                foreach ($list->getObjects() as $object) {
                    if ($object->isAllowed("view")) {
                        $o = Object\Service::gridObjectData($object, $fields);
                        $objects[] = $o;
                    }
                }

                $this->_helper->json(["data" => $objects, "success" => true, "total" => $list->getTotalCount()]);
            } else {
                throw new \Exception("Permission denied");
            }
        }
    }
}
