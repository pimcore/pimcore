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

class Admin_ObjectController extends Pimcore_Controller_Action_Admin
{
    /**
     * @var Object_Service
     */
    protected $_objectService;

    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array();
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAllowed("objects")) {

                $this->redirect("/admin/login");
                die();
            }
        }

        $this->_objectService = new Object_Service($this->getUser());
    }

    public function treeGetChildsByIdAction()
    {

        $object = Object_Abstract::getById($this->getParam("node"));
        if ($object->hasChilds()) {

            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->getParam("start"));


            $childsList = new Object_List();
            $condition = "o_parentId = '" . $object->getId() . "'";

            // custom views start
            if ($this->getParam("view")) {
                $cvConfig = Pimcore_Tool::getCustomViewConfig();
                $cv = $cvConfig[($this->getParam("view") - 1)];

                if ($cv["classes"]) {
                    $cvConditions = array();
                    $cvClasses = explode(",", $cv["classes"]);
                    foreach ($cvClasses as $cvClass) {
                        $cvConditions[] = "o_classId = '" . $cvClass . "'";
                    }

                    $cvConditions[] = "o_type = 'folder'";

                    if (count($cvConditions) > 0) {
                        $condition .= " AND (" . implode(" OR ", $cvConditions) . ")";
                    }
                }
            }
            // custom views end

            $childsList->setCondition($condition);
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey("o_key");
            $childsList->setOrder("asc");

            $childs = $childsList->load();

            foreach ($childs as $child) {
                $tmpObject = $this->getTreeNodeConfig($child);

                if ($child->isAllowed("list")) {
                    $objects[] = $tmpObject;
                }
            }
        }

        if ($this->getParam("limit")) {
            $this->_helper->json(array(
                                      "total" => $object->getChildAmount(),
                                      "nodes" => $objects
                                 ));
        }
        else {
            $this->_helper->json($objects);
        }

    }

    public function getRequiresDependenciesAction()
    {
        $id = $this->getParam("id");
        $object = Object_Abstract::getById($id);
        if ($object instanceof Object_Abstract) {
            $dependencies = Element_Service::getRequiresDependenciesForFrontend($object->getDependencies());
            $this->_helper->json($dependencies);
        }
        $this->_helper->json(false);
    }

    public function getRequiredByDependenciesAction()
    {
        $id = $this->getParam("id");
        $object = Object_Abstract::getById($id);
        if ($object instanceof Object_Abstract) {
            $dependencies = Element_Service::getRequiredByDependenciesForFrontend($object->getDependencies());
            $this->_helper->json($dependencies);
        }
        $this->_helper->json(false);
    }

    public function treeGetRootAction()
    {

        $id = 1;
        if ($this->getParam("id")) {
            $id = intval($this->getParam("id"));
        }

        $root = Object_Abstract::getById($id);
        if ($root->isAllowed("list")) {
            $this->_helper->json($this->getTreeNodeConfig($root));
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    /**
     * @param Object_Abstract $child
     * @return array
     */
    protected function getTreeNodeConfig($child)
    {


        $tmpObject = array(
            "id" => $child->getId(),
            "text" => $child->getKey(),
            "type" => $child->getType(),
            "path" => $child->getFullPath(),
            "basePath" => $child->getPath(),
            "elementType" => "object",
            "locked" => $child->isLocked(),
            "lockOwner" => $child->getLocked() ? true : false
        );

        $tmpObject["isTarget"] = false;
        $tmpObject["allowDrop"] = false;
        $tmpObject["allowChildren"] = false;

        $tmpObject["leaf"] = $child->hasNoChilds();
//        $tmpObject["iconCls"] = "pimcore_icon_object";

        $tmpObject["isTarget"] = true;
        $tmpObject["allowDrop"] = true;
        $tmpObject["allowChildren"] = true;
        $tmpObject["leaf"] = false;
        $tmpObject["cls"] = "";

        if ($child->getType() == "folder") {
//            $tmpObject["iconCls"] = "pimcore_icon_folder";
            $tmpObject["qtipCfg"] = array(
                "title" => "ID: " . $child->getId()
            );
        }
        else {
            $tmpObject["published"] = $child->isPublished();
            $tmpObject["className"] = $child->getClass()->getName();
            $tmpObject["qtipCfg"] = array(
                "title" => "ID: " . $child->getId(),
                "text" => 'Type: ' . $child->getClass()->getName()
            );

            if (!$child->isPublished()) {
                $tmpObject["cls"] .= "pimcore_unpublished ";
            }
//            if ($child->getClass()->getIcon()) {
//                unset($tmpObject["iconCls"]);
//                $tmpObject["icon"] = $child->getClass()->getIcon();
//            }
        }
        if($child->getElementAdminStyle()->getElementIcon()) {
            $tmpObject["icon"] = $child->getO_elementAdminStyle()->getElementIcon();
        }
        if($child->getElementAdminStyle()->getElementIconClass()) {
            $tmpObject["iconCls"] = $child->getO_elementAdminStyle()->getElementIconClass();
        }
        if($child->getElementAdminStyle()->getElementCssClass()) {
            $tmpObject["cls"] .= $child->getO_elementAdminStyle()->getElementCssClass() . " ";
        }


        $tmpObject["expanded"] = $child->hasNoChilds();
        $tmpObject["permissions"] = $child->getUserPermissions($this->getUser());


        if ($child->isLocked()) {
            $tmpObject["cls"] .= "pimcore_treenode_locked ";
        }
        if ($child->getLocked()) {
            $tmpObject["cls"] .= "pimcore_treenode_lockOwner ";
        }

        return $tmpObject;
    }

    public function getIdPathPagingInfoAction () {

        $path = $this->getParam("path");
        $pathParts = explode("/", $path);
        $id = array_pop($pathParts);

        $limit = $this->getParam("limit");

        if(empty($limit)) {
            $limit = 30;
        }

        $data = array();

        $targetObject = Object_Abstract::getById($id);
        $object = $targetObject;

        while ($parent = $object->getParent()) {
            $list = new Object_List();
            $list->setCondition("o_parentId = ?", $parent->getId());
            $list->setUnpublished(true);
            $total = $list->getTotalCount();

            $info = array(
                "total" => $total
            );

            if($total > $limit) {
                $idList = $list->loadIdList();
                $position = array_search($object->getId(), $idList);
                $info["position"] = $position+1;

                $info["page"] = ceil($info["position"]/$limit);
                $containsPaging = true;
            }

            $data[$parent->getId()] = $info;

            $object = $parent;
        }

        $this->_helper->json($data);
    }

    public function getAction()
    {

        // check for lock
        if (Element_Editlock::isLocked($this->getParam("id"), "object")) {
            $this->_helper->json(array(
                  "editlock" => Element_Editlock::getByElement($this->getParam("id"), "object")
            ));
        }
        Element_Editlock::lock($this->getParam("id"), "object");

        $object = Object_Abstract::getById(intval($this->getParam("id")));

        // set the latest available version for editmode
        $latestObject = $this->getLatestVersion($object);

        // we need to know if the latest version is published or not (a version), because of lazy loaded fields in $this->getDataForObject()
        $objectFromVersion = $latestObject === $object ? false : true;
        $object = $latestObject;

        if ($object->isAllowed("view")) {

            $objectData = array();

            $objectData["idPath"] = Pimcore_Tool::getIdPathForElement($object);
            $objectData["previewUrl"] = $object->getClass()->getPreviewUrl();
            $objectData["layout"] = $object->getClass()->getLayoutDefinitions();
            $this->getDataForObject($object, $objectFromVersion);
            $objectData["data"] = $this->objectData;
            $objectData["metaData"] = $this->metaData;

            $objectData["general"] = array();
            $allowedKeys = array("o_published", "o_key", "o_id", "o_modificationDate", "o_classId", "o_className", "o_locked", "o_type");

            foreach (get_object_vars($object) as $key => $value) {
                if (strstr($key, "o_") && in_array($key, $allowedKeys)) {
                    $objectData["general"][$key] = $value;
                }
            }

            $objectData["general"]["o_locked"] = $object->isLocked();

            $objectData["properties"] = Element_Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData["userPermissions"] = $object->getUserPermissions();
            $objectData["versions"] = $object->getVersions();
            $objectData["scheduledTasks"] = $object->getScheduledTasks();
            $objectData["general"]["allowVariants"] = $object->getClass()->getAllowVariants();

            if($object->getElementAdminStyle()->getElementIcon()) {
                $objectData["general"]["icon"] = $object->getO_elementAdminStyle()->getElementIcon();
            }
            if($object->getElementAdminStyle()->getElementIconClass()) {
                $objectData["general"]["iconCls"] = $object->getO_elementAdminStyle()->getElementIconClass();
            }


            if ($object instanceof Object_Concrete) {
                $objectData["lazyLoadedFields"] = $object->getLazyLoadedFields();
            }

            $objectData["childdata"]["id"] = $object->getId();
            $objectData["childdata"]["data"]["classes"] = $object->getResource()->getClasses();

            $this->_helper->json($objectData);
        }
        else {
            Logger::debug("prevented getting object id [ " . $object->getId() . " ] because of missing permissions");
            $this->_helper->json(array("success" => false, "message" => "missing_permission"));
        }


    }

    private $objectData;
    private $metaData;

    private function getDataForObject(Object_Concrete $object, $objectFromVersion = false) {
        foreach ($object->getClass()->getFieldDefinitions() as $key => $def) {
            $this->getDataForField($object, $key, $def, $objectFromVersion);
        }
    }

    /**
     * gets recursively attribute data from parent and fills objectData and metaData
     *
     * @param  $object
     * @param  $key
     * @param  $fielddefinition
     * @return void
     */
    private function getDataForField($object, $key, $fielddefinition, $objectFromVersion, $level = 0) {
        $parent = Object_Service::hasInheritableParentObject($object);
        $getter = "get" . ucfirst($key);

        // relations but not for objectsMetadata, because they have additional data which cannot be loaded directly from the DB
        // nonownerobjects should go in there anyway (regardless if it a version or not), so that the values can be loaded
        if (
            (!$objectFromVersion
            && $fielddefinition instanceof Object_Class_Data_Relations_Abstract
            && $fielddefinition->getLazyLoading()
            && !$fielddefinition instanceof Object_Class_Data_ObjectsMetadata )
            || $fielddefinition instanceof Object_Class_Data_Nonownerobjects
        ) {

            //lazy loading data is fetched from DB differently, so that not every relation object is instantiated
            if ($fielddefinition->isRemoteOwner()) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = Object_Class::getByName($fielddefinition->getOwnerClassName());
                $refId = $refClass->getId();
            } else {
                $refKey = $key;
            }
            $relations = $object->getRelationData($refKey, !$fielddefinition->isRemoteOwner(), $refId);
            if(empty($relations) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $data = array();

                if ($fielddefinition instanceof Object_Class_Data_Href) {
                    $data = $relations[0];
                } else {
                    foreach ($relations as $rel) {
                        if ($fielddefinition instanceof Object_Class_Data_Objects) {
                            $data[] = array($rel["id"], $rel["path"], $rel["subtype"]);
                        } else {
                            $data[] = array($rel["id"], $rel["path"], $rel["type"], $rel["subtype"]);
                        }
                    }
                }
                $this->objectData[$key] = $data;
                $this->metaData[$key]['objectid'] = $object->getId();
                $this->metaData[$key]['inherited'] = $level != 0;
            }

        } else {
            $value = $fielddefinition->getDataForEditmode($object->$getter(), $object, $objectFromVersion);
            if(empty($value) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $isInheritedValue = $level != 0;
                $this->metaData[$key]['objectid'] = $object->getId();

                $this->objectData[$key] = $value;
                $this->metaData[$key]['inherited'] = $isInheritedValue;

                if($isInheritedValue && !empty($value) &&  !$this->isInheritableField($fielddefinition)) {
                    $this->objectData[$key] = null;
                    $this->metaData[$key]['inherited'] = false;
                    $this->metaData[$key]['hasParentValue'] = true;
                } else {
                    // CF: I don't think this code is necessary at all - fact is, that it is buggy
//                    $parentValue = $this->getParentValue($object, $key);
//                    $this->metaData[$key]['hasParentValue'] = !empty($parentValue->value);
//                    if(!empty($parentValue->value)) {
//                        $this->metaData[$key]['objectid'] = $parentValue->id;
//                    }
                }

            }
        }
    }

    private function getParentValue($object, $key) {
        $parent = Object_Service::hasInheritableParentObject($object);
        $getter = "get" . ucfirst($key);
        if($parent) {
            $value = $parent->$getter();
            if($value) {
                $result = new stdClass();
                $result->value = $value;
                $result->id = $parent->getId();
                return $result;
            } else {
                return $this->getParentValue($parent, $key);
            }
        }
    }

    private function isInheritableField(Object_Class_Data $fielddefinition) {
        if($fielddefinition instanceof Object_Class_Data_Fieldcollections ||
           $fielddefinition instanceof Object_Class_Data_Localizedfields) {
            return false;
        }
        return true;
    }

    public function lockAction()
    {
        $object = Object_Abstract::getById($this->getParam("id"));
        if ($object instanceof Object_Abstract) {
            $object->setO_locked((bool)$this->getParam("locked"));
            //TODO: if latest version published - publish
            //if latest version not published just save new version

        }
    }

    public function getFolderAction()
    {

        // check for lock
        if (Element_Editlock::isLocked($this->getParam("id"), "object")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->getParam("id"), "object")
            ));
        }
        Element_Editlock::lock($this->getParam("id"), "object");

        $object = Object_Abstract::getById(intval($this->getParam("id")));
        if ($object->isAllowed("view")) {

            $objectData = array();

            $objectData["general"] = array();
            $objectData["idPath"] = Pimcore_Tool::getIdPathForElement($object);

            $allowedKeys = array("o_published", "o_key", "o_id", "o_type");
            foreach (get_object_vars($object) as $key => $value) {
                if (strstr($key, "o_") && in_array($key, $allowedKeys)) {
                    $objectData["general"][$key] = $value;
                }
            }

            $objectData["general"]["o_locked"] = $object->isLocked();

            $objectData["properties"] = Element_Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData["userPermissions"] = $object->getUserPermissions();
            $objectData["classes"] = $object->getResource()->getClasses();

            $this->_helper->json($objectData);
        }
        else {
            Logger::debug("prevented getting folder id [ " . $object->getId() . " ] because of missing permissions");
            $this->_helper->json(array("success" => false, "message" => "missing_permission"));
        }
    }


    public function addAction()
    {

        $success = false;

        $className = "Object_" . ucfirst($this->getParam("className"));
        // check for a mapped class
        $className = Pimcore_Tool::getModelClassMapping($className);

        $parent = Object_Abstract::getById($this->getParam("parentId"));

        $message = "";
        if ($parent->isAllowed("create")) {
            $intendedPath = $parent->getFullPath() . "/" . $this->getParam("key");

            if (!Object_Service::pathExists($intendedPath)) {

                $object = new $className();
                if($object instanceof Object_Concrete) {
                    $object->setOmitMandatoryCheck(true); // allow to save the object although there are mandatory fields
                }
                $object->setClassId($this->getParam("classId"));
                $object->setClassName($this->getParam("className"));
                $object->setParentId($this->getParam("parentId"));
                $object->setKey($this->getParam("key"));
                $object->setCreationDate(time());
                $object->setUserOwner($this->getUser()->getId());
                $object->setUserModification($this->getUser()->getId());
                $object->setPublished(false);

                if($this->getParam("objecttype") == Object_Abstract::OBJECT_TYPE_OBJECT || $this->getParam("objecttype") == Object_Abstract::OBJECT_TYPE_VARIANT) {
                    $object->setO_type($this->getParam("objecttype"));
                }

                try {

                    $object->save();
                    $success = true;
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }

            } else {
                $message = "prevented creating object because object with same path+key already exists";
                Logger::debug("prevented creating object because object with same path+key [ $intendedPath ] already exists");
            }
        } else {
            $message = "prevented adding object because of missing permissions";
            Logger::debug($message);
        }

        if ($success) {
            $this->_helper->json(array(
                                      "success" => $success,
                                      "id" => $object->getId(),
                                      "type" => $object->getType(),
                                      "message" => $message
                                 ));
        }
        else {
            $this->_helper->json(array(
                                      "success" => $success,
                                      "message" => $message
                                 ));
        }
    }

    public function addFolderAction()
    {
        $success = false;

        $parent = Object_Abstract::getById($this->getParam("parentId"));
        if ($parent->isAllowed("create")) {

            if (!Object_Service::pathExists($parent->getFullPath() . "/" . $this->getParam("key"))) {
                $folder = Object_Folder::create(array(
                     "o_parentId" => $this->getParam("parentId"),
                     "o_creationDate" => time(),
                     "o_userOwner" => $this->user->getId(),
                     "o_userModification" => $this->user->getId(),
                     "o_key" => $this->getParam("key"),
                     "o_published" => true
                ));

                $folder->setCreationDate(time());
                $folder->setUserOwner($this->getUser()->getId());
                $folder->setUserModification($this->getUser()->getId());

                try {
                    $folder->save();
                    $success = true;
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
        }
        else {
            Logger::debug("prevented creating object id because of missing permissions");
        }

        $this->_helper->json(array("success" => $success));
    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {

            $parentObject = Object_Abstract::getById($this->getParam("id"));
            
            $list = new Object_List();
            $list->setCondition("o_path LIKE '" . $parentObject->getFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(o_path)", false);
            $list->setOrder("DESC");

            $objects = $list->load();

            $deletedItems = array();
            foreach ($objects as $object) {
                $deletedItems[] = $object->getFullPath();
                $object->delete();
            }

            $this->_helper->json(array("success" => true, "deleted" => $deletedItems));
            
        } else if($this->getParam("id")) {
            $object = Object_Abstract::getById($this->getParam("id"));
            if ($object->isAllowed("delete")) {
                $object->delete();

                $this->_helper->json(array("success" => true));
            }
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function deleteInfoAction()
    {

        $hasDependency = false;

        try {
            $object = Object_Abstract::getById($this->getParam("id"));
            $hasDependency = $object->getDependencies()->isRequired();
        }
        catch (Exception $e) {
            Logger::err("failed to access object with id: " . $this->getParam("id"));
        }

        $deleteJobs = array();

        // check for childs
        if($object instanceof Object_Abstract) {

            $deleteJobs[] = array(array(
                "url" => "/admin/recyclebin/add",
                "params" => array(
                    "type" => "object",
                    "id" => $object->getId()
                )
            ));

            $hasChilds = $object->hasChilds();
            if (!$hasDependency) {
                $hasDependency = $hasChilds;
            }

            $childs = 0;
            if($hasChilds) {
                // get amount of childs
                $list = new Object_List();
                $list->setCondition("o_path LIKE '" . $object->getFullPath() . "/%'");
                $childs = $list->getTotalCount();

                if($childs > 0) {
                    $deleteObjectsPerRequest = 5;
                    for($i=0; $i<ceil($childs/$deleteObjectsPerRequest); $i++) {
                        $deleteJobs[] = array(array(
                            "url" => "/admin/object/delete",
                            "params" => array(
                                "step" => $i,
                                "amount" => $deleteObjectsPerRequest,
                                "type" => "childs",
                                "id" => $object->getId()
                            )
                        ));
                    }
                }
            }

            // the object itself is the last one
            $deleteJobs[] = array(array(
                "url" => "/admin/object/delete",
                "params" => array(
                    "id" => $object->getId()
                )
            ));
        }

        $this->_helper->json(array(
              "hasDependencies" => $hasDependency,
              "childs" => $childs,
              "deletejobs" => $deleteJobs
        ));
    }


    public function updateAction()
    {

        $success = false;
        $allowUpdate = true;

        $object = Object_Abstract::getById($this->getParam("id"));
        if($object instanceof Object_Concrete) {
            $object->setOmitMandatoryCheck(true);
        }

        $values = Zend_Json::decode($this->getParam("values"));

        if ($object->isAllowed("settings")) {



            if ($values["key"] && $object->isAllowed("rename")) {
                $object->setKey($values["key"]);
            } else if ($values["key"]!= $object->getKey()){
                Logger::debug("prevented renaming object because of missing permissions ");
            }

            if ($values["parentId"]) {
                $parent = Object_Abstract::getById($values["parentId"]);

                //check if parent is changed
                if ($object->getParentId() != $parent->getId()) {

                    if(!$parent->isAllowed("create")){
                        throw new Exception("Prevented moving object - no create permission on new parent ");
                    }

                    $objectWithSamePath = Object_Abstract::getByPath($parent->getFullPath() . "/" . $object->getKey());

                    if ($objectWithSamePath != null) {
                        $allowUpdate = false;
                    }
                }

                //$object->setO_path($newPath);
                $object->setParentId($values["parentId"]);


            }

            if (array_key_exists("locked", $values)) {
                $object->setLocked($values["locked"]);
            }

            if ($allowUpdate) {
                $object->setModificationDate(time());
                $object->setUserModification($this->getUser()->getId());

                try {
                    $object->save();
                    $success = true;
                } catch (Exception $e) {
                    Logger::error($e);
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
            else {
                Logger::debug("prevented move of object, object with same path+key alredy exists in this location.");
            }
        } else if ($object->isAllowed("rename") &&  $values["key"] ) {
            //just rename
            try {
                    $object->setKey($values["key"]);
                    $object->save();
                    $success = true;
                } catch (Exception $e) {
                    Logger::error($e);
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
        } else {
            Logger::debug("prevented update object because of missing permissions.");
        }

        $this->_helper->json(array("success" => $success));
    }


    public function saveAction()
    {

        $object = Object_Abstract::getById($this->getParam("id"));

        // set the latest available version for editmode
        $object = $this->getLatestVersion($object);
        $object->setUserModification($this->getUser()->getId());

        // data
        if ($this->getParam("data")) {

            $data = Zend_Json::decode($this->getParam("data"));
            foreach ($data as $key => $value) {

                $fd = $object->getClass()->getFieldDefinition($key);
                if ($fd) {
                    if (method_exists($fd, "isRemoteOwner") and $fd->isRemoteOwner()) {
                        $relations = $object->getRelationData($fd->getOwnerFieldName(), false, null);
                        $toAdd = $this->detectAddedRemoteOwnerRelations($relations, $value);
                        $toDelete = $this->detectDeletedRemoteOwnerRelations($relations, $value);
                        if (count($toAdd) > 0 or count($toDelete) > 0) {
                            $this->processRemoteOwnerRelations($object, $toDelete, $toAdd, $fd->getOwnerFieldName());
                        }
                    } else {
                        $object->setValue($key, $fd->getDataFromEditmode($value, $object));
                    }
                }
            }
        }

        // general settings
        // @TODO: IS THIS STILL NECESSARY?
        if ($this->getParam("general")) {
            $general = Zend_Json::decode($this->getParam("general"));

            // do not allow all values to be set, will cause problems (eg. icon)
            if (is_array($general) && count($general) > 0) {
                foreach ($general as $key => $value) {
                    if(!in_array($key, array("o_id", "o_classId", "o_className", "o_type", "icon"))) {
                        $object->setValue($key,$value);
                    }
                }
            }
        }

        $object = $this->assignPropertiesFromEditmode($object);


        // scheduled tasks
        if ($this->getParam("scheduler")) {
            $tasks = array();
            $tasksData = Zend_Json::decode($this->getParam("scheduler"));

            if (!empty($tasksData)) {
                foreach ($tasksData as $taskData) {
                    $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                    $task = new Schedule_Task($taskData);
                    $tasks[] = $task;
                }
            }

            $object->setScheduledTasks($tasks);
        }

        if ($this->getParam("task") == "unpublish") {
            $object->setPublished(false);
        }
        if ($this->getParam("task") == "publish") {
            $object->setPublished(true);
        }

        // unpublish and save version is possible without checking mandatory fields
        if($this->getParam("task") == "unpublish" || $this->getParam("task") == "version") {
            $object->setOmitMandatoryCheck(true);
        }


        if (($this->getParam("task") == "publish" && $object->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $object->isAllowed("unpublish"))) {

            try {
                $object->save();
                $this->_helper->json(array("success" => true));
            } catch (Exception $e) {
                Logger::log($e);
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }

        }
        else if ($this->getParam("task") == "session") {
            $key = "object_" . $object->getId();
            $session = new Zend_Session_Namespace("pimcore_objects");
            //$object->_fulldump = true; // not working yet, donno why
            $session->$key = $object;

            $this->_helper->json(array("success" => true));
        }
        else {
            if ($object->isAllowed("save")) {
                try {
                    $object->saveVersion();
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    Logger::log($e);
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
        }


    }

    public function saveFolderAction()
    {

        $object = Object_Abstract::getById($this->getParam("id"));
        $classId = $this->getParam("class_id");

        // general settings
        $general = Zend_Json::decode($this->getParam("general"));
        $object->setValues($general);
        $object->setUserModification($this->getUser()->getId());

        $object = $this->assignPropertiesFromEditmode($object);

        if ($object->isAllowed("publish")) {
            try {

                // grid config
                $gridConfig = Zend_Json::decode($this->getParam("gridconfig"));
                if($classId) {
                    $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . "_" . $classId . "-user_" . $this->getUser()->getId() . ".psf";
                } else {
                    $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . "-user_" . $this->getUser()->getId() . ".psf";
                }

                $configDir = dirname($configFile);
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0755, true);
                }
                file_put_contents($configFile, Pimcore_Tool_Serialize::serialize($gridConfig));
                chmod($configFile, 0766);


                $object->save();
                $this->_helper->json(array("success" => true));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    protected function assignPropertiesFromEditmode($object)
    {

        if ($this->getParam("properties")) {
            $properties = array();
            // assign inherited properties
            foreach ($object->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = Zend_Json::decode($this->getParam("properties"));

            if (is_array($propertiesData)) {
                foreach ($propertiesData as $propertyName => $propertyData) {

                    $value = $propertyData["data"];


                    try {
                        $property = new Property();
                        $property->setType($propertyData["type"]);
                        $property->setName($propertyName);
                        $property->setCtype("object");
                        $property->setDataFromEditmode($value);
                        $property->setInheritable($propertyData["inheritable"]);

                        $properties[$propertyName] = $property;
                    }
                    catch (Exception $e) {
                        Logger::err("Can't add " . $propertyName . " to object " . $object->getFullPath());
                    }
                }
            }
            $object->setProperties($properties);
        }

        return $object;
    }


    public function getPredefinedPropertiesAction()
    {

        $list = new Property_Predefined_List();
        $list->setCondition("ctype = 'object'");
        $list->load();

        $properties = array();
        foreach ($list->getProperties() as $type) {
            $properties[] = $type;
        }

        $this->_helper->json(array("properties" => $properties));
    }

    public function deleteVersionAction()
    {
        $version = Version::getById($this->getParam("id"));
        $version->delete();

        $this->_helper->json(array("success" => true));
    }

    public function publishVersionAction()
    {

        $version = Version::getById($this->getParam("id"));
        $object = $version->loadData();

        $currentObject = Object_Abstract::getById($object->getId());
        if ($currentObject->isAllowed("publish")) {
            $object->setPublished(true);
            $object->setUserModification($this->getUser()->getId());
            try {
                $object->save();
                $this->_helper->json(array("success" => true));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function previewVersionAction()
    {
        $version = Version::getById($this->getParam("id"));
        $object = $version->loadData();

        $this->view->object = $object;
    }

    public function diffVersionsAction()
    {
        $version1 = Version::getById($this->getParam("from"));
        $object1 = $version1->loadData();

        $version2 = Version::getById($this->getParam("to"));
        $object2 = $version2->loadData();

        $this->view->object1 = $object1;
        $this->view->object2 = $object2;
    }

    public function getVersionsAction()
    {
        if ($this->getParam("id")) {
            $object = Object_Abstract::getById($this->getParam("id"));
            $versions = $object->getVersions();

            $this->_helper->json(array("versions" => $versions));
        }
    }


    public function gridProxyAction()
    {

        if($this->getParam("language")) {
            $this->setLanguage($this->getParam("language"), true);
        }

        if ($this->getParam("data")) {
            if ($this->getParam("xaction") == "update") {

                $data = Zend_Json::decode($this->getParam("data"));

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
                    $this->_helper->json(array("data" => Object_Service::gridObjectData($object, $this->getParam("fields")), "success" => true));
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
        } else {
            // get list of objects
            $folder = Object_Abstract::getById($this->getParam("folderId"));
            $class = Object_Class::getById($this->getParam("classId"));
            $className = $class->getName();

            $colMappings = array(
                "filename" => "o_key",
                "fullpath" => array("o_path", "o_key"),
                "id" => "o_id",
                "published" => "o_published",
                "modificationDate" => "o_modificationDate",
                "creationDate" => "o_creationDate"
            );

            $start = 0;
            $limit = 20;
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
                if (array_key_exists($this->getParam("sort"), $colMappings)) {
                    $orderKey = $colMappings[$this->getParam("sort")];
                } else {
                    $orderKey = $this->getParam("sort");
                }
            }
            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            $listClass = "Object_" . ucfirst($className) . "_List";

            $conditionFilters = array();
            if($this->getParam("only_direct_children") == "true") {
                $conditionFilters[] = "o_parentId = " . $folder->getId();
            } else {
                $conditionFilters[] = "(o_path = '" . $folder->getFullPath() . "' OR o_path LIKE '" . str_replace("//","/",$folder->getFullPath() . "/") . "%')";
            }

            // create filter condition
            if ($this->getParam("filter")) {
                $conditionFilters[] = Object_Service::getFilterCondition($this->getParam("filter"), $class);
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

            $list->load();

            $objects = array();
            foreach ($list->getObjects() as $object) {
                $o = Object_Service::gridObjectData($object, $fields);
                $objects[] = $o;
            }
            $this->_helper->json(array("data" => $objects, "success" => true, "total" => $list->getTotalCount()));
        }


    }

    public function copyInfoAction() {

        $transactionId = time();
        $pasteJobs = array();
        $session = new Zend_Session_Namespace("pimcore_copy");
        $session->$transactionId = array();

        if ($this->getParam("type") == "recursive") {

            $object = Object_Abstract::getById($this->getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = array(array(
                "url" => "/admin/object/copy",
                "params" => array(
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => "child",
                    "transactionId" => $transactionId,
                    "saveParentId" => true
                )
            ));

            if($object->hasChilds(array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER, Object_Abstract::OBJECT_TYPE_VARIANT))) {
                // get amount of childs
                $list = new Object_List();
                $list->setCondition("o_path LIKE '" . $object->getFullPath() . "/%'");
                $list->setOrderKey("LENGTH(o_path)", false);
                $list->setOrder("ASC");
                $list->setObjectTypes(array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER, Object_Abstract::OBJECT_TYPE_VARIANT));
                $childIds = $list->loadIdList();

                if(count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = array(array(
                            "url" => "/admin/object/copy",
                            "params" => array(
                                "sourceId" => $id,
                                "targetParentId" => $this->getParam("targetId"),
                                "sourceParentId" => $this->getParam("sourceId"),
                                "type" => "child",
                                "transactionId" => $transactionId
                            )
                        ));
                    }
                }
            }
        }
        else if ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = array(array(
                "url" => "/admin/object/copy",
                "params" => array(
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => $this->getParam("type"),
                    "transactionId" => $transactionId
                )
            ));
        }


        $this->_helper->json(array(
            "pastejobs" => $pasteJobs
        ));
    }


    public function copyAction()
    {
        $success = false;
        $message = "";
        $sourceId = intval($this->getParam("sourceId"));
        $source = Object_Abstract::getById($sourceId);
        $session = new Zend_Session_Namespace("pimcore_copy");

        $targetId = intval($this->getParam("targetId"));
        if($this->getParam("targetParentId")) {
            $sourceParent = Object_Abstract::getById($this->getParam("sourceParentId"));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if($session->{$this->getParam("transactionId")}["parentId"]) {
                $targetParent = Object_Abstract::getById($session->{$this->getParam("transactionId")}["parentId"]);
            } else {
                $targetParent = Object_Abstract::getById($this->getParam("targetParentId"));
            }

            $targetPath = preg_replace("@^".$sourceParent->getFullPath()."@", $targetParent."/", $source->getPath());
            $target = Object_Abstract::getByPath($targetPath);
        } else {
            $target = Object_Abstract::getById($targetId);
        }

        if ($target->isAllowed("create")) {
            $source = Object_Abstract::getById($sourceId);
            if ($source != null) {
                try {
                    if ($this->getParam("type") == "child") {
                        $newObject = $this->_objectService->copyAsChild($target, $source);

                        // this is because the key can get the prefix "_copy" if the target does already exists
                        if($this->getParam("saveParentId")) {
                            $session->{$this->getParam("transactionId")}["parentId"] = $newObject->getId();
                        }
                    }
                    else if ($this->getParam("type") == "replace") {
                        $this->_objectService->copyContents($target, $source);
                    }

                    $success = true;
                } catch (Exception $e) {
                    Logger::err($e);
                    $success = false;
                    $message = $e->getMessage() . " in object " . $source->getFullPath() . " [id: " . $source->getId() . "]";
                }
            }
            else {
                Logger::error("could not execute copy/paste, source object with id [ $sourceId ] not found");
                $this->_helper->json(array("success" => false, "message" => "source object not found"));
            }
        } else {
            Logger::error("could not execute copy/paste because of missing permissions on target [ ".$targetId." ]");
            $this->_helper->json(array("error" => false, "message" => "missing_permission"));
        }

        $this->_helper->json(array("success" => $success, "message" => $message));
    }


    public function previewAction () {


        $id = $this->getParam("id");
        $key = "object_" . $id;
        $session = new Zend_Session_Namespace("pimcore_objects");
        if($session->$key) {
            $object = $session->$key;
        } else {
            die("Preview not available, it seems that there's a problem with this object.");
        }

        $url = $object->getClass()->getPreviewUrl();

        // replace named variables
        $vars = get_object_vars($object);
        foreach ($vars as $key => $value) {
            if(!empty($value)) {
                $url = str_replace("%".$key, urlencode($value), $url);
            } else {
                if(strpos($url, "%".$key) !== false) {
                    die("No preview available, please ensure that all fields which are required for the preview are filled correctly.");
                }
            }
        }

        $urlParts = parse_url($url);
        $this->redirect($urlParts["path"] . "?pimcore_object_preview=" . $id . "&_dc=" . time() . "&" . $urlParts["query"]);
    }

    /**
     * @param  Object_Concrete $object
     * @param  array $toDelete
     * @param  array $toAdd
     * @param  string $ownerFieldName
     * @return void
     */
    protected function  processRemoteOwnerRelations($object, $toDelete, $toAdd, $ownerFieldName)
    {

        $getter = "get" . ucfirst($ownerFieldName);
        $setter = "set" . ucfirst($ownerFieldName);

        foreach ($toDelete as $id) {

            $owner = Object_Abstract::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    for ($i = 0; $i < count($currentData); $i++) {
                        if ($currentData[$i]->getId() == $object->getId()) {
                            unset($currentData[$i]);
                            $owner->$setter($currentData);
                            $owner->setUserModification($this->getUser()->getId());
                            $owner->save();
                            Logger::debug("Saved object id [ " . $owner->getId() . " ] by remote modification through [" . $object->getId() . "], Action: deleted [ " . $object->getId() . " ] from [ $ownerFieldName]");
                            break;
                        }
                    }
                }
            }
        }


        foreach ($toAdd as $id) {
            $owner = Object_Abstract::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                $currentData[] = $object;

                $owner->$setter($currentData);
                $owner->setUserModification($this->getUser()->getId());
                $owner->save();
                Logger::debug("Saved object id [ " . $owner->getId() . " ] by remote modification through [" . $object->getId() . "], Action: added [ " . $object->getId() . " ] to [ $ownerFieldName ]");
            }
        }
    }

    /**
     * @param  array $relations
     * @param  array $value
     * @return array
     */
    protected function detectDeletedRemoteOwnerRelations($relations, $value)
    {
        $originals = array();
        $changed = array();
        foreach ($relations as $r) {
            $originals[] = $r["dest_id"];
        }
        if (is_array($value)) {
            foreach ($value as $row) {
                $changed[] = $row['id'];
            }
        }
        $diff = array_diff($originals, $changed);
        return $diff;
    }

    /**
     * @param  array $relations
     * @param  array $value
     * @return array
     */
    protected function detectAddedRemoteOwnerRelations($relations, $value)
    {
        $originals = array();
        $changed = array();
        foreach ($relations as $r) {
            $originals[] = $r["dest_id"];
        }
        if (is_array($value)) {
            foreach ($value as $row) {
                $changed[] = $row['id'];
            }
        }
        $diff = array_diff($changed, $originals);
        return $diff;
    }

    /**
     * @param  Object_Concrete $object
     * @return Object_Concrete
     */
    protected function getLatestVersion(Object_Concrete $object)
    {
        $modificationDate = $object->getModificationDate();
        $latestVersion = $object->getLatestVersion();
        if ($latestVersion) {
            $latestObj = $latestVersion->loadData();
            if ($latestObj instanceof Object_Concrete) {
                $object = $latestObj;
                $object->setModificationDate($modificationDate); // set de modification-date from published version to compare it in js-frontend
            }
        }
        return $object;
    }
}
