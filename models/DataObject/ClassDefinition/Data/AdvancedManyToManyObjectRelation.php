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
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Db;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;

class AdvancedManyToManyObjectRelation extends ManyToManyObjectRelation
{
    use DataObject\Traits\ElementWithMetadataComparisonTrait;

    /**
     * @var
     */
    public $allowedClassId;

    /**
     * @var
     */
    public $visibleFields;

    /**
     * @var
     */
    public $columns;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'advancedManyToManyObjectRelation';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\ObjectMetadata[]';

    /**
     * @var bool
     */
    public $enableBatchEdit;

    /**
     * @var bool
     */
    public $allowMultipleAssignments;

    /**
     * @inheritdoc
     */
    public function prepareDataForPersistence($data, $object = null, $params = [])
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
                        'index' => $counter
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
     * @inheritdoc
     */
    public function loadData($data, $container = null, $params = [])
    {
        $list = [];

        if (is_array($data) && count($data) > 0) {
            $db = Db::get();
            $targets = [];

            foreach ($data as $relation) {
                $targetId = $relation['dest_id'];
                $targets[] = $targetId;
            }

            $existingTargets = $db->fetchCol(
                'SELECT o_id FROM objects WHERE o_id IN ('.implode(',', $targets).')'
            );

            foreach ($data as $key => $relation) {
                if ($relation['dest_id']) {
                    $source = DataObject::getById($relation['src_id']);
                    $destinationId = $relation['dest_id'];

                    if (!in_array($destinationId, $existingTargets)) {
                        // destination object does not exist anymore
                        continue;
                    }

                    if ($source instanceof DataObject\Concrete) {
                        /** @var $metaData DataObject\Data\ObjectMetadata */
                        $metaData = \Pimcore::getContainer()->get('pimcore.model.factory')
                            ->build(DataObject\Data\ObjectMetadata::class, [
                                'fieldname' => $this->getName(),
                                'columns' => $this->getColumnKeys(),
                                'object' => null
                            ]);

                        $metaData->setOwner($container, $this->getName());
                        $metaData->setObjectId($destinationId);

                        $ownertype = $relation['ownertype'] ? $relation['ownertype'] : '';
                        $ownername = $relation['ownername'] ? $relation['ownername'] : '';
                        $position = $relation['position'] ? $relation['position'] : '0';
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

                        $list[] = $metaData;
                    }
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $list;
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @throws \Exception
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        //return null when data is not set
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof DataObject\Concrete) {
                    $ids[] = $object->getId();
                }
            }

            return ',' . implode(',', $ids) . ',';
        } elseif (is_array($data) && count($data) === 0) {
            return '';
        } else {
            throw new \Exception('invalid data passed to getDataForQueryResource - must be array');
        }
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $return = [];

        $visibleFieldsArray = $this->getVisibleFields() ? explode(',', $this->getVisibleFields()) : [];

        $gridFields = (array)$visibleFieldsArray;

        // add data
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $mkey => $metaObject) {
                $index = $mkey + 1;
                $object = $metaObject->getObject();
                if ($object instanceof DataObject\Concrete) {
                    $columnData = DataObject\Service::gridObjectData($object, $gridFields, null, ['purpose' => 'editmode']);
                    foreach ($this->getColumns() as $c) {
                        $getter = 'get' . ucfirst($c['key']);
                        $columnData[$c['key']] = $metaObject->$getter();
                    }

                    $columnData['rowId'] = $columnData['id'] . self::RELATION_ID_SEPARATOR . $index . self::RELATION_ID_SEPARATOR . $columnData['type'];

                    $return[] = $columnData;
                }
            }
        }

        return $return;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        //if not set, return null
        if ($data === null or $data === false) {
            return null;
        }

        $relationsMetadata = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $relation) {
                $o = DataObject::getById($relation['id']);
                if ($o && $o->getClassName() == $this->getAllowedClassId()) {
                    $metaData = \Pimcore::getContainer()->get('pimcore.model.factory')
                        ->build('Pimcore\Model\DataObject\Data\ObjectMetadata', [
                            'fieldname' => $this->getName(),
                            'columns' => $this->getColumnKeys(),
                            'object' => $o
                        ]);
                    $metaData->setOwner($object, $this->getName());

                    foreach ($this->getColumns() as $c) {
                        $setter = 'set' . ucfirst($c['key']);
                        $value = $relation[$c['key']];

                        if ($c['type'] == 'multiselect') {
                            if ($value) {
                                if (is_array($value) && count($value)) {
                                    $value = implode(',', $value);
                                }
                            } else {
                                $value = null;
                            }
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

    /**
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param $data
     * @param null $object
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
     * @param array $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        $items = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
                $item = $o->getRealFullPath();

                if (sizeof($metaObject->getData())) {
                    $subItems = [];
                    foreach ($metaObject->getData() as $key => $value) {
                        if (!$value) {
                            continue;
                        }
                        $subItems[] = $key . ': ' . $value;
                    }

                    if (sizeof($subItems)) {
                        $item .= ' <br/><span class="preview-metadata">[' . implode(' | ', $subItems) . ']</span>';
                    }
                }

                $items[] = $item;
            }

            return implode('<br />', $items);
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        if (is_array($data)) {
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
                    throw new Element\ValidationException('Invalid object relation to object [' . $id . '] in field ' . $this->getName() . ' , tried to assign ' . $o->getId(), null, null);
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
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
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode(',', $importValue);

        $value = [];
        foreach ($values as $element) {
            if ($el = DataObject::getByPath($element)) {
                $metaObject = \Pimcore::getContainer()->get('pimcore.model.factory')
                    ->build('Pimcore\Model\DataObject\Data\ObjectMetadata', [
                        'fieldname' => $this->getName(),
                        'columns' => $this->getColumnKeys(),
                        'object' => $el
                    ]);
                $metaObject->setOwner($object, $this->getName());

                $value[] = $metaObject;
            }
        }

        return $value;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        if ($this->getLazyLoading()) {
            return $tags;
        }

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $object = $metaObject->getObject();
                if ($object instanceof Element\ElementInterface && !array_key_exists($object->getCacheTag(), $tags)) {
                    $tags = $object->getCacheTags($tags);
                }
            }
        }

        return $tags;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getObject();
                if ($o instanceof DataObject\AbstractObject) {
                    $dependencies['object_' . $o->getId()] = [
                        'id' => $o->getId(),
                        'type' => 'object'
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|mixed|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $items = [];
            foreach ($data as $metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $item = [];
                    $item['type'] = $eo->getType();
                    $item['id'] = $eo->getId();

                    foreach ($this->getColumns() as $c) {
                        $getter = 'get' . ucfirst($c['key']);
                        $item[$c['key']] = $metaObject->$getter();
                    }
                    $items[] = $item;
                }
            }

            return $items;
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param mixed $params
     * @param null $idMapper
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        $objects = [];
        if (empty($value)) {
            return null;
        } elseif (is_array($value)) {
            foreach ($value as $key => $item) {
                $item = (array)$item;
                $id = $item['id'];

                if ($idMapper) {
                    $id = $idMapper->getMappedId('object', $id);
                }

                $dest = null;
                if ($id) {
                    $dest = DataObject::getById($id);
                }

                if ($dest instanceof DataObject\AbstractObject) {
                    $metaObject = \Pimcore::getContainer()->get('pimcore.model.factory')
                        ->build('Pimcore\Model\DataObject\Data\ObjectMetadata', [
                            'fieldname' => $this->getName(),
                            'columns' => $this->getColumnKeys(),
                            'object' => $dest
                        ]);
                    $metaObject->setOwner($object, $this->getName());

                    foreach ($this->getColumns() as $c) {
                        $setter = 'set' . ucfirst($c['key']);
                        $metaObject->$setter($item[$c['key']]);
                    }

                    $objects[] = $metaObject;
                } else {
                    if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                        throw new \Exception('cannot get values from web service import - references unknown object with id [ ' . $item['id'] . ' ]');
                    } else {
                        $idMapper->recordMappingFailure('object', $object->getId(), 'object', $item['id']);
                    }
                }
            }
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }

        return $objects;
    }

    /**
     * @param Element\AbstractElement $object
     * @param array $params
     */
    public function save($object, $params = [])
    {
        if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $object instanceof DataObject\DirtyIndicatorInterface) {
            if ($object instanceof DataObject\Localizedfield) {
                if ($object->getObject() instanceof DataObject\DirtyIndicatorInterface) {
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

        $objectsMetadata = $this->getDataFromObjectParam($object, $params);

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

        $this->enrichRelation($object, $params, $classId, $relation);

        $position = (isset($relation['position']) && $relation['position']) ? $relation['position'] : '0';

        if ($params && $params['context'] && ($params['context']['containerType'] == 'fieldcollection' || $params['context']['containerType'] == 'objectbrick') && $params['context']['subContainerType'] == 'localizedfield') {
            $context = $params['context'];
            $index = $context['index'];
            $containerName = $context['fieldname'];

            if ($params['context']['containerType'] == 'fieldcollection') {
                $ownerName = '/' . $params['context']['containerType'] . '~' . $containerName . '/' . $index . '/%';
            } else {
                $ownerName = '/' . $params['context']['containerType'] . '~' . $containerName . '/%';
            }

            $sql = $db->quoteInto('o_id = ?', $objectId) . " AND ownertype = 'localizedfield' AND "
                . $db->quoteInto('ownername LIKE ?', $ownerName)
                . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                . ' AND ' . $db->quoteInto('position = ?', $position);
        } else {
            $sql = $db->quoteInto('o_id = ?', $objectId) . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                . ' AND ' . $db->quoteInto('position = ?', $position);

            if ($params && $params['context']) {
                if ($params['context']['fieldname']) {
                    $sql .= ' AND '.$db->quoteInto('ownername = ?', $params['context']['fieldname']);
                }

                if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $object instanceof DataObject\DirtyIndicatorInterface) {
                    if ($params['context']['containerType']) {
                        $sql .= ' AND '.$db->quoteInto('ownertype = ?', $params['context']['containerType']);
                    }
                }
            }
        }

        $db->deleteWhere($table, $sql);

        if (!empty($objectsMetadata)) {
            if ($object instanceof DataObject\Localizedfield || $object instanceof DataObject\Objectbrick\Data\AbstractData
                || $object instanceof DataObject\Fieldcollection\Data\AbstractData) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            $counter = 1;
            foreach ($objectsMetadata as $mkey => $meta) {
                $ownerName = isset($relation['ownername']) ? $relation['ownername'] : null;
                $ownerType = isset($relation['ownertype']) ? $relation['ownertype'] : null;
                $meta->save($objectConcrete, $ownerType, $ownerName, $position, $counter);

                $counter++;
            }
        }

        parent::save($object, $params);
    }

    /**
     * @param $object
     * @param array $params
     *
     * @return array|mixed|null
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof DataObject\Concrete) {
            $data = $object->getObjectVar($this->getName());
            if ($this->getLazyLoading() && !$object->isLazyKeyLoaded($this->getName())) {
                //$data = $this->getDataFromResource($object->getRelationData($this->getName(),true,null));
                $data = $this->load($object, ['force' => true]);

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
     * @param Element\AbstractElement $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
        $db = Db::get();

        if ($params && $params['context'] && ($params['context']['containerType'] == 'fieldcollection' || $params['context']['containerType'] == 'objectbrick') && $params['context']['subContainerType'] == 'localizedfield') {
            if ($params['context']['containerType'] == 'objectbrick') {
                throw new \Exception('deletemeta not implemented');
            }
            $context = $params['context'];
            $containerName = $context['fieldname'];

            if ($params['context']['containerType'] == 'fieldcollection') {
                $index = $context['index'];
                $db->deleteWhere(
                    'object_metadata_' . $object->getClassId(),
                    $db->quoteInto('o_id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . $db->quoteInto('ownername LIKE ?', '/' . $params['context']['containerType'] . '~' . $containerName . '/' . "$index . /%")
                    . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                );
            } elseif ($params['context']['containerType'] == 'objectbrick') {
                $index = $context['index'];
                $db->deleteWhere(
                    'object_metadata_' . $object->getClassId(),
                    $db->quoteInto('o_id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . $db->quoteInto('ownername LIKE ?', '/' . $params['context']['containerType'] . '~' . $containerName . '/%')
                    . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                );
            } else {
                $db->deleteWhere(
                    'object_metadata_' . $object->getClassId(),
                    $db->quoteInto('o_id = ?', $object->getId()) . " AND ownertype = 'localizedfield' AND "
                    . $db->quoteInto('ownername LIKE ?', '/' . $params['context']['containerType'] . '~' . $containerName . '/%')
                    . ' AND ' . $db->quoteInto('fieldname = ?', $this->getName())
                );
            }
        } else {
            $deleteConditions = [
                'o_id' => $object->getId(),
                'fieldname' => $this->getName()
            ];
            if ($params && $params['context']) {
                if ($params['context']['fieldname']) {
                    $deleteConditions['ownername'] = $params['context']['fieldname'];
                }

                if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $object instanceof DataObject\DirtyIndicatorInterface) {
                    if ($params['context']['containerType']) {
                        $deleteConditions['ownertype'] = $params['context']['containerType'];
                    }
                }
            }

            $db->delete('object_metadata_' . $object->getClassId(), $deleteConditions);
        }
    }

    /**
     * @param $allowedClassId
     *
     * @return $this
     */
    public function setAllowedClassId($allowedClassId)
    {
        $this->allowedClassId = $allowedClassId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAllowedClassId()
    {
        return $this->allowedClassId;
    }

    /**
     * @param $visibleFields
     *
     * @return $this
     */
    public function setVisibleFields($visibleFields)
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

    /**
     * @return mixed
     */
    public function getVisibleFields()
    {
        return $this->visibleFields;
    }

    /**
     * @param $columns
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
            $c['key'] = strtolower($c['key']);
            $this->columns[] = $c;
            $this->columnKeys[] = $c['key'];
        }

        return $this;
    }

    /**
     * @return mixed
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
     * @param $a
     * @param $b
     *
     * @return int
     */
    public function sort($a, $b)
    {
        if (is_array($a) && is_array($b)) {
            return $a['position'] - $b['position'];
        }

        return strcmp($a, $b);
    }

    /**
     * @param $class
     * @param array $params
     */
    public function classSaved($class, $params = [])
    {
        $temp = \Pimcore::getContainer()->get('pimcore.model.factory')
            ->build('Pimcore\Model\DataObject\Data\ObjectMetadata', [
                'fieldname' => null
            ]);

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
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

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

    /**
     * @param DataObject\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->allowedClassId = $masterDefinition->allowedClassId;
        $this->visibleFields = $masterDefinition->visibleFields;
        $this->columns = $masterDefinition->columns;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object DataObject\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $classId = $this->allowedClassId;

        if (!$classId) {
            return;
        }

        if (is_numeric($classId)) {
            $class = DataObject\ClassDefinition::getById($classId);
        } else {
            $class = DataObject\ClassDefinition::getByName($classId);
        }

        if (!$this->visibleFields) {
            return;
        }

        $this->visibleFieldDefinitions = [];

        $translator = \Pimcore::getContainer()->get('translator');

        $visibleFields = explode(',', $this->visibleFields);
        foreach ($visibleFields as $field) {
            $fd = $class->getFieldDefinition($field, $context);

            if (!$fd) {
                $fieldFound = false;
                if ($localizedfields = $class->getFieldDefinitions($context)['localizedfields']) {
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

                if ($fd instanceof DataObject\ClassDefinition\Data\Select || $fd instanceof DataObject\ClassDefinition\Data\MultiSelect) {
                    if ($fd->getOptionsProviderClass()) {
                        $this->visibleFieldDefinitions[$field]['optionsProviderClass'] = $fd->getOptionsProviderClass();
                    }

                    $this->visibleFieldDefinitions[$field]['options'] = $fd->getOptions();
                }
            }
        }
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            /** @var $elementMetadata DataObject\Data\ObjectMetadata */
            foreach ($value as $elementMetadata) {
                $element = $elementMetadata->getElement();

                $type = Element\Service::getType($element);
                $id = $element->getId();
                $result[] = [
                    'element' => [
                        'type' => $type,
                        'id' => $id
                    ],
                    'fieldname' => $elementMetadata->getFieldname(),
                    'columns' => $elementMetadata->getColumns(),
                    'data' => $elementMetadata->getData()];
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $elementMetadata) {
                $elementData = $elementMetadata['element'];

                $type = $elementData['type'];
                $id = $elementData['id'];
                $target = Element\Service::getElementById($type, $id);
                if ($target) {
                    $columns = $elementMetadata['columns'];
                    $fieldname = $elementMetadata['fieldname'];
                    $data = $elementMetadata['data'];

                    $item = new DataObject\Data\ObjectMetadata($fieldname, $columns, $target);
                    $item->setOwner($object, $this->getName());
                    $item->setData($data);
                    $result[] = $item;
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function processDiffDataForEditMode($originalData, $data, $object = null, $params = [])
    {
        if ($data) {
            $data = $data[0];

            $items = $data['data'];
            $newItems = [];
            if ($items) {
                $columns = array_merge(['id', 'fullpath'], $this->getColumnKeys());
                foreach ($items as $itemBeforeCleanup) {
                    $unique = $this->buildUniqueKeyForDiffEditor($itemBeforeCleanup);

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
                        'unique' => $unique
                    ];
                }
                $data['data'] = $newItems;
            }

            $data['value'] = [
                'type' => 'grid',
                'columnConfig' => [
                    'id' => [
                        'width' => 60
                    ],
                    'fullpath' => [
                        'flex' => 2
                    ]

                ],
                'html' => $this->getVersionPreview($originalData, $data, $object, $params)
            ];

            $newData = [];
            $newData[] = $data;

            return $newData;
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function getAllowMultipleAssignments()
    {
        return $this->allowMultipleAssignments;
    }

    /**
     * @param bool $allowMultipleAssignments
     */
    public function setAllowMultipleAssignments($allowMultipleAssignments)
    {
        $this->allowMultipleAssignments = $allowMultipleAssignments;
    }

    /**
     * @param $item
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
}
