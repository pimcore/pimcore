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
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;

/**
 * @method DataObject\Data\ObjectMetadata\Dao getDao()
 */
class AdvancedManyToManyObjectRelation extends ManyToManyObjectRelation implements IdRewriterInterface, PreGetDataInterface, LayoutDefinitionEnrichmentInterface, ClassSavedInterface
{
    use DataObject\Traits\ElementWithMetadataComparisonTrait;
    use DataObject\ClassDefinition\Data\Extension\PositionSortTrait;

    /**
     * @internal
     *
     */
    public ?string $allowedClassId = null;

    /**
     * @internal
     *
     * @var string[]|string|null
     */
    public array|string|null $visibleFields = null;

    /**
     * @internal
     *
     */
    public array $columns = [];

    /**
     * @internal
     *
     * @var string[]
     */
    public array $columnKeys = [];

    /**
     * @internal
     */
    public bool $enableBatchEdit = false;

    /**
     * @internal
     */
    public bool $allowMultipleAssignments = false;

    /**
     * @internal
     *
     * @var array<string, array<string, mixed>>
     */
    public array $visibleFieldDefinitions = [];

    protected function prepareDataForPersistence(array|Element\ElementInterface $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): mixed
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
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
        $list = [
            'dirty' => false,
            'data' => [],
        ];

        if (count($data) > 0) {
            $db = Db::get();
            $targets = [];

            foreach ($data as $relation) {
                $targetId = $relation['dest_id'];
                $targets[] = $targetId;
            }

            $existingTargets = $db->fetchFirstColumn(
                'SELECT id FROM objects WHERE id IN ('.implode(',', $targets).')'
            );

            foreach ($data as $key => $relation) {
                if ($relation['dest_id']) {
                    $source = DataObject::getById($relation['src_id']);
                    $destinationId = $relation['dest_id'];

                    if (!in_array($destinationId, $existingTargets)) {
                        // destination object does not exist anymore
                        $list['dirty'] = true;

                        continue;
                    }

                    if ($source instanceof DataObject\Concrete) {
                        /** @var DataObject\Data\ObjectMetadata $metaData */
                        $metaData = Pimcore::getContainer()->get('pimcore.model.factory')
                            ->build(DataObject\Data\ObjectMetadata::class, [
                                'fieldname' => $this->getName(),
                                'columns' => $this->getColumnKeys(),
                                'object' => null,
                            ]);

                        $metaData->_setOwner($object);
                        $metaData->_setOwnerFieldname($this->getName());
                        $metaData->setObjectId($destinationId);

                        $ownertype = $relation['ownertype'] ?: '';
                        $ownername = $relation['ownername'] ?: '';
                        $position = $relation['position'] ?: '0';
                        $index = $key + 1;

                        $metaData->load(
                            $source,
                            $relation['dest_id'],
                            $this->getName(),
                            $ownertype,
                            $ownername,
                            $position,
                            $index
                        );

                        $list['data'][] = $metaData;
                    }
                }
            }
        }

        //must return array - otherwise this means data is not loaded
        return $list;
    }

    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data)) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof DataObject\Concrete) {
                    $ids[] = $object->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array');
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
            foreach ($data as $mkey => $metaObject) {
                $index = $mkey + 1;
                $object = $metaObject->getObject();
                if ($object instanceof DataObject\Concrete) {
                    $columnData = DataObject\Service::gridObjectData($object, $gridFields, null, ['purpose' => 'editmode']);
                    foreach ($this->getColumns() as $c) {
                        $getter = 'get' . ucfirst($c['key']);

                        try {
                            $columnData[$c['key']] = $metaObject->$getter();
                        } catch (Exception $e) {
                            Logger::debug('Meta column '.$c['key'].' does not exist');
                        }
                    }

                    $columnData['rowId'] = $columnData['id'] . self::RELATION_ID_SEPARATOR . $index . self::RELATION_ID_SEPARATOR . $columnData['type'];

                    $return[] = $columnData;
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

        $relationsMetadata = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $relation) {
                $o = DataObject\Concrete::getById($relation['id']);
                if ($o && $o->getClassName() == $this->getAllowedClassId()) {
                    /** @var DataObject\Data\ObjectMetadata $metaData */
                    $metaData = Pimcore::getContainer()->get('pimcore.model.factory')
                        ->build('Pimcore\Model\DataObject\Data\ObjectMetadata', [
                            'fieldname' => $this->getName(),
                            'columns' => $this->getColumnKeys(),
                            'object' => $o,
                        ]);
                    $metaData->_setOwner($object);
                    $metaData->_setOwnerFieldname($this->getName());

                    foreach ($this->getColumns() as $c) {
                        $setter = 'set' . ucfirst($c['key']);
                        $value = $relation[$c['key']] ?? null;

                        if ($c['type'] == 'multiselect' && is_array($value)) {
                            $value = implode(',', $value);
                        }

                        $metaData->$setter($value);
                    }

                    $relationsMetadata[] = $metaData;
                }
            }
        }

        //must return array if data shall be set
        return $relationsMetadata;
    }

    public function getDataFromGridEditor(array $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(?array $data, Concrete $object = null, array $params = []): array
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
        $items = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                if (!($metaObject instanceof DataObject\Data\ObjectMetadata)) {
                    continue;
                }
                $o = $metaObject->getObject();

                if (!$o) {
                    continue;
                }

                $item = $o->getRealFullPath();

                if (count($metaObject->getData())) {
                    $subItems = [];
                    foreach ($metaObject->getData() as $key => $value) {
                        if (!$value) {
                            continue;
                        }
                        $subItems[] = $key . ': ' . $value;
                    }

                    if (count($subItems)) {
                        $item .= ' <br/><span class="preview-metadata">[' . implode(' | ', $subItems) . ']</span>';
                    }
                }

                $items[] = $item;
            }

            return implode('<br />', $items);
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

            foreach ($data as $objectMetadata) {
                if (!($objectMetadata instanceof DataObject\Data\ObjectMetadata)) {
                    throw new Element\ValidationException('Expected DataObject\\Data\\ObjectMetadata');
                }

                $o = $objectMetadata->getObject();
                if ($o->getClassName() != $this->getAllowedClassId() || !($o instanceof DataObject\Concrete)) {
                    if ($o instanceof DataObject\Concrete) {
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
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
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
            foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
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

    public function save(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        if ($this->skipSaveCheck($object, $params)) {
            return;
        }

        $objectsMetadata = $this->getDataFromObjectParam($object, $params);

        $objectId = null;

        if ($object instanceof DataObject\Concrete) {
            $objectId = $object->getId();
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        } elseif ($object instanceof DataObject\Localizedfield) {
            $objectId = $object->getObject()->getId();
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        }

        if ($object instanceof DataObject\Localizedfield) {
            $classId = $object->getClass()->getId();
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData || $object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $classId = $object->getObject()->getClassId();
        } else {
            $classId = $object->getClassId();
        }

        $table = 'object_metadata_' . $classId;
        $db = Db::get();

        $relation = [];
        $this->enrichDataRow($object, $params, $classId, $relation);

        $position = isset($relation['position']) ? (string)$relation['position'] : '0';
        $context = $params['context'] ?? null;

        if (isset($context['containerType'], $context['subContainerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick') && $context['subContainerType'] === 'localizedfield') {
            $index = $context['index'] ?? null;
            $containerName = $context['fieldname'] ?? null;

            if ($context['containerType'] === 'fieldcollection') {
                $ownerName = '/' . $context['containerType'] . '~' . $containerName . '/' . $index . '/%';
            } else {
                $ownerName = '/' . $context['containerType'] . '~' . $containerName . '/%';
            }

            $sql = Db\Helper::quoteInto($db, 'id = ?', $objectId) . " AND ownertype = 'localizedfield' AND "
                . Db\Helper::quoteInto($db, 'ownername LIKE ?', $ownerName)
                . ' AND ' . Db\Helper::quoteInto($db, 'fieldname = ?', $this->getName())
                . ' AND ' . Db\Helper::quoteInto($db, 'position = ?', $position);
        } else {
            $sql = Db\Helper::quoteInto($db, 'id = ?', $objectId) . ' AND ' . Db\Helper::quoteInto($db, 'fieldname = ?', $this->getName())
                . ' AND ' . Db\Helper::quoteInto($db, 'position = ?', $position);

            if ($context) {
                if (!empty($context['fieldname'])) {
                    $sql .= ' AND '.Db\Helper::quoteInto($db, 'ownername = ?', $context['fieldname']);
                }

                if (!DataObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
                    if ($context['containerType']) {
                        $sql .= ' AND '.Db\Helper::quoteInto($db, 'ownertype = ?', $context['containerType']);
                    }
                }
            }
        }

        $db->executeStatement('DELETE FROM ' . $table . ' WHERE ' . $sql);

        if (!empty($objectsMetadata)) {
            if ($object instanceof DataObject\Localizedfield || $object instanceof DataObject\Objectbrick\Data\AbstractData
                || $object instanceof DataObject\Fieldcollection\Data\AbstractData) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            $counter = 1;
            foreach ($objectsMetadata as $mkey => $meta) {
                $ownerName = isset($relation['ownername']) ? $relation['ownername'] : '';
                $ownerType = isset($relation['ownertype']) ? $relation['ownertype'] : '';
                $meta->save($objectConcrete, $ownerType, $ownerName, $position, $counter);

                $counter++;
            }
        }

        parent::save($object, $params);
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

        // note, in case of advanced many to many relations we don't want to force the loading of the element
        // instead, ask the database directly
        return Element\Service::filterUnpublishedAdvancedElements($data);
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $db = Db::get();
        $context = $params['context'] ?? null;

        if (isset($context['containerType'], $context['subContainerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick') && $context['subContainerType'] === 'localizedfield') {
            if ($context['containerType'] === 'objectbrick') {
                throw new Exception('deletemeta not implemented');
            }
            $containerName = $context['fieldname'] ?? null;
            $index = $context['index'];
            $db->executeStatement(
                'DELETE FROM object_metadata_' . $object->getClassId()
                . ' WHERE ' . Db\Helper::quoteInto($db, 'id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                . Db\Helper::quoteInto($db, 'ownername LIKE ?', '/' . $context['containerType'] . '~' . $containerName . '/' . "$index . /%")
                . ' AND ' . Db\Helper::quoteInto($db, 'fieldname = ?', $this->getName())
            );
        } else {
            $deleteConditions = [
                'id' => $object->getId(),
                'fieldname' => $this->getName(),
            ];
            if ($context) {
                if (!empty($context['fieldname'])) {
                    $deleteConditions['ownername'] = $context['fieldname'];
                }

                if (!DataObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
                    if ($context['containerType']) {
                        $deleteConditions['ownertype'] = $context['containerType'];
                    }
                }
            }

            $db->delete('object_metadata_' . $object->getClassId(), $deleteConditions);
        }
    }

    /**
     * @return $this
     */
    public function setAllowedClassId(?string $allowedClassId): static
    {
        $this->allowedClassId = $allowedClassId;

        return $this;
    }

    public function getAllowedClassId(): ?string
    {
        return $this->allowedClassId;
    }

    public function setVisibleFields(array|string|null $visibleFields): static
    {
        /**
         * @extjs6
         */
        if (is_array($visibleFields)) {
            if (count($visibleFields)) {
                $visibleFields = implode(',', $visibleFields);
            } else {
                $visibleFields = null;
            }
        }
        $this->visibleFields = $visibleFields;

        return $this;
    }

    public function getVisibleFields(): array|string|null
    {
        return $this->visibleFields;
    }

    /**
     * @return $this
     */
    public function setColumns(array $columns): static
    {
        if (isset($columns['key'])) {
            $columns = [$columns];
        }
        usort($columns, [$this, 'sort']);

        $this->columns = [];
        $this->columnKeys = [];
        foreach ($columns as $c) {
            $this->columns[] = $c;
            $this->columnKeys[] = $c['key'];
        }

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnKeys(): array
    {
        $this->columnKeys = [];
        foreach ($this->columns as $c) {
            $this->columnKeys[] = $c['key'];
        }

        return $this->columnKeys;
    }

    public function getEnableBatchEdit(): bool
    {
        return $this->enableBatchEdit;
    }

    public function setEnableBatchEdit(bool $enableBatchEdit): void
    {
        $this->enableBatchEdit = $enableBatchEdit;
    }

    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        /** @var DataObject\Data\ObjectMetadata $temp */
        $temp = Pimcore::getContainer()->get('pimcore.model.factory')
            ->build('Pimcore\Model\DataObject\Data\ObjectMetadata', [
                'fieldname' => null,
            ]);

        $temp->getDao()->createOrUpdateTable($class);
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if (is_array($data)) {
            foreach ($data as &$metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $id = $eo->getId();
                    $type = Element\Service::getElementType($eo);

                    if (array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                        $newElement = Element\Service::getElementById($type, $idMapping[$type][$id]);
                        $metaObject->setObject($newElement);
                    }
                }
            }
        }

        return $data;
    }

    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        if ($mainDefinition instanceof self) {
            $this->allowedClassId = $mainDefinition->getAllowedClassId();
            $this->visibleFields = $mainDefinition->getVisibleFields();
            $this->columns = $mainDefinition->getColumns();
        }
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $classId = $this->allowedClassId;

        if (!$classId) {
            return $this;
        }

        if (is_numeric($classId)) {
            $class = DataObject\ClassDefinition::getById($classId);
        } else {
            $class = DataObject\ClassDefinition::getByName($classId);
        }

        if (!$class) {
            return $this;
        }

        if (!$this->visibleFields) {
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

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $object = $params['object'] ?? null;
            $result = [];
            foreach ($value as $elementMetadata) {
                $elementData = $elementMetadata['element'];

                $type = $elementData['type'];
                $id = $elementData['id'];
                $target = Element\Service::getElementById($type, $id);
                if ($target instanceof DataObject\Concrete) {
                    $columns = $elementMetadata['columns'];
                    $fieldname = $elementMetadata['fieldname'];
                    $data = $elementMetadata['data'];

                    $item = new DataObject\Data\ObjectMetadata($fieldname, $columns, $target);
                    $item->_setOwner($object);
                    $item->_setOwnerFieldname($this->getName());
                    $item->setData($data);
                    $result[] = $item;
                }
            }

            return $result;
        }

        return null;
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            /** @var DataObject\Data\ObjectMetadata $elementMetadata */
            foreach ($value as $elementMetadata) {
                $element = $elementMetadata->getElement();

                $type = Element\Service::getElementType($element);
                $id = $element->getId();
                $result[] = [
                    'element' => [
                        'type' => $type,
                        'id' => $id,
                    ],
                    'fieldname' => $elementMetadata->getFieldname(),
                    'columns' => $elementMetadata->getColumns(),
                    'data' => $elementMetadata->getData(), ];
            }

            return $result;
        }

        return null;
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
                $columns = array_merge(['id', 'fullpath'], $this->getColumnKeys());
                foreach ($items as $itemBeforeCleanup) {
                    $unique = $this->buildUniqueKeyForDiffEditor($itemBeforeCleanup);
                    $item = [];

                    foreach ($itemBeforeCleanup as $key => $value) {
                        if (in_array($key, $columns)) {
                            $item[$key] = $value;
                        }
                    }

                    $itemId = json_encode($item);
                    $raw = $itemId;

                    $newItems[] = [
                        'itemId' => $itemId,
                        'title' => $item['fullpath'],
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
                    'fullpath' => [
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

    public function getAllowMultipleAssignments(): bool
    {
        return $this->allowMultipleAssignments;
    }

    /**
     * @return $this
     */
    public function setAllowMultipleAssignments(bool $allowMultipleAssignments): static
    {
        $this->allowMultipleAssignments = $allowMultipleAssignments;

        return $this;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\'.DataObject\Data\ObjectMetadata::class.'[]';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\'.DataObject\Data\ObjectMetadata::class.'[]';
    }

    public function getFieldType(): string
    {
        return 'advancedManyToManyObjectRelation';
    }
}
