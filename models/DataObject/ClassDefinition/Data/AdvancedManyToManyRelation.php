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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

class AdvancedManyToManyRelation extends ManyToManyRelation
{
    use DataObject\Traits\ElementWithMetadataComparisonTrait;
    use DataObject\ClassDefinition\Data\Extension\PositionSortTrait;

    /**
     * @internal
     *
     * @var array
     */
    public $columns;

    /**
     * @internal
     *
     * @var string[]
     */
    public $columnKeys;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'advancedManyToManyRelation';

    /**
     * Type for the generated phpdoc
     *
     * @internal
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\ElementMetadata[]';

    /**
     * @internal
     *
     * @var bool
     */
    public $optimizedAdminLoading = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $enableBatchEdit;

    /**
     * @internal
     *
     * @var bool
     */
    public $allowMultipleAssignments;

    /**
     * {@inheritdoc}
     */
    protected function prepareDataForPersistence($data, $object = null, $params = [])
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
        } elseif (is_array($data) and count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData(array $data, $object = null, $params = [])
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
                $identifier = $targetType === 'object' ? 'o_id' : 'id';

                $result = $db->fetchCol(
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
                        $metaData = \Pimcore::getContainer()->get('pimcore.model.factory')
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

                        $ownertype = $element['ownertype'] ? $element['ownertype'] : '';
                        $ownername = $element['ownername'] ? $element['ownername'] : '';
                        $position = $element['position'] ? $element['position'] : '0';
                        $index = $element['index'] ? $element['index'] : '0';

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
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
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

        throw new \Exception('invalid data passed to getDataForQueryResource - must be array');
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
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
                $identifier = $targetType == 'object' ? 'o_id' : 'id';
                if ($targetType == 'object') {
                    $pathCol = 'o_path';
                    $keyCol = 'o_key';
                    $typeCol = 'o_type';
                    $className = ', o_ClassName className';
                } else {
                    $pathCol = 'path';
                    if ($targetType == 'asset') {
                        $keyCol = 'filename';
                    } else {
                        $keyCol = '`key`';
                    }
                    $typeCol = 'type';
                    $className = '';
                }

                $result = $db->fetchAll(
                    'SELECT '
                    . $identifier . ' id, '
                    . $typeCol . ' type' . $className
                    . ' ,concat(' . $pathCol . ',' . $keyCol . ') fullpath FROM ' . $targetType . 's'
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
            if (empty($return)) {
                $return = false;
            }

            return $return;
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
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
                    $metaData = \Pimcore::getContainer()->get('pimcore.model.factory')
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

                        if ($columnConfig['type'] === 'multiselect') {
                            if (is_array($value) && count($value)) {
                                $value = implode(',', $value);
                            }
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

    /**
     * @param array|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        $items = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getElement();
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

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
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
                    throw new Element\ValidationException(sprintf('Invalid relation in field `%s` [type: %s]', $this->getName(), $this->getFieldtype()), null, null);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getType($eo) . ':' . $eo->getRealFullPath();
                }
            }

            return implode(',', $paths);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function save($object, $params = [])
    {
        if (!DataObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
            if ($object instanceof DataObject\Localizedfield) {
                if ($object->getObject() instanceof Element\DirtyIndicatorInterface) {
                    if (!$object->hasDirtyFields()) {
                        return;
                    }
                }
            } else {
                if ($this->supportsDirtyDetection()) {
                    if (!$object->isFieldDirty($this->getName())) {
                        return;
                    }
                }
            }
        }

        $multihrefMetadata = $this->getDataFromObjectParam($object, $params);

        $classId = null;
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

        $this->enrichDataRow($object, $params, $classId, $relation);

        $position = (isset($relation['position']) && $relation['position']) ? $relation['position'] : '0';
        $context = $params['context'] ?? null;

        if (isset($context['containerType'], $context['subContainerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick') && $context['subContainerType'] === 'localizedfield') {
            $index = $context['index'] ?? null;
            $containerName = $context['fieldname'];

            if ($context['containerType'] === 'fieldcollection') {
                $ownerName = '/' . $context['containerType'] . '~' . $containerName . '/' . $index . '/%';
            } else {
                $ownerName = '/' . $context['containerType'] . '~' . $containerName . '/%';
            }

            $sql = $db->quoteInto('o_id = ?', $objectId) . " AND ownertype = 'localizedfield' AND "
                . $db->quoteInto('ownername LIKE ?', $ownerName)
                . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                . ' AND ' . $db->quoteInto('position = ?', $position);
        } else {
            $sql = $db->quoteInto('o_id = ?', $objectId) . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                . ' AND ' . $db->quoteInto('position = ?', $position);

            if ($context) {
                if (!empty($context['fieldname'])) {
                    $sql .= ' AND ' . $db->quoteInto('ownername = ?', $context['fieldname']);
                }

                if (!DataObject::isDirtyDetectionDisabled() && $object instanceof Element\DirtyIndicatorInterface) {
                    if ($context['containerType']) {
                        $sql .= ' AND ' . $db->quoteInto('ownertype = ?', $context['containerType']);
                    }
                }
            }
        }

        $db->deleteWhere($table, $sql);

        if (!empty($multihrefMetadata)) {
            if ($object instanceof DataObject\Localizedfield || $object instanceof DataObject\Objectbrick\Data\AbstractData
                || $object instanceof DataObject\Fieldcollection\Data\AbstractData
            ) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            $counter = 1;
            foreach ($multihrefMetadata as $mkey => $meta) {
                $ownerName = isset($relation['ownername']) ? $relation['ownername'] : null;
                $ownerType = isset($relation['ownertype']) ? $relation['ownertype'] : null;
                $meta->save($objectConcrete, $ownerType, $ownerName, $position, $counter);
                $counter++;
            }
        }

        parent::save($object, $params);
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return array
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof DataObject\Concrete) {
            $data = $object->getObjectVar($this->getName());
            if (!$object->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($object);

                $object->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($object);
            }
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($object instanceof DataObject\Fieldcollection\Data\AbstractData) {
            parent::loadLazyFieldcollectionField($object);
            $data = $object->getObjectVar($this->getName());
        } elseif ($object instanceof DataObject\Objectbrick\Data\AbstractData) {
            parent::loadLazyBrickField($object);
            $data = $object->getObjectVar($this->getName());
        }

        // note, in case of advanced many to many relations we don't want to force the loading of the element
        // instead, ask the database directly
        return Element\Service::filterUnpublishedAdvancedElements($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object, $params = [])
    {
        $db = Db::get();
        $context = $params['context'] ?? null;

        if (isset($context['containerType'], $context['subContainerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick') && $context['subContainerType'] === 'localizedfield') {
            $containerName = $context['fieldname'] ?? null;

            if ($context['containerType'] === 'objectbrick') {
                $db->deleteWhere(
                    'object_metadata_' . $object->getClassId(),
                    $db->quoteInto('o_id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . $db->quoteInto('ownername LIKE ?', '/' . $context['containerType'] . '~' . $containerName . '/%')
                    . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                );
            } else {
                $index = $context['index'];

                $db->deleteWhere(
                    'object_metadata_' . $object->getClassId(),
                    $db->quoteInto('o_id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . $db->quoteInto('ownername LIKE ?', '/' . $context['containerType'] . '~' . $containerName . '/' . $index . '/%')
                    . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                );
            }
        } else {
            $deleteCondition = [
                'o_id' => $object->getId(),
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
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns($columns)
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

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getColumnKeys()
    {
        $this->columnKeys = [];
        foreach ($this->columns as $c) {
            $this->columnKeys[] = $c['key'];
        }

        return $this->columnKeys;
    }

    /**
     * @param DataObject\ClassDefinition $class
     */
    public function classSaved($class)
    {
        /** @var DataObject\Data\ElementMetadata $temp */
        $temp = \Pimcore::getContainer()->get('pimcore.model.factory')
            ->build(
                'Pimcore\Model\DataObject\Data\ElementMetadata',
                [
                    'fieldname' => null,
                ]
            );

        $temp->getDao()->createOrUpdateTable($class);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     *
     * @return DataObject\Data\ElementMetadata[]
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

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
     * @param DataObject\ClassDefinition\Data\AdvancedManyToManyRelation $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        parent::synchronizeWithMasterDefinition($masterDefinition);
        $this->columns = $masterDefinition->columns;
    }

    /**
     * Override point for Enriching the layout definition before the layout is returned to the admin interface.
     *
     * @param DataObject\Concrete $object
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        // nothing to do
    }

    /**
     * @param DataObject\Data\ElementMetadata[]|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
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

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            /** @var DataObject\Data\ElementMetadata $elementMetadata */
            foreach ($value as $elementMetadata) {
                $element = $elementMetadata->getElement();

                $type = Element\Service::getType($element);
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

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
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
    }

    /**
     * {@inheritdoc}
     */
    protected function processDiffDataForEditMode($originalData, $data, $object = null, $params = [])
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

    /**
     * {@inheritdoc}
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $data = parent::getDiffDataForEditMode($data, $object, $params);

        return $data;
    }

    /** See parent class.
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
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

        return;
    }

    /**
     * @internal
     *
     * @param DataObject\Data\ElementMetadata $item
     *
     * @return string
     */
    protected function buildUniqueKeyForAppending($item)
    {
        $element = $item->getElement();
        $elementType = Element\Service::getElementType($element);
        $id = $element->getId();

        return $elementType . $id;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptimizedAdminLoading(): bool
    {
        return (bool) $this->optimizedAdminLoading;
    }

    /**
     * @param bool $optimizedAdminLoading
     */
    public function setOptimizedAdminLoading($optimizedAdminLoading)
    {
        $this->optimizedAdminLoading = $optimizedAdminLoading;
    }

    /**
     * @return bool
     */
    public function getAllowMultipleAssignments()
    {
        return $this->allowMultipleAssignments;
    }

    /**
     * @param int|bool|null $allowMultipleAssignments
     *
     * @return $this
     */
    public function setAllowMultipleAssignments($allowMultipleAssignments)
    {
        $this->allowMultipleAssignments = $allowMultipleAssignments;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnableBatchEdit()
    {
        return $this->enableBatchEdit;
    }

    /**
     * @param bool $enableBatchEdit
     */
    public function setEnableBatchEdit($enableBatchEdit)
    {
        $this->enableBatchEdit = $enableBatchEdit;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\Pimcore\\Model\\DataObject\\Data\\ElementMetadata[]';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\Pimcore\\Model\\DataObject\\Data\\ElementMetadata[]';
    }
}
