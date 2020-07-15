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
 * @package    DataObject\Objectbrick
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Objectbrick\Data;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @property \Pimcore\Model\DataObject\Objectbrick\Data\AbstractData $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @var DataObject\Concrete\Dao\InheritanceHelper
     */
    protected $inheritanceHelper = null;

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @throws \Exception
     */
    public function save(DataObject\Concrete $object, $params = [])
    {
        // HACK: set the pimcore admin mode to false to get the inherited values from parent if this source one is empty
        $inheritedValues = DataObject\AbstractObject::doGetInheritedValues();

        $storetable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $querytable = $this->model->getDefinition()->getTableName($object->getClass(), true);

        $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($object->getClassId(), 'o_id', $storetable, $querytable, null, 'o_id');

        DataObject\AbstractObject::setGetInheritedValues(false);

        $fieldDefinitions = $this->model->getDefinition()->getFieldDefinitions();

        $data = [];
        $data['o_id'] = $object->getId();
        $data['fieldname'] = $this->model->getFieldname();

        $dirtyRelations = [];

        // remove all relations
        $db = Db::get();
        try {
            $where = 'src_id = ' . $object->getId() . " AND ownertype = 'objectbrick' AND ownername = '" . $this->model->getFieldname() . "' AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')";
            // if the model supports dirty detection then only delete the dirty fields
            // as a consequence, only do inserts only on dirty fields
            if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $this->model instanceof  Model\Element\DirtyIndicatorInterface) {
                foreach ($fieldDefinitions as $key => $fd) {
                    if ($fd instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
                        if ($fd->supportsDirtyDetection()) {
                            if ($this->model->isFieldDirty('_self')) {
                                $this->model->markFieldDirty($key);
                            }

                            if ($this->model->isFieldDirty($key)) {
                                $dirtyRelations[] = $db->quote($key);
                            }
                        } else {
                            $dirtyRelations[] = $db->quote($key);
                        }
                    }
                }
                if ($dirtyRelations) {
                    $where .= ' AND fieldname IN (' . implode(',', $dirtyRelations) . ')';
                    $this->db->deleteWhere('object_relations_' . $object->getClassId(), $where);
                }
            } else {
                $this->db->deleteWhere('object_relations_' . $object->getClassId(), $where);
            }
        } catch (\Exception $e) {
            Logger::warning('Error during removing old relations: ' . $e);
        }

        foreach ($fieldDefinitions as $key => $fd) {
            $getter = 'get' . ucfirst($fd->getName());

            if ($this->model->getObject()->getClass()->getAllowInherit() && isset($params['isUpdate']) && $params['isUpdate'] === false) {
                // if this is a fresh object, then we don't need the check
                $isBrickUpdate = false; // used to indicate whether we want to consider the default value
            } else {
                // or brick has been added
                $existsResult = $this->db->fetchOne('SELECT o_id FROM ' . $storetable . ' WHERE o_id = ? LIMIT 1', $object->getId());
                $isBrickUpdate = $existsResult ? true : false;  // used to indicate whether we want to consider the default value
            }

            if ($fd instanceof CustomResourcePersistingInterface) {
                if ((!isset($params['newParent']) || !$params['newParent']) && isset($params['isUpdate']) && $params['isUpdate'] && !DataObject\AbstractObject::isDirtyDetectionDisabled() && $this->model instanceof Model\Element\DirtyIndicatorInterface) {
                    // ownerNameList contains the dirty stuff
                    if ($fd instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations && !in_array($db->quote($key), $dirtyRelations)) {
                        continue;
                    }
                }

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
                        'fieldname' => $key,
                    ]));
            }

            if ($fd instanceof ResourcePersistenceAwareInterface) {
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object, [
                        'owner' => $this->model, //\Pimcore\Model\DataObject\Objectbrick\Data\Dao
                        'isUpdate' => $isBrickUpdate,
                        'context' => [
                            'containerType' => 'objectbrick',
                            'containerKey' => $this->model->getType(),
                            'fieldname' => $this->model->getFieldname(),
                        ],
                    ]);
                    $data = array_merge($data, $insertDataArray);
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $object, [
                        'owner' => $this->model, //\Pimcore\Model\DataObject\Objectbrick\Data\Dao
                        'fieldname' => $key,
                        'isUpdate' => $isBrickUpdate,
                        'context' => [
                            'containerType' => 'objectbrick',
                            'containerKey' => $this->model->getType(),
                            'fieldname' => $this->model->getFieldname(),
                        ],
                    ]);
                    $data[$key] = $insertData;
                }
            }
        }

        $this->db->insertOrUpdate($storetable, $data);

        // get data for query table
        // $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        // this is special because we have to call each getter to get the inherited values from a possible parent object

        $data = [];
        $data['o_id'] = $object->getId();
        $data['fieldname'] = $this->model->getFieldname();

        $this->inheritanceHelper->resetFieldsToCheck();
        $oldData = $this->db->fetchRow('SELECT * FROM ' . $querytable . ' WHERE o_id = ?', $object->getId());

        $inheritanceEnabled = $object->getClass()->getAllowInherit();
        $parentData = null;
        if ($inheritanceEnabled) {
            // get the next suitable parent for inheritance
            $parentForInheritance = $object->getNextParentForInheritance();
            if ($parentForInheritance) {
                // we don't use the getter (built in functionality to get inherited values) because we need to avoid race conditions
                // we cannot DataObject\AbstractObject::setGetInheritedValues(true); and then $this->model->$method();
                // so we select the data from the parent object using FOR UPDATE, which causes a lock on this row
                // so the data of the parent cannot be changed while this transaction is on progress
                $parentData = $this->db->fetchRow('SELECT * FROM ' . $querytable . ' WHERE o_id = ? FOR UPDATE', $parentForInheritance->getId());
            }
        }

        foreach ($fieldDefinitions as $key => $fd) {
            if ($fd instanceof QueryResourcePersistenceAwareInterface) {
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

        $this->db->insertOrUpdate($querytable, $data);

        if ($inheritanceEnabled) {
            $this->inheritanceHelper->doUpdate($object->getId(), true,
                ['inheritanceRelationContext' => [
                    'ownertype' => 'objectbrick',
                ]]);
        }
        $this->inheritanceHelper->resetFieldsToCheck();

        // HACK: see a few lines above!
        DataObject\AbstractObject::setGetInheritedValues($inheritedValues);
    }

    /**
     * @param DataObject\Concrete $object
     */
    public function delete(DataObject\Concrete $object)
    {
        // update data for store table
        $storeTable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $this->db->delete($storeTable, ['o_id' => $object->getId()]);

        // update data for query table
        $queryTable = $this->model->getDefinition()->getTableName($object->getClass(), true);

        $oldData = $this->db->fetchRow('SELECT * FROM ' . $queryTable . ' WHERE o_id = ?', $object->getId());
        $this->db->delete($queryTable, ['o_id' => $object->getId()]);

        //update data for relations table
        $this->db->delete('object_relations_' . $object->getClassId(), [
            'src_id' => $object->getId(),
            'ownertype' => 'objectbrick',
            'ownername' => $this->model->getFieldname(),
            'position' => $this->model->getType(),
        ]);

        $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($object->getClassId(), 'o_id', $storeTable, $queryTable);
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

    /**
     * @param string $field
     * @param bool $forOwner
     * @param string $remoteClassId
     *
     * @return array
     */
    public function getRelationData($field, $forOwner, $remoteClassId)
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

        $relations = $this->db->fetchAll('SELECT r.' . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, o.o_className as subtype, concat(o.o_path ,o.o_key) as path , r.index, o.o_published as published
            FROM objects o, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . ' = ?
            AND o.o_id = r.' . $dest . "
            AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')
            AND r.type='object'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type,  a.type as subtype,  concat(a.path,a.filename) as path, r.index, "null" as published
            FROM assets a, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . ' = ?
            AND a.id = r.' . $dest . "
            AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')
            AND r.type='asset'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, d.type as subtype, concat(d.path,d.key) as path, r.index, d.published as published
            FROM documents d, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . ' = ?
            AND d.id = r.' . $dest . "
            AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')
            AND r.type='document'
            ORDER BY `index` ASC", $params);

        if (is_array($relations) and count($relations) > 0) {
            return $relations;
        } else {
            return [];
        }
    }
}
