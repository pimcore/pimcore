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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

class AdvancedManyToManyRelation extends ManyToManyRelation implements IdRewriterInterface, PreGetDataInterface, ClassSavedInterface
{
    use DataObject\Traits\ElementWithMetadataComparisonTrait;
    use DataObject\ClassDefinition\Data\Extension\PositionSortTrait;

    /**
     * @internal
     *
     */
    public array $columns;

    /**
     * @internal
     *
     * @var string[]
     */
    public array $columnKeys;

    /**
     * Type for the generated phpdoc
     *
     * @internal
     *
     */
    public string $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\ElementMetadata[]';

    /**
     * @internal
     */
    public bool $optimizedAdminLoading = false;

    /**
     * @internal
     */
    public bool $enableBatchEdit = false;

    /**
     * @internal
     */
    public bool $allowMultipleAssignments = false;

    protected function prepareDataForPersistence(array|Element\ElementInterface $data, Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): mixed
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface) {
                    $return[] = [
                        'dest_id' => $element->getId(),
                        'type' => Element\Service::getElementType($element),
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
            $targets = [];
            $existingTargets = [];

            foreach ($data as $element) {
                $targetType = $element['type'];
                $targetId = $element['dest_id'];
                $targets[$targetType][] = $targetId;
            }

            $db = Db::get();
            foreach ($targets as $targetType => $targetIds) {
                $identifier = 'id';

                $result = $db->fetchFirstColumn(
                    'SELECT ' . $identifier . ' FROM ' . $targetType . 's'
                    . ' WHERE ' . $identifier . ' IN (' . implode(',', $targetIds) . ')'
                );
                $existingTargets[$targetType] = $result;
            }

            foreach ($data as $element) {
                $source = DataObject::getById($element['src_id']);

                if ($element['type'] && $element['dest_id']) {
                    $destinationType = $element['type'];
                    $destinationId = $element['dest_id'];

                    if (!in_array($destinationId, $existingTargets[$destinationType])) {
                        // destination object does not exist anymore
                        $list['dirty'] = true;

                        continue;
                    }

                    if ($source instanceof DataObject\Concrete) {
                        /** @var DataObject\Data\ElementMetadata $metaData */
                        $metaData = Pimcore::getContainer()->get('pimcore.model.factory')
                            ->build(
                                'Pimcore\Model\DataObject\Data\ElementMetadata',
                                [
                                    'fieldname' => $this->getName(),
                                    'columns' => $this->getColumnKeys(),
                                    'element' => null,
                                ]
                            );

                        $metaData->_setOwner($object);
                        $metaData->_setOwnerFieldname($this->getName());

                        $metaData->setElementTypeAndId($element['type'], $element['dest_id']);

                        $ownertype = $element['ownertype'] ?: '';
                        $ownername = $element['ownername'] ?: '';
                        $position = $element['position'] ?: '0';
                        $index = $element['index'] ?: '0';

                        $metaData->load(
                            $source,
                            $element['dest_id'],
                            $this->getName(),
                            $ownertype,
                            $ownername,
                            $position,
                            $index,
                            $destinationType
                        );
                        $objects[] = $metaData;

                        $list['data'][] = $metaData;
                    }
                }
            }
        }

        //must return array - otherwise this means data is not loaded
        return $list;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $d = [];

        if (is_array($data)) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $d[] = $elementType . '|' . $element->getId();
                }
            }

            return ',' . implode(',', $d) . ',';
        }

        throw new Exception('invalid data passed to getDataForQueryResource - must be array');
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $itemData = null;

            $targets = [];
            $existingTargets = [];

            /** @var DataObject\Data\ElementMetadata $metaObject */
            foreach ($data as $metaObject) {
                $targetType = $metaObject->getElementType();
                $targetId = $metaObject->getElementId();
                $targets[$targetType][] = $targetId;
            }

            $db = Db::get();

            foreach ($targets as $targetType => $targetIds) {
                $identifier = 'id';
                $pathCol = 'path';
                $typeCol = 'type';

                $keyCol = 'key';
                $className = '';
                if ($targetType == 'object') {
                    $className = ', className';
                } elseif ($targetType == 'asset') {
                    $keyCol = 'filename';
                }

                $result = $db->fetchAllAssociative(
                    'SELECT '
                    . $identifier . ' id, '
                    . $typeCol . ' type' . $className
                    . ' ,concat(' . $db->quoteIdentifier($pathCol) . ',' . $db->quoteIdentifier($keyCol) . ') fullpath FROM ' . $targetType . 's'
                    . ' WHERE ' . $identifier . ' IN (' . implode(',', $targetIds) . ')'
                );

                $resultMap = [];

                foreach ($result as $resultItem) {
                    $resultMap[$resultItem['id']] = $resultItem;
                }

                $existingTargets[$targetType] = $resultMap;
            }

            /** @var DataObject\Data\ElementMetadata $metaObject */
            foreach ($data as $key => $metaObject) {
                $targetType = $metaObject->getElementType();
                $targetId = $metaObject->getElementId();
                $index = $key + 1;

                if (!isset($existingTargets[$targetType]) || !isset($existingTargets[$targetType][$targetId])) {
                    Logger::error('element ' . $targetType . ' ' . $targetId . ' does not exist anymore');

                    continue;
                }

                $elementData = $existingTargets[$targetType][$targetId];
                $type = $elementData['type'];
                $id = $elementData['id'];
                $fullpath = $elementData['fullpath'];

                if ($targetType == DataObject::OBJECT_TYPE_OBJECT) {
                    if ($type == DataObject::OBJECT_TYPE_FOLDER) {
                        $itemData = ['id' => $id, 'path' => $fullpath, 'type' => DataObject::OBJECT_TYPE_OBJECT, 'subtype' => DataObject::OBJECT_TYPE_FOLDER];
                    } else {
                        $className = $elementData['className'];
                        $itemData = ['id' => $id, 'path' => $fullpath, 'type' => DataObject::OBJECT_TYPE_OBJECT, 'subtype' => $className];
                        /** @var DataObject\Concrete $obj */
                        $obj = Element\Service::getElementById('object', $id);
                        $itemData['published'] = $obj->getPublished();
                    }
                } elseif ($targetType == 'asset') {
                    $itemData = ['id' => $id, 'path' => $fullpath, 'type' => 'asset', 'subtype' => $type];
                } elseif ($targetType == 'document') {
                    $itemData = ['id' => $id, 'path' => $fullpath, 'type' => 'document', 'subtype' => $type];
                    $document = Element\Service::getElementById('document', $id);
                    if (method_exists($document, 'getPublished')) {
                        $itemData['published'] = $document->getPublished();
                    }
                }

                if (!$itemData) {
                    continue;
                }

                foreach ($this->getColumns() as $c) {
                    $getter = 'get' . ucfirst($c['key']);

                    try {
                        $itemData[$c['key']] = $metaObject->$getter();
                    } catch (Exception $e) {
                        Logger::debug('Meta column '.$c['key'].' does not exist');
                    }
                }

                $itemData['rowId'] = $itemData['id'] . self::RELATION_ID_SEPARATOR . $index . self::RELATION_ID_SEPARATOR . $itemData['type'];

                $return[] = $itemData;
            }

            return $return;
        }

        return null;
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

        $multihrefMetadata = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $e = null;
                if ($element['type'] === 'object') {
                    $e = DataObject::getById($element['id']);
                } elseif ($element['type'] === 'asset') {
                    $e = Asset::getById($element['id']);
                } elseif ($element['type'] === 'document') {
                    $e = Document::getById($element['id']);
                }

                if ($e instanceof Element\ElementInterface) {
                    /** @var DataObject\Data\ElementMetadata $metaData */
                    $metaData = Pimcore::getContainer()->get('pimcore.model.factory')
                        ->build(
                            'Pimcore\Model\DataObject\Data\ElementMetadata',
                            [
                                'fieldname' => $this->getName(),
                                'columns' => $this->getColumnKeys(),
                                'element' => $e,
                            ]
                        );

                    $metaData->_setOwner($object);
                    $metaData->_setOwnerFieldname($this->getName());

                    foreach ($this->getColumns() as $columnConfig) {
                        $key = $columnConfig['key'];
                        $setter = 'set' . ucfirst($key);
                        $value = $element[$key] ?? null;

                        if ($columnConfig['type'] === 'multiselect' && is_array($value)) {
                            $value = implode(',', $value);
                        }

                        $metaData->$setter($value);
                    }
                    $multihrefMetadata[] = $metaData;

                    $elements[] = $e;
                }
            }
        }

        //must return array if data shall be set
        return $multihrefMetadata;
    }

    public function getDataForGrid(?array $data, Concrete $object = null, array $params = []): ?array
    {
        $ret = $this->getDataForEditmode($data, $object, $params);

        return is_array($ret) ? $ret : null;
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
                if (!($metaObject instanceof DataObject\Data\ElementMetadata)) {
                    continue;
                }

                $o = $metaObject->getElement();
                if (!$o) {
                    continue;
                }
                $item = Element\Service::getElementType($o) . ' ' . $o->getRealFullPath();

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
            foreach ($data as $elementMetadata) {
                if (!($elementMetadata instanceof DataObject\Data\ElementMetadata)) {
                    throw new Element\ValidationException('Expected DataObject\\Data\\ElementMetadata');
                }

                $d = $elementMetadata->getElement();

                if ($d instanceof Document) {
                    $allow = $this->allowDocumentRelation($d);
                } elseif ($d instanceof Asset) {
                    $allow = $this->allowAssetRelation($d);
                } elseif ($d instanceof DataObject\AbstractObject) {
                    $allow = $this->allowObjectRelation($d);
                } elseif (empty($d)) {
                    $allow = true;
                } else {
                    $allow = false;
                }
                if (!$allow) {
                    throw new Element\ValidationException(sprintf('Invalid relation in field `%s` [type: %s]', $this->getName(), $this->getFieldtype()));
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
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getElementType($eo) . ':' . $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    public function save(
        Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object,
        array $params = []): void
    {
        if ($this->skipSaveCheck($object, $params)) {
            return;
        }

        $multihrefMetadata = $this->getDataFromObjectParam($object, $params);

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

        $position = isset($relation['position']) ? (string)$relation['position'] : '0';
        $context = $params['context'] ?? null;

        if (isset($context['containerType'], $context['subContainerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick') && $context['subContainerType'] === 'localizedfield') {
            $index = $context['index'] ?? null;
            $containerName = $context['fieldname'];

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

                if (!DataObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
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

        if (!empty($multihrefMetadata)) {
            if ($object instanceof DataObject\Localizedfield
                || $object instanceof DataObject\Objectbrick\Data\AbstractData
                || $object instanceof DataObject\Fieldcollection\Data\AbstractData
            ) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            $counter = 1;
            foreach ($multihrefMetadata as $mkey => $meta) {
                $ownerName = isset($relation['ownername']) ? $relation['ownername'] : '';
                $ownerType = isset($relation['ownertype']) ? $relation['ownertype'] : '';
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

                if (!DataObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
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

    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        /** @var DataObject\Data\ElementMetadata $temp */
        $temp = Pimcore::getContainer()->get('pimcore.model.factory')
            ->build(
                'Pimcore\Model\DataObject\Data\ElementMetadata',
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
            foreach ($data as &$metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $id = $eo->getId();
                    $type = Element\Service::getElementType($eo);

                    if (array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                        $newElement = Element\Service::getElementById($type, $idMapping[$type][$id]);
                        $metaObject->setElement($newElement);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data\AdvancedManyToManyRelation $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        parent::synchronizeWithMainDefinition($mainDefinition);
        $this->columns = $mainDefinition->columns;
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaElement) {
                $e = $metaElement->getElement();
                if ($e instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($e);
                    $dependencies[$elementType . '_' . $e->getId()] = [
                        'id' => $e->getId(),
                        'type' => $elementType,
                    ];
                }
            }
        }

        return $dependencies;
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
                if ($element) {
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

    protected function processDiffDataForEditMode(?array $originalData, ?array $data, Concrete $object = null, array $params = []): ?array
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                foreach ($items as $item) {
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
        $data = parent::getDiffDataForEditMode($data, $object, $params);

        return $data;
    }

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

    public function isOptimizedAdminLoading(): bool
    {
        return $this->optimizedAdminLoading;
    }

    public function setOptimizedAdminLoading(bool $optimizedAdminLoading): void
    {
        $this->optimizedAdminLoading = $optimizedAdminLoading;
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

    public function getEnableBatchEdit(): bool
    {
        return $this->enableBatchEdit;
    }

    public function setEnableBatchEdit(bool $enableBatchEdit): void
    {
        $this->enableBatchEdit = $enableBatchEdit;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\'.DataObject\Data\ElementMetadata::class.'[]';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\'.DataObject\Data\ElementMetadata::class.'[]';
    }

    public function getFieldType(): string
    {
        return 'advancedManyToManyRelation';
    }
}
