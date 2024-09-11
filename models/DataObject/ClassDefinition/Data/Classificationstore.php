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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool;

class Classificationstore extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface, NormalizerInterface, PreGetDataInterface, LayoutDefinitionEnrichmentInterface, VarExporterInterface, ClassSavedInterface
{
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     */
    public array $children = [];

    /**
     * @internal
     *
     */
    public ?string $name = null;

    /**
     * @internal
     *
     */
    public string $region;

    /**
     * @internal
     *
     */
    public string $layout;

    /**
     * @internal
     *
     */
    public ?string $title = null;

    /**
     * @internal
     *
     */
    public int $maxTabs;

    /**
     * @internal
     *
     */
    public int $labelWidth = 0;

    /**
     * @internal
     */
    public bool $localized = false;

    /**
     * @internal
     *
     */
    public int $storeId;

    /**
     * @internal
     */
    public bool $hideEmptyData = false;

    /**
     * @internal
     */
    public bool $disallowAddRemove = false;

    /**
     * contains further localized field definitions if there are more than one localized fields in on class
     *
     * @internal
     *
     */
    protected array $referencedFields = [];

    /**
     * @internal
     *
     */
    public ?array $fieldDefinitionsCache = null;

    /**
     * @internal
     *
     */
    public array $allowedGroupIds;

    /**
     * @internal
     *
     */
    public array $activeGroupDefinitions = [];

    /**
     * @internal
     *
     */
    public ?int $maxItems = null;

    /**
     * @internal
     *
     */
    public array $permissionView;

    /**
     * @internal
     *
     */
    public array $permissionEdit;

    /**
     * @param Concrete|null $object
     *
     * @throws Exception
     *
     * @see Data::getDataForEditmode
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        if (!$data instanceof DataObject\Classificationstore) {
            return [];
        }

        $fieldData = [];
        $metaData = [];
        $result = $this->doGetDataForEditMode($data, $object, $fieldData, $metaData, 1);

        // replace the real data with the data for the editmode
        foreach ($result['data'] as $language => &$groups) {
            foreach ($groups as $groupId => &$keys) {
                foreach ($keys as $keyId => &$keyValue) {
                    $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                    if ($keyConfig->getEnabled()) {
                        $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                        $keyValue = $fd->getDataForEditmode($keyValue, $object, $params);
                    }
                }
            }
        }

        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        $validLanguages = $this->getValidLanguages();

        foreach ($validLanguages as $language) {
            foreach ($activeGroupIds as $groupId => $enabled) {
                if (!$enabled) {
                    continue;
                }

                $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
                $relation->setCondition("`type` = 'calculatedValue' and groupId = " . $relation->quote($groupId));
                $relation = $relation->load();
                foreach ($relation as $key) {
                    $keyId = $key->getKeyId();
                    $childDef = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                    $childData = new DataObject\Data\CalculatedValue($this->getName());
                    $childData->setContextualData('classificationstore', $this->getName(), null, $language, $groupId, $keyId, $childDef);
                    $childData = $childDef->getDataForEditmode($childData, $object, $params);
                    $result['data'][$language][$groupId][$keyId] = $childData;
                }
            }
        }

        $result['activeGroups'] = $data->getActiveGroups();
        $result['groupCollectionMapping'] = $data->getGroupCollectionMappings();

        return $result;
    }

    /**
     * @param array $fieldData structure: [language][groupId][keyId] = field data
     * @param array $metaData structure: [language][groupId][keyId] = array with meta info
     */
    private function doGetDataForEditMode(DataObject\Classificationstore $data, Concrete $object, array &$fieldData, array &$metaData, int $level = 1): array
    {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        $items = $data->getItems();

        foreach ($items as $groupId => $keys) {
            if (!isset($data->getActiveGroups()[$groupId])) {
                continue;
            }
            foreach ($keys as $keyId => $languages) {
                $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                if ($keyConfig->getEnabled()) {
                    $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    foreach ($languages as $language => $value) {
                        $fdata = $value;
                        if (!isset($fieldData[$language][$groupId][$keyId]) || $fd->isEmpty($fieldData[$language][$groupId][$keyId])) {
                            // never override existing data
                            $fieldData[$language][$groupId][$keyId] = $fdata;
                            if (!$fd->isEmpty($fdata)) {
                                $metaData[$language][$groupId][$keyId] = ['inherited' => $level > 1, 'objectid' => $object->getId()];
                            }
                        }
                    }
                }
            }
        }

        // TODO
        if ($inheritanceAllowed) {
            // check if there is a parent with the same type
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                // same type, iterate over all language and all fields and check if there is something missing
                if ($this->localized) {
                    $validLanguages = Tool::getValidLanguages();
                } else {
                    $validLanguages = [];
                }
                array_unshift($validLanguages, 'default');

                $foundEmptyValue = false;

                $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

                foreach ($validLanguages as $language) {
                    foreach ($activeGroupIds as $groupId => $enabled) {
                        if (!$enabled) {
                            continue;
                        }

                        $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
                        $relation->setCondition('groupId = ' . $relation->quote($groupId));
                        $relation = $relation->load();
                        foreach ($relation as $key) {
                            $keyId = $key->getKeyId();
                            $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                            if ($fd->isEmpty($fieldData[$language][$groupId][$keyId] ?? null)) {
                                $foundEmptyValue = true;
                                $inherited = true;
                                $metaData[$language][$groupId][$keyId] = ['inherited' => true, 'objectid' => $parent->getId()];
                            }
                        }
                    }
                }

                if ($foundEmptyValue) {
                    // still some values are missing, ask the parent
                    $getter = 'get' . ucfirst($this->getName());
                    $parentData = $parent->$getter();
                    $parentResult = $this->doGetDataForEditMode($parentData, $parent, $fieldData, $metaData, $level + 1);
                }
            }
        }

        $result = [
            'data' => $fieldData,
            'metaData' => $metaData,
            'inherited' => $inherited,
        ];

        return $result;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(
        mixed $data,
        DataObject\Concrete $object = null,
        array $params = []
    ): DataObject\Classificationstore {
        $classificationStore = $this->getDataFromObjectParam($object);

        if (!$classificationStore instanceof DataObject\Classificationstore) {
            $classificationStore = new DataObject\Classificationstore();
        }

        $activeGroups = $data['activeGroups'];
        $groupCollectionMapping = $data['groupCollectionMapping'];
        $data = $data['data'];

        $correctedMapping = [];

        foreach ($groupCollectionMapping as $groupId => $collectionId) {
            if (isset($activeGroups[$groupId]) && $activeGroups[$groupId]) {
                $correctedMapping[$groupId] = $collectionId;
            }
        }

        $classificationStore->setGroupCollectionMappings($correctedMapping);

        if (is_array($data)) {
            foreach ($data as $language => $groups) {
                foreach ($groups as $groupId => $keys) {
                    foreach ($keys as $keyId => $value) {
                        $keyConfig = $this->getKeyConfiguration($keyId);

                        $dataDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig(
                            $keyConfig
                        );

                        $dataFromEditMode = $dataDefinition->getDataFromEditmode($value);
                        $activeGroups[$groupId] = true;

                        $classificationStore->setLocalizedKeyValue($groupId, $keyId, $dataFromEditMode, $language);
                    }
                }
            }
        }

        $activeGroupIds = array_keys($activeGroups);

        $classificationStore->setActiveGroups($activeGroups);

        // cleanup
        $existingGroupIds = $classificationStore->getGroupIdsWithData();
        foreach ($existingGroupIds as $existingGroupId) {
            if (!in_array($existingGroupId, $activeGroupIds)) {
                $classificationStore->removeGroupData($existingGroupId);
            }
        }

        return $classificationStore;
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(mixed $data, Concrete $object = null, array $params = []): string
    {
        return 'not supported';
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        // this is handled directly in the template
        // https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/templates/admin/data_object/data_object/preview_version.html.twig
        return 'CLASSIFICATIONSTORE';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return 'not supported';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $dataString = '';
        $getter = 'get' . ucfirst($this->getName());
        $classificationStore = $object->$getter();
        $items = $classificationStore->getItems();
        $activeGroups = $classificationStore->getActiveGroups();
        $params['owner'] = $classificationStore;

        if ($items) {
            foreach ($items as $groupId => $keys) {
                if (!isset($activeGroups[$groupId])) {
                    continue;
                }
                foreach ($keys as $keyId => $values) {
                    $keyConfig = $this->getKeyConfiguration($keyId);
                    /** @var ResourcePersistenceAwareInterface $fieldDefinition */
                    $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    foreach ($values as $language => $value) {
                        $value = $fieldDefinition->getDataForResource($value, $object, $params);
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $dataString .= $value . ' ';
                    }
                }
            }
        }

        return $dataString;
    }

    public function isEmpty(mixed $data): bool
    {
        if ($data instanceof DataObject\Classificationstore) {
            return empty($data->getItems());
        }

        return is_null($data);
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return $this
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;
        $this->fieldDefinitionsCache = null;

        return $this;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * typehint "mixed" is required for asset-metadata-definitions bundle
     * since it doesn't extend Core Data Types
     *
     * @param Data|Layout $child
     */
    public function addChild(mixed $child): void
    {
        $this->children[] = $child;
        $this->fieldDefinitionsCache = null;
    }

    public function setReferencedFields(array $referencedFields): void
    {
        $this->referencedFields = $referencedFields;
        $this->fieldDefinitionsCache = null;
    }

    public function getReferencedFields(): array
    {
        return $this->referencedFields;
    }

    public function addReferencedField(Data $field): void
    {
        $this->referencedFields[] = $field;
        $this->fieldDefinitionsCache = null;
    }

    public function save(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $classificationStore = $this->getDataFromObjectParam($object);
        if ($classificationStore instanceof DataObject\Classificationstore) {
            $classificationStore->setObject($object);
            $classificationStore->save();
        }
    }

    public function load(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): DataObject\Classificationstore
    {
        $classificationStore = new DataObject\Classificationstore();
        $classificationStore->setObject($object);
        $classificationStore->setFieldname($this->getName());
        $classificationStore->load();

        return $classificationStore;
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $classificationStore = $this->getDataFromObjectParam($object);

        if ($classificationStore instanceof DataObject\Classificationstore) {
            $classificationStore->setObject($object);
            $classificationStore->setFieldname($this->getName());
            $classificationStore->delete();
        }
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the classification data
     *
     */
    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        $classificationStore = new DataObject\Classificationstore();
        $classificationStore->setClass($class);
        $classificationStore->createUpdateTable();
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        if (!$container instanceof DataObject\Concrete) {
            throw new Exception('Classification store fields are only valid in Objects');
        }

        if (!$container->getObjectVar($this->getName()) instanceof DataObject\Classificationstore) {
            $store = new DataObject\Classificationstore();
            $store->setObject($container);
            $store->setFieldname($this->getName());

            $container->{'set' . $this->getName()}($store);
        }

        return $container->getObjectVar($this->getName());
    }

    public function getKeyConfiguration(int $keyId): DataObject\Classificationstore\KeyConfig
    {
        /** @var DataObject\Classificationstore\KeyConfig $keyConfig */
        $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);

        return $keyConfig;
    }

    /**
     * @return $this
     */
    public function setLayout(mixed $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $activeGroups = $data->getActiveGroups();
        if (!$activeGroups) {
            return;
        }
        $items = $data->getItems();
        $validLanguages = $this->getValidLanguages();
        $subItems = [];
        $getInheritedValues = DataObject::doGetInheritedValues();

        if (!$omitMandatoryCheck) {
            if ($this->maxItems && count($activeGroups) > $this->maxItems) {
                throw new Model\Element\ValidationException(
                    'Groups in field [' . $this->getName() . '] is bigger than ' . $this->getMaxItems()
                );
            }

            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $groupDefinition = DataObject\Classificationstore\GroupConfig::getById($activeGroupId);
                    if (!$groupDefinition) {
                        continue;
                    }

                    $keyGroupRelations = $groupDefinition->getRelations();

                    foreach ($keyGroupRelations as $keyGroupRelation) {
                        foreach ($validLanguages as $validLanguage) {
                            $keyId = $keyGroupRelation->getKeyId();

                            $object = $data->getObject();
                            if ($object->getClass()->getAllowInherit()) {
                                DataObject::setGetInheritedValues(true);
                                $value = $data->getLocalizedKeyValue($activeGroupId, $keyId, $validLanguage, true);
                                DataObject::setGetInheritedValues($getInheritedValues);
                            } else {
                                $value = $items[$activeGroupId][$keyId][$validLanguage] ?? null;
                            }

                            $keyDef = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(json_decode($keyGroupRelation->getDefinition(), true), $keyGroupRelation->getType());

                            if ($keyGroupRelation->isMandatory()) {
                                $keyDef->setMandatory(true);
                            }

                            try {
                                $keyDef->checkValidity($value, false, $params);
                            } catch (Exception $exception) {
                                $subItems[] = new Model\Element\ValidationException(
                                    $exception->getMessage() . ' (' . $validLanguage . ')',
                                    $exception->getCode(),
                                    $exception->getPrevious()
                                );
                            }
                        }
                    }
                }
            }
        }

        if ($subItems) {
            $messages = array_map(function (Model\Element\ValidationException $validationException) {
                $validationException->addContext($this->getName());

                return $validationException->getMessage();
            }, $subItems);

            $validationException = new Model\Element\ValidationException(implode(', ', $messages));
            $validationException->setSubItems($subItems);

            throw $validationException;
        }
    }

    /**
     *
     * @throws Exception
     */
    public function getDiffDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        throw new Exception('not supported');
    }

    /**
     * @throws Exception
     */
    public function getDiffDataFromEditmode(array $data, Concrete $object = null, array $params = []): mixed
    {
        throw new Exception('not supported');
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return false;
    }

    public function getBlockedVarsForExport(): array
    {
        return [
            'fieldDefinitionsCache',
            'referencedFields',
            'blockedVarsForExport',
        ];
    }

    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        $blockedVars = $this->getBlockedVarsForExport();

        foreach ($blockedVars as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    public function setMaxTabs(int $maxTabs): void
    {
        $this->maxTabs = $maxTabs;
    }

    public function getMaxTabs(): int
    {
        return $this->maxTabs;
    }

    public function setLabelWidth(int $labelWidth): void
    {
        $this->labelWidth = $labelWidth;
    }

    public function getLabelWidth(): int
    {
        return $this->labelWidth;
    }

    public function setMaxItems(?int $maxItems): void
    {
        $this->maxItems = $maxItems;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function isLocalized(): bool
    {
        return $this->localized;
    }

    public function setLocalized(bool $localized): void
    {
        $this->localized = $localized;
    }

    public function getPermissionView(): ?array
    {
        return $this->permissionView;
    }

    public function setPermissionView(array|string|null $permissionView): void
    {
        $this->permissionView = $permissionView;
    }

    public function getPermissionEdit(): ?array
    {
        return $this->permissionEdit;
    }

    public function setPermissionEdit(array|string|null $permissionEdit): void
    {
        $this->permissionEdit = $permissionEdit;
    }

    private function recursiveGetActiveGroupCollectionMapping(?Concrete $object, array $mergedMapping = []): ?array
    {
        if (!$object) {
            return null;
        }

        $getter = 'get' . ucfirst($this->getName());
        /** @var DataObject\Classificationstore $classificationStore */
        $classificationStore = $object->$getter();
        $mapping = $classificationStore->getGroupCollectionMappings();

        foreach ($mapping as $groupId => $collectionId) {
            if (!isset($mergedMapping[$groupId]) && $collectionId) {
                $mergedMapping[$groupId] = $collectionId;
            }
        }

        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();

        if ($inheritanceAllowed) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                $mergedMapping = $this->recursiveGetActiveGroupCollectionMapping($parent, $mergedMapping);
            }
        }

        return $mergedMapping;
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     * @internal
     */
    public function recursiveGetActiveGroupsIds(?Concrete $object, array $activeGroups = []): ?array
    {
        if (!$object) {
            return null;
        }

        $getter = 'get' . ucfirst($this->getName());
        /** @var DataObject\Classificationstore $classificationStore */
        $classificationStore = $object->$getter();
        $activeGroupIds = $classificationStore->getActiveGroups();

        if ($activeGroupIds) {
            $activeGroups = array_filter($activeGroupIds);
        }

        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();

        if ($inheritanceAllowed) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                $activeGroups += $this->recursiveGetActiveGroupsIds($parent, $activeGroups);
            }
        }

        return $activeGroups;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $this->activeGroupDefinitions = [];
        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        if (!$activeGroupIds) {
            return $this;
        }

        $filteredGroupIds = array_keys($activeGroupIds, true, true);

        $groupList = new DataObject\Classificationstore\GroupConfig\Listing();
        $groupList->setCondition('`id` in (' . implode(',', array_fill(0, count($filteredGroupIds), '?')) . ')', $filteredGroupIds);

        $groupList->setOrderKey(['id']);
        $groupList->setOrder(['ASC']);

        foreach ($groupList->load() as $group) {
            $keyList = [];

            $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
            $relation->setCondition('`groupId` = ?', $group->getId());
            $relation->setOrderKey(['sorter', 'id']);
            $relation->setOrder(['ASC', 'ASC']);
            $relation = $relation->load();
            foreach ($relation as $keyGroupRelation) {
                if (!$keyGroupRelation->isEnabled()) {
                    continue;
                }
                $definition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyGroupRelation);

                // changes here also have an effect here: "https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/public/js/pimcore/object/tags/classificationstore.js"
                $fallbackTooltip = $definition->getName();
                if (!empty($keyGroupRelation->getDescription())) {
                    $fallbackTooltip .= ' - ' . $keyGroupRelation->getDescription();
                }

                $definition->setTooltip($definition->getTooltip() ?: $fallbackTooltip);

                if (method_exists($definition, '__wakeup')) {
                    $definition->__wakeup();
                }

                $definition->setMandatory($definition->getMandatory() || $keyGroupRelation->isMandatory());

                if ($definition instanceof LayoutDefinitionEnrichmentInterface) {
                    $context['object'] = $object;
                    $context['class'] = $object->getClass();
                    $context['ownerType'] = 'classificationstore';
                    $context['ownerName'] = $this->getName();
                    $context['keyId'] = $keyGroupRelation->getKeyId();
                    $context['groupId'] = $keyGroupRelation->getGroupId();
                    $context['keyDefinition'] = $definition;
                    $definition = $definition->enrichLayoutDefinition($object, $context);
                }

                $keyList[] = [
                    'name' => $keyGroupRelation->getName(),
                    'id' => $keyGroupRelation->getKeyId(),
                    'description' => $keyGroupRelation->getDescription(),
                    'definition' => $definition,
                ];
            }

            $this->activeGroupDefinitions[$group->getId()] = [
                'name' => $group->getName(),
                'id' => $group->getId(),
                'description' => $group->getDescription(),
                'keys' => $keyList,
            ];
        }

        $groupCollectionMapping = $this->recursiveGetActiveGroupCollectionMapping($object);
        if (!empty($groupCollectionMapping)) {
            $collectionIds = array_values($groupCollectionMapping);

            $relation = new DataObject\Classificationstore\CollectionGroupRelation\Listing();
            $relation->setCondition('`colId` in (' . implode(',', array_fill(0, count($collectionIds), '?')) . ')', $collectionIds);

            $sorting = [];
            foreach ($relation->load() as $item) {
                $sorting[$item->getGroupId()] = $item->getSorter();
            }

            usort($this->activeGroupDefinitions, static function ($a, $b) use ($sorting) {
                $s1 = $sorting[$a['id']] ?? 0;
                $s2 = $sorting[$b['id']] ?? 0;

                return $s1 <=> $s2;
            });
        }

        return $this;
    }

    public function getAllowedGroupIds(): array
    {
        return $this->allowedGroupIds;
    }

    public function setAllowedGroupIds(array|string $allowedGroupIds): void
    {
        $parts = [];
        if (is_string($allowedGroupIds) && !empty($allowedGroupIds)) {
            $allowedGroupIds = str_replace([' ', "\n"], '', $allowedGroupIds);
            $parts = explode(',', $allowedGroupIds);
        } elseif (is_array($allowedGroupIds)) {
            $parts = $allowedGroupIds;
        }

        $this->allowedGroupIds = $parts;
    }

    public function getStoreId(): int
    {
        return $this->storeId ? $this->storeId : 1;
    }

    /**
     * @return $this
     */
    public function setStoreId(int $storeId): static
    {
        $this->storeId = $storeId ? $storeId : 1;

        return $this;
    }

    /**
     * @return string[]
     */
    private function getValidLanguages(): array
    {
        if ($this->localized) {
            $validLanguages = Tool::getValidLanguages();
        } else {
            $validLanguages = [];
        }
        array_unshift($validLanguages, 'default');

        return $validLanguages;
    }

    public function getHideEmptyData(): bool
    {
        return $this->hideEmptyData;
    }

    /**
     * @return $this
     */
    public function setHideEmptyData(bool $hideEmptyData): static
    {
        $this->hideEmptyData = $hideEmptyData;

        return $this;
    }

    public function isDisallowAddRemove(): bool
    {
        return $this->disallowAddRemove;
    }

    /**
     * @return $this
     */
    public function setDisallowAddRemove(bool $disallowAddRemove): static
    {
        $this->disallowAddRemove = $disallowAddRemove;

        return $this;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Classificationstore::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Classificationstore::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Classificationstore::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Classificationstore::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Classificationstore) {
            $validLanguages = array_merge(['default'], Tool::getValidLanguages());
            $result = [];
            $activeGroups = $value->getActiveGroups();
            if ($activeGroups) {
                foreach ($activeGroups as $groupId => $active) {
                    if (!$active) {
                        continue;
                    }

                    $groupConfig = DataObject\Classificationstore\GroupConfig::getById($groupId);
                    $result[$groupConfig->getName()] = [];

                    $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
                    $relation->setCondition('groupId = ' . $relation->quote($groupId));
                    $relation = $relation->load();

                    foreach ($validLanguages as $validLanguage) {
                        foreach ($relation as $key) {
                            $keyId = $key->getKeyId();

                            $csValue = $value->getLocalizedKeyValue($groupId, $keyId, $validLanguage, true, true);
                            $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                            $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                            if ($fd instanceof NormalizerInterface) {
                                $csValue = $fd->normalize($csValue, $params);
                            }
                            if ($csValue !== null) {
                                $result[$groupConfig->getName()][$validLanguage][$key->getName()] = $csValue;
                            }
                        }
                    }
                }
            }

            return $result;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Classificationstore
    {
        if (is_array($value)) {
            $activeGroups = [];
            $resultData = [];
            foreach ($value as $groupName => $groupData) {
                $groupConfig = DataObject\Classificationstore\GroupConfig::getByName($groupName);
                $activeGroups[$groupConfig->getId()] = true;
                $resultData[$groupConfig->getId()] = [];

                foreach ($groupData as $language => $languageData) {
                    foreach ($languageData as $fieldKey => $fieldData) {
                        $keyConfig = DataObject\Classificationstore\KeyConfig::getByName($fieldKey);
                        $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                        if ($fd instanceof NormalizerInterface) {
                            $fieldData = $fd->denormalize($fieldData, $params);
                        }
                        $resultData[$groupConfig->getId()][$keyConfig->getId()][$language] = $fieldData;
                    }
                }
            }

            $result = new DataObject\Classificationstore();
            $result->setActiveGroups($activeGroups);
            $result->setItems($resultData);

            return $result;
        }

        return null;
    }

    /**
     * Creates getter code which is used for generation of php file for object classes using this data type
     *
     *
     */
    public function getGetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
    {
        $key = $this->getName();

        $typeDeclaration = '';
        if ($this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface && $this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        }

        $code = '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . '()' . $typeDeclaration . "\n";
        $code .= '{' . "\n";

        $code .= $this->getPreGetValueHookCode($key);

        $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n\n";
        $code .= "\t" . 'return $data;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }

    public function getFieldType(): string
    {
        return 'classificationstore';
    }
}
