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

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;
use Pimcore\Logger;

class Admin_ClassController extends \Pimcore\Controller\Action\Admin
{
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = ["get-tree", "fieldcollection-list", "fieldcollection-tree", "fieldcollection-get", "get-class-definition-for-column-config", "objectbrick-list", "objectbrick-tree", "objectbrick-get"];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("classes");
        }
    }

    public function getDocumentTypesAction()
    {
        $documentTypes = Document::getTypes();
        $typeItems = [];
        foreach ($documentTypes as $documentType) {
            $typeItems[] = [
                "text" => $documentType
            ];
        }
        $this->_helper->json($typeItems);
    }

    public function getAssetTypesAction()
    {
        $assetTypes = Asset::getTypes();
        $typeItems = [];
        foreach ($assetTypes as $assetType) {
            $typeItems[] = [
                "text" => $assetType
            ];
        }
        $this->_helper->json($typeItems);
    }

    public function getTreeAction()
    {
        $defaultIcon = "/pimcore/static6/img/flat-color-icons/timeline.svg";
        if (!\Pimcore\Tool\Admin::isExtJS6()) {
            $defaultIcon = '/pimcore/static6/img/icon/database_gear.png';
        }


        $classesList = new Object\ClassDefinition\Listing();
        $classesList->setOrderKey("name");
        $classesList->setOrder("asc");
        $classes = $classesList->load();

        // filter classes
        if ($this->getParam("createAllowed")) {
            $tmpClasses = [];
            foreach ($classes as $class) {
                if ($this->getUser()->isAllowed($class->getId(), "class")) {
                    $tmpClasses[] = $class;
                }
            }
            $classes = $tmpClasses;
        }

        $getClassConfig = function ($class) use ($defaultIcon) {
            return [
                "id" => $class->getId(),
                "text" => $class->getName(),
                "leaf" => true,
                "icon" => $class->getIcon() ? $class->getIcon() : $defaultIcon,
                "propertyVisibility" => $class->getPropertyVisibility(),
                "qtipCfg" => [
                    "title" => "ID: " . $class->getId()
                ]
            ];
        };

        // build groups
        $groups = [];
        foreach ($classes as $class) {
            if ($class->getGroup()) {
                $type = "manual";
                $groupName = $class->getGroup();
            } else {
                $type = "auto";
                preg_match("@^([A-Za-z])([^A-Z]+)@", $class->getName(), $matches);
                $groupName = $matches[0];
            }

            $groupName = \Pimcore\Model\Translation\Admin::getByKeyLocalized($groupName, true, true);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    "classes" => [],
                    "type" => $type
                ];
            }
            $groups[$groupName]["classes"][] = $class;
        }

        $treeNodes = [];

        if (!$this->getParam('grouped')) {
            // list output
            foreach ($groups as $groupName => $groupData) {
                foreach ($groupData["classes"] as $class) {
                    $node = $getClassConfig($class);
                    if (count($groupData["classes"]) > 1 || $groupData["type"] == "manual") {
                        $node["group"] = $groupName;
                    }
                    $treeNodes[] = $node;
                }
            }
        } else {
            // create json output
            foreach ($groups as $groupName => $groupData) {
                if (count($groupData["classes"]) === 1 && $groupData["type"] == "auto") {
                    // no group, only one child
                    $node = $getClassConfig($groupData["classes"][0]);
                } else {
                    // group classes
                    $node = [
                        "id" => "folder_" . $groupName,
                        "text" => $groupName,
                        "leaf" => false,
                        'expandable' => true,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        "children" => []
                    ];

                    foreach ($groupData["classes"] as $class) {
                        $node['children'][] = $getClassConfig($class);
                    }
                }

                $treeNodes[] = $node;
            }
        }

        $this->_helper->json($treeNodes);
    }

    public function getAction()
    {
        $class = Object\ClassDefinition::getById(intval($this->getParam("id")));
        $class->setFieldDefinitions(null);

        $this->_helper->json($class);
    }

    public function getCustomLayoutAction()
    {
        $customLayout = Object\ClassDefinition\CustomLayout::getById(intval($this->getParam("id")));

        $this->_helper->json(["success" => true, "data" => $customLayout]);
    }

    public function addAction()
    {
        $class = Object\ClassDefinition::create(['name' => $this->correctClassname($this->getParam("name")),
                'userOwner' => $this->user->getId()]
        );

        $class->save();

        $this->_helper->json(["success" => true, "id" => $class->getId()]);
    }

    public function addCustomLayoutAction()
    {
        $customLayout = Object\ClassDefinition\CustomLayout::create(['name' => $this->getParam("name"),
                'userOwner' => $this->user->getId(),
                "classId" => $this->getParam("classId")]
        );

        $customLayout->save();

        $this->_helper->json(["success" => true, "id" => $customLayout->getId(), "name" => $customLayout->getName(),
            "data" => $customLayout]);
    }



    public function deleteAction()
    {
        $class = Object\ClassDefinition::getById(intval($this->getParam("id")));
        $class->delete();

        $this->removeViewRenderer();
    }

    public function deleteCustomLayoutAction()
    {
        $customLayout = Object\ClassDefinition\CustomLayout::getById(intval($this->getParam("id")));
        if ($customLayout) {
            $customLayout->delete();
        }

        $this->_helper->json(["success" => true]);
    }


    public function saveCustomLayoutAction()
    {
        $customLayout = Object\ClassDefinition\CustomLayout::getById($this->getParam("id"));
        $class = Object\ClassDefinition::getById($customLayout->getClassId());

        $configuration = \Zend_Json::decode($this->getParam("configuration"));
        $values = \Zend_Json::decode($this->getParam("values"));

        $modificationDate = intval($values["modificationDate"]);
        if ($modificationDate < $customLayout->getModificationDate()) {
            $this->_helper->json(["success" => false, "msg" => "custom_layout_changed"]);
        }


        $configuration["datatype"] = "layout";
        $configuration["fieldtype"] = "panel";
        $configuration["name"] = "pimcore_root";

        try {
            $layout = Object\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
            $customLayout->setLayoutDefinitions($layout);
            $customLayout->setName($values["name"]);
            $customLayout->setDescription($values["description"]);
            $customLayout->setDefault($values["default"]);
            $customLayout->save();

            $this->_helper->json(["success" => true, "id" => $customLayout->getId(), "data" => $customLayout]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function saveAction()
    {
        $class = Object\ClassDefinition::getById(intval($this->getParam("id")));

        $configuration = \Zend_Json::decode($this->getParam("configuration"));
        $values = \Zend_Json::decode($this->getParam("values"));

        // check if the class was changed during editing in the frontend
        if ($class->getModificationDate() != $values["modificationDate"]) {
            throw new \Exception("The class was modified during editing, please reload the class and make your changes again");
        }

        if ($values["name"] != $class->getName()) {
            $values["name"] = $this->correctClassname($values["name"]);
            $class->rename($values["name"]);
        }

        unset($values["creationDate"]);
        unset($values["userOwner"]);
        unset($values["layoutDefinitions"]);
        unset($values["fieldDefinitions"]);


        $configuration["datatype"] = "layout";
        $configuration["fieldtype"] = "panel";
        $configuration["name"] = "pimcore_root";

        $class->setValues($values);

        try {
            $layout = Object\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);

            $class->setLayoutDefinitions($layout);

            $class->setUserModification($this->user->getId());
            $class->setModificationDate(time());

            $propertyVisibility = [];
            foreach ($values as $key => $value) {
                if (preg_match("/propertyVisibility/i", $key)) {
                    if (preg_match("/\.grid\./i", $key)) {
                        $propertyVisibility["grid"][preg_replace("/propertyVisibility\.grid\./i", "", $key)] = (bool) $value;
                    } elseif (preg_match("/\.search\./i", $key)) {
                        $propertyVisibility["search"][preg_replace("/propertyVisibility\.search\./i", "", $key)] = (bool) $value;
                    }
                }
            }
            if (!empty($propertyVisibility)) {
                $class->setPropertyVisibility($propertyVisibility);
            }

            $class->save();

            // set the fielddefinitions to null because we don't need them in the response
            $class->setFieldDefinitions(null);

            $this->_helper->json(["success" => true, "class" => $class]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
        }
    }


    protected function correctClassname($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9]+/', '', $name);
        $name = preg_replace("/^[0-9]+/", "", $name);

        return $name;
    }


    public function importClassAction()
    {
        $class = Object\ClassDefinition::getById(intval($this->getParam("id")));
        $json = file_get_contents($_FILES["Filedata"]["tmp_name"]);

        $success = Object\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);

        $this->removeViewRenderer();

        $this->_helper->json([
            "success" => $success
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }


    public function importCustomLayoutDefinitionAction()
    {
        $success = false;
        $json = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $importData = \Zend_Json::decode($json);


        $customLayoutId = $this->getParam("id");
        $customLayout = Object\ClassDefinition\CustomLayout::getById($customLayoutId);
        if ($customLayout) {
            try {
                $layout = Object\ClassDefinition\Service::generateLayoutTreeFromArray($importData["layoutDefinitions"], true);
                $customLayout->setLayoutDefinitions($layout);
                $customLayout->setDescription($importData["description"]);
                $customLayout->save();
                $success = true;
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        $this->removeViewRenderer();

        $this->_helper->json([
            "success" => $success
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function getCustomLayoutDefinitionsAction()
    {
        $classId = $this->getParam("classId");
        $list = new Object\ClassDefinition\CustomLayout\Listing();

        $list->setCondition("classId = " . $list->quote($classId));
        $list = $list->load();
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                "id" => $item->getId(),
                "name" => $item->getName() . " (ID: " . $item->getId() . ")",
                "default" => $item->getDefault() ?: 0,
            ];
        }

        $this->_helper->json(["success" => true, "data" => $result]);
    }

    public function getAllLayoutsAction()
    {
        // get all classes
        $resultList = [];
        $mapping = [];

        $customLayouts = new Object\ClassDefinition\CustomLayout\Listing();
        $customLayouts->setOrder("ASC");
        $customLayouts->setOrderKey("name");
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $layout) {
            $mapping[$layout->getClassId()][] = $layout;
        }

        $classList = new Object\ClassDefinition\Listing();
        $classList->setOrder("ASC");
        $classList->setOrderKey("name");
        $classList = $classList->load();

        foreach ($classList as $class) {
            $classMapping = $mapping[$class->getId()];
            if ($classMapping) {
                $resultList[] = [
                    "type" => "master",
                    "id" => $class->getId() . "_" . 0,
                    "name" => $class->getName()
                ];

                foreach ($classMapping as $layout) {
                    $resultList[] = [
                        "type" => "custom",
                        "id" => $class->getId() . "_" . $layout->getId(),
                        "name" => $class->getName() . " - " . $layout->getName()
                    ];
                }
            }
        }

        $this->_helper->json(["data" => $resultList]);
    }

    public function exportClassAction()
    {
        $this->removeViewRenderer();

        $id = intval($this->getParam("id"));
        $class = Object\ClassDefinition::getById($id);

        if (!$class instanceof Object\ClassDefinition) {
            $errorMessage = ": Class with id [ " . $id . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $json = Object\ClassDefinition\Service::generateClassDefinitionJson($class);
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename=\"class_" . $class->getName() . "_export.json\"");
            echo $json;
        }
    }


    public function exportCustomLayoutDefinitionAction()
    {
        $this->removeViewRenderer();
        $id = intval($this->getParam("id"));

        if ($id) {
            $customLayout = Object\ClassDefinition\CustomLayout::getById($id);
            if ($customLayout) {
                $name = $customLayout->getName();
                unset($customLayout->id);
                unset($customLayout->classId);
                unset($customLayout->name);
                unset($customLayout->creationDate);
                unset($customLayout->modificationDate);
                unset($customLayout->userOwner);
                unset($customLayout->userModification);
                unset($customLayout->fieldDefinitions);

                header("Content-type: application/json");
                header("Content-Disposition: attachment; filename=\"custom_definition_" . $name . "_export.json\"");
                $json = json_encode($customLayout);
                $json = \Zend_Json::prettyPrint($json);
                echo $json;
                die();
            }
        }


        $errorMessage = ": Custom Layout with id [ " . $id . " not found. ]";
        Logger::error($errorMessage);
        echo $errorMessage;
    }



    /**
     * FIELDCOLLECTIONS
     */

    public function fieldcollectionGetAction()
    {
        $fc = Object\Fieldcollection\Definition::getByKey($this->getParam("id"));
        $this->_helper->json($fc);
    }

    public function fieldcollectionUpdateAction()
    {
        try {
            $key = $this->getParam("key");

            if ($this->getParam("task") == "add") {
                // check for existing fieldcollection with same name with different lower/upper cases
                $list = new Object\Fieldcollection\Definition\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    if (strtolower($key) === strtolower($item->getKey())) {
                        throw new \Exception("FieldCollection with the same name already exists (lower/upper cases may be different)");
                    }
                }
            }

            $fc = new Object\Fieldcollection\Definition();
            $fc->setKey($key);

            if ($this->getParam("values")) {
                $values = \Zend_Json::decode($this->getParam("values"));
                $fc->setParentClass($values["parentClass"]);
            }

            if ($this->getParam("configuration")) {
                $configuration = \Zend_Json::decode($this->getParam("configuration"));

                $configuration["datatype"] = "layout";
                $configuration["fieldtype"] = "panel";

                $layout = Object\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
                $fc->setLayoutDefinitions($layout);
            }

            $fc->save();

            $this->_helper->json(["success" => true, "id" => $fc->getKey()]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function importFieldcollectionAction()
    {
        $fieldCollection = Object\Fieldcollection\Definition::getByKey($this->getParam("id"));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);

        $success = Object\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, $data);

        $this->removeViewRenderer();

        $this->_helper->json([
            "success" => $success
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function exportFieldcollectionAction()
    {
        $this->removeViewRenderer();
        $fieldCollection = Object\Fieldcollection\Definition::getByKey($this->getParam("id"));
        $key = $fieldCollection->getKey();
        if (!$fieldCollection instanceof Object\Fieldcollection\Definition) {
            $errorMessage = ": Field-Collection with id [ " . $this->getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $json = Object\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection);
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename=\"fieldcollection_" . $key . "_export.json\"");
            echo $json;
        }
    }

    public function fieldcollectionDeleteAction()
    {
        $fc = Object\Fieldcollection\Definition::getByKey($this->getParam("id"));
        $fc->delete();

        $this->_helper->json(["success" => true]);
    }

    public function fieldcollectionTreeAction()
    {
        $list = new Object\Fieldcollection\Definition\Listing();
        $list = $list->load();

        $items = [];

        foreach ($list as $fc) {
            $items[] = [
                "id" => $fc->getKey(),
                "text" => $fc->getKey()
            ];
        }

        $this->_helper->json($items);
    }

    public function fieldcollectionListAction()
    {
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        $currentLayoutId = $this->getParam("layoutId");

        $list = new Object\Fieldcollection\Definition\Listing();
        $list = $list->load();

        if ($this->hasParam("allowedTypes")) {
            $filteredList = [];
            $allowedTypes = explode(",", $this->getParam("allowedTypes"));
            /** @var $type Object\Fieldcollection\Definition */
            foreach ($list as $type) {
                if (in_array($type->getKey(), $allowedTypes)) {
                    $filteredList[] = $type;

                    // mainly for objects-meta data-type
                    $layoutDefinitions = $type->getLayoutDefinitions();
                    $context = [
                        "containerType" => "fieldcollection",
                        "containerKey" => $type->getKey()
                    ];

                    $object = Object\AbstractObject::getById($this->getParam("object_id"));

                    Object\Service::enrichLayoutDefinition($layoutDefinitions, $object, $context);

                    if ($currentLayoutId == -1 && $user->isAdmin()) {
                        Object\Service::createSuperLayout($layoutDefinitions);
                    }
                }
            }

            $list = $filteredList;
        }


        $this->_helper->json(["fieldcollections" => $list]);
    }



    public function getClassDefinitionForColumnConfigAction()
    {
        $class = Object\ClassDefinition::getById(intval($this->getParam("id")));
        $objectId = intval($this->getParam("oid"));

        $filteredDefinitions = Object\Service::getCustomLayoutDefinitionForGridColumnConfig($class, $objectId);

        $layoutDefinitions = isset($filteredDefinitions["layoutDefinition"]) ? $filteredDefinitions["layoutDefinition"] : false;
        $filteredFieldDefinition = isset($filteredDefinitions["fieldDefinition"]) ? $filteredDefinitions["fieldDefinition"] : false;

        $class->setFieldDefinitions(null);

        $result = [];

        $result['objectColumns']['childs'] = $layoutDefinitions->getChilds();
        $result['objectColumns']['nodeLabel'] = "object_columns";
        $result['objectColumns']['nodeType'] = "object";

        // array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
        $systemColumnNames = Object\Concrete::$systemColumnNames;
        $systemColumns = [];
        foreach ($systemColumnNames as $systemColumn) {
            $systemColumns[] = ["title" => $systemColumn, "name" => $systemColumn, "datatype" => "data", "fieldtype" => "system"];
        }
        $result['systemColumns']['nodeLabel'] = "system_columns";
        $result['systemColumns']['nodeType'] = "system";
        $result['systemColumns']['childs'] = $systemColumns;


        $list = new Object\Objectbrick\Definition\Listing();
        $list = $list->load();

        foreach ($list as $brickDefinition) {
            $classDefs = $brickDefinition->getClassDefinitions();
            if (!empty($classDefs)) {
                foreach ($classDefs as $classDef) {
                    if ($classDef['classname'] == $class->getId()) {
                        $fieldName = $classDef["fieldname"];
                        if ($filteredFieldDefinition && !$filteredFieldDefinition[$fieldName]) {
                            continue;
                        }

                        $key = $brickDefinition->getKey();
                        $result[$key]['nodeLabel'] = $key;
                        $result[$key]['nodeType'] = "objectbricks";
                        $result[$key]['childs'] = $brickDefinition->getLayoutdefinitions()->getChilds();
                        break;
                    }
                }
            }
        }

        $this->_helper->json($result);
    }


    /**
     * OBJECT BRICKS
     */

    public function objectbrickGetAction()
    {
        $fc = Object\Objectbrick\Definition::getByKey($this->getParam("id"));
        $this->_helper->json($fc);
    }

    public function objectbrickUpdateAction()
    {
        try {
            $key = $this->getParam("key");

            if ($this->getParam("task") == "add") {
                // check for existing brick with same name with different lower/upper cases
                $list = new Object\Objectbrick\Definition\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    if (strtolower($key) === strtolower($item->getKey())) {
                        throw new \Exception("Brick with the same name already exists (lower/upper cases may be different)");
                    }
                }
            }

            // now we create a new definition
            $fc = new Object\Objectbrick\Definition();
            $fc->setKey($key);

            if ($this->getParam("values")) {
                $values = \Zend_Json::decode($this->getParam("values"));

                $fc->setParentClass($values["parentClass"]);
                $fc->setClassDefinitions($values["classDefinitions"]);
            }

            if ($this->getParam("configuration")) {
                $configuration = \Zend_Json::decode($this->getParam("configuration"));

                $configuration["datatype"] = "layout";
                $configuration["fieldtype"] = "panel";

                $layout = Object\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
                $fc->setLayoutDefinitions($layout);
            }

            $fc->save();

            $this->_helper->json(["success" => true, "id" => $fc->getKey()]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function importObjectbrickAction()
    {
        $objectBrick = Object\Objectbrick\Definition::getByKey($this->getParam("id"));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $success = Object\ClassDefinition\Service::importObjectBrickFromJson($objectBrick, $data);

        $this->removeViewRenderer();

        $this->_helper->json([
            "success" => $success
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function exportObjectbrickAction()
    {
        $this->removeViewRenderer();
        $objectBrick = Object\Objectbrick\Definition::getByKey($this->getParam("id"));
        $key = $objectBrick->getKey();
        if (!$objectBrick instanceof Object\Objectbrick\Definition) {
            $errorMessage = ": Object-Brick with id [ " . $this->getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $xml = Object\ClassDefinition\Service::generateObjectBrickJson($objectBrick);
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename=\"objectbrick_" . $key . "_export.json\"");
            echo $xml;
        }
    }

    public function objectbrickDeleteAction()
    {
        $fc = Object\Objectbrick\Definition::getByKey($this->getParam("id"));
        $fc->delete();

        $this->_helper->json(["success" => true]);
    }

    public function objectbrickTreeAction()
    {
        $list = new Object\Objectbrick\Definition\Listing();
        $list = $list->load();

        $items = [];

        foreach ($list as $fc) {
            $items[] = [
                "id" => $fc->getKey(),
                "text" => $fc->getKey()
            ];
        }

        $this->_helper->json($items);
    }

    public function objectbrickListAction()
    {
        $list = new Object\Objectbrick\Definition\Listing();
        $list = $list->load();

        if ($this->hasParam("class_id") && $this->hasParam("field_name")) {
            $filteredList = [];
            $classId = $this->getParam("class_id");
            $fieldname = $this->getParam("field_name");
            $classDefinition = Object\ClassDefinition::getById($classId);
            $className = $classDefinition->getName();

            foreach ($list as $type) {
                /** @var  $type Object\Objectbrick\Definition */
                $clsDefs = $type->getClassDefinitions();
                if (!empty($clsDefs)) {
                    foreach ($clsDefs as $cd) {
                        if ($cd["classname"] == $classId && $cd["fieldname"] == $fieldname) {
                            $filteredList[] = $type;
                            continue;
                        }
                    }
                }

                $layout = $type->getLayoutDefinitions();

                $context = [
                    "containerType" => "objectbrick",
                    "containerKey" => $type->getKey()
                ];

                $object = Object\AbstractObject::getById($this->getParam("object_id"));

                Object\Service::enrichLayoutDefinition($layout, $object, $context);
                $type->setLayoutDefinitions($layout);
            }

            $list = $filteredList;
        }

        $returnValueContainer = new Model\Tool\Admin\EventDataContainer($list);
        \Pimcore::getEventManager()->trigger("admin.class.objectbrickList.preSendData", $this, ["returnValueContainer" => $returnValueContainer, "objectId"=>$this->getParam('object_id')]);

        $this->_helper->json(["objectbricks" => $list]);
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    public function bulkImportAction()
    {
        $result = [];

        $tmpName = $_FILES["Filedata"]["tmp_name"];
        $json = file_get_contents($tmpName);

        $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/bulk-import.tmp";
        file_put_contents($tmpName, $json);

        $json = json_decode($json, true);

        foreach ($json as $groupName => $group) {
            foreach ($group as $groupItem) {
                $displayName = null;

                if ($groupName == "class") {
                    $name = $groupItem["name"];
                    $icon = \Pimcore\Tool\Admin::isExtJS6() ? "class" : "database_gear";
                } elseif ($groupName == "customlayout") {
                    $className = $groupItem["className"];

                    $layoutData = ["className" => $className, "name" => $groupItem["name"]];
                    $name = serialize($layoutData);
                    $displayName = $className . " / " . $groupItem["name"];
                    $icon = "database_lightning";
                } else {
                    if ($groupName == "objectbrick") {
                        $icon = \Pimcore\Tool\Admin::isExtJS6() ? "objectbricks" : "bricks";
                    } elseif ($groupName == "fieldcollection") {
                        $icon = \Pimcore\Tool\Admin::isExtJS6() ? "fieldcollection" : "table_multiple";
                    }
                    $name = $groupItem["key"];
                }

                if (!$displayName) {
                    $displayName = $name;
                }
                $result[] = ["icon" => $icon, "checked" => true, "type" => $groupName, "name" => $name, "displayName" => $displayName];
            }
        }

        $this->_helper->json(["success" => true, "filename" => $tmpName, "data" => $result], false);
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    public function bulkCommitAction()
    {
        $filename = $this->getParam("filename");
        $data = json_decode($this->getParam("data"), true);

        $json = @file_get_contents($filename);
        $json = json_decode($json, true);

        $type = $data["type"];
        $name = $data["name"];
        $list = $json[$type];

        foreach ($list as $item) {
            unset($item["creationDate"]);
            unset($item["modificationDate"]);
            unset($item["userOwner"]);
            unset($item["userModification"]);


            unset($item["id"]);

            if ($type == "class" && $item["name"] == $name) {
                $class = Object\ClassDefinition::getByName($name);
                if (!$class) {
                    $class = new Object\ClassDefinition();
                    $class->setName($name);
                }
                $success = Object\ClassDefinition\Service::importClassDefinitionFromJson($class, json_encode($item), true);
                $this->_helper->json(["success" => $success !== false]);
            } elseif ($type == "objectbrick" && $item["key"] == $name) {
                try {
                    $brick = Object\Objectbrick\Definition::getByKey($name);
                } catch (\Exception $e) {
                    $brick = new Object\Objectbrick\Definition();
                    $brick->setKey($name);
                }

                $success = Object\ClassDefinition\Service::importObjectBrickFromJson($brick, json_encode($item), true);
                $this->_helper->json(["success" => $success !== false]);
            } elseif ($type == "fieldcollection" && $item["key"] == $name) {
                try {
                    $fieldCollection = Object\Fieldcollection\Definition::getByKey($name);
                } catch (\Exception $e) {
                    $fieldCollection = new Object\Fieldcollection\Definition();
                    $fieldCollection->setKey($name);
                }
                $success = Object\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, json_encode($item), true);
                $this->_helper->json(["success" => $success !== false]);
            } elseif ($type == "customlayout") {
                $layoutData = unserialize($data["name"]);
                $className = $layoutData["className"];
                $layoutName = $layoutData["name"];

                if ($item["name"] == $layoutName && $item["className"] == $className) {
                    $class = Object\ClassDefinition::getByName($className);
                    if (!$class) {
                        throw new \Exception("Class does not exist");
                    }

                    $classId = $class->getId();


                    $layoutList = new Object\ClassDefinition\CustomLayout\Listing();
                    $db = \Pimcore\Db::get();
                    $layoutList->setCondition("name = " . $db->quote($layoutName) . " AND classId = " . $classId);
                    $layoutList = $layoutList->load();

                    $layoutDefinition = null;
                    if ($layoutList) {
                        $layoutDefinition = $layoutList[0];
                    }

                    if (!$layoutDefinition) {
                        $layoutDefinition = new Object\ClassDefinition\CustomLayout();
                        $layoutDefinition->setName($layoutName);
                        $layoutDefinition->setClassId($classId);
                    }

                    try {
                        $layoutDefinition->setDescription($item["description"]);
                        $layoutDef = Object\ClassDefinition\Service::generateLayoutTreeFromArray($item["layoutDefinitions"], true);
                        $layoutDefinition->setLayoutDefinitions($layoutDef);
                        $layoutDefinition->save();
                    } catch (\Exception $e) {
                        Logger::error($e->getMessage());
                        $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                    }
                }
            }
        }


        $this->_helper->json(["success" => true]);
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    public function bulkExportAction()
    {
        $result = [];
        $this->removeViewRenderer();

        $fieldCollections = new Object\Fieldcollection\Definition\Listing();
        $fieldCollections = $fieldCollections->load();

        foreach ($fieldCollections as $fieldCollection) {
            $key = $fieldCollection->key;
            $fieldCollectionJson = json_decode(Object\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection));
            $fieldCollectionJson->key = $key;
            $result["fieldcollection"][] = $fieldCollectionJson;
        }


        $classes = new Object\ClassDefinition\Listing();
        $classes->setOrder("ASC");
        $classes->setOrderKey("id");
        $classes = $classes->load();

        foreach ($classes as $class) {
            $data = Model\Webservice\Data\Mapper::map($class, "\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\Out", "out");
            unset($data->fieldDefinitions);
            $result["class"][] = $data;
        }

        $objectBricks = new Object\Objectbrick\Definition\Listing();
        $objectBricks = $objectBricks->load();

        foreach ($objectBricks as $objectBrick) {
            $key = $objectBrick->key;
            $objectBrickJson = json_decode(Object\ClassDefinition\Service::generateObjectBrickJson($objectBrick));
            $objectBrickJson->key = $key;
            $result["objectbrick"][] = $objectBrickJson;
        }

        $customLayouts = new Object\ClassDefinition\CustomLayout\Listing();
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $customLayout) {
            /** @var  $customLayout Object\ClassDefinition\CustomLayout */
            $classId = $customLayout->getClassId();
            $class = Object\ClassDefinition::getById($classId);
            $customLayout->className = $class->getName();
            $result["customlayout"][] = $customLayout;
        }


        header("Content-type: application/json");
        header("Content-Disposition: attachment; filename=\"bulk_export.json\"");
        $result = json_encode($result);
        echo $result;
    }
}
