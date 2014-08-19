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

class Admin_ClassController extends Pimcore_Controller_Action_Admin {


    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array("get-tree", "fieldcollection-list", "fieldcollection-tree", "fieldcollection-get", "get-class-definition-for-column-config", "objectbrick-list", "objectbrick-tree", "objectbrick-get");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("classes");
        }
    }

    public function getDocumentTypesAction() {
        $documentTypes = Document::getTypes();
        $typeItems = array();
        foreach ($documentTypes as $documentType) {
            $typeItems[] = array(
                "text" => $documentType
            );
        }
        $this->_helper->json($typeItems);
    }

    public function getAssetTypesAction() {
        $assetTypes = Asset::getTypes();
        $typeItems = array();
        foreach ($assetTypes as $assetType) {
            $typeItems[] = array(
                "text" => $assetType
            );
        }
        $this->_helper->json($typeItems);

    }

    public function getTreeAction() {

        $classesList = new Object_Class_List();
        $classesList->setOrderKey("name");
        $classesList->setOrder("asc");
        $classes = $classesList->load();

        // filter classes
        $tmpClasses = array();
        foreach($classes as $class) {
            if($this->getUser()->isAllowed($class->getId(), "class")) {
                $tmpClasses[] = $class;
            }
        }
        $classes = $tmpClasses;

        $classItems = array();

        if(!$this->getParam('grouped')) {
            // list output
            foreach ($classes as $classItem) {
                $classItems[] = array(
                    "id" => $classItem->getId(),
                    "text" => $classItem->getName(),
                    "icon" => $classItem->getIcon() ? $classItem->getIcon() : '/pimcore/static/img/icon/database_gear.png',
                    "propertyVisibility" => $classItem->getPropertyVisibility(),
                    "qtipCfg" => array(
                        "title" => "ID: " . $classItem->getId()
                    ),
                );
            }
        }
        else
        {
            // group classes
            $cnf['matchCount'] = 3; // min chars to group

            /**
             * @param string $str1
             * @param string $str2
             *
             * @return int
             */
            $getEqual
                = function($str1, $str2) {
                $count = 0;
                for($c = 0; $c < strlen($str1); $c++)
                {
                    if(strcasecmp($str1[$c], $str2[$c]) !== 0)
                        break;

                    $count++;
                }

                return $count;
            };

            // create groups
            $classGroups = array();
            $lastGroup = '';
            for($i = 0; $i < count($classes); $i++)
            {
                /* @var Object_Class $classItem */
                $currentClass = $classes[$i];
                $nextClass = $classes[$i+1];

                // check last group
                $className = $currentClass->getName();
                $count = $getEqual($lastGroup, $className);
                if($count <= $cnf['matchCount'] || strlen($lastGroup) != $count)
                {
                    // check new class to group with
                    if($nextClass === null)
                    {
                        // this is the last class
                        $count = strlen($currentClass->getName());
                    }
                    else
                    {
                        // check next class to group with
                        $count = $getEqual($currentClass->getName(), $nextClass->getName());
                        if($count <= $cnf['matchCount'])
                        {
                            // match is to low, use the complete name
                            $count = strlen($currentClass->getName());
                        }
                    }

                    $group = substr($currentClass->getName(), 0, $count);
                }
                else
                {
                    // use previous group
                    $group = $lastGroup;
                }


                // add class to group
                $classGroups[ $group ][] = $currentClass;
                $lastGroup = $group;
            }

            // create json output
            $classItems = array();
            foreach ($classGroups as $name => $classes) {
                if(isset($classes[0]) && $classes[0] instanceof Object_Class) {
                    // basic setup
                    $class = array(
                        "id" => $classes[0]->getId(),
                        "text" => $name,
                        "leaf" => true,
                        "children" => array()
                    );

                    // add childs?
                    if(count($classes) === 1)
                    {
                        // no group
                        $class['id'] = $classes[0]->getId();
                        $class['text'] = $classes[0]->getName();
                        $class['icon'] = $classes[0]->getIcon() ? $classes[0]->getIcon() : '/pimcore/static/img/icon/database_gear.png';
                        $class['propertyVisibility'] = $classes[0]->getPropertyVisibility();
                        $class['qtipCfg']['title'] = "ID: " . $classes[0]->getId();
                    }
                    else
                    {
                        // group classes
                        $class['id'] = "folder_" . $class['id'];
                        $class['leaf'] = false;
                        $class['expandable'] = true;
                        $class['allowChildren'] = true;
                        $class['iconCls'] = 'pimcore_icon_folder';
                        foreach($classes as $classItem)
                        {
                            $child = array(
                                "id" => $classItem->getId(),
                                "text" => $classItem->getName(),
                                "leaf" => true,
                                "icon" => $classItem->getIcon() ? $classItem->getIcon() : '/pimcore/static/img/icon/database_gear.png',
                                "propertyVisibility" => $classItem->getPropertyVisibility(),
                                "qtipCfg" => array(
                                    "title" => "ID: " . $classItem->getId()
                                ),
                            );

                            $class['children'][] = $child;
                        }
                    }
                }

                // add
                $classItems[] = $class;
            }
        }

        // send json

        $this->_helper->json($classItems);
    }

    public function getAction() {
        $class = Object_Class::getById(intval($this->getParam("id")));
        $class->setFieldDefinitions(null);

        $this->_helper->json($class);
    }

    public function getCustomLayoutAction() {
        $customLayout = Object_Class_CustomLayout::getById(intval($this->getParam("id")));

        $this->_helper->json(array("success" => true, "data" => $customLayout));
    }

    public function addAction() {
        $class = Object_Class::create(array('name' => $this->correctClassname($this->getParam("name")),
                'userOwner' => $this->user->getId())
        );

        $class->save();

        $this->_helper->json(array("success" => true, "id" => $class->getId()));
    }

    public function addCustomLayoutAction() {
        $customLayout = Object_Class_CustomLayout::create(array('name' => $this->getParam("name"),
                'userOwner' => $this->user->getId(),
                "classId" => $this->getParam("classId"))
        );

        $customLayout->save();

        $this->_helper->json(array("success" => true, "id" => $customLayout->getId(), "name" => $customLayout->getName(),
            "data" => $customLayout));
    }



    public function deleteAction() {
        $class = Object_Class::getById(intval($this->getParam("id")));
        $class->delete();

        $this->removeViewRenderer();
    }

    public function deleteCustomLayoutAction() {
        $customLayout = Object_Class_CustomLayout::getById(intval($this->getParam("id")));
        if ($customLayout) {
            $customLayout->delete();
        }

        $this->_helper->json(array("success" => true));
    }


    public function saveCustomLayoutAction() {
        $customLayout = Object_Class_CustomLayout::getById($this->getParam("id"));
        $class = Object_Class::getById($customLayout->getClassId());

        $configuration = Zend_Json::decode($this->getParam("configuration"));
        $values = Zend_Json::decode($this->getParam("values"));

        $modificationDate = intval($values["modificationDate"]);
        if ($modificationDate < $customLayout->getModificationDate()) {
            $this->_helper->json(array("success" => false, "msg" => "custom_layout_changed"));
        }


        $configuration["datatype"] = "layout";
        $configuration["fieldtype"] = "panel";
        $configuration["name"] = "pimcore_root";

        $layout = Object_Class_Service::generateLayoutTreeFromArray($configuration);
        $customLayout->setLayoutDefinitions($layout);
        $customLayout->setName($values["name"]);
        $customLayout->setDescription($values["description"]);
        $customLayout->save();

        $this->_helper->json(array("success" => true, "id" => $customLayout->getId(), "data" => $customLayout));
    }

    public function saveAction() {
        $class = Object_Class::getById(intval($this->getParam("id")));

        $configuration = Zend_Json::decode($this->getParam("configuration"));
        $values = Zend_Json::decode($this->getParam("values"));

        // check if the class was changed during editing in the frontend
        if($class->getModificationDate() != $values["modificationDate"]) {
            throw new Exception("The class was modified during editing, please reload the class and make your changes again");
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

        $layout = Object_Class_Service::generateLayoutTreeFromArray($configuration);

        $class->setLayoutDefinitions($layout);

        $class->setUserModification($this->user->getId());
        $class->setModificationDate(time());

        $propertyVisibility = array();
        foreach ($values as $key => $value) {
            if (preg_match("/propertyVisibility/i", $key)) {
                if (preg_match("/\.grid\./i", $key)) {
                    $propertyVisibility["grid"][preg_replace("/propertyVisibility\.grid\./i", "", $key)] = (bool) $value;
                } else if (preg_match("/\.search\./i", $key)) {
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

        $this->_helper->json(array("success" => true, "class" => $class));
    }


    protected function correctClassname($name) {
        $name = preg_replace('/[^a-zA-Z0-9]+/', '', $name);
        $name = preg_replace("/^[0-9]+/", "", $name);
        return $name;
    }


    public function importClassAction() {

        $class = Object_Class::getById(intval($this->getParam("id")));
        $json = file_get_contents($_FILES["Filedata"]["tmp_name"]);

        $success = Object_Class_Service::importClassDefinitionFromJson($class, $json);

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => $success
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }


    public function importCustomLayoutDefinitionAction() {

        $success = false;
        $json = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $importData = Zend_Json::decode($json);


        $customLayoutId = $this->getParam("id");
        $customLayout = Object_Class_CustomLayout::getById($customLayoutId);
        if ($customLayout) {
            $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
            $customLayout->setLayoutDefinitions($layout);
            $customLayout->setDescription($importData["description"]);
            $customLayout->save();
            $success = true;
        }

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => $success
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function getCustomLayoutDefinitionsAction() {
        $classId = $this->getParam("classId");
        $list = new Object_Class_CustomLayout_List();

        $list->setCondition("classId = " . $list->quote($classId));
        $list = $list->load();
        $result = array();
        foreach ($list as $item) {
            $result[] = array(
                "id" => $item->getId(),
                "name" => $item->getName() . " (ID: " . $item->getId() . ")"
            );
        }

        $this->_helper->json(array("success" => true, "data" => $result));
    }

    public function getAllLayoutsAction() {
        // get all classes
        $resultList = array();
        $mapping = array();

        $customLayouts = new Object_Class_CustomLayout_List();
        $customLayouts->setOrder("ASC");
        $customLayouts->setOrderKey("name");
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $layout) {
            $mapping[$layout->getClassId()][] = $layout;
        }

        $classList = new Object_Class_List();
        $classList->setOrder("ASC");
        $classList->setOrderKey("name");
        $classList = $classList->load();

        foreach ($classList as $class) {


            $classMapping = $mapping[$class->getId()];
            if ($classMapping) {
                $resultList[] = array(
                    "type" => "master",
                    "id" => $class->getId() . "_" . 0,
                    "name" => $class->getName()
                );

                foreach($classMapping as $layout) {
                    $resultList[] = array(
                        "type" => "custom",
                        "id" => $class->getId() . "_" . $layout->getId(),
                        "name" => $class->getName() . " - " . $layout->getName()
                    );
                }
            }
        }

        $this->_helper->json(array("data" => $resultList));
    }

    public function exportClassAction() {

        $this->removeViewRenderer();
        $class = Object_Class::getById(intval($this->getParam("id")));

        if (!$class instanceof Object_Class) {
            $errorMessage = ": Class with id [ " . $this->getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $json = Object_Class_Service::generateClassDefinitionJson($class);
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename=\"class_" . $class->getName() . "_export.json\"");
            echo $json;
        }

    }


    public function exportCustomLayoutDefinitionAction() {

        $this->removeViewRenderer();
        $id = $this->getParam("id");

        if ($id) {
            $customLayout = Object_Class_CustomLayout::getById($id);
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
                $json = Zend_Json::prettyPrint($json);
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

    public function fieldcollectionGetAction() {
        $fc = Object_Fieldcollection_Definition::getByKey($this->getParam("id"));
        $this->_helper->json($fc);
    }

    public function fieldcollectionUpdateAction() {


        $fc = new Object_Fieldcollection_Definition();
        $fc->setKey($this->getParam("key"));

        if ($this->getParam("values")) {
            $values = Zend_Json::decode($this->getParam("values"));
            $fc->setParentClass($values["parentClass"]);
        }

        if ($this->getParam("configuration")) {
            $configuration = Zend_Json::decode($this->getParam("configuration"));

            $configuration["datatype"] = "layout";
            $configuration["fieldtype"] = "panel";

            $layout = Object_Class_Service::generateLayoutTreeFromArray($configuration);
            $fc->setLayoutDefinitions($layout);
        }


        $fc->save();

        $this->_helper->json(array("success" => true, "id" => $fc->getKey()));
    }

    public function importFieldcollectionAction() {

        $fieldCollection = Object_Fieldcollection_Definition::getByKey($this->getParam("id"));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);

        $success = Object_Class_Service::importFieldCollectionFromJson($fieldCollection, $data);

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => $success
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function exportFieldcollectionAction() {

        $this->removeViewRenderer();
        $fieldCollection = Object_Fieldcollection_Definition::getByKey($this->getParam("id"));
        $key = $fieldCollection->getKey();
        if (!$fieldCollection instanceof Object_Fieldcollection_Definition) {
            $errorMessage = ": Field-Collection with id [ " . $this->getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $json = Object_Class_Service::generateFieldCollectionJson($fieldCollection);
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename=\"fieldcollection_" . $key . "_export.json\"");
            echo $json;
        }
    }

    public function fieldcollectionDeleteAction() {
        $fc = Object_Fieldcollection_Definition::getByKey($this->getParam("id"));
        $fc->delete();

        $this->_helper->json(array("success" => true));
    }

    public function fieldcollectionTreeAction() {

        $list = new Object_Fieldcollection_Definition_List();
        $list = $list->load();

        $items = array();

        foreach ($list as $fc) {
            $items[] = array(
                "id" => $fc->getKey(),
                "text" => $fc->getKey()
            );
        }

        $this->_helper->json($items);
    }

    public function fieldcollectionListAction() {

        $list = new Object_Fieldcollection_Definition_List();
        $list = $list->load();

        if ($this->hasParam("allowedTypes")) {
            $filteredList = array();
            $allowedTypes = explode(",", $this->getParam("allowedTypes"));
            foreach ($list as $type) {
                if (in_array($type->getKey(), $allowedTypes)) {
                    $filteredList[] = $type;
                }
            }

            $list = $filteredList;
        }

        $this->_helper->json(array("fieldcollections" => $list));
    }



    public function getClassDefinitionForColumnConfigAction() {
        $class = Object_Class::getById(intval($this->getParam("id")));
        $objectId = intval($this->getParam("oid"));

        $filteredDefinitions = Object_Service::getCustomLayoutDefinitionForGridColumnConfig($class, $objectId);;
        $layoutDefinitions = $filteredDefinitions["layoutDefinition"];
        $filteredFieldDefinition = $filteredDefinitions["fieldDefinition"];

        $class->setFieldDefinitions(null);

        $result = array();

        $result['objectColumns']['childs'] = $layoutDefinitions->getChilds();
        $result['objectColumns']['nodeLabel'] = "object_columns";
        $result['objectColumns']['nodeType'] = "object";

        // array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
        $systemColumnNames = Object_Concrete::$systemColumnNames;
        $systemColumns = array();
        foreach($systemColumnNames as $systemColumn) {
            $systemColumns[] = array("title" => $systemColumn, "name" => $systemColumn, "datatype" => "data", "fieldtype" => "system");
        }
        $result['systemColumns']['nodeLabel'] = "system_columns";
        $result['systemColumns']['nodeType'] = "system";
        $result['systemColumns']['childs'] = $systemColumns;


        $list = new Object_Objectbrick_Definition_List();
        $list = $list->load();

        foreach($list as $brickDefinition) {

            $classDefs = $brickDefinition->getClassDefinitions();
            if(!empty($classDefs)) {
                foreach($classDefs as $classDef) {
                    if($classDef['classname'] == $class->getId()) {

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

    public function objectbrickGetAction() {
        $fc = Object_Objectbrick_Definition::getByKey($this->getParam("id"));
        $this->_helper->json($fc);
    }

    public function objectbrickUpdateAction() {


        $fc = new Object_Objectbrick_Definition();
        $fc->setKey($this->getParam("key"));

        if ($this->getParam("values")) {
            $values = Zend_Json::decode($this->getParam("values"));

            $fc->setParentClass($values["parentClass"]);
            $fc->setClassDefinitions($values["classDefinitions"]);
        }

        if ($this->getParam("configuration")) {
            $configuration = Zend_Json::decode($this->getParam("configuration"));

            $configuration["datatype"] = "layout";
            $configuration["fieldtype"] = "panel";

            $layout = Object_Class_Service::generateLayoutTreeFromArray($configuration);
            $fc->setLayoutDefinitions($layout);
        }


        $fc->save();

        $this->_helper->json(array("success" => true, "id" => $fc->getKey()));
    }

    public function importObjectbrickAction() {

        $objectBrick = Object_Objectbrick_Definition::getByKey($this->getParam("id"));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $success = Object_Class_Service::importObjectBrickFromJson($objectBrick, $data);

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => $success
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function exportObjectbrickAction() {

        $this->removeViewRenderer();
        $objectBrick = Object_Objectbrick_Definition::getByKey($this->getParam("id"));
        $key = $objectBrick->getKey();
        if (!$objectBrick instanceof Object_Objectbrick_Definition) {
            $errorMessage = ": Object-Brick with id [ " . $this->getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $xml = Object_Class_Service::generateObjectBrickJson($objectBrick);
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename=\"objectbrick_" . $key . "_export.json\"");
            echo $xml;
        }

    }

    public function objectbrickDeleteAction() {
        $fc = Object_Objectbrick_Definition::getByKey($this->getParam("id"));
        $fc->delete();

        $this->_helper->json(array("success" => true));
    }

    public function objectbrickTreeAction() {
        $list = new Object_Objectbrick_Definition_List();
        $list = $list->load();

        $items = array();

        foreach ($list as $fc) {
            $items[] = array(
                "id" => $fc->getKey(),
                "text" => $fc->getKey()
            );
        }

        $this->_helper->json($items);
    }

    public function objectbrickListAction() {
        $list = new Object_Objectbrick_Definition_List();
        $list = $list->load();

        if ($this->hasParam("class_id") && $this->hasParam("field_name")) {
            $filteredList = array();
            $classId = $this->getParam("class_id");
            $fieldname = $this->getParam("field_name");
            foreach ($list as $type) {
                /** @var  $type Object_Objectbrick_Definition */
                $clsDefs = $type->getClassDefinitions();
                if(!empty($clsDefs)) {
                    foreach($clsDefs as $cd) {
                        if($cd["classname"] == $classId && $cd["fieldname"] == $fieldname) {
                            $filteredList[] = $type;
                            continue;
                        }
                    }
                }

                $layout = $type->getLayoutDefinitions();
                Object_Service::enrichLayoutDefinition($layout);
                $type->setLayoutDefinitions($layout);
            }

            $list = $filteredList;
        }
        $this->_helper->json(array("objectbricks" => $list));
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    public function bulkImportAction() {

        $result = array();

        $tmpName = $_FILES["Filedata"]["tmp_name"];
        $json = file_get_contents($tmpName);

        $tmpName = PIMCORE_TEMPORARY_DIRECTORY . "/bulk-import.tmp";
        file_put_contents($tmpName, $json);

        $json = json_decode($json, true);

        foreach($json as $groupName => $group) {
            foreach($group as $groupItem) {
                $displayName = null;

                if ($groupName == "class") {
                    $name = $groupItem["name"];
                    $icon = "database_gear";
                } else if ($groupName == "customlayout") {
                    $className = $groupItem["className"];

                    $layoutData = array("className" => $className, "name" => $groupItem["name"]);
                    $name = serialize($layoutData);
                    $displayName = $className . " / " . $groupItem["name"];
                    $icon = "database_lightning";
                } else {
                    if ($groupName == "objectbrick") {
                        $icon = "bricks";
                    } else if ($groupName == "fieldcollection") {
                        $icon = "table_multiple";
                    }
                    $name = $groupItem["key"];

                }

                if (!$displayName) {
                    $displayName = $name;
                }
                $result[] = array("icon" => $icon, "checked" => true, "type" => $groupName, "name" => $name, "displayName" => $displayName);
            }

        }

        $this->_helper->json(array("success" => true, "filename" => $tmpName, "data" => $result), false);
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    public function bulkCommitAction() {
        $filename = $this->getParam("filename");
        $data = json_decode($this->getParam("data"), true);

        $json = @file_get_contents($filename);
        $json = json_decode($json, true);

        $type = $data["type"];
        $name = $data["name"];
        $list = $json[$type];

        foreach($list as $item) {
            unset($item["creationDate"]);
            unset($item["modificationDate"]);
            unset($item["userOwner"]);
            unset($item["userModification"]);


            unset($item["id"]);

            if ($type == "class" && $item["name"] == $name) {

                $class = Object_Class::getByName($name);
                if (!$class) {
                    $class = new Object_Class();
                    $class->setName($name);

                }
                $success = Object_Class_Service::importClassDefinitionFromJson($class, json_encode($item), true);
                $this->_helper->json(array("success" => $success !== false));

            } else if ($type == "objectbrick" && $item["key"] == $name) {
                try {
                    $brick = Object_Objectbrick_Definition::getByKey($name);
                } catch (Exception $e) {
                    $brick = new Object_Objectbrick_Definition();
                    $brick->setKey($name);
                }

                $success = Object_Class_Service::importObjectBrickFromJson($brick, json_encode($item), true);
                $this->_helper->json(array("success" => $success !== false));

            } else if ($type == "fieldcollection" && $item["key"] == $name) {
                try {
                    $fieldCollection = Object_Fieldcollection_Definition::getByKey($name);
                } catch (Exception $e) {
                    $fieldCollection = new Object_Fieldcollection_Definition();
                    $fieldCollection->setKey($name);
                }
                $success = Object_Class_Service::importFieldCollectionFromJson($fieldCollection, json_encode($item), true);
                $this->_helper->json(array("success" => $success !== false));

            } else if ($type == "customlayout") {
                $layoutData = unserialize($data["name"]);
                $className = $layoutData["className"];
                $layoutName = $layoutData["name"];

                if ($item["name"] == $layoutName && $item["className"] == $className) {
                    $class = Object_Class::getByName($className);
                    if (!$class) {
                        throw new Exception("Class does not exist");
                    }

                    $classId = $class->getId();


                    $layoutList = new Object_Class_CustomLayout_List();
                    $db = Pimcore_Resource::get();
                    $layoutList->setCondition("name = " . $db->quote($layoutName) . " AND classId = " . $classId);
                    $layoutList = $layoutList->load();

                    $layoutDefinition = null;
                    if ($layoutList) {
                        $layoutDefinition = $layoutList[0];
                    }

                    if (!$layoutDefinition) {
                        $layoutDefinition = new Object_Class_CustomLayout();
                        $layoutDefinition->setName($layoutName);
                        $layoutDefinition->setClassId($classId);
                    }

                    $layoutDefinition->setDescription($item["description"]);
                    $layoutDef = Object_Class_Service::generateLayoutTreeFromArray($item["layoutDefinitions"]);
                    $layoutDefinition->setLayoutDefinitions($layoutDef);
                    $layoutDefinition->save();
                }

            }
        }


        $this->_helper->json(array("success" => true));
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */
    public function bulkExportAction() {

        $result = array();
        $this->removeViewRenderer();

        $fieldCollections = new Object_Fieldcollection_Definition_List();
        $fieldCollections = $fieldCollections->load();

        foreach ($fieldCollections as $fieldCollection) {
            $key = $fieldCollection->key;
            $fieldCollectionJson = json_decode(Object_Class_Service::generateFieldCollectionJson($fieldCollection));
            $fieldCollectionJson->key = $key;
            $result["fieldcollection"][] = $fieldCollectionJson;
        }


        $classes = new Object_Class_List();
        $classes->setOrder("ASC");
        $classes->setOrderKey("id");
        $classes = $classes->load();

        foreach ($classes as $class) {
            $data = Webservice_Data_Mapper::map($class, "Webservice_Data_Class_Out", "out");
            unset($data->fieldDefinitions);
            $result["class"][] = $data;
        }

        $objectBricks = new Object_Objectbrick_Definition_List();
        $objectBricks = $objectBricks->load();

        foreach ($objectBricks as $objectBrick) {
            $key = $objectBrick->key;
            $objectBrickJson = json_decode(Object_Class_Service::generateObjectBrickJson($objectBrick));
            $objectBrickJson->key = $key;
            $result["objectbrick"][] = $objectBrickJson;
        }

        $customLayouts = new Object_Class_CustomLayout_List();
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $customLayout) {
            /** @var  $customLayout Object_Class_CustomLayout */
            $classId = $customLayout->getClassId();
            $class = Object_Class::getById($classId);
            $customLayout->className = $class->getName();
            $result["customlayout"][] = $customLayout;
        }


        header("Content-type: application/json");
        header("Content-Disposition: attachment; filename=\"bulk_export.json\"");
        $result = json_encode($result);
        echo $result;
    }




}
