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
use Pimcore\Model\Object\Classificationstore;
use Pimcore\Resource;

class Admin_ClassificationstoreController extends \Pimcore\Controller\Action\Admin
{


    public function deleteRelationAction() {
        $keyId = $this->_getParam("keyId");
        $groupId = $this->_getParam("groupId");

        $config = new Classificationstore\KeyGroupRelation();
        $config->setKeyId($keyId);
        $config->setGroupId($groupId);

        $config->delete();
//
        $this->_helper->json(array("success" => true));
    }


    public function deletegroupAction() {
        $id = $this->_getParam("id");

        $config = Classificationstore\GroupConfig::getById($id);
        $config->delete();

        $this->_helper->json(array("success" => true));
    }

    public function addgroupAction() {
        $name = $this->_getParam("name");
        $alreadyExist = false;
        $config = Classificationstore\GroupConfig::getByName($name);


        if(!$config) {
            $config = new Classificationstore\GroupConfig();
            $config->setName($name);
            $config->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $config->getName()));
    }

    public function grouptreeGetChildsByIdAction() {
        $nodeId = $this->getParam("node");

        $list = new Object\Classificationstore\GroupConfig\Listing();
        $list->setCondition("parentId = ?", $nodeId);
        $list = $list->load();

        $contents = array();

        /** @var $item Object\Classificationstore\GroupConfig */
        foreach ($list as $item) {

            $hasChilds = $item->hasChilds();

            $itemConfig = array(
                "id" => $item->getId(),
                "text" => "text 1-" . $item->getName(),
                "leaf" => !$hasChilds,
                "iconCls" => $item->getLevel() < 2 ? "pimcore_icon_Classificationstore_icon_group" : "pimcore_icon_Classificationstore_icon_subgroup"
            );

            $contents[] = $itemConfig;
        }



        $this->_helper->json($contents);


    }

    public function getgroupAction() {
        $id = $this->_getParam("id");
        $config = Classificationstore\GroupConfig::getByName($id);

        $data = array(
            "id" => $id,
            "name" => $config->getName(),
            "description" => $config->getDescription(),
            "sorter" => $config->getSorter()
        );

        $this->_helper->json($data);
    }

    public function groupsAction() {
        if ($this->_getParam("data")) {
            $dataParam = $this->_getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $id = $data["id"];
            $config = Classificationstore\GroupConfig::getById($id);

            foreach ($data as $key => $value) {
                if ($key != "id") {
                    $setter = "set" . $key;
                    $config->$setter($value);
                }
            }

            $config->save();

            $this->_helper->json(array("success" => true, "data" => $config));
        } else {

            $start = 0;
            $limit = 15;
            $orderKey = "name";
            $order = "ASC";

            if ($this->_getParam("dir")) {
                $order = $this->_getParam("dir");
            }

            if ($this->_getParam("sort")) {
                $orderKey = $this->_getParam("sort");
            }

            if ($this->_getParam("limit")) {
                $limit = $this->_getParam("limit");
            }
            if ($this->_getParam("start")) {
                $start = $this->_getParam("start");
            }

            if ($this->_getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            $list = new Classificationstore\GroupConfig\Listing();

            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $condition = "";

            if($this->_getParam("filter")) {
                $filterString = $this->_getParam("filter");
                $filters = json_decode($filterString);

                $db = Resource::get();
                $count = 0;

                foreach($filters as $f) {
                    if ($count > 0) {
                        $condition .= " OR ";
                    }
                    $count++;
                    $condition .= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                }

            }

            if ($this->getParam("oid")) {
                $object = Object_Concrete::getById($this->getParam("oid"));
                $class = $object->getClass();
                $fd = $class->getFieldDefinition($this->getParam("fieldname"));
                $allowedGroupIds = $fd->getAllowedGroupIds();

                if ($allowedGroupIds) {
                    if ($condition) {
                        $condition = "(" . $condition . ") AND ";
                    }
                    $condition .= "ID in (" . implode(",", $allowedGroupIds) . ")";
                }

            }

            $list->setCondition($condition);

            $list->load();
            $configList = $list->getList();

            $rootElement = array();

            $data = array();
            foreach($configList as $config) {
                $name = $config->getName();
                if (!$name) {
                    $name = "EMPTY";
                }
                $item = array(
                    "id" => $config->getId(),
                    "name" => $name,
                    "description" => $config->getDescription(),
                    "sorter" => $config->getSorter()
                );
                if ($config->getCreationDate()) {
                    $item["creationDate"] = $config->getCreationDate();
                }

                if ($config->getModificationDate()) {
                    $item["modificationDate"] = $config->getModificationDate();
                }


                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();
            return $this->_helper->json($rootElement);
        }
    }


    public function relationsAction() {
        if ($this->_getParam("data")) {
            $dataParam = $this->_getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $keyId = $data["keyId"];
            $groupId = $data["groupId"];

            $config = new Classificationstore\KeyGroupRelation();
            $config->setGroupId($groupId);
            $config->setKeyId($keyId);

            $config->save();
            $data["id"] = $config->getGroupId() . "-" . $config->getKeyId();

            $this->_helper->json(array("success" => true, "data" => $data));
        } else {
            $mapping = array("keyName" => "name", "keyDescription" => "description");

            $start = 0;
            $limit = 15;
            $orderKey = "name";
            $order = "ASC";

            if ($this->_getParam("dir")) {
                $order = $this->_getParam("dir");
            }

            if ($this->_getParam("sort")) {
                $orderKey = $this->_getParam("sort");
                $orderKey = $mapping[$orderKey];
            }

            if ($this->_getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            if ($this->_getParam("limit")) {
                $limit = $this->_getParam("limit");
            }
            if ($this->_getParam("start")) {
                $start = $this->_getParam("start");
            }

            $list = new Classificationstore\KeyGroupRelation\Listing();

            if ($limit > 0) {
                $list->setLimit($limit);
            }
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            if($this->_getParam("filter")) {
                $db = Resource::get();
                $condition = "";
                $filterString = $this->_getParam("filter");
                $filters = json_decode($filterString);

                $count = 0;

                foreach($filters as $f) {
                    if ($count > 0) {
                        $condition .= " OR ";
                    }
                    $count++;
                    $fieldname = $mapping[$f->field];
                    $condition .= $db->getQuoteIdentifierSymbol() . $fieldname . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                }


            }

            $groupId = $this->getParam("groupId");
            if ($condition) {
                $condition = "( " . $condition . " ) AND";
            }
            $condition .= " groupId = " . $list->quote($groupId);

            $list->setCondition($condition);





            $listItems = $list->load();

            $rootElement = array();

            $data = array();
            foreach($listItems as $config) {
                $item = array(
                    "keyId" => $config->getKeyId(),
                    "groupId" => $config->getGroupId(),
                    "keyName" => $config->getName(),
                    "keyDescription" => $config->getDescription(),
                    "id" => $config->getGroupId() . "-" . $config->getKeyId()
                );
                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();
            return $this->_helper->json($rootElement);
        }
    }

    public function addGroupsAction() {
        $db = Resource::get();


        $ids = \Zend_Json::decode($this->_getParam("groupIds"));

        $keyCondition = "groupId in (" . implode(",", $ids) . ")";

        $keyList = new Classificationstore\KeyGroupRelation\Listing();
        $keyList->setCondition($keyCondition);
        $keyList = $keyList->load();


        $groupCondition = "id in (" . implode(",", $ids) . ")";

        $groupList = new Classificationstore\GroupConfig\Listing();
        $groupList->setCondition($groupCondition);
        $groupList->setOrder("ASC");
        $groupList->setOrderKey("id");
        $groupList = $groupList->load();

        $data = array();

        foreach($groupList as $groupData) {
            $data[$groupData->getId()] = array(
                "name" => $groupData->getName(),
                "id" => $groupData->getId(),
                "description" => $groupData->getDescription(),
                "keys" => array()
            );

        }


        foreach ($keyList as $keyData) {
            $groupId = $keyData->getGroupId();

            $keyList = $data[$groupId]["keys"];
            $definition = $keyData->getDefinition();
            $keyList[] = array(
                "name" => $keyData->getName(),
                "id" => $keyData->getKeyId(),
                "description" => $keyData->getDescription(),
                "definition" => json_decode($definition)
            );
            $data[$groupId]["keys"] = $keyList;
        }

//        $data = array_values($data);
        return $this->_helper->json($data);

    }

    public function propertiesAction() {
        if ($this->_getParam("data")) {
            $dataParam = $this->_getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $id = $data["id"];
            $config = Classificationstore\KeyConfig::getById($id);

            foreach ($data as $key => $value) {
                if ($key != "id") {
                    $setter = "set" . $key;
                    if (method_exists($config, $setter)) {
                        $config->$setter($value);
                    }
                }
            }

            $config->save();
            $item = $this->getConfigItem($config);

            $this->_helper->json(array("success" => true, "data" => $item));
        } else {

            $start = 0;
            $limit = 15;
            $orderKey = "name";
            $order = "ASC";

            if ($this->_getParam("dir")) {
                $order = $this->_getParam("dir");
            }

            if ($this->_getParam("sort")) {
                $orderKey = $this->_getParam("sort");
            }

            if ($this->_getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            if ($this->_getParam("limit")) {
                $limit = $this->_getParam("limit");
            }
            if ($this->_getParam("start")) {
                $start = $this->_getParam("start");
            }

            $list = new Classificationstore\KeyConfig\Listing();

            if ($limit > 0) {
                $list->setLimit($limit);
            }
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            if($this->_getParam("filter")) {
                $db = Resource::get();
                $condition = "";
                $filterString = $this->_getParam("filter");
                $filters = json_decode($filterString);

                $count = 0;

                foreach($filters as $f) {
                    if ($count > 0) {
                        $condition .= " OR ";
                    }
                    $count++;
                    $condition .= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                }


                $list->setCondition($condition);
            }

            if ($this->_getParam("groupIds") || $this->_getParam("keyIds")) {
                $db = Resource::get();

                if ($this->_getParam("groupIds")) {
                    $ids = \Zend_Json::decode($this->_getParam("groupIds"));
                    $col = "group";
                } else {
                    $ids = \Zend_Json::decode($this->_getParam("keyIds"));
                    $col = "id";
                }

                $condition = $db->getQuoteIdentifierSymbol() . $col . $db->getQuoteIdentifierSymbol() . " IN (";
                $count = 0;
                foreach ($ids as $theId) {
                    if ($count > 0) {
                        $condition .= ",";
                    }
                    $condition .= $theId;
                    $count++;
                }

                $condition .= ")";
                $list->setCondition($condition);
            }

            $list->load();
            $configList = $list->getList();

            $rootElement = array();

            $data = array();
            foreach($configList as $config) {
                $item = $this->getConfigItem($config);
                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();
            return $this->_helper->json($rootElement);
        }
    }


    protected function getConfigItem($config) {
        $name = $config->getName();

        $groupDescription = null;
        $item = array(
            "id" => $config->getId(),
            "name" => $name,
            "description" => $config->getDescription(),
            "type" => $config->getType() ? $config->getType() : "input",
            "definition" => $config->getDefinition(),
            "sorter" => $config->getSorter()

        );

        if ($config->getDefinition()) {
            $definition = json_decode($config->getDefinition(), true);
            if ($definition) {
                $item["title"] = $definition["title"];
            }
        }

        if ($config->getCreationDate()) {
            $item["creationDate"] = $config->getCreationDate();
        }

        if ($config->getModificationDate()) {
            $item["modificationDate"] = $config->getModificationDate();
        }
        return $item;
    }

    public function addPropertyAction() {
        $name = $this->_getParam("name");
        $alreadyExist = false;

        if(!$alreadyExist) {
            $definition = array(
                "fieldtype" => "input",
                "name" => $name,
                "title" => $name,
                "datatype" => "data"
            );
            $config = new Classificationstore\KeyConfig();
            $config->setName($name);
            $config->setType("input");
            $config->setEnabled(1);
            $config->setDefinition(json_encode($definition));
            $config->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $config->getName()));
    }

    public function deletePropertyAction() {
        $id = $this->_getParam("id");

        $config = Classificationstore\KeyConfig::getById($id);
//        $config->delete();
        $config->setEnabled(false);
        $config->save();

        $this->_helper->json(array("success" => true));
    }


}