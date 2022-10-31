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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject;

use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Event\AdminEvents;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
trait DataObjectActionsTrait
{
    /**
     * @param DataObject|null $object
     * @param string $key
     *
     * @return array
     */
    protected function renameObject(?DataObject $object, string $key): array
    {
        try {
            if (!$object instanceof DataObject) {
                throw new \Exception('No Object found for given id.');
            }

            $object->setKey($key);
            $object->save();

            return ['success' => true];
        } catch (\Exception $e) {
            Logger::error((string) $e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array $allParams
     * @param string $objectType
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     * @param GridHelperService $gridHelperService
     * @param LocaleServiceInterface $localeService
     *
     * @return array
     */
    protected function gridProxy(
        array $allParams,
        string $objectType,
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        GridHelperService $gridHelperService,
        LocaleServiceInterface $localeService
    ): array {
        $action = $allParams['xaction'] ?? 'list';
        $csvMode = $allParams['csvMode'] ?? false;

        $requestedLanguage = $allParams['language'] ?? null;
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        if ($action === 'update') {
            try {
                $data = $this->decodeJson($allParams['data']);
                $object = DataObject::getById($data['id']);

                if (!$object instanceof DataObject\Concrete) {
                    throw $this->createNotFoundException('Object not found');
                }

                if (!$object->isAllowed('publish')) {
                    throw $this->createAccessDeniedException("Permission denied. You don't have the rights to save this object.");
                }

                $objectData = $this->prepareObjectData($data, $object, $requestedLanguage, $localeService);
                $object->setValues($objectData);

                if ($object->getPublished() == false) {
                    $object->setOmitMandatoryCheck(true);
                }
                $object->save();

                return [
                    'success' => true,
                    'data' => DataObject\Service::gridObjectData($object, $allParams['fields'], $requestedLanguage),
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        } else { // get list of objects/variants
            $list = $gridHelperService->prepareListingForGrid($allParams, $requestedLanguage, $this->getAdminUser());

            if ($objectType === DataObject::OBJECT_TYPE_OBJECT) {
                $beforeListLoadEvent = new GenericEvent($this, [
                    'list' => $list,
                    'context' => $allParams,
                ]);
                $eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD);
                /** @var DataObject\Listing\Concrete $list */
                $list = $beforeListLoadEvent->getArgument('list');
            }

            if ($objectType === DataObject::OBJECT_TYPE_VARIANT) {
                $list->setObjectTypes([DataObject::OBJECT_TYPE_VARIANT]);
            }

            $list->load();

            $objects = [];
            foreach ($list->getObjects() as $object) {
                if ($csvMode) {
                    $o = DataObject\Service::getCsvDataForObject($object, $requestedLanguage, $request->get('fields'), DataObject\Service::getHelperDefinitions(), $localeService, false, $allParams['context']);
                } else {
                    $o = DataObject\Service::gridObjectData($object, $allParams['fields'] ?? null, $requestedLanguage,
                        ['csvMode' => $csvMode]);
                }

                // Like for treeGetChildsByIdAction, so we respect isAllowed method which can be extended (object DI) for custom permissions, so relying only users_workspaces_object is insufficient and could lead security breach
                if ($object->isAllowed('list')) {
                    $objects[] = $o;
                }
            }

            $result = [
                'success' => true,
                'data' => $objects,
                'total' => $list->getTotalCount(),
            ];

            if ($objectType === DataObject::OBJECT_TYPE_OBJECT) {
                $afterListLoadEvent = new GenericEvent($this, [
                    'list' => $result,
                    'context' => $allParams,
                ]);
                $eventDispatcher->dispatch($afterListLoadEvent, AdminEvents::OBJECT_LIST_AFTER_LIST_LOAD);
                $result = $afterListLoadEvent->getArgument('list');
            }

            return $result;
        }
    }

    /**
     * @throws \Exception
     */
    private function prepareObjectData(
        array $data,
        DataObject\Concrete $object,
        string $requestedLanguage,
        LocaleServiceInterface $localeService
    ): array {
        $user = Tool\Admin::getCurrentUser();
        $allLanguagesAllowed = false;
        $languagePermissions = [];
        if (!$user->isAdmin()) {
            $languagePermissions = $object->getPermissions('lEdit', $user);

            //sets allowed all languages modification when the lEdit column is empty
            $allLanguagesAllowed = $languagePermissions['lEdit'] == '';
            $languagePermissions = explode(',', $languagePermissions['lEdit']);
        }

        $class = $object->getClass();
        $objectData = [];
        foreach ($data as $key => $value) {
            $parts = explode('~', $key);
            if (substr($key, 0, 1) == '~') {
                list(, $type, $field, $keyId) = $parts;

                if ($type == 'classificationstore') {
                    $groupKeyId = array_map('intval', explode('-', $keyId));
                    list($groupId, $keyId) = $groupKeyId;

                    $getter = 'get' . ucfirst($field);
                    if (method_exists($object, $getter)) {
                        /** @var DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                        $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                        $csLanguage = $requestedLanguage;
                        if (!$csFieldDefinition->isLocalized()) {
                            $csLanguage = 'default';
                        }

                        /** @var DataObject\Classificationstore $classificationStoreData */
                        $classificationStoreData = $object->$getter();

                        $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);
                        if ($keyConfig) {
                            $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(
                                json_decode($keyConfig->getDefinition()),
                                $keyConfig->getType()
                            );
                            if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                                $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                            }
                        }

                        $activeGroups = $classificationStoreData->getActiveGroups() ?: [];
                        $activeGroups[$groupId] = true;
                        $classificationStoreData->setActiveGroups($activeGroups);
                        $classificationStoreData->setLocalizedKeyValue($groupId, $keyId, $value, $csLanguage);
                    }
                }
            } elseif (count($parts) > 1) {
                $brickType = $parts[0];
                $brickDescriptor = null;

                if (strpos($brickType, '?') !== false) {
                    $brickDescriptor = substr($brickType, 1);
                    $brickDescriptor = json_decode($brickDescriptor, true);
                    $brickType = $brickDescriptor['containerKey'];
                }
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

                if ($brickDescriptor) {
                    $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);
                    /** @var DataObject\ClassDefinition\Data\Localizedfields $fieldDefinitionLocalizedFields */
                    $fieldDefinitionLocalizedFields = $brickDefinition->getFieldDefinition('localizedfields');
                    $fieldDefinition = $fieldDefinitionLocalizedFields->getFieldDefinition($brickKey);
                } else {
                    $fieldDefinition = $this->getFieldDefinitionFromBrick($brickType, $brickKey);
                }

                if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                    $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                }

                if ($brickDescriptor) {
                    /** @var DataObject\Localizedfield $localizedFields */
                    $localizedFields = $brick->getLocalizedfields();
                    $localizedFields->setLocalizedValue($brickKey, $value);
                } else {
                    $brick->$valueSetter($value);
                }
            } else {
                if (!$user->isAdmin() && $languagePermissions) {
                    $fd = $class->getFieldDefinition($key);
                    if (!$fd) {
                        // try to get via localized fields
                        $localized = $class->getFieldDefinition('localizedfields');
                        if ($localized instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                            $field = $localized->getFieldDefinition($key);
                            if ($field) {
                                $currentLocale = $localeService->findLocale();
                                if (!$allLanguagesAllowed && !in_array($currentLocale, $languagePermissions)) {
                                    continue;
                                }
                            }
                        }
                    }
                }

                $fieldDefinition = $this->getFieldDefinition($class, $key);
                if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                    $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                }

                $objectData[$key] = $value;
            }
        }

        return $objectData;
    }

    /**
     * @param DataObject\ClassDefinition $class
     * @param string $key
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    protected function getFieldDefinition(DataObject\ClassDefinition $class, string $key): ?DataObject\ClassDefinition\Data
    {
        $fieldDefinition = $class->getFieldDefinition($key);
        if ($fieldDefinition) {
            return $fieldDefinition;
        }

        $localized = $class->getFieldDefinition('localizedfields');
        if ($localized instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            $fieldDefinition = $localized->getFieldDefinition($key);
        }

        return $fieldDefinition;
    }

    /**
     * @param string $brickType
     * @param string $key
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    protected function getFieldDefinitionFromBrick(string $brickType, string $key): ?DataObject\ClassDefinition\Data
    {
        $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);
        $fieldDefinition = null;
        if ($brickDefinition) {
            $fieldDefinition = $brickDefinition->getFieldDefinition($key);
        }

        return $fieldDefinition;
    }
}
