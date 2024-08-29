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
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;

class ManyToManyObjectRelation extends AbstractRelations implements QueryResourcePersistenceAwareInterface, OptimizedAdminLoadingInterface, VarExporterInterface, NormalizerInterface, PreGetDataInterface, PreSetDataInterface, LayoutDefinitionEnrichmentInterface
{
    use DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait;
    use DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait;
    use DataObject\ClassDefinition\Data\Extension\RelationFilterConditionParser;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\DataHeightTrait;

    /**
     * @internal
     *
     */
    public ?int $maxItems = null;

    /**
     * @internal
     */
    public bool $relationType = true;

    /**
     * @internal
     *
     * @var string[]|string|null
     */
    public array|string|null $visibleFields = null;

    /**
     * @internal
     */
    public bool $allowToCreateNewObject = true;

    /**
     * @internal
     */
    public bool $allowToClearRelation = true;

    /**
     * @internal
     */
    public bool $optimizedAdminLoading = false;

    /**
     * @internal
     */
    public bool $enableTextSelection = false;

    /**
     * @internal
     *
     */
    public array $visibleFieldDefinitions = [];

    public function getObjectsAllowed(): bool
    {
        return true;
    }

    protected function prepareDataForPersistence(array|Element\ElementInterface $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): mixed
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $object) {
                if ($object instanceof DataObject\Concrete) {
                    $return[] = [
                        'dest_id' => $object->getId(),
                        'type' => 'object',
                        'fieldname' => $this->getName(),
                        'index' => $counter,
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) && count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }
    }

    protected function loadData(array $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): mixed
    {
        $objects = [
            'dirty' => false,
            'data' => [],
        ];
        foreach ($data as $relation) {
            $o = DataObject::getById($relation['dest_id']);
            if ($o instanceof DataObject\Concrete) {
                $objects['data'][] = $o;
            } else {
                $objects['dirty'] = true;
            }
        }

        //must return array - otherwise this means data is not loaded
        return $objects;
    }

    /**
     *
     *
     * @throws Exception
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data)) {
            foreach ($data as $relation) {
                if ($relation instanceof DataObject\Concrete) {
                    $ids[] = $relation->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array and it is: ' . print_r($data, true));
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $return = [];

        $visibleFieldsArray = $this->getVisibleFields() ? explode(',', $this->getVisibleFields()) : [];

        $gridFields = $visibleFieldsArray;

        // add data
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $referencedObject) {
                if ($referencedObject instanceof DataObject\Concrete) {
                    $return[] = DataObject\Service::gridObjectData($referencedObject, $gridFields, null, ['purpose' => 'editmode']);
                }
            }
        }

        return $return;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        //if not set, return null
        if ($data === null || $data === false) {
            return null;
        }

        $objects = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $object) {
                $o = DataObject::getById($object['id']);
                if ($o) {
                    $objects[] = $o;
                }
            }
        }

        //must return array if data shall be set
        return $objects;
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromGridEditor(array $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function getDataForGrid(?array $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if (is_array($data) && count($data) > 0) {
            $paths = [];
            foreach ($data as $o) {
                if ($o instanceof Element\ElementInterface) {
                    $paths[] = $o->getRealFullPath();
                }
            }

            return implode('<br />', $paths);
        }

        return '';
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        if (is_array($data)) {
            $this->performMultipleAssignmentCheck($data);

            foreach ($data as $o) {
                if (empty($o)) {
                    continue;
                }

                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass || !($o instanceof DataObject\Concrete)) {
                    if (!$allowClass && $o instanceof DataObject\Concrete) {
                        $id = $o->getId();
                    } else {
                        $id = '??';
                    }

                    throw new Element\ValidationException('Invalid object relation to object [' . $id . '] in field ' . $this->getName() . ' , tried to assign ' . $o->getId());
                }
            }

            if ($this->getMaxItems() && count($data) > $this->getMaxItems()) {
                throw new Element\ValidationException('Number of allowed relations in field `' . $this->getName() . '` exceeded (max. ' . $this->getMaxItems() . ')');
            }
        }
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $o) {
                if ($o instanceof DataObject\AbstractObject) {
                    $dependencies['object_' . $o->getId()] = [
                        'id' => $o->getId(),
                        'type' => 'object',
                    ];
                }
            }
        }

        return $dependencies;
    }

    public function preGetData(mixed $container, array $params = []): array
    {
        $data = null;
        if ($container instanceof DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
            if (!$container->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($container);

                $container->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($container);
            }
        } elseif ($container instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            parent::loadLazyFieldcollectionField($container);
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            parent::loadLazyBrickField($container);
            $data = $container->getObjectVar($this->getName());
        }

        return $this->filterUnpublishedElements($data);
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($container);

        return $data;
    }

    /**
     * @return $this
     */
    public function setMaxItems(?int $maxItems): static
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param Element\ElementInterface[]|null $data
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDiffVersionPreview(?array $data, Concrete $object = null, array $params = []): array
    {
        $value = [];
        $value['type'] = 'html';
        $value['html'] = '';

        if ($data) {
            $html = $this->getVersionPreview($data, $object, $params);
            $value['html'] = $html;
        }

        return $value;
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        $data = $this->rewriteIdsService($data, $idMapping);

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\ManyToManyObjectRelation $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->maxItems = $mainDefinition->maxItems;
        $this->relationType = $mainDefinition->relationType;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        if (!$this->visibleFields) {
            return $this;
        }

        $classIds = $this->getClasses();

        if (empty($classIds[0]['classes'])) {
            return $this;
        }

        $classId = $classIds[0]['classes'];

        if (is_numeric($classId)) {
            $class = DataObject\ClassDefinition::getById($classId);
        } else {
            $class = DataObject\ClassDefinition::getByName($classId);
        }

        if (!$class) {
            return $this;
        }

        if (!isset($context['purpose'])) {
            $context['purpose'] = 'layout';
        }

        $this->visibleFieldDefinitions = [];

        $translator = Pimcore::getContainer()->get('translator');

        $visibleFields = explode(',', $this->visibleFields);
        foreach ($visibleFields as $field) {
            $fd = $class->getFieldDefinition($field, $context);

            if (!$fd) {
                $fieldFound = false;
                /** @var Localizedfields|null $localizedfields */
                $localizedfields = $class->getFieldDefinitions($context)['localizedfields'] ?? null;
                if ($localizedfields) {
                    if ($fd = $localizedfields->getFieldDefinition($field)) {
                        $this->visibleFieldDefinitions[$field]['name'] = $fd->getName();
                        $this->visibleFieldDefinitions[$field]['title'] = $fd->getTitle();
                        $this->visibleFieldDefinitions[$field]['fieldtype'] = $fd->getFieldType();

                        if ($fd instanceof DataObject\ClassDefinition\Data\Select || $fd instanceof DataObject\ClassDefinition\Data\Multiselect) {
                            $this->visibleFieldDefinitions[$field]['options'] = $fd->getOptions();
                        }

                        $fieldFound = true;
                    }
                }

                if (!$fieldFound) {
                    $this->visibleFieldDefinitions[$field]['name'] = $field;
                    $this->visibleFieldDefinitions[$field]['title'] = $translator->trans($field, [], 'admin');
                    $this->visibleFieldDefinitions[$field]['fieldtype'] = 'input';
                }
            } else {
                $this->visibleFieldDefinitions[$field]['name'] = $fd->getName();
                $this->visibleFieldDefinitions[$field]['title'] = $fd->getTitle();
                $this->visibleFieldDefinitions[$field]['fieldtype'] = $fd->getFieldType();
                $this->visibleFieldDefinitions[$field]['noteditable'] = true;

                if (
                    $fd instanceof DataObject\ClassDefinition\Data\Select
                    || $fd instanceof DataObject\ClassDefinition\Data\Multiselect
                    || $fd instanceof DataObject\ClassDefinition\Data\BooleanSelect
                ) {
                    if (
                        $fd instanceof DataObject\ClassDefinition\Data\Select
                        || $fd instanceof DataObject\ClassDefinition\Data\Multiselect
                    ) {
                        $this->visibleFieldDefinitions[$field]['optionsProviderClass'] = $fd->getOptionsProviderClass();
                    }

                    $this->visibleFieldDefinitions[$field]['options'] = $fd->getOptions();
                }
            }
        }

        return $this;
    }

    protected function getPhpdocType(): string
    {
        return $this->getPhpDocClassString(true);
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $element) {
                $type = Element\Service::getElementType($element);
                $id = $element->getId();
                $result[] = [
                    'type' => $type,
                    'id' => $id,
                ];
            }

            return $result;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $elementData) {
                $type = $elementData['type'];
                $id = $elementData['id'];
                $element = Element\Service::getElementById($type, $id);
                if ($element) {
                    $result[] = $element;
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * Returns a ID which must be unique across the grid rows
     *
     *
     *
     * @internal
     *
     */
    protected function buildUniqueKeyForDiffEditor(array $item): string
    {
        return (string) $item['id'];
    }

    /**
     * @internal
     */
    protected function processDiffDataForEditMode(mixed $originalData, mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                foreach ($items as $in) {
                    $item = [];
                    $item['id'] = $in['id'];
                    $item['path'] = $in['fullpath'];
                    $item['type'] = $in['type'];

                    $unique = $this->buildUniqueKeyForDiffEditor($item);

                    $itemId = json_encode($item);
                    $raw = $itemId;

                    $newItems[] = [
                        'itemId' => $itemId,
                        'title' => $item['path'],
                        'raw' => $raw,
                        'gridrow' => $item,
                        'unique' => $unique,
                    ];
                }
                $data['data'] = $newItems;
            }

            $data['value'] = [
                'type' => 'grid',
                'columnConfig' => [
                    'id' => [
                        'width' => 60,
                    ],
                    'path' => [
                        'flex' => 2,
                    ],

                ],
                'html' => $this->getVersionPreview($originalData, $object, $params),
            ];

            $newData = [];
            $newData[] = $data;

            return $newData;
        }

        return $data;
    }

    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $originalData = $data;
        $data = parent::getDiffDataForEditMode($data, $object, $params);
        $data = $this->processDiffDataForEditMode($originalData, $data, $object, $params);

        return $data;
    }

    /** See parent class.
     *
     *
     */
    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $tabledata = $data[0]['data'];

            $result = [];
            if ($tabledata) {
                foreach ($tabledata as $in) {
                    $out = json_decode($in['raw'], true);
                    $result[] = $out;
                }
            }

            return $this->getDataFromEditmode($result, $object, $params);
        }

        return null;
    }

    /**
     * @param string[]|string|null $visibleFields
     *
     * @return $this
     */
    public function setVisibleFields(array|string|null $visibleFields): static
    {
        if (is_array($visibleFields) && count($visibleFields)) {
            $visibleFields = implode(',', $visibleFields);
        }
        $this->visibleFields = $visibleFields;

        return $this;
    }

    public function getVisibleFields(): array|null|string
    {
        return $this->visibleFields;
    }

    public function isAllowToCreateNewObject(): bool
    {
        return $this->allowToCreateNewObject;
    }

    public function setAllowToCreateNewObject(bool $allowToCreateNewObject): void
    {
        $this->allowToCreateNewObject = $allowToCreateNewObject;
    }

    public function isAllowedToClearRelation(): bool
    {
        return $this->allowToClearRelation;
    }

    public function setAllowToClearRelation(bool $allowToClearRelation): void
    {
        $this->allowToClearRelation = $allowToClearRelation;
    }

    public function isOptimizedAdminLoading(): bool
    {
        return $this->optimizedAdminLoading;
    }

    public function setOptimizedAdminLoading(bool $optimizedAdminLoading): void
    {
        $this->optimizedAdminLoading = $optimizedAdminLoading;
    }

    public function isEnableTextSelection(): bool
    {
        return $this->enableTextSelection;
    }

    public function setEnableTextSelection(bool $enableTextSelection): void
    {
        $this->enableTextSelection = $enableTextSelection;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function addListingFilter(DataObject\Listing $listing, float|array|int|string|Model\Element\ElementInterface $data, string $operator = '='): DataObject\Listing
    {
        if ($data instanceof DataObject\Concrete) {
            $data = $data->getId();
        }

        if ($operator === '=') {
            $listing->addConditionParam('`'.$this->getName().'` LIKE ?', '%,'.$data.',%');

            return $listing;
        }

        return parent::addListingFilter($listing, $data, $operator);
    }

    /**
     * Filter by relation feature
     *
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $name = $params['name'] ?: $this->name;

        return $this->getRelationFilterCondition($value, $operator, $name);
    }

    public function getQueryColumnType(): string
    {
        return 'text';
    }

    public function getFieldType(): string
    {
        return 'manyToManyObjectRelation';
    }
}
