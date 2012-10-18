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

class Admin_ObjectHelperController extends Pimcore_Controller_Action_Admin {

    public function loadObjectDataAction() {
        $object = Object_Abstract::getById($this->getParam("id"));
        $result = array();
        if($object) {
            $result['success'] = true;
            $fields = $this->getParam("fields");
            foreach($fields as $f) {
                $result['fields']['id'] = $object->getId();
                $getter = "get" . ucfirst($f);
                if(method_exists($object, $getter)) {
                    $result['fields'][$f] = (string) $object->$getter();
                }
            }
            
        } else {
            $result['success'] = false;
        }
        $this->_helper->json($result);
    }


    public function gridGetColumnConfigAction()
    {

        if ($this->getParam("id")) {
            $class = Object_Class::getById($this->getParam("id"));
        } else if ($this->getParam("name")) {
            $class = Object_Class::getByName($this->getParam("name"));
        }

        $gridType = "search";
        if($this->getParam("gridtype")) {
            $gridType = $this->getParam("gridtype");
        }

        $fields = $class->getFieldDefinitions();

        $types = array();
        if ($this->getParam("types")) {
            $types = explode(",", $this->getParam("types"));
        }

        // grid config
        $gridConfig = array();
        if ($this->getParam("objectId")) {

            $configFiles["configFileClassUser"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $this->getParam("objectId") . "_" . $class->getId() . "-user_" . $this->getUser()->getId() . ".psf";
            $configFiles["configFileUser"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $this->getParam("objectId") . "-user_" . $this->getUser()->getId() . ".psf";

            // this is for backward compatibility (not based on user)
            $configFiles["configFileClassCompatible"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $this->getParam("objectId") . "_" . $class->getId() . ".psf";
            $configFiles["configFileCompatible"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $this->getParam("objectId") . ".psf";

            foreach ($configFiles as $configFile) {
                if (is_file($configFile)) {
                    $gridConfig = Pimcore_Tool_Serialize::unserialize(file_get_contents($configFile));
                    break;
                }
            }
        }

        $localizedFields = array();
        $objectbrickFields = array();
        foreach ($fields as $key => $field) {
            if ($field instanceof Object_Class_Data_Localizedfields) {
                $localizedFields[] = $field;
            } else if($field instanceof Object_Class_Data_Objectbricks) {
                $objectbrickFields[] = $field;
            }

        }

        $availableFields = array();
        $systemColumns = array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
        if(empty($gridConfig)) {
            $count = 0;

            if(!$this->getParam("no_system_columns")) {
                $vis = $class->getPropertyVisibility();
                foreach($systemColumns as $sc) {
                    $key = $sc;
                    if($key == "fullpath") {
                        $key = "path";
                    }

                    if(empty($types) && ($vis[$gridType][$key] || $gridType == "all")) {
                        $availableFields[] = array(
                            "key" => $sc,
                            "type" => "system",
                            "label" => $sc,
                            "position" => $count);
                        $count++;
                    }
                }

            }

            $includeBricks = !$this->getParam("no_brick_columns");

            foreach ($fields as $key => $field) {
                if ($field instanceof Object_Class_Data_Localizedfields) {
                    foreach ($field->getFieldDefinitions() as $fd) {
                        if (empty($types) || in_array($fd->getFieldType(), $types)) {
                            $fd->setNoteditable(true);
                            $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $count);
                            if(!empty($fieldConfig)) {
                                $availableFields[] = $fieldConfig;
                                $count++;
                            }
                        }
                    }

                } else if($field instanceof Object_Class_Data_Objectbricks && $includeBricks) {

                    if (in_array($field->getFieldType(), $types)) {
                        $fieldConfig = $this->getFieldGridConfig($field, $gridType, $count);
                        if(!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    } else {
                        $allowedTypes = $field->getAllowedTypes();
                        if(!empty($allowedTypes)) {
                            foreach($allowedTypes as $t) {
                                $brickClass = Object_Objectbrick_Definition::getByKey($t);
                                $brickFields = $brickClass->getFieldDefinitions();
                                if(!empty($brickFields)) {
                                    foreach($brickFields as $bf) {
                                        $fieldConfig = $this->getFieldGridConfig($bf, $gridType, $count, false, $t . "~");
                                        if(!empty($fieldConfig)) {
                                            $availableFields[] = $fieldConfig;
                                            $count++;
                                        }
                                    }
                                }

                            }
                        }
                    }
                } else {
                    if (empty($types) || in_array($field->getFieldType(), $types)) {
                        $fieldConfig = $this->getFieldGridConfig($field, $gridType, $count, !empty($types));
                        if(!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    }
                }
            }
        } else {
            $savedColumns = $gridConfig['columns'];
            foreach($savedColumns as $key => $sc) {
                if(!$sc['hidden']) {
                    if(in_array($key, $systemColumns)) {
                        $availableFields[] = array(
                            "key" => $key,
                            "type" => "system",
                            "label" => $key,
                            "position" => $sc['position']);
                    } else {
                        $keyParts = explode("~", $key);
                        if(count($keyParts) > 1) {
                            $brick = $keyParts[0];
                            $key = $keyParts[1];

                            $brickClass = Object_Objectbrick_Definition::getByKey($brick);
                            $fd = $brickClass->getFieldDefinition($key);
                            if(!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, $brick . "~");
                                if(!empty($fieldConfig)) {
                                    $availableFields[] = $fieldConfig;
                                }
                            }
                        } else {
                            $fd = $class->getFieldDefinition($key);
                            //if not found, look for localized fields
                            if(empty($fd)) {
                                foreach($localizedFields as $lf) {
                                    $fd = $lf->getFieldDefinition($key);
                                    if(!empty($fd)) {
                                        break;
                                    }
                                }
                            }

                            if(!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true);
                                if(!empty($fieldConfig)) {
                                    $availableFields[] = $fieldConfig;
                                }
                            }

                        }

                    }
                }
            }
        }
        usort($availableFields, function ($a, $b)
        {
            if ($a["position"] == $b["position"]) {
                return 0;
            }
            return ($a["position"] < $b["position"]) ? -1 : 1;
        });

        $language = $this->getLanguage();

        if(!Pimcore_Tool::isValidLanguage($language)) {
            $validLanguages = Pimcore_Tool::getValidLanguages();
            $language = $validLanguages[0];
        }


        if(!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }
        $this->_helper->json(array(
            "sortinfo" => $gridConfig['sortinfo'],
            "language" => $language,
            "availableFields" => $availableFields,
            "onlyDirectChildren" => $gridConfig['onlyDirectChildren']
        ));
    }


    protected function getFieldGridConfig($field, $gridType, $position, $force = false, $keyPrefix = null)
    {

        $key = $keyPrefix . $field->getName();
        $config = null;
        $title = $field->getName();
        if (method_exists($field, "getTitle")) {
            if ($field->getTitle()) {
                $title = $field->getTitle();
            }
        }

        if ($field->getFieldType() == "slider") {
            $config["minValue"] = $field->getMinValue();
            $config["maxValue"] = $field->getMaxValue();
            $config["increment"] = $field->getIncrement();
        }

        if (method_exists($field, "getWidth")) {
            $config["width"] = $field->getWidth();
        }
        if (method_exists($field, "getHeight")) {
            $config["height"] = $field->getHeight();
        }

        $visible = false;
        if($gridType == "search") {
            $visible = $field->getVisibleSearch();
        } elseif($gridType == "grid") {
            $visible = $field->getVisibleGridView();
        } elseif($gridType == "all") {
            $visible = true;
        }

        if(!$field->getInvisible() && ($force || $visible)) {
            return array(
                "key" => $key,
                "type" => $field->getFieldType(),
                "label" => $title,
                "config" => $config,
                "layout" => $field ,
                "position" => $position
            );
        } else {
            return null;
        }

    }


    /**
     * CUSTOM VIEWS
     */
    public function saveCustomviewsAction()
    {

        $success = true;

        $settings = array("views" => array("view" => array()));

        for ($i = 0; $i < 1000; $i++) {
            if ($this->getParam("name_" . $i)) {

                // check for root-folder
                $rootfolder = "/";
                if ($this->getParam("rootfolder_" . $i)) {
                    $rootfolder = $this->getParam("rootfolder_" . $i);
                }

                $settings["views"]["view"][] = array(
                    "name" => $this->getParam("name_" . $i),
                    "condition" => $this->getParam("condition_" . $i),
                    "icon" => $this->getParam("icon_" . $i),
                    "id" => ($i + 1),
                    "rootfolder" => $rootfolder,
                    "showroot" => ($this->getParam("showroot_" . $i) == "true") ? true : false,
                    "classes" => $this->getParam("classes_" . $i)
                );
            }
        }


        $config = new Zend_Config($settings, true);
        $writer = new Zend_Config_Writer_Xml(array(
              "config" => $config,
              "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/customviews.xml"
        ));
        $writer->write();


        $this->_helper->json(array("success" => $success));
    }

    public function getCustomviewsAction()
    {

        $data = Pimcore_Tool::getCustomViewConfig();

        $this->_helper->json(array(
              "success" => true,
              "data" => $data
        ));
    }



    /**
     * IMPORTER
     */

    public function importUploadAction()
    {
        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);

        $encoding = Pimcore_Tool_Text::detectEncoding($data);
        if ($encoding) {
            $data = iconv($encoding, "UTF-8", $data);
        }

        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id");
        file_put_contents($importFile, $data);
        chmod($importFile, 0766);

        $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id") . "_original";
        file_put_contents($importFileOriginal, $data);
        chmod($importFileOriginal, 0766);

        $this->_helper->json(array(
            "success" => true
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function importGetFileInfoAction()
    {

        $success = true;
        $supportedFieldTypes = array("checkbox", "country", "date", "datetime", "href", "image", "input", "language", "table", "multiselect", "numeric", "password", "select", "slider", "textarea", "wysiwyg", "objects", "multihref", "geopoint", "geopolygon", "geobounds", "link", "user");

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id");

        // determine type
        $dialect = Pimcore_Tool_Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id") . "_original");

        $count = 0;
        if (($handle = fopen($file, "r")) !== false) {
            while (($rowData = fgetcsv($handle, 10000, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                if ($count == 0) {
                    $firstRowData = $rowData;
                }
                $tmpData = array();
                foreach ($rowData as $key => $value) {
                    $tmpData["field_" . $key] = $value;
                }
                $data[] = $tmpData;
                $cols = count($rowData);

                $count++;

                if ($count > 18) {
                    break;
                }

            }
            fclose($handle);
        }

        // get class data
        $class = Object_Class::getById($this->getParam("classId"));
        $fields = $class->getFieldDefinitions();

        $availableFields = array();

        foreach ($fields as $key => $field) {

            $config = null;
            $title = $field->getName();
            if (method_exists($field, "getTitle")) {
                if ($field->getTitle()) {
                    $title = $field->getTitle();
                }
            }

            if (in_array($field->getFieldType(), $supportedFieldTypes)) {
                $availableFields[] = array($field->getName(), $title . "(" . $field->getFieldType() . ")");
            }
        }

        $mappingStore = array();
        for ($i = 0; $i < $cols; $i++) {

            $mappedField = null;
            if ($availableFields[$i]) {
                $mappedField = $availableFields[$i][0];
            }

            $firstRow = $i;
            if (is_array($firstRowData)) {
                $firstRow = $firstRowData[$i];
                if (strlen($firstRow) > 40) {
                    $firstRow = substr($firstRow, 0, 40) . "...";
                }
            }

            $mappingStore[] = array(
                "source" => $i,
                "firstRow" => $firstRow,
                "target" => $mappedField
            );
        }

        $this->_helper->json(array(
              "success" => $success,
              "dataPreview" => $data,
              "dataFields" => array_keys($data[0]),
              "targetFields" => $availableFields,
              "mappingStore" => $mappingStore,
              "rows" => count(file($file)),
              "cols" => $cols
        ));
    }

    public function importProcessAction()
    {

        $success = true;

        $parentId = $this->getParam("parentId");
        $job = $this->getParam("job");
        $id = $this->getParam("id");
        $mappingRaw = Zend_Json::decode($this->getParam("mapping"));
        $class = Object_Class::getById($this->getParam("classId"));
        $skipFirstRow = $this->getParam("skipHeadRow") == "true";
        $fields = $class->getFieldDefinitions();

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $id;

        // currently only csv supported
        // determine type
        $dialect = Pimcore_Tool_Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $id . "_original");

        $count = 0;
        if (($handle = fopen($file, "r")) !== false) {
            $data = fgetcsv($handle, 1000, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
        }
        if ($skipFirstRow && $job == 1) {
            //read the next row, we need to skip the head row
            $data = fgetcsv($handle, 1000, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
        }

        $tmpFile = $file . "_tmp";
        $tmpHandle = fopen($tmpFile, "w+");
        while (!feof($handle)) {
            $buffer = fgets($handle);
            fwrite($tmpHandle, $buffer);
        }

        fclose($handle);
        fclose($tmpHandle);

        unlink($file);
        rename($tmpFile, $file);


        // prepare mapping
        foreach ($mappingRaw as $map) {

            if ($map[0] !== "" && $map[1] && !empty($map[2])) {
                $mapping[$map[2]] = $map[0];
            } else if ($map[1] == "published (system)") {
                $mapping["published"] = $map[0];
            }
        }

        // create new object
        $className = "Object_" . ucfirst($this->getParam("className"));

        $parent = Object_Abstract::getById($this->getParam("parentId"));

        $objectKey = "object_" . $job;
        if ($this->getParam("filename") == "id") {
            $objectKey = null;
        }
        else if ($this->getParam("filename") != "default") {
            $objectKey = Pimcore_File::getValidFilename($data[$this->getParam("filename")]);
        }

        $overwrite = false;
        if ($this->getParam("overwrite") == "true") {
            $overwrite = true;
        }

        if ($parent->isAllowed("create")) {

            $intendedPath = $parent->getFullPath() . "/" . $objectKey;

            if ($overwrite) {
                $object = Object_Abstract::getByPath($intendedPath);
                if (!$object instanceof Object_Concrete) {
                    //create new object
                    $object = new $className();
                } else if (object instanceof Object_Concrete and $object->getO_className() !== $className) {
                    //delete the old object it is of a different class
                    $object->delete();
                    $object = new $className();
                } else if (object instanceof Object_Folder) {
                    //delete the folder
                    $object->delete();
                    $object = new $className();
                } else {
                    //use the existing object
                }
            } else {
                $counter = 1;
                while (Object_Abstract::getByPath($intendedPath) != null) {
                    $objectKey .= "_" . $counter;
                    $intendedPath = $parent->getFullPath() . "/" . $objectKey;
                    $counter++;
                }
                $object = new $className();
            }
            $object->setClassId($this->getParam("classId"));
            $object->setClassName($this->getParam("className"));
            $object->setParentId($this->getParam("parentId"));
            $object->setKey($objectKey);
            $object->setCreationDate(time());
            $object->setUserOwner($this->getUser()->getId());
            $object->setUserModification($this->getUser()->getId());

            if ($data[$mapping["published"]] === "1") {
                $object->setPublished(true);
            } else {
                $object->setPublished(false);
            }

            foreach ($class->getFieldDefinitions() as $key => $field) {

                $value = $data[$mapping[$key]];
                if (array_key_exists($key, $mapping) and  $value != null) {
                    // data mapping
                    $value = $field->getFromCsvImport($value);

                    if ($value !== null) {
                        $object->setValue($key, $value);
                    }
                }
            }

            try {
                $object->save();
                $this->_helper->json(array("success" => true));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $object->getKey() . " - " . $e->getMessage()));
            }
        }


        $this->_helper->json(array("success" => $success));
    }



    public function exportAction()
    {

        $folder = Object_Abstract::getById($this->getParam("folderId"));
        $class = Object_Class::getById($this->getParam("classId"));

        $className = $class->getName();

        $listClass = "Object_" . ucfirst($className) . "_List";

        if(empty($folder)) {
            $conditionFilters = array("o_path LIKE '" . $folder->getFullPath() . "%'");
        } else {
            $conditionFilters = array();
        }
        if ($this->getParam("filter")) {
            $conditionFilters[] = Object_Service::getFilterCondition($this->getParam("filter"), $class);
        }
        if ($this->getParam("condition")) {
            $conditionFilters[] = "(" . $this->getParam("condition") . ")";
        }

        $list = new $listClass();
        $list->setCondition(implode(" AND ", $conditionFilters));
        $list->setOrder("ASC");
        $list->setOrderKey("o_id");

        if($this->getParam("objecttype")) {
            $list->setObjectTypes(array($this->getParam("objecttype")));
        }

        $list->load();

        $objects = array();
        Logger::debug("objects in list:" . count($list->getObjects()));
        foreach ($list->getObjects() as $object) {

            if ($object instanceof Object_Concrete) {
                $o = $this->csvObjectData($object);
                $objects[] = $o;
            }
        }
        //create csv
        if(!empty($objects)) {
            $columns = array_keys($objects[0]);
            foreach ($columns as $key => $value) {
                $columns[$key] = '"' . $value . '"';
            }
            $csv = implode(";", $columns) . "\r\n";
            foreach ($objects as $o) {
                foreach ($o as $key => $value) {

                    //clean value of evil stuff such as " and linebreaks
                    if (is_string($value)) {
                        $value = strip_tags($value);
                        $value = str_replace('"', '', $value);
                        $value = str_replace("\r", "", $value);
                        $value = str_replace("\n", "", $value);

                        $o[$key] = '"' . $value . '"';
                    }
                }
                $csv .= implode(";", $o) . "\r\n";
            }

        }
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=\"export.csv\"");
        echo $csv;
        exit;
    }


    /**
     * Flattens object data to an array with key=>value where
     * value is simply a string representation of the value (for objects, hrefs and assets the full path is used)
     *
     * @param Object_Abstract $object
     * @return array
     */
    protected function csvObjectData($object)
    {

        $o = array();
        foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
            //exclude remote owner fields
            if (!($value instanceof Object_Class_Data_Relations_Abstract and $value->isRemoteOwner())) {
                $o[$key] = $value->getForCsvExport($object);
            }

        }

        $o["id (system)"] = $object->getId();
        $o["key (system)"] = $object->getKey();
        $o["fullpath (system)"] = $object->getFullPath();
        $o["published (system)"] = $object->isPublished();

        return $o;
    }


    public function getBatchJobsAction()
    {

        $folder = Object_Abstract::getById($this->getParam("folderId"));
        $class = Object_Class::getById($this->getParam("classId"));

        $conditionFilters = array("o_path = ? OR o_path LIKE '" . str_replace("//","/",$folder->getFullPath() . "/") . "%'");

        if ($this->getParam("filter")) {
            $conditionFilters[] = Object_Service::getFilterCondition($this->getParam("filter"), $class);
        }
        if ($this->getParam("condition")) {
            $conditionFilters[] = " AND (" . $this->getParam("condition") . ")";
        }

        $className = $class->getName();
        $listClass = "Object_" . ucfirst($className) . "_List";
        $list = new $listClass();
        $list->setCondition(implode(" AND ", $conditionFilters), array($folder->getFullPath()));
        $list->setOrder("ASC");
        $list->setOrderKey("o_id");

        if($this->getParam("objecttype")) {
            $list->setObjectTypes(array($this->getParam("objecttype")));
        }

        $jobs = $list->loadIdList();

        $this->_helper->json(array("success"=>true,"jobs"=>$jobs));

    }

    public function batchAction()
    {

        $success = true;

        try {
            $object = Object_Abstract::getById($this->getParam("job"));

            if ($object) {
                $className = $object->getO_className();
                $class = Object_Class::getByName($className);
                $value = $this->getParam("value");
                if ($this->getParam("valueType") == "object") {
                    $value = Zend_Json::decode($value);
                }

                $name = $this->getParam("name");
                $parts = explode("~", $name);

                // check for bricks
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

                    $brickClass = Object_Objectbrick_Definition::getByKey($brickType);
                    $field = $brickClass->getFieldDefinition($brickKey);
                    $brick->$valueSetter($field->getDataFromEditmode($value, $object));

                } else {
                    // everything else
                    $field = $class->getFieldDefinition($name);
                    if($field) {
                        $object->setValue($name, $field->getDataFromEditmode($value, $object));
                    } else {
                        // seems to be a system field, this is actually only possible for the "published" field yet
                        if($name == "published") {
                            if($value == "false" || empty($value)) {
                                $object->setPublished(false);
                            } else {
                                $object->setPublished(true);
                            }
                        }
                    }
                }

                try {
                    // don't check for mandatory fields here
                    $object->setOmitMandatoryCheck(true);
                    $object->setUserModification($this->getUser()->getId());
                    $object->save();
                    $success = true;
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
            else {
                Logger::debug("ObjectController::batchAction => There is no object left to update.");
                $this->_helper->json(array("success" => false, "message" => "ObjectController::batchAction => There is no object left to update."));
            }

        }
        catch (Exception $e) {
            Logger::err($e);
            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
        }

        $this->_helper->json(array("success" => $success));
    }


}