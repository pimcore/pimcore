<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Model\Translation;
use Pimcore\Tool;
use Pimcore\Tool\Session;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/translation")
 *
 * @internal
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
    public function importAction(Request $request, LocaleServiceInterface $localeService): JsonResponse
    {
        $domain = $request->get('domain', Translation::DOMAIN_DEFAULT);
        $admin = $domain == Translation::DOMAIN_ADMIN;

        $dialect = $request->get('csvSettings', null);
        $session = Session::getSessionBag($request->getSession(), 'pimcore_importconfig');
        $tmpFile = $session->get('translation_import_file');

        if ($dialect) {
            $dialect = json_decode($dialect);
        }

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        $merge = $request->get('merge');
        $overwrite = !$merge;

        $allowedLanguages = $this->getAdminUser()->getAllowedLanguagesForEditingWebsiteTranslations();
        if ($admin) {
            $allowedLanguages = Tool\Admin::getLanguages();
        }

        $delta = Translation::importTranslationsFromFile(
            $tmpFile,
            $domain,
            $overwrite,
            $allowedLanguages,
            $dialect
        );

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
    public function uploadImportFileAction(Request $request): JsonResponse
    {
        $tmpData = file_get_contents($_FILES['Filedata']['tmp_name']);

        //store data for further usage
        $filename = uniqid('import_translations-');
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $filename;
        File::put($importFile, $tmpData);

        Session::useBag($request->getSession(), function (AttributeBagInterface $session) use ($importFile) {
            $session->set('translation_import_file', $importFile);
        }, 'pimcore_importconfig');

        // determine csv settings
        $dialect = Tool\Admin::determineCsvDialect($importFile);

        //ignore if line terminator is already hex otherwise generate hex for string
        if (!empty($dialect->lineterminator) && empty(preg_match('/[a-f0-9]{2}/i', $dialect->lineterminator))) {
            $dialect->lineterminator = bin2hex($dialect->lineterminator);
        }

        return $this->adminJson([
            'success' => true,
            'config' => [
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
    public function exportAction(Request $request): Response
    {
        $domain = $request->get('domain', Translation::DOMAIN_DEFAULT);
        $admin = $domain == Translation::DOMAIN_ADMIN;

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        $translation = new Translation();
        $translation->setDomain($domain);
        $tableName = $translation->getDao()->getDatabaseTableName();

        // clear translation cache
        Translation::clearDependentCache();

        $list = new Translation\Listing();
        $list->setDomain($domain);

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
                $t = new Translation();
                $t->setDomain(Translation::DOMAIN_ADMIN);
                $languages = Tool\Admin::getLanguages();
            } else {
                $t = new Translation();
                $languages = $this->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();
            }

            foreach ($languages as $language) {
                $t->addTranslation($language, '');
            }

            $translationObjects[] = $t;
        }

        foreach ($translationObjects as $t) {
            $row = $t->getTranslations();
            $row = Element\Service::escapeCsvRecord($row);
            $translations[] = array_merge(
                ['key' => $t->getKey(),
                    'creationDate' => $t->getCreationDate(),
                    'modificationDate' => $t->getModificationDate(),
                ],
                $row
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
                $value = $t[$key] ?? null;
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

        $response = new Response("\xEF\xBB\xBF" . $csv);
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $domain . '_translations.csv"');
        ini_set('display_errors', '0'); //to prevent warning messages in csv

        return $response;
    }

    /**
     * @Route("/add-admin-translation-keys", name="pimcore_admin_translation_addadmintranslationkeys", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAdminTranslationKeysAction(Request $request): JsonResponse
    {
        $keys = $request->get('keys');
        if ($keys) {
            $availableLanguages = Tool\Admin::getLanguages();
            $data = $this->decodeJson($keys);
            foreach ($data as $translationData) {
                $t = null; // reset

                try {
                    $t = Translation::getByKey($translationData, Translation::DOMAIN_ADMIN);
                } catch (\Exception $e) {
                    Logger::log((string) $e);
                }
                if (!$t instanceof Translation) {
                    $t = new Translation();
                    $t->setDomain(Translation::DOMAIN_ADMIN);
                    $t->setKey($translationData);
                    $t->setCreationDate(time());
                    $t->setModificationDate(time());

                    foreach ($availableLanguages as $lang) {
                        $t->addTranslation($lang, '');
                    }

                    try {
                        $t->save();
                    } catch (\Exception $e) {
                        Logger::log((string) $e);
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
    public function translationsAction(Request $request, TranslatorInterface $translator): JsonResponse
    {
        $domain = $request->get('domain', Translation::DOMAIN_DEFAULT);
        $admin = $domain === Translation::DOMAIN_ADMIN;

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        $translation = new Translation();
        $translation->setDomain($domain);
        $tableName = $translation->getDao()->getDatabaseTableName();

        // clear translation cache
        Translation::clearDependentCache();

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if ($request->get('xaction') == 'destroy') {
                $t = Translation::getByKey($data['key'], $domain);
                if ($t instanceof Translation) {
                    $t->delete();
                }

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $t = Translation::getByKey($data['key'], $domain);

                foreach ($data as $key => $value) {
                    $key = preg_replace('/^_/', '', $key, 1);
                    if (!in_array($key, ['key', 'type'])) {
                        $t->addTranslation($key, $value);
                    }
                }

                if ($data['key']) {
                    $t->setKey($data['key']);
                }

                if ($data['type']) {
                    $t->setType($data['type']);
                }
                $t->setModificationDate(time());
                $t->save();

                $return = array_merge(
                    [
                        'key' => $t->getKey(),
                        'creationDate' => $t->getCreationDate(),
                        'modificationDate' => $t->getModificationDate(),
                        'type' => $t->getType(),
                    ],
                    $this->prefixTranslations($t->getTranslations())
                );

                return $this->adminJson(['data' => $return, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $t = Translation::getByKey($data['key'], $domain);
                if ($t) {
                    return $this->adminJson([
                        'message' => 'identifier_already_exists',
                        'success' => false,
                    ]);
                }

                $t = new Translation();
                $t->setDomain($domain);
                $t->setKey($data['key']);
                $t->setCreationDate(time());
                $t->setModificationDate(time());
                $t->setType($data['type'] ?? null);

                foreach (Tool::getValidLanguages() as $lang) {
                    $t->addTranslation($lang, '');
                }
                $t->save();

                $return = array_merge(
                    [
                        'key' => $t->getKey(),
                        'creationDate' => $t->getCreationDate(),
                        'modificationDate' => $t->getModificationDate(),
                        'type' => $t->getType(),
                    ],
                    $this->prefixTranslations($t->getTranslations())
                );

                return $this->adminJson(['data' => $return, 'success' => true]);
            }
        } else {
            // get list of types
            $list = new Translation\Listing();
            $list->setDomain($domain);

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
                } elseif ($list->isValidOrderKey($sortingSettings['orderKey'])) {
                    $list->setOrderKey($tableName . '.' . $sortingSettings['orderKey'], false);
                }
            }
            if ($sortingSettings['order']) {
                $list->setOrder($sortingSettings['order']);
            }

            $list->setLimit((int) $request->get('limit', 50));
            $list->setOffset((int) $request->get('start', 0));

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
            $searchString = $request->get('searchString');
            foreach ($list->getTranslations() as $t) {
                //Reload translation to get complete data,
                //if translation fetched based on the text not key
                if ($searchString && !strpos($searchString, $t->getKey())) {
                    if (!$t = Translation::getByKey($t->getKey(), $domain)) {
                        continue;
                    }
                }

                $translations[] = array_merge(
                    $this->prefixTranslations($t->getTranslations()),
                    [
                        'key' => $t->getKey(),
                        'creationDate' => $t->getCreationDate(),
                        'modificationDate' => $t->getModificationDate(),
                        'type' => $t->getType(),
                    ]
                );
            }

            return $this->adminJson(['data' => $translations, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    protected function prefixTranslations(array $translations): array
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

    protected function extendTranslationQuery(array $joins, Translation\Listing $list, string $tableName, array $filters): void
    {
        if ($joins) {
            $list->onCreateQueryBuilder(
                function (DoctrineQueryBuilder $select) use (
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

                        $select->addSelect($fieldname . '.text AS ' . $fieldname);
                        $select->leftJoin(
                            $tableName,
                            $tableName,
                            $fieldname,
                            '('
                            . $fieldname . '.key = ' . $tableName . '.key'
                            . ' and ' . $fieldname . '.language = ' . $db->quote($fieldname)
                            . ')'
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

    protected function getGridFilterCondition(Request $request, string $tableName, bool $languageMode = false, bool $admin = false): array|string|null
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

                if (!empty($filter['value'])) {
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
    public function cleanupAction(Request $request): JsonResponse
    {
        $domain = $request->get('domain', Translation::DOMAIN_DEFAULT);
        $list = new Translation\Listing();
        $list->setDomain($domain);
        $list->cleanup();

        \Pimcore\Cache::clearTags(['translator', 'translate']);

        return $this->adminJson(['success' => true]);
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
    public function contentExportJobsAction(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request->get('data'));
        $elements = [];
        $jobs = [];
        $exportId = uniqid();
        $source = $request->get('source', '');
        $target = $request->get('target', '');
        $type = $request->get('type');
        $jobUrl = $request->get('job_url', $request->getBaseUrl() . '/admin/translation/' . $type . '-export');

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
                            [DataObject::OBJECT_TYPE_VARIANT,
                                DataObject::OBJECT_TYPE_OBJECT,
                                DataObject::OBJECT_TYPE_FOLDER, ]
                        );
                    }
                    $list->setCondition(
                        'path LIKE ?',
                        [$list->escapeLike($el->getRealFullPath() . ($el->getRealFullPath() != '/' ? '/' : '')) . '%']
                    );
                    $children = $list->load();

                    foreach ($children as $child) {
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
                    if (!$el instanceof Element\ElementInterface) {
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

        $elementsPerJob = (int)$request->get('elements_per_job', 10);

        // make sure elements per job is not 0
        if (!$elementsPerJob) {
            $elementsPerJob = 1;
        }

        // one job = X elements
        $elements = array_chunk($elements, $elementsPerJob);
        foreach ($elements as $chunk) {
            $jobs[] = [[
                'url' => $jobUrl,
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
     * @Route("/merge-item", name="pimcore_admin_translation_mergeitem", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function mergeItemAction(Request $request): JsonResponse
    {
        $domain = $request->get('domain', Translation::DOMAIN_DEFAULT);

        $dataList = json_decode($request->get('data'), true);

        foreach ($dataList as $data) {
            $t = Translation::getByKey($data['key'], $domain, true);
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
    public function getWebsiteTranslationLanguagesAction(Request $request): JsonResponse
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

    /**
     * @Route("/get-translation-domains", name="pimcore_admin_translation_gettranslationdomains", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getTranslationDomainsAction(Request $request): JsonResponse
    {
        $translation = new Translation();

        $domains = array_map(
            fn ($domain) => ['name' => $domain],
            $translation->getDao()->getAvailableDomains(),
        );

        return $this->adminJson(['domains' => $domains]);
    }
}
