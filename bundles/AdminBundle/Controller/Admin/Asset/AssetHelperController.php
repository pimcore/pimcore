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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Asset;

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Db;
use Pimcore\Event\AdminEvents;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model\GridConfig;
use Pimcore\Model\GridConfigFavourite;
use Pimcore\Model\GridConfigShare;
use Pimcore\Model\Metadata;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Version;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/asset-helper")
 */
class AssetHelperController extends AdminController
{
    /**
     * @param int $userId
     * @param string $classId
     * @param string $searchType
     *
     * @return GridConfig[]
     */
    public function getMyOwnGridColumnConfigs($userId, $classId, $searchType)
    {
        $db = Db::get();
        $configListingConditionParts = [];
        $configListingConditionParts[] = 'ownerId = ' . $userId;
        $configListingConditionParts[] = 'classId = ' . $db->quote($classId);

        if ($searchType) {
            $configListingConditionParts[] = 'searchType = ' . $db->quote($searchType);
        }

        $configCondition = implode(' AND ', $configListingConditionParts);
        $configListing = new GridConfig\Listing();
        $configListing->setOrderKey('name');
        $configListing->setOrder('ASC');
        $configListing->setCondition($configCondition);
        $configListing = $configListing->load();

        return $configListing;
    }

    /**
     * @param User $user
     * @param string $classId
     * @param string $searchType
     *
     * @return GridConfig[]
     */
    public function getSharedGridColumnConfigs($user, $classId, $searchType = null)
    {
        $db = Db::get();

        $configListing = [];

        $userIds = [$user->getId()];
        // collect all roles
        $userIds = array_merge($userIds, $user->getRoles());
        $userIds = implode(',', $userIds);

        $query = 'select distinct c1.id from gridconfigs c1, gridconfig_shares s
                    where (c1.searchType = ' . $db->quote($searchType) . ' and ((c1.id = s.gridConfigId and s.sharedWithUserId IN (' . $userIds . '))) and c1.classId = ' . $db->quote($classId) . ')
                            UNION distinct select c2.id from gridconfigs c2 where shareGlobally = 1 and c2.classId = '. $db->quote($classId) . '  and c2.ownerId != ' . $db->quote($user->getId());

        $ids = $db->fetchCol($query);

        if ($ids) {
            $ids = implode(',', $ids);
            $configListing = new GridConfig\Listing();
            $configListing->setOrderKey('name');
            $configListing->setOrder('ASC');
            $configListing->setCondition('id in (' . $ids . ')');
            $configListing = $configListing->load();
        }

        return $configListing;
    }

    /**
     * @Route("/grid-delete-column-config", name="pimcore_admin_asset_assethelper_griddeletecolumnconfig", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridDeleteColumnConfigAction(Request $request)
    {
        $gridConfigId = $request->get('gridConfigId');
        $gridConfig = null;
        try {
            $gridConfig = GridConfig::getById($gridConfigId);
        } catch (\Exception $e) {
        }
        $success = false;
        if ($gridConfig) {
            if ($gridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                throw new \Exception("don't mess with someone elses grid config");
            }

            $gridConfig->delete();
            $success = true;
        }

        $newGridConfig = $this->doGetGridColumnConfig($request, true);
        $newGridConfig['deleteSuccess'] = $success;

        return $this->adminJson($newGridConfig);
    }

    /**
     * @Route("/grid-get-column-config", name="pimcore_admin_asset_assethelper_gridgetcolumnconfig", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridGetColumnConfigAction(Request $request)
    {
        $result = $this->doGetGridColumnConfig($request);

        return $this->adminJson($result);
    }

    /**
     * @param Request $request
     * @param bool $isDelete
     *
     * @return array
     */
    public function doGetGridColumnConfig(Request $request, $isDelete = false)
    {
        $gridConfigId = null;

        $classId = $request->get('id');
        $type = $request->get('type');

        $context = ['purpose' => 'gridconfig'];

        $types = [];
        if ($request->get('types')) {
            $types = explode(',', $request->get('types'));
        }

        $userId = $this->getAdminUser()->getId();

        $requestedGridConfigId = $isDelete ? null : $request->get('gridConfigId');

        // grid config
        $gridConfig = [];
        $searchType = $request->get('searchType');

        if (strlen($requestedGridConfigId) == 0) {
            // check if there is a favourite view
            $favourite = null;
            try {
                try {
                    $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $classId, 0, $searchType);
                } catch (\Exception $e) {
                }

                if ($favourite) {
                    $requestedGridConfigId = $favourite->getGridConfigId();
                }
            } catch (\Exception $e) {
            }
        }

        if (is_numeric($requestedGridConfigId) && $requestedGridConfigId > 0) {
            $db = Db::get();
            $configListingConditionParts = [];
            $configListingConditionParts[] = 'ownerId = ' . $userId;
            $configListingConditionParts[] = 'classId = ' . $db->quote($classId);
            $configListingConditionParts[] = 'type = ' . $db->quote($type);

            if ($searchType) {
                $configListingConditionParts[] = 'searchType = ' . $db->quote($searchType);
            }

            $savedGridConfig = null;
            try {
                $savedGridConfig = GridConfig::getById($requestedGridConfigId);
            } catch (\Exception $e) {
            }

            if ($savedGridConfig) {
                $shared = null;
                try {
                    $userIds = [$this->getAdminUser()->getId()];
                    if ($this->getAdminUser()->getRoles()) {
                        $userIds = array_merge($userIds, $this->getAdminUser()->getRoles());
                    }
                    $userIds = implode(',', $userIds);
                    $shared = ($savedGridConfig->getOwnerId() != $userId && $savedGridConfig->isShareGlobally()) || $db->fetchOne('select * from gridconfig_shares where sharedWithUserId IN (' . $userIds . ') and gridConfigId = ' . $savedGridConfig->getId());
                } catch (\Exception $e) {
                }

                if (!$shared && $savedGridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                    throw new \Exception('you are neither the onwner of this config nor it is shared with you');
                }
                $gridConfigId = $savedGridConfig->getId();
                $gridConfig = $savedGridConfig->getConfig();
                $gridConfig = json_decode($gridConfig, true);
                $gridConfigName = $savedGridConfig->getName();
                $gridConfigDescription = $savedGridConfig->getDescription();
                $sharedGlobally = $savedGridConfig->isShareGlobally();
            }
        }

        $availableFields = [];
        $language = '';

        if (empty($gridConfig)) {
            $availableFields = $this->getDefaultGridFields(
                $request->get('no_system_columns'),
                null, //maybe required for types other than metadata
                $context,
                $types);
        } else {
            $savedColumns = $gridConfig['columns'];

            foreach ($savedColumns as $key => $sc) {
                if (!$sc['hidden']) {
                    $colConfig = $this->getFieldGridConfig($sc, $language, null);
                    if ($colConfig) {
                        $availableFields[] = $colConfig;
                    }
                }
            }
        }
        usort($availableFields, function ($a, $b) {
            if ($a['position'] == $b['position']) {
                return 0;
            }

            return ($a['position'] < $b['position']) ? -1 : 1;
        });

        $availableConfigs = $classId ? $this->getMyOwnGridColumnConfigs($userId, $classId, $searchType) : [];
        $sharedConfigs = $classId ? $this->getSharedGridColumnConfigs($this->getAdminUser(), $classId, $searchType) : [];
        $settings = $this->getShareSettings((int)$gridConfigId);
        $settings['gridConfigId'] = (int)$gridConfigId;
        $settings['gridConfigName'] = $gridConfigName ?? null;
        $settings['gridConfigDescription'] = $gridConfigDescription ?? null;
        $settings['shareGlobally'] = $sharedGlobally ?? null;
        $settings['isShared'] = !$gridConfigId || ($shared ?? null);

        $context = $gridConfig['context'] ?? null;
        if ($context) {
            $context = json_decode($context, true);
        }

        return [
            'sortinfo' => isset($gridConfig['sortinfo']) ? $gridConfig['sortinfo'] : false,
            'availableFields' => $availableFields,
            'settings' => $settings,
            'onlyDirectChildren' => isset($gridConfig['onlyDirectChildren']) ? $gridConfig['onlyDirectChildren'] : false,
            'onlyUnreferenced' => isset($gridConfig['onlyUnreferenced']) ? $gridConfig['onlyUnreferenced'] : false,
            'pageSize' => isset($gridConfig['pageSize']) ? $gridConfig['pageSize'] : false,
            'availableConfigs' => $availableConfigs,
            'sharedConfigs' => $sharedConfigs,
            'context' => $context,
        ];
    }

    /**
     * @param array $field
     * @param string $language
     * @param string|null $keyPrefix
     *
     * @return array|null
     */
    protected function getFieldGridConfig($field, $language = '', $keyPrefix = null)
    {
        $defaulMetadataFields = ['copyright', 'alt', 'title'];
        $predefined = null;

        if (isset($field['fieldConfig']['layout']['name'])) {
            $predefined = Metadata\Predefined::getByName($field['fieldConfig']['layout']['name']);
        }

        $key = $field['name'];
        if ($keyPrefix) {
            $key = $keyPrefix . $key;
        }

        $fieldDef = explode('~', $field['name']);
        $field['name'] = $fieldDef[0];

        if (isset($fieldDef[1]) && $fieldDef[1] === 'system') {
            $type = 'system';
        } elseif (in_array($fieldDef[0], $defaulMetadataFields)) {
            $type = 'input';
        } else {
            $type = $field['fieldConfig']['type'];
            if (isset($fieldDef[1])) {
                $field['fieldConfig']['label'] = $field['fieldConfig']['layout']['title'] = $fieldDef[0] . ' (' . $fieldDef[1] . ')';
                $field['fieldConfig']['layout']['icon'] = Tool::getLanguageFlagFile($fieldDef[1], true);
            }
        }

        $result = [
            'key' => $key,
            'type' => $type,
            'label' => $field['fieldConfig']['label'] ?? $key,
            'width' => $field['width'],
            'position' => $field['position'],
            'language' => $field['fieldConfig']['language'] ?? null,
            'layout' => $field['fieldConfig']['layout'] ?? null,
        ];

        if (isset($field['locked'])) {
            $result['locked'] = $field['locked'];
        }

        if ($type === 'select' && $predefined) {
            $field['fieldConfig']['layout']['config'] = $predefined->getConfig();
            $result['layout'] = $field['fieldConfig']['layout'];
        } elseif ($type === 'document' || $type === 'asset' || $type === 'object') {
            $result['layout']['fieldtype'] = 'manyToOneRelation';
            $result['layout']['subtype'] = $type;
        }

        return $result;
    }

    /**
     * @param bool $noSystemColumns
     * @param array $fields
     * @param array $context
     * @param array $types
     *
     * @return array
     */
    public function getDefaultGridFields($noSystemColumns, $fields, $context, $types = [])
    {
        $count = 0;
        $availableFields = [];

        if (!$noSystemColumns) {
            foreach (Asset\Service::$gridSystemColumns as $sc) {
                if (empty($types)) {
                    $availableFields[] = [
                        'key' => $sc . '~system',
                        'type' => 'system',
                        'label' => $sc,
                        'position' => $count, ];
                    $count++;
                }
            }
        }

        return $availableFields;
    }

    /**
     * @Route("/prepare-helper-column-configs", name="pimcore_admin_asset_assethelper_preparehelpercolumnconfigs", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function prepareHelperColumnConfigs(Request $request)
    {
        $helperColumns = [];
        $newData = [];
        $data = json_decode($request->get('columns'));
        /** @var \stdClass $item */
        foreach ($data as $item) {
            if (!empty($item->isOperator)) {
                $itemKey = '#' . uniqid();

                $item->key = $itemKey;
                $newData[] = $item;
                $helperColumns[$itemKey] = $item;
            } else {
                $newData[] = $item;
            }
        }

        Tool\Session::useSession(function (AttributeBagInterface $session) use ($helperColumns) {
            $existingColumns = $session->get('helpercolumns', []);
            $helperColumns = array_merge($helperColumns, $existingColumns);
            $session->set('helpercolumns', $helperColumns);
        }, 'pimcore_gridconfig');

        return $this->adminJson(['success' => true, 'columns' => $newData]);
    }

    /**
     * @Route("/grid-mark-favourite-column-config", name="pimcore_admin_asset_assethelper_gridmarkfavouritecolumnconfig", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridMarkFavouriteColumnConfigAction(Request $request)
    {
        $classId = $request->get('classId');
        $asset = Asset::getById($classId);

        if ($asset->isAllowed('list')) {
            $gridConfigId = $request->get('gridConfigId');
            $searchType = $request->get('searchType');
            $type = $request->get('type');
            $user = $this->getAdminUser();

            $favourite = new GridConfigFavourite();
            $favourite->setOwnerId($user->getId());
            $favourite->setClassId($classId);
            $favourite->setSearchType($searchType);
            $favourite->setType($type);
            $specializedConfigs = false;

            try {
                if ($gridConfigId != 0) {
                    $gridConfig = GridConfig::getById($gridConfigId);
                    $favourite->setGridConfigId($gridConfig->getId());
                }

                $favourite->setObjectId(0);
                $favourite->save();
            } catch (\Exception $e) {
                $favourite->delete();
            }

            return $this->adminJson(['success' => true, 'spezializedConfigs' => $specializedConfigs]);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param int $gridConfigId
     *
     * @return array
     */
    protected function getShareSettings($gridConfigId)
    {
        $result = [
            'sharedUserIds' => [],
            'sharedRoleIds' => [],
        ];

        $db = Db::get();
        $allShares = $db->fetchAll('select s.sharedWithUserId, u.type from gridconfig_shares s, users u
                      where s.sharedWithUserId = u.id and s.gridConfigId = ' . $gridConfigId);

        if ($allShares) {
            foreach ($allShares as $share) {
                $type = $share['type'];
                $key = 'shared' . ucfirst($type) . 'Ids';
                $result[$key][] = $share['sharedWithUserId'];
            }
        }

        foreach ($result as $idx => $value) {
            $value = $value ? implode(',', $value) : '';
            $result[$idx] = $value;
        }

        return $result;
    }

    /**
     * @Route("/grid-save-column-config", name="pimcore_admin_asset_assethelper_gridsavecolumnconfig", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridSaveColumnConfigAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if ($asset->isAllowed('list')) {
            try {
                $classId = $request->get('class_id');
                $context = $request->get('context');

                $searchType = $request->get('searchType');
                $type = $request->get('type');

                // grid config
                $gridConfigData = $this->decodeJson($request->get('gridconfig'));
                $gridConfigData['pimcore_version'] = Version::getVersion();
                $gridConfigData['pimcore_revision'] = Version::getRevision();
                $gridConfigData['context'] = $context;
                unset($gridConfigData['settings']['isShared']);

                $metadata = $request->get('settings');
                $metadata = json_decode($metadata, true);

                $gridConfigId = $metadata['gridConfigId'];
                $gridConfig = null;
                if ($gridConfigId) {
                    try {
                        $gridConfig = GridConfig::getById($gridConfigId);
                    } catch (\Exception $e) {
                    }
                }
                if ($gridConfig && $gridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                    throw new \Exception("don't mess around with somebody else's configuration");
                }

                $this->updateGridConfigShares($gridConfig, $metadata);

                if (!$gridConfig) {
                    $gridConfig = new GridConfig();
                    $gridConfig->setName(date('c'));
                    $gridConfig->setClassId($classId);
                    $gridConfig->setSearchType($searchType);
                    $gridConfig->setType($type);

                    $gridConfig->setOwnerId($this->getAdminUser()->getId());
                }

                if ($metadata) {
                    $gridConfig->setName($metadata['gridConfigName']);
                    $gridConfig->setDescription($metadata['gridConfigDescription']);
                    $gridConfig->setShareGlobally($metadata['shareGlobally'] && $this->getAdminUser()->isAdmin());
                }

                $gridConfigData = json_encode($gridConfigData);
                $gridConfig->setConfig($gridConfigData);
                $gridConfig->save();

                $userId = $this->getAdminUser()->getId();

                $availableConfigs = $this->getMyOwnGridColumnConfigs($userId, $classId, $searchType);
                $sharedConfigs = $this->getSharedGridColumnConfigs($this->getAdminUser(), $classId, $searchType);

                $settings = $this->getShareSettings($gridConfig->getId());
                $settings['gridConfigId'] = (int)$gridConfig->getId();
                $settings['gridConfigName'] = $gridConfig->getName();
                $settings['gridConfigDescription'] = $gridConfig->getDescription();
                $settings['shareGlobally'] = $gridConfig->isShareGlobally();
                $settings['isShared'] = !$gridConfig || ($gridConfig->getOwnerId() != $this->getAdminUser()->getId());

                return $this->adminJson(['success' => true,
                        'settings' => $settings,
                        'availableConfigs' => $availableConfigs,
                        'sharedConfigs' => $sharedConfigs,
                    ]
                );
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param GridConfig $gridConfig
     * @param array $metadata
     *
     * @throws \Exception
     */
    protected function updateGridConfigShares($gridConfig, $metadata)
    {
        $user = $this->getAdminUser();
        if (!$gridConfig || !$user->isAllowed('share_configurations')) {
            // nothing to do
            return;
        }

        if ($gridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
            throw new \Exception("don't mess with someone elses grid config");
        }
        $combinedShares = [];
        $sharedUserIds = $metadata['sharedUserIds'];
        $sharedRoleIds = $metadata['sharedRoleIds'];

        if ($sharedUserIds) {
            $combinedShares = explode(',', $sharedUserIds);
        }

        if ($sharedRoleIds) {
            $sharedRoleIds = explode(',', $sharedRoleIds);
            $combinedShares = array_merge($combinedShares, $sharedRoleIds);
        }

        $db = Db::get();
        $db->delete('gridconfig_shares', ['gridConfigId' => $gridConfig->getId()]);

        foreach ($combinedShares as $id) {
            $share = new GridConfigShare();
            $share->setGridConfigId($gridConfig->getId());
            $share->setSharedWithUserId($id);
            $share->save();
        }
    }

    /**
     * @Route("/get-export-jobs", name="pimcore_admin_asset_assethelper_getexportjobs", methods={"GET"})
     *
     * @param Request $request
     * @param GridHelperService $gridHelperService
     *
     * @return JsonResponse
     */
    public function getExportJobsAction(Request $request, GridHelperService $gridHelperService)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());
        $list = $gridHelperService->prepareAssetListingForGrid($allParams, $this->getAdminUser());

        if (empty($ids = $allParams['ids'] ?? '')) {
            $ids = $list->loadIdList();
        }

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('asset-export-');
        file_put_contents($this->getCsvFile($fileHandle), '');

        return $this->adminJson(['success' => true, 'jobs' => $jobs, 'fileHandle' => $fileHandle]);
    }

    /**
     * @Route("/do-export", name="pimcore_admin_asset_assethelper_doexport", methods={"POST"})
     *
     * @param Request $request
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function doExportAction(Request $request, LocaleServiceInterface $localeService)
    {
        $fileHandle = \Pimcore\File::getValidFilename($request->get('fileHandle'));
        $ids = $request->get('ids');
        $settings = $request->get('settings');
        $settings = json_decode($settings, true);
        $delimiter = $settings['delimiter'] ? $settings['delimiter'] : ';';
        $language = str_replace('default', '', $request->get('language'));

        /** @var \Pimcore\Model\Asset\Listing $list */
        $list = new Asset\Listing();

        $quotedIds = [];
        foreach ($ids as $id) {
            $quotedIds[] = $list->quote($id);
        }

        $list->setCondition('id IN (' . implode(',', $quotedIds) . ')');
        $list->setOrderKey(' FIELD(id, ' . implode(',', $quotedIds) . ')', false);

        $fields = $request->get('fields');

        $addTitles = $request->get('initial');

        $csv = $this->getCsvData($request, $language, $list, $fields, $addTitles);

        $fp = fopen($this->getCsvFile($fileHandle), 'a');

        $firstLine = true;
        foreach ($csv as $line) {
            if ($addTitles && $firstLine) {
                $firstLine = false;
                $line = implode($delimiter, $line) . "\r\n";
                fwrite($fp, $line);
            } else {
                fwrite($fp, implode($delimiter, array_map([$this, 'encodeFunc'], $line)) . "\r\n");
            }
        }

        fclose($fp);

        return $this->adminJson(['success' => true]);
    }

    public function encodeFunc($value)
    {
        $value = str_replace('"', '""', $value);
        //force wrap value in quotes and return
        return '"' . $value . '"';
    }

    /**
     * @param Request $request
     * @param string $language
     * @param Asset\Listing $list
     * @param array $fields
     * @param bool $addTitles
     *
     * @return array
     */
    protected function getCsvData(Request $request, $language, $list, $fields, $addTitles = true)
    {
        //create csv
        $csv = [];

        $unsupportedFields = ['preview~system', 'size~system'];
        $fields = array_diff($fields, $unsupportedFields);

        if ($addTitles) {
            $columns = $fields;
            foreach ($columns as $columnIdx => $columnKey) {
                $columns[$columnIdx] = '"' . $columnKey . '"';
            }
            $csv[] = $columns;
        }

        foreach ($list->load() as $asset) {
            if ($fields) {
                $dataRows = [];
                foreach ($fields as $field) {
                    $fieldDef = explode('~', $field);
                    $getter = 'get' . ucfirst($fieldDef[0]);

                    if (isset($fieldDef[1])) {
                        if ($fieldDef[1] == 'system' && method_exists($asset, $getter)) {
                            $data = $asset->$getter($language);
                        } else {
                            $fieldDef[1] = str_replace('none', '', $fieldDef[1]);
                            $data = $asset->getMetadata($fieldDef[0], $fieldDef[1], true);
                        }
                    } else {
                        $data = $asset->getMetadata($field, $language, true);
                    }

                    if ($data instanceof Element\AbstractElement) {
                        $data = $data->getFullPath();
                    }
                    $dataRows[] = $data;
                }
                $csv[] = $dataRows;
            }
        }

        return $csv;
    }

    /**
     * @param Request $request
     *
     * @return mixed|string
     */
    protected function extractLanguage(Request $request)
    {
        $requestedLanguage = $request->get('language');
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        return $requestedLanguage;
    }

    /**
     * @param string $fileHandle
     *
     * @return string
     */
    protected function getCsvFile($fileHandle)
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $fileHandle . '.csv';
    }

    /**
     * @Route("/download-csv-file", name="pimcore_admin_asset_assethelper_downloadcsvfile", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function downloadCsvFileAction(Request $request)
    {
        $fileHandle = \Pimcore\File::getValidFilename($request->get('fileHandle'));
        $csvFile = $this->getCsvFile($fileHandle);
        if (file_exists($csvFile)) {
            $response = new BinaryFileResponse($csvFile);
            $response->headers->set('Content-Type', 'application/csv');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'export.csv');
            $response->deleteFileAfterSend(true);

            return $response;
        }

        throw $this->createNotFoundException('CSV file not found');
    }

    /**
     * @Route("/download-xlsx-file", name="pimcore_admin_asset_assethelper_downloadxlsxfile", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function downloadXlsxFileAction(Request $request)
    {
        $fileHandle = \Pimcore\File::getValidFilename($request->get('fileHandle'));
        $csvFile = $this->getCsvFile($fileHandle);
        if (file_exists($csvFile)) {
            $csvReader = new Csv();
            $csvReader->setDelimiter(';');
            $csvReader->setEnclosure('""');
            $csvReader->setSheetIndex(0);

            $spreadsheet = $csvReader->load($csvFile);
            $writer = new Xlsx($spreadsheet);
            $xlsxFilename = PIMCORE_SYSTEM_TEMP_DIRECTORY. '/' .$fileHandle. '.xlsx';
            $writer->save($xlsxFilename);

            $response = new BinaryFileResponse($xlsxFilename);
            $response->headers->set('Content-Type', 'application/xlsx');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'export.xlsx');
            $response->deleteFileAfterSend(true);

            return $response;
        }

        throw $this->createNotFoundException('XLSX file not found');
    }

    /**
     * @Route("/get-metadata-for-column-config", name="pimcore_admin_asset_assethelper_getmetadataforcolumnconfig", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getMetadataForColumnConfigAction(Request $request)
    {
        $result = [];

        //default metadata
        $defaultMetadataNames = ['copyright', 'alt', 'title'];
        foreach ($defaultMetadataNames as $defaultMetadata) {
            $defaultColumns[] = ['title' => $defaultMetadata, 'name' => $defaultMetadata, 'datatype' => 'data', 'fieldtype' => 'input'];
        }
        $result['defaultColumns']['nodeLabel'] = 'default_metadata';
        $result['defaultColumns']['nodeType'] = 'image';
        $result['defaultColumns']['childs'] = $defaultColumns;

        //predefined metadata
        $list = Metadata\Predefined\Listing::getByTargetType('asset', null);
        $metadataItems = [];
        $tmp = [];
        /** @var Metadata\Predefined $item */
        foreach ($list as $item) {
            //only allow unique metadata columns with subtypes
            $uniqueKey = $item->getName().'_'.$item->getTargetSubtype();
            if (!in_array($uniqueKey, $tmp) && !in_array($item->getName(), $defaultMetadataNames)) {
                $tmp[] = $uniqueKey;
                $item->expand();
                $metadataItems[] = [
                    'title' => $item->getName(),
                    'name' => $item->getName(),
                    'subtype' => $item->getTargetSubtype(),
                    'datatype' => 'data',
                    'fieldtype' => $item->getType(),
                    'config' => $item->getConfig(),
                ];
            }
        }

        $result['metadataColumns']['childs'] = $metadataItems;
        $result['metadataColumns']['nodeLabel'] = 'predefined_metadata';
        $result['metadataColumns']['nodeType'] = 'metadata';

        //system columns
        $systemColumnNames = Asset\Service::$gridSystemColumns;
        $systemColumns = [];
        foreach ($systemColumnNames as $systemColumn) {
            $systemColumns[] = ['title' => $systemColumn, 'name' => $systemColumn, 'datatype' => 'data', 'fieldtype' => 'system'];
        }
        $result['systemColumns']['nodeLabel'] = 'system_columns';
        $result['systemColumns']['nodeType'] = 'system';
        $result['systemColumns']['childs'] = $systemColumns;

        return $this->adminJson($result);
    }

    /**
     * @Route("/get-batch-jobs", name="pimcore_admin_asset_assethelper_getbatchjobs", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getBatchJobsAction(Request $request, GridHelperService $gridHelperService)
    {
        if ($request->get('language')) {
            $request->setLocale($request->get('language'));
        }

        $allParams = array_merge($request->request->all(), $request->query->all());
        $list = $gridHelperService->prepareAssetListingForGrid($allParams, $this->getAdminUser());

        $jobs = $list->loadIdList();

        return $this->adminJson(['success' => true, 'jobs' => $jobs]);
    }

    /**
     * @Route("/batch", name="pimcore_admin_asset_assethelper_batch", methods={"PUT"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function batchAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        try {
            if ($request->get('data')) {
                $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');

                $data = $this->decodeJson($request->get('data'), true);

                $updateEvent = new GenericEvent($this, [
                    'data' => $data,
                    'processed' => false,
                ]);

                $eventDispatcher->dispatch(AdminEvents::ASSET_LIST_BEFORE_BATCH_UPDATE, $updateEvent);

                $processed = $updateEvent->getArgument('processed');

                if ($processed) {
                    return $this->adminJson(['success' => true]);
                }

                $language = null;
                if (isset($data['language'])) {
                    $language = $data['language'] != 'default' ? $data['language'] : null;
                }

                $asset = Asset::getById($data['job']);

                if ($asset) {
                    if (!$asset->isAllowed('publish')) {
                        throw new \Exception("Permission denied. You don't have the rights to save this asset.");
                    }

                    $metadata = $asset->getMetadata(null, null, false, true);
                    $dirty = false;

                    $name = $data['name'];
                    $value = $data['value'];

                    if ($data['valueType'] == 'object') {
                        $value = $this->decodeJson($value);
                    }

                    $fieldDef = explode('~', $name);
                    $name = $fieldDef[0];
                    if (count($fieldDef) > 1) {
                        $language = ($fieldDef[1] == 'none' ? '' : $fieldDef[1]);
                    }

                    foreach ($metadata as $idx => &$em) {
                        if ($em['name'] == $name && $em['language'] == $language) {
                            try {
                                $dataImpl = $loader->build($em['type']);
                                $value = $dataImpl->getDataFromListfolderGrid($value, $em);
                            } catch (UnsupportedException $le) {
                                Logger::error('could not resolve metadata implementation for ' . $em['type']);
                            }
                            $em['data'] = $value;
                            $dirty = true;
                            break;
                        }
                    }

                    if (!$dirty) {
                        $defaulMetadata = ['title', 'alt', 'copyright'];
                        if (in_array($name, $defaulMetadata)) {
                            $newEm = [
                                'name' => $name,
                                'language' => $language,
                                'type' => 'input',
                                'data' => $value,
                            ];

                            try {
                                $dataImpl = $loader->build($newEm['type']);
                                $newEm['data'] = $dataImpl->getDataFromListfolderGrid($value, $newEm);
                            } catch (UnsupportedException $le) {
                                Logger::error('could not resolve metadata implementation for ' . $newEm['type']);
                            }

                            $metadata[] = $newEm;
                            $dirty = true;
                        } else {
                            $predefined = Metadata\Predefined::getByName($name);
                            if ($predefined && (empty($predefined->getTargetSubtype())
                                    || $predefined->getTargetSubtype() == $asset->getType())) {
                                $newEm = [
                                    'name' => $name,
                                    'language' => $language,
                                    'type' => $predefined->getType(),
                                    'data' => $value,
                                ];

                                try {
                                    $dataImpl = $loader->build($newEm['type']);
                                    $newEm['data'] = $dataImpl->getDataFromListfolderGrid($value, $newEm);
                                } catch (UnsupportedException $le) {
                                    Logger::error('could not resolve metadata implementation for ' . $newEm['type']);
                                }

                                $metadata[] = $newEm;

                                $dirty = true;
                            }
                        }
                    }

                    try {
                        if ($dirty) {
                            // $metadata = Asset\Service::minimizeMetadata($metadata, "grid");
                            $asset->setMetadataRaw($metadata);
                            $asset->save();

                            return $this->adminJson(['success' => true]);
                        }
                    } catch (\Exception $e) {
                        return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    Logger::debug('AssetHelperController::batchAction => There is no asset left to update.');

                    return $this->adminJson(['success' => false, 'message' => 'AssetHelperController::batchAction => There is no asset left to update.']);
                }
            }
        } catch (\Exception $e) {
            Logger::err($e);

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }

        return $this->adminJson(['success' => false, 'message' => 'something went wrong.']);
    }
}
