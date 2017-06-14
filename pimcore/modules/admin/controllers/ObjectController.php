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

use Pimcore\Tool;
use Pimcore\File;
use Pimcore\Model\Object;
use Pimcore\Model\Element;
use Pimcore\Model;
use Pimcore\Logger;

class Admin_ObjectController extends \Pimcore\Controller\Action\Admin\Element
{
    /**
     * @var Object\Service
     */
    protected $_objectService;

    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = [];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("objects");
        }

        $this->_objectService = new Object\Service($this->getUser());
    }

    public function treeGetChildsByIdAction()
    {
        $object = Object\AbstractObject::getById($this->getParam("node"));
        $objectTypes = null;
        $objects = [];
        $cv = false;
        $offset = 0;
        $total = 0;
        if ($object instanceof Object\Concrete) {
            $class = $object->getClass();
            if ($class->getShowVariants()) {
                $objectTypes = [Object\AbstractObject::OBJECT_TYPE_FOLDER, Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_VARIANT];
            }
        }

        if (!$objectTypes) {
            $objectTypes = [Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER];
        }

        if ($object->hasChilds($objectTypes)) {
            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->getParam("start"));


            $childsList = new Object\Listing();
            $condition = "objects.o_parentId = '" . $object->getId() . "'";

            // custom views start
            if ($this->getParam("view")) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($this->getParam("view"));

                if ($cv["classes"]) {
                    $cvConditions = [];
                    $cvClasses = explode(",", $cv["classes"]);
                    foreach ($cvClasses as $cvClass) {
                        $cvConditions[] = "objects.o_classId = '" . $cvClass . "'";
                    }

                    $cvConditions[] = "objects.o_type = 'folder'";

                    if (count($cvConditions) > 0) {
                        $condition .= " AND (" . implode(" OR ", $cvConditions) . ")";
                    }
                }
            }
            // custom views end

            if (!$this->getUser()->isAdmin()) {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $condition .= " AND (
                                                    (select list from users_workspaces_object where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_object where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )";
            }


            $childsList->setCondition($condition);
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey("FIELD(objects.o_type, 'folder') DESC, objects.o_key ASC", false);
            $childsList->setObjectTypes($objectTypes);

            Element\Service::addTreeFilterJoins($cv, $childsList);

            $childs = $childsList->load();

            foreach ($childs as $child) {
                $tmpObject = $this->getTreeNodeConfig($child);

                if ($child->isAllowed("list")) {
                    $objects[] = $tmpObject;
                }
            }
            //pagination for custom view
            $total = $cv
                ? $childsList->count()
                : $object->getChildAmount([Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER,
                    Object\AbstractObject::OBJECT_TYPE_VARIANT], $this->getUser());
        }

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new Model\Tool\Admin\EventDataContainer($objects);
        \Pimcore::getEventManager()->trigger("admin.object.treeGetChildsById.preSendData", $this, ["returnValueContainer" => $returnValueContainer]);


        if ($this->getParam("limit")) {
            $this->_helper->json([
                "offset" => $offset,
                "limit" => $limit,
                "total" => $total,
                "nodes" => $returnValueContainer->getData(),
                "fromPaging" => intval($this->getParam("fromPaging"))
            ]);
        } else {
            $this->_helper->json($returnValueContainer->getData());
        }
    }

    /**
     * @param Object\AbstractObject $element
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        $child = $element;

        $tmpObject = [
            "id" => $child->getId(),
            "text" => $child->getKey(),
            "type" => $child->getType(),
            "path" => $child->getRealFullPath(),
            "basePath" => $child->getRealPath(),
            "elementType" => "object",
            "locked" => $child->isLocked(),
            "lockOwner" => $child->getLocked() ? true : false
        ];

        $allowedTypes = [Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER];
        if ($child instanceof Object\Concrete && $child->getClass()->getShowVariants()) {
            $allowedTypes[] = Object\AbstractObject::OBJECT_TYPE_VARIANT;
        }

        $hasChildren = $child->hasChildren($allowedTypes);

        $tmpObject["isTarget"] = false;
        $tmpObject["allowDrop"] = false;
        $tmpObject["allowChildren"] = false;

        $tmpObject["leaf"] = !$hasChildren;

        $tmpObject["isTarget"] = true;
        if ($tmpObject["type"] != "variant") {
            $tmpObject["allowDrop"] = true;
        }

        $tmpObject["allowChildren"] = true;

        if (\Pimcore\Tool\Admin::isExtJS6()) {
            $tmpObject["leaf"] = !$hasChildren;
        } else {
            $tmpObject["leaf"] = false;
        }
        $tmpObject["cls"] = "";

        $tmpObject["qtipCfg"] = $child->getElementAdminStyle()->getElementQtipConfig();

        if ($child->getType() != "folder") {
            $tmpObject["published"] = $child->isPublished();
            $tmpObject["className"] = $child->getClass()->getName();


            if (!$child->isPublished()) {
                $tmpObject["cls"] .= "pimcore_unpublished ";
            }

            $tmpObject["allowVariants"] = $child->getClass()->getAllowVariants();
        }
        if ($tmpObject["type"] == "variant") {
            $tmpObject["iconCls"] = "pimcore_icon_variant";
        } else {
            if ($child->getElementAdminStyle()->getElementIcon()) {
                $tmpObject["icon"] = $child->getElementAdminStyle()->getElementIcon();
            }

            if ($child->getElementAdminStyle()->getElementIconClass()) {
                $tmpObject["iconCls"] = $child->getElementAdminStyle()->getElementIconClass();
            }
        }

        if ($child->getElementAdminStyle()->getElementCssClass()) {
            $tmpObject["cls"] .= $child->getElementAdminStyle()->getElementCssClass() . " ";
        }


        $tmpObject["expanded"] = !$hasChildren;
        $tmpObject["permissions"] = $child->getUserPermissions($this->getUser());


        if ($child->isLocked()) {
            $tmpObject["cls"] .= "pimcore_treenode_locked ";
        }
        if ($child->getLocked()) {
            $tmpObject["cls"] .= "pimcore_treenode_lockOwner ";
        }

        if (\Pimcore\Tool\Admin::isExtJS6()) {
            if ($tmpObject["leaf"]) {
                $tmpObject["expandable"] = false;
                $tmpObject["expanded"] = true;
                $tmpObject["leaf"] = false;
                $tmpObject["loaded"] = true;
            }
        }


        return $tmpObject;
    }

    public function getIdPathPagingInfoAction()
    {
        $path = $this->getParam("path");
        $pathParts = explode("/", $path);
        $id = array_pop($pathParts);

        $limit = $this->getParam("limit");

        if (empty($limit)) {
            $limit = 30;
        }

        $data = [];

        $targetObject = Object::getById($id);
        $object = $targetObject;

        while ($parent = $object->getParent()) {
            $list = new Object\Listing();
            $list->setCondition("o_parentId = ?", $parent->getId());
            $list->setUnpublished(true);
            $total = $list->getTotalCount();

            $info = [
                "total" => $total
            ];

            if ($total > $limit) {
                $idList = $list->loadIdList();
                $position = array_search($object->getId(), $idList);
                $info["position"] = $position + 1;

                $info["page"] = ceil($info["position"] / $limit);
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
        if (Element\Editlock::isLocked($this->getParam("id"), "object")) {
            $this->_helper->json([
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "object")
            ]);
        }
        Element\Editlock::lock($this->getParam("id"), "object");

        $object = Object::getById(intval($this->getParam("id")));
        $object = clone $object;

        // set the latest available version for editmode
        $latestObject = $this->getLatestVersion($object);

        // we need to know if the latest version is published or not (a version), because of lazy loaded fields in $this->getDataForObject()
        $objectFromVersion = $latestObject === $object ? false : true;
        $object = $latestObject;

        if ($object->isAllowed("view")) {
            $objectData = [];

            $objectData["idPath"] = Element\Service::getIdPath($object);
            $objectData["previewUrl"] = $object->getClass()->getPreviewUrl();

            $objectData["general"] = [];
            $allowedKeys = ["o_published", "o_key", "o_id", "o_modificationDate", "o_creationDate", "o_classId", "o_className", "o_locked", "o_type", "o_parentId", "o_userOwner", "o_userModification"];

            foreach (get_object_vars($object) as $key => $value) {
                if (strstr($key, "o_") && in_array($key, $allowedKeys)) {
                    $objectData["general"][$key] = $value;
                }
            }

            $objectData["general"]["o_locked"] = $object->isLocked();

            $this->getDataForObject($object, $objectFromVersion);
            $objectData["data"] = $this->objectData;

            $objectData["metaData"] = $this->metaData;

            $objectData["layout"] = $object->getClass()->getLayoutDefinitions();

            $objectData["properties"] = Element\Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData["userPermissions"] = $object->getUserPermissions();
            $objectVersions = Element\Service::getSafeVersionInfo($object->getVersions());
            $objectData["versions"] = array_splice($objectVersions, 0, 1);
            $objectData["scheduledTasks"] = $object->getScheduledTasks();
            $objectData["general"]["allowVariants"] = $object->getClass()->getAllowVariants();
            $objectData["general"]["showVariants"] = $object->getClass()->getShowVariants();
            $objectData["general"]["fullpath"] = $object->getRealFullPath();

            if ($object->getElementAdminStyle()->getElementIcon()) {
                $objectData["general"]["icon"] = $object->getElementAdminStyle()->getElementIcon();
            }
            if ($object->getElementAdminStyle()->getElementIconClass()) {
                $objectData["general"]["iconCls"] = $object->getElementAdminStyle()->getElementIconClass();
            }


            if ($object instanceof Object\Concrete) {
                $objectData["lazyLoadedFields"] = $object->getLazyLoadedFields();
            }

            $objectData["childdata"]["id"] = $object->getId();
            $objectData["childdata"]["data"]["classes"] = $this->prepareChildClasses($object->getDao()->getClasses());

            $currentLayoutId = $this->getParam("layoutId", null);

            $validLayouts = Object\Service::getValidLayouts($object);

            //master layout has id 0 so we check for is_null()
            if (is_null($currentLayoutId) && !empty($validLayouts)) {
                foreach ($validLayouts as $checkDefaultLayout) {
                    if ($checkDefaultLayout->getDefault()) {
                        $currentLayoutId = $checkDefaultLayout->getId();
                    }
                }
            }
            if (!empty($validLayouts)) {
                $objectData["validLayouts"] = [ ];

                foreach ($validLayouts as $validLayout) {
                    $objectData["validLayouts"][] = ["id" => $validLayout->getId(), "name" => $validLayout->getName()];
                }

                $user = Tool\Admin::getCurrentUser();
                if ($currentLayoutId == 0 && !$user->isAdmin()) {
                    $first = reset($validLayouts);
                    $currentLayoutId = $first->getId();
                }

                if ($currentLayoutId > 0) {
                    // check if user has sufficient rights
                    if ($validLayouts && $validLayouts[$currentLayoutId]) {
                        $customLayout = Object\ClassDefinition\CustomLayout::getById($currentLayoutId);
                        $customLayoutDefinition = $customLayout->getLayoutDefinitions();
                        $objectData["layout"] = $customLayoutDefinition;
                    } else {
                        $currentLayoutId = 0;
                    }
                } elseif ($currentLayoutId == -1 && $user->isAdmin()) {
                    $layout = Object\Service::getSuperLayoutDefinition($object);
                    $objectData["layout"] = $layout;
                }

                $objectData["currentLayoutId"] = $currentLayoutId;
            }

            $objectData = $this->filterLocalizedFields($object, $objectData);
            Object\Service::enrichLayoutDefinition($objectData["layout"], $object);


            //Hook for modifying return value - e.g. for changing permissions based on object data
            //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
            $returnValueContainer = new Model\Tool\Admin\EventDataContainer($objectData);
            \Pimcore::getEventManager()->trigger("admin.object.get.preSendData", $this, [
                "object" => $object,
                "returnValueContainer" => $returnValueContainer
            ]);

            $this->_helper->json($returnValueContainer->getData());
        } else {
            Logger::debug("prevented getting object id [ " . $object->getId() . " ] because of missing permissions");
            $this->_helper->json(["success" => false, "message" => "missing_permission"]);
        }
    }

    /**
     * @var
     */
    private $objectData;

    /**
     * @var
     */
    private $metaData;

    /**
     * @param Object\Concrete $object
     * @param bool $objectFromVersion
     */
    private function getDataForObject(Object\Concrete $object, $objectFromVersion = false)
    {
        foreach ($object->getClass()->getFieldDefinitions() as $key => $def) {
            $this->getDataForField($object, $key, $def, $objectFromVersion);
        }
    }

    /**
     * gets recursively attribute data from parent and fills objectData and metaData
     *
     * @param $object
     * @param $key
     * @param $fielddefinition
     * @param $objectFromVersion
     * @param int $level
     */
    private function getDataForField($object, $key, $fielddefinition, $objectFromVersion, $level = 0)
    {
        $parent = Object\Service::hasInheritableParentObject($object);
        $getter = "get" . ucfirst($key);

        // relations but not for objectsMetadata, because they have additional data which cannot be loaded directly from the DB
        // nonownerobjects should go in there anyway (regardless if it a version or not), so that the values can be loaded
        if (
            (!$objectFromVersion
                && $fielddefinition instanceof Object\ClassDefinition\Data\Relations\AbstractRelations
                && $fielddefinition->getLazyLoading()
                && !$fielddefinition instanceof Object\ClassDefinition\Data\ObjectsMetadata
                && !$fielddefinition instanceof Object\ClassDefinition\Data\MultihrefMetadata
            )
            || $fielddefinition instanceof Object\ClassDefinition\Data\Nonownerobjects
        ) {

            //lazy loading data is fetched from DB differently, so that not every relation object is instantiated
            $refId = null;

            if ($fielddefinition->isRemoteOwner()) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = Object\ClassDefinition::getByName($fielddefinition->getOwnerClassName());
                if ($refClass) {
                    $refId = $refClass->getId();
                }
            } else {
                $refKey = $key;
            }
            $relations = $object->getRelationData($refKey, !$fielddefinition->isRemoteOwner(), $refId);
            if (empty($relations) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $data = [];

                if ($fielddefinition instanceof Object\ClassDefinition\Data\Href) {
                    $data = $relations[0];
                } else {
                    foreach ($relations as $rel) {
                        if ($fielddefinition instanceof Object\ClassDefinition\Data\Objects) {
                            $data[] = [$rel["id"], $rel["path"], $rel["subtype"]];
                        } else {
                            $data[] = [$rel["id"], $rel["path"], $rel["type"], $rel["subtype"]];
                        }
                    }
                }
                $this->objectData[$key] = $data;
                $this->metaData[$key]['objectid'] = $object->getId();
                $this->metaData[$key]['inherited'] = $level != 0;
            }
        } else {
            $fieldData = $object->$getter();
            $isInheritedValue = false;

            if ($fielddefinition instanceof Object\ClassDefinition\Data\CalculatedValue) {
                $fieldData = new Object\Data\CalculatedValue($fielddefinition->getName());
                $fieldData->setContextualData("object", null, null, null);
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, $objectFromVersion);
            } else {
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, $objectFromVersion);
            }

            // following some exceptions for special data types (localizedfields, objectbricks)
            if ($value && ($fieldData instanceof Object\Localizedfield || $fieldData instanceof Object\Classificationstore)) {
                // make sure that the localized field participates in the inheritance detection process
                $isInheritedValue = $value["inherited"];
            }
            if ($fielddefinition instanceof Object\ClassDefinition\Data\Objectbricks && is_array($value)) {
                // make sure that the objectbricks participate in the inheritance detection process
                foreach ($value as $singleBrickData) {
                    if ($singleBrickData["inherited"]) {
                        $isInheritedValue = true;
                    }
                }
            }


            if ($fielddefinition->isEmpty($fieldData) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $isInheritedValue = $isInheritedValue || ($level != 0);
                $this->metaData[$key]['objectid'] = $object->getId();

                $this->objectData[$key] = $value;
                $this->metaData[$key]['inherited'] = $isInheritedValue;

                if ($isInheritedValue && !$fielddefinition->isEmpty($fieldData) && !$this->isInheritableField($fielddefinition)) {
                    $this->objectData[$key] = null;
                    $this->metaData[$key]['inherited'] = false;
                    $this->metaData[$key]['hasParentValue'] = true;
                }
            }
        }
    }

    /**
     * @param $object
     * @param $key
     * @return stdClass
     */
    private function getParentValue($object, $key)
    {
        $parent = Object\Service::hasInheritableParentObject($object);
        $getter = "get" . ucfirst($key);
        if ($parent) {
            $value = $parent->$getter();
            if ($value) {
                $result = new stdClass();
                $result->value = $value;
                $result->id = $parent->getId();

                return $result;
            } else {
                return $this->getParentValue($parent, $key);
            }
        }
    }

    /**
     * @param Object\ClassDefinition\Data $fielddefinition
     * @return bool
     */
    private function isInheritableField(Object\ClassDefinition\Data $fielddefinition)
    {
        if ($fielddefinition instanceof Object\ClassDefinition\Data\Fieldcollections
            //            || $fielddefinition instanceof Object\ClassDefinition\Data\Localizedfields
        ) {
            return false;
        }

        return true;
    }

    public function lockAction()
    {
        $object = Object::getById($this->getParam("id"));
        if ($object instanceof Object\AbstractObject) {
            $object->setLocked((bool)$this->getParam("locked"));
            //TODO: if latest version published - publish
            //if latest version not published just save new version
        }
    }

    /**
     * @param $layout
     * @param $allowedView
     * @param $allowedEdit
     */
    public function setLayoutPermission(&$layout, $allowedView, $allowedEdit)
    {
        if ($layout->{"fieldtype"} == "localizedfields") {
            if (is_array($allowedView) && count($allowedView) > 0) {
                $layout->{"permissionView"} = \Pimcore\Tool\Admin::reorderWebsiteLanguages(\Pimcore\Tool\Admin::getCurrentUser(), array_keys($allowedView), true);
            }
            if (is_array($allowedEdit) && count($allowedEdit) > 0) {
                $layout->{"permissionEdit"} = \Pimcore\Tool\Admin::reorderWebsiteLanguages(\Pimcore\Tool\Admin::getCurrentUser(), array_keys($allowedEdit), true);
            }
        } else {
            if (method_exists($layout, "getChilds")) {
                $children = $layout->getChilds();
                if (is_array($children)) {
                    foreach ($children as $child) {
                        $this->setLayoutPermission($child, $allowedView, $allowedEdit);
                    }
                }
            }
        }
    }

    /**
     * @param Object\AbstractObject $object
     * @param $objectData
     * @return mixed
     */
    public function filterLocalizedFields(Object\AbstractObject $object, $objectData)
    {
        if (!($object instanceof Object\Concrete)) {
            return $objectData;
        }

        $user = Tool\Admin::getCurrentUser();
        if ($user->getAdmin()) {
            return $objectData;
        }

        $fieldDefinitions = $object->getClass()->getFieldDefinitions();
        if ($fieldDefinitions) {
            $languageAllowedView = Object\Service::getLanguagePermissions($object, $user, "lView");
            $languageAllowedEdit = Object\Service::getLanguagePermissions($object, $user, "lEdit");

            foreach ($fieldDefinitions as $key => $fd) {
                if ($fd->getFieldtype() == "localizedfields") {
                    foreach ($objectData["data"][$key]["data"] as $language => $languageData) {
                        if (!is_null($languageAllowedView) && !$languageAllowedView[$language]) {
                            unset($objectData["data"][$key]["data"][$language]);
                        }
                    }
                }
            }
            $this->setLayoutPermission($objectData["layout"], $languageAllowedView, $languageAllowedEdit);
        }

        return $objectData;
    }

    public function getFolderAction()
    {
        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "object")) {
            $this->_helper->json([
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "object")
            ]);
        }
        Element\Editlock::lock($this->getParam("id"), "object");

        $object = Object::getById(intval($this->getParam("id")));
        if ($object->isAllowed("view")) {
            $objectData = [];

            $objectData["general"] = [];
            $objectData["idPath"] = Element\Service::getIdPath($object);
            $allowedKeys = ["o_published", "o_key", "o_id", "o_type", "o_path", "o_modificationDate", "o_creationDate", "o_userOwner", "o_userModification"];
            foreach (get_object_vars($object) as $key => $value) {
                if (strstr($key, "o_") && in_array($key, $allowedKeys)) {
                    $objectData["general"][$key] = $value;
                }
            }
            $objectData["general"]["fullpath"] = $object->getRealFullPath();

            $objectData["general"]["o_locked"] = $object->isLocked();

            $objectData["properties"] = Element\Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData["userPermissions"] = $object->getUserPermissions();
            $objectData["classes"] = $this->prepareChildClasses($object->getDao()->getClasses());

            // grid-config
            $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . "-user_" . $this->getUser()->getId() . ".psf";
            if (is_file($configFile)) {
                $gridConfig = Tool\Serialize::unserialize(file_get_contents($configFile));
                if ($gridConfig) {
                    $selectedClassId = $gridConfig["classId"];

                    foreach ($objectData["classes"] as $class) {
                        if ($class["id"] == $selectedClassId) {
                            $objectData["selectedClass"] = $selectedClassId;
                            break;
                        }
                    }
                }
            }

            $this->_helper->json($objectData);
        } else {
            Logger::debug("prevented getting folder id [ " . $object->getId() . " ] because of missing permissions");
            $this->_helper->json(["success" => false, "message" => "missing_permission"]);
        }
    }

    /**
     * @param $classes
     * @return array
     */
    protected function prepareChildClasses($classes)
    {
        $reduced = [];
        foreach ($classes as $class) {
            $reduced[] = [
                "id" => $class->getId(),
                "name" => $class->getName()
            ];
        }

        return $reduced;
    }

    public function addAction()
    {
        $success = false;

        $className = "Pimcore\\Model\\Object\\" . ucfirst($this->getParam("className"));
        $parent = Object::getById($this->getParam("parentId"));

        $message = "";
        if ($parent->isAllowed("create")) {
            $intendedPath = $parent->getRealFullPath() . "/" . $this->getParam("key");

            if (!Object\Service::pathExists($intendedPath)) {
                $object = \Pimcore::getDiContainer()->make($className);
                if ($object instanceof Object\Concrete) {
                    $object->setOmitMandatoryCheck(true); // allow to save the object although there are mandatory fields
                }

                if ($this->getParam("variantViaTree")) {
                    $parentId = $this->getParam("parentId");
                    $parent = Object::getById($parentId);
                    $object->setClassId($parent->getClass()->getId());
                } else {
                    $object->setClassId($this->getParam("classId"));
                }

                $object->setClassName($this->getParam("className"));
                $object->setParentId($this->getParam("parentId"));
                $object->setKey($this->getParam("key"));
                $object->setCreationDate(time());
                $object->setUserOwner($this->getUser()->getId());
                $object->setUserModification($this->getUser()->getId());
                $object->setPublished(false);

                if ($this->getParam("objecttype") == Object\AbstractObject::OBJECT_TYPE_OBJECT
                    || $this->getParam("objecttype") == Object\AbstractObject::OBJECT_TYPE_VARIANT) {
                    $object->setType($this->getParam("objecttype"));
                }

                try {
                    $object->save();
                    $success = true;
                } catch (\Exception $e) {
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                $message = "prevented creating object because object with same path+key already exists";
                Logger::debug($message);
            }
        } else {
            $message = "prevented adding object because of missing permissions";
            Logger::debug($message);
        }

        if ($success) {
            $this->_helper->json([
                "success" => $success,
                "id" => $object->getId(),
                "type" => $object->getType(),
                "message" => $message
            ]);
        } else {
            $this->_helper->json([
                "success" => $success,
                "message" => $message
            ]);
        }
    }

    public function addFolderAction()
    {
        $success = false;

        $parent = Object::getById($this->getParam("parentId"));
        if ($parent->isAllowed("create")) {
            if (!Object\Service::pathExists($parent->getRealFullPath() . "/" . $this->getParam("key"))) {
                $folder = Object\Folder::create([
                    "o_parentId" => $this->getParam("parentId"),
                    "o_creationDate" => time(),
                    "o_userOwner" => $this->user->getId(),
                    "o_userModification" => $this->user->getId(),
                    "o_key" => $this->getParam("key"),
                    "o_published" => true
                ]);

                $folder->setCreationDate(time());
                $folder->setUserOwner($this->getUser()->getId());
                $folder->setUserModification($this->getUser()->getId());

                try {
                    $folder->save();
                    $success = true;
                } catch (\Exception $e) {
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            }
        } else {
            Logger::debug("prevented creating object id because of missing permissions");
        }

        $this->_helper->json(["success" => $success]);
    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {
            $parentObject = Object::getById($this->getParam("id"));

            $list = new Object\Listing();
            $list->setCondition("o_path LIKE '" . $parentObject->getRealFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(o_path)", false);
            $list->setOrder("DESC");

            $objects = $list->load();

            $deletedItems = [];
            foreach ($objects as $object) {
                $deletedItems[] = $object->getRealFullPath();
                if ($object->isAllowed("delete")) {
                    $object->delete();
                }
            }

            $this->_helper->json(["success" => true, "deleted" => $deletedItems]);
        } elseif ($this->getParam("id")) {
            $object = Object::getById($this->getParam("id"));
            if ($object) {
                if (!$object->isAllowed("delete")) {
                    $this->_helper->json(["success" => false, "message" => "missing_permission"]);
                } else {
                    $object->delete();
                }
            }

            // return true, even when the object doesn't exist, this can be the case when using batch delete incl. children
            $this->_helper->json(["success" => true]);
        }
    }

    public function deleteInfoAction()
    {
        $hasDependency = false;
        $deleteJobs = [];
        $recycleJobs = [];

        $totalChilds = 0;

        $ids = $this->getParam("id");
        $ids = explode(',', $ids);

        foreach ($ids as $id) {
            try {
                $object = Object::getById($id);
                if (!$object) {
                    continue;
                }
                $hasDependency |= $object->getDependencies()->isRequired();
            } catch (\Exception $e) {
                Logger::err("failed to access object with id: " . $id);
                continue;
            }


            // check for children
            if ($object instanceof Object\AbstractObject) {
                $recycleJobs[] = [[
                    "url" => "/admin/recyclebin/add",
                    "params" => [
                        "type" => "object",
                        "id" => $object->getId()
                    ]
                ]];

                $hasChilds = $object->hasChilds();
                if (!$hasDependency) {
                    $hasDependency = $hasChilds;
                }

                $childs = 0;
                if ($hasChilds) {
                    // get amount of childs
                    $list = new Object\Listing();
                    $list->setCondition("o_path LIKE '" . $object->getRealFullPath() . "/%'");
                    $childs = $list->getTotalCount();

                    $totalChilds += $childs;
                    if ($childs > 0) {
                        $deleteObjectsPerRequest = 5;
                        for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                            $deleteJobs[] = [[
                                "url" => "/admin/object/delete",
                                "params" => [
                                    "step" => $i,
                                    "amount" => $deleteObjectsPerRequest,
                                    "type" => "childs",
                                    "id" => $object->getId()
                                ]
                            ]];
                        }
                    }
                }

                // the object itself is the last one
                $deleteJobs[] = [[
                    "url" => "/admin/object/delete",
                    "params" => [
                        "id" => $object->getId()
                    ]
                ]];
            }
        }

        // get the element key in case of just one
        $elementKey = false;
        if (count($ids) === 1) {
            $elementKey = Object::getById($id)->getKey();
        }

        $deleteJobs = array_merge($recycleJobs, $deleteJobs);
        $this->_helper->json([
            "hasDependencies" => $hasDependency,
            "childs" => $totalChilds,
            "deletejobs" => $deleteJobs,
            "batchDelete" => count($ids) > 1,
            "elementKey" => $elementKey
        ]);
    }


    public function updateAction()
    {
        $success = false;
        $allowUpdate = true;

        $object = Object::getById($this->getParam("id"));
        if ($object instanceof Object\Concrete) {
            $object->setOmitMandatoryCheck(true);
        }

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($object instanceof Object\Concrete) {
            $latestVersion = $object->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $object->getModificationDate()) {
                $this->_helper->json(["success" => false, "message" => "You can't relocate if there's a newer not published version"]);
            }
        }


        $values = \Zend_Json::decode($this->getParam("values"));

        if ($object->isAllowed("settings")) {
            if ($values["key"] && $object->isAllowed("rename")) {
                $object->setKey($values["key"]);
            } elseif ($values["key"] != $object->getKey()) {
                Logger::debug("prevented renaming object because of missing permissions ");
            }

            if ($values["parentId"]) {
                $parent = Object::getById($values["parentId"]);

                //check if parent is changed
                if ($object->getParentId() != $parent->getId()) {
                    if (!$parent->isAllowed("create")) {
                        throw new \Exception("Prevented moving object - no create permission on new parent ");
                    }

                    $objectWithSamePath = Object::getByPath($parent->getRealFullPath() . "/" . $object->getKey());

                    if ($objectWithSamePath != null) {
                        $allowUpdate = false;
                        $this->_helper->json(["success" => false, "message" => "prevented creating object because object with same path+key already exists"]);
                    }

                    if ($object->isLocked()) {
                        $this->_helper->json(["success" => false, "message" => "prevented moving object, because it is locked: ID: " . $object->getId()]);
                    }

                    $object->setParentId($values["parentId"]);
                }
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
                } catch (\Exception $e) {
                    Logger::error($e);
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                Logger::debug("prevented move of object, object with same path+key already exists in this location.");
            }
        } elseif ($object->isAllowed("rename") && $values["key"]) {
            //just rename
            try {
                $object->setKey($values["key"]);
                $object->save();
                $success = true;
            } catch (\Exception $e) {
                Logger::error($e);
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        } else {
            Logger::debug("prevented update object because of missing permissions.");
        }

        $this->_helper->json(["success" => $success]);
    }


    public function saveAction()
    {
        try {
            $object = Object::getById($this->getParam("id"));
            $originalModificationDate = $object->getModificationDate();

            // set the latest available version for editmode
            $object = $this->getLatestVersion($object);
            $object->setUserModification($this->getUser()->getId());

            // data
            if ($this->getParam("data")) {
                $data = \Zend_Json::decode($this->getParam("data"));
                foreach ($data as $key => $value) {
                    $fd = $object->getClass()->getFieldDefinition($key);
                    if ($fd) {
                        if ($fd instanceof Object\ClassDefinition\Data\Localizedfields) {
                            $user = Tool\Admin::getCurrentUser();
                            if (!$user->getAdmin()) {
                                $allowedLanguages = Object\Service::getLanguagePermissions($object, $user, "lEdit");
                                if (!is_null($allowedLanguages)) {
                                    $allowedLanguages = array_keys($allowedLanguages);
                                    $submittedLanguages = array_keys($data[$key]);
                                    foreach ($submittedLanguages as $submittedLanguage) {
                                        if (!in_array($submittedLanguage, $allowedLanguages)) {
                                            unset($value[$submittedLanguage]);
                                        }
                                    }
                                }
                            }
                        }

                        if (method_exists($fd, "isRemoteOwner") and $fd->isRemoteOwner()) {
                            $remoteClass = Object\ClassDefinition::getByName($fd->getOwnerClassName());
                            $relations = $object->getRelationData($fd->getOwnerFieldName(), false, $remoteClass->getId());
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
                $general = \Zend_Json::decode($this->getParam("general"));

                // do not allow all values to be set, will cause problems (eg. icon)
                if (is_array($general) && count($general) > 0) {
                    foreach ($general as $key => $value) {
                        if (!in_array($key, ["o_id", "o_classId", "o_className", "o_type", "icon", "o_userOwner", "o_userModification"])) {
                            $object->setValue($key, $value);
                        }
                    }
                }
            }

            $object = $this->assignPropertiesFromEditmode($object);


            // scheduled tasks
            if ($this->getParam("scheduler")) {
                $tasks = [];
                $tasksData = \Zend_Json::decode($this->getParam("scheduler"));

                if (!empty($tasksData)) {
                    foreach ($tasksData as $taskData) {
                        $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                        $task = new Model\Schedule\Task($taskData);
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
            if ($this->getParam("task") == "unpublish" || $this->getParam("task") == "version") {
                $object->setOmitMandatoryCheck(true);
            }

            if (($this->getParam("task") == "publish" && $object->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $object->isAllowed("unpublish"))) {
                if ($data) {
                    $this->performFieldcollectionModificationCheck($object, $originalModificationDate, $data);
                }

                $object->save();
                $treeData = $this->getTreeNodeConfig($object);

                $this->_helper->json([
                    "success" => true,
                    "general" => ["o_modificationDate" => $object->getModificationDate()],
                    "treeData" => $treeData]);
            } elseif ($this->getParam("task") == "session") {

                //$object->_fulldump = true; // not working yet, donno why

                Tool\Session::useSession(function ($session) use ($object) {
                    $key = "object_" . $object->getId();
                    $session->$key = $object;
                }, "pimcore_objects");

                $this->_helper->json(["success" => true]);
            } else {
                if ($object->isAllowed("save")) {
                    $object->saveVersion();
                    $treeData = $this->getTreeNodeConfig($object);

                    $this->_helper->json([
                        "success" => true,
                        "general" => ["o_modificationDate" => $object->getModificationDate()],
                        "treeData" => $treeData]);
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if (Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                $this->_helper->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }
    }

    /**
     * @param Object\Concrete $object
     * @param $originalModificationDate
     * @param $data
     */
    public function performFieldcollectionModificationCheck(Object\Concrete $object, $originalModificationDate, $data)
    {
        $modificationDate = $this->getParam("modificationDate");
        if ($modificationDate != $originalModificationDate) {
            $fielddefinitions = $object->getClass()->getFieldDefinitions();
            foreach ($fielddefinitions as $fd) {
                if ($fd instanceof Object\ClassDefinition\Data\Fieldcollections) {
                    if (isset($data[$fd->getName()])) {
                        $allowedTypes = $fd->getAllowedTypes();
                        foreach ($allowedTypes as $type) {
                            /** @var  $fdDef Object\Fieldcollection\Definition */
                            $fdDef = Object\Fieldcollection\Definition::getByKey($type);
                            $childDefinitions = $fdDef->getFieldDefinitions();
                            foreach ($childDefinitions as $childDef) {
                                if ($childDef instanceof Object\ClassDefinition\Data\Localizedfields) {
                                    $this->_helper->json(["success" => false, "message" => "Could be that someone messed around with the fieldcollection in the meantime. Please reload and try again"]);
                                    ;
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function saveFolderAction()
    {
        $object = Object::getById($this->getParam("id"));

        if ($object->isAllowed("publish")) {
            try {
                $classId = $this->getParam("class_id");

                // general settings
                $general = \Zend_Json::decode($this->getParam("general"));
                $object->setValues($general);
                $object->setUserModification($this->getUser()->getId());

                $object = $this->assignPropertiesFromEditmode($object);

                $object->save();
                $this->_helper->json(["success" => true]);
            } catch (\Exception $e) {
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    /**
     * @param $object
     * @return mixed
     */
    protected function assignPropertiesFromEditmode($object)
    {
        if ($this->getParam("properties")) {
            $properties = [];
            // assign inherited properties
            foreach ($object->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = \Zend_Json::decode($this->getParam("properties"));

            if (is_array($propertiesData)) {
                foreach ($propertiesData as $propertyName => $propertyData) {
                    $value = $propertyData["data"];


                    try {
                        $property = new Model\Property();
                        $property->setType($propertyData["type"]);
                        $property->setName($propertyName);
                        $property->setCtype("object");
                        $property->setDataFromEditmode($value);
                        $property->setInheritable($propertyData["inheritable"]);

                        $properties[$propertyName] = $property;
                    } catch (\Exception $e) {
                        Logger::err("Can't add " . $propertyName . " to object " . $object->getRealFullPath());
                    }
                }
            }
            $object->setProperties($properties);
        }

        return $object;
    }

    public function publishVersionAction()
    {
        $version = Model\Version::getById($this->getParam("id"));
        $object = $version->loadData();

        $currentObject = Object::getById($object->getId());
        if ($currentObject->isAllowed("publish")) {
            $object->setPublished(true);
            $object->setUserModification($this->getUser()->getId());
            try {
                $object->save();
                $treeData = [];
                $treeData["qtipCfg"] = $object->getElementAdminStyle()->getElementQtipConfig();
                $this->_helper->json([
                        "success" => true,
                        "general" => ["o_modificationDate" => $object->getModificationDate() ],
                        "treeData" => $treeData]
                );
            } catch (\Exception $e) {
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    public function previewVersionAction()
    {
        Pimcore\Model\Object\AbstractObject::setDoNotRestoreKeyAndPath(true);

        $id = intval($this->getParam("id"));
        $version = Model\Version::getById($id);
        $object = $version->loadData();

        Pimcore\Model\Object\AbstractObject::setDoNotRestoreKeyAndPath(false);

        if ($object) {
            if ($object->isAllowed("versions")) {
                $this->view->object = $object;
            } else {
                throw new \Exception("Permission denied, version id [" . $id . "]");
            }
        } else {
            throw new \Exception("Version with id [" . $id . "] doesn't exist");
        }
    }

    public function diffVersionsAction()
    {
        Pimcore\Model\Object\AbstractObject::setDoNotRestoreKeyAndPath(true);

        $id1 = intval($this->getParam("from"));
        $id2 = intval($this->getParam("to"));

        $version1 = Model\Version::getById($id1);
        $object1 = $version1->loadData();

        $version2 = Model\Version::getById($id2);
        $object2 = $version2->loadData();

        Pimcore\Model\Object\AbstractObject::setDoNotRestoreKeyAndPath(false);

        if ($object1 && $object2) {
            if ($object1->isAllowed("versions") && $object2->isAllowed("versions")) {
                $this->view->object1 = $object1;
                $this->view->object2 = $object2;
            } else {
                throw new \Exception("Permission denied, version ids [" . $id1 . ", " . $id2 . "]");
            }
        } else {
            throw new \Exception("Version with ids [" . $id1 . ", " . $id2 . "] doesn't exist");
        }
    }

    public function gridProxyAction()
    {
        $requestedLanguage = $this->getParam("language");
        if ($requestedLanguage) {
            if ($requestedLanguage != "default") {
                $this->setLanguage($requestedLanguage, true);
            }
        } else {
            $requestedLanguage = $this->getLanguage();
        }

        if ($this->getParam("data")) {
            if ($this->getParam("xaction") == "update") {
                try {
                    $data = \Zend_Json::decode($this->getParam("data"));

                    // save
                    $object = Object::getById($data["id"]);
                    /** @var Object\ClassDefinition $class */
                    $class = $object->getClass();

                    if (!$object->isAllowed("publish")) {
                        throw new \Exception("Permission denied. You don't have the rights to save this object.");
                    }

                    $user = Tool\Admin::getCurrentUser();
                    $allLanguagesAllowed = false;
                    if (!$user->isAdmin()) {
                        $languagePermissions = $object->getPermissions("lEdit", $user);

                        //sets allowed all languages modification when the lEdit column is empty
                        $allLanguagesAllowed = $languagePermissions["lEdit"] == '';

                        $languagePermissions = explode(",", $languagePermissions["lEdit"]);
                    }

                    $objectData = [];
                    foreach ($data as $key => $value) {
                        $parts = explode("~", $key);
                        if (substr($key, 0, 1) == "~") {
                            $type = $parts[1];
                            $field = $parts[2];
                            $keyid = $parts[3];

                            if ($type == "classificationstore") {
                                $groupKeyId = explode("-", $keyid);
                                $groupId = $groupKeyId[0];
                                $keyid = $groupKeyId[1];

                                $getter = "get" . ucfirst($field);
                                if (method_exists($object, $getter)) {
                                    /** @var  $classificationStoreData Object\Classificationstore */
                                    $classificationStoreData = $object->$getter();
                                    $classificationStoreData->setLocalizedKeyValue($groupId, $keyid, $value, $requestedLanguage);
                                }
                            } else {
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
                            }
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
                            if (!$user->isAdmin() && $languagePermissions) {
                                $fd = $class->getFieldDefinition($key);
                                if (!$fd) {
                                    // try to get via localized fields
                                    $localized = $class->getFieldDefinition("localizedfields");
                                    if ($localized instanceof Object\ClassDefinition\Data\Localizedfields) {
                                        $field = $localized->getFieldDefinition($key);
                                        if ($field) {
                                            $currentLocale = (string) \Zend_Registry::get("Zend_Locale");
                                            if (!$allLanguagesAllowed && !in_array($currentLocale, $languagePermissions)) {
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }

                            $objectData[$key] = $value;
                        }
                    }

                    $object->setValues($objectData);


                    $object->save();
                    $this->_helper->json(["data" => Object\Service::gridObjectData($object, $this->getParam("fields"), $requestedLanguage), "success" => true]);
                } catch (\Exception $e) {
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            }
        } else {
            // get list of objects
            $folder = Object::getById($this->getParam("folderId"));
            $class = Object\ClassDefinition::getById($this->getParam("classId"));
            $className = $class->getName();

            $colMappings = [
                "filename" => "o_key",
                "fullpath" => ["o_path", "o_key"],
                "id" => "oo_id",
                "published" => "o_published",
                "modificationDate" => "o_modificationDate",
                "creationDate" => "o_creationDate"
            ];

            $start = 0;
            $limit = 20;
            $orderKey = "oo_id";
            $order = "ASC";

            $fields = [];
            $bricks = [];
            if ($this->getParam("fields")) {
                $fields = $this->getParam("fields");

                foreach ($fields as $f) {
                    $parts = explode("~", $f);
                    $sub = substr($f, 0, 1);
                    if (substr($f, 0, 1) == "~") {
                        $type = $parts[1];
                        //                        $field = $parts[2];
                        //                        $keyid = $parts[3];
                        // key value, ignore for now
                        if ($type == "classificationstore") {
                        }
                    } elseif (count($parts) > 1) {
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


            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());

            $doNotQuote = false;

            if ($sortingSettings['order']) {
                $order = $sortingSettings['order'];
            }
            if (strlen($sortingSettings['orderKey']) > 0) {
                $orderKey = $sortingSettings['orderKey'];
                if (!(substr($orderKey, 0, 1) == "~")) {
                    if (array_key_exists($orderKey, $colMappings)) {
                        $orderKey = $colMappings[$orderKey];
                    } elseif ($class->getFieldDefinition($orderKey) instanceof  Object\ClassDefinition\Data\QuantityValue) {
                        $orderKey = "concat(" . $orderKey . "__unit, " . $orderKey . "__value)";
                        $doNotQuote = true;
                    } elseif (strpos($orderKey, "~") !== false) {
                        $orderKeyParts = explode("~", $orderKey);
                        if (count($orderKeyParts) == 2) {
                            $orderKey = $orderKeyParts[1];
                        }
                    }
                }
            }

            $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

            $conditionFilters = [];
            if ($this->getParam("only_direct_children") == "true") {
                $conditionFilters[] = "o_parentId = " . $folder->getId();
            } else {
                $conditionFilters[] = "(o_path = '" . $folder->getRealFullPath() . "' OR o_path LIKE '" . str_replace("//", "/", $folder->getRealFullPath() . "/") . "%')";
            }

            if (!$this->getUser()->isAdmin()) {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $conditionFilters[] .= " (
                                                    (select list from users_workspaces_object where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_object where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )";
            }



            $featureJoins = [];
            $featureFilters = false;

            // create filter condition
            if ($this->getParam("filter")) {
                $conditionFilters[] = Object\Service::getFilterCondition($this->getParam("filter"), $class);
                $featureFilters = Object\Service::getFeatureFilters($this->getParam("filter"), $class);
                if ($featureFilters) {
                    $featureJoins = array_merge($featureJoins, $featureFilters["joins"]);
                }
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


            if (isset($sortingSettings["isFeature"]) && $sortingSettings["isFeature"]) {
                $orderKey = "cskey_" . $sortingSettings["fieldname"] . "_" . $sortingSettings["groupId"]. "_" . $sortingSettings["keyId"]."`.`value";
                $list->setOrderKey($orderKey);
                $list->setGroupBy("object_".$this->getParam("classId").".o_id");

                $featureJoins[] = $sortingSettings;
            } else {
                $list->setOrderKey($orderKey, !$doNotQuote);
            }
            $list->setOrder($order);

            if ($class->getShowVariants()) {
                $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_VARIANT]);
            }

            Object\Service::addGridFeatureJoins($list, $featureJoins, $class, $featureFilters, $requestedLanguage);

            $list->load();

            $objects = [];
            foreach ($list->getObjects() as $object) {
                $o = Object\Service::gridObjectData($object, $fields, $requestedLanguage);
                $objects[] = $o;
            }
            $this->_helper->json(["data" => $objects, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    public function copyInfoAction()
    {
        $transactionId = time();
        $pasteJobs = [];

        Tool\Session::useSession(function ($session) use ($transactionId) {
            $session->$transactionId = ["idMapping" => []];
        }, "pimcore_copy");

        if ($this->getParam("type") == "recursive" || $this->getParam("type") == "recursive-update-references") {
            $object = Object::getById($this->getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = [[
                "url" => "/admin/object/copy",
                "params" => [
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => "child",
                    "transactionId" => $transactionId,
                    "saveParentId" => true
                ]
            ]];

            if ($object->hasChilds([Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER, Object\AbstractObject::OBJECT_TYPE_VARIANT])) {
                // get amount of childs
                $list = new Object\Listing();
                $list->setCondition("o_path LIKE '" . $object->getRealFullPath() . "/%'");
                $list->setOrderKey("LENGTH(o_path)", false);
                $list->setOrder("ASC");
                $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER, Object\AbstractObject::OBJECT_TYPE_VARIANT]);
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            "url" => "/admin/object/copy",
                            "params" => [
                                "sourceId" => $id,
                                "targetParentId" => $this->getParam("targetId"),
                                "sourceParentId" => $this->getParam("sourceId"),
                                "type" => "child",
                                "transactionId" => $transactionId
                            ]
                        ]];
                    }
                }
            }

            // add id-rewrite steps
            if ($this->getParam("type") == "recursive-update-references") {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        "url" => "/admin/object/copy-rewrite-ids",
                        "params" => [
                            "transactionId" => $transactionId,
                            "_dc" => uniqid()
                        ]
                    ]];
                }
            }
        } elseif ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = [[
                "url" => "/admin/object/copy",
                "params" => [
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => $this->getParam("type"),
                    "transactionId" => $transactionId
                ]
            ]];
        }


        $this->_helper->json([
            "pastejobs" => $pasteJobs
        ]);
    }

    public function copyRewriteIdsAction()
    {
        $transactionId = $this->getParam("transactionId");

        $idStore = Tool\Session::useSession(function ($session) use ($transactionId) {
            return $session->$transactionId;
        }, "pimcore_copy");

        if (!array_key_exists("rewrite-stack", $idStore)) {
            $idStore["rewrite-stack"] = array_values($idStore["idMapping"]);
        }

        $id = array_shift($idStore["rewrite-stack"]);
        $object = Object::getById($id);

        // create rewriteIds() config parameter
        $rewriteConfig = ["object" => $idStore["idMapping"]];

        $object = Object\Service::rewriteIds($object, $rewriteConfig);

        $object->setUserModification($this->getUser()->getId());
        $object->save();


        // write the store back to the session
        Tool\Session::useSession(function ($session) use ($transactionId, $idStore) {
            $session->$transactionId = $idStore;
        }, "pimcore_copy");

        $this->_helper->json([
            "success" => true,
            "id" => $id
        ]);
    }

    public function copyAction()
    {
        $success = false;
        $message = "";
        $sourceId = intval($this->getParam("sourceId"));
        $source = Object::getById($sourceId);
        $session = Tool\Session::get("pimcore_copy");

        $targetId = intval($this->getParam("targetId"));
        if ($this->getParam("targetParentId")) {
            $sourceParent = Object::getById($this->getParam("sourceParentId"));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($session->{$this->getParam("transactionId")}["parentId"]) {
                $targetParent = Object::getById($session->{$this->getParam("transactionId")}["parentId"]);
            } else {
                $targetParent = Object::getById($this->getParam("targetParentId"));
            }

            $targetPath = preg_replace("@^" . $sourceParent->getRealFullPath() . "@", $targetParent . "/", $source->getRealPath());
            $target = Object::getByPath($targetPath);
        } else {
            $target = Object::getById($targetId);
        }

        if ($target->isAllowed("create")) {
            $source = Object::getById($sourceId);
            if ($source != null) {
                try {
                    if ($this->getParam("type") == "child") {
                        $newObject = $this->_objectService->copyAsChild($target, $source);

                        $session->{$this->getParam("transactionId")}["idMapping"][(int)$source->getId()] = (int)$newObject->getId();

                        // this is because the key can get the prefix "_copy" if the target does already exists
                        if ($this->getParam("saveParentId")) {
                            $session->{$this->getParam("transactionId")}["parentId"] = $newObject->getId();
                            Tool\Session::writeClose();
                        }
                    } elseif ($this->getParam("type") == "replace") {
                        $this->_objectService->copyContents($target, $source);
                    }

                    $success = true;
                } catch (\Exception $e) {
                    Logger::err($e);
                    $success = false;
                    $message = $e->getMessage() . " in object " . $source->getRealFullPath() . " [id: " . $source->getId() . "]";
                }
            } else {
                Logger::error("could not execute copy/paste, source object with id [ $sourceId ] not found");
                $this->_helper->json(["success" => false, "message" => "source object not found"]);
            }
        } else {
            Logger::error("could not execute copy/paste because of missing permissions on target [ " . $targetId . " ]");
            $this->_helper->json(["error" => false, "message" => "missing_permission"]);
        }

        $this->_helper->json(["success" => $success, "message" => $message]);
    }


    public function previewAction()
    {
        $id = $this->getParam("id");
        $key = "object_" . $id;

        $session = Tool\Session::getReadOnly("pimcore_objects");
        if ($session->$key) {
            $object = $session->$key;
        } else {
            die("Preview not available, it seems that there's a problem with this object.");
        }

        $url = $object->getClass()->getPreviewUrl();

        // replace named variables
        $vars = get_object_vars($object);
        foreach ($vars as $key => $value) {
            if (!empty($value) && (is_string($value) || is_numeric($value))) {
                $url = str_replace("%" . $key, urlencode($value), $url);
            } else {
                if (strpos($url, "%" . $key) !== false) {
                    die("No preview available, please ensure that all fields which are required for the preview are filled correctly.");
                }
            }
        }

        // replace all remainaing % signs
        $url = str_replace("%", "%25", $url);

        $urlParts = parse_url($url);
        $this->redirect($urlParts["path"] . "?pimcore_object_preview=" . $id . "&_dc=" . time() . "&" . $urlParts["query"]);
    }

    /**
     * @param  Object\Concrete $object
     * @param  array $toDelete
     * @param  array $toAdd
     * @param  string $ownerFieldName
     */
    protected function processRemoteOwnerRelations($object, $toDelete, $toAdd, $ownerFieldName)
    {
        $getter = "get" . ucfirst($ownerFieldName);
        $setter = "set" . ucfirst($ownerFieldName);

        foreach ($toDelete as $id) {
            $owner = Object::getById($id);
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
            $owner = Object::getById($id);
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
        $originals = [];
        $changed = [];
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
        $originals = [];
        $changed = [];
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
     * @param  Object\Concrete $object
     * @return Object\Concrete
     */
    protected function getLatestVersion(Object\Concrete $object)
    {
        $modificationDate = $object->getModificationDate();
        $latestVersion = $object->getLatestVersion();
        if ($latestVersion) {
            $latestObj = $latestVersion->loadData();
            if ($latestObj instanceof Object\Concrete) {
                $object = $latestObj;
                $object->setModificationDate($modificationDate); // set de modification-date from published version to compare it in js-frontend
            }
        }

        return $object;
    }
}
