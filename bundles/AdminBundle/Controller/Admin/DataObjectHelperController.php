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
use Pimcore\Config;
use Pimcore\DataObject\Import\ColumnConfig\ConfigElementInterface;
use Pimcore\DataObject\Import\Service as ImportService;
use Pimcore\Db;
use Pimcore\Event\DataObjectImportEvents;
use Pimcore\Event\Model\DataObjectImportEvent;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\GridConfig;
use Pimcore\Model\GridConfigFavourite;
use Pimcore\Model\GridConfigShare;
use Pimcore\Model\ImportConfig;
use Pimcore\Model\ImportConfigShare;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Version;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * @Route("/object-helper")
 */
class DataObjectHelperController extends AdminController
{
    const SYSTEM_COLUMNS = ['id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname'];

    /**
     * @Route("/load-object-data")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loadObjectDataAction(Request $request)
    {
        $object = DataObject\AbstractObject::getById($request->get('id'));
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
     * @param $userId
     * @param $classId
     * @param $searchType
     *
     * @return GridConfig\Listing
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
     * @param $user User
     * @param $classId
     * @param $searchType
     *
     * @return GridConfig\Listing
     */
    public function getSharedGridColumnConfigs($user, $classId, $searchType = null)
    {
        $db = Db::get();
        $configListingConditionParts = [];
        $configListingConditionParts[] = 'sharedWithUserId = ' . $user->getId();
        $configListingConditionParts[] = 'classId = ' . $db->quote($classId);

        if ($searchType) {
            $configListingConditionParts[] = 'searchType = ' . $db->quote($searchType);
        }

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

        return $configListing;
    }

    /**
     * @Route("/import-export-config")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param ImportService $importService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function importExportConfigAction(Request $request, ImportService $importService)
    {
        $gridConfigId = $request->get('gridConfigId');

        if ($gridConfigId == -1) {
            $gridConfig = new GridConfig();
            $classId = $request->get('classId');
            $class = DataObject\ClassDefinition::getById($classId);
            // getDefaultGridFields($noSystemColumns, $class, $gridType, $noBrickColumns, $fields, $context, $objectId)

            $fields = $class->getFieldDefinitions();
            $context = ['purpose' => 'gridconfig', 'class' => $class];

            $availableColumns = $this->getDefaultGridFields(false, $class, 'grid', false, $fields, $context, null, []);
            $availableColumns = json_decode(json_encode($availableColumns), true);

            foreach ($availableColumns as &$column) {
                $fieldConfig = [
                    'key' => $column['key'],
                    'label' => $column['label'],
                    'type' => $column['type']
                ];

                $column['fieldConfig'] = $fieldConfig;
            }

            $config = [];
            $config['classId'] = $classId;
            $config['columns'] = $availableColumns;
            $gridConfig->setClassId($classId);
            $gridConfig->setConfig(json_encode($config));
        } else {
            $gridConfig = GridConfig::getById($gridConfigId);
            $user = $this->getAdminUser();
            if ($gridConfig && $gridConfig->getOwnerId() != $user->getId()) {
                $sharedGridConfigs = $this->getSharedGridColumnConfigs($this->getAdminUser(), $gridConfig->getClassId());

                if ($sharedGridConfigs) {
                    $found = false;
                    /** @var $sharedConfig GridConfigShare */
                    foreach ($sharedGridConfigs as $sharedConfig) {
                        if ($sharedConfig->getSharedWithUserId() == $this->getAdminUser()->getId()) {
                            $found = true;
                            break;
                        }
                    }
                }
            } else {
                $found = true;
            }

            if (!$found) {
                throw new \Exception('not allowed to import somebody elses config');
            }
        }

        $importConfigData = $importService->createFromExportConfig($gridConfig);
        $selectedGridColumns = $importConfigData->selectedGridColumns;

        return $this->adminJson(['success' => true, 'selectedGridColumns' => $selectedGridColumns]);
    }

    /**
     * @param ImportService $importService
     * @param $user
     * @param $classId
     *
     * @return array
     */
    private function getImportConfigs(ImportService $importService, $user, $classId)
    {
        $list = $importService->getMyOwnImportConfigs($user, $classId);

        if (!is_array($list)) {
            $list = [];
        }
        $list = array_merge($list, $importService->getSharedImportConfigs($user, $classId));
        $result = [];
        if ($list) {
            /** @var $config ImportConfig */
            foreach ($list as $config) {
                $result[] = [
                    'id' => $config->getId(),
                    'name' => $config->getName()
                ];
            }
        }

        return $result;
    }

    /**
     * @Route("/get-export-configs")
     * @Method({"GET"})
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
            'name' => '--default--'
        ];

        if ($list) {
            /** @var $config Config */
            foreach ($list as $config) {
                $result[] = [
                    'id' => $config->getId(),
                    'name' => $config->getName()
                ];
            }
        }

        return $this->adminJson(['success' => true, 'data' => $result]);
    }

    /**
     * @Route("/delete-import-config")
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteImportConfigAction(Request $request)
    {
        $configId = $request->get('importConfigId');
        try {
            $config = ImportConfig::getById($configId);
        } catch (\Exception $e) {
        }
        $success = false;
        if ($config) {
            if ($config->getOwnerId() != $this->getAdminUser()->getId()) {
                throw new \Exception("don't mess with someone elses grid config");
            }

            $config->delete();
            $success = true;
        }

        return $this->adminJson(['deleteSuccess' => $success]);
    }

    /**
     * @Route("/grid-delete-column-config")
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridDeleteColumnConfigAction(Request $request)
    {
        $gridConfigId = $request->get('gridConfigId');
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
     * @Route("/grid-get-column-config")
     * @Method({"GET"})
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
        $class = null;
        $fields = null;

        /** @var $class DataObject\ClassDefinition */
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
            $object = DataObject\AbstractObject::getById($objectId);
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

            try {
                $savedGridConfig = GridConfig::getById($requestedGridConfigId);
            } catch (\Exception $e) {
            }

            if ($savedGridConfig) {
                try {
                    $userIds = [$this->getAdminUser()->getId()];
                    if ($this->getAdminUser()->getRoles()) {
                        $userIds = array_merge($userIds, $this->getAdminUser()->getRoles());
                    }
                    $userIds = implode(',', $userIds);
                    $shared = ($savedGridConfig->getOwnerId() != $userId && $savedGridConfig->isShareGlobally()) || $db->fetchOne('select * from gridconfig_shares where sharedWithUserId IN (' . $userIds . ') and gridConfigId = ' . $savedGridConfig->getId());

//                    $shared = $savedGridConfig->isShareGlobally() ||GridConfigShare::getByGridConfigAndSharedWithId($savedGridConfig->getId(), $this->getUser()->getId());
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
                            'position' => $sc['position']];
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

                            if ($brickDescriptor) {
                                $innerContainer = $brickDescriptor['innerContainer'] ? $brickDescriptor['innerContainer'] : 'localizedfields';
                                $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                                $fd = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                            } else {
                                $fd = $brickClass->getFieldDefinition($fieldname);
                            }

                            if (!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, $keyPrefix, $class, $objectId);
                                if (!empty($fieldConfig)) {
                                    if (isset($sc['width'])) {
                                        $fieldConfig['width'] = $sc['width'];
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

        if (!empty($gridConfig) && !empty($gridConfig['pageSize'])) {
            $pageSize = $gridConfig['pageSize'];
        }

        $availableConfigs = $class ? $this->getMyOwnGridColumnConfigs($userId, $class->getId(), $searchType) : [];
        $sharedConfigs = $class ? $this->getSharedGridColumnConfigs($this->getAdminUser(), $class->getId(), $searchType) : [];
        $settings = $this->getShareSettings((int)$gridConfigId);
        $settings['gridConfigId'] = (int)$gridConfigId;
        $settings['gridConfigName'] = $gridConfigName;
        $settings['gridConfigDescription'] = $gridConfigDescription;
        $settings['shareGlobally'] = $sharedGlobally;
        $settings['isShared'] = (!$gridConfigId || $shared) ? true : false;

        return [
            'sortinfo' => isset($gridConfig['sortinfo']) ? $gridConfig['sortinfo'] : false,
            'language' => $language,
            'pageSize' => $pageSize,
            'availableFields' => $availableFields,
            'settings' => $settings,
            'onlyDirectChildren' => isset($gridConfig['onlyDirectChildren']) ? $gridConfig['onlyDirectChildren'] : false,
            'pageSize' => isset($gridConfig['pageSize']) ? $gridConfig['pageSize'] : false,
            'availableConfigs' => $availableConfigs,
            'sharedConfigs' => $sharedConfigs

        ];
    }

    /**
     * @param $noSystemColumns
     * @param $class DataObject\ClassDefinition
     * @param $gridType
     * @param $noBrickColumns
     * @param $fields
     * @param $context
     * @param $objectId
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
                if ($key == 'fullpath') {
                    $key = 'path';
                }

                if (empty($types) && ($vis[$gridType][$key] || $gridType == 'all')) {
                    $availableFields[] = [
                        'key' => $sc,
                        'type' => 'system',
                        'label' => $sc,
                        'position' => $count];
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
     * @param $field DataObject\ClassDefinition\Data
     * @param $brickFields
     * @param $availableFields
     * @param $gridType
     * @param $count
     * @param $brickType
     * @param $class
     * @param $objectId
     * @param null $context
     */
    protected function appendBrickFields($field, $brickFields, &$availableFields, $gridType, &$count, $brickType, $class, $objectId, $context = null)
    {
        if (!empty($brickFields)) {
            foreach ($brickFields as $bf) {
                if ($bf instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $localizedFieldDefinitions = $bf->getFieldDefinitions();

                    $context = [
                        'containerKey' => $brickType,
                        'fieldname' => $field->getName()
                    ];

                    $this->appendBrickFields($bf, $localizedFieldDefinitions, $availableFields, $gridType, $count, $brickType, $class, $objectId, $context);
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
     * @Route("/prepare-helper-column-configs")
     * @Method({"POST"})
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
        foreach ($data as $item) {
            if ($item->isOperator) {
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
     * @Route("/grid-config-apply-to-all")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridConfigApplyToAllAction(Request $request)
    {
        $objectId = $request->get('objectId');
        $object = DataObject\AbstractObject::getById($objectId);

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
        } else {
            return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
        }
    }

    /**
     * @Route("/grid-mark-favourite-column-config")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridMarkFavouriteColumnConfigAction(Request $request)
    {
        $objectId = $request->get('objectId');
        $object = DataObject\AbstractObject::getById($objectId);

        if ($object->isAllowed('list')) {
            $classId = $request->get('classId');
            $gridConfigId = $request->get('gridConfigId');
            $searchType = $request->get('searchType');
            $global = $request->get('global');
            $user = $this->getAdminUser();

            $favourite = new GridConfigFavourite();
            $favourite->setOwnerId($user->getId());
            $class = DataObject\ClassDefinition::getById($classId);
            if (!$class) {
                throw new \Exception('class ' . $classId . ' does not exist anymore');
            }
            $favourite->setClassId($classId);
            $favourite->setSearchType($searchType);

            try {
                if ($gridConfigId != 0) {
                    $gridConfig = GridConfig::getById($gridConfigId);
                    $favourite->setGridConfigId($gridConfig->getId());
                }
                $favourite->setObjectId($objectId);
                $favourite->save();

                $specializedConfigs = false;

                if ($global) {
                    $favourite->setObjectId(0);
                    $favourite->save();
                }
                $db = Db::get();
                $count = $db->fetchOne('select * from gridconfig_favourites where '
                    . 'ownerId = ' . $user->getId()
                    . ' and classId = ' . $db->quote($classId).
                    ' and searchType = ' . $db->quote($searchType)
                    . ' and objectId != ' . $objectId . ' and objectId != 0');
                $specializedConfigs = $count > 0;
            } catch (\Exception $e) {
                $favourite->delete();
            }

            return $this->adminJson(['success' => true, 'spezializedConfigs' => $specializedConfigs]);
        } else {
            return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
        }
    }

    /**
     * @param $gridConfigId
     *
     * @return array
     */
    protected function getShareSettings($gridConfigId)
    {
        $result = [
            'sharedUserIds' => [],
            'sharedRoleIds' => []
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
     * @Route("/import-save-config")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param ImportService $importService
     *
     * @return JsonResponse
     */
    public function importSaveConfigAction(Request $request, ImportService $importService)
    {
        try {
            $classId = $request->get('classId');
            $configData = $request->get('config');
            $configData = json_decode($configData, true);

            $configData['pimcore_version'] = Version::getVersion();
            $configData['pimcore_revision'] = Version::getRevision();

            $importConfigId = $request->get('importConfigId');
            if ($importConfigId) {
                try {
                    $importConfig = ImportConfig::getById($importConfigId);
                } catch (\Exception $e) {
                }
            }
            if ($importConfig && $importConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                throw new \Exception("don't mess around with somebody elses configuration");
            }

            if (!$importConfig) {
                $importConfig = new ImportConfig();
                $importConfig->setName(date('c'));
                $importConfig->setClassId($classId);
                $importConfig->setOwnerId($this->getAdminUser()->getId());
            }

            if ($configData) {
                unset($configData['importConfigId']);
                $name = $configData['shareSettings']['configName'];
                $description = $configData['shareSettings']['configDescription'];
                $importConfig->setName($name);
                $importConfig->setDescription($description);
                $importConfig->setShareGlobally($configData['shareSettings']['shareGlobally'] && $this->getAdminUser()->isAdmin());
            }

            $configDataEncoded = json_encode($configData);
            $importConfig->setConfig($configDataEncoded);
            $importConfig->save();

            $this->updateImportConfigShares($importConfig, $configData);

            return $this->adminJson(['success' => true,
                    'importConfigId' => $importConfig->getId(),
                    'availableConfigs' => $this->getImportConfigs($importService, $this->getAdminUser(), $classId)
                ]
            );
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/grid-save-column-config")
     * @Method({"POST"})
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

                $searchType = $request->get('searchType');

                // grid config
                $gridConfigData = $this->decodeJson($request->get('gridconfig'));
                $gridConfigData['pimcore_version'] = Version::getVersion();
                $gridConfigData['pimcore_revision'] = Version::getRevision();
                unset($gridConfigData['settings']['isShared']);

                $metadata = $request->get('settings');
                $metadata = json_decode($metadata, true);

                $gridConfigId = $metadata['gridConfigId'];
                if ($gridConfigId) {
                    try {
                        $gridConfig = GridConfig::getById($gridConfigId);
                    } catch (\Exception $e) {
                    }
                }
                if ($gridConfig && $gridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
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

        return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @param $importConfig ImportConfig
     * @param $metadata
     *
     * @throws \Exception
     */
    protected function updateImportConfigShares($importConfig, $configData)
    {
        $user = $this->getAdminUser();
        if (!$importConfig || !$user->isAllowed('share_configurations')) {
            // nothing to do
            return;
        }

        if ($importConfig->getOwnerId() != $this->getAdminUser()->getId()) {
            throw new \Exception("don't mess with someone elses grid config");
        }
        $combinedShares = [];
        $sharedUserIds = $configData['shareSettings'] ? $configData['shareSettings']['sharedUserIds'] : [];
        $sharedRoleIds = $configData['shareSettings'] ? $configData['shareSettings']['sharedRoleIds'] : [];

        if ($sharedUserIds) {
            $combinedShares = explode(',', $sharedUserIds);
        }

        if ($sharedRoleIds) {
            $sharedRoleIds = explode(',', $sharedRoleIds);
            $combinedShares = array_merge($combinedShares, $sharedRoleIds);
        }

        $db = Db::get();
        $db->delete('importconfig_shares', ['importConfigId' => $importConfig->getId()]);

        foreach ($combinedShares as $id) {
            $share = new ImportConfigShare();
            $share->setImportConfigId($importConfig->getId());
            $share->setSharedWithUserId($id);
            $share->save();
        }
    }

    /**
     * @param $gridConfig GridConfig
     * @param $metadata
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
     * @param $field
     * @param $gridType
     * @param $position
     * @param bool $force
     * @param null $keyPrefix
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

        if ($field->getFieldType() == 'slider') {
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
                $object = DataObject\AbstractObject::getById($objectId);
                $context['object'] = $object;
            }
            DataObject\Service::enrichLayoutDefinition($field, null, $context);

            $result = [
                'key' => $key,
                'type' => $field->getFieldType(),
                'label' => $title,
                'config' => $config,
                'layout' => $field,
                'position' => $position
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
     * @Route("/prepare-import-preview")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function prepareImportPreviewAction(Request $request)
    {
        $data = $request->get('data');
        $data = json_decode($data, false);
        $importId = $data->importId;

        try {
            Tool\Session::useSession(function (AttributeBagInterface $session) use ($importId, $data) {
                $session->set('importconfig_' . $importId, $data);
            }, 'pimcore_gridconfig');
        } catch (\Exception $e) {
            Logger::error($e);
        }

        $response = $this->adminJson([
            'success' => true
        ]);

        return $response;
    }

    /**
     * @Route("/import-preview")
     * @Method({"GET"})
     *
     * @param Request $request
     * @param ImportService $importService
     * @param LocaleServiceInterface $localeService
     * @param FactoryInterface $modelFactory
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return Response
     */
    public function importPreviewAction(
        Request $request,
        ImportService $importService,
        LocaleServiceInterface $localeService,
        FactoryInterface $modelFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        try {
            $importId = $request->get('importId');

            $configData = $request->get('config');
            $configData = json_decode($configData, false);

            $data = Tool\Session::useSession(function (AttributeBagInterface $session) use ($importId, $configData) {
                return $session->get('importconfig_' . $importId, $configData);
            }, 'pimcore_gridconfig');

            $configData = $data->config;
            $additionalData = json_decode($data->additionalData, true);
            $rowIndex = $data->rowIndex;

            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_' . $request->get('importId');
            $originalFile = $file . '_original';

            // determine type
            $dialect = Tool\Admin::determineCsvDialect($originalFile);

            $count = 0;
            $haveData = false;

            $rowData = [];
            if (($handle = fopen($originalFile, 'r')) !== false) {
                while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                    if ($count == $rowIndex) {
                        $haveData = true;
                        break;
                    }
                    $count++;
                }
                fclose($handle);
            }

            if (!$haveData) {
                throw new \Exception("don't have data");
            }

            $paramsBag = [];

            $resolver = $importService->getResolver($configData->resolverSettings->strategy);

            $classId = $data->classId;
            $class = DataObject\ClassDefinition::getById($classId);

            $object1 = $resolver->resolve($configData, $data->parentId, $rowData);

            if ($object1 == null) {
                $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($class->getName());
                $object1 = $modelFactory->build($className);
                $paramsBag['isNew'] = true;
            }

            $deepCopy = new \DeepCopy\DeepCopy();
            $object2 = $deepCopy->copy($object1);

            $context = [];
            $eventData = new DataObjectImportEvent($configData, $originalFile);
            $eventData->setAdditionalData($additionalData);
            $eventData->setContext($context);

            $eventDispatcher->dispatch(DataObjectImportEvents::PREVIEW, $eventData);

            $context = $eventData->getContext();

            $object2 = $this->populateObject($importService, $localeService, $object2, $configData, $rowData, $context);

            $paramsBag['object1'] = $object1;
            $paramsBag['object2'] = $object2;
            $paramsBag['isImportPreview'] = true;

            $response = $this->render('PimcoreAdminBundle:Admin/DataObject:diffVersions.html.php', $paramsBag);

            return $response;
        } catch (\Exception $e) {
            $response = new Response($e);

            return $response;
        }
    }

    protected function populateObject(
        ImportService $importService,
        LocaleServiceInterface $localeService,
        $object,
        $configData,
        $rowData,
        $context
    ) {
        $selectedGridColumns = $configData->selectedGridColumns;

        $colIndex = -1;

        $locale = null;
        if ($configData->resolverSettings) {
            if ($configData->resolverSettings && $configData->resolverSettings->language != 'default') {
                $locale = $configData->resolverSettings->language;
            }
        }

        foreach ($selectedGridColumns as $selectedGridColumn) {
            $colIndex++;

            $attributes = $selectedGridColumn->attributes;

            /** @var $config ConfigElementInterface */
            $config = $importService->buildInputDataConfig([$attributes]);
            if (!$config) {
                continue;
            }

            $config = $config[0];
            $target = $object;

            if ($locale) {
                $localeService->setLocale($locale);
            }

            $config->process($object, $target, $rowData, $colIndex, $context);
        }

        return $object;
    }

    /**
     * IMPORTER
     */

    /**
     * @Route("/import-upload")
     * @Method({"POST"})
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
            'success' => true
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/import-get-file-info")
     * @Method({"GET"})
     *
     * @param Request $request
     * @param ImportService $importService
     *
     * @return JsonResponse
     */
    public function importGetFileInfoAction(Request $request, ImportService $importService)
    {
        $importConfigId = $request->get('importConfigId');
        $success = true;
        $supportedFieldTypes = ['checkbox', 'country', 'date', 'datetime', 'href', 'image', 'input', 'language', 'table', 'multiselect', 'numeric', 'password', 'select', 'slider', 'textarea', 'wysiwyg', 'objects', 'multihref', 'geopoint', 'geopolygon', 'geobounds', 'link', 'user', 'email', 'gender', 'firstname', 'lastname', 'newsletterActive', 'newsletterConfirmed', 'countrymultiselect', 'objectsMetadata'];

        $classId = $request->get('classId');
        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_' . $request->get('importId');

        $originalFile = $file . '_original';
        // determine type
        $dialect = Tool\Admin::determineCsvDialect($file . '_original');

        $count = 0;
        if (($handle = fopen($originalFile, 'r')) !== false) {
            while (($rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                $tmpData = [];

                foreach ($rowData as $key => $value) {
                    $tmpData['field_' . $key] = $value;
                }

                $tmpData['rowId'] = $count + 1;
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
        $class = DataObject\ClassDefinition::getById($request->get('classId'));
        $fields = $class->getFieldDefinitions();

        $availableFields = [];

        foreach ($fields as $key => $field) {
            $config = null;
            $title = $field->getName();
            if (method_exists($field, 'getTitle')) {
                if ($field->getTitle()) {
                    $title = $field->getTitle();
                }
            }

            if (in_array($field->getFieldType(), $supportedFieldTypes)) {
                $availableFields[] = [$field->getName(), $title . '(' . $field->getFieldType() . ')'];
            }
        }

        $csv = new \SplFileObject($originalFile);
        $csv->setFlags(\SplFileObject::READ_CSV);
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

        try {
            $importConfig = ImportConfig::getById($importConfigId);
        } catch (\Exception $e) {
        }

        $dialect->lineterminator = bin2hex($dialect->lineterminator);

        $selectedGridColumns = [];
        if ($importConfig) {
            $configData = $importConfig->getConfig();
            $configData = json_decode($configData, true);
            $selectedGridColumns = $configData['selectedGridColumns'];
            $resolverSettings = $configData['resolverSettings'];
            $shareSettings = $configData['shareSettings'];
            $dialect = json_decode(json_encode($configData['csvSettings']), FALSE);

        }

        $availableConfigs = $this->getImportConfigs($importService, $this->getAdminUser(), $classId);

        return $this->adminJson([
            'success' => $success,
            'config' => [
                'importConfigId' => $importConfigId,
                'dataPreview' => $data,
                'dataFields' => array_keys($data[0]),
                'targetFields' => $availableFields,
                'selectedGridColumns' => $selectedGridColumns,
                'resolverSettings' => $resolverSettings,
                'shareSettings' => $shareSettings,
                'csvSettings' => $dialect,
                'rows' => $rows,
                'cols' => $cols,
                'classId' => $classId,
                'isShared' => $importConfig && $importConfig->getOwnerId() != $this->getAdminUser()->getId()
            ],
            'availableConfigs' => $availableConfigs
        ]);
    }

    /**
     * @Route("/import-process")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param ImportService $importService
     * @param LocaleServiceInterface $localeService
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function importProcessAction(
        Request $request,
        ImportService $importService,
        LocaleServiceInterface $localeService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $parentId = $request->get('parentId');
        $additionalData = json_decode($request->get('additionalData'), true);
        $job = $request->get('job');
        $importId = $request->get('importId');
        $importJobTotal = $request->get('importJobTotal');

        $configData = $request->get('config');
        $configData = json_decode($configData, false);

        $skipFirstRow = $configData->resolverSettings->skipHeadRow;

        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/import_' . $importId;
        $originalFile = $file . '_original';

        $context = [];
        $eventData = new DataObjectImportEvent($configData, $originalFile);
        $eventData->setAdditionalData($additionalData);
        $eventData->setContext($context);

        if ($job == 1) {
            \Pimcore::getEventDispatcher()->dispatch(DataObjectImportEvents::BEFORE_START, $eventData);

            if (!copy($originalFile, $file)) {
                throw new \Exception('failed to copy file');
            }
        }

        // currently only csv supported
        $dialect = $configData->csvSettings;

        $rowData = [];
        if (($handle = fopen($file, 'r')) !== false) {
            $rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
        }

        if ($skipFirstRow && $job == 1) {
            //read the next row, we need to skip the head row
            $rowData = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
        }

        $tmpFile = $file . '_tmp';
        $tmpHandle = fopen($tmpFile, 'w+');
        while (!feof($handle)) {
            $buffer = fgets($handle);
            fwrite($tmpHandle, $buffer);
        }

        fclose($handle);
        fclose($tmpHandle);

        unlink($file);
        rename($tmpFile, $file);

        $rowId = $skipFirstRow ? $job + 1 : $job;

        try {
            $configData->classId = $request->get('classId');
            $resolver = $importService->getResolver($configData->resolverSettings->strategy);

            /** @var $object DataObject\Concrete */
            $object = $resolver->resolve($configData, $parentId, $rowData);

            $context = $eventData->getContext();

            $object = $this->populateObject($importService, $localeService, $object, $configData, $rowData, $context);

            $eventData->setObject($object);
            $eventData->setRowData($rowData);

            $eventDispatcher->dispatch(DataObjectImportEvents::PRE_SAVE, $eventData);

            $object->setUserModification($this->getUser());
            $object->save();

            $eventDispatcher->dispatch(DataObjectImportEvents::POST_SAVE, $eventData);

            if ($job >= $importJobTotal) {
                $eventDispatcher->dispatch(DataObjectImportEvents::DONE, $eventData);
            }

            return $this->adminJson(['success' => true, 'rowId' => $rowId, 'message' => $object->getFullPath(), 'objectId' => $object->getId()]);
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false, 'rowId' => $rowId, 'message' => $e->getMessage()]);
        }
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
     * @param Request $request
     *
     * @return array
     */
    protected function extractFieldsAndBricks(Request $request)
    {
        $fields = [];
        $newFields = [];

        $bricks = [];
        if ($request->get('fields')) {
            $fields = $request->get('fields');

            foreach ($fields as $f) {
                $fieldName = $f;
                $parts = explode('~', $f);
                if (substr($f, 0, 1) == '~') {
                    // key value, ignore for now
                } elseif (count($parts) > 1) {
                    $brickType = $parts[0];

                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $brickType = $brickDescriptor['containerKey'];
//                        $fieldName =  $brickDescriptor["brickfield"];
                    }

                    $bricks[$brickType] = $brickType;
                }
                $newFields[] = $fieldName;
            }
        }

        return [$fields, $bricks];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function prepareExportList(Request $request)
    {
        $requestedLanguage = $this->extractLanguage($request);

        $folder = DataObject\AbstractObject::getById($request->get('folderId'));
        $class = DataObject\ClassDefinition::getById($request->get('classId'));

        $className = $class->getName();

        $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';

        if (!empty($folder)) {
            $conditionFilters = ["o_path LIKE '" . $folder->getRealFullPath() . "%'"];
        } else {
            $conditionFilters = [];
        }

        $featureJoins = [];

        if ($request->get('filter')) {
            $conditionFilters[] = DataObject\Service::getFilterCondition($request->get('filter'), $class);
            $featureFilters = DataObject\Service::getFeatureFilters($request->get('filter'), $class);
            if ($featureFilters) {
                $featureJoins = array_merge($featureJoins, $featureFilters['joins']);
            }
        }
        if ($request->get('condition')) {
            $conditionFilters[] = '(' . $request->get('condition') . ')';
        }

        /** @var DataObject\Listing\Concrete $list */
        $list = new $listClass();
        $objectTableName = $list->getDao()->getTableName();
        $list->setCondition(implode(' AND ', $conditionFilters));

        //parameters specified in the objects grid
        $ids = $request->get('ids', []);
        $quotedIds = [];
        foreach ($ids as $id) {
            $quotedIds[] = $list->quote($id);
        }
        if (!empty($quotedIds)) {
            //add a condition if id numbers are specified
            $list->addConditionParam("{$objectTableName}.o_id IN (" . implode(',', $quotedIds) . ')');
        }

        $list->setOrder('ASC');
        $list->setOrderKey('o_id');

        $objectType = $request->get('objecttype');
        if ($objectType) {
            if ($objectType == DataObject\AbstractObject::OBJECT_TYPE_OBJECT && $class->getShowVariants()) {
                $list->setObjectTypes([DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);
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
        DataObject\Service::addGridFeatureJoins($list, $featureJoins, $class, $featureFilters, $requestedLanguage);

        return [$list, $fields, $requestedLanguage];
    }

    /**
     * @param $fileHandle
     *
     * @return string
     */
    protected function getCsvFile($fileHandle)
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $fileHandle . '.csv';
    }

    /**
     * @Route("/get-export-jobs")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getExportJobsAction(Request $request)
    {
        list($list, $fields, $requestedLanguage) = $this->prepareExportList($request);

        $ids = $list->loadIdList();

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('export-');
        file_put_contents($this->getCsvFile($fileHandle), '');

        return $this->adminJson(['success' => true, 'jobs' => $jobs, 'fileHandle' => $fileHandle]);
    }

    /**
     * @Route("/do-export")
     * @Method({"POST"})
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

        $enableInheritance = $settings['enableInheritance'];
        DataObject\Concrete::setGetInheritedValues($enableInheritance);

        $class = DataObject\ClassDefinition::getById($request->get('classId'));
        $className = $class->getName();
        $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';

        /**
         * @var $list \Pimcore\Model\DataObject\Listing
         */
        $list = new $listClass();

        $quotedIds = [];
        foreach ($ids as $id) {
            $quotedIds[] = $list->quote($id);
        }

        $list->setObjectTypes(['object', 'folder', 'variant']);
        $list->setCondition('o_id IN (' . implode(',', $quotedIds) . ')');
        $list->setOrderKey(' FIELD(o_id, ' . implode(',', $quotedIds) . ')', false);

        list($fields, $bricks) = $this->extractFieldsAndBricks($request);

        $addTitles = $request->get('initial');

        $csv = $this->getCsvData($request, $localeService, $list, $fields, $addTitles);

        $fp = fopen($this->getCsvFile($fileHandle), 'a');

        $firstLine = true;
        foreach ($csv as $line) {
            if ($addTitles && $firstLine) {
                $firstLine = false;
                $line = implode($delimiter, $line) . "\r\n";
                fwrite($fp, $line);
            } else {
                fputs($fp, implode($delimiter, array_map([$this, 'encodeFunc'], $line)) . "\r\n");
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
     * @Route("/download-csv-file")
     * @Method({"GET"})
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
    }

    /**
     * @param $field
     *
     * @return string
     */
    protected function mapFieldname($field, $helperDefinitions)
    {
        if (strpos($field, '#') === 0) {
            if (isset($helperDefinitions[$field])) {
                if ($helperDefinitions[$field]->attributes) {
                    return $helperDefinitions[$field]->attributes->label ? $helperDefinitions[$field]->attributes->label : $field;
                }

                return $field;
            }
        } elseif (substr($field, 0, 1) == '~') {
            $fieldParts = explode('~', $field);
            $type = $fieldParts[1];

            if ($type == 'classificationstore') {
                $fieldname = $fieldParts[2];
                $groupKeyId = explode('-', $fieldParts[3]);
                $groupId = $groupKeyId[0];
                $keyId = $groupKeyId[1];

                $groupConfig = DataObject\Classificationstore\GroupConfig::getById($groupId);
                $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);

                $field = $fieldname . '~' . $groupConfig->getName() . '~' . $keyConfig->getName();
            }
        }

        return $field;
    }

    /**
     * @param Request $request
     * @param LocaleServiceInterface $localeService
     * @param $list
     * @param $fields
     * @param bool $addTitles
     *
     * @return string
     */
    protected function getCsvData(Request $request, LocaleServiceInterface $localeService, $list, $fields, $addTitles = true)
    {
        $requestedLanguage = $this->extractLanguage($request);
        $mappedFieldnames = [];

        $objects = [];
        Logger::debug('objects in list:' . count($list->getObjects()));

        $helperDefinitions = DataObject\Service::getHelperDefinitions();

        foreach ($list->getObjects() as $object) {
            if ($fields) {
                $objectData = [];
                foreach ($fields as $field) {
                    if (DataObject\Service::isHelperGridColumnConfig($field) && $validLanguages = DataObject\Service::expandGridColumnForExport($helperDefinitions, $field)) {
                        $currentLocale = $localeService->getLocale();
                        $mappedFieldnameBase = $this->mapFieldname($field, $helperDefinitions);

                        foreach ($validLanguages as $validLanguage) {
                            $localeService->setLocale($validLanguage);
                            $fieldData = $this->getCsvFieldData($request, $field, $object, $validLanguage, $helperDefinitions);
                            $localizedFieldKey = $field . '-' . $validLanguage;
                            if (!isset($mappedFieldnames[$localizedFieldKey])) {
                                $mappedFieldnames[$localizedFieldKey] = $mappedFieldnameBase . '-' . $validLanguage;
                            }
                            $objectData[$localizedFieldKey] = $fieldData;
                        }

                        $localeService->setLocale($currentLocale);
                    } else {
                        $fieldData = $this->getCsvFieldData($request, $field, $object, $requestedLanguage, $helperDefinitions);
                        if (!isset($mappedFieldnames[$field])) {
                            $mappedFieldnames[$field] = $this->mapFieldname($field, $helperDefinitions);
                        }
                        $objectData[$field] = $fieldData;
                    }
                }
                $objects[] = $objectData;
            }
        }
        //create csv
        $csv = [];
        if (!empty($objects)) {
            if ($addTitles) {
                $columns = array_keys($objects[0]);
                foreach ($columns as $columnIdx => $columnKey) {
                    $columnName = $mappedFieldnames[$columnKey];
                    $columns[$columnIdx] = '"' . $columnName . '"';
                }
                $csv[] = $columns;
            }
            foreach ($objects as $o) {
                $csv[] = $o;
            }
        }

        return $csv;
    }

    /**
     * @param Request $request
     * @param $field
     * @param $object
     * @param $requestedLanguage
     *
     * @return mixed
     */
    protected function getCsvFieldData(Request $request, $field, $object, $requestedLanguage, $helperDefinitions)
    {
        //check if field is systemfield
        $systemFieldMap = [
            'id' => 'getId',
            'fullpath' => 'getRealFullPath',
            'published' => 'getPublished',
            'creationDate' => 'getCreationDate',
            'modificationDate' => 'getModificationDate',
            'filename' => 'getKey',
            'classname' => 'getClassname'
        ];
        if (in_array($field, array_keys($systemFieldMap))) {
            return $object->{$systemFieldMap[$field]}();
        } else {
            //check if field is standard object field
            $fieldDefinition = $object->getClass()->getFieldDefinition($field);
            if ($fieldDefinition) {
                return $fieldDefinition->getForCsvExport($object);
            } else {
                $fieldParts = explode('~', $field);

                // check for objects bricks and localized fields
                if (DataObject\Service::isHelperGridColumnConfig($field)) {
                    if ($helperDefinitions[$field]) {
                        return DataObject\Service::calculateCellValue($object, $helperDefinitions, $field);
                    }
                } elseif (substr($field, 0, 1) == '~') {
                    $type = $fieldParts[1];

                    if ($type == 'classificationstore') {
                        $fieldname = $fieldParts[2];
                        $groupKeyId = explode('-', $fieldParts[3]);
                        $groupId = $groupKeyId[0];
                        $keyId = $groupKeyId[1];
                        $getter = 'get' . ucfirst($fieldname);
                        if (method_exists($object, $getter)) {
                            $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);
                            $type = $keyConfig->getType();
                            $definition = json_decode($keyConfig->getDefinition());
                            $fieldDefinition = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                            /** @var $csFieldDefinition DataObject\ClassDefinition\Data\Classificationstore */
                            $csFieldDefinition = $object->getClass()->getFieldDefinition($fieldname);
                            $csLanguage = $requestedLanguage;
                            if (!$csFieldDefinition->isLocalized()) {
                                $csLanguage = 'default';
                            }

                            return $fieldDefinition->getForCsvExport(
                                $object,
                                ['context' => [
                                    'containerType' => 'classificationstore',
                                    'fieldname' => $fieldname,
                                    'groupId' => $groupId,
                                    'keyId' => $keyId,
                                    'language' => $csLanguage
                                ]]
                            );
                        }
                    }
                    //key value store - ignore for now
                } elseif (count($fieldParts) > 1) {
                    // brick
                    $brickType = $fieldParts[0];

                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $innerContainer = $brickDescriptor['innerContainer'] ? $brickDescriptor['innerContainer'] : 'localizedfields';
                        $brickType = $brickDescriptor['containerKey'];
                    }
                    $brickKey = $fieldParts[1];

                    $key = DataObject\Service::getFieldForBrickType($object->getClass(), $brickType);

                    $brickClass = DataObject\Objectbrick\Definition::getByKey($brickType);

                    if ($brickDescriptor) {
                        $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                        $fieldDefinition = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                    } else {
                        $fieldDefinition = $brickClass->getFieldDefinition($brickKey);
                    }

                    if ($fieldDefinition) {
                        $brickContainer = $object->{'get' . ucfirst($key)}();
                        if ($brickContainer && !empty($brickKey)) {
                            $brick = $brickContainer->{'get' . ucfirst($brickType)}();
                            if ($brick) {
                                $params = [
                                    'context' => [
                                        'containerType' => 'objectbrick',
                                        'containerKey' => $brickType,
                                        'fieldname' => $brickKey
                                    ]

                                ];

                                $value = $brick;

                                if ($brickDescriptor) {
                                    $innerContainer = $brickDescriptor['innerContainer'] ? $brickDescriptor['innerContainer'] : 'localizedfields';
                                    $value = $brick->{'get' . ucfirst($innerContainer)}();
                                }

                                return $fieldDefinition->getForCsvExport($value, $params);
                            }
                        }
                    }
                } elseif ($locFields = $object->getClass()->getFieldDefinition('localizedfields')) {

                    // if the definition is not set try to get the definition from localized fields
                    $fieldDefinition = $locFields->getFieldDefinition($field);
                    if ($fieldDefinition) {
                        $needLocalizedPermissions = true;

                        return $fieldDefinition->getForCsvExport($object->getLocalizedFields(), ['language' => $request->get('language')]);
                    }
                }
            }
        }
    }

    /**
     * Flattens object data to an array with key=>value where
     * value is simply a string representation of the value (for objects, hrefs and assets the full path is used)
     *
     * @param DataObject\AbstractObject $object
     *
     * @return array
     */
    protected function csvObjectData($object)
    {
        $o = [];
        foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
            //exclude remote owner fields
            if (!($value instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations and $value->isRemoteOwner())) {
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
     * @Route("/get-batch-jobs")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getBatchJobsAction(Request $request)
    {
        if ($request->get('language')) {
            $request->setLocale($request->get('language'));
        }

        $folder = DataObject::getById($request->get('folderId'));
        $class = DataObject\ClassDefinition::getById($request->get('classId'));

        $conditionFilters = ["(o_path = ? OR o_path LIKE '" . str_replace('//', '/', $folder->getRealFullPath() . '/') . "%')"];

        if ($request->get('filter')) {
            $conditionFilters[] = DataObject\Service::getFilterCondition($request->get('filter'), $class);
        }
        if ($request->get('condition')) {
            $conditionFilters[] = ' (' . $request->get('condition') . ')';
        }

        $className = $class->getName();
        $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';
        $list = new $listClass();
        $list->setCondition(implode(' AND ', $conditionFilters), [$folder->getRealFullPath()]);
        $list->setOrder('ASC');
        $list->setOrderKey('o_id');

        if ($request->get('objecttype')) {
            $objectTypes = [$request->get('objecttype')];

            if ($class->getShowVariants()) {
                $objectTypes[] = DataObject\AbstractObject::OBJECT_TYPE_VARIANT;
            }

            $list->setObjectTypes($objectTypes);
        }

        $jobs = $list->loadIdList();

        return $this->adminJson(['success' => true, 'jobs' => $jobs]);
    }

    /**
     * @Route("/batch")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function batchAction(Request $request)
    {
        $success = true;

        try {
            $object = DataObject::getById($request->get('job'));

            if ($object) {
                if (!$object->isAllowed('publish')) {
                    throw new \Exception("Permission denied. You don't have the rights to save this object.");
                }

                $append = $request->get('append');

                $className = $object->getClassName();
                $class = DataObject\ClassDefinition::getByName($className);
                $value = $request->get('value');
                if ($request->get('valueType') == 'object') {
                    $value = $this->decodeJson($value);
                }

                $name = $request->get('name');
                $parts = explode('~', $name);

                if (substr($name, 0, 1) == '~') {
                    $type = $parts[1];
                    $field = $parts[2];
                    $keyid = $parts[3];

                    if ($type == 'classificationstore') {
                        $requestedLanguage = $request->get('language');
                        if ($requestedLanguage) {
                            if ($requestedLanguage != 'default') {
                                //                $this->get('translator')->setLocale($requestedLanguage);
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

                            /** @var $csFieldDefinition DataObject\ClassDefinition\Data\Classificationstore */
                            $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                            $csLanguage = $requestedLanguage;
                            if (!$csFieldDefinition->isLocalized()) {
                                $csLanguage = 'default';
                            }

                            /** @var $fd DataObject\ClassDefinition\Data\Classificationstore */
                            $fd = $class->getFieldDefinition($field);
                            $keyConfig = $fd->getKeyConfiguration($keyid);
                            $dataDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                            /** @var $classificationStoreData DataObject\Classificationstore */
                            $classificationStoreData = $object->$getter();
                            $classificationStoreData->setLocalizedKeyValue(
                                $groupId,
                                $keyid,
                                $dataDefinition->getDataFromEditmode($value),
                                $csLanguage
                            );
                        }
                    } else {
                        $getter = 'get' . ucfirst($field);
                        $setter = 'set' . ucfirst($field);
                        $keyValuePairs = $object->$getter();

                        if (!$keyValuePairs) {
                            $keyValuePairs = new DataObject\Data\KeyValue();
                            $keyValuePairs->setObjectId($object->getId());
                            $keyValuePairs->setClass($object->getClass());
                        }

                        $keyValuePairs->setPropertyWithId($keyid, $value, true);

                        $object->$setter($keyValuePairs);
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
                        $object->setValue($name, $newData);
                    } else {
                        // check if it is a localized field
                        if ($request->get('language')) {
                            $localizedField = $class->getFieldDefinition('localizedfields');
                            if ($localizedField) {
                                $field = $localizedField->getFieldDefinition($name);
                                if ($field) {
                                    $getter = 'get' . $name;
                                    $setter = 'set' . $name;
                                    /** @var $field DataObject\ClassDefinition\Data */
                                    $newData = $field->getDataFromEditmode($value, $object);
                                    if ($append) {
                                        $existingData = $object->$getter($request->get('language'));
                                        $newData = $field->appendData($existingData, $newData);
                                    }

                                    $object->$setter($newData, $request->get('language'));
                                }
                            }
                        }

                        // seems to be a system field, this is actually only possible for the "published" field yet
                        if ($name == 'published') {
                            if ($value == 'false' || empty($value)) {
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
        } catch (\Exception $e) {
            Logger::err($e);

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }

        return $this->adminJson(['success' => $success]);
    }
}
