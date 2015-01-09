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

use Pimcore\Tool;
use Pimcore\File;
use Pimcore\Model\Object;

class   Admin_ObjectHelperController extends \Pimcore\Controller\Action\Admin {

    public function loadObjectDataAction() {
        $object = Object::getById($this->getParam("id"));
        $result = array();
        if($object) {
            $result['success'] = true;
            $fields = $this->getParam("fields");
            $result['fields'] = Object\Service::gridObjectData($object, $fields);

        } else {
            $result['success'] = false;
        }
        $this->_helper->json($result);
    }


    public function gridGetColumnConfigAction() {

        if ($this->getParam("id")) {
            $class = Object\ClassDefinition::getById($this->getParam("id"));
        } else if ($this->getParam("name")) {
            $class = Object\ClassDefinition::getByName($this->getParam("name"));
        }

        $gridType = "search";
        if($this->getParam("gridtype")) {
            $gridType = $this->getParam("gridtype");
        }

        $objectId = $this->getParam("objectId");

        if ($objectId) {
            $fields = Object\Service::getCustomGridFieldDefinitions($class->getId(), $objectId);
        }

        if (!$fields) {
            $fields = $class->getFieldDefinitions();
        }

        $types = array();
        if ($this->getParam("types")) {
            $types = explode(",", $this->getParam("types"));
        }

        // grid config
        $gridConfig = array();
        if ($objectId) {

            $configFiles["configFileClassUser"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $this->getParam("objectId") . "_" . $class->getId() . "-user_" . $this->getUser()->getId() . ".psf";
            $configFiles["configFileUser"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $this->getParam("objectId") . "-user_" . $this->getUser()->getId() . ".psf";

            foreach ($configFiles as $configFile) {
                if (is_file($configFile)) {
                    $gridConfig = Tool\Serialize::unserialize(file_get_contents($configFile));
                    if(array_key_exists("classId", $gridConfig)) {
                        if($gridConfig["classId"] == $class->getId()) {
                            break;
                        } else {
                            $gridConfig = array();
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        $localizedFields = array();
        $objectbrickFields = array();
        foreach ($fields as $key => $field) {
            if ($field instanceof Object\ClassDefinition\Data\Localizedfields) {
                $localizedFields[] = $field;
            } else if($field instanceof Object\ClassDefinition\Data\Objectbricks) {
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
                if ($field instanceof Object\ClassDefinition\Data\Localizedfields) {
                    foreach ($field->getFieldDefinitions() as $fd) {
                        if (empty($types) || in_array($fd->getFieldType(), $types)) {
                            $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $count);
                            if(!empty($fieldConfig)) {
                                $availableFields[] = $fieldConfig;
                                $count++;
                            }
                        }
                    }

                } else if($field instanceof Object\ClassDefinition\Data\Objectbricks && $includeBricks) {

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
                                $brickClass = Object\Objectbrick\Definition::getByKey($t);
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
                        $colConfig = array(
                            "key" => $key,
                            "type" => "system",
                            "label" => $key,
                            "position" => $sc['position']);
                        if (isset($sc['width'])) {
                            $colConfig['width'] = $sc['width'];
                        }
                        $availableFields[] = $colConfig;
                    } else {
                        $keyParts = explode("~", $key);

                        if (substr($key, 0, 1) == "~") {
                            // not needed for now
//                            $type = $keyParts[1];
//                            $field = $keyParts[2];
//                            $keyid = $keyParts[3];
                        } else if(count($keyParts) > 1) {
                            $brick = $keyParts[0];
                            $key = $keyParts[1];

                            $brickClass = Object\Objectbrick\Definition::getByKey($brick);
                            $fd = $brickClass->getFieldDefinition($key);
                            if(!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, $brick . "~");
                                if(!empty($fieldConfig)) {
                                    if (isset($sc['width'])) {
                                        $fieldConfig['width'] = $sc['width'];
                                    }
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
                                    if (isset($sc['width'])) {
                                        $fieldConfig['width'] = $sc['width'];
                                    }

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

        if(!Tool::isValidLanguage($language)) {
            $validLanguages = Tool::getValidLanguages();
            $language = $validLanguages[0];
        }


        if(!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }
        $this->_helper->json(array(
            "sortinfo" => $gridConfig['sortinfo'],
            "language" => $language,
            "availableFields" => $availableFields,
            "onlyDirectChildren" => $gridConfig['onlyDirectChildren'],
            "pageSize" => $gridConfig['pageSize']
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


        $config = new \Zend_Config($settings, true);
        $writer = new \Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/customviews.xml"
        ));
        $writer->write();


        $this->_helper->json(array("success" => $success));
    }

    public function getCustomviewsAction()
    {

        $data = Tool::getCustomViewConfig();

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
        $data = Tool\Text::convertToUTF8($data);

        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id");
        File::put($importFile, $data);

        $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id") . "_original";
        File::put($importFileOriginal, $data);

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
        $supportedFieldTypes = array("checkbox", "country", "date", "datetime", "href", "image", "input", "language", "table", "multiselect", "numeric", "password", "select", "slider", "textarea", "wysiwyg", "objects", "multihref", "geopoint", "geopolygon", "geobounds", "link", "user", "email", "gender", "firstname", "lastname", "newsletterActive", "newsletterConfirmed", "countrymultiselect", "objectsMetadata");

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id");

        // determine type
        $dialect = Tool\Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $this->getParam("id") . "_original");

        $count = 0;
        if (($handle = fopen($file, "r")) !== false) {
            while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
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
        $class = Object\ClassDefinition::getById($this->getParam("classId"));
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

        //How many rows
        $csv = new SplFileObject($file);
        $csv->setFlags(SplFileObject::READ_CSV);
        $csv->setCsvControl($dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
        $rows = 0;
        $nbFields = 0;
        foreach ($csv as $fields) {
            if (0 === $rows) {
                $nbFields = count($fields);
                $rows++;
            } elseif ($nbFields == count($fields)) {
                $rows++;
            }
        }

        $this->_helper->json(array(
            "success" => $success,
            "dataPreview" => $data,
            "dataFields" => array_keys($data[0]),
            "targetFields" => $availableFields,
            "mappingStore" => $mappingStore,
            "rows" => $rows,
            "cols" => $cols
        ));
    }

    public function importProcessAction()
    {

        $success = true;

        $parentId = $this->getParam("parentId");
        $job = $this->getParam("job");
        $id = $this->getParam("id");
        $mappingRaw = \Zend_Json::decode($this->getParam("mapping"));
        $class = Object\ClassDefinition::getById($this->getParam("classId"));
        $skipFirstRow = $this->getParam("skipHeadRow") == "true";
        $fields = $class->getFieldDefinitions();

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $id;

        // currently only csv supported
        // determine type
        $dialect = Tool\Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $id . "_original");

        $count = 0;
        if (($handle = fopen($file, "r")) !== false) {
            $data = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
        }
        if ($skipFirstRow && $job == 1) {
            //read the next row, we need to skip the head row
            $data = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
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
            } else if ($map[1] == "type (system)") {
                $mapping["type"] = $map[0];
            }

        }

        // create new object
        $className = "\\Pimcore\\Model\\Object\\" . ucfirst($this->getParam("className"));
        $className = Tool::getModelClassMapping($className);

        $parent = Object::getById($this->getParam("parentId"));

        $objectKey = "object_" . $job;
        if ($this->getParam("filename") == "id") {
            $objectKey = null;
        }
        else if ($this->getParam("filename") != "default") {
            $objectKey = File::getValidFilename($data[$this->getParam("filename")]);
        }

        $overwrite = false;
        if ($this->getParam("overwrite") == "true") {
            $overwrite = true;
        }

        if ($parent->isAllowed("create")) {

            $intendedPath = $parent->getFullPath() . "/" . $objectKey;

            if ($overwrite) {
                $object = Object::getByPath($intendedPath);
                if (!$object instanceof Object\Concrete) {
                    //create new object
                    $object = new $className();
                } else if ($object instanceof Object\Concrete and !($object instanceof $className)) {
                    //delete the old object it is of a different class
                    $object->delete();
                    $object = new $className();
                } else if ($object instanceof Object\Folder) {
                    //delete the folder
                    $object->delete();
                    $object = new $className();
                } else {
                    //use the existing object
                }
            } else {
                $counter = 1;
                while (Object::getByPath($intendedPath) != null) {
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
            $object->setType($data[$mapping["type"]]);

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
            } catch (\Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $object->getKey() . " - " . $e->getMessage()));
            }
        }


        $this->_helper->json(array("success" => $success));
    }



    public function exportAction()
    {

        $folder = Object::getById($this->getParam("folderId"));
        $class = Object\ClassDefinition::getById($this->getParam("classId"));

        $className = $class->getName();

        $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

        if(!empty($folder)) {
            $conditionFilters = array("o_path LIKE '" . $folder->getFullPath() . "%'");
        } else {
            $conditionFilters = array();
        }
        if ($this->getParam("filter")) {
            $conditionFilters[] = Object\Service::getFilterCondition($this->getParam("filter"), $class);
        }
        if ($this->getParam("condition")) {
            $conditionFilters[] = "(" . $this->getParam("condition") . ")";
        }

        $list = new $listClass();
        $list->setCondition(implode(" AND ", $conditionFilters));
        $list->setOrder("ASC");
        $list->setOrderKey("o_id");

        $objectType = $this->getParam("objecttype");
        if($objectType) {
            if ($objectType == Object\AbstractObject::OBJECT_TYPE_OBJECT && $class->getShowVariants()) {
                $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_VARIANT]);
            } else {
                $list->setObjectTypes(array($objectType));
            }
        }

        $list->load();

        $objects = array();
        \Logger::debug("objects in list:" . count($list->getObjects()));
        foreach ($list->getObjects() as $object) {

            if ($object instanceof Object\Concrete) {
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
     * @param Object\AbstractObject $object
     * @return array
     */
    protected function csvObjectData($object)
    {

        $o = array();
        foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
            //exclude remote owner fields
            if (!($value instanceof Object\ClassDefinition\Data\Relations\AbstractRelations and $value->isRemoteOwner())) {
                $o[$key] = $value->getForCsvExport($object);
            }

        }

        $o["id (system)"] = $object->getId();
        $o["key (system)"] = $object->getKey();
        $o["fullpath (system)"] = $object->getFullPath();
        $o["published (system)"] = $object->isPublished();
        $o["type (system)"] = $object->getType();


        return $o;
    }


    public function getBatchJobsAction()
    {

        if($this->getParam("language")) {
            $this->setLanguage($this->getParam("language"), true);
        }

        $folder = Object::getById($this->getParam("folderId"));
        $class = Object\ClassDefinition::getById($this->getParam("classId"));

        $conditionFilters = array("o_path = ? OR o_path LIKE '" . str_replace("//","/",$folder->getFullPath() . "/") . "%'");

        if ($this->getParam("filter")) {
            $conditionFilters[] = Object\Service::getFilterCondition($this->getParam("filter"), $class);
        }
        if ($this->getParam("condition")) {
            $conditionFilters[] = " AND (" . $this->getParam("condition") . ")";
        }

        $className = $class->getName();
        $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";
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
            $object = Object::getById($this->getParam("job"));

            if ($object) {
                $className = $object->getClassName();
                $class = Object\ClassDefinition::getByName($className);
                $value = $this->getParam("value");
                if ($this->getParam("valueType") == "object") {
                    $value = \Zend_Json::decode($value);
                }

                $name = $this->getParam("name");
                $parts = explode("~", $name);

                if (substr($name, 0, 1) == "~") {
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
                } else if(count($parts) > 1) {
                    // check for bricks
                    $brickType = $parts[0];
                    $brickKey = $parts[1];
                    $brickField = Object\Service::getFieldForBrickType($object->getClass(), $brickType);

                    $fieldGetter = "get" . ucfirst($brickField);
                    $brickGetter = "get" . ucfirst($brickType);
                    $valueSetter = "set" . ucfirst($brickKey);

                    $brick = $object->$fieldGetter()->$brickGetter();
                    if(empty($brick)) {
                        $classname = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($brickType);
                        $brickSetter = "set" . ucfirst($brickType);
                        $brick = new $classname($object);
                        $object->$fieldGetter()->$brickSetter($brick);
                    }

                    $brickClass = Object\Objectbrick\Definition::getByKey($brickType);
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
                } catch (\Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
            else {
                \Logger::debug("ObjectController::batchAction => There is no object left to update.");
                $this->_helper->json(array("success" => false, "message" => "ObjectController::batchAction => There is no object left to update."));
            }

        }
        catch (\Exception $e) {
            \Logger::err($e);
            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
        }

        $this->_helper->json(array("success" => $success));
    }


}
