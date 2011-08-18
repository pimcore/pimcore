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

class Admin_ClassController extends Pimcore_Controller_Action_Admin {


    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array("get-tree", "fieldcollection-list", "fieldcollection-tree", "fieldcollection-get", "get-class-definition-for-column-config", "objectbrick-list", "objectbrick-tree", "objectbrick-get");
        if (!in_array($this->_getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAllowed("classes")) {

                $this->_redirect("/admin/login");
                die();
            }
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


        $classItems = array();

        foreach ($classes as $classItem) {
            $classItems[] = array(
                "id" => $classItem->getId(),
                "text" => $classItem->getName(),
                "icon" => $classItem->getIcon(),
                "propertyVisibility" => $classItem->getPropertyVisibility()
            );
        }

        $this->_helper->json($classItems);
    }

    public function getAction() {
        $class = Object_Class::getById(intval($this->_getParam("id")));
        $class->setFieldDefinitions(null);

        $this->_helper->json($class);
    }

    public function addAction() {
        $class = Object_Class::create();
        $class->setName($this->correctClassname($this->_getParam("name")));
        $class->setUserOwner($this->user->getId());
        $class->save();
        $this->removeViewRenderer();
    }

    public function deleteAction() {
        $class = Object_Class::getById(intval($this->_getParam("id")));
        $class->delete();

        $this->removeViewRenderer();
    }

    public function importClassAction() {

        $class = Object_Class::getById(intval($this->_getParam("id")));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $conf = new Zend_Config_Xml($data);
        $importData = $conf->toArray();

        $values["modificationDate"] = time();
        $values["userModification"] = $this->user->getId();

        // set layout-definition
        $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
        $class->setLayoutDefinitions($layout);

        // set properties of class
        $class->setModificationDate(time());
        $class->setUserModification($this->user->getId());
        $class->setIcon($importData["icon"]);
        $class->setAllowInherit($importData["allowInherit"]);
        $class->setAllowVariants($importData["allowVariants"]);
        $class->setParentClass($importData["parentClass"]);
        $class->setPreviewUrl($importData["previewUrl"]);
        $class->setPropertyVisibility($importData["propertyVisibility"]);
        
        $class->save();

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => true
        ));
    }


    public function exportClassAction() {

        $this->removeViewRenderer();
        $class = Object_Class::getById(intval($this->_getParam("id")));

        if (!$class instanceof Object_Class) {
            $errorMessage = ": Class with id [ " . $this->_getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $xml = Object_Class_Service::generateClassDefinitionXml($class);
            header("Content-type: application/xml");
            header("Content-Disposition: attachment; filename=\"class_" . $class->getName() . "_export.xml\"");
            echo $xml;
        }

    }

    public function saveAction() {
        $class = Object_Class::getById(intval($this->_getParam("id")));

        $configuration = Zend_Json::decode($this->_getParam("configuration"));
        $values = Zend_Json::decode($this->_getParam("values"));

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
        $this->removeViewRenderer();
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


    /**
     * FIELDCOLLECTIONS
     */

    public function fieldcollectionGetAction() {
        $fc = Object_Fieldcollection_Definition::getByKey($this->_getParam("id"));
        $this->_helper->json($fc);
    }

    public function fieldcollectionUpdateAction() {


        $fc = new Object_Fieldcollection_Definition();
        $fc->setKey($this->_getParam("key"));

        if ($this->_getParam("values")) {
            $values = Zend_Json::decode($this->_getParam("values"));
            $fc->setParentClass($values["parentClass"]);
        }

        if ($this->_getParam("configuration")) {
            $configuration = Zend_Json::decode($this->_getParam("configuration"));

            $configuration["datatype"] = "layout";
            $configuration["fieldtype"] = "panel";

            $layout = Object_Class_Service::generateLayoutTreeFromArray($configuration);
            $fc->setLayoutDefinitions($layout);
        }


        $fc->save();

        $this->_helper->json(array("success" => true));
    }

    public function importFieldcollectionAction() {

        $fieldCollection = Object_Fieldcollection_Definition::getByKey($this->_getParam("id"));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $conf = new Zend_Config_Xml($data);
        $importData = $conf->toArray();

        $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
        $fieldCollection->setLayoutDefinitions($layout);
        $fieldCollection->save();

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => true
        ));

    }

    public function exportFieldcollectionAction() {

        $this->removeViewRenderer();
        $fieldCollection = Object_Fieldcollection_Definition::getByKey($this->_getParam("id"));
        if (!$fieldCollection instanceof Object_Fieldcollection_Definition) {
            $errorMessage = ": Field-Collection with id [ " . $this->_getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $xml = Object_Class_Service::generateFieldCollectionXml($fieldCollection);
            header("Content-type: application/xml");
            header("Content-Disposition: attachment; filename=\"class_" . $fieldCollection->getKey() . "_export.xml\"");
            echo $xml;
        }

    }

    public function fieldcollectionDeleteAction() {
        $fc = Object_Fieldcollection_Definition::getByKey($this->_getParam("id"));
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

        if ($this->_hasParam("allowedTypes")) {
            $filteredList = array();
            $allowedTypes = explode(",", $this->_getParam("allowedTypes"));
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
        $class = Object_Class::getById(intval($this->_getParam("id")));
        $class->setFieldDefinitions(null);

        $result = array();
        $result['objectColumns']['childs'] = $class->getLayoutDefinitions()->getChilds();
        $result['objectColumns']['nodeLabel'] = "object_columns";
        $result['objectColumns']['nodeType'] = "object";

        $systemColumnNames = array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
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
        $fc = Object_Objectbrick_Definition::getByKey($this->_getParam("id"));
        $this->_helper->json($fc);
    }

    public function objectbrickUpdateAction() {


        $fc = new Object_Objectbrick_Definition();
        $fc->setKey($this->_getParam("key"));

        if ($this->_getParam("values")) {
            $values = Zend_Json::decode($this->_getParam("values"));

            $fc->setParentClass($values["parentClass"]);
            $fc->setClassDefinitions($values["classDefinitions"]);
        }

        if ($this->_getParam("configuration")) {
            $configuration = Zend_Json::decode($this->_getParam("configuration"));

            $configuration["datatype"] = "layout";
            $configuration["fieldtype"] = "panel";

            $layout = Object_Class_Service::generateLayoutTreeFromArray($configuration);
            $fc->setLayoutDefinitions($layout);
        }


        $fc->save();

        $this->_helper->json(array("success" => true));
    }

    public function importObjectbrickAction() {

        $objectBrick = Object_Objectbrick_Definition::getByKey($this->_getParam("id"));

        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $conf = new Zend_Config_Xml($data);
        $importData = $conf->toArray();

        $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
        $objectBrick->setLayoutDefinitions($layout);
        $objectBrick->save();

        $this->removeViewRenderer();

        $this->_helper->json(array(
            "success" => true
        ));

    }

    public function exportObjectbrickAction() {

        $this->removeViewRenderer();
        $objectBrick = Object_Objectbrick_Definition::getByKey($this->_getParam("id"));
        if (!$objectBrick instanceof Object_Objectbrick_Definition) {
            $errorMessage = ": Object-Brick with id [ " . $this->_getParam("id") . " not found. ]";
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $xml = Object_Class_Service::generateFieldCollectionXml($objectBrick);
            header("Content-type: application/xml");
            header("Content-Disposition: attachment; filename=\"class_" . $objectBrick->getKey() . "_export.xml\"");
            echo $xml;
        }

    }

    public function objectbrickDeleteAction() {
        $fc = Object_Objectbrick_Definition::getByKey($this->_getParam("id"));
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

        if ($this->_hasParam("class_id") && $this->_hasParam("field_name")) {
            $filteredList = array();
            $classId = $this->_getParam("class_id");
            $fieldname = $this->_getParam("field_name");
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
