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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Object;
use Pimcore\Model\Translation;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/translation")
 */
class TranslationController extends AdminController
{
    const SELFCLOSING_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    /**
     * @Route("/import")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importAction(Request $request)
    {
        $this->checkPermission('translations');

        $admin = $request->get('admin');
        $merge = $request->get('merge');

        $tmpFile = $_FILES['Filedata']['tmp_name'];

        $overwrite = $merge ? false : true;

        if ($admin) {
            $delta = Translation\Admin::importTranslationsFromFile($tmpFile, $overwrite, Tool\Admin::getLanguages());
        } else {
            $delta = Translation\Website::importTranslationsFromFile($tmpFile, $overwrite, $this->getUser()->getAllowedLanguagesForEditingWebsiteTranslations());
        }

        $result =[
            'success' => true
        ];
        if ($merge) {
            $enrichedDelta = [];

            foreach ($delta as $item) {
                $lg = $item['lg'];
                $currentLocale = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();
                $item['lgname'] =  \Locale::getDisplayLanguage($lg, $currentLocale);
                $item['icon'] = '/admin/misc/get-language-flag?language=' . $lg;
                $item['current'] = $item['text'];
                $enrichedDelta[]= $item;
            }

            $result['delta'] = base64_encode(json_encode($enrichedDelta));
        }

        $response = $this->json($result);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/export")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $this->checkPermission('translations');
        $admin = $request->get('admin');

        if ($admin) {
            $class = '\\Pimcore\\Model\\Translation\\Admin';
        } else {
            $class = '\\Pimcore\\Model\\Translation\\Website';
        }

        $tableName = call_user_func($class . '\\Dao::getTableName');

        // clear translation cache
        Translation\AbstractTranslation::clearDependentCache();

        if ($admin) {
            $list = new Translation\Admin\Listing();
        } else {
            $list = new Translation\Website\Listing();
        }

        $joins = [];

        $list->setOrder('asc');
        $list->setOrderKey($tableName . '.key', false);

        $condition = $this->getGridFilterCondition($request, $tableName);
        if ($condition) {
            $list->setCondition($condition);
        }

        $filters = $this->getGridFilterCondition($request, $tableName, true);

        if ($filters) {
            $joins = array_merge($joins, $filters['joins']);
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
                $t->addTranslation($language, '');
            }

            $translationObjects[] = $t;
        }

        foreach ($translationObjects as $t) {
            $translations[] = array_merge(['key' => $t->getKey(),
                'creationDate' => $t->getCreationDate(),
                'modificationDate' => $t->getModificationDate(),
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
        $csv = implode(';', $headerRow) . "\r\n";

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
            $csv .= implode(';', $tempRow) . "\r\n";
        }

        $suffix = $admin ? 'admin' : 'website';
        $response = new Response("\xEF\xBB\xBF" . $csv);
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-type:', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_ ' . $suffix . '_translations.csv"');
        ini_set('display_errors', false); //to prevent warning messages in csv
        return $response;
    }

    /**
     * @Route("/add-admin-translation-keys")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAdminTranslationKeysAction(Request $request)
    {
        $keys = $request->get('keys');
        if ($keys) {
            $availableLanguages = Tool\Admin::getLanguages();
            $data = $this->decodeJson($keys);
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
                        $t->addTranslation($lang, '');
                    }

                    try {
                        $t->save();
                    } catch (\Exception $e) {
                        Logger::log($e);
                    }
                }
            }
        }

        return $this->json(null);
    }

    /**
     * @Route("/translations")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationsAction(Request $request)
    {
        $admin = $request->get('admin');

        if ($admin) {
            $class = '\\Pimcore\\Model\\Translation\\Admin';
            $this->checkPermission('translations_admin');
        } else {
            $class = '\\Pimcore\\Model\\Translation\\Website';
            $this->checkPermission('translations');
        }

        $tableName = call_user_func($class . '\\Dao::getTableName');

        // clear translation cache
        Translation\Website::clearDependentCache();

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $t = $class::getByKey($data['key']);
                $t->delete();

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $t = $class::getByKey($data['key']);

                foreach ($data as $key => $value) {
                    if ($key != 'key') {
                        $t->addTranslation($key, $value);
                    }
                }

                if ($data['key']) {
                    $t->setKey($data['key']);
                }
                $t->setModificationDate(time());
                $t->save();

                $return = array_merge(['key' => $t->getKey(),
                    'creationDate' => $t->getCreationDate(),
                    'modificationDate' => $t->getModificationDate()],
                    $t->getTranslations());

                return $this->json(['data' => $return, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                try {
                    $t = $class::getByKey($data['key']);
                } catch (\Exception $e) {
                    $t = new $class();

                    $t->setKey($data['key']);
                    $t->setCreationDate(time());
                    $t->setModificationDate(time());

                    foreach (Tool::getValidLanguages() as $lang) {
                        $t->addTranslation($lang, '');
                    }
                    $t->save();
                }

                $return = array_merge([
                    'key' => $t->getKey(),
                    'creationDate' => $t->getCreationDate(),
                    'modificationDate' => $t->getModificationDate(),
                ], $t->getTranslations());

                return $this->json(['data' => $return, 'success' => true]);
            }
        } else {
            // get list of types
            if ($admin) {
                $list = new Translation\Admin\Listing();
            } else {
                $list = new Translation\Website\Listing();
            }

            $validLanguages = $admin ? Tool\Admin::getLanguages() : $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations();

            $list->setOrder('asc');
            $list->setOrderKey($tableName . '.key', false);

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));

            $joins = [];

            if ($sortingSettings['orderKey']) {
                if (in_array($sortingSettings['orderKey'], $validLanguages)) {
                    $joins[] = [
                        'language' => $sortingSettings['orderKey']
                    ];
                    $list->setOrderKey($sortingSettings['orderKey']);
                } else {
                    $list->setOrderKey($tableName . '.' . $sortingSettings['orderKey'], false);
                }
            }
            if ($sortingSettings['order']) {
                $list->setOrder($sortingSettings['order']);
            }

            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $condition = $this->getGridFilterCondition($request, $tableName);
            $filters = $this->getGridFilterCondition($request, $tableName, true);

            if ($filters) {
                $joins = array_merge($joins, $filters['joins']);
            }
            if ($condition) {
                $list->setCondition($condition);
            }

            $this->extendTranslationQuery($joins, $list, $tableName, $filters);

            $list->load();

            $translations = [];
            foreach ($list->getTranslations() as $t) {
                $translations[] = array_merge($t->getTranslations(), ['key' => $t->getKey(),
                    'creationDate' => $t->getCreationDate(),
                    'modificationDate' => $t->getModificationDate()]);
            }

            return $this->json(['data' => $translations, 'success' => true, 'total' => $list->getTotalCount()]);
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
            $list->onCreateQuery(function (\Pimcore\Db\ZendCompatibility\QueryBuilder $select) use ($list, $joins, $tableName, $filters) {
                $db = \Pimcore\Db::get();

                $alreadyJoined = [];

                foreach ($joins as $join) {
                    $fieldname = $join['language'];

                    if ($alreadyJoined[$fieldname]) {
                        continue;
                    }
                    $alreadyJoined[$fieldname] = 1;

                    $select->joinLeft(
                        [$fieldname => $tableName],
                        '('
                        . $fieldname . '.key = ' . $tableName . '.key'
                        . ' and ' . $fieldname . '.language = '. $db->quote($fieldname)
                        . ')',
                        [
                            $fieldname => 'text'
                        ]
                    );
                }

                $havings = $filters['conditions'];
                if ($havings) {
                    $havings = implode(' AND ', $havings);
                    $select->having($havings);
                }
            }
            );
        }
    }

    /**
     * @param Request $request
     * @param $tableName
     * @param bool $languageMode
     *
     * @return array|null|string
     */
    protected function getGridFilterCondition(Request $request, $tableName, $languageMode = false)
    {
        $joins = [];
        $conditions = [];
        $validLanguages = $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations();

        $db = \Pimcore\Db::get();
        $conditionFilters = [];

        $filterJson = $request->get('filter');
        if ($filterJson) {
            $propertyField = 'property';
            $operatorField = 'operator';

            $filters = $this->decodeJson($filterJson);
            foreach ($filters as $filter) {
                $operator = '=';
                $field = null;
                $value = null;

                if (!$languageMode && in_array($filter[$propertyField], $validLanguages)
                    || $languageMode && !in_array($filter[$propertyField], $validLanguages)) {
                    continue;
                }

                if ($languageMode) {
                    $fieldname = $filter[$propertyField];
                } else {
                    $fieldname = $tableName . '.' . $filter[$propertyField];
                }

                if ($filter['type'] == 'string') {
                    $operator = 'LIKE';
                    $field = $fieldname;
                    $value = '%' . $filter['value'] . '%';
                } elseif ($filter['type'] == 'date' ||
                    (in_array($fieldname, ['modificationDate', 'creationDate']))) {
                    if ($filter[$operatorField] == 'lt') {
                        $operator = '<';
                    } elseif ($filter[$operatorField] == 'gt') {
                        $operator = '>';
                    } elseif ($filter[$operatorField] == 'eq') {
                        $operator = '=';
                        $fieldname = "UNIX_TIMESTAMP(DATE(FROM_UNIXTIME({$fieldname})))";
                    }
                    $filter['value'] = strtotime($filter['value']);
                    $field = $fieldname;
                    $value = $filter['value'];
                }

                if ($field && $value) {
                    $condition = $field . ' ' . $operator . ' ' . $db->quote($value);

                    if ($languageMode) {
                        $conditions[$filter[$propertyField]] = $condition;
                        $joins[] =  [
                            'language' => $filter[$propertyField]
                        ];
                    } else {
                        $conditionFilters[] = $condition;
                    }
                }
            }
        }

        if ($request->get('searchString')) {
            $filterTerm = $db->quote('%'.mb_strtolower($request->get('searchString')).'%');
            $conditionFilters[] = '(lower(' .$tableName . '.key) LIKE ' . $filterTerm . ' OR lower(' . $tableName . '.text) LIKE ' . $filterTerm.')';
        }

        if ($languageMode) {
            $result = [
                'joins' => $joins,
                'conditions' => $conditions
            ];

            return $result;
        } else {
            if (!empty($conditionFilters)) {
                return implode(' AND ', $conditionFilters);
            }

            return null;
        }
    }

    /**
     * @Route("/cleanup")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cleanupAction(Request $request)
    {
        $listClass = '\\Pimcore\\Model\\Translation\\' . ucfirst($request->get('type')) . '\\Listing';
        if (Tool::classExists($listClass)) {
            $list = new $listClass();
            $list->cleanup();

            \Pimcore\Cache::clearTags(['translator', 'translate']);

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false]);
    }

    /**
     * -----------------------------------------------------------------------------------
     * THE FOLLOWING ISN'T RELATED TO THE SHARED TRANSLATIONS OR ADMIN-TRANSLATIONS
     * XLIFF CONTENT-EXPORT & MS WORD CONTENT-EXPORT
     * -----------------------------------------------------------------------------------
     */

    /**
     * @Route("/content-export-jobs")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contentExportJobsAction(Request $request)
    {
        $data = $this->decodeJson($request->get('data'));
        $elements = [];
        $jobs = [];
        $exportId = uniqid();
        $source = $request->get('source');
        $target = $request->get('target');
        $type = $request->get('type');

        $source = str_replace('_', '-', $source);
        $target = str_replace('_', '-', $target);

        if ($data && is_array($data)) {
            foreach ($data as $element) {
                $elements[$element['type'] . '_' . $element['id']] = [
                    'id' => $element['id'],
                    'type' => $element['type']
                ];

                if ($element['children']) {
                    $el = Element\Service::getElementById($element['type'], $element['id']);
                    $listClass = '\\Pimcore\\Model\\' . ucfirst($element['type']) . '\\Listing';
                    $list = new $listClass();
                    $list->setUnpublished(true);
                    if ($el instanceof Object\AbstractObject) {
                        // inlcude variants
                        $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_VARIANT, Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_FOLDER]);
                    }
                    $list->setCondition(($el instanceof Object\AbstractObject ? 'o_' : '') . 'path LIKE ?', [$el->getRealFullPath() . ($el->getRealFullPath() != '/' ? '/' : '') . '%']);
                    $idList = $list->loadIdList();

                    foreach ($idList as $id) {
                        $elements[$element['type'] . '_' . $id] = [
                            'id' => $id,
                            'type' => $element['type']
                        ];
                    }
                }
            }
        }

        $elements = array_values($elements);

        $elementsPerJob = 10;
        if ($type == 'word') {
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
                'url' => '/admin/translation/' . $type . '-export',
                'params' => [
                    'id' => $exportId,
                    'source' => $source,
                    'target' => $target,
                    'data' => $this->encodeJson($chunk)
                ]
            ]];
        }

        return $this->json([
            'success' => true,
            'jobs' => $jobs,
            'id' => $exportId
        ]);
    }

    /**
     * @Route("/xliff-export")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function xliffExportAction(Request $request)
    {
        $id = $request->get('id');
        $data = $this->decodeJson($request->get('data'));
        $source = $request->get('source');
        $target = $request->get('target');

        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $id . '.xliff';
        if (!is_file($exportFile)) {
            // create initial xml file structure
            File::put($exportFile, '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<xliff version="1.2"></xliff>');
        }

        $xliff = simplexml_load_file($exportFile, null, LIBXML_NOCDATA);

        foreach ($data as $el) {
            $element = Element\Service::getElementById($el['type'], $el['id']);
            $file = $xliff->addChild('file');
            $file->addAttribute('original', Element\Service::getElementType($element) . '-' . $element->getId());
            $file->addAttribute('source-language', $source);
            $file->addAttribute('target-language', $target);
            $file->addAttribute('datatype', 'html');
            $file->addAttribute('tool', 'pimcore');
            $file->addAttribute('category', Element\Service::getElementType($element));

            $file->addChild('header');

            $body = $file->addChild('body');
            $addedElements = false;

            // elements
            if ($element instanceof Document) {
                $elements = [];

                $doc = $element;

                // get also content of inherited document elements
                while ($doc) {
                    if (method_exists($doc, 'getElements')) {
                        $elements = array_merge($elements, $doc->getElements());
                    }

                    if (method_exists($doc, 'getContentMasterDocument')) {
                        $doc = $doc->getContentMasterDocument();
                    } else {
                        $doc = null;
                    }
                }

                foreach ($elements as $tag) {
                    if (in_array($tag->getType(), ['wysiwyg', 'input', 'textarea', 'image', 'link'])) {
                        if (in_array($tag->getType(), ['image', 'link'])) {
                            $content = $tag->getText();
                        } else {
                            $content = $tag->getData();
                        }

                        if (is_string($content)) {
                            $contentCheck = trim(strip_tags($content));
                            if (!empty($contentCheck)) {
                                $this->addTransUnitNode($body, 'tag~-~' . $tag->getName(), $content, $source);
                                $addedElements = true;
                            }
                        }
                    }
                }

                if ($element instanceof Document\Page) {
                    $data = [
                        'title' => $element->getTitle(),
                        'description' => $element->getDescription()
                    ];

                    foreach ($data as $key => $content) {
                        if (!empty($content)) {
                            $this->addTransUnitNode($body, 'settings~-~' . $key, $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            } elseif ($element instanceof Object\Concrete) {
                if ($fd = $element->getClass()->getFieldDefinition('localizedfields')) {
                    $definitions = $fd->getFielddefinitions();

                    $locale = str_replace('-', '_', $source);
                    if (!Tool::isValidLanguage($locale)) {
                        $locale = \Locale::getPrimaryLanguage($locale);
                    }

                    foreach ($definitions as $definition) {

                        // check allowed datatypes
                        if (!in_array($definition->getFieldtype(), ['input', 'textarea', 'wysiwyg'])) {
                            continue;
                        }

                        $content = $element->{'get' . ucfirst($definition->getName())}($locale);

                        if (!empty($content)) {
                            $this->addTransUnitNode($body, 'localizedfield~-~' . $definition->getName(), $content, $source);
                            $addedElements = true;
                        }
                    }
                }
            }

            // properties
            $properties = $element->getProperties();
            if (is_array($properties)) {
                foreach ($properties as $property) {
                    if ($property->getType() == 'text' && !$property->isInherited()) {

                        // exclude text properties
                        if ($element instanceof Document) {
                            if (in_array($property->getName(), [
                                'language',
                                'navigation_target',
                                'navigation_exclude',
                                'navigation_class',
                                'navigation_anchor',
                                'navigation_parameters',
                                'navigation_relation',
                                'navigation_accesskey',
                                'navigation_tabindex'])) {
                                continue;
                            }
                        }

                        $content = $property->getData();
                        if (!empty($content)) {
                            $this->addTransUnitNode($body, 'property~-~' . $property->getName(), $content, $source);
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

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/xliff-export-download")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function xliffExportDownloadAction(Request $request)
    {
        $id = $request->get('id');
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $id . '.xliff';

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set('Content-Type', 'application/x-xliff+xml');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($exportFile));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @Route("/xliff-import-upload")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function xliffImportUploadAction(Request $request)
    {
        $jobs = [];
        $id = uniqid();
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $id . '.xliff';
        copy($_FILES['file']['tmp_name'], $importFile);

        $xliff = simplexml_load_file($importFile, null, LIBXML_NOCDATA);
        $steps = count($xliff->file);

        for ($i=0; $i < $steps; $i++) {
            $jobs[] = [[
                'url' => '/admin/translation/xliff-import-element',
                'params' => [
                    'id' => $id,
                    'step' => $i
                ]
            ]];
        }

        $response = $this->json([
            'success' => true,
            'jobs' => $jobs,
            'id' => $id
        ], false);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/xliff-import-element")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function xliffImportElementAction(Request $request)
    {
        include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');

        $id = $request->get('id');
        $step = $request->get('step');
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $id . '.xliff';

        $xliff = simplexml_load_file($importFile, null, LIBXML_NOCDATA);
        $file = $xliff->file[(int)$step];
        $target = $file['target-language'];

        // see https://en.wikipedia.org/wiki/IETF_language_tag
        $target = str_replace('-', '_', $target);

        if (!Tool::isValidLanguage($target)) {
            $target = \Locale::getPrimaryLanguage($target);
            if (!Tool::isValidLanguage($target)) {
                return $this->json([
                    'success' => false
                ]);
            }
        }

        list($type, $id) = explode('-', $file['original']);
        $element = Element\Service::getElementById($type, $id);

        if ($element) {
            foreach ($file->body->{'trans-unit'} as $transUnit) {
                list($fieldType, $name) = explode('~-~', $transUnit['id']);
                $content = $transUnit->target->asXml();
                $content = $this->unescapeXliff($content);

                if ($element instanceof Document) {
                    if ($fieldType == 'tag' && method_exists($element, 'getElement')) {
                        $tag = $element->getElement($name);
                        if ($tag) {
                            if (in_array($tag->getType(), ['image', 'link'])) {
                                $tag->setText($content);
                            } else {
                                $tag->setDataFromEditmode($content);
                            }

                            $tag->setInherited(false);
                            $element->setElement($tag->getName(), $tag);
                        }
                    }

                    if ($fieldType == 'settings' && $element instanceof Document\Page) {
                        $setter = 'set' . ucfirst($name);
                        if (method_exists($element, $setter)) {
                            $element->$setter($content);
                        }
                    }
                } elseif ($element instanceof Object\Concrete) {
                    if ($fieldType == 'localizedfield') {
                        $setter = 'set' . ucfirst($name);
                        if (method_exists($element, $setter)) {
                            $element->$setter($content, $target);
                        }
                    }
                }

                if ($fieldType == 'property') {
                    $property = $element->getProperty($name, true);
                    if ($property) {
                        $property->setData($content);
                    } else {
                        $element->setProperty($name, 'text', $content);
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
                throw new \Exception('Unable to save ' . Element\Service::getElementType($element) . ' with id ' . $element->getId() . ' because of the following reason: ' . $e->getMessage());
            }
        } else {
            Logger::error('Could not resolve element ' . $file['original']);
        }

        return $this->json([
            'success' => true
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
        $transUnit->addAttribute('id', htmlentities($name));

        $sourceNode = $transUnit->addChild('source');
        $sourceNode->addAttribute('xmlns:xml:lang', $source);

        $node = dom_import_simplexml($sourceNode);
        $no = $node->ownerDocument;
        $f = $no->createDocumentFragment();
        $f->appendXML($this->escapeXliff($content));
        @$node->appendChild($f);
    }

    /**
     * @param $content
     *
     * @return mixed|string
     */
    protected function unescapeXliff($content)
    {
        $content = preg_replace("/<\/?(target|mrk)([^>.]+)?>/i", '', $content);
        // we have to do this again but with html entities because of CDATA content
        $content = preg_replace("/&lt;\/?(target|mrk)((?!&gt;).)*&gt;/i", '', $content);

        if (preg_match("/<\/?(bpt|ept)/", $content)) {
            $xml = str_get_html($content);
            if ($xml) {
                $els = $xml->find('bpt,ept,ph');
                foreach ($els as $el) {
                    $content = html_entity_decode($el->innertext, null, 'UTF-8');
                    $el->outertext = $content;
                }
            }
            $content = $xml->save();
        }

        return $content;
    }

    /**
     * @param $content
     *
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
        $content = html_entity_decode($content, null, 'UTF-8');

        if (!preg_match_all('/<([^>]+)>([^<]+)?/', $content, $matches)) {
            // return original content if it doesn't contain HTML tags
            return '<![CDATA[' . $content . ']]>';
        }

        // Handle text before the first HTML tag
        $firstTagPosition = strpos($content, '<');
        $preText = ($firstTagPosition > 0) ? '<![CDATA[' . substr($content, 0, $firstTagPosition) . ']]>' : '';

        foreach ($matches[0] as $match) {
            $parts = explode('>', $match);
            $parts[0] .= '>';
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part)) {
                    if (preg_match("/<([a-z0-9\/]+)/", $part, $tag)) {
                        $tagName = str_replace('/', '', $tag[1]);
                        if (in_array($tagName, self::SELFCLOSING_TAGS)) {
                            $part = '<ph id="' . $count . '"><![CDATA[' . $part . ']]></ph>';

                            $count++;
                        } elseif (strpos($tag[1], '/') === false) {
                            $openTags[$count] = ['tag' => $tagName, 'id' => $count];
                            $part = '<bpt id="' . $count . '"><![CDATA[' . $part . ']]></bpt>';

                            $count++;
                        } else {
                            $closingTag = array_pop($openTags);
                            $part = '<ept id="' . $closingTag['id'] . '"><![CDATA[' . $part . ']]></ept>';
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

        $content = $preText . implode('', $final);

        return $content;
    }

    /**
     * @Route("/word-export")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function wordExportAction(Request $request)
    {
        error_reporting(0);
        ini_set('display_errors', 'off');

        $id         = $this->sanitzeExportId((string)$request->get('id'));
        $exportFile = $this->getExportFilePath($id, false);

        $data = $this->decodeJson($request->get('data'));
        $source = $request->get('source');

        if (!is_file($exportFile)) {
            File::put($exportFile, '');
        }

        foreach ($data as $el) {
            try {
                $element = Element\Service::getElementById($el['type'], $el['id']);
                $output = '';

                // check supported types (subtypes)
                if (!in_array($element->getType(), ['page', 'snippet', 'email', 'object'])) {
                    continue;
                }

                if ($element instanceof Element\ElementInterface) {
                    $output .= '<h1 class="element-headline">' . ucfirst($element->getType()) . ' - ' . $element->getRealFullPath() . ' (ID: ' . $element->getId() . ')</h1>';
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

                        if ($element->getProperty('navigation_name')) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Navigation</span></td>
                                    <td>' . $element->getProperty('navigation_name') . '&nbsp;</td>
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
                    $_REQUEST['pimcore_admin'] = true;

                    $html = Document\Service::render($element, [], false);

                    $_REQUEST = $reqBak; // set the request back to original

                    $html = preg_replace('@</?(img|meta|div|section|aside|article|body|bdi|bdo|canvas|embed|footer|head|header|html)([^>]+)?>@', '', $html);
                    $html = preg_replace('/<!--(.*)-->/Uis', '', $html);

                    include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');
                    $dom = str_get_html($html);
                    if ($dom) {

                        // remove containers including their contents
                        $elements = $dom->find('form,script,style,noframes,noscript,object,area,mapm,video,audio,iframe,textarea,input,select,button,');
                        if ($elements) {
                            foreach ($elements as $el) {
                                $el->outertext = '';
                            }
                        }

                        $clearText = function ($string) {
                            $string = str_replace("\r\n", '', $string);
                            $string = str_replace("\n", '', $string);
                            $string = str_replace("\r", '', $string);
                            $string = str_replace("\t", '', $string);
                            $string = preg_replace('/&[a-zA-Z0-9]+;/', '', $string); // remove html entities
                            $string = preg_replace('#[ ]+#', '', $string);

                            return $string;
                        };

                        // remove empty tags (where it matters)
                        $elements = $dom->find('a, li');
                        if ($elements) {
                            foreach ($elements as $el) {
                                $string = $clearText($el->plaintext);
                                if (empty($string)) {
                                    $el->outertext = '';
                                }
                            }
                        }

                        // replace links => links get [Linktext]
                        $elements = $dom->find('a');
                        if ($elements) {
                            foreach ($elements as $el) {
                                $string = $clearText($el->plaintext);
                                if (!empty($string)) {
                                    $el->outertext = '[' . $el->plaintext . ']';
                                } else {
                                    $el->outertext = '';
                                }
                            }
                        }

                        $html = $dom->save();
                        $dom->clear();
                        unset($dom);

                        // force closing tags (simple_html_dom doesn't seem to support this anymore)
                        $doc = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        $doc->loadHTML('<?xml encoding="UTF-8"><article>' . $html . '</article>');
                        libxml_clear_errors();
                        $html = $doc->saveHTML();

                        $bodyStart = strpos($html, '<body>') + 6;
                        $bodyEnd = strpos($html, '</body>');
                        if ($bodyStart && $bodyEnd) {
                            $html = substr($html, $bodyStart, $bodyEnd - $bodyStart);
                        }

                        $output .= $html;
                    }
                } elseif ($element instanceof Object\Concrete) {
                    $hasContent = false;

                    if ($fd = $element->getClass()->getFieldDefinition('localizedfields')) {
                        $definitions = $fd->getFielddefinitions();

                        $locale = str_replace('-', '_', $source);
                        if (!Tool::isValidLanguage($locale)) {
                            $locale = \Locale::getPrimaryLanguage($locale);
                        }

                        $output .= '
                            <table border="1" cellspacing="0" cellpadding="2">
                                <tr>
                                    <td colspan="2"><span style="color:#cc2929;font-weight: bold;">Localized Data</span></td>
                                </tr>
                        ';

                        foreach ($definitions as $definition) {

                            // check allowed datatypes
                            if (!in_array($definition->getFieldtype(), ['input', 'textarea', 'wysiwyg'])) {
                                continue;
                            }

                            $content = $element->{'get' . ucfirst($definition->getName())}($locale);

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
                        $output = ''; // there's no content in the object, so reset all contents and do not inclide it in the export
                    }
                }

                // append contents
                if (!empty($output)) {
                    $f = fopen($exportFile, 'a+');
                    fwrite($f, $output);
                    fclose($f);
                }
            } catch (\Exception $e) {
                Logger::error('Word Export: ' . $e->getMessage());
                Logger::error($e);
            }
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/word-export-download")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function wordExportDownloadAction(Request $request)
    {
        $id         = $this->sanitzeExportId((string)$request->get('id'));
        $exportFile = $this->getExportFilePath($id, true);

        // no conversion, output html file, works fine with MS Word and LibreOffice
        $content = file_get_contents($exportFile);
        @unlink($exportFile);

        // replace <script> and <link>
        $content = preg_replace('/<link[^>]+>/im', '$1', $content);
        $content = preg_replace("/<script[^>]+>(.*)?<\/script>/im", '$1', $content);

        $content =
            "<html>\n" .
                "<head>\n" .
                    '<style type="text/css">' . "\n" .
                    file_get_contents(PIMCORE_WEB_ROOT . '/pimcore/static6/css/word-export.css') .
                    "</style>\n" .
                "</head>\n\n" .
                "<body>\n" .
                    $content .
                "\n\n</body>\n" .
            "</html>\n"
        ;

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Content-Disposition', 'attachment; filename="word-export-' . date('Ymd') . '_' . uniqid() . '.htm"');

        return $response;
    }

    private function sanitzeExportId(string $id): string
    {
        if (empty($id) || !preg_match('/^[a-z0-9]+$/', $id)) {
            throw new BadRequestHttpException('Invalid export ID format');
        }

        return $id;
    }

    private function getExportFilePath(string $id, bool $checkExistence = true): string
    {
        // no need to check for path traversals here as sanitizeExportId restricted the ID parameter
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $id . '.html';

        if ($checkExistence && !file_exists($exportFile)) {
            throw $this->createNotFoundException(sprintf('Export file does not exist at path %s', $exportFile));
        }

        return $exportFile;
    }

    /**
     * @Route("/merge-item")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function mergeItemAction(Request $request)
    {
        $translationType = $request->get('translationType');

        $dataList = json_decode($request->get('data'), true);

        $classname = '\\Pimcore\\Model\\Translation\\' . ucfirst($translationType);
        foreach ($dataList as $data) {
            $t = $classname::getByKey($data['key'], true);
            $t->addTranslation($data['lg'], $data['current']);
            $t->setModificationDate(time());
            $t->save();
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/get-website-translation-languages")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWebsiteTranslationLanguagesAction(Request $request)
    {
        return $this->json([
            'view' => $this->getUser()->getAllowedLanguagesForViewingWebsiteTranslations(),

            //when no view language is defined, all languages are editable. if one view language is defined, it
            //may be possible that no edit language is set intentionally
            'edit' => $this->getUser()->getAllowedLanguagesForEditingWebsiteTranslations()
        ]);
    }
}
