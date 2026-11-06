<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Helper\DocBlockSanitizer;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool;

class Classificationstore extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface, NormalizerInterface, PreGetDataInterface, LayoutDefinitionEnrichmentInterface, VarExporterInterface, ClassSavedInterface, EqualComparisonInterface
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
     *
     * @deprecated use the per key flag Pimcore\Model\DataObject\Classificationstore\KeyConfig::setLocalized()
     *             instead; this stays the default for the keys that carry none
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
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
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
                    if ($keyValue instanceof DataObject\Classificationstore\RenderedRelation) {
                        // a relation key the store rendered from its descriptors, so that the
                        // payload costs no element load per target
                        $keyValue = $keyValue->getData();

                        continue;
                    }

                    $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                    if ($keyConfig->getEnabled()) {
                        $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                        $keyValue = $fd->getDataForEditmode($keyValue, $object, $params);
                    }
                }
            }
        }

        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        $relationCache = $this->getKeyGroupRelationCache();
        $relationCache->load(array_keys($activeGroupIds, true, true));

        foreach ($activeGroupIds as $groupId => $enabled) {
            if (!$enabled) {
                continue;
            }

            foreach ($relationCache->getRelations($groupId) as $keyId => $key) {
                if ($key->getType() !== 'calculatedValue') {
                    continue;
                }

                $childDef = $relationCache->getFieldDefinition($groupId, $keyId);

                foreach ($this->getValidLanguages($keyId) as $language) {
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

        $relationCache = $this->getKeyGroupRelationCache();
        $relationCache->load(array_keys($data->getActiveGroups(), true, true));

        // the relations above bring the key configs of the keys that are still in their group with
        // them; the ones of values whose key was removed from the group are read here, in one go,
        // so that the loop below does not resolve them a statement at a time
        DataObject\Classificationstore\DefinitionCache::load(
            $this->getItemKeyIds($data->getItems(), $data->getActiveGroups())
        );

        // this reader takes the items as they are, so the relation descriptors are rendered here -
        // from their stored descriptors, without instantiating a target
        $renderedRelations = $data->renderRelationsForEditMode();

        $items = $data->getItems();

        foreach ($items as $groupId => $keys) {
            if (!isset($data->getActiveGroups()[$groupId])) {
                continue;
            }
            foreach ($keys as $keyId => $languages) {
                $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                if ($keyConfig->getEnabled()) {
                    $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    // a stored value is answered only for the languages the key is kept in
                    $keyLanguages = array_flip($this->getValidLanguages($keyId));

                    foreach (array_intersect_key($languages, $keyLanguages) as $language => $value) {
                        $fdata = $renderedRelations[$groupId][$keyId][$language] ?? $value;
                        if (!isset($fieldData[$language][$groupId][$keyId]) || $this->isValueEmpty($fd, $fieldData[$language][$groupId][$keyId])) {
                            // never override existing data
                            $fieldData[$language][$groupId][$keyId] = $fdata;
                            if (!$this->isValueEmpty($fd, $fdata)) {
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
                $foundEmptyValue = false;

                $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

                $relationCache->load(array_keys($activeGroupIds, true, true));

                foreach ($activeGroupIds as $groupId => $enabled) {
                    if (!$enabled) {
                        continue;
                    }

                    foreach (array_keys($relationCache->getRelations($groupId)) as $keyId) {
                        $fd = $relationCache->getFieldDefinition($groupId, $keyId);

                        foreach ($this->getValidLanguages($keyId) as $language) {
                            if ($this->isValueEmpty($fd, $fieldData[$language][$groupId][$keyId] ?? null)) {
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
     * Whether the edit mode value of one key counts as empty, which decides whether the payload
     * asks the inheritable parent for it.
     *
     * A relation key the store rendered answers for itself, {@see DataObject\Classificationstore\RenderedRelation::isEmpty()}:
     * it carries one entry per target that was rendered, so the answer is the one the elements
     * would have given.
     */
    private function isValueEmpty(Data $fd, mixed $value): bool
    {
        if ($value instanceof DataObject\Classificationstore\RenderedRelation) {
            return $value->isEmpty();
        }

        return $fd->isEmpty($value);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(
        mixed $data,
        ?DataObject\Concrete $object = null,
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
                        // the save writes the languages the key is kept in, which the payload answered
                        if (!in_array($language, $this->getValidLanguages($keyId), true)) {
                            continue;
                        }

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
    public function getDataForGrid(mixed $data, ?Concrete $object = null, array $params = []): string
    {
        return 'not supported';
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
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
                    $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    if ($fieldDefinition instanceof ResourcePersistenceAwareInterface) {
                        foreach ($values as $value) {
                            $value = $fieldDefinition->getDataForResource($value, $object, $params);
                            if (is_array($value)) {
                                $value = implode(',', $value);
                            }
                            $dataString .= $value . ' ';
                        }
                    }
                }
            }
        }

        return $dataString;
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if (!$data instanceof DataObject\Classificationstore) {
            return $tags;
        }

        foreach ($this->getOwnStoredValues($data) as [$fieldDefinition, $value]) {
            $tags = $fieldDefinition->getCacheTags(
                $this->resolveForProjectType($data, $fieldDefinition, $value),
                $tags
            );
        }

        return $tags;
    }

    public function resolveDependencies(mixed $data): array
    {
        if (!$data instanceof DataObject\Classificationstore) {
            return [];
        }

        $dependencies = [];

        foreach ($this->getOwnStoredValues($data) as [$fieldDefinition, $value]) {
            $dependencies = array_merge(
                $dependencies,
                $this->resolveValueDependencies($data, $fieldDefinition, $value)
            );
        }

        return $dependencies;
    }

    /**
     * A relation-typed key the object never read still holds the descriptors the document was
     * loaded from. They name the elements the dependency rows need, so the rows are written without
     * a target having to be instantiated - and without an ordinary save wiping the rows of a key
     * nobody looked at.
     *
     * Only for the five relation types core ships, whose `resolveDependencies()` the descriptors
     * reproduce exactly. A type a project adds is asked its own, {@see self::resolveForProjectType()}.
     *
     * @return array<string, array{id: int, type: string}>
     *
     * @throws Exception
     */
    private function resolveValueDependencies(
        DataObject\Classificationstore $data,
        Data $fieldDefinition,
        mixed $value
    ): array {
        if (
            $value instanceof DataObject\Classificationstore\RelationDescriptor
            && $this->getRelationResolver()->isCoreRelation($fieldDefinition)
        ) {
            return $this->getRelationResolver()->describeDependencies($value->getValue());
        }

        return $fieldDefinition->resolveDependencies(
            $this->resolveForProjectType($data, $fieldDefinition, $value)
        );
    }

    /**
     * The elements of an unread relation whose type core does not ship, and every other value as it
     * stands.
     *
     * What this field answers from stored descriptors rather than from the key - the dependency
     * rows, the four validity constraints, the edit mode payload - it answers for the five relation
     * types core ships, which declare exactly that. A relation type a project adds may answer any
     * of it differently from an overridden method, so its descriptors are resolved into the
     * elements that method expects, at one element load per target: what an object saves must not
     * depend on whether its keys had been read.
     *
     * A descriptor of one of the five is handed over as it stands. Nothing their own methods are
     * asked here can turn on it - `getCacheTags()` answers with the tags it was given whatever the
     * value, {@see Data::getCacheTags()}, and the dependency rows are read off the descriptors
     * themselves - so resolving would buy an unchanged answer at an element load per target.
     *
     * @throws Exception
     */
    private function resolveForProjectType(
        DataObject\Classificationstore $data,
        Data $fieldDefinition,
        mixed $value
    ): mixed {
        if (!$value instanceof DataObject\Classificationstore\RelationDescriptor) {
            return $value;
        }

        $resolver = $this->getRelationResolver();

        if (!$resolver->isRelation($fieldDefinition)) {
            // the key is not a relation any more: the stored descriptor is not a value this
            // definition can be asked about, and resolving it against one would be a guess
            return null;
        }

        if ($resolver->isCoreRelation($fieldDefinition)) {
            return $value;
        }

        return $resolver->resolve($fieldDefinition, $value->getValue(), $data->getObject(), $data)->getData();
    }

    /**
     * Collects the store's own values only - inherited ones belong to the parent object
     */
    private function getOwnStoredValues(DataObject\Classificationstore $data): array
    {
        return DataObject\Service::useInheritedValues(false, fn () => $this->collectStoredValues($data));
    }

    /**
     * Returns a [field definition, value] pair for every stored value of an active group
     */
    private function collectStoredValues(DataObject\Classificationstore $data): array
    {
        $storedValues = [];
        $activeGroups = $data->getActiveGroups();

        foreach ($data->getItems() as $groupId => $keys) {
            if (!isset($activeGroups[$groupId])) {
                continue;
            }

            foreach ($keys as $keyId => $values) {
                $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                if ($keyConfig === null) {
                    continue;
                }

                $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                if ($fieldDefinition === null) {
                    continue;
                }

                foreach ($values as $value) {
                    $storedValues[] = [$fieldDefinition, $value];
                }
            }
        }

        return $storedValues;
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
     * The store is taken from this definition rather than looked up by field name, so that two
     * containers defining a classification store under the same name each create the tables of
     * their own engine.
     *
     * @throws Exception
     */
    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        $storeId = DataObject\Classificationstore\Service::getConfiguredStoreId($this);

        Pimcore::getContainer()->get(DataObject\Classificationstore\Engine\EngineResolver::class)
            ->resolveForStore($storeId)
            ->createUpdateTables($class, $storeId);
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

    /**
     * @throws InvalidArgumentException
     */
    public function setName(string $name): static
    {
        parent::setName($name);

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
        $subItems = [];
        $storedRelations = [];

        if (!$omitMandatoryCheck) {
            if ($this->maxItems && count($activeGroups) > $this->maxItems) {
                throw new Model\Element\ValidationException(
                    'Groups in field [' . $this->getName() . '] is bigger than ' . $this->getMaxItems()
                );
            }

            $relationCache = $this->getKeyGroupRelationCache();
            $existingGroupIds = $this->getExistingGroupIds(array_keys($activeGroups, true, true));
            $relationCache->load($existingGroupIds);

            foreach ($existingGroupIds as $activeGroupId) {
                foreach ($relationCache->getRelations($activeGroupId) as $keyId => $keyGroupRelation) {
                    $keyDef = $relationCache->getMutableFieldDefinition($activeGroupId, $keyId);

                    if ($keyGroupRelation->isMandatory()) {
                        $keyDef->setMandatory(true);
                    }

                    foreach ($this->getValidLanguages($keyId) as $validLanguage) {
                        $object = $data->getObject();
                        if ($object->getClass()->getAllowInherit()) {
                            // the inherited value is selected without a relation-typed one being
                            // resolved, so that a reload-and-save of an inheritable class reads no
                            // target of a key nobody looked at
                            $value = DataObject\Service::useInheritedValues(
                                true,
                                fn () => $data->getStoredKeyValue($activeGroupId, $keyId, $validLanguage, true)
                            );
                        } else {
                            $value = $items[$activeGroupId][$keyId][$validLanguage] ?? null;
                        }

                        if ($value instanceof DataObject\Classificationstore\RelationDescriptor) {
                            if (!$keyDef instanceof Relations\AbstractRelations) {
                                // the key is not a relation any more: the descriptor the document
                                // holds is not a value this definition can be asked about
                                continue;
                            }

                            $resolver = $this->getRelationResolver();

                            if ($resolver->isCoreRelation($keyDef)) {
                                // an unread descriptor names its targets without holding them, and
                                // the key's own check reads the element of every entry. It is
                                // checked against the definition in force now - this one, the
                                // group's - once the walk is done, so that the whole store costs
                                // one round of lookups and no element load
                                $storedRelations[] = [$keyDef, $value->getValue(), $validLanguage];

                                continue;
                            }

                            // a relation type core does not ship may refuse what the four core
                            // constraints allow, and the bulk check implements those four alone. So
                            // its own validator is asked, over the elements and the $params it
                            // expects, at the cost of an element load per target - the same trade
                            // {@see DataObject\Classificationstore\RelationEditModeResolver} makes
                            // for such a type. Validation must not depend on whether the key had
                            // been read.
                            $value = $resolver->resolve($keyDef, $value->getValue(), $object, $data)->getData();
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

            $subItems = array_merge($subItems, $this->checkStoredRelations($storedRelations));
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
     * The stored descriptors of the relation-typed keys nobody read, checked against the definition
     * each of them is assigned to the group under.
     *
     * The targets of every one of them are described in one round of lookups,
     * {@see DataObject\Classificationstore\RelationResolver::summarize()}, so a store holding fifty
     * relation keys in seventeen languages costs one statement per element type rather than one
     * element load per target. What is checked and why the key's own `checkValidity()` cannot be:
     * {@see DataObject\Classificationstore\RelationResolver::checkStoredValidity()}.
     *
     * @param list<array{0: Relations\AbstractRelations, 1: mixed, 2: string}> $storedRelations
     *
     * @return list<Model\Element\ValidationException>
     *
     * @throws Exception
     */
    private function checkStoredRelations(array $storedRelations): array
    {
        if ($storedRelations === []) {
            return [];
        }

        $resolver = $this->getRelationResolver();
        $descriptors = [];

        foreach ($storedRelations as [, $normalizedValue, ]) {
            foreach ($resolver->describe($normalizedValue) as $descriptor) {
                $descriptors[] = $descriptor;
            }
        }

        $summaries = $resolver->summarize($descriptors);
        $exceptions = [];

        foreach ($storedRelations as [$keyDef, $normalizedValue, $language]) {
            try {
                $resolver->checkStoredValidity($keyDef, $normalizedValue, $summaries);
            } catch (Model\Element\ValidationException $exception) {
                $exceptions[] = new Model\Element\ValidationException(
                    $exception->getMessage() . ' (' . $language . ')',
                    $exception->getCode(),
                    $exception->getPrevious()
                );
            }
        }

        return $exceptions;
    }

    /**
     *
     * @throws Exception
     */
    public function getDiffDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        throw new Exception('not supported');
    }

    /**
     * @throws Exception
     */
    public function getDiffDataFromEditmode(array $data, ?Concrete $object = null, array $params = []): mixed
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

    /**
     * @deprecated use the per key flag Pimcore\Model\DataObject\Classificationstore\KeyConfig::getLocalized()
     *             instead; this stays the default for the keys that carry none
     */
    public function isLocalized(): bool
    {
        return $this->localized;
    }

    /**
     * @deprecated use the per key flag Pimcore\Model\DataObject\Classificationstore\KeyConfig::setLocalized()
     *             instead; this stays the default for the keys that carry none
     */
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

        $relationCache = $this->getKeyGroupRelationCache();
        $relationCache->load($filteredGroupIds);

        // the relations bring the key configs with them, so the per key flag below costs no statement
        $localizationResolver = $this->getLocalizationResolver();

        $groupList = new DataObject\Classificationstore\GroupConfig\Listing();
        $groupList->setCondition('`id` in (' . implode(',', array_fill(0, count($filteredGroupIds), '?')) . ')', $filteredGroupIds);

        $groupList->setOrderKey(['id']);
        $groupList->setOrder(['ASC']);

        foreach ($groupList->load() as $group) {
            $keyList = [];

            foreach ($relationCache->getRelations($group->getId()) as $keyId => $keyGroupRelation) {
                if (!$keyGroupRelation->isEnabled()) {
                    continue;
                }
                $definition = $relationCache->getMutableFieldDefinition($group->getId(), $keyId);

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

                // the languages the key is kept in are decided by the key and only inherited from
                // this field, so the editor cannot read them off the field's own localized flag:
                // a key is answered here, filled or empty, and carries its effective flag with it
                $keyList[] = [
                    'name' => $keyGroupRelation->getName(),
                    'id' => $keyGroupRelation->getKeyId(),
                    'description' => $keyGroupRelation->getDescription(),
                    'localized' => $localizationResolver->isKeyLocalized($keyGroupRelation->getKeyId(), $this),
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
     * The languages one key of this field is kept in: `default` and, for a localized key, every
     * valid language. A key that carries no flag of its own inherits this field's.
     *
     * @return string[]
     */
    private function getValidLanguages(int $keyId): array
    {
        if ($this->getLocalizationResolver()->isKeyLocalized($keyId, $this)) {
            $validLanguages = Tool::getValidLanguages();
        } else {
            $validLanguages = [];
        }
        array_unshift($validLanguages, 'default');

        return $validLanguages;
    }

    private function getKeyGroupRelationCache(): DataObject\Classificationstore\KeyGroupRelationCache
    {
        return Pimcore::getContainer()->get(DataObject\Classificationstore\KeyGroupRelationCache::class);
    }

    private function getRelationResolver(): DataObject\Classificationstore\RelationResolver
    {
        return Pimcore::getContainer()->get(DataObject\Classificationstore\RelationResolver::class);
    }

    private function getLocalizationResolver(): DataObject\Classificationstore\LocalizationResolver
    {
        return Pimcore::getContainer()->get(DataObject\Classificationstore\LocalizationResolver::class);
    }

    /**
     * The key ids held by the items of an active group.
     *
     * @param array $items structure: [groupId][keyId][language] = value
     * @param array<int, bool> $activeGroups
     *
     * @return int[]
     */
    private function getItemKeyIds(array $items, array $activeGroups): array
    {
        $keyIds = [];

        foreach ($items as $groupId => $keys) {
            if (!isset($activeGroups[$groupId])) {
                continue;
            }

            foreach (array_keys($keys) as $keyId) {
                $keyIds[] = (int) $keyId;
            }
        }

        return $keyIds;
    }

    /**
     * @param int[] $groupIds
     *
     * @return int[] the given ids whose group config still exists, in the order given
     */
    private function getExistingGroupIds(array $groupIds): array
    {
        if (!$groupIds) {
            return [];
        }

        $groupList = new DataObject\Classificationstore\GroupConfig\Listing();
        $groupList->setCondition('`id` in (' . implode(',', array_fill(0, count($groupIds), '?')) . ')', $groupIds);

        $existingIds = [];
        foreach ($groupList->load() as $group) {
            $existingIds[] = $group->getId();
        }

        return array_values(array_intersect($groupIds, $existingIds));
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
                    if (!$groupConfig) {
                        continue;
                    }

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
        if ($this->getReturnTypeDeclaration()) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        }

        $code = '/**' . "\n";
        $code .= '* Get ' . DocBlockSanitizer::sanitize($this->getName()) . ' - ' . DocBlockSanitizer::sanitize($this->getTitle()) . "\n";
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

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        // Check if they are the same reference
        if ($oldValue === $newValue) {
            return true;
        }

        // Check if related objects are the same
        if ($oldValue->getObject()->getId() !== $newValue->getObject()->getId()) {
            return false;
        }

        // Check if they are of the same type
        if (get_class($oldValue) !== get_class($newValue)) {
            return false;
        }

        // Compare items
        return serialize($oldValue->getItems()) === serialize($newValue->getItems());
    }
}
