<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject;

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Event\AdminEvents;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\GridConfig;
use Pimcore\Model\GridConfigFavourite;
use Pimcore\Model\GridConfigShare;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Version;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/object-helper")
 *
 * @internal
 */
class DataObjectHelperController extends AdminController
{
    const SYSTEM_COLUMNS = ['id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname'];

    /**
     * @Route("/load-object-data", name="pimcore_admin_dataobject_dataobjecthelper_loadobjectdata", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loadObjectDataAction(Request $request)
    {
        $object = DataObject::getById($request->get('id'));
        $result = [];
        if ($object) {
            $result['success'] = true;
            $fields = $request->get('fields');
            $result['fields'] = DataObject\Service::gridObjectData($object, $fields);
        } else {
            $result['success'] = false;
        }

        return $this->adminJson($result);
    }

    /**
     * @param int $userId
     * @param string $classId
     * @param string $searchType
     *
     * @return array
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

        $configData = [];
        if (is_array($configListing)) {
            foreach ($configListing as $config) {
                $configData[] = $config->getObjectVars();
            }
        }

        return $configData;
    }

    /**
     * @param User $user
     * @param string $classId
     * @param string $searchType
     *
     * @return array
     */
    public function getSharedGridColumnConfigs($user, $classId, $searchType = null)
    {
        $configListing = [];

        $userIds = [$user->getId()];
        // collect all roles
        $userIds = array_merge($userIds, $user->getRoles());
        $userIds = implode(',', $userIds);
        $db = Db::get();

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

        $configData = [];
        if (is_array($configListing)) {
            foreach ($configListing as $config) {
                $configData[] = $config->getObjectVars();
            }
        }

        return $configData;
    }

    /**
     * @Route("/get-export-configs", name="pimcore_admin_dataobject_dataobjecthelper_getexportconfigs", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getExportConfigsAction(Request $request)
    {
        $classId = $request->get('classId');
        $list = $this->getMyOwnGridColumnConfigs($this->getAdminUser()->getId(), $classId, null);
        if (!is_array($list)) {
            $list = [];
        }
        $list = array_merge($list, $this->getSharedGridColumnConfigs($this->getAdminUser(), $classId, null));
        $result = [];

        $result[] = [
            'id' => -1,
            'name' => '--default--',
        ];

        if ($list) {
            /** @var GridConfig $config */
            foreach ($list as $config) {
                $result[] = [
                    'id' => $config['id'],
                    'name' => $config['name'],
                ];
            }
        }

        return $this->adminJson(['success' => true, 'data' => $result]);
    }

    /**
     * @Route("/grid-delete-column-config", name="pimcore_admin_dataobject_dataobjecthelper_griddeletecolumnconfig", methods={"DELETE"})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return JsonResponse
     */
    public function gridDeleteColumnConfigAction(Request $request, Config $config)
    {
        $gridConfigId = $request->get('gridConfigId');
        $gridConfig = null;
        try {
            $gridConfig = GridConfig::getById($gridConfigId);
        } catch (\Exception $e) {
        }
        $success = false;
        if ($gridConfig) {
            if ($gridConfig->getOwnerId() != $this->getAdminUser()->getId() && !$this->getAdminUser()->isAdmin()) {
                throw new \Exception("don't mess with someone elses grid config");
            }

            $gridConfig->delete();
            $success = true;
        }

        $newGridConfig = $this->doGetGridColumnConfig($request, $config, true);
        $newGridConfig['deleteSuccess'] = $success;

        return $this->adminJson($newGridConfig);
    }

    /**
     * @Route("/grid-get-column-config", name="pimcore_admin_dataobject_dataobjecthelper_gridgetcolumnconfig", methods={"GET"})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return JsonResponse
     */
    public function gridGetColumnConfigAction(Request $request, Config $config)
    {
        $result = $this->doGetGridColumnConfig($request, $config);

        return $this->adminJson($result);
    }

    /**
     * @param Request $request
     * @param Config $config
     * @param bool $isDelete
     *
     * @return array
     */
    public function doGetGridColumnConfig(Request $request, Config $config, $isDelete = false)
    {
        $class = null;
        $fields = null;

        if ($request->get('id')) {
            $class = DataObject\ClassDefinition::getById($request->get('id'));
        } elseif ($request->get('name')) {
            $class = DataObject\ClassDefinition::getByName($request->get('name'));
        }

        $gridConfigId = null;
        $gridType = 'search';
        if ($request->get('gridtype')) {
            $gridType = $request->get('gridtype');
        }

        $objectId = $request->get('objectId');

        if ($objectId) {
            $fields = DataObject\Service::getCustomGridFieldDefinitions($class->getId(), $objectId);
        }

        $context = ['purpose' => 'gridconfig'];
        if ($class) {
            $context['class'] = $class;
        }

        if ($objectId) {
            $object = DataObject::getById($objectId);
            $context['object'] = $object;
        }

        if (!$fields && $class) {
            $fields = $class->getFieldDefinitions();
        }

        $types = [];
        if ($request->get('types')) {
            $types = explode(',', $request->get('types'));
        }

        $userId = $this->getAdminUser()->getId();

        $requestedGridConfigId = $isDelete ? null : $request->get('gridConfigId');

        // grid config
        $gridConfig = [];
        $searchType = $request->get('searchType');

        if (strlen($requestedGridConfigId) == 0 && $class) {
            // check if there is a favourite view
            $favourite = null;
            try {
                try {
                    $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $class->getId(), $objectId ? $objectId : 0, $searchType);
                } catch (\Exception $e) {
                }
                if (!$favourite && $objectId) {
                    $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $class->getId(), 0, $searchType);
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
            $configListingConditionParts[] = 'classId = ' . $db->quote($class->getId());

            if ($searchType) {
                $configListingConditionParts[] = 'searchType = ' . $db->quote($searchType);
            }

            $savedGridConfig = null;
            try {
                $savedGridConfig = GridConfig::getById($requestedGridConfigId);
            } catch (\Exception $e) {
            }

            if ($savedGridConfig) {
                $shared = false;
                if (!$this->getAdminUser()->isAdmin()) {
                    try {
                        $userIds = [$this->getAdminUser()->getId()];
                        if ($this->getAdminUser()->getRoles()) {
                            $userIds = array_merge($userIds, $this->getAdminUser()->getRoles());
                        }
                        $userIds = implode(',', $userIds);
                        $shared = ($savedGridConfig->getOwnerId() != $userId && $savedGridConfig->isShareGlobally()) || $db->fetchOne('select 1 from gridconfig_shares where sharedWithUserId IN ('.$userIds.') and gridConfigId = '.$savedGridConfig->getId());
//                    $shared = $savedGridConfig->isShareGlobally() ||GridConfigShare::getByGridConfigAndSharedWithId($savedGridConfig->getId(), $this->getUser()->getId());
                    } catch (\Exception $e) {
                    }

                    if (!$shared && $savedGridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                        throw new \Exception('you are neither the onwner of this config nor it is shared with you');
                    }
                }

                $gridConfigId = $savedGridConfig->getId();
                $gridConfig = $savedGridConfig->getConfig();
                $gridConfig = json_decode($gridConfig, true);
                $gridConfigName = $savedGridConfig->getName();
                $owner = $savedGridConfig->getOwnerId();
                $ownerObject = User::getById($owner);
                if ($ownerObject instanceof User) {
                    $owner = $ownerObject->getName();
                }
                $modificationDate = $savedGridConfig->getModificationDate();
                $gridConfigDescription = $savedGridConfig->getDescription();
                $sharedGlobally = $savedGridConfig->isShareGlobally();
            }
        }

        $localizedFields = [];
        $objectbrickFields = [];
        if (is_array($fields)) {
            foreach ($fields as $key => $field) {
                if ($field instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $localizedFields[] = $field;
                } elseif ($field instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                    $objectbrickFields[] = $field;
                }
            }
        }

        $availableFields = [];

        if (empty($gridConfig)) {
            $availableFields = $this->getDefaultGridFields(
                $request->get('no_system_columns'),
                $class,
                $gridType,
                $request->get('no_brick_columns'),
                $fields,
                $context,
                $objectId,
                $types);
        } else {
            $savedColumns = $gridConfig['columns'];
            foreach ($savedColumns as $key => $sc) {
                if (!$sc['hidden']) {
                    if (in_array($key, self::SYSTEM_COLUMNS)) {
                        $colConfig = [
                            'key' => $key,
                            'type' => 'system',
                            'label' => $key,
                            'locked' => $sc['locked'] ?? null,
                            'position' => $sc['position'],
                        ];
                        if (isset($sc['width'])) {
                            $colConfig['width'] = $sc['width'];
                        }
                        $availableFields[] = $colConfig;
                    } else {
                        $keyParts = explode('~', $key);

                        if (substr($key, 0, 1) == '~') {
                            // not needed for now
                            $type = $keyParts[1];
                            //                            $field = $keyParts[2];
                            $groupAndKeyId = explode('-', $keyParts[3]);
                            $keyId = $groupAndKeyId[1];

                            if ($type == 'classificationstore') {
                                $keyDef = DataObject\Classificationstore\KeyConfig::getById($keyId);
                                if ($keyDef) {
                                    $keyFieldDef = json_decode($keyDef->getDefinition(), true);
                                    if ($keyFieldDef) {
                                        $keyFieldDef = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($keyFieldDef, $keyDef->getType());
                                        $fieldConfig = $this->getFieldGridConfig($keyFieldDef, $gridType, $sc['position'], true, null, $class, $objectId);
                                        if ($fieldConfig) {
                                            $fieldConfig['key'] = $key;
                                            $fieldConfig['label'] = '#' . $keyFieldDef->getTitle();
                                            if (isset($sc['locked'])) {
                                                $fieldConfig['locked'] = $sc['locked'];
                                            }
                                            $availableFields[] = $fieldConfig;
                                        }
                                    }
                                }
                            }
                        } elseif (count($keyParts) > 1) {
                            $brick = $keyParts[0];
                            $brickDescriptor = null;

                            if (strpos($brick, '?') !== false) {
                                $brickDescriptor = substr($brick, 1);
                                $brickDescriptor = json_decode($brickDescriptor, true);
                                $keyPrefix = $brick . '~';
                                $brick = $brickDescriptor['containerKey'];
                            } else {
                                $keyPrefix = $brick . '~';
                            }

                            $fieldname = $keyParts[1];

                            $brickClass = DataObject\Objectbrick\Definition::getByKey($brick);

                            $fd = null;
                            if ($brickClass instanceof DataObject\Objectbrick\Definition) {
                                if ($brickDescriptor) {
                                    $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                                    /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedFields */
                                    $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                                    $fd = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                                } else {
                                    $fd = $brickClass->getFieldDefinition($fieldname);
                                }
                            }

                            if ($fd !== null) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, $keyPrefix, $class, $objectId);
                                if (!empty($fieldConfig)) {
                                    if (isset($sc['width'])) {
                                        $fieldConfig['width'] = $sc['width'];
                                    }
                                    if (isset($sc['locked'])) {
                                        $fieldConfig['locked'] = $sc['locked'];
                                    }
                                    $availableFields[] = $fieldConfig;
                                }
                            }
                        } else {
                            if (DataObject\Service::isHelperGridColumnConfig($key)) {
                                $calculatedColumnConfig = $this->getCalculatedColumnConfig($savedColumns[$key]);
                                if ($calculatedColumnConfig) {
                                    $availableFields[] = $calculatedColumnConfig;
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
                                    $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, null, $class, $objectId);
                                    if (!empty($fieldConfig)) {
                                        if (isset($sc['width'])) {
                                            $fieldConfig['width'] = $sc['width'];
                                        }
                                        if (isset($sc['locked'])) {
                                            $fieldConfig['locked'] = $sc['locked'];
                                        }
                                        $availableFields[] = $fieldConfig;
                                    }
                                }
                            }
                        }
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

        $frontendLanguages = Tool\Admin::reorderWebsiteLanguages(\Pimcore\Tool\Admin::getCurrentUser(), $config['general']['valid_languages']);
        if ($frontendLanguages) {
            $language = explode(',', $frontendLanguages)[0];
        } else {
            $language = $request->getLocale();
        }

        if (!Tool::isValidLanguage($language)) {
            $validLanguages = Tool::getValidLanguages();
            $language = $validLanguages[0];
        }

        if (!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }

        if (!empty($gridConfig) && !empty($gridConfig['pageSize'])) {
            $pageSize = $gridConfig['pageSize'];
        }

        $availableConfigs = $class ? $this->getMyOwnGridColumnConfigs($userId, $class->getId(), $searchType) : [];
        $sharedConfigs = $class ? $this->getSharedGridColumnConfigs($this->getAdminUser(), $class->getId(), $searchType) : [];
        $settings = $this->getShareSettings((int)$gridConfigId);
        $settings['gridConfigId'] = (int)$gridConfigId;
        $settings['gridConfigName'] = $gridConfigName ?? null;
        $settings['gridConfigDescription'] = $gridConfigDescription ?? null;
        $settings['owner'] = $owner ?? null;
        $settings['modificationDate'] = $modificationDate ?? null;
        $settings['shareGlobally'] = $sharedGlobally ?? null;
        $settings['isShared'] = !$gridConfigId || ($shared ?? null);

        $context = $gridConfig['context'] ?? null;
        if ($context) {
            $context = json_decode($context, true);
        }

        return [
            'sortinfo' => $gridConfig['sortinfo'] ?? false,
            'language' => $language,
            'availableFields' => $availableFields,
            'settings' => $settings,
            'onlyDirectChildren' => $gridConfig['onlyDirectChildren'] ?? false,
            'pageSize' => $gridConfig['pageSize'] ?? false,
            'availableConfigs' => $availableConfigs,
            'sharedConfigs' => $sharedConfigs,
            'context' => $context,
            'sqlFilter' => $gridConfig['sqlFilter'] ?? '',
            'searchFilter' => $gridConfig['searchFilter'] ?? '',
        ];
    }

    /**
     * @param bool $noSystemColumns
     * @param DataObject\ClassDefinition|null $class
     * @param string $gridType
     * @param bool $noBrickColumns
     * @param DataObject\ClassDefinition\Data[] $fields
     * @param array $context
     * @param int $objectId
     * @param array $types
     *
     * @return array
     */
    public function getDefaultGridFields($noSystemColumns, $class, $gridType, $noBrickColumns, $fields, $context, $objectId, $types = [])
    {
        $count = 0;
        $availableFields = [];

        if (!$noSystemColumns && $class) {
            $vis = $class->getPropertyVisibility();
            foreach (self::SYSTEM_COLUMNS as $sc) {
                $key = $sc;
                if ($key === 'fullpath') {
                    $key = 'path';
                }

                if (empty($types) && (!empty($vis[$gridType][$key]) || $gridType === 'all')) {
                    $availableFields[] = [
                        'key' => $sc,
                        'type' => 'system',
                        'label' => $sc,
                        'position' => $count, ];
                    $count++;
                }
            }
        }

        $includeBricks = !$noBrickColumns;

        if (is_array($fields)) {
            foreach ($fields as $key => $field) {
                if ($field instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    foreach ($field->getFieldDefinitions($context) as $fd) {
                        if (empty($types) || in_array($fd->getFieldType(), $types)) {
                            $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $count, false, null, $class, $objectId);
                            if (!empty($fieldConfig)) {
                                $availableFields[] = $fieldConfig;
                                $count++;
                            }
                        }
                    }
                } elseif ($field instanceof DataObject\ClassDefinition\Data\Objectbricks && $includeBricks) {
                    if (in_array($field->getFieldType(), $types)) {
                        $fieldConfig = $this->getFieldGridConfig($field, $gridType, $count, false, null, $class, $objectId);
                        if (!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    } else {
                        $allowedTypes = $field->getAllowedTypes();
                        if (!empty($allowedTypes)) {
                            foreach ($allowedTypes as $t) {
                                $brickClass = DataObject\Objectbrick\Definition::getByKey($t);
                                $brickFields = $brickClass->getFieldDefinitions($context);

                                $this->appendBrickFields($field, $brickFields, $availableFields, $gridType, $count, $t, $class, $objectId);
                            }
                        }
                    }
                } else {
                    if (empty($types) || in_array($field->getFieldType(), $types)) {
                        $fieldConfig = $this->getFieldGridConfig($field, $gridType, $count, !empty($types), null, $class, $objectId);
                        if (!empty($fieldConfig)) {
                            $availableFields[] = $fieldConfig;
                            $count++;
                        }
                    }
                }
            }
        }

        return $availableFields;
    }

    /**
     * @param DataObject\ClassDefinition\Data $field
     * @param DataObject\ClassDefinition\Data[] $brickFields
     * @param array $availableFields
     * @param string $gridType
     * @param int $count
     * @param string $brickType
     * @param DataObject\ClassDefinition $class
     * @param int $objectId
     * @param array|null $context
     */
    protected function appendBrickFields($field, $brickFields, &$availableFields, $gridType, &$count, $brickType, $class, $objectId, $context = null)
    {
        if (!empty($brickFields)) {
            foreach ($brickFields as $bf) {
                if ($bf instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $localizedFieldDefinitions = $bf->getFieldDefinitions();

                    $localizedContext = [
                        'containerKey' => $brickType,
                        'fieldname' => $field->getName(),
                    ];

                    $this->appendBrickFields($bf, $localizedFieldDefinitions, $availableFields, $gridType, $count, $brickType, $class, $objectId, $localizedContext);
                } else {
                    if ($context) {
                        $context['brickfield'] = $bf->getName();
                        $keyPrefix = '?' . json_encode($context) . '~';
                    } else {
                        $keyPrefix = $brickType . '~';
                    }
                    $fieldConfig = $this->getFieldGridConfig($bf, $gridType, $count, false, $keyPrefix, $class, $objectId);
                    if (!empty($fieldConfig)) {
                        $availableFields[] = $fieldConfig;
                        $count++;
                    }
                }
            }
        }
    }

    /**
     * @param array $config
     *
     * @return mixed
     */
    protected function getCalculatedColumnConfig($config)
    {
        try {
            $calculatedColumnConfig = Tool\Session::useSession(function (AttributeBagInterface $session) use ($config) {

                //otherwise create a new one

                $calculatedColumn = [];
                // note that we have to generate a new key!

                $existingKey = $config['fieldConfig']['key'];
                $calculatedColumnConfig['key'] = $existingKey;
                $calculatedColumnConfig['position'] = $config['position'];
                $calculatedColumnConfig['isOperator'] = true;
                $calculatedColumnConfig['attributes'] = $config['fieldConfig']['attributes'];
                $calculatedColumnConfig['width'] = $config['width'];
                $calculatedColumnConfig['locked'] = $config['locked'];

                $existingColumns = $session->get('helpercolumns', []);

                if (isset($existingColumns[$existingKey])) {
                    // if the configuration is still in the session, then reuse it
                    return $calculatedColumnConfig;
                }

                $newKey = '#' . uniqid();
                $calculatedColumnConfig['key'] = $newKey;

                // prepare a column config on the fly
                $phpConfig = json_encode($config['fieldConfig']);
                $phpConfig = json_decode($phpConfig);
                $helperColumns = [];
                $helperColumns[$newKey] = $phpConfig;

                $helperColumns = array_merge($helperColumns, $existingColumns);
                $session->set('helpercolumns', $helperColumns);

                return $calculatedColumnConfig;
            }, 'pimcore_gridconfig');

            return $calculatedColumnConfig;
        } catch (\Exception $e) {
            Logger::error($e);
        }
    }

    /**
     * @Route("/prepare-helper-column-configs", name="pimcore_admin_dataobject_dataobjecthelper_preparehelpercolumnconfigs", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function prepareHelperColumnConfigs(Request $request)
    {
        $helperColumns = [];
        $newData = [];
        /** @var \stdClass[] $data */
        $data = json_decode($request->get('columns'));
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
     * @Route("/grid-config-apply-to-all", name="pimcore_admin_dataobject_dataobjecthelper_gridconfigapplytoall", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridConfigApplyToAllAction(Request $request)
    {
        $objectId = $request->get('objectId');
        $object = DataObject::getById($objectId);

        if ($object->isAllowed('list')) {
            $classId = $request->get('classId');
            $gridConfigId = $request->get('gridConfigId');
            $searchType = $request->get('searchType');
            $user = $this->getAdminUser();
            $db = Db::get();
            $db->query('delete from gridconfig_favourites where '
                . 'ownerId = ' . $user->getId()
                . ' and classId = ' . $db->quote($classId) .
                ' and searchType = ' . $db->quote($searchType)
                . ' and objectId != ' . $objectId . ' and objectId != 0');

            return $this->adminJson(['success' => true]);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/grid-mark-favourite-column-config", name="pimcore_admin_dataobject_dataobjecthelper_gridmarkfavouritecolumnconfig", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridMarkFavouriteColumnConfigAction(Request $request)
    {
        $objectId = $request->get('objectId');
        $object = DataObject::getById($objectId);

        if ($object->isAllowed('list')) {
            $classId = $request->get('classId');
            $gridConfigId = $request->get('gridConfigId');
            $searchType = $request->get('searchType');
            $global = $request->get('global');
            $user = $this->getAdminUser();
            $type = $request->get('type');

            $favourite = new GridConfigFavourite();
            $favourite->setOwnerId($user->getId());
            $class = DataObject\ClassDefinition::getById($classId);
            if (!$class) {
                throw new \Exception('class ' . $classId . ' does not exist anymore');
            }
            $favourite->setClassId($classId);
            $favourite->setSearchType($searchType);
            $favourite->setType($type);
            $specializedConfigs = false;

            try {
                if ($gridConfigId != 0) {
                    $gridConfig = GridConfig::getById($gridConfigId);
                    $favourite->setGridConfigId($gridConfig->getId());
                }
                $favourite->setObjectId($objectId);
                $favourite->save();

                if ($global) {
                    $favourite->setObjectId(0);
                    $favourite->save();
                }
                $db = Db::get();
                $count = $db->fetchOne('select * from gridconfig_favourites where '
                    . 'ownerId = ' . $user->getId()
                    . ' and classId = ' . $db->quote($classId).
                    ' and searchType = ' . $db->quote($searchType)
                    . ' and objectId != ' . $objectId . ' and objectId != 0'
                    . ' and type != ' . $db->quote($type));
                $specializedConfigs = $count > 0;
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
     * @Route("/grid-save-column-config", name="pimcore_admin_dataobject_dataobjecthelper_gridsavecolumnconfig", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridSaveColumnConfigAction(Request $request)
    {
        $object = DataObject::getById($request->get('id'));

        if ($object->isAllowed('list')) {
            try {
                $classId = $request->get('class_id');
                $context = $request->get('context');

                $searchType = $request->get('searchType');

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
                if ($gridConfig && $gridConfig->getOwnerId() != $this->getAdminUser()->getId() && !$this->getAdminUser()->isAdmin()) {
                    throw new \Exception("don't mess around with somebody elses configuration");
                }

                $this->updateGridConfigShares($gridConfig, $metadata);

                if (!$gridConfig) {
                    $gridConfig = new GridConfig();
                    $gridConfig->setName(date('c'));
                    $gridConfig->setClassId($classId);
                    $gridConfig->setSearchType($searchType);

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
                $settings['isShared'] = $gridConfig->getOwnerId() != $this->getAdminUser()->getId() && !$this->getAdminUser()->isAdmin();

                return $this->adminJson([
                    'success' => true,
                    'settings' => $settings,
                    'availableConfigs' => $availableConfigs,
                    'sharedConfigs' => $sharedConfigs,
                ]);
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param GridConfig|null $gridConfig
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

        if ($gridConfig->getOwnerId() != $user->getId() && !$user->isAdmin()) {
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
     * @param DataObject\ClassDefinition\Data $field
     * @param string $gridType
     * @param string $position
     * @param bool $force
     * @param string|null $keyPrefix
     * @param DataObject\ClassDefinition|null $class
     * @param int|null $objectId
     *
     * @return array|null
     */
    protected function getFieldGridConfig($field, $gridType, $position, $force = false, $keyPrefix = null, $class = null, $objectId = null)
    {
        $key = $keyPrefix . $field->getName();
        $config = null;
        $title = $field->getName();
        if (method_exists($field, 'getTitle')) {
            if ($field->getTitle()) {
                $title = $field->getTitle();
            }
        }

        if ($field instanceof DataObject\ClassDefinition\Data\Slider) {
            $config['minValue'] = $field->getMinValue();
            $config['maxValue'] = $field->getMaxValue();
            $config['increment'] = $field->getIncrement();
        }

        if (method_exists($field, 'getWidth')) {
            $config['width'] = $field->getWidth();
        }
        if (method_exists($field, 'getHeight')) {
            $config['height'] = $field->getHeight();
        }

        $visible = false;
        if ($gridType == 'search') {
            $visible = $field->getVisibleSearch();
        } elseif ($gridType == 'grid') {
            $visible = $field->getVisibleGridView();
        } elseif ($gridType == 'all') {
            $visible = true;
        }

        if (!$field->getInvisible() && ($force || $visible)) {
            $context = ['purpose' => 'gridconfig'];
            if ($class) {
                $context['class'] = $class;
            }

            if ($objectId) {
                $object = DataObject::getById($objectId);
                $context['object'] = $object;
            }
            DataObject\Service::enrichLayoutDefinition($field, null, $context);

            $result = [
                'key' => $key,
                'type' => $field->getFieldType(),
                'label' => $title,
                'config' => $config,
                'layout' => $field,
                'position' => $position,
            ];

            if ($field instanceof DataObject\ClassDefinition\Data\EncryptedField) {
                $result['delegateDatatype'] = $field->getDelegateDatatype();
            }

            return $result;
        } else {
            return null;
        }
    }

    /**
     * IMPORTER
     */

    /**
     * @Route("/import-upload", name="pimcore_admin_dataobject_dataobjecthelper_importupload", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importUploadAction(Request $request)
    {
        $data = file_get_contents($_FILES['Filedata']['tmp_name']);
        $data = Tool\Text::convertToUTF8($data);

        $importId = $request->get('importId');
        $importId = str_replace('..', '', $importId);
        $importFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_' . $importId;
        File::put($importFile, $data);

        $importFileOriginal = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_' . $importId . '_original';
        File::put($importFileOriginal, $data);

        $response = $this->adminJson([
            'success' => true,
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    private function getDataPreview($originalFile, $dialect)
    {
        $count = 0;
        $data = [];
        if (($handle = fopen($originalFile, 'r')) !== false) {
            while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                $tmpData = [];

                foreach ($rowData as $key => $value) {
                    $tmpData['field_' . $key] = $value;
                }

                $tmpData['rowId'] = $count + 1;
                $data[] = $tmpData;

                $count++;

                /**
                 * Reached the number or rows for the preview
                 */
                if ($count > 18) {
                    break;
                }
            }
            fclose($handle);
        }

        return $data;
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
     * @Route("/get-export-jobs", name="pimcore_admin_dataobject_dataobjecthelper_getexportjobs", methods={"GET"})
     *
     * @param Request $request
     * @param GridHelperService $gridHelperService
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function getExportJobsAction(Request $request, GridHelperService $gridHelperService, EventDispatcherInterface $eventDispatcher)
    {
        $requestedLanguage = $this->extractLanguage($request);
        $allParams = array_merge($request->request->all(), $request->query->all());

        $list = $gridHelperService->prepareListingForGrid($allParams, $requestedLanguage, $this->getAdminUser());

        $beforeListPrepareEvent = new GenericEvent($this, [
            'list' => $list,
            'context' => $allParams,
        ]);
        $eventDispatcher->dispatch($beforeListPrepareEvent, AdminEvents::OBJECT_LIST_BEFORE_EXPORT_PREPARE);

        $list = $beforeListPrepareEvent->getArgument('list');

        $ids = $list->loadIdList();

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('export-');
        file_put_contents($this->getCsvFile($fileHandle), '');

        return $this->adminJson(['success' => true, 'jobs' => $jobs, 'fileHandle' => $fileHandle]);
    }

    /**
     * @Route("/do-export", name="pimcore_admin_dataobject_dataobjecthelper_doexport", methods={"POST"})
     *
     * @param Request $request
     * @param LocaleServiceInterface $localeService
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function doExportAction(Request $request, LocaleServiceInterface $localeService, EventDispatcherInterface $eventDispatcher)
    {
        $fileHandle = \Pimcore\File::getValidFilename($request->get('fileHandle'));
        $ids = $request->get('ids');
        $settings = $request->get('settings');
        $settings = json_decode($settings, true);
        $delimiter = $settings['delimiter'] ?? ';';

        $allParams = array_merge($request->request->all(), $request->query->all());

        $enableInheritance = $settings['enableInheritance'] ?? null;
        DataObject\Concrete::setGetInheritedValues($enableInheritance);

        $class = DataObject\ClassDefinition::getById($request->get('classId'));

        if (!$class) {
            throw new \Exception('No class definition found');
        }

        $className = $class->getName();
        $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';

        /** @var \Pimcore\Model\DataObject\Listing $list */
        $list = new $listClass();

        $quotedIds = [];
        foreach ($ids as $id) {
            $quotedIds[] = $list->quote($id);
        }

        $list->setObjectTypes(DataObject::$types);
        $list->setCondition('o_id IN (' . implode(',', $quotedIds) . ')');
        $list->setOrderKey(' FIELD(o_id, ' . implode(',', $quotedIds) . ')', false);

        $beforeListExportEvent = new GenericEvent($this, [
            'list' => $list,
            'context' => $allParams,
        ]);
        $eventDispatcher->dispatch($beforeListExportEvent, AdminEvents::OBJECT_LIST_BEFORE_EXPORT);

        $list = $beforeListExportEvent->getArgument('list');

        $fields = $request->get('fields');

        $addTitles = $request->get('initial');

        $requestedLanguage = $this->extractLanguage($request);

        $contextFromRequest = $request->get('context');
        if ($contextFromRequest) {
            $contextFromRequest = json_decode($contextFromRequest, true);
        }

        $context = [
            'source' => 'pimcore-export',
        ];

        if (is_array($contextFromRequest)) {
            $context = array_merge($context, $contextFromRequest);
        }

        $csv = DataObject\Service::getCsvData($requestedLanguage, $localeService, $list, $fields, $addTitles, $context);

        $fp = fopen($this->getCsvFile($fileHandle), 'a');

        $firstLine = true;
        $lineCount = count($csv);

        if (!$addTitles && $lineCount > 0) {
            fwrite($fp, "\r\n");
        }

        for ($i = 0; $i < $lineCount; $i++) {
            $line = $csv[$i];
            if ($addTitles && $firstLine) {
                $firstLine = false;
                $line = implode($delimiter, $line);
                fwrite($fp, $line);
            } else {
                fwrite($fp, implode($delimiter, array_map([$this, 'encodeFunc'], $line)));
            }
            if ($i < $lineCount - 1) {
                fwrite($fp, "\r\n");
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
     * @Route("/download-csv-file", name="pimcore_admin_dataobject_dataobjecthelper_downloadcsvfile", methods={"GET"})
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
     * @Route("/download-xlsx-file", name="pimcore_admin_dataobject_dataobjecthelper_downloadxlsxfile", methods={"GET"})
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
     * Flattens object data to an array with key=>value where
     * value is simply a string representation of the value (for objects, hrefs and assets the full path is used)
     *
     * @param DataObject\Concrete $object
     *
     * @return array
     */
    protected function csvObjectData($object)
    {
        $o = [];
        foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
            //exclude remote owner fields
            if (!$value instanceof DataObject\ClassDefinition\Data\ReverseObjectRelation) {
                $o[$key] = $value->getForCsvExport($object);
            }
        }

        $o['id (system)'] = $object->getId();
        $o['key (system)'] = $object->getKey();
        $o['fullpath (system)'] = $object->getRealFullPath();
        $o['published (system)'] = $object->isPublished();
        $o['type (system)'] = $object->getType();

        return $o;
    }

    /**
     * @Route("/get-batch-jobs", name="pimcore_admin_dataobject_dataobjecthelper_getbatchjobs", methods={"GET"})
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
        $list = $gridHelperService->prepareListingForGrid($allParams, $request->getLocale(), $this->getAdminUser());

        $jobs = $list->loadIdList();

        return $this->adminJson(['success' => true, 'jobs' => $jobs]);
    }

    /**
     * @Route("/batch", name="pimcore_admin_dataobject_dataobjecthelper_batch", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function batchAction(Request $request)
    {
        $success = true;

        try {
            if ($request->get('data')) {
                $params = $this->decodeJson($request->get('data'), true);
                $object = DataObject\Concrete::getById($params['job']);

                if ($object) {
                    $name = $params['name'];

                    if (!$object->isAllowed('save') || ($name === 'published' && !$object->isAllowed('publish'))) {
                        throw new \Exception("Permission denied. You don't have the rights to save this object.");
                    }

                    $append = $params['append'] ?? false;
                    $remove = $params['remove'] ?? false;

                    $className = $object->getClassName();
                    $class = DataObject\ClassDefinition::getByName($className);
                    $value = $params['value'];
                    if ($params['valueType'] == 'object') {
                        $value = $this->decodeJson($value);
                    }

                    $parts = explode('~', $name);

                    if (substr($name, 0, 1) == '~') {
                        $type = $parts[1];
                        $field = $parts[2];
                        $keyid = $parts[3];

                        if ($type == 'classificationstore') {
                            $requestedLanguage = $params['language'];
                            if ($requestedLanguage) {
                                if ($requestedLanguage != 'default') {
                                    $request->setLocale($requestedLanguage);
                                }
                            } else {
                                $requestedLanguage = $request->getLocale();
                            }

                            $groupKeyId = explode('-', $keyid);
                            $groupId = $groupKeyId[0];
                            $keyid = $groupKeyId[1];

                            $getter = 'get' . ucfirst($field);
                            if (method_exists($object, $getter)) {

                                /** @var DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                                $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                                $csLanguage = $requestedLanguage;
                                if (!$csFieldDefinition->isLocalized()) {
                                    $csLanguage = 'default';
                                }

                                /** @var DataObject\ClassDefinition\Data\Classificationstore $fd */
                                $fd = $class->getFieldDefinition($field);
                                $keyConfig = $fd->getKeyConfiguration($keyid);
                                $dataDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                                /** @var DataObject\Classificationstore $classificationStoreData */
                                $classificationStoreData = $object->$getter();
                                $classificationStoreData->setLocalizedKeyValue(
                                    $groupId,
                                    $keyid,
                                    $dataDefinition->getDataFromEditmode($value),
                                    $csLanguage
                                );
                            }
                        }
                    } elseif (count($parts) > 1) {
                        // check for bricks
                        $brickType = $parts[0];
                        $brickKey = $parts[1];
                        $brickField = DataObject\Service::getFieldForBrickType($object->getClass(), $brickType);

                        $fieldGetter = 'get' . ucfirst($brickField);
                        $brickGetter = 'get' . ucfirst($brickType);
                        $valueSetter = 'set' . ucfirst($brickKey);

                        $brick = $object->$fieldGetter()->$brickGetter();
                        if (empty($brick)) {
                            $classname = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickType);
                            $brickSetter = 'set' . ucfirst($brickType);
                            $brick = new $classname($object);
                            $object->$fieldGetter()->$brickSetter($brick);
                        }

                        $brickClass = DataObject\Objectbrick\Definition::getByKey($brickType);
                        $field = $brickClass->getFieldDefinition($brickKey);

                        $newData = $field->getDataFromEditmode($value, $object);

                        if ($append) {
                            $valueGetter = 'get' . ucfirst($brickKey);
                            $existingData = $brick->$valueGetter();
                            $newData = $field->appendData($existingData, $newData);
                        }
                        if ($remove) {
                            $valueGetter = 'get' . ucfirst($brickKey);
                            $existingData = $brick->$valueGetter();
                            $newData = $field->removeData($existingData, $newData);
                        }
                        $brick->$valueSetter($newData);
                    } else {
                        // everything else
                        $field = $class->getFieldDefinition($name);
                        if ($field) {
                            $newData = $field->getDataFromEditmode($value, $object);

                            if ($append) {
                                $existingData = $object->{'get' . $name}();
                                $newData = $field->appendData($existingData, $newData);
                            }
                            if ($remove) {
                                $existingData = $object->{'get' . $name}();
                                $newData = $field->removeData($existingData, $newData);
                            }
                            $object->setValue($name, $newData);
                        } else {
                            // check if it is a localized field
                            if ($params['language']) {
                                $localizedField = $class->getFieldDefinition('localizedfields');
                                if ($localizedField instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                                    $field = $localizedField->getFieldDefinition($name);
                                    if ($field) {
                                        $getter = 'get' . $name;
                                        $setter = 'set' . $name;
                                        $newData = $field->getDataFromEditmode($value, $object);
                                        if ($append) {
                                            $existingData = $object->$getter($params['language']);
                                            $newData = $field->appendData($existingData, $newData);
                                        }
                                        if ($remove) {
                                            $existingData = $object->$getter($request->get('language'));
                                            $newData = $field->removeData($existingData, $newData);
                                        }

                                        $object->$setter($newData, $params['language']);
                                    }
                                }
                            }

                            // seems to be a system field, this is actually only possible for the "published" field yet
                            if ($name == 'published') {
                                if ($value === 'false' || empty($value)) {
                                    $object->setPublished(false);
                                } else {
                                    $object->setPublished(true);
                                }
                            }
                        }
                    }

                    try {
                        // don't check for mandatory fields here
                        $object->setOmitMandatoryCheck(!$object->isPublished());
                        $object->setUserModification($this->getAdminUser()->getId());
                        $object->save();
                        $success = true;
                    } catch (\Exception $e) {
                        return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    Logger::debug('DataObjectController::batchAction => There is no object left to update.');

                    return $this->adminJson(['success' => false, 'message' => 'DataObjectController::batchAction => There is no object left to update.']);
                }
            }
        } catch (\Exception $e) {
            Logger::err($e);

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/get-available-visible-vields", name="pimcore_admin_dataobject_dataobjecthelper_getavailablevisiblefields", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableVisibleFieldsAction(Request $request)
    {
        $class = null;
        $fields = null;

        $classList = [];
        $classNameList = [];

        if ($request->get('classes')) {
            $classNameList = $request->get('classes');
            $classNameList = explode(',', $classNameList);
            foreach ($classNameList as $className) {
                $class = DataObject\ClassDefinition::getByName($className);
                if ($class) {
                    $classList[] = $class;
                }
            }
        }

        if (!$classList) {
            return $this->adminJson(['availableFields' => []]);
        }
        $availableFields = [];
        foreach (self::SYSTEM_COLUMNS as $field) {
            $availableFields[] = [
                'key' => $field,
                'value' => $field,
            ];
        }

        /** @var DataObject\ClassDefinition\Data[] $commonFields */
        $commonFields = [];

        $firstOne = true;
        foreach ($classNameList as $className) {
            $class = DataObject\ClassDefinition::getByName($className);
            if ($class) {
                $fds = $class->getFieldDefinitions();

                $additionalFieldNames = array_keys($fds);
                $localizedFields = $class->getFieldDefinition('localizedfields');
                if ($localizedFields instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $lfNames = array_keys($localizedFields->getFieldDefinitions());
                    $additionalFieldNames = array_merge($additionalFieldNames, $lfNames);
                }

                foreach ($commonFields as $commonFieldKey => $commonFieldDefinition) {
                    if (!in_array($commonFieldKey, $additionalFieldNames)) {
                        unset($commonFields[$commonFieldKey]);
                    }
                }

                $this->processAvailableFieldDefinitions($fds, $firstOne, $commonFields);

                $firstOne = false;
            }
        }

        $commonFieldKeys = array_keys($commonFields);
        foreach ($commonFieldKeys as $field) {
            $availableFields[] = [
                'key' => $field,
                'value' => $field,
            ];
        }

        return $this->adminJson(['availableFields' => $availableFields]);
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $fds
     * @param bool $firstOne
     * @param DataObject\ClassDefinition\Data[] $commonFields
     */
    protected function processAvailableFieldDefinitions($fds, &$firstOne, &$commonFields)
    {
        foreach ($fds as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\Fieldcollections || $fd instanceof DataObject\ClassDefinition\Data\Objectbricks
                || $fd instanceof DataObject\ClassDefinition\Data\Block) {
                continue;
            }

            if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $lfDefs = $fd->getFieldDefinitions();
                $this->processAvailableFieldDefinitions($lfDefs, $firstOne, $commonFields);
            } elseif ($firstOne || (isset($commonFields[$fd->getName()]) && $commonFields[$fd->getName()]->getFieldtype() == $fd->getFieldtype())) {
                $commonFields[$fd->getName()] = $fd;
            }
        }
    }
}
