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

namespace Pimcore\Model\DataObject\Objectbrick\Data;

use Exception;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Objectbrick\Data\AbstractData $model
 */
class Dao extends Model\Dao\AbstractDao
{
    protected ?DataObject\Concrete\Dao\InheritanceHelper $inheritanceHelper = null;

    /**
     *
     * @throws Exception
     */
    public function save(DataObject\Concrete $object, array $params = []): void
    {
        // HACK: set the pimcore admin mode to false to get the inherited values from parent if this source one is empty
        $inheritedValues = DataObject::doGetInheritedValues();

        $storetable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $querytable = $this->model->getDefinition()->getTableName($object->getClass(), true);

        $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($object->getClassId(), 'id', $storetable, $querytable, null, 'id');

        DataObject::setGetInheritedValues(false);

        $fieldDefinitions = $this->model->getDefinition()->getFieldDefinitions();

        $data = [];
        $data['id'] = $object->getId();
        $data['fieldname'] = $this->model->getFieldname();

        $dirtyRelations = [];
        $db = Db::get();

        if (($params['isUpdate'] ?? false) === false && $this->model->getObject()->getClass()->getAllowInherit()) {
            // if this is a fresh object, then we don't need the check
            $isBrickUpdate = false; // used to indicate whether we want to consider the default value
        } else {
            // or brick has been added
            $existsResult = $this->db->fetchOne(
                'SELECT id FROM ' . $storetable . ' WHERE id = ? LIMIT 1',
                [$object->getId()]
            );

            $isBrickUpdate = (bool)$existsResult; // used to indicate whether we want to consider the default value
        }

        foreach ($fieldDefinitions as $fieldName => $fd) {
            $getter = 'get' . ucfirst($fd->getName());

            if ($fd instanceof CustomResourcePersistingInterface) {
                // for fieldtypes which have their own save algorithm eg. relational data-types, ...
                $fd->save($this->model,
                    array_merge($params, [
                        'context' => [
                            'containerType' => 'objectbrick',
                            'containerKey' => $this->model->getType(),
                            'fieldname' => $this->model->getFieldname(),
                        ],
                        'isUpdate' => $isBrickUpdate,
                        'owner' => $this->model,
                        'fieldname' => $fieldName,
                    ]));
            }

            if ($fd instanceof ResourcePersistenceAwareInterface) {
                $fieldDefinitionParams = [
                    'owner' => $this->model, //\Pimcore\Model\DataObject\Objectbrick\Data\Dao
                    'fieldname' => $fieldName,
                    'isUpdate' => $isBrickUpdate,
                    'context' => [
                        'containerType' => 'objectbrick',
                        'containerKey' => $this->model->getType(),
                        'fieldname' => $this->model->getFieldname(),
                    ],
                ];
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object, $fieldDefinitionParams);
                    $data = array_merge($data, $insertDataArray);
                    $this->model->set($fieldName, $fd->getDataFromResource($insertDataArray, $object, $fieldDefinitionParams));
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $object, $fieldDefinitionParams);
                    $data[$fieldName] = $insertData;
                    $this->model->set($fieldName, $fd->getDataFromResource($insertData, $object, $fieldDefinitionParams));
                }

                if ($this->model instanceof Model\Element\DirtyIndicatorInterface) {
                    $this->model->markFieldDirty($fieldName, false);
                }
            }
        }

        if ($isBrickUpdate) {
            $this->db->update($storetable, Helper::quoteDataIdentifiers($this->db, $data), ['id'=> $object->getId()]);
        } else {
            $this->db->insert($storetable, Helper::quoteDataIdentifiers($this->db, $data));
        }

        // get data for query table
        // $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        // this is special because we have to call each getter to get the inherited values from a possible parent object

        $data = [];
        $data['id'] = $object->getId();
        $data['fieldname'] = $this->model->getFieldname();

        $this->inheritanceHelper->resetFieldsToCheck();
        $oldData = $this->db->fetchAssociative('SELECT * FROM ' . $querytable . ' WHERE id = ?', [$object->getId()]);

        $inheritanceEnabled = $object->getClass()->getAllowInherit();
        $parentData = null;
        if ($inheritanceEnabled) {
            // get the next suitable parent for inheritance
            $parentForInheritance = $object->getNextParentForInheritance();
            if ($parentForInheritance) {
                // we don't use the getter (built in functionality to get inherited values) because we need to avoid race conditions
                // we cannot DataObject::setGetInheritedValues(true); and then $this->model->$method();
                // so we select the data from the parent object using FOR UPDATE, which causes a lock on this row
                // so the data of the parent cannot be changed while this transaction is on progress
                $parentData = $this->db->fetchAssociative('SELECT * FROM ' . $querytable . ' WHERE id = ? FOR UPDATE', [$parentForInheritance->getId()]);
            }
        }

        foreach ($fieldDefinitions as $key => $fd) {
            if ($fd instanceof QueryResourcePersistenceAwareInterface
                && $fd instanceof DataObject\ClassDefinition\Data) {
                $method = 'get' . $key;
                $fieldValue = $this->model->$method();
                $insertData = $fd->getDataForQueryResource($fieldValue, $object);
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

                if ($inheritanceEnabled) {
                    //get changed fields for inheritance
                    if ($fd instanceof DataObject\ClassDefinition\Data\CalculatedValue) {
                        // nothing to do, see https://github.com/pimcore/pimcore/issues/727
                        continue;
                    } elseif ($fd->isRelationType()) {
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
                                $this->inheritanceHelper->addRelationToCheck($key, $fd, array_keys($insertData));
                            }
                        } else {
                            $oldDataValue = $oldData[$key] ?? null;
                            $parentDataValue = $parentData[$key] ?? null;
                            if ($isEmpty && $oldDataValue == $parentDataValue) {
                                // do nothing, ... value is still empty and parent data is equal to current data in query table
                            } elseif ($oldDataValue != $insertData) {
                                $this->inheritanceHelper->addRelationToCheck($key, $fd);
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
                                    $this->inheritanceHelper->addFieldToCheck($insertDataKey, $fd);
                                }
                            }
                        } else {
                            $oldDataValue = $oldData[$key] ?? null;
                            $parentDataValue = $parentData[$key] ?? null;
                            if ($isEmpty && $oldDataValue == $parentDataValue) {
                                // do nothing, ... value is still empty and parent data is equal to current data in query table
                            } elseif ($oldDataValue != $insertData) {
                                // data changed, do check and update
                                $this->inheritanceHelper->addFieldToCheck($key, $fd);
                            }
                        }
                    }
                }
            }
        }

        Helper::upsert($this->db, $querytable, $data, $this->getPrimaryKey($querytable));

        if ($inheritanceEnabled) {
            $this->inheritanceHelper->doUpdate($object->getId(), true,
                ['inheritanceRelationContext' => [
                    'ownertype' => 'objectbrick',
                ]]);
        }
        $this->inheritanceHelper->resetFieldsToCheck();

        // HACK: see a few lines above!
        DataObject::setGetInheritedValues($inheritedValues);
    }

    public function delete(DataObject\Concrete $object): void
    {
        // update data for store table
        $storeTable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $this->db->delete($storeTable, ['id' => $object->getId()]);

        // update data for query table
        $queryTable = $this->model->getDefinition()->getTableName($object->getClass(), true);

        $oldData = $this->db->fetchAssociative('SELECT * FROM ' . $queryTable . ' WHERE id = ?', [$object->getId()]);
        $this->db->delete($queryTable, ['id' => $object->getId()]);

        //update data for relations table
        $this->db->delete('object_relations_' . $object->getClassId(), [
            'src_id' => $object->getId(),
            'ownertype' => 'objectbrick',
            'ownername' => $this->model->getFieldname(),
            'position' => $this->model->getType(),
        ]);

        $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($object->getClassId(), 'id', $storeTable, $queryTable);
        $this->inheritanceHelper->resetFieldsToCheck();

        $objectVars = $this->model->getObjectVars();

        foreach ($objectVars as $key => $value) {
            $fd = $this->model->getDefinition()->getFieldDefinition($key);

            if ($fd) {
                if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $localizedFieldDao = new DataObject\Localizedfield\Dao();
                    $localizedFieldDao->configure();

                    $fakeModel = new DataObject\Localizedfield();
                    $fakeModel->setObject($object);
                    $fakeModel->setContext([
                        'containerType' => 'objectbrick',
                        'containerKey' => $this->model->getType(),
                        'fieldname' => $this->model->getFieldname(),
                    ]);
                    $localizedFieldDao->setModel($fakeModel);
                    $localizedFieldDao->delete();

                    continue;
                }

                if ($fd instanceof QueryResourcePersistenceAwareInterface) {
                    //exclude untouchables if value is not an array - this means data has not been loaded
                    //get changed fields for inheritance
                    if ($fd instanceof DataObject\ClassDefinition\Data\CalculatedValue) {
                        continue;
                    }

                    if (!empty($oldData[$key])) {
                        if ($fd->isRelationType()) {
                            $this->inheritanceHelper->addRelationToCheck($key, $fd);
                        } else {
                            $this->inheritanceHelper->addFieldToCheck($key, $fd);
                        }
                    }

                    if ($fd instanceof CustomResourcePersistingInterface) {
                        $fd->delete($object);
                    }
                }
            }
        }

        $this->inheritanceHelper->doDelete($object->getId());

        $this->inheritanceHelper->resetFieldsToCheck();
    }

    public function getRelationData(string $field, bool $forOwner, ?string $remoteClassId = null): array
    {
        $id = $this->model->getObject()->getId();
        if ($remoteClassId) {
            $classId = $remoteClassId;
        } else {
            $classId = $this->model->getObject()->getClassId();
        }

        $params = [$field, $id, $field, $id, $field, $id];

        $dest = 'dest_id';
        $src = 'src_id';
        if (!$forOwner) {
            $dest = 'src_id';
            $src = 'dest_id';
        }

        return $this->db->fetchAllAssociative('SELECT r.' . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, o.className as subtype, concat(o.path ,o.key) as `path` , r.index, o.published
            FROM objects o, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . ' = ?
            AND o.id = r.' . $dest . "
            AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')
            AND r.type='object'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type,  a.type as subtype,  concat(a.path,a.filename) as `path`, r.index, "null" as published
            FROM assets a, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . ' = ?
            AND a.id = r.' . $dest . "
            AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')
            AND r.type='asset'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, d.type as subtype, concat(d.path,d.key) as `path`, r.index, d.published as published
            FROM documents d, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . ' = ?
            AND d.id = r.' . $dest . "
            AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')
            AND r.type='document'
            ORDER BY `index` ASC", $params);
    }
}
