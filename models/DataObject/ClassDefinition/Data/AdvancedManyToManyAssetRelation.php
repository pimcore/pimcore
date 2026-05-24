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

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Pimcore;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;

class AdvancedManyToManyAssetRelation extends ManyToManyAssetRelation implements IdRewriterInterface, PreGetDataInterface, LayoutDefinitionEnrichmentInterface, ClassSavedInterface
{
    use DataObject\Traits\ElementWithMetadataComparisonTrait;
    use DataObject\ClassDefinition\Data\Extension\PositionSortTrait;

    /**
     * @internal
     *
     * @var string[]
     */
    public array $columnKeys = [];

    /**
     * @internal
     *
     */
    public array $columns = [];

    /**
     * @internal
     */
    public bool $enableBatchEdit = false;

    /**
     * @internal
     */
    public bool $allowMultipleAssignments = false;

    protected function prepareDataForPersistence(array|Element\ElementInterface $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete|null $object = null, array $params = []): mixed
    {
        if (is_array($data)) {
            $return = [];
            $counter = 1;
            foreach ($data as $metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Asset) {
                    $return[] = [
                        'dest_id' => $asset->getId(),
                        'type' => 'asset',
                        'fieldname' => $this->getName(),
                        'index' => $counter,
                    ];
                    $counter++;
                }
            }

            return $return;
        }

        return null;
    }

    protected function loadData(array $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete|null $object = null, array $params = []): mixed
    {
        $list = [
            'dirty' => false,
            'data' => [],
        ];

        if (count($data) > 0) {
            $db = Db::get();
            $targets = [];
            foreach ($data as $relation) {
                if (!empty($relation['dest_id'])) {
                    $targets[] = (int) $relation['dest_id'];
                }
            }

            $existingTargets = [];
            if (!empty($targets)) {
                $existingTargets = $db->fetchFirstColumn(
                    'SELECT id FROM assets WHERE id IN (?)',
                    [$targets],
                    [ArrayParameterType::INTEGER]
                );
            }

            $sources = [];
            foreach ($data as $key => $relation) {
                if (empty($relation['dest_id'])) {
                    continue;
                }

                $destinationId = (int) $relation['dest_id'];
                $destinationExists = empty($targets) || in_array($destinationId, $existingTargets);

                if (!$destinationExists) {
                    $list['dirty'] = true;

                    continue;
                }

                $sourceId = $relation['src_id'] ?? null;
                if (!array_key_exists($sourceId, $sources)) {
                    $sources[$sourceId] = DataObject::getById($sourceId);
                }
                $source = $sources[$sourceId];
                if ($source instanceof DataObject\Concrete) {
                    /** @var DataObject\Data\ElementMetadata $metaData */
                    $metaData = Pimcore::getContainer()->get('pimcore.model.factory')
                        ->build(DataObject\Data\ElementMetadata::class, [
                            'fieldname' => $this->getName(),
                            'columns' => $this->getColumnKeys(),
                            'element' => null,
                        ]);

                    $metaData->_setOwner($object);
                    $metaData->_setOwnerFieldname($this->getName());
                    $metaData->setElementTypeAndId('asset', $destinationId);

                    $ownertype = $relation['ownertype'] ?? '';
                    $ownername = $relation['ownername'] ?? '';
                    $position = $relation['position'] ?? '0';
                    $index = $key + 1;

                    $metaData->load(
                        $source,
                        $destinationId,
                        $this->getName(),
                        $ownertype,
                        $ownername,
                        $position,
                        $index,
                        'asset'
                    );

                    $list['data'][] = $metaData;
                }
            }
        }

        return $list;
    }

    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data)) {
            foreach ($data as $metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Asset) {
                    $ids[] = $asset->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array');
    }

    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): array
    {
        $return = [];
        $visibleFieldsArray = $this->getVisibleFields() ? explode(',', (string) $this->getVisibleFields()) : [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $mkey => $metaAsset) {
                $index = $mkey + 1;
                $asset = $metaAsset->getElement();
                if ($asset instanceof Asset) {
                    $row = Element\Service::gridElementData($asset);

                    if (!empty($visibleFieldsArray)) {
                        foreach ($visibleFieldsArray as $field) {
                            if (array_key_exists($field, $row)) {
                                continue;
                            }

                            $getter = 'get' . ucfirst($field);
                            if (method_exists($asset, $getter)) {
                                $row[$field] = $asset->{$getter}();
                                continue;
                            }

                            $row[$field] = $asset->getMetadata($field);
                        }
                    }

                    foreach ($this->getColumns() as $c) {
                        $getter = 'get' . ucfirst($c['key']);

                        try {
                            $row[$c['key']] = $metaAsset->$getter();
                        } catch (Exception $e) {
                            Logger::debug('Meta column ' . $c['key'] . ' does not exist');
                        }
                    }

                    $row['rowId'] = $row['id'] . self::RELATION_ID_SEPARATOR . $index . self::RELATION_ID_SEPARATOR . ($row['type'] ?? 'asset');

                    $return[] = $row;
                }
            }
        }

        return $return;
    }

    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data === null || $data === false) {
            return null;
        }

        $assetMetadata = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $asset = Asset::getById((int) $element['id']);
                if ($asset instanceof Asset) {
                    /** @var DataObject\Data\ElementMetadata $metaData */
                    $metaData = Pimcore::getContainer()->get('pimcore.model.factory')
                        ->build(
                            DataObject\Data\ElementMetadata::class,
                            [
                                'fieldname' => $this->getName(),
                                'columns' => $this->getColumnKeys(),
                                'element' => $asset,
                            ]
                        );

                    $metaData->_setOwner($object);
                    $metaData->_setOwnerFieldname($this->getName());

                    foreach ($this->getColumns() as $c) {
                        $setter = 'set' . ucfirst($c['key']);
                        $value = $element[$c['key']] ?? null;

                        if ($c['type'] === 'multiselect' && is_array($value)) {
                            $value = implode(',', $value);
                        }

                        $metaData->$setter($value);
                    }

                    $assetMetadata[] = $metaData;
                }
            }
        }

        return $assetMetadata;
    }

    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        $items = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaAsset) {
                if (!($metaAsset instanceof DataObject\Data\ElementMetadata)) {
                    continue;
                }

                $asset = $metaAsset->getElement();
                if (!$asset instanceof Asset) {
                    continue;
                }

                $item = $asset->getRealFullPath();

                if (count($metaAsset->getData())) {
                    $subItems = [];
                    foreach ($metaAsset->getData() as $key => $value) {
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

            foreach ($data as $assetMetadata) {
                if (!($assetMetadata instanceof DataObject\Data\ElementMetadata)) {
                    throw new Element\ValidationException('Expected DataObject\\Data\\ElementMetadata');
                }

                $asset = $assetMetadata->getElement();
                if ($asset instanceof Asset) {
                    $allowAsset = $this->allowAssetRelation($asset);
                } elseif (empty($asset)) {
                    $allowAsset = true;
                } else {
                    $allowAsset = false;
                }

                if (!$allowAsset) {
                    $id = $asset instanceof Asset ? $asset->getId() : '??';
                    throw new Element\ValidationException('Invalid asset relation to asset [' . $id . '] in field ' . $this->getName());
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
            foreach ($data as $metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Element\ElementInterface) {
                    $paths[] = $asset->getRealFullPath();
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
            foreach ($data as $metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Asset) {
                    $dependencies['asset_' . $asset->getId()] = [
                        'id' => $asset->getId(),
                        'type' => 'asset',
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

        $assetsMetadata = $this->getDataFromObjectParam($object, $params);

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
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData ||
            $object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $classId = $object->getObject()->getClassId();
        } else {
            $classId = $object->getClassId();
        }

        $table = 'object_metadata_' . $classId;
        $db = Db::get();

        $relation = [];
        $this->enrichDataRow($object, $params, $classId, $relation);

        $position = isset($relation['position']) ? (string) $relation['position'] : '0';
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
            $sql = Db\Helper::quoteInto($db, 'id = ?', $objectId) . ' AND ' .
                Db\Helper::quoteInto($db, 'fieldname = ?', $this->getName())
                . ' AND ' . Db\Helper::quoteInto($db, 'position = ?', $position);

            if ($context) {
                if (!empty($context['fieldname'])) {
                    $sql .= ' AND ' . Db\Helper::quoteInto($db, 'ownername = ?', $context['fieldname']);
                }

                if (!DataObject::isDirtyDetectionDisabled()) {
                    if ($context['containerType']) {
                        if ($object instanceof Localizedfield) {
                            $context['containerType'] = 'localizedfield';
                        }
                        $sql .= ' AND ' . Db\Helper::quoteInto($db, 'ownertype = ?', $context['containerType']);
                    }
                }
            }
        }

        $db->executeStatement('DELETE FROM ' . $table . ' WHERE ' . $sql);

        if (!empty($assetsMetadata)) {
            if ($object instanceof DataObject\Localizedfield
                || $object instanceof DataObject\Objectbrick\Data\AbstractData
                || $object instanceof DataObject\Fieldcollection\Data\AbstractData
            ) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            $counter = 1;
            foreach ($assetsMetadata as $mkey => $meta) {
                $ownerName = $relation['ownername'] ?? '';
                $ownerType = $relation['ownertype'] ?? '';
                $meta->save($objectConcrete, $ownerType, $ownerName, $position, $counter);
                $counter++;
            }
        }

        parent::save($object, $params);
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        $data = null;
        if ($container instanceof DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
            if (!$container->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($container);

                $container->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($container);
            }
        } elseif ($container instanceof DataObject\Localizedfield || $container instanceof DataObject\Data\BlockElement) {
            $data = $params['data'];
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            parent::loadLazyFieldcollectionField($container);
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            parent::loadLazyBrickField($container);
            $data = $container->getObjectVar($this->getName());
        }

        return Element\Service::filterUnpublishedAdvancedElements($data);
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $db = Db::get();
        $context = $params['context'] ?? null;

        if (isset($context['containerType'], $context['subContainerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick') && $context['subContainerType'] === 'localizedfield') {
            $containerName = $context['fieldname'] ?? null;

            if ($context['containerType'] === 'objectbrick') {
                $db->executeStatement(
                    'DELETE FROM object_metadata_' . $object->getClassId() . ' WHERE ' .
                    Db\Helper::quoteInto($db, 'id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . Db\Helper::quoteInto($db, 'ownername LIKE ?', '/' . $context['containerType'] . '~' . $containerName . '/%')
                    . ' AND ' . Db\Helper::quoteInto($db, 'fieldname = ?', $this->getName())
                );
            } else {
                $index = $context['index'];

                $db->executeStatement(
                    'DELETE FROM object_metadata_' . $object->getClassId() . ' WHERE ' .
                    Db\Helper::quoteInto($db, 'id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . Db\Helper::quoteInto($db, 'ownername LIKE ?', '/' . $context['containerType'] . '~' . $containerName . '/' . $index . '/%')
                    . ' AND ' . Db\Helper::quoteInto($db, 'fieldname = ?', $this->getName())
                );
            }
        } else {
            $deleteCondition = [
                'id' => $object->getId(),
                'fieldname' => $this->getName(),
            ];

            if ($context) {
                if (!empty($context['fieldname'])) {
                    $deleteCondition['ownername'] = $context['fieldname'];
                }

                if (!DataObject::isDirtyDetectionDisabled()) {
                    if (!empty($context['containerType'])) {
                        $deleteCondition['ownertype'] = $context['containerType'];
                    }
                }
            }

            $db->delete('object_metadata_' . $object->getClassId(), $deleteCondition);
        }
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
        /** @var DataObject\Data\ElementMetadata $temp */
        $temp = Pimcore::getContainer()->get('pimcore.model.factory')
            ->build(
                DataObject\Data\ElementMetadata::class,
                [
                    'fieldname' => null,
                ]
            );

        $temp->getDao()->createOrUpdateTable($class);
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if (is_array($data)) {
            foreach ($data as &$metaAsset) {
                $asset = $metaAsset->getElement();
                if ($asset instanceof Element\ElementInterface) {
                    $id = $asset->getId();
                    $type = Element\Service::getElementType($asset);

                    if (array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                        $newElement = Element\Service::getElementById($type, $idMapping[$type][$id]);
                        $metaAsset->setElement($newElement);
                    }
                }
            }
        }

        return $data;
    }

    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        parent::synchronizeWithMainDefinition($mainDefinition);

        if ($mainDefinition instanceof self) {
            $this->visibleFields = $mainDefinition->getVisibleFields();
            $this->columns = $mainDefinition->getColumns();
            $this->enableBatchEdit = $mainDefinition->getEnableBatchEdit();
            $this->allowMultipleAssignments = $mainDefinition->getAllowMultipleAssignments();
        }
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            /** @var DataObject\Data\ElementMetadata $elementMetadata */
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
                    'data' => $elementMetadata->getData(),
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
            $object = $params['object'] ?? null;
            foreach ($value as $elementMetadata) {
                $elementData = $elementMetadata['element'];

                $type = $elementData['type'];
                $id = $elementData['id'];
                $element = Element\Service::getElementById($type, $id);
                if ($element instanceof Asset) {
                    $columns = $elementMetadata['columns'];
                    $fieldname = $elementMetadata['fieldname'];
                    $data = $elementMetadata['data'];

                    $item = new DataObject\Data\ElementMetadata($fieldname, $columns, $element);
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

    protected function processDiffDataForEditMode(?array $originalData, ?array $data, ?DataObject\Concrete $object = null, array $params = []): ?array
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
                        'title' => $item['fullpath'] ?? '',
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
    public function setAllowMultipleAssignments(bool|int|null $allowMultipleAssignments): static
    {
        $this->allowMultipleAssignments = (bool) $allowMultipleAssignments;

        return $this;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\ElementMetadata::class . '[]';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\ElementMetadata::class . '[]';
    }

    public function getFieldType(): string
    {
        return 'advancedManyToManyAssetRelation';
    }
}
