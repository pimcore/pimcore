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
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Translation;
use Pimcore\Tool;
use Pimcore\Translation\ExportService\Exporter\ExporterInterface;
use Pimcore\Translation\ExportService\ExportServiceInterface;
use Pimcore\Translation\ImportDataExtractor\ImportDataExtractorInterface;
use Pimcore\Translation\ImporterService\ImporterServiceInterface;
use Pimcore\Translation\TranslationItemCollection\TranslationItemCollection;
use Pimcore\Translation\Translator;
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
    /**
     * @Route("/import", name="pimcore_admin_translation_import", methods={"POST"})
     *
     * @param Request $request
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function importAction(Request $request, LocaleServiceInterface $localeService)
    {
        $admin = $request->get('admin');
        $dialect = $request->get('csvSettings', null);
        $tmpFile = $request->get('importFile');

        if ($dialect) {
            $dialect = json_decode($dialect);
        }

        if (!empty($tmpFile)) {
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $tmpFile;
        } else {
            $tmpFile = $_FILES['Filedata']['tmp_name'];
        }

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        $merge = $request->get('merge');

        $overwrite = $merge ? false : true;

        if ($admin) {
            $delta = Translation\Admin::importTranslationsFromFile($tmpFile, $overwrite, Tool\Admin::getLanguages(), $dialect);
        } else {
            $delta = Translation\Website::importTranslationsFromFile(
                $tmpFile,
                $overwrite,
                $this->getAdminUser()->getAllowedLanguagesForEditingWebsiteTranslations(),
                $dialect
            );
        }

        if (is_file($tmpFile)) {
            @unlink($tmpFile);
        }

        $result = [
            'success' => true,
        ];
        if ($merge) {
            $enrichedDelta = [];

            foreach ($delta as $item) {
                $lg = $item['lg'];
                $currentLocale = $localeService->findLocale();
                $item['lgname'] = \Locale::getDisplayLanguage($lg, $currentLocale);
                $item['icon'] = $this->generateUrl('pimcore_admin_misc_getlanguageflag', ['language' => $lg]);
                $item['current'] = $item['text'];
                $enrichedDelta[] = $item;
            }

            $result['delta'] = base64_encode(json_encode($enrichedDelta));
        }

        $response = $this->adminJson($result);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/upload-import", name="pimcore_admin_translation_uploadimportfile", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImportFileAction(Request $request)
    {
        $tmpData = file_get_contents($_FILES['Filedata']['tmp_name']);

        //store data for further usage
        $filename = uniqid('import_translations-');
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $filename;
        File::put($importFile, $tmpData);

        // determine csv settings
        $dialect = Tool\Admin::determineCsvDialect($importFile);

        //ignore if line terminator is already hex otherwise generate hex for string
        if (!empty($dialect->lineterminator) && empty(preg_match('/[a-f0-9]{2}/i', $dialect->lineterminator))) {
            $dialect->lineterminator = bin2hex($dialect->lineterminator);
        }

        return $this->adminJson([
            'success' => true,
            'config' => [
                'tmpFile' => $filename,
                'csvSettings' => $dialect,
            ],
        ]);
    }

    /**
     * @Route("/export", name="pimcore_admin_translation_export", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $admin = $request->get('admin');
        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

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

        $condition = $this->getGridFilterCondition($request, $tableName, false, $admin);
        if ($condition) {
            $list->setCondition($condition);
        }

        $filters = $this->getGridFilterCondition($request, $tableName, true, $admin);

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
                $languages = $this->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();
            }

            foreach ($languages as $language) {
                $t->addTranslation($language, '');
            }

            $translationObjects[] = $t;
        }

        foreach ($translationObjects as $t) {
            $translations[] = array_merge(
                ['key' => $t->getKey(),
                    'creationDate' => $t->getCreationDate(),
                    'modificationDate' => $t->getModificationDate(),
                ],
                $t->getTranslations()
            );
        }

        //header column
        $columns = array_keys($translations[0]);

        if ($admin) {
            $languages = Tool\Admin::getLanguages();
        } else {
            $languages = $this->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();
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
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_ ' . $suffix . '_translations.csv"');
        ini_set('display_errors', false); //to prevent warning messages in csv

        return $response;
    }

    /**
     * @Route("/add-admin-translation-keys", name="pimcore_admin_translation_addadmintranslationkeys", methods={"POST"})
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

        return $this->adminJson(null);
    }

    /**
     * @Route("/translations", name="pimcore_admin_translation_translations", methods={"POST"})
     *
     * @param Request $request
     * @param Translator $translator
     *
     * @return JsonResponse
     */
    public function translationsAction(Request $request, Translator $translator)
    {
        $admin = $request->get('admin');

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        if ($admin) {
            $class = '\\Pimcore\\Model\\Translation\\Admin';
        } else {
            $class = '\\Pimcore\\Model\\Translation\\Website';
        }

        $tableName = call_user_func($class . '\\Dao::getTableName');

        // clear translation cache
        Translation\Website::clearDependentCache();

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if ($request->get('xaction') == 'destroy') {
                $t = $class::getByKey($data['key']);
                $t->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $t = $class::getByKey($data['key']);

                foreach ($data as $key => $value) {
                    $key = preg_replace('/^_/', '', $key, 1);
                    if ($key != 'key') {
                        $t->addTranslation($key, $value);
                    }
                }

                if ($data['key']) {
                    $t->setKey($data['key']);
                }
                $t->setModificationDate(time());
                $t->save();

                $return = array_merge(
                    ['key' => $t->getKey(),
                        'creationDate' => $t->getCreationDate(),
                        'modificationDate' => $t->getModificationDate(), ],
                    $this->prefixTranslations($t->getTranslations())
                );

                return $this->adminJson(['data' => $return, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $t = $class::getByKey($data['key']);
                if ($t) {
                    throw new \Exception($translator->trans('identifier_already_exists', [], 'admin'));
                }

                $t = new $class();
                $t->setKey($data['key']);
                $t->setCreationDate(time());
                $t->setModificationDate(time());

                foreach (Tool::getValidLanguages() as $lang) {
                    $t->addTranslation($lang, '');
                }
                $t->save();

                $return = array_merge(
                    [
                        'key' => $t->getKey(),
                        'creationDate' => $t->getCreationDate(),
                        'modificationDate' => $t->getModificationDate(),
                    ],
                    $this->prefixTranslations($t->getTranslations())
                );

                return $this->adminJson(['data' => $return, 'success' => true]);
            }
        } else {
            // get list of types
            if ($admin) {
                $list = new Translation\Admin\Listing();
            } else {
                $list = new Translation\Website\Listing();
            }

            $validLanguages = $admin ? Tool\Admin::getLanguages() : $this->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();

            $list->setOrder('asc');
            $list->setOrderKey($tableName . '.key', false);

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(
                array_merge($request->request->all(), $request->query->all())
            );

            $joins = [];

            if ($orderKey = $sortingSettings['orderKey']) {
                if (in_array(trim($orderKey, '_'), $validLanguages)) {
                    $orderKey = trim($orderKey, '_');
                    $joins[] = [
                        'language' => $orderKey,
                    ];
                    $list->setOrderKey($orderKey);
                } else {
                    $list->setOrderKey($tableName . '.' . $sortingSettings['orderKey'], false);
                }
            }
            if ($sortingSettings['order']) {
                $list->setOrder($sortingSettings['order']);
            }

            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $condition = $this->getGridFilterCondition($request, $tableName, false, $admin);
            $filters = $this->getGridFilterCondition($request, $tableName, true, $admin);

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
                $translations[] = array_merge(
                    $this->prefixTranslations($t->getTranslations()),
                    ['key' => $t->getKey(),
                        'creationDate' => $t->getCreationDate(),
                        'modificationDate' => $t->getModificationDate(), ]
                );
            }

            return $this->adminJson(['data' => $translations, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @param array $translations
     *
     * @return array
     */
    protected function prefixTranslations($translations)
    {
        if (!is_array($translations)) {
            return $translations;
        }

        $prefixedTranslations = [];
        foreach ($translations as $lang => $trans) {
            $prefixedTranslations['_' . $lang] = $trans;
        }

        return $prefixedTranslations;
    }

    /**
     * @param array $joins
     * @param Translation\AbstractTranslation\Listing $list
     * @param string $tableName
     * @param array $filters
     */
    protected function extendTranslationQuery($joins, $list, $tableName, $filters)
    {
        if ($joins) {
            $list->onCreateQuery(
                function (\Pimcore\Db\ZendCompatibility\QueryBuilder $select) use (
                    $joins,
                    $tableName,
                    $filters
                ) {
                    $db = \Pimcore\Db::get();

                    $alreadyJoined = [];

                    foreach ($joins as $join) {
                        $fieldname = $join['language'];

                        if (isset($alreadyJoined[$fieldname])) {
                            continue;
                        }
                        $alreadyJoined[$fieldname] = 1;

                        $select->joinLeft(
                            [$fieldname => $tableName],
                            '('
                            . $fieldname . '.key = ' . $tableName . '.key'
                            . ' and ' . $fieldname . '.language = ' . $db->quote($fieldname)
                            . ')',
                            [
                                $fieldname => 'text',
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
     * @param string $tableName
     * @param bool $languageMode
     *
     * @return array|null|string
     */
    protected function getGridFilterCondition(Request $request, $tableName, $languageMode = false, $admin = false)
    {
        $joins = [];
        $conditions = [];
        $validLanguages = $admin ? Tool\Admin::getLanguages() : $this->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();

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

                $fieldname = $filter[$propertyField];
                if (in_array(ltrim($fieldname, '_'), $validLanguages)) {
                    $fieldname = ltrim($fieldname, '_');
                }

                if (!$languageMode && in_array($fieldname, $validLanguages)
                    || $languageMode && !in_array($fieldname, $validLanguages)) {
                    continue;
                }

                if (!$languageMode) {
                    $fieldname = $tableName . '.' . $fieldname;
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
                        $conditions[$fieldname] = $condition;
                        $joins[] = [
                            'language' => $fieldname,
                        ];
                    } else {
                        $conditionFilters[] = $condition;
                    }
                }
            }
        }

        if ($request->get('searchString')) {
            $filterTerm = $db->quote('%' . mb_strtolower($request->get('searchString')) . '%');
            $conditionFilters[] = '(lower(' . $tableName . '.key) LIKE ' . $filterTerm . ' OR lower(' . $tableName . '.text) LIKE ' . $filterTerm . ')';
        }

        if ($languageMode) {
            $result = [
                'joins' => $joins,
                'conditions' => $conditions,
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
     * @Route("/cleanup", name="pimcore_admin_translation_cleanup", methods={"DELETE"})
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

            return $this->adminJson(['success' => true]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * -----------------------------------------------------------------------------------
     * THE FOLLOWING ISN'T RELATED TO THE SHARED TRANSLATIONS OR ADMIN-TRANSLATIONS
     * XLIFF CONTENT-EXPORT & MS WORD CONTENT-EXPORT
     * -----------------------------------------------------------------------------------
     */

    /**
     * @Route("/content-export-jobs", name="pimcore_admin_translation_contentexportjobs", methods={"POST"})
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
                    'type' => $element['type'],
                ];

                $el = null;

                if ($element['children']) {
                    $el = Element\Service::getElementById($element['type'], $element['id']);
                    $baseClass = ELement\Service::getBaseClassNameForElement($element['type']);
                    $listClass = '\\Pimcore\\Model\\' . $baseClass . '\\Listing';
                    $list = new $listClass();
                    $list->setUnpublished(true);
                    if ($el instanceof DataObject\AbstractObject) {
                        // inlcude variants
                        $list->setObjectTypes(
                            [DataObject\AbstractObject::OBJECT_TYPE_VARIANT,
                                DataObject\AbstractObject::OBJECT_TYPE_OBJECT,
                                DataObject\AbstractObject::OBJECT_TYPE_FOLDER, ]
                        );
                    }
                    $list->setCondition(
                        ($el instanceof DataObject\AbstractObject ? 'o_' : '') . 'path LIKE ?',
                        [$list->escapeLike($el->getRealFullPath() . ($el->getRealFullPath() != '/' ? '/' : '')) . '%']
                    );
                    $childs = $list->load();

                    foreach ($childs as $child) {
                        $childId = $child->getId();
                        $elements[$element['type'] . '_' . $childId] = [
                            'id' => $childId,
                            'type' => $element['type'],
                        ];

                        if (isset($element['relations']) && $element['relations']) {
                            $childDependencies = $child->getDependencies()->getRequires();
                            foreach ($childDependencies as $cd) {
                                if ($cd['type'] == 'object' || $cd['type'] == 'document') {
                                    $elements[$cd['type'] . '_' . $cd['id']] = $cd;
                                }
                            }
                        }
                    }
                }

                if (isset($element['relations']) && $element['relations']) {
                    if (!$el instanceof Element\AbstractElement) {
                        $el = Element\Service::getElementById($element['type'], $element['id']);
                    }

                    $dependencies = $el->getDependencies()->getRequires();
                    foreach ($dependencies as $dependency) {
                        if ($dependency['type'] == 'object' || $dependency['type'] == 'document') {
                            $elements[$dependency['type'] . '_' . $dependency['id']] = $dependency;
                        }
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
                'url' => $this->get('router')->getContext()->getBaseUrl() . '/admin/translation/' . $type . '-export',
                'method' => 'POST',
                'params' => [
                    'id' => $exportId,
                    'source' => $source,
                    'target' => $target,
                    'data' => $this->encodeJson($chunk),
                ],
            ]];
        }

        return $this->adminJson(
            [
                'success' => true,
                'jobs' => $jobs,
                'id' => $exportId,
            ]
        );
    }

    /**
     * @Route("/xliff-export", name="pimcore_admin_translation_xliffexport", methods={"POST"})
     *
     * @param Request $request
     * @param ExportServiceInterface $exportService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function xliffExportAction(Request $request, ExportServiceInterface $exportService)
    {
        $id = $request->get('id');
        $data = $this->decodeJson($request->get('data'));
        $source = $request->get('source');
        $target = $request->get('target');

        $translationItems = new TranslationItemCollection();

        foreach ($data as $el) {
            $element = Element\Service::getElementById($el['type'], $el['id']);
            $translationItems->addPimcoreElement($element);
        }

        $exportService->exportTranslationItems($translationItems, $source, [$target], $id);

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/xliff-export-download", name="pimcore_admin_translation_xliffexportdownload", methods={"GET"})
     *
     * @param Request $request
     * @param ExporterInterface $translationExporter
     *
     * @return BinaryFileResponse
     */
    public function xliffExportDownloadAction(Request $request, ExporterInterface $translationExporter, ExportServiceInterface $exportService)
    {
        $id = $request->get('id');
        $exportFile = $exportService->getTranslationExporter()->getExportFilePath($id);

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set('Content-Type', $translationExporter->getContentType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($exportFile));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @Route("/xliff-import-upload", name="pimcore_admin_translation_xliffimportupload", methods={"POST"})
     *
     * @param Request $request
     * @param ImportDataExtractorInterface $importDataExtractor
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function xliffImportUploadAction(Request $request, ImportDataExtractorInterface $importDataExtractor)
    {
        $jobs = [];
        $id = uniqid();
        $importFile = $importDataExtractor->getImportFilePath($id);
        copy($_FILES['file']['tmp_name'], $importFile);

        $steps = $importDataExtractor->countSteps($id);

        for ($i = 0; $i < $steps; $i++) {
            $jobs[] = [[
                'url' => $this->generateUrl('pimcore_admin_translation_xliffimportelement'),
                'method' => 'POST',
                'params' => [
                    'id' => $id,
                    'step' => $i,
                ],
            ]];
        }

        $response = $this->adminJson([
            'success' => true,
            'jobs' => $jobs,
            'id' => $id,
        ]);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/xliff-import-element", name="pimcore_admin_translation_xliffimportelement", methods={"POST"})
     *
     * @param Request $request
     * @param ImportDataExtractorInterface $importDataExtractor
     * @param ImporterServiceInterface $importerService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function xliffImportElementAction(Request $request, ImportDataExtractorInterface $importDataExtractor, ImporterServiceInterface $importerService)
    {
        $id = $request->get('id');
        $step = $request->get('step');

        try {
            $attributeSet = $importDataExtractor->extractElement($id, $step);
            if ($attributeSet) {
                $importerService->import($attributeSet);
            } else {
                Logger::warning(sprintf('Could not resolve element %s', $id));
            }
        } catch (\Exception $e) {
            Logger::err($e->getMessage());

            return $this->adminJson([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/word-export", name="pimcore_admin_translation_wordexport", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function wordExportAction(Request $request)
    {
        ini_set('display_errors', 'off');

        $id = $this->sanitzeExportId((string)$request->get('id'));
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
                    $output .= '<h1 class="element-headline">' . ucfirst(
                            $element->getType()
                        ) . ' - ' . $element->getRealFullPath() . ' (ID: ' . $element->getId() . ')</h1>';
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
                    $html = Document\Service::render($element, [], false, ['pimcore_admin' => true]);

                    $html = preg_replace(
                        '@</?(img|meta|div|section|aside|article|body|bdi|bdo|canvas|embed|footer|head|header|html)([^>]+)?>@',
                        '',
                        $html
                    );
                    $html = preg_replace('/<!--(.*)-->/Uis', '', $html);

                    $dom = str_get_html($html);
                    if ($dom) {

                        // remove containers including their contents
                        $elements = $dom->find(
                            'form,script,style,noframes,noscript,object,area,mapm,video,audio,iframe,textarea,input,select,button,'
                        );
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
                } elseif ($element instanceof DataObject\Concrete) {
                    $hasContent = false;

                    /** @var DataObject\ClassDefinition\Data\Localizedfields|null $fd */
                    $fd = $element->getClass()->getFieldDefinition('localizedfields');
                    if ($fd) {
                        $definitions = $fd->getFieldDefinitions();

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

                throw $e;
            }
        }

        return $this->adminJson(
            [
                'success' => true,
            ]
        );
    }

    /**
     * @Route("/word-export-download", name="pimcore_admin_translation_wordexportdownload", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function wordExportDownloadAction(Request $request)
    {
        $id = $this->sanitzeExportId((string)$request->get('id'));
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
            file_get_contents(PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/css/word-export.css') .
            "</style>\n" .
            "</head>\n\n" .
            "<body>\n" .
            $content .
            "\n\n</body>\n" .
            "</html>\n";

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="word-export-' . date('Ymd') . '_' . uniqid() . '.htm"'
        );

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
     * @Route("/merge-item", name="pimcore_admin_translation_mergeitem", methods={"PUT"})
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
            $newValue = htmlspecialchars_decode($data['current']);
            $t->addTranslation($data['lg'], $newValue);
            $t->setModificationDate(time());
            $t->save();
        }

        return $this->adminJson(
            [
                'success' => true,
            ]
        );
    }

    /**
     * @Route("/get-website-translation-languages", name="pimcore_admin_translation_getwebsitetranslationlanguages", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWebsiteTranslationLanguagesAction(Request $request)
    {
        return $this->adminJson(
            [
                'view' => $this->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations(),

                //when no view language is defined, all languages are editable. if one view language is defined, it
                //may be possible that no edit language is set intentionally
                'edit' => $this->getAdminUser()->getAllowedLanguagesForEditingWebsiteTranslations(),
            ]
        );
    }
}
