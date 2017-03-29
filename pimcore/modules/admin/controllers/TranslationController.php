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
use Pimcore\Model\Translation;
use Pimcore\Model\Object;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model;
use Pimcore\Logger;

class Admin_TranslationController extends \Pimcore\Controller\Action\Admin
{
    const SELFCLOSING_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    public function importAction()
    {
        $this->checkPermission("translations");

        $admin = $this->getParam("admin");
        $merge = $this->getParam("merge");

        $tmpFile = $_FILES["Filedata"]["tmp_name"];

        $overwrite = $merge ? false : true;

        if ($admin) {
            $delta = Translation\Admin::importTranslationsFromFile($tmpFile, $overwrite, Tool\Admin::getLanguages());
        } else {
            $delta = Translation\Website::importTranslationsFromFile($tmpFile, $overwrite, $this->getUser()->getAllowedLanguagesForEditingWebsiteTranslations());
        }

        $result =[
            "success" => true
        ];
        if ($merge) {
            $enrichedDelta = [];

            foreach ($delta as $item) {
                $lg = $item["lg"];
                $item["lgname"] =  \Zend_Locale::getTranslation($lg, "language");
                $item["icon"] = "/admin/misc/get-language-flag?language=" . $lg;
                $item["current"] = $item["text"];
                $enrichedDelta[]= $item;
            }

            $result["delta"] = base64_encode(json_encode($enrichedDelta));
        }

        $this->_helper->json($result, false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function exportAction()
    {
        $this->checkPermission("translations");
        $admin = $this->getParam("admin");

        if ($admin) {
            $class = "\\Pimcore\\Model\\Translation\\Admin";
        } else {
            $class = "\\Pimcore\\Model\\Translation\\Website";
        }

        $tableName = call_user_func($class . "\\Dao::getTableName");

        // clear translation cache
        Translation\AbstractTranslation::clearDependentCache();

        if ($admin) {
            $list = new Translation\Admin\Listing();
        } else {
            $list = new Translation\Website\Listing();
        }

        $joins = [];


        $list->setOrder("asc");
        $list->setOrderKey($tableName . ".key", false);

        $condition = $this->getGridFilterCondition($tableName);
        if ($condition) {
            $list->setCondition($condition);
        }

        $filters = $this->getGridFilterCondition($tableName, true);

        if ($filters) {
            $joins = array_merge($joins, $filters["joins"]);
        }

        $this->extendTranslationQuery($joins, $list, $tableName, $filters);
        $list->load();

        $translations = [];
        $translationObjects = $list->getTranslations();

        // fill with one dummy translation if the store is empty
        if (empty($translationObjects)) {
            if ($admin) {
                $t = new Translation\Admin();
                $languages = Tool\Admin::getLanguages();
            } else {
                $t = new Translation\Website();
                $languages = $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations();
            }

            foreach ($languages as $language) {
                $t->addTranslation($language, "");
            }

            $translationObjects[] = $t;
        }


        foreach ($translationObjects as $t) {
            $translations[] = array_merge(["key" => $t->getKey(),
                "creationDate" => $t->getCreationDate(),
                "modificationDate" => $t->getModificationDate(),
            ], $t->getTranslations());
        }

        //header column
        $columns = array_keys($translations[0]);

        if ($admin) {
            $languages = Tool\Admin::getLanguages();
        } else {
            $languages = $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations();
        }

        //add language columns which have no translations yet
        foreach ($languages as $l) {
            if (!in_array($l, $columns)) {
                $columns[] = $l;
            }
        }

        //remove invalid languages
        foreach ($columns as $key => $column) {
            if (strtolower(trim($column)) != 'key' && !in_array($column, $languages)) {
                unset($columns[$key]);
            }
        }
        $columns = array_values($columns);

        $headerRow = [];
        foreach ($columns as $key => $value) {
            $headerRow[] = '"' . $value . '"';
        }
        $csv = implode(";", $headerRow) . "\r\n";

        foreach ($translations as $t) {
            $tempRow = [];
            foreach ($columns as $key) {
                $value = $t[$key];
                //clean value of evil stuff such as " and linebreaks
                if (is_string($value)) {
                    $value = Tool\Text::removeLineBreaks($value);
                    $value = str_replace('"', '&quot;', $value);

                    $tempRow[$key] = '"' . $value . '"';
                } else {
                    $tempRow[$key] = $value;
                }
            }
            $csv .= implode(";", $tempRow) . "\r\n";
        }

        $suffix = $admin ? "admin" : "website";
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"export_ " . $suffix . "_translations.csv\"");
        ini_set('display_errors', false); //to prevent warning messages in csv
        echo "\xEF\xBB\xBF";
        echo $csv;
        die();
    }

    public function addAdminTranslationKeysAction()
    {
        $this->removeViewRenderer();

        $keys = $this->getParam("keys");
        if ($keys) {
            $availableLanguages = Tool\Admin::getLanguages();
            $data = \Zend_Json_Decoder::decode($keys);
            foreach ($data as $translationData) {
                $t = null; // reset

                try {
                    $t = Translation\Admin::getByKey($translationData);
                } catch (\Exception $e) {
                    Logger::log($e);
                }
                if (!$t instanceof Translation\Admin) {
                    $t = new Translation\Admin();

                    $t->setKey($translationData);
                    $t->setCreationDate(time());
                    $t->setModificationDate(time());

                    foreach ($availableLanguages as $lang) {
                        $t->addTranslation($lang, "");
                    }

                    try {
                        $t->save();
                    } catch (\Exception $e) {
                        Logger::log($e);
                    }
                }
            }
        }
    }

    public function translationsAction()
    {
        $admin = $this->getParam("admin");

        if ($admin) {
            $class = "\\Pimcore\\Model\\Translation\\Admin";
            $this->checkPermission("translations_admin");
        } else {
            $class = "\\Pimcore\\Model\\Translation\\Website";
            $this->checkPermission("translations");
        }

        $tableName = call_user_func($class . "\\Dao::getTableName");

        // clear translation cache
        Translation\Website::clearDependentCache();

        if ($this->getParam("data")) {
            $data = \Zend_Json::decode($this->getParam("data"));

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                if (\Pimcore\Tool\Admin::isExtJS6()) {
                    $t = $class::getByKey($data["key"]);
                } else {
                    $t = $class::getByKey($data);
                }
                $t->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $t = $class::getByKey($data["key"]);

                foreach ($data as $key => $value) {
                    if ($key != "key") {
                        $t->addTranslation($key, $value);
                    }
                }

                if ($data["key"]) {
                    $t->setKey($data["key"]);
                }
                $t->setModificationDate(time());
                $t->save();

                $return = array_merge(["key" => $t->getKey(),
                    "creationDate" => $t->getCreationDate(),
                    "modificationDate" => $t->getModificationDate()],
                    $t->getTranslations());

                $this->_helper->json(["data" => $return, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                try {
                    $t = $class::getByKey($data["key"]);
                } catch (\Exception $e) {
                    $t = new $class();

                    $t->setKey($data["key"]);
                    $t->setCreationDate(time());
                    $t->setModificationDate(time());

                    foreach (Tool::getValidLanguages() as $lang) {
                        $t->addTranslation($lang, "");
                    }
                    $t->save();
                }

                $return = array_merge([
                    "key" => $t->getKey(),
                    "creationDate" => $t->getCreationDate(),
                    "modificationDate" => $t->getModificationDate(),
                ], $t->getTranslations());

                $this->_helper->json(["data" => $return, "success" => true]);
            }
        } else {
            // get list of types
            if ($admin) {
                $list = new Translation\Admin\Listing();
            } else {
                $list = new Translation\Website\Listing();
            }

            $validLanguages = $admin ? Tool\Admin::getLanguages() : $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations();

            $list->setOrder("asc");
            $list->setOrderKey($tableName . ".key", false);

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());

            $joins = [];

            if ($sortingSettings['orderKey']) {
                if (in_array($sortingSettings['orderKey'], $validLanguages)) {
                    $joins[] = [
                        "language" => $sortingSettings['orderKey']
                    ];
                    $list->setOrderKey($sortingSettings['orderKey']);
                } else {
                    $list->setOrderKey($tableName . "." . $sortingSettings['orderKey'], false);
                }
            }
            if ($sortingSettings['order']) {
                $list->setOrder($sortingSettings['order']);
            }

            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $condition = $this->getGridFilterCondition($tableName);
            $filters = $this->getGridFilterCondition($tableName, true);

            if ($filters) {
                $joins = array_merge($joins, $filters["joins"]);
            }
            if ($condition) {
                $list->setCondition($condition);
            }

            $this->extendTranslationQuery($joins, $list, $tableName, $filters);

            $list->load();

            $translations = [];
            foreach ($list->getTranslations() as $t) {
                $translations[] = array_merge($t->getTranslations(), ["key" => $t->getKey(),
                    "creationDate" => $t->getCreationDate(),
                    "modificationDate" => $t->getModificationDate()]);
            }

            $this->_helper->json(["data" => $translations, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    /**
     * @param $joins
     * @param $list
     * @param $tableName
     * @param $filters
     */
    protected function extendTranslationQuery($joins, $list, $tableName, $filters)
    {
        if ($joins) {
            $list->onCreateQuery(function (\Zend_Db_Select $select) use ($list, $joins, $tableName, $filters) {
                $db = \Pimcore\Db::get();

                $alreadyJoined = [];

                foreach ($joins as $join) {
                    $fieldname = $join["language"];

                    if ($alreadyJoined[$fieldname]) {
                        continue;
                    }
                    $alreadyJoined[$fieldname] = 1;

                    $select->joinLeft(
                        [$fieldname => $tableName],
                        "("
                        . $fieldname . ".key = " . $tableName . ".key"
                        . " and " . $fieldname . ".language = ". $db->quote($fieldname)
                        . ")",
                        [
                            $fieldname => "text"
                        ]
                    );
                }

                $havings = $filters["conditions"];
                if ($havings) {
                    $havings = implode(" AND ", $havings);
                    $select->having($havings);
                }
            }
            );
        }
    }

    /**
     * @param $tableName
     * @param bool $languageMode
     * @return array|null|string
     */
    protected function getGridFilterCondition($tableName, $languageMode = false)
    {
        $joins = [];
        $conditions = [];
        $validLanguages = $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations();
        ;

        $db = \Pimcore\Db::get();
        $conditionFilters = [];

        $filterJson = $this->getParam("filter");
        if ($filterJson) {
            $isExtJs6 = \Pimcore\Tool\Admin::isExtJS6();
            if ($isExtJs6) {
                $propertyField = "property";
                $operatorField = "operator";
            } else {
                $propertyField = "field";
                $operatorField = "comparison";
            }

            $filters = \Zend_Json::decode($filterJson);
            foreach ($filters as $filter) {
                $operator = "=";
                $field = null;
                $value = null;

                if (!$languageMode && in_array($filter[$propertyField], $validLanguages)
                    || $languageMode && !in_array($filter[$propertyField], $validLanguages)) {
                    continue;
                }

                if ($languageMode) {
                    $fieldname = $filter[$propertyField];
                } else {
                    $fieldname = $tableName . "." . $filter[$propertyField];
                }

                if ($filter["type"] == "string") {
                    $operator = "LIKE";
                    $field = $fieldname;
                    $value = "%" . $filter["value"] . "%";
                } elseif ($filter["type"] == "date" ||
                    ($isExtJs6 && in_array($fieldname, ["modificationDate", "creationDate"]))) {
                    if ($filter[$operatorField] == "lt") {
                        $operator = "<";
                    } elseif ($filter[$operatorField] == "gt") {
                        $operator = ">";
                    } elseif ($filter[$operatorField] == "eq") {
                        $operator = "=";
                        $fieldname = "UNIX_TIMESTAMP(DATE(FROM_UNIXTIME({$fieldname})))";
                    }
                    $filter["value"] = strtotime($filter["value"]);
                    $field = $fieldname;
                    $value = $filter["value"];
                }

                if ($field && $value) {
                    $condition = $field . " " . $operator . " " . $db->quote($value);

                    if ($languageMode) {
                        $conditions[$filter[$propertyField]] = $condition;
                        $joins[] =  [
                            "language" => $filter[$propertyField]
                        ];
                    } else {
                        $conditionFilters[] = $condition;
                    }
                }
            }
        }

        if ($this->getParam("searchString")) {
            $filterTerm = $db->quote("%".mb_strtolower($this->getParam("searchString"))."%");
            $conditionFilters[] = "(lower(" .$tableName . ".key) LIKE " . $filterTerm . " OR lower(" . $tableName . ".text) LIKE " . $filterTerm.")";
        }

        if ($languageMode) {
            $result = [
                "joins" => $joins,
                "conditions" => $conditions
            ];

            return $result;
        } else {
            if (!empty($conditionFilters)) {
                return implode(" AND ", $conditionFilters);
            }

            return null;
        }
    }

    public function cleanupAction()
    {
        $listClass = "\\Pimcore\\Model\\Translation\\" . ucfirst($this->getParam("type")) . "\\Listing";
        if (Tool::classExists($listClass)) {
            $list = new $listClass();
            $list->cleanup();

            \Pimcore\Cache::clearTags(["translator", "translate"]);

            $this->_helper->json(["success" => true]);
        }

        $this->_helper->json(["success" => false]);
    }


    /**
     * THE FOLLOWING ISN'T RELATED TO THE SHARED TRANSLATIONS OR ADMIN-TRANSLATIONS
     * XLIFF CONTENT-EXPORT & MS WORD CONTENT-EXPORT
     */
    public function contentExportJobsAction()
    {
        $data = \Zend_Json::decode($this->getParam("data"));
        $elements = [];
        $jobs = [];
        $exportId = uniqid();
        $source = $this->getParam("source");
        $target = $this->getParam("target");
        $type = $this->getParam("type");

        // XLIFF requires region in language code
        /*$languages = \Zend_Locale::getLocaleList();
        if(strlen($source) < 5) {
            foreach ($languages as $key => $value) {
                if(strlen($key) > 4 && strpos($key, $source . "_") === 0) {
                    $source = $key;
                    break;
                }
            }
        }

        if(strlen($target) < 5) {
            foreach ($languages as $key => $value) {
                if(strlen($key) > 4 && strpos($key, $target . "_") === 0) {
                    $target = $key;
                    break;
                }
            }
        }*/

        $source = str_replace("_", "-", $source);
        $target = str_replace("_", "-", $target);

        if ($data && is_array($data)) {
            foreach ($data as $element) {
                $elements[$element["type"] . "_" . $element["id"]] = [
                    "id" => $element["id"],
                    "type" => $element["type"]
                ];

                if ($element["children"]) {
                    $el = Element\Service::getElementById($element["type"], $element["id"]);
                    $listClass = "\\Pimcore\\Model\\" . ucfirst($element["type"]) . "\\Listing";
                    $list = new $listClass();
                    $list->setUnpublished(true);
                    if ($el instanceof Object\AbstractObject) {
                        // inlcude variants
                        $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_VARIANT, Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER]);
                    }
                    $list->setCondition(($el instanceof Object\AbstractObject ? "o_" : "") . "path LIKE ?", [$el->getRealFullPath() . ($el->getRealFullPath() != "/" ? "/" : "") . "%"]);
                    $idList = $list->loadIdList();

                    foreach ($idList as $id) {
                        $elements[$element["type"] . "_" . $id] = [
                            "id" => $id,
                            "type" => $element["type"]
                        ];
                    }
                }
            }
        }

        $elements = array_values($elements);

        $elementsPerJob = 10;
        if ($type == "word") {
            // the word export can only handle one document per request
            // the problem is Document\Service::render(), ... in the action can be a $this->redirect() or exit;
            // nobody knows what's happening in an action ;-) So we need to isolate them in isolated processes
            // so that the export doesn't stop completely after a "redirect" or any other unexpected behavior of an action
            $elementsPerJob = 1;
        }

        // one job = X elements
        $elements = array_chunk($elements, $elementsPerJob);
        foreach ($elements as $chunk) {
            $jobs[] = [[
                "url" => "/admin/translation/" . $type . "-export",
                "params" => [
                    "id" => $exportId,
                    "source" => $source,
                    "target" => $target,
                    "data" => \Zend_Json::encode($chunk)
                ]
            ]];
        }

        $this->_helper->json([
            "success" => true,
            "jobs" => $jobs,
            "id" => $exportId
        ]);
    }

    public function xliffExportAction()
    {
        $id = $this->getParam("id");
        $data = \Zend_Json::decode($this->getParam("data"));
        $source = $this->getParam("source");
        $target = $this->getParam("target");

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";
        if (!is_file($exportFile)) {
            // create initial xml file structure
            File::put($exportFile, '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<xliff version="1.2"></xliff>');
        }

        $xliff = simplexml_load_file($exportFile, null, LIBXML_NOCDATA);

        foreach ($data as $el) {
            $element = Element\Service::getElementById($el["type"], $el["id"]);
            $file = $xliff->addChild('file');
            $file->addAttribute('original', Element\Service::getElementType($element) . '-' . $element->getId());
            $file->addAttribute('source-language', $source);
            $file->addAttribute('target-language', $target);
            $file->addAttribute('datatype', "html");
            $file->addAttribute('tool', "pimcore");
            $file->addAttribute('category', Element\Service::getElementType($element));

            $header = $file->addChild('header');

            $body = $file->addChild('body');
            $addedElements = false;

            // elements
            if ($element instanceof Document) {
                $elements = [];

                $doc = $element;

                // get also content of inherited document elements
                while ($doc) {
                    if (method_exists($doc, "getElements")) {
                        $elements = array_merge($elements, $doc->getElements());
                    }

                    if (method_exists($doc, "getContentMasterDocument")) {
                        $doc = $doc->getContentMasterDocument();
                    } else {
                        $doc = null;
                    }
                }

                foreach ($elements as $tag) {
                    if (in_array($tag->getType(), ["wysiwyg", "input", "textarea", "image", "link"])) {
                        if (in_array($tag->getType(), ["image", "link"])) {
                            $content = $tag->getText();
                        } else {
                            $content = $tag->getData();
                        }

                        if (is_string($content)) {
                            $contentCheck = trim(strip_tags($content));
                            if (!empty($contentCheck)) {
                                $this->addTransUnitNode($body, "tag~-~" . $tag->getName(), $content, $source);
                                $addedElements = true;
                            }
                        }
                    }
                }


                if ($element instanceof Document\Page) {
                    $data = [
                        "title" => $element->getTitle(),
                        "description" => $element->getDescription()
                    ];

                    foreach ($data as $key => $content) {
                        if (!empty($content)) {
                            $this->addTransUnitNode($body, "settings~-~" . $key, $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            } elseif ($element instanceof Object\Concrete) {
                if ($fd = $element->getClass()->getFieldDefinition("localizedfields")) {
                    $definitions = $fd->getFielddefinitions();

                    $locale = new \Zend_Locale(str_replace("-", "_", $source));
                    if (Tool::isValidLanguage((string) $locale)) {
                        $locale = (string) $locale;
                    } else {
                        $locale = $locale->getLanguage();
                    }

                    foreach ($definitions as $definition) {

                        // check allowed datatypes
                        if (!in_array($definition->getFieldtype(), ["input", "textarea", "wysiwyg"])) {
                            continue;
                        }

                        $content = $element->{"get" . ucfirst($definition->getName())}($locale);

                        if (!empty($content)) {
                            $this->addTransUnitNode($body, "localizedfield~-~" . $definition->getName(), $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            }

            // properties
            $properties = $element->getProperties();
            if (is_array($properties)) {
                foreach ($properties as $property) {
                    if ($property->getType() == "text" && !$property->isInherited()) {

                        // exclude text properties
                        if ($element instanceof Document) {
                            if (in_array($property->getName(), [
                                "language",
                                "navigation_target",
                                "navigation_exclude",
                                "navigation_class",
                                "navigation_anchor",
                                "navigation_parameters",
                                "navigation_relation",
                                "navigation_accesskey",
                                "navigation_tabindex"])) {
                                continue;
                            }
                        }

                        $content = $property->getData();
                        if (!empty($content)) {
                            $this->addTransUnitNode($body, "property~-~" . $property->getName(), $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            }

            // remove file if it is empty
            if (!$addedElements) {
                $file = dom_import_simplexml($file);
                $file->parentNode->removeChild($file);
            }
        }

        $xliff->asXML($exportFile);

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function xliffExportDownloadAction()
    {
        $id = $this->getParam("id");
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";

        header("Content-Type: application/x-xliff+xml");
        header('Content-Disposition: attachment; filename="' . basename($exportFile) . '"'); while (@ob_end_flush());
        flush();

        readfile($exportFile);
        @unlink($exportFile);
        exit;
    }

    public function xliffImportUploadAction()
    {
        $jobs = [];
        $id = uniqid();
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";
        copy($_FILES["file"]["tmp_name"], $importFile);

        $xliff = simplexml_load_file($importFile, null, LIBXML_NOCDATA);
        $steps = count($xliff->file);

        for ($i=0; $i<$steps; $i++) {
            $jobs[] = [[
                "url" => "/admin/translation/xliff-import-element",
                "params" => [
                    "id" => $id,
                    "step" => $i
                ]
            ]];
        }

        $this->_helper->json([
            "success" => true,
            "jobs" => $jobs,
            "id" => $id
        ], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function xliffImportElementAction()
    {
        include_once("simple_html_dom.php");

        $id = $this->getParam("id");
        $step = $this->getParam("step");
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".xliff";

        $xliff = simplexml_load_file($importFile, null, LIBXML_NOCDATA);
        $file = $xliff->file[(int)$step];
        $target = $file["target-language"];

        // see https://en.wikipedia.org/wiki/IETF_language_tag
        $target = str_replace("-", "_", $target);

        if (!Tool::isValidLanguage($target)) {
            $locale = new \Zend_Locale($target);
            $target = $locale->getLanguage();
            if (!Tool::isValidLanguage($target)) {
                $this->_helper->json([
                    "success" => false
                ]);
            }
        }

        list($type, $id) = explode("-", $file["original"]);
        $element = Element\Service::getElementById($type, $id);

        if ($element) {
            foreach ($file->body->{"trans-unit"} as $transUnit) {
                list($fieldType, $name) = explode("~-~", $transUnit["id"]);
                $content = $transUnit->target->asXml();
                $content = $this->unescapeXliff($content);

                if ($element instanceof Document) {
                    if ($fieldType == "tag" && method_exists($element, "getElement")) {
                        $tag = $element->getElement($name);
                        if ($tag) {
                            if (in_array($tag->getType(), ["image", "link"])) {
                                $tag->setText($content);
                            } else {
                                $tag->setDataFromEditmode($content);
                            }

                            $tag->setInherited(false);
                            $element->setElement($tag->getName(), $tag);
                        }
                    }

                    if ($fieldType == "settings" && $element instanceof Document\Page) {
                        $setter = "set" . ucfirst($name);
                        if (method_exists($element, $setter)) {
                            $element->$setter($content);
                        }
                    }
                } elseif ($element instanceof Object\Concrete) {
                    if ($fieldType == "localizedfield") {
                        $setter = "set" . ucfirst($name);
                        if (method_exists($element, $setter)) {
                            $element->$setter($content, $target);
                        }
                    }
                }

                if ($fieldType == "property") {
                    $property = $element->getProperty($name, true);
                    if ($property) {
                        $property->setData($content);
                    } else {
                        $element->setProperty($name, "text", $content);
                    }
                }
            }

            try {
                // allow to save objects although there are mandatory fields
                if ($element instanceof Object\AbstractObject) {
                    $element->setOmitMandatoryCheck(true);
                }

                $element->save();
            } catch (\Exception $e) {
                throw new \Exception("Unable to save " . Element\Service::getElementType($element) . " with id " . $element->getId() . " because of the following reason: " . $e->getMessage());
            }
        } else {
            Logger::error("Could not resolve element " . $file["original"]);
        }

        $this->_helper->json([
            "success" => true
        ]);
    }

    /**
     * @param $xml
     * @param $name
     * @param $content
     * @param $source
     */
    protected function addTransUnitNode($xml, $name, $content, $source)
    {
        $transUnit = $xml->addChild('trans-unit');
        $transUnit->addAttribute("id", htmlentities($name));

        $sourceNode = $transUnit->addChild('source');
        $sourceNode->addAttribute("xmlns:xml:lang", $source);

        $node = dom_import_simplexml($sourceNode);
        $no = $node->ownerDocument;
        $f = $no->createDocumentFragment();
        $f->appendXML($this->escapeXliff($content));
        @$node->appendChild($f);
    }

    /**
     * @param $content
     * @return mixed|string
     */
    protected function unescapeXliff($content)
    {
        $content = preg_replace("/<\/?(target|mrk)([^>.]+)?>/i", "", $content);
        // we have to do this again but with html entities because of CDATA content
        $content = preg_replace("/&lt;\/?(target|mrk)((?!&gt;).)*&gt;/i", "", $content);

        if (preg_match("/<\/?(bpt|ept)/", $content)) {
            $xml = str_get_html($content);
            if ($xml) {
                $els = $xml->find("bpt,ept,ph");
                foreach ($els as $el) {
                    $content = html_entity_decode($el->innertext, null, "UTF-8");
                    $el->outertext = $content;
                }
            }
            $content = $xml->save();
        }

        return $content;
    }

    /**
     * @param $content
     * @return mixed|string
     */
    protected function escapeXliff($content)
    {
        $count = 1;
        $openTags = [];
        $final = [];

        // remove nasty device control characters
        $content = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        $replacement = ['%_%_%lt;%_%_%', '%_%_%gt;%_%_%'];
        $content = str_replace(['&lt;', '&gt;'], $replacement, $content);
        $content = html_entity_decode($content, null, "UTF-8");

        if (!preg_match_all("/<([^>]+)>([^<]+)?/", $content, $matches)) {
            // return original content if it doesn't contain HTML tags
            return '<![CDATA[' . $content . ']]>';
        }

        foreach ($matches[0] as $match) {
            $parts = explode(">", $match);
            $parts[0] .= ">";
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    if (preg_match("/<([a-z0-9\/]+)/", $part, $tag)) {
                        $tagName = str_replace("/", "", $tag[1]);
                        if (strpos($tag[1], "/") === false) {
                            if (in_array($tagName, self::SELFCLOSING_TAGS)) {
                                $part = '<ph id="' . $count . '"><![CDATA[' . $part . ']]></ph>';
                            } else {
                                $openTags[$count] = ["tag" => $tagName, "id" => $count];
                                $part = '<bpt id="' . $count . '"><![CDATA[' . $part . ']]></bpt>';
                            }

                            $count++;
                        } else {
                            $closingTag = array_pop($openTags);
                            $part = '<ept id="' . $closingTag["id"] . '"><![CDATA[' . $part . ']]></ept>';
                        }
                    } else {
                        $part = str_replace($replacement, ['<', '>'], $part);
                        $part = '<![CDATA[' . $part . ']]>';
                    }

                    if (!empty($part)) {
                        $final[] = $part;
                    }
                }
            }
        }

        $content = implode("", $final);

        return $content;
    }


    public function wordExportAction()
    {

        //error_reporting(E_ERROR);
        //ini_set("display_errors", "off");

        $id = $this->getParam("id");
        $data = \Zend_Json::decode($this->getParam("data"));
        $source = $this->getParam("source");

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".html";
        if (!is_file($exportFile)) {
            File::put($exportFile, '<style type="text/css">' . file_get_contents(PIMCORE_PATH . "/static6/css/word-export.css") . '</style>');
        }

        foreach ($data as $el) {
            try {
                $element = Element\Service::getElementById($el["type"], $el["id"]);
                $output = "";

                // check supported types (subtypes)
                if (!in_array($element->getType(), ["page", "snippet", "email", "object"])) {
                    continue;
                }

                if ($element instanceof Element\ElementInterface) {
                    $output .= '<h1 class="element-headline">' . ucfirst($element->getType()) . " - " . $element->getRealFullPath() . ' (ID: ' . $element->getId() . ')</h1>';
                }

                if ($element instanceof Document\PageSnippet) {
                    if ($element instanceof Document\Page) {
                        $structuredDataEmpty = true;
                        $structuredData = '
                            <table border="1" cellspacing="0" cellpadding="5">
                                <tr>
                                    <td colspan="2"><span style="color:#cc2929;font-weight: bold;">Structured Data</span></td>
                                </tr>
                        ';

                        if ($element->getTitle()) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Title</span></td>
                                    <td>' . $element->getTitle() . '&nbsp;</td>
                                </tr>';
                            $structuredDataEmpty = false;
                        }

                        if ($element->getDescription()) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Description</span></td>
                                    <td>' . $element->getDescription() . '&nbsp;</td>
                                </tr>';
                            $structuredDataEmpty = false;
                        }

                        if ($element->getProperty("navigation_name")) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Navigation</span></td>
                                    <td>' . $element->getProperty("navigation_name") . '&nbsp;</td>
                                </tr>';
                            $structuredDataEmpty = false;
                        }

                        $structuredData .= '</table>';

                        if (!$structuredDataEmpty) {
                            $output .= $structuredData;
                        }
                    }


                    // we need to set the parameter "pimcore_admin" here to be able to render unpublished documents
                    $reqBak = $_REQUEST;
                    $_REQUEST["pimcore_admin"] = true;

                    $html = Document\Service::render($element, [], false);

                    $_REQUEST = $reqBak; // set the request back to original

                    $html = preg_replace("@</?(img|meta|div|section|aside|article|body|bdi|bdo|canvas|embed|footer|head|header|html)([^>]+)?>@", "", $html);
                    $html = preg_replace('/<!--(.*)-->/Uis', '', $html);

                    include_once("simple_html_dom.php");
                    $dom = str_get_html($html);
                    if ($dom) {

                        // remove containers including their contents
                        $elements = $dom->find("form,script,style,noframes,noscript,object,area,mapm,video,audio,iframe,textarea,input,select,button,");
                        if ($elements) {
                            foreach ($elements as $el) {
                                $el->outertext = "";
                            }
                        }

                        $clearText = function ($string) {
                            $string = str_replace("\r\n", "", $string);
                            $string = str_replace("\n", "", $string);
                            $string = str_replace("\r", "", $string);
                            $string = str_replace("\t", "", $string);
                            $string = preg_replace('/&[a-zA-Z0-9]+;/', '', $string); // remove html entities
                            $string = preg_replace('#[ ]+#', '', $string);

                            return $string;
                        };

                        // remove empty tags (where it matters)
                        $elements = $dom->find("a, li");
                        if ($elements) {
                            foreach ($elements as $el) {
                                $string = $clearText($el->plaintext);
                                if (empty($string)) {
                                    $el->outertext = "";
                                }
                            }
                        }


                        // replace links => links get [Linktext]
                        $elements = $dom->find("a");
                        if ($elements) {
                            foreach ($elements as $el) {
                                $string = $clearText($el->plaintext);
                                if (!empty($string)) {
                                    $el->outertext = "[" . $el->plaintext . "]";
                                } else {
                                    $el->outertext = "";
                                }
                            }
                        }

                        $html = $dom->save();
                        $dom->clear();
                        unset($dom);

                        // force closing tags (simple_html_dom doesn't seem to support this anymore)
                        $doc = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        $doc->loadHTML('<?xml encoding="UTF-8"><article>' . $html . "</article>");
                        libxml_clear_errors();
                        $html = $doc->saveHTML();

                        $bodyStart = strpos($html, "<body>")+6;
                        $bodyEnd = strpos($html, "</body>");
                        if ($bodyStart && $bodyEnd) {
                            $html = substr($html, $bodyStart, $bodyEnd - $bodyStart);
                        }

                        $output .= $html;
                    }
                } elseif ($element instanceof Object\Concrete) {
                    $hasContent = false;

                    if ($fd = $element->getClass()->getFieldDefinition("localizedfields")) {
                        $definitions = $fd->getFielddefinitions();

                        $locale = new \Zend_Locale(str_replace("-", "_", $source));
                        if (Tool::isValidLanguage((string) $locale)) {
                            $locale = (string) $locale;
                        } else {
                            $locale = $locale->getLanguage();
                        }

                        $output .= '
                            <table border="1" cellspacing="0" cellpadding="2">
                                <tr>
                                    <td colspan="2"><span style="color:#cc2929;font-weight: bold;">Localized Data</span></td>
                                </tr>
                        ';

                        foreach ($definitions as $definition) {

                            // check allowed datatypes
                            if (!in_array($definition->getFieldtype(), ["input", "textarea", "wysiwyg"])) {
                                continue;
                            }

                            $content = $element->{"get" . ucfirst($definition->getName())}($locale);

                            if (!empty($content)) {
                                $output .= '
                                <tr>
                                    <td><span style="color:#cc2929;">' . $definition->getTitle() . ' (' . $definition->getName() . ')<span></td>
                                    <td>' . $content . '&nbsp;</td>
                                </tr>
                                ';

                                $hasContent = true;
                            }
                        }

                        $output .= '</table>';
                    }

                    if (!$hasContent) {
                        $output = ""; // there's no content in the object, so reset all contents and do not inclide it in the export
                    }
                }


                // append contents
                if (!empty($output)) {
                    $f = fopen($exportFile, "a+");
                    fwrite($f, $output);
                    fclose($f);
                }
            } catch (\Exception $e) {
                Logger::error("Word Export: " . $e->getMessage());
                Logger::error($e);
            }
        }


        $this->_helper->json([
            "success" => true
        ]);
    }

    public function wordExportDownloadAction()
    {
        $id = $this->getParam("id");
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $id . ".html";

        // add closing body/html
        //$f = fopen($exportFile, "a+");
        //fwrite($f, "</body></html>");
        //fclose($f);

        // should be done via Pimcore_Document(_Adapter_LibreOffice) in the future
        if (\Pimcore\Document::isFileTypeSupported("docx")) {
            $lockKey = "soffice";
            Model\Tool\Lock::acquire($lockKey); // avoid parallel conversions of the same document

            $out = Tool\Console::exec(\Pimcore\Document\Adapter\LibreOffice::getLibreOfficeCli() . ' --headless --convert-to docx:"Office Open XML Text" --outdir ' . PIMCORE_SYSTEM_TEMP_DIRECTORY . " " . $exportFile);

            Logger::debug("LibreOffice Output was: " . $out);

            $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . preg_replace("/\." . File::getFileExtension($exportFile) . "$/", ".docx", basename($exportFile));

            Model\Tool\Lock::release($lockKey);
            // end what should be done in Pimcore_Document

            header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
            header('Content-Disposition: attachment; filename="' . basename($tmpName) . '"');
        } else {
            // no conversion, output html file
            $tmpName = $exportFile;
            header("Content-Type: text/html");
            header('Content-Disposition: attachment; filename="' . basename($tmpName) . '"');
        } while (@ob_end_flush());
        flush();

        readfile($tmpName);

        @unlink($exportFile);
        @unlink($tmpName);
        exit;
    }

    public function mergeItemAction()
    {
        $translationType = $this->getParam("translationType");

        $dataList = json_decode($this->getParam("data"), true);

        $classname = "\\Pimcore\\Model\\Translation\\" . ucfirst($translationType);
        foreach ($dataList as $data) {
            $t = $classname::getByKey($data["key"], true);
            $t->addTranslation($data["lg"], $data["current"]);
            $t->setModificationDate(time());
            $t->save();
        }


        $this->_helper->json([
            "success" => true
        ]);
    }


    public function getWebsiteTranslationLanguagesAction()
    {
        $this->_helper->json([
            'view' => $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations(),

            //when no view language is defined, all languages are editable. if one view language is defined, it
            //may be possible that no edit language is set intentionally
            'edit' => $this->getUser()->getAllowedLanguagesForEditingWebsiteTranslations()
        ]);
    }
}
