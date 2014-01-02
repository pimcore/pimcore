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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
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

        if($this->getParam('grouped') == 0)
        {
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
            $cnf['matchCount'] = 3;     // min chars to group

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
                $count = $getEqual($lastGroup, $currentClass->getName());
                if($count <= $cnf['matchCount'])
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
            foreach ($classGroups as $name => $classes)
            {
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

    public function addAction() {
        $class = Object_Class::create(array('name' => $this->correctClassname($this->getParam("name")),
                                            'userOwner' => $this->user->getId())
        );

        $class->save();

        $this->_helper->json(array("success" => true, "id" => $class->getId()));
    }

    public function deleteAction() {
        $class = Object_Class::getById(intval($this->getParam("id")));
        $class->delete();

        $this->removeViewRenderer();
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
        $tmpFilename = $name;
        $validChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $filenameParts = array();

        for ($i = 0; $i < strlen($tmpFilename); $i++) {
            if (strpos($validChars, $tmpFilename[$i]) !== false) {
                $filenameParts[] = $tmpFilename[$i];
            }
        }

        return implode("", $filenameParts);
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
        $class->setFieldDefinitions(null);

        $result = array();
        $result['objectColumns']['childs'] = $class->getLayoutDefinitions()->getChilds();
        $result['objectColumns']['nodeLabel'] = "object_columns";
        $result['objectColumns']['nodeType'] = "object";

        $systemColumnNames = Object_Concrete::$systemColumnNames; // array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
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
                $clsDefs = $type->getClassDefinitions();
                if(!empty($clsDefs)) {
                    foreach($clsDefs as $cd) {

                        if($cd["classname"] == $classId && $cd["fieldname"] == $fieldname) {
                            $filteredList[] = $type;
                            continue;
                        }

                    }

                }
            }

            $list = $filteredList;
        }
        $this->_helper->json(array("objectbricks" => $list));
    }


}
