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
use Pimcore\Model\Object\Classificationstore;
use Pimcore\Db;

class Admin_ClassificationstoreController extends \Pimcore\Controller\Action\Admin
{
    /**
     * Delete collection with the group-relations
     */
    public function deleteCollectionAction()
    {
        $id = $this->getParam("id");

        $configRelations = new Classificationstore\CollectionGroupRelation\Listing();
        $configRelations->setCondition("colId = ?", $id);
        $list = $configRelations->load();
        foreach ($list as $item) {
            $item->delete();
        }

        $config = Classificationstore\CollectionConfig::getById($id);
        $config->delete();

        $this->_helper->json(array("success" => true));
    }

    public function deleteCollectionRelationAction()
    {
        $colId = $this->getParam("colId");
        $groupId = $this->getParam("groupId");

        $config = new Classificationstore\CollectionGroupRelation();
        $config->setColId($colId);
        $config->setGroupId($groupId);

        $config->delete();
        $this->_helper->json(array("success" => true));
    }


    public function deleteRelationAction()
    {
        $keyId = $this->getParam("keyId");
        $groupId = $this->getParam("groupId");

        $config = new Classificationstore\KeyGroupRelation();
        $config->setKeyId($keyId);
        $config->setGroupId($groupId);

        $config->delete();
        $this->_helper->json(array("success" => true));
    }


    public function deletegroupAction()
    {
        $id = $this->getParam("id");

        $config = Classificationstore\GroupConfig::getById($id);
        $config->delete();

        $this->_helper->json(array("success" => true));
    }

    public function createGroupAction()
    {
        $name = $this->getParam("name");
        $storeId = $this->getParam("storeId");
        $alreadyExist = false;
        $config = Classificationstore\GroupConfig::getByName($name, $storeId);


        if (!$config) {
            $config = new Classificationstore\GroupConfig();
            $config->setStoreId($storeId);
            $config->setName($name);
            $config->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $config->getName()));
    }

    public function createStoreAction()
    {
        $name = $this->getParam("name");

        $config = Classificationstore\StoreConfig::getByName($name);

        if (!$config) {
            $config = new Classificationstore\StoreConfig();
            $config->setName($name);
            $config->save();
        } else {
            throw new \Exception("Store with the given name exists");
        }

        $this->_helper->json(array("success" => true, "storeId" => $config->getId()));
    }



    public function createCollectionAction()
    {
        $name = $this->getParam("name");
        $storeId = $this->getParam("storeId");
        $alreadyExist = false;
        $config = Classificationstore\CollectionConfig::getByName($name, $storeId);

        if (!$config) {
            $config = new Classificationstore\CollectionConfig();
            $config->setName($name);
            $config->setStoreId($storeId);
            $config->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $config->getName()));
    }



    public function grouptreeGetChildsByIdAction()
    {
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

    public function getgroupAction()
    {
        $id = $this->getParam("id");
        $config = Classificationstore\GroupConfig::getByName($id);

        $data = array(
            "storeId" => $config->getStoreId(),
            "id" => $id,
            "name" => $config->getName(),
            "description" => $config->getDescription(),
            "sorter" => $config->getSorter()
        );

        $this->_helper->json($data);
    }

    public function collectionsAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $id = $data["id"];
            $config = Classificationstore\CollectionConfig::getById($id);

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

            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                $orderKey = $sortingSettings['orderKey'];
                $order = $sortingSettings['order'];
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            $allowedCollectionIds = array();
            if ($this->getParam("oid")) {
                $object = Object_Concrete::getById($this->getParam("oid"));
                $class = $object->getClass();
                $fd = $class->getFieldDefinition($this->getParam("fieldname"));
                $allowedGroupIds = $fd->getAllowedGroupIds();

                if ($allowedGroupIds) {
                    $db = \Pimcore\Db::get();
                    $query = "select * from classificationstore_collectionrelations where groupId in (" . implode(",", $allowedGroupIds) .")";
                    $relationList = $db->fetchAll($query);

                    if (is_array($relationList)) {
                        foreach ($relationList as $item) {
                            $allowedCollectionIds[] = $item["colId"];
                        }
                    }
                }
            }


            $list = new Classificationstore\CollectionConfig\Listing();

            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $conditionParts = array();
            $db = Db::get();

            $searchfilter = $this->getParam("searchfilter");
            if ($searchfilter) {
                $conditionParts[] = "(name LIKE " . $db->quote("%" . $searchfilter . "%") . " OR description LIKE " . $db->quote("%". $searchfilter . "%") . ")";
            }

            $conditionParts[] = " (storeId = " . $this->getParam("storeId") . ")";

            if ($this->getParam("filter")) {
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                foreach ($filters as $f) {
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $conditionParts[]= $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } else {
                        $conditionParts[]= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    }
                }
            }

            if ($allowedCollectionIds) {
                $conditionParts[]= " id in (" . implode(",", $allowedCollectionIds) . ")";
            }

            $condition = implode(" AND ", $conditionParts);

            $list->setCondition($condition);


            $list->load();
            $configList = $list->getList();

            $rootElement = array();

            $data = array();
            foreach ($configList as $config) {
                $name = $config->getName();
                if (!$name) {
                    $name = "EMPTY";
                }
                $item = array(
                    "storeId" => $config->getStoreId(),
                    "id" => $config->getId(),
                    "name" => $name,
                    "description" => $config->getDescription()
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



    public function groupsAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
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

            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            if ($this->getParam("sort")) {
                $orderKey = $this->getParam("sort");
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                $orderKey = $sortingSettings['orderKey'];
                $order = $sortingSettings['order'];
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            $list = new Classificationstore\GroupConfig\Listing();

            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $conditionParts = array();
            $db = Db::get();

            $searchfilter = $this->getParam("searchfilter");
            if ($searchfilter) {
                $conditionParts[] = "(name LIKE " . $db->quote("%" . $searchfilter . "%") . " OR description LIKE " . $db->quote("%". $searchfilter . "%") . ")";
            }

            if ($this->getParam("storeId")) {
                $conditionParts[] = "(storeId = " . $this->getParam("storeId") . ")";
            }


            if ($this->getParam("filter")) {
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                foreach ($filters as $f) {
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $conditionParts[]= $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } else {
                        $conditionParts[]= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    }
                }
            }

            if ($this->getParam("oid")) {
                $object = Object_Concrete::getById($this->getParam("oid"));
                $class = $object->getClass();
                $fd = $class->getFieldDefinition($this->getParam("fieldname"));
                $allowedGroupIds = $fd->getAllowedGroupIds();

                if ($allowedGroupIds) {
                    $conditionParts[]= "ID in (" . implode(",", $allowedGroupIds) . ")";
                }
            }

            $condition = implode(" AND ", $conditionParts);
            $list->setCondition($condition);

            $list->load();
            $configList = $list->getList();

            $rootElement = array();

            $data = array();
            foreach ($configList as $config) {
                $name = $config->getName();
                if (!$name) {
                    $name = "EMPTY";
                }
                $item = array(
                    "storeId" => $config->getStoreId(),
                    "id" => $config->getId(),
                    "name" => $name,
                    "description" => $config->getDescription()
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

    public function collectionRelationsAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $colId = $data["colId"];
            $groupId = $data["groupId"];
            $sorter = $data["sorter"];

            $config = new Classificationstore\CollectionGroupRelation();
            $config->setGroupId($groupId);
            $config->setColId($colId);
            $config->setSorter($sorter);

            $config->save();
            $data["id"] = $config->getColId() . "-" . $config->getGroupId();

            $this->_helper->json(array("success" => true, "data" => $data));
        } else {
            $mapping = array("groupName" => "name", "groupDescription" => "description");

            $start = 0;
            $limit = 15;
            $orderKey = "sorter";
            $order = "ASC";

            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                $orderKey = $sortingSettings['orderKey'];
                $order = $sortingSettings['order'];
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $list = new Classificationstore\CollectionGroupRelation\Listing();

            if ($limit > 0) {
                $list->setLimit($limit);
            }
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            if ($this->getParam("filter")) {
                $db = Db::get();
                $condition = "";
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                $count = 0;

                foreach ($filters as $f) {
                    if ($count > 0) {
                        $condition .= " AND ";
                    }
                    $count++;
                    $fieldname = $mapping[$f->field];
                    $condition .= $db->getQuoteIdentifierSymbol() . $fieldname . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                }
            }

            $colId = $this->getParam("colId");
            if ($condition) {
                $condition = "( " . $condition . " ) AND";
            }
            $condition .= " colId = " . $list->quote($colId);

            $list->setCondition($condition);

            $listItems = $list->load();

            $rootElement = array();

            $data = array();
            foreach ($listItems as $config) {
                $item = array(
                    "colId" => $config->getColId(),
                    "groupId" => $config->getGroupId(),
                    "groupName" => $config->getName(),
                    "groupDescription" => $config->getDescription(),
                    "id" => $config->getColId() . "-" . $config->getGroupId(),
                    "sorter" => $config->getSorter()
                );
                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();
            return $this->_helper->json($rootElement);
        }
    }

    public function listStoresAction()
    {
        $list = new Pimcore\Model\Object\Classificationstore\StoreConfig\Listing();
        $list = $list->load();
        return $this->_helper->json($list);
    }


    public function searchRelationsAction()
    {
        $db = Db::get();

        $storeId = $this->getParam("storeId");

        $mapping = array("keyName" => "name", "keyDescription" => "description");

        $start = 0;
        $limit = 15;
        $orderKey = "name";
        $order = "ASC";

        if ($this->getParam("dir")) {
            $order = $this->getParam("dir");
        }

        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $sortingSettings['orderKey'];
            $order = $sortingSettings['order'];
        }

        if ($this->getParam("overrideSort") == "true") {
            $orderKey = "id";
            $order = "DESC";
        }

        if ($this->getParam("limit")) {
            $limit = $this->getParam("limit");
        }
        if ($this->getParam("start")) {
            $start = $this->getParam("start");
        }

        $list = new Classificationstore\KeyGroupRelation\Listing();

        if ($limit > 0) {
            $list->setLimit($limit);
        }
        $list->setOffset($start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $conditionParts = array();

        if ($this->getParam("filter")) {
            $db = Db::get();
            $condition = "";
            $filterString = $this->getParam("filter");
            $filters = json_decode($filterString);

            $count = 0;

            foreach ($filters as $f) {
                $count++;
                $fieldname = $mapping[$f->field];
                $conditionParts[]= " AND " .$db->getQuoteIdentifierSymbol() . $fieldname . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
            }
        }

        $conditionParts[] = "  groupId IN (select id from classificationstore_groups where storeId = " . $db->quote($storeId) . ")";

        $searchfilter = $this->getParam("searchfilter");
        if ($searchfilter) {
            $conditionParts[] = "("
                . Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . ".name LIKE " . $db->quote("%" . $searchfilter . "%")
                . " OR " . Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . ".name LIKE " . $db->quote("%" . $searchfilter . "%")
                . " OR " . Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . ".description LIKE " . $db->quote("%" . $searchfilter . "%") . ")";
        }
        $condition = implode(" AND ", $conditionParts);
        $list->setCondition($condition);
        $list->setResolveGroupName(1);

        $listItems = $list->load();

        $rootElement = array();

        $data = array();
        foreach ($listItems as $config) {
            $item = array(
                "keyId" => $config->getKeyId(),
                "groupId" => $config->getGroupId(),
                "keyName" => $config->getName(),
                "keyDescription" => $config->getDescription(),
                "id" => $config->getGroupId() . "-" . $config->getKeyId(),
                "sorter" => $config->getSorter()
            );


            $groupConfig = Classificationstore\GroupConfig::getById($config->getGroupId());
            if ($groupConfig) {
                $item["groupName"] = $groupConfig->getName();
            }

            $data[] = $item;
        }
        $rootElement["data"] = $data;
        $rootElement["success"] = true;
        $rootElement["total"] = $list->getTotalCount();
        return $this->_helper->json($rootElement);
    }




    public function relationsAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $keyId = $data["keyId"];
            $groupId = $data["groupId"];
            $sorter = $data["sorter"];

            $config = new Classificationstore\KeyGroupRelation();
            $config->setGroupId($groupId);
            $config->setKeyId($keyId);
            $config->setSorter($sorter);

            $config->save();
            $data["id"] = $config->getGroupId() . "-" . $config->getKeyId();

            $this->_helper->json(array("success" => true, "data" => $data));
        } else {
            $mapping = array("keyName" => "name", "keyDescription" => "description");

            $start = 0;
            $limit = 15;
            $orderKey = "name";
            $order = "ASC";

            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                $orderKey = $sortingSettings['orderKey'];
                $order = $sortingSettings['order'];
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $list = new Classificationstore\KeyGroupRelation\Listing();

            if ($limit > 0) {
                $list->setLimit($limit);
            }
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            if ($this->getParam("filter")) {
                $db = Db::get();
                $conditionParts = array();
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                $count = 0;

                foreach ($filters as $f) {
                    $count++;
                    $fieldname = $mapping[$f->field];
                    $conditionParts[]= $db->getQuoteIdentifierSymbol() . $fieldname . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                }
            }

            if (!$this->getParam("relationIds")) {
                $groupId = $this->getParam("groupId");
                $conditionParts[]= " groupId = " . $list->quote($groupId);
            }

            $relationIds = $this->getParam("relationIds");
            if ($relationIds) {
                $relationIds = json_decode($relationIds, true);
                $relationParts = array();
                foreach ($relationIds as $relationId) {
                    $keyId = $relationId["keyId"];
                    $groupId = $relationId["groupId"];
                    $relationParts[] = "(keyId = " . $keyId . " and groupId = " . $groupId . ")";
                }
                $conditionParts[] = "(" . implode(" OR ", $relationParts) . ")";
            }

            $condition = implode(" AND ", $conditionParts);

            $list->setCondition($condition);

            $listItems = $list->load();

            $rootElement = array();

            $data = array();
            /** @var  $config Classificationstore\KeyGroupRelation */
            foreach ($listItems as $config) {
                $type = $config->getType();
                $definition = json_decode($config->getDefinition());
                $definition = \Pimcore\Model\Object\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                $item = array(
                    "keyId" => $config->getKeyId(),
                    "groupId" => $config->getGroupId(),
                    "keyName" => $config->getName(),
                    "keyDescription" => $config->getDescription(),
                    "id" => $config->getGroupId() . "-" . $config->getKeyId(),
                    "sorter" => $config->getSorter(),
                    "layout" => $definition
                );

                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();
            return $this->_helper->json($rootElement);
        }
    }

    public function addCollectionsAction()
    {
        $ids = \Zend_Json::decode($this->getParam("collectionIds"));

        if ($ids) {
            $db = \Pimcore\Db::get();
            $query = "select * from classificationstore_groups g, classificationstore_collectionrelations c where colId IN (" . implode(",", $ids)
                . ") and g.id = c.groupId";

            $mappedData = array();
            $groupsData = $db->fetchAll($query);

            foreach ($groupsData as $groupData) {
                $mappedData[$groupData["id"]] = $groupData;
            }

            $groupIdList = array();

            $allowedGroupIds = null;

            if ($this->getParam("oid")) {
                $object = Object_Concrete::getById($this->getParam("oid"));
                $class = $object->getClass();
                $fd = $class->getFieldDefinition($this->getParam("fieldname"));
                $allowedGroupIds = $fd->getAllowedGroupIds();
            }

            foreach ($groupsData as $groupItem) {
                $groupId = $groupItem["groupId"];
                if (!$allowedGroupIds || ($allowedGroupIds && in_array($groupId, $allowedGroupIds))) {
                    $groupIdList[] = $groupId;
                }
            }

            $groupCondition = "id in (" . implode(",", $groupIdList) .  ")";

            $groupList = new Classificationstore\GroupConfig\Listing();
            $groupList->setCondition($groupCondition);
            $groupList = $groupList->load();

            $keyCondition = "groupId in (" . implode(",", $groupIdList) . ")";

            $keyList = new Classificationstore\KeyGroupRelation\Listing();
            $keyList->setCondition($keyCondition);
            $keyList->setOrderKey(array("sorter", "id"));
            $keyList->setOrder(array("ASC", "ASC"));
            $keyList = $keyList->load();

            foreach ($groupList as $groupData) {
                $data[$groupData->getId()] = array(
                    "name" => $groupData->getName(),
                    "id" => $groupData->getId(),
                    "description" => $groupData->getDescription(),
                    "keys" => array(),
                    "collectionId" => $mappedData[$groupId]["colId"]
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
        }

        return $this->_helper->json($data);
    }


    public function addGroupsAction()
    {
        $ids = \Zend_Json::decode($this->getParam("groupIds"));

        $keyCondition = "groupId in (" . implode(",", $ids) . ")";

        $keyList = new Classificationstore\KeyGroupRelation\Listing();
        $keyList->setCondition($keyCondition);
        $keyList->setOrderKey(array("sorter", "id"));
        $keyList->setOrder(array("ASC", "ASC"));
        $keyList = $keyList->load();


        $groupCondition = "id in (" . implode(",", $ids) . ")";

        $groupList = new Classificationstore\GroupConfig\Listing();
        $groupList->setCondition($groupCondition);
        $groupList->setOrder("ASC");
        $groupList->setOrderKey("id");
        $groupList = $groupList->load();

        $data = array();

        foreach ($groupList as $groupData) {
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
            $type = $keyData->getType();
            $definition = json_decode($keyData->getDefinition());
            $definition = \Pimcore\Model\Object\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

            if (method_exists($definition, "__wakeup")) {
                $definition->__wakeup();
            }

            $keyList[] = array(
                "name" => $keyData->getName(),
                "id" => $keyData->getKeyId(),
                "description" => $keyData->getDescription(),
                "definition" => $definition
            );
            $data[$groupId]["keys"] = $keyList;
        }

        return $this->_helper->json($data);
    }

    public function propertiesAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
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

            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                $orderKey = $sortingSettings['orderKey'];
                $order = $sortingSettings['order'];
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $list = new Classificationstore\KeyConfig\Listing();

            if ($limit > 0) {
                $list->setLimit($limit);
            }
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $conditionParts = array();
            $db = \Pimcore\Db::get();

            $searchfilter = $this->getParam("searchfilter");
            if ($searchfilter) {
                $conditionParts[] = "(name LIKE " . $db->quote("%" . $searchfilter . "%") . " OR description LIKE " . $db->quote("%". $searchfilter . "%") . ")";
            }

            if ($this->getParam("storeId")) {
                $conditionParts[] = "(storeId = " . $this->getParam("storeId") . ")";
            }

            if ($this->getParam("filter")) {
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                foreach ($filters as $f) {
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $conditionParts[]= $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } else {
                        $conditionParts[]= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    }
                }
            }

            $condition = implode(" AND ", $conditionParts);
            $list->setCondition($condition);

            if ($this->getParam("groupIds") || $this->getParam("keyIds")) {
                $db = Db::get();

                if ($this->getParam("groupIds")) {
                    $ids = \Zend_Json::decode($this->getParam("groupIds"));
                    $col = "group";
                } else {
                    $ids = \Zend_Json::decode($this->getParam("keyIds"));
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
            foreach ($configList as $config) {
                $item = $this->getConfigItem($config);
                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();
            return $this->_helper->json($rootElement);
        }
    }


    protected function getConfigItem($config)
    {
        $name = $config->getName();

        $groupDescription = null;
        $item = array(
            "storeId" => $config->getStoreId(),
            "id" => $config->getId(),
            "name" => $name,
            "description" => $config->getDescription(),
            "type" => $config->getType() ? $config->getType() : "input",
            "definition" => $config->getDefinition()
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

    public function addPropertyAction()
    {
        $name = $this->getParam("name");
        $alreadyExist = false;
        $storeId = $this->getParam("storeId");

        if (!$alreadyExist) {
            $definition = array(
                "fieldtype" => "input",
                "name" => $name,
                "title" => $name,
                "datatype" => "data"
            );
            $config = new Classificationstore\KeyConfig();
            $config->setName($name);
            $config->setTitle($name);
            $config->setType("input");
            $config->setStoreId($storeId);
            $config->setEnabled(1);
            $config->setDefinition(json_encode($definition));
            $config->save();
        }

        $this->_helper->json(array("success" => !$alreadyExist, "id" => $config->getName()));
    }

    public function deletePropertyAction()
    {
        $id = $this->getParam("id");

        $config = Classificationstore\KeyConfig::getById($id);
//        $config->delete();
        $config->setEnabled(false);
        $config->save();

        $this->_helper->json(array("success" => true));
    }

    public function editStoreAction()
    {
        $id = $this->getParam("id");
        $data = json_decode($this->getParam("data"), true);

        $name = $data["name"];
        if (!$name) {
            throw new \Exception("Name must not be empty");
        }

        $description = $data["description"];

        $config = Classificationstore\StoreConfig::getByName($name);
        if ($config && $config->getId() != $id) {
            throw new \Exception("There is already a config with the same name");
        }

        $config = Classificationstore\StoreConfig::getById($id);

        if (!$config) {
            throw new \Exception("Configuration does not exist");
        }

        $config->setName($name);
        $config->setDescription($description);
        $config->save();

        $this->_helper->json(array("success" => true));
    }

    public function storetreeAction()
    {
        $result = array();
        $list = new Pimcore\Model\Object\Classificationstore\StoreConfig\Listing();
        $list = $list->load();
        /** @var  $item Classificationstore\StoreConfig */
        foreach ($list as $item) {
            $resultItem = array(
                "id" => $item->getId(),
                "text" => $item->getName(),
                "expandable" => false,
                "leaf" => true,
                "expanded" => true,
                "description" => $item->getDescription(),
                "iconCls" => "pimcore_icon_classificationstore"
            );

            $resultItem["qtitle"] = "ID: " . $item->getId();

            if ($item->getDescription()) {
            }
            $resultItem["qtip"] = $item->getDescription() ? $item->getDescription() : " ";
            $result[] = $resultItem;
        }

        return $this->_helper->json($result);
    }
}
