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

namespace Pimcore\Model\DataObject\Concrete;

use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Concrete $model
 */
class Dao extends Model\DataObject\AbstractObject\Dao
{
    use Model\Element\Traits\ScheduledTasksDaoTrait;
    use Model\Element\Traits\VersionDaoTrait;

    protected ?Dao\InheritanceHelper $inheritanceHelper = null;

    public function init(): void
    {
        return;
    }

    protected function getInheritanceHelper(): Dao\InheritanceHelper
    {
        if (!$this->inheritanceHelper) {
            $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($this->model->getClassId());
        }

        return $this->inheritanceHelper;
    }

    /**
     * Get the data for the object from database for the given id
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative("SELECT objects.*, tree_locks.locked as locked FROM objects
            LEFT JOIN tree_locks ON objects.id = tree_locks.id AND tree_locks.type = 'object'
                WHERE objects.id = ?", [$id]);

        if ($data) {
            $data['published'] = (bool)$data['published'];
            $this->assignVariablesToModel($data);
            $this->getData();
        } else {
            throw new Model\Exception\NotFoundException('Object with the ID ' . $id . " doesn't exists");
        }
    }

    public function getRelationIds(string $fieldName): array
    {
        $relations = [];
        $allRelations = $this->db->fetchAllAssociative('SELECT * FROM object_relations_' . $this->model->getClassId() . " WHERE fieldname = ? AND src_id = ? AND ownertype = 'object' ORDER BY `index` ASC", [$fieldName, $this->model->getId()]);
        foreach ($allRelations as $relation) {
            $relations[] = $relation['dest_id'];
        }

        return $relations;
    }

    public function getRelationData(string $field, bool $forOwner, ?string $remoteClassId = null): array
    {
        $id = $this->model->getId();
        if ($remoteClassId) {
            $classId = $remoteClassId;
        } else {
            $classId = $this->model->getClassId();
        }

        $params = [$field, $id, $field, $id, $field, $id];

        $dest = 'dest_id';
        $src = 'src_id';
        if (!$forOwner) {
            $dest = 'src_id';
            $src = 'dest_id';
        }

        return $this->db->fetchAllAssociative('SELECT r.' . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, o.className as subtype, o.published as published, concat(o.path ,o.key) as `path` , r.index
            FROM objects o, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . ' = ?
            AND o.id = r.' . $dest . "
            AND r.type='object'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type,  a.type as subtype, "null" as published, concat(a.path,a.filename) as `path`, r.index
            FROM assets a, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . ' = ?
            AND a.id = r.' . $dest . "
            AND r.type='asset'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, d.type as subtype, d.published as published, concat(d.path,d.key) as `path`, r.index
            FROM documents d, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . ' = ?
            AND d.id = r.' . $dest . "
            AND r.type='document'

            ORDER BY `index` ASC", $params);
    }

    /**
     * Get all data-elements for all fields that are not lazy-loaded.
     */
    public function getData(): void
    {
        if (!$data = $this->db->fetchAssociative('SELECT * FROM object_store_' . $this->model->getClassId() . ' WHERE oo_id = ?', [$this->model->getId()])) {
            return;
        }

        $fieldDefinitions = $this->model->getClass()->getFieldDefinitions(['object' => $this->model]);
        foreach ($fieldDefinitions as $key => $value) {
            if ($value instanceof CustomResourcePersistingInterface) {
                if (!$value instanceof LazyLoadingSupportInterface || !$value->getLazyLoading()) {
                    // datafield has it's own loader
                    $params = [
                        'context' => [
                            'object' => $this->model,
                        ],
                        'owner' => $this->model,
                        'fieldname' => $key,
                    ];
                    $value = $value->load($this->model, $params);
                    if ($value === 0 || !empty($value)) {
                        $this->model->setValue($key, $value);
                    }
                }
            }
            if ($value instanceof ResourcePersistenceAwareInterface) {
                // if a datafield requires more than one field
                if (is_array($value->getColumnType())) {
                    $multidata = [];
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $multidata[$key . '__' . $fkey] = $data[$key . '__' . $fkey];
                    }
                    $this->model->setValue($key, $value->getDataFromResource($multidata));
                } else {
                    $this->model->setValue($key, $value->getDataFromResource($data[$key], $this->model, [
                        'owner' => $this->model,
                        'fieldname' => $key,
                    ]));
                }
            }
        }
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     */
    public function update(bool $isUpdate = null): void
    {
        parent::update($isUpdate);

        // get fields which shouldn't be updated
        $fieldDefinitions = $this->model->getClass()->getFieldDefinitions();
        $untouchable = [];

        foreach ($fieldDefinitions as $fieldName => $fd) {
            if ($fd instanceof LazyLoadingSupportInterface && $fd->getLazyLoading()) {
                if (!$this->model->isLazyKeyLoaded($fieldName) || $fd instanceof DataObject\ClassDefinition\Data\ReverseObjectRelation) {
                    //this is a relation subject to lazy loading - it has not been loaded
                    $untouchable[] = $fieldName;
                }
            }

            if (!DataObject::isDirtyDetectionDisabled() && $fd->supportsDirtyDetection()) {
                if ($this->model instanceof Model\Element\DirtyIndicatorInterface && !$this->model->isFieldDirty($fieldName)) {
                    if (!in_array($fieldName, $untouchable)) {
                        $untouchable[] = $fieldName;
                    }
                }
            }
        }

        $inheritedValues = DataObject::doGetInheritedValues();
        DataObject::setGetInheritedValues(false);

        $data = [];
        $data['oo_id'] = $this->model->getId();
        foreach ($fieldDefinitions as $fieldName => $fd) {
            $getter = 'get' . ucfirst($fieldName);

            if ($fd instanceof CustomResourcePersistingInterface
                && $fd instanceof DataObject\ClassDefinition\Data) {
                // for fieldtypes which have their own save algorithm eg. fieldcollections, relational data-types, ...
                $saveParams = ['isUntouchable' => in_array($fd->getName(), $untouchable),
                    'isUpdate' => $isUpdate,
                    'context' => [
                        'containerType' => 'object',
                    ],
                    'owner' => $this->model,
                    'fieldname' => $fieldName,
                ]
                ;
                if ($this->model instanceof Model\Element\DirtyIndicatorInterface) {
                    $saveParams['newParent'] = $this->model->isFieldDirty('parentId');
                }
                $fd->save($this->model, $saveParams);
            }
            if ($fd instanceof ResourcePersistenceAwareInterface) {
                // pimcore saves the values with getDataForResource

                $fieldDefinitionParams = [
                    'isUpdate' => $isUpdate,
                    'owner' => $this->model,
                    'fieldname' => $fieldName,
                ];
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $this->model, $fieldDefinitionParams);
                    if (is_array($insertDataArray)) {
                        $data = array_merge($data, $insertDataArray);
                        $this->model->set($fieldName, $fd->getDataFromResource($insertDataArray, $this->model, $fieldDefinitionParams));
                    }
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $this->model, $fieldDefinitionParams);
                    $data[$fieldName] = $insertData;
                    $this->model->set($fieldName, $fd->getDataFromResource($insertData, $this->model, $fieldDefinitionParams));
                }

                if ($this->model instanceof Model\Element\DirtyIndicatorInterface) {
                    $this->model->markFieldDirty($fieldName, false);
                }
            }
        }
        $tableName = 'object_store_' . $this->model->getClassId();
        if ($isUpdate) {
            Helper::upsert($this->db, $tableName, $data, $this->getPrimaryKey($tableName));
        } else {
            $this->db->insert('object_store_' . $this->model->getClassId(), Helper::quoteDataIdentifiers($this->db, $data));
        }

        // get data for query table
        $data = [];
        $this->getInheritanceHelper()->resetFieldsToCheck();
        $oldData = $this->db->fetchAssociative('SELECT * FROM object_query_' . $this->model->getClassId() . ' WHERE oo_id = ?', [$this->model->getId()]);

        $inheritanceEnabled = $this->model->getClass()->getAllowInherit();
        $parentData = null;
        if ($inheritanceEnabled) {
            // get the next suitable parent for inheritance
            $parentForInheritance = $this->model->getNextParentForInheritance();
            if ($parentForInheritance) {
                // we don't use the getter (built in functionality to get inherited values) because we need to avoid race conditions
                // we cannot DataObject::setGetInheritedValues(true); and then $this->model->$method();
                // so we select the data from the parent object using FOR UPDATE, which causes a lock on this row
                // so the data of the parent cannot be changed while this transaction is on progress
                $parentData = $this->db->fetchAssociative('SELECT * FROM object_query_' . $this->model->getClassId() . ' WHERE oo_id = ? FOR UPDATE', [$parentForInheritance->getId()]);
            }
        }

        foreach ($fieldDefinitions as $key => $fd) {
            if ($fd instanceof QueryResourcePersistenceAwareInterface
                && $fd instanceof DataObject\ClassDefinition\Data) {
                //exclude untouchables if value is not an array - this means data has not been loaded
                if (!in_array($key, $untouchable)) {
                    $method = 'get' . $key;
                    $fieldValue = $this->model->$method();
                    $insertData = $fd->getDataForQueryResource($fieldValue, $this->model,
                        [
                            'isUpdate' => $isUpdate,
                            'owner' => $this->model,
                            'fieldname' => $key,
                        ]);
                    $isEmpty = $fd->isEmpty($fieldValue);

                    if (is_array($insertData)) {
                        $columnNames = array_keys($insertData);
                        $data = array_merge($data, $insertData);
                    } else {
                        $columnNames = [$key];
                        $data[$key] = $insertData;
                    }

                    // if the current value is empty and we have data from the parent, we just use it
                    if ($isEmpty && $parentData) {
                        foreach ($columnNames as $columnName) {
                            if (array_key_exists($columnName, $parentData)) {
                                $data[$columnName] = $parentData[$columnName];
                                if (is_array($insertData)) {
                                    $insertData[$columnName] = $parentData[$columnName];
                                } else {
                                    $insertData = $parentData[$columnName];
                                }
                            }
                        }
                    }

                    if ($inheritanceEnabled && $fd->supportsInheritance()) {
                        //get changed fields for inheritance
                        if ($fd->isRelationType()) {
                            if (is_array($insertData)) {
                                $doInsert = false;
                                foreach ($insertData as $insertDataKey => $insertDataValue) {
                                    $oldDataValue = $oldData[$insertDataKey] ?? null;
                                    $parentDataValue = $parentData[$insertDataKey] ?? null;
                                    if ($isEmpty && $oldDataValue == $parentDataValue) {
                                        // do nothing, ... value is still empty and parent data is equal to current data in query table
                                    } elseif ($oldDataValue != $insertDataValue) {
                                        $doInsert = true;

                                        break;
                                    }
                                }

                                if ($doInsert) {
                                    $this->getInheritanceHelper()->addRelationToCheck($key, $fd, array_keys($insertData));
                                }
                            } else {
                                $oldDataValue = $oldData[$key] ?? null;
                                $parentDataValue = $parentData[$key] ?? null;
                                if ($isEmpty && $oldDataValue == $parentDataValue) {
                                    // do nothing, ... value is still empty and parent data is equal to current data in query table
                                } elseif ($oldDataValue != $insertData) {
                                    $this->getInheritanceHelper()->addRelationToCheck($key, $fd);
                                }
                            }
                        } else {
                            if (is_array($insertData)) {
                                foreach ($insertData as $insertDataKey => $insertDataValue) {
                                    $oldDataValue = $oldData[$insertDataKey] ?? null;
                                    $parentDataValue = $parentData[$insertDataKey] ?? null;
                                    if ($isEmpty && $oldDataValue == $parentDataValue) {
                                        // do nothing, ... value is still empty and parent data is equal to current data in query table
                                    } elseif ($oldDataValue != $insertDataValue) {
                                        $this->getInheritanceHelper()->addFieldToCheck($insertDataKey, $fd);
                                    }
                                }
                            } else {
                                $oldDataValue = $oldData[$key] ?? null;
                                $parentDataValue = $parentData[$key] ?? null;
                                if ($isEmpty && $oldDataValue == $parentDataValue) {
                                    // do nothing, ... value is still empty and parent data is equal to current data in query table
                                } elseif ($oldDataValue != $insertData) {
                                    // data changed, do check and update
                                    $this->getInheritanceHelper()->addFieldToCheck($key, $fd);
                                }
                            }
                        }
                    }
                } else {
                    Logger::debug('Excluding untouchable query value for object [ ' . $this->model->getId() . " ]  key [ $key ] because it has not been loaded");
                }
            }
        }
        $data['oo_id'] = $this->model->getId();

        $tableName = 'object_query_' . $this->model->getClassId();
        Helper::upsert($this->db, $tableName, $data, $this->getPrimaryKey($tableName));

        DataObject::setGetInheritedValues($inheritedValues);
    }

    public function saveChildData(): void
    {
        $this->getInheritanceHelper()->doUpdate($this->model->getId(), false, [
            'inheritanceRelationContext' => [
                'ownerType' => 'object',
            ],
        ]);
        $this->getInheritanceHelper()->resetFieldsToCheck();
    }

    /**
     * Save object to database
     */
    public function delete(): void
    {
        // delete fields which have their own delete algorithm
        foreach ($this->model->getClass()->getFieldDefinitions() as $fd) {
            if ($fd instanceof CustomResourcePersistingInterface) {
                $fd->delete($this->model);
            }
        }

        parent::delete();
    }
}
