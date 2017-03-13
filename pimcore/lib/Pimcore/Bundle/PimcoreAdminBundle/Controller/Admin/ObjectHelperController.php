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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Object;
use Pimcore\Model\Element;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/object-helper")
 */
class ObjectHelperController extends AdminController
{
    /**
     * @Route("/load-object-data")
     * @param Request $request
     * @return JsonResponse
     */
    public function loadObjectDataAction(Request $request)
    {
        $object = Object\AbstractObject::getById($request->get("id"));
        $result = [];
        if ($object) {
            $result['success'] = true;
            $fields = $request->get("fields");
            $result['fields'] = Object\Service::gridObjectData($object, $fields);
        } else {
            $result['success'] = false;
        }

        return $this->json($result);
    }


    /**
     * @Route("/grid-get-column-config")
     * @param Request $request
     * @return JsonResponse
     */
    public function gridGetColumnConfigAction(Request $request)
    {
        if ($request->get("id")) {
            $class = Object\ClassDefinition::getById($request->get("id"));
        } elseif ($request->get("name")) {
            $class = Object\ClassDefinition::getByName($request->get("name"));
        }

        $gridType = "search";
        if ($request->get("gridtype")) {
            $gridType = $request->get("gridtype");
        }

        $objectId = $request->get("objectId");

        if ($objectId) {
            $fields = Object\Service::getCustomGridFieldDefinitions($class->getId(), $objectId);
        }

        if (!$fields) {
            $fields = $class->getFieldDefinitions();
        }

        $types = [];
        if ($request->get("types")) {
            $types = explode(",", $request->get("types"));
        }

        // grid config
        $gridConfig = [];
        if ($objectId) {
            $searchType = $request->get("searchType");
            $postfix =  $searchType && $searchType != "folder" ? "_" . $request->get("searchType") : "";

            $configFiles["configFileClassUser"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $request->get("objectId") . "_" . $class->getId() . $postfix . "-user_" . $this->getUser()->getId() . ".psf";
            $configFiles["configFileUser"] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $request->get("objectId") . $postfix . "-user_" . $this->getUser()->getId() . ".psf";

            foreach ($configFiles as $configFile) {
                if (is_file($configFile)) {
                    $gridConfig = Tool\Serialize::unserialize(file_get_contents($configFile));
                    if (is_array($gridConfig) && array_key_exists("classId", $gridConfig)) {
                        if ($gridConfig["classId"] == $class->getId()) {
                            break;
                        } else {
                            $gridConfig = [];
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        $localizedFields = [];
        $objectbrickFields = [];
        foreach ($fields as $key => $field) {
            if ($field instanceof Object\ClassDefinition\Data\Localizedfields) {
                $localizedFields[] = $field;
            } elseif ($field instanceof Object\ClassDefinition\Data\Objectbricks) {
                $objectbrickFields[] = $field;
            }
        }

        $availableFields = [];
        $systemColumns = ["id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname"];
        if (empty($gridConfig)) {
            $count = 0;

            if (!$request->get("no_system_columns")) {
                $vis = $class->getPropertyVisibility();
                foreach ($systemColumns as $sc) {
                    $key = $sc;
                    if ($key == "fullpath") {
                        $key = "path";
                    }

                    if (empty($types) && ($vis[$gridType][$key] || $gridType == "all")) {
                        $availableFields[] = [
                            "key" => $sc,
                            "type" => "system",
                            "label" => $sc,
                            "position" => $count];
                        $count++;
                    }
                }
            }

            $includeBricks = !$request->get("no_brick_columns");

            foreach ($fields as $key => $field) {
                if ($field instanceof Object\ClassDefinition\Data\Localizedfields) {
                    foreach ($field->getFieldDefinitions() as $fd) {
                        if (empty($types) || in_array($fd->getFieldType(), $types)) {
                            $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $count);
                            if (!empty($fieldConfig)) {
                                $availableFields[] = $fieldConfig;
                                $count++;
                            }
                        }
                    }
                } elseif ($field instanceof Object\ClassDefinition\Data\Objectbricks && $includeBricks) {
                    if (in_array($field->getFieldType(), $types)) {
                        $fieldConfig = $this->getFieldGridConfig($field, $gridType, $count);
                        if (!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    } else {
                        $allowedTypes = $field->getAllowedTypes();
                        if (!empty($allowedTypes)) {
                            foreach ($allowedTypes as $t) {
                                $brickClass = Object\Objectbrick\Definition::getByKey($t);
                                $brickFields = $brickClass->getFieldDefinitions();
                                if (!empty($brickFields)) {
                                    foreach ($brickFields as $bf) {
                                        $fieldConfig = $this->getFieldGridConfig($bf, $gridType, $count, false, $t . "~");
                                        if (!empty($fieldConfig)) {
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
                        if (!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    }
                }
            }
        } else {
            $savedColumns = $gridConfig['columns'];
            foreach ($savedColumns as $key => $sc) {
                if (!$sc['hidden']) {
                    if (in_array($key, $systemColumns)) {
                        $colConfig = [
                            "key" => $key,
                            "type" => "system",
                            "label" => $key,
                            "position" => $sc['position']];
                        if (isset($sc['width'])) {
                            $colConfig['width'] = $sc['width'];
                        }
                        $availableFields[] = $colConfig;
                    } else {
                        $keyParts = explode("~", $key);

                        if (substr($key, 0, 1) == "~") {
                            // not needed for now
                            $type = $keyParts[1];
//                            $field = $keyParts[2];
                            $groupAndKeyId = explode("-", $keyParts[3]);
                            $keyId = $groupAndKeyId[1];

                            if ($type == "classificationstore") {
                                $keyDef = Object\Classificationstore\KeyConfig::getById($keyId);
                                if ($keyDef) {
                                    $keyFieldDef = json_decode($keyDef->getDefinition(), true);
                                    if ($keyFieldDef) {
                                        $keyFieldDef = \Pimcore\Model\Object\Classificationstore\Service::getFieldDefinitionFromJson($keyFieldDef, $keyDef->getType());
                                        $fieldConfig = $this->getFieldGridConfig($keyFieldDef, $gridType, $sc['position'], true);
                                        if ($fieldConfig) {
                                            $fieldConfig["key"] = $key;
                                            $fieldConfig["label"] = "#" . $keyFieldDef->getTitle();
                                            $availableFields[] = $fieldConfig;
                                        }
                                    }
                                }
                            }
                        } elseif (count($keyParts) > 1) {
                            $brick = $keyParts[0];
                            $key = $keyParts[1];

                            $brickClass = Object\Objectbrick\Definition::getByKey($brick);
                            $fd = $brickClass->getFieldDefinition($key);
                            if (!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, $brick . "~");
                                if (!empty($fieldConfig)) {
                                    if (isset($sc['width'])) {
                                        $fieldConfig['width'] = $sc['width'];
                                    }
                                    $availableFields[] = $fieldConfig;
                                }
                            }
                        } else {
                            $fd = $class->getFieldDefinition($key);
                            //if not found, look for localized fields
                            if (empty($fd)) {
                                foreach ($localizedFields as $lf) {
                                    $fd = $lf->getFieldDefinition($key);
                                    if (!empty($fd)) {
                                        break;
                                    }
                                }
                            }

                            if (!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true);
                                if (!empty($fieldConfig)) {
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
        usort($availableFields, function ($a, $b) {
            if ($a["position"] == $b["position"]) {
                return 0;
            }

            return ($a["position"] < $b["position"]) ? -1 : 1;
        });

        $config = \Pimcore\Config::getSystemConfig();
        $frontendLanguages = Tool\Admin::reorderWebsiteLanguages(\Pimcore\Tool\Admin::getCurrentUser(), $config->general->validLanguages);
        if ($frontendLanguages) {
            $language = explode(',', $frontendLanguages)[0];
        } else {
            $language = $this->getLanguage();
        }

        if (!Tool::isValidLanguage($language)) {
            $validLanguages = Tool::getValidLanguages();
            $language = $validLanguages[0];
        }


        if (!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }

        return $this->json([
            "sortinfo" => isset($gridConfig['sortinfo']) ? $gridConfig['sortinfo'] : false,
            "language" => $language,
            "availableFields" => $availableFields,
            "onlyDirectChildren" => isset($gridConfig['onlyDirectChildren']) ? $gridConfig['onlyDirectChildren'] : false,
            "pageSize" => isset($gridConfig['pageSize']) ? $gridConfig['pageSize'] : false
        ]);
    }


    /**
     * @Route("/grid-delete-column-config")
     * @param Request $request
     * @return JsonResponse
     */
    public function gridDeleteColumnConfigAction(Request $request)
    {
        $object = Object::getById($request->get("id"));


        if ($object->isAllowed("publish")) {
            try {
                $classId = $request->get("class_id");

                $searchType = $request->get("searchType");
                $postfix =  $searchType && $searchType != "folder" ? "_" . $request->get("searchType") : "";

                $configFiles = [];
                $configFiles[]= PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . "_" . $classId . $postfix . "-user_" . $this->getUser()->getId() . ".psf";
                $configFiles[] = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . $postfix . "-user_" . $this->getUser()->getId() . ".psf";


                foreach ($configFiles as $configFile) {
                    $configDir = dirname($configFile);
                    if (is_dir($configDir)) {
                        if (is_file($configFile)) {
                            @unlink($configFile);
                        }
                    }
                }

                return $this->json(["success" => true]);
            } catch (\Exception $e) {
                return $this->json(["success" => false, "message" => $e->getMessage()]);
            }
        }

        return $this->json(["success" => false, "message" => "missing_permission"]);
    }

    /**
     * @Route("/grid-save-column-config")
     * @param Request $request
     * @return JsonResponse
     */
    public function gridSaveColumnConfigAction(Request $request)
    {
        $object = Object::getById($request->get("id"));


        if ($object->isAllowed("publish")) {
            try {
                $classId = $request->get("class_id");

                $searchType = $request->get("searchType");
                $postfix =  $searchType && $searchType != "folder" ? "_" . $request->get("searchType") : "";

                // grid config
                $gridConfig = $this->decodeJson($request->get("gridconfig"));
                if ($classId) {
                    $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . "_" . $classId . $postfix . "-user_" . $this->getUser()->getId() . ".psf";
                } else {
                    $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/object/grid/" . $object->getId() . $postfix . "-user_" . $this->getUser()->getId() . ".psf";
                }

                $configDir = dirname($configFile);
                if (!is_dir($configDir)) {
                    File::mkdir($configDir);
                }
                File::put($configFile, Tool\Serialize::serialize($gridConfig));

                return $this->json(["success" => true]);
            } catch (\Exception $e) {
                return $this->json(["success" => false, "message" => $e->getMessage()]);
            }
        }

        return $this->json(["success" => false, "message" => "missing_permission"]);
    }

    /**
     * @param $field
     * @param $gridType
     * @param $position
     * @param bool $force
     * @param null $keyPrefix
     * @return array|null
     */
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
        if ($gridType == "search") {
            $visible = $field->getVisibleSearch();
        } elseif ($gridType == "grid") {
            $visible = $field->getVisibleGridView();
        } elseif ($gridType == "all") {
            $visible = true;
        }

        if (!$field->getInvisible() && ($force || $visible)) {
            Object\Service::enrichLayoutDefinition($field);

            return [
                "key" => $key,
                "type" => $field->getFieldType(),
                "label" => $title,
                "config" => $config,
                "layout" => $field ,
                "position" => $position
            ];
        } else {
            return null;
        }
    }

    /**
     * IMPORTER
     */

    /**
     * @Route("/import-upload")
     * @param Request $request
     * @return JsonResponse
     */
    public function importUploadAction(Request $request)
    {
        $data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $data = Tool\Text::convertToUTF8($data);

        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $request->get("id");
        File::put($importFile, $data);

        $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $request->get("id") . "_original";
        File::put($importFileOriginal, $data);

        $response = $this->json([
            "success" => true
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set("Content-Type", "text/html");

        return $response;
    }

    /**
     * @Route("/import-get-file-info")
     * @param Request $request
     * @return JsonResponse
     */
    public function importGetFileInfoAction(Request $request)
    {
        $success = true;
        $supportedFieldTypes = ["checkbox", "country", "date", "datetime", "href", "image", "input", "language", "table", "multiselect", "numeric", "password", "select", "slider", "textarea", "wysiwyg", "objects", "multihref", "geopoint", "geopolygon", "geobounds", "link", "user", "email", "gender", "firstname", "lastname", "newsletterActive", "newsletterConfirmed", "countrymultiselect", "objectsMetadata"];

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $request->get("id");

        // determine type
        $dialect = Tool\Admin::determineCsvDialect(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/import_" . $request->get("id") . "_original");

        $count = 0;
        if (($handle = fopen($file, "r")) !== false) {
            while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                if ($count == 0) {
                    $firstRowData = $rowData;
                }
                $tmpData = [];
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
        $class = Object\ClassDefinition::getById($request->get("classId"));
        $fields = $class->getFieldDefinitions();

        $availableFields = [];

        foreach ($fields as $key => $field) {
            $config = null;
            $title = $field->getName();
            if (method_exists($field, "getTitle")) {
                if ($field->getTitle()) {
                    $title = $field->getTitle();
                }
            }

            if (in_array($field->getFieldType(), $supportedFieldTypes)) {
                $availableFields[] = [$field->getName(), $title . "(" . $field->getFieldType() . ")"];
            }
        }

        $mappingStore = [];
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

            $mappingStore[] = [
                "source" => $i,
                "firstRow" => $firstRow,
                "target" => $mappedField
            ];
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

        return $this->json([
            "success" => $success,
            "dataPreview" => $data,
            "dataFields" => array_keys($data[0]),
            "targetFields" => $availableFields,
            "mappingStore" => $mappingStore,
            "rows" => $rows,
            "cols" => $cols
        ]);
    }

    /**
     * @Route("/import-process")
     * @param Request $request
     * @return JsonResponse
     */
    public function importProcessAction(Request $request)
    {
        $success = true;

        $parentId = $request->get("parentId");
        $job = $request->get("job");
        $id = $request->get("id");
        $mappingRaw = $this->decodeJson($request->get("mapping"));
        $class = Object\ClassDefinition::getById($request->get("classId"));
        $skipFirstRow = $request->get("skipHeadRow") == "true";
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
            } elseif ($map[1] == "published (system)") {
                $mapping["published"] = $map[0];
            } elseif ($map[1] == "type (system)") {
                $mapping["type"] = $map[0];
            }
        }

        // create new object
        $className = "Pimcore\\Model\\Object\\" . ucfirst($request->get("className"));
        $parent = Object::getById($request->get("parentId"));

        $objectKey = "object_" . $job;
        if ($request->get("filename") == "id") {
            $objectKey = null;
        } elseif ($request->get("filename") != "default") {
            $objectKey = Element\Service::getValidKey($data[$request->get("filename")], "object");
        }

        $overwrite = false;
        if ($request->get("overwrite") == "true") {
            $overwrite = true;
        }

        if ($parent->isAllowed("create")) {
            $intendedPath = $parent->getRealFullPath() . "/" . $objectKey;

            if ($overwrite) {
                $object = Object::getByPath($intendedPath);
                if (!$object instanceof Object\Concrete) {
                    //create new object
                    $object = \Pimcore::getDiContainer()->make($className);
                } elseif ($object instanceof Object\Concrete and !($object instanceof $className)) {
                    //delete the old object it is of a different class
                    $object->delete();
                    $object = \Pimcore::getDiContainer()->make($className);
                } elseif ($object instanceof Object\Folder) {
                    //delete the folder
                    $object->delete();
                    $object = \Pimcore::getDiContainer()->make($className);
                } else {
                    //use the existing object
                }
            } else {
                $counter = 1;
                while (Object::getByPath($intendedPath) != null) {
                    $objectKey .= "_" . $counter;
                    $intendedPath = $parent->getRealFullPath() . "/" . $objectKey;
                    $counter++;
                }
                $object = new $className();
            }
            $object->setClassId($request->get("classId"));
            $object->setClassName($request->get("className"));
            $object->setParentId($request->get("parentId"));
            $object->setKey($objectKey);
            $object->setCreationDate(time());
            $object->setUserOwner($this->getUser()->getId());
            $object->setUserModification($this->getUser()->getId());

            if (in_array($data[$mapping["type"]], ["object", "variant"])) {
                $object->setType($data[$mapping["type"]]);
            } else {
                $object->setType("object");
            }

            if ($data[$mapping["published"]] === "1") {
                $object->setPublished(true);
            } else {
                $object->setPublished(false);
            }

            foreach ($class->getFieldDefinitions() as $key => $field) {
                $value = $data[$mapping[$key]];
                if (array_key_exists($key, $mapping) and  $value != null) {
                    // data mapping
                    $value = $field->getFromCsvImport($value, $object);

                    if ($value !== null) {
                        $object->setValue($key, $value);
                    }
                }
            }

            try {
                $object->save();

                return $this->json(["success" => true]);
            } catch (\Exception $e) {
                return $this->json(["success" => false, "message" => $object->getKey() . " - " . $e->getMessage()]);
            }
        }


        return $this->json(["success" => $success]);
    }

    /**
     * @param Request $request
     * @return mixed|string
     */
    protected function extractLanguage(Request $request)
    {
        $requestedLanguage = $request->get("language");
        if ($requestedLanguage) {
            if ($requestedLanguage != "default") {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        return $requestedLanguage;
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function extractFieldsAndBricks(Request $request)
    {
        $fields = [];
        $bricks = [];
        if ($request->get("fields")) {
            $fields = $request->get("fields");

            foreach ($fields as $f) {
                $parts = explode("~", $f);
                if (substr($f, 0, 1) == "~") {
                    // key value, ignore for now
                } elseif (count($parts) > 1) {
                    $bricks[$parts[0]] = $parts[0];
                }
            }
        }

        return [$fields, $bricks];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function prepareExportList(Request $request)
    {
        $requestedLanguage = $this->extractLanguage($request);

        $folder = Object\AbstractObject::getById($request->get("folderId"));
        $class = Object\ClassDefinition::getById($request->get("classId"));

        $className = $class->getName();

        $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

        if (!empty($folder)) {
            $conditionFilters = ["o_path LIKE '" . $folder->getRealFullPath() . "%'"];
        } else {
            $conditionFilters = [];
        }

        $featureJoins = [];

        if ($request->get("filter")) {
            $conditionFilters[] = Object\Service::getFilterCondition($request->get("filter"), $class);
            $featureFilters = Object\Service::getFeatureFilters($request->get("filter"), $class);
            if ($featureFilters) {
                $featureJoins = array_merge($featureJoins, $featureFilters["joins"]);
            }
        }
        if ($request->get("condition")) {
            $conditionFilters[] = "(" . $request->get("condition") . ")";
        }

        /** @var Object\Listing\Concrete $list */
        $list = new $listClass();
        $objectTableName = $list->getDao()->getTableName();
        $list->setCondition(implode(" AND ", $conditionFilters));

        //parameters specified in the objects grid
        $ids = $request->get('ids', []);
        if (! empty($ids)) {
            //add a condition if id numbers are specified
            $list->addConditionParam("{$objectTableName}.o_id IN (" . implode(',', $ids) . ')');
        }

        $list->setOrder("ASC");
        $list->setOrderKey("o_id");

        $objectType = $request->get("objecttype");
        if ($objectType) {
            if ($objectType == Object\AbstractObject::OBJECT_TYPE_OBJECT && $class->getShowVariants()) {
                $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_VARIANT]);
            } else {
                $list->setObjectTypes([$objectType]);
            }
        }

        list($fields, $bricks) = $this->extractFieldsAndBricks($request);

        if (!empty($bricks)) {
            foreach ($bricks as $b) {
                $list->addObjectbrick($b);
            }
        }

        $list->setLocale($requestedLanguage);
        Object\Service::addGridFeatureJoins($list, $featureJoins, $class, $featureFilters, $requestedLanguage);

        return [$list, $fields, $requestedLanguage];
    }

    /**
     * @param $fileHandle
     * @return string
     */
    protected function getCsvFile($fileHandle)
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $fileHandle . ".csv";
    }

    /**
     * @Route("/get-export-jobs")
     * @param Request $request
     * @return JsonResponse
     */
    public function getExportJobsAction(Request $request)
    {
        list($list, $fields, $requestedLanguage) = $this->prepareExportList($request);

        $ids = $list->loadIdList();

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid("export-");
        file_put_contents($this->getCsvFile($fileHandle), "");

        return $this->json(["success"=>true, "jobs"=> $jobs, "fileHandle" => $fileHandle]);
    }

    /**
     * @Route("/do-export")
     * @param Request $request
     * @return JsonResponse
     */
    public function doExportAction(Request $request)
    {
        $fileHandle = \Pimcore\File::getValidFilename($request->get("fileHandle"));
        $ids = $request->get("ids");

        $class = Object\ClassDefinition::getById($request->get("classId"));
        $className = $class->getName();
        $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

        /**
         * @var $list \Pimcore\Model\Object\Listing
         */
        $list = new $listClass();
        $list->setObjectTypes(["object", "folder", "variant"]);
        $list->setCondition("o_id IN (" . implode(",", $ids) . ")");
        $list->setOrderKey(" FIELD(o_id, " . implode(",", $ids) . ")", false);

        list($fields, $bricks) = $this->extractFieldsAndBricks($request);

        $csv = $this->getCsvData($request, $list, $fields, $request->get("initial"));

        file_put_contents($this->getCsvFile($fileHandle), $csv, FILE_APPEND);

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/download-csv-file")
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function downloadCsvFileAction(Request $request)
    {
        $fileHandle = \Pimcore\File::getValidFilename($request->get("fileHandle"));
        $csvFile = $this->getCsvFile($fileHandle);
        if (file_exists($csvFile)) {
            $response = new BinaryFileResponse($csvFile);
            $response->headers->set("Content-Type", "application/csv");
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "export.csv");
            $response->deleteFileAfterSend(true);

            return $response;
        }
    }

    /**
     * @param $field
     * @return string
     */
    protected function mapFieldname($field)
    {
        if (substr($field, 0, 1) == "~") {
            $fieldParts = explode("~", $field);
            $type = $fieldParts[1];

            if ($type == "classificationstore") {
                $fieldname = $fieldParts[2];
                $groupKeyId = explode("-", $fieldParts[3]);
                $groupId = $groupKeyId[0];
                $keyId = $groupKeyId[1];

                $groupConfig = Object\Classificationstore\GroupConfig::getById($groupId);
                $keyConfig = Object\Classificationstore\KeyConfig::getById($keyId);

                $field = $fieldname . "~" . $groupConfig->getName() . "~" . $keyConfig->getName();
            }
        }

        return $field;
    }

    /**
     * @param Request $request
     * @param $list
     * @param $fields
     * @param bool $addTitles
     * @return string
     */
    protected function getCsvData(Request $request, $list, $fields, $addTitles = true)
    {
        $requestedLanguage = $this->extractLanguage($request);
        $mappedFieldnames = [];

        $objects = [];
        Logger::debug("objects in list:" . count($list->getObjects()));
        //add inherited values to objects
        Object\AbstractObject::setGetInheritedValues(true);
        foreach ($list->getObjects() as $object) {
            if ($fields) {
                $objectData = [];
                foreach ($fields as $field) {
                    $fieldData = $this->getCsvFieldData($request, $field, $object, $requestedLanguage);
                    if (!$mappedFieldnames[$field]) {
                        $mappedFieldnames[$field] = $this->mapFieldname($field);
                    }

                    $objectData[$mappedFieldnames[$field]] = $fieldData;
                }
                $objects[] = $objectData;
            } else {
                /**
                 * @extjs - TODO remove this, when old ext support is removed
                 */
                if ($object instanceof Object\Concrete) {
                    $o = $this->csvObjectData($object);
                    $objects[] = $o;
                }
            }
        }
        //create csv
        $csv = "";
        if (!empty($objects)) {
            if ($addTitles) {
                $columns = array_keys($objects[0]);
                foreach ($columns as $key => $value) {
                    $columns[$key] = '"' . $value . '"';
                }
                $csv = implode(";", $columns) . "\r\n";
            }
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

        return $csv;
    }

    /**
     * @param Request $request
     * @param $field
     * @param $object
     * @param $requestedLanguage
     * @return mixed
     */
    protected function getCsvFieldData(Request $request, $field, $object, $requestedLanguage)
    {

        //check if field is systemfield
        $systemFieldMap = [
            'id' => "getId",
            'fullpath' => "getRealFullPath",
            'published' => "getPublished",
            'creationDate' => "getCreationDate",
            'modificationDate' => "getModificationDate",
            'filename' => "getKey",
            'classname' => "getClassname"
        ];
        if (in_array($field, array_keys($systemFieldMap))) {
            return $object->{$systemFieldMap[$field]}();
        } else {
            //check if field is standard object field
            $fieldDefinition = $object->getClass()->getFieldDefinition($field);
            if ($fieldDefinition) {
                return $fieldDefinition->getForCsvExport($object);
            } else {
                $fieldParts = explode("~", $field);

                // check for objects bricks and localized fields
                if (substr($field, 0, 1) == "~") {
                    $type = $fieldParts[1];

                    if ($type == "classificationstore") {
                        $fieldname = $fieldParts[2];
                        $groupKeyId = explode("-", $fieldParts[3]);
                        $groupId = $groupKeyId[0];
                        $keyId = $groupKeyId[1];
                        $getter = "get" . ucfirst($fieldname);
                        if (method_exists($object, $getter)) {
                            /** @var  $classificationStoreData Classificationstore */
                            $keyConfig = Object\Classificationstore\KeyConfig::getById($keyId);
                            $type = $keyConfig->getType();
                            $definition = json_decode($keyConfig->getDefinition());
                            $fieldDefinition = \Pimcore\Model\Object\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                            return $fieldDefinition->getForCsvExport($object,
                                ["context" => [
                                    "containerType" => "classificationstore",
                                    "fieldname" => $fieldname,
                                    "groupId" => $groupId,
                                    "keyId" => $keyId,
                                    "language" => $requestedLanguage
                                ]]
                            );
                        }
                    }
                    //key value store - ignore for now
                } elseif (count($fieldParts) > 1) {
                    // brick
                    $brickType = $fieldParts[0];
                    $brickKey = $fieldParts[1];
                    $key = Object\Service::getFieldForBrickType($object->getClass(), $brickType);

                    $brickClass = Object\Objectbrick\Definition::getByKey($brickType);
                    $fieldDefinition = $brickClass->getFieldDefinition($brickKey);

                    if ($fieldDefinition) {
                        $brickContainer = $object->{"get".ucfirst($key)}();
                        if ($brickContainer && !empty($brickKey)) {
                            $brick = $brickContainer->{"get".ucfirst($brickType)}();
                            if ($brick) {
                                return $fieldDefinition->getForCsvExport($brick);
                            }
                        }
                    }
                } elseif ($locFields = $object->getClass()->getFieldDefinition("localizedfields")) {

                    // if the definition is not set try to get the definition from localized fields
                    $fieldDefinition = $locFields->getFieldDefinition($field);
                    if ($fieldDefinition) {
                        $needLocalizedPermissions = true;

                        return $fieldDefinition->getForCsvExport($object->getLocalizedFields(), ["language" => $request->get("language")]);
                    }
                }
            }
        }
    }

    /**
     * @extjs - TODO remove this, when old ext support is removed
     */
    /**
     * Flattens object data to an array with key=>value where
     * value is simply a string representation of the value (for objects, hrefs and assets the full path is used)
     *
     * @param Object\AbstractObject $object
     * @return array
     */
    protected function csvObjectData($object)
    {
        $o = [];
        foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
            //exclude remote owner fields
            if (!($value instanceof Object\ClassDefinition\Data\Relations\AbstractRelations and $value->isRemoteOwner())) {
                $o[$key] = $value->getForCsvExport($object);
            }
        }

        $o["id (system)"] = $object->getId();
        $o["key (system)"] = $object->getKey();
        $o["fullpath (system)"] = $object->getRealFullPath();
        $o["published (system)"] = $object->isPublished();
        $o["type (system)"] = $object->getType();


        return $o;
    }

    /**
     * @Route("/get-batch-jobs")
     * @param Request $request
     * @return JsonResponse
     */
    public function getBatchJobsAction(Request $request)
    {
        if ($request->get("language")) {
            $request->setLocale($request->get("language"));
        }

        $folder = Object::getById($request->get("folderId"));
        $class = Object\ClassDefinition::getById($request->get("classId"));

        $conditionFilters = ["o_path = ? OR o_path LIKE '" . str_replace("//", "/", $folder->getRealFullPath() . "/") . "%'"];

        if ($request->get("filter")) {
            $conditionFilters[] = Object\Service::getFilterCondition($request->get("filter"), $class);
        }
        if ($request->get("condition")) {
            $conditionFilters[] = " (" . $request->get("condition") . ")";
        }

        $className = $class->getName();
        $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";
        $list = new $listClass();
        $list->setCondition(implode(" AND ", $conditionFilters), [$folder->getRealFullPath()]);
        $list->setOrder("ASC");
        $list->setOrderKey("o_id");

        if ($request->get("objecttype")) {
            $list->setObjectTypes([$request->get("objecttype")]);
        }

        $jobs = $list->loadIdList();

        return $this->json(["success"=>true, "jobs"=>$jobs]);
    }

    /**
     * @Route("/batch")
     * @param Request $request
     * @return JsonResponse
     */
    public function batchAction(Request $request)
    {
        $success = true;

        try {
            $object = Object::getById($request->get("job"));

            if ($object) {
                $className = $object->getClassName();
                $class = Object\ClassDefinition::getByName($className);
                $value = $request->get("value");
                if ($request->get("valueType") == "object") {
                    $value = $this->decodeJson($value);
                }

                $name = $request->get("name");
                $parts = explode("~", $name);

                if (count($parts) > 1) {
                    // check for bricks
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

                    $brickClass = Object\Objectbrick\Definition::getByKey($brickType);
                    $field = $brickClass->getFieldDefinition($brickKey);
                    $brick->$valueSetter($field->getDataFromEditmode($value, $object));
                } else {
                    // everything else
                    $field = $class->getFieldDefinition($name);
                    if ($field) {
                        $object->setValue($name, $field->getDataFromEditmode($value, $object));
                    } else {
                        // check if it is a localized field
                        if ($request->get("language")) {
                            $localizedField = $class->getFieldDefinition("localizedfields");
                            if ($localizedField) {
                                $field = $localizedField->getFieldDefinition($name);
                                if ($field) {
                                    /** @var $field Object\ClassDefinition\Data */
                                    $object->{"set" . $name}($field->getDataFromEditmode($value, $object), $request->get("language"));
                                }
                            }
                        }

                        // seems to be a system field, this is actually only possible for the "published" field yet
                        if ($name == "published") {
                            if ($value == "false" || empty($value)) {
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
                    return $this->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                Logger::debug("ObjectController::batchAction => There is no object left to update.");

                return $this->json(["success" => false, "message" => "ObjectController::batchAction => There is no object left to update."]);
            }
        } catch (\Exception $e) {
            Logger::err($e);

            return $this->json(["success" => false, "message" => $e->getMessage()]);
        }

        return $this->json(["success" => $success]);
    }
}
