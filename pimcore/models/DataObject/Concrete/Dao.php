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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Concrete;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Concrete $model
 */
class Dao extends Model\DataObject\AbstractObject\Dao
{
    /**
     * @var DataObject\Concrete\Dao\InheritanceHelper
     */
    protected $inheritanceHelper = null;

    public function init()
    {
        $this->inheritanceHelper = new DataObject\Concrete\Dao\InheritanceHelper($this->model->getClassId());
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param int $id
     */
    public function getById($id)
    {
        try {
            $data = $this->db->fetchRow("SELECT objects.*, tree_locks.locked as o_locked FROM objects
                LEFT JOIN tree_locks ON objects.o_id = tree_locks.id AND tree_locks.type = 'object'
                    WHERE o_id = ?", $id);

            if ($data['o_id']) {
                $this->assignVariablesToModel($data);
                $this->getData();
            } else {
                throw new \Exception('Object with the ID ' . $id . " doesn't exists");
            }
        } catch (\Exception $e) {
            Logger::warning($e);
        }
    }

    /**
     * @param  string $fieldName
     *
     * @return array
     */
    public function getRelationIds($fieldName)
    {
        $relations = [];
        $allRelations = $this->db->fetchAll('SELECT * FROM object_relations_' . $this->model->getClassId() . " WHERE fieldname = ? AND src_id = ? AND ownertype = 'object' ORDER BY `index` ASC", [$fieldName, $this->model->getId()]);
        foreach ($allRelations as $relation) {
            $relations[] = $relation['dest_id'];
        }

        return $relations;
    }

    /**
     * @param string $field
     * @param $forOwner
     * @param $remoteClassId
     *
     * @return array
     */
    public function getRelationData($field, $forOwner, $remoteClassId)
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

        $relations = $this->db->fetchAll('SELECT r.' . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, o.o_className as subtype, concat(o.o_path ,o.o_key) as path , r.index
            FROM objects o, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . ' = ?
            AND o.o_id = r.' . $dest . "
            AND r.type='object'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type,  a.type as subtype,  concat(a.path,a.filename) as path, r.index
            FROM assets a, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . ' = ?
            AND a.id = r.' . $dest . "
            AND r.type='asset'

            UNION SELECT r." . $dest . ' as dest_id, r.' . $dest . ' as id, r.type, d.type as subtype, concat(d.path,d.key) as path, r.index
            FROM documents d, object_relations_' . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . ' = ?
            AND d.id = r.' . $dest . "
            AND r.type='document'

            ORDER BY `index` ASC", $params);

        if (is_array($relations) and count($relations) > 0) {
            return $relations;
        } else {
            return [];
        }
    }

    /**
     * Get the data-elements for the object from database for the given path
     */
    public function getData()
    {
        $data = $this->db->fetchRow('SELECT * FROM object_store_' . $this->model->getClassId() . ' WHERE oo_id = ?', $this->model->getId());

        $fieldDefinitions = $this->model->getClass()->getFieldDefinitions(['object' => $this->model]);
        foreach ($fieldDefinitions as $key => $value) {
            if (method_exists($value, 'load')) {
                // datafield has it's own loader
                $value = $value->load($this->model);
                if ($value === 0 || !empty($value)) {
                    $this->model->setValue($key, $value);
                }
            } else {
                // if a datafield requires more than one field
                if (is_array($value->getColumnType())) {
                    $multidata = [];
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $multidata[$key . '__' . $fkey] = $data[$key . '__' . $fkey];
                    }
                    $this->model->setValue($key, $this->model->getClass()->getFieldDefinition($key)->getDataFromResource($multidata));
                } else {
                    $this->model->setValue($key, $value->getDataFromResource($data[$key], $this->model));
                }
            }
        }
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     */
    public function update()
    {
        parent::update();

        // get fields which shouldn't be updated
        $fieldDefinitions = $this->model->getClass()->getFieldDefinitions();
        $untouchable = [];
        foreach ($fieldDefinitions as $key => $fd) {
            if (method_exists($fd, 'getLazyLoading') && $fd->getLazyLoading()) {
                if (!in_array($key, $this->model->getLazyLoadedFields())) {
                    //this is a relation subject to lazy loading - it has not been loaded
                    $untouchable[] = $key;
                }
            }
        }

        // empty relation table except the untouchable fields (eg. lazy loading fields)
        if (count($untouchable) > 0) {
            $untouchables = "'" . implode("','", $untouchable) . "'";
            $this->db->deleteWhere('object_relations_' . $this->model->getClassId(), $this->db->quoteInto('src_id = ? AND fieldname not in (' . $untouchables . ") AND ownertype = 'object'", $this->model->getId()));
        } else {
            $this->db->delete('object_relations_' . $this->model->getClassId(), [
                'src_id' => $this->model->getId(),
                'ownertype' => 'object'
            ]);
        }

        $inheritedValues = DataObject\AbstractObject::doGetInheritedValues();
        DataObject\AbstractObject::setGetInheritedValues(false);

        $data = [];
        $data['oo_id'] = $this->model->getId();
        foreach ($fieldDefinitions as $key => $fd) {
            $getter = 'get' . ucfirst($key);

            if (method_exists($fd, 'save')) {
                // for fieldtypes which have their own save algorithm eg. fieldcollections, objects, multihref, ...
                $fd->save($this->model);
            } elseif ($fd->getColumnType()) {
                // pimcore saves the values with getDataForResource
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $this->model);
                    if (is_array($insertDataArray)) {
                        $data = array_merge($data, $insertDataArray);
                    }
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $this->model);
                    $data[$key] = $insertData;
                }
            }
        }

        $this->db->insertOrUpdate('object_store_' . $this->model->getClassId(), $data);

        // get data for query table
        $data = [];
        $this->inheritanceHelper->resetFieldsToCheck();
        $oldData = $this->db->fetchRow('SELECT * FROM object_query_' . $this->model->getClassId() . ' WHERE oo_id = ?', $this->model->getId());

        $inheritanceEnabled = $this->model->getClass()->getAllowInherit();
        $parentData = null;
        if ($inheritanceEnabled) {
            // get the next suitable parent for inheritance
            $parentForInheritance = $this->model->getNextParentForInheritance();
            if ($parentForInheritance) {
                // we don't use the getter (built in functionality to get inherited values) because we need to avoid race conditions
                // we cannot DataObject\AbstractObject::setGetInheritedValues(true); and then $this->model->$method();
                // so we select the data from the parent object using FOR UPDATE, which causes a lock on this row
                // so the data of the parent cannot be changed while this transaction is on progress
                $parentData = $this->db->fetchRow('SELECT * FROM object_query_' . $this->model->getClassId() . ' WHERE oo_id = ? FOR UPDATE', $parentForInheritance->getId());
            }
        }

        foreach ($fieldDefinitions as $key => $fd) {
            if ($fd->getQueryColumnType()) {
                //exclude untouchables if value is not an array - this means data has not been loaded
                if (!(in_array($key, $untouchable) and !is_array($this->model->$key))) {
                    $method = 'get' . $key;
                    $fieldValue = $this->model->$method();
                    $insertData = $fd->getDataForQueryResource($fieldValue, $this->model);
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

                    if ($inheritanceEnabled && $fd->getFieldType() != 'calculatedValue') {
                        //get changed fields for inheritance
                        if ($fd->isRelationType()) {
                            if (is_array($insertData)) {
                                $doInsert = false;
                                foreach ($insertData as $insertDataKey => $insertDataValue) {
                                    if ($isEmpty && $oldData[$insertDataKey] == $parentData[$insertDataKey]) {
                                        // do nothing, ... value is still empty and parent data is equal to current data in query table
                                    } elseif ($oldData[$insertDataKey] != $insertDataValue) {
                                        $doInsert = true;
                                        break;
                                    }
                                }

                                if ($doInsert) {
                                    $this->inheritanceHelper->addRelationToCheck($key, $fd, array_keys($insertData));
                                }
                            } else {
                                if ($isEmpty && $oldData[$key] == $parentData[$key]) {
                                    // do nothing, ... value is still empty and parent data is equal to current data in query table
                                } elseif ($oldData[$key] != $insertData) {
                                    $this->inheritanceHelper->addRelationToCheck($key, $fd);
                                }
                            }
                        } else {
                            if (is_array($insertData)) {
                                foreach ($insertData as $insertDataKey => $insertDataValue) {
                                    if ($isEmpty && $oldData[$insertDataKey] == $parentData[$insertDataKey]) {
                                        // do nothing, ... value is still empty and parent data is equal to current data in query table
                                    } elseif ($oldData[$insertDataKey] != $insertDataValue) {
                                        $this->inheritanceHelper->addFieldToCheck($insertDataKey, $fd);
                                    }
                                }
                            } else {
                                if ($isEmpty && $oldData[$key] == $parentData[$key]) {
                                    // do nothing, ... value is still empty and parent data is equal to current data in query table
                                } elseif ($oldData[$key] != $insertData) {
                                    // data changed, do check and update
                                    $this->inheritanceHelper->addFieldToCheck($key, $fd);
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

        $this->db->insertOrUpdate('object_query_' . $this->model->getClassId(), $data);

        DataObject\AbstractObject::setGetInheritedValues($inheritedValues);
    }

    public function saveChildData()
    {
        $this->inheritanceHelper->doUpdate($this->model->getId());
        $this->inheritanceHelper->resetFieldsToCheck();
    }

    /**
     * Save object to database
     */
    public function delete()
    {
        $this->db->delete('object_query_' . $this->model->getClassId(), ['oo_id' => $this->model->getId()]);
        $this->db->delete('object_store_' . $this->model->getClassId(), ['oo_id' => $this->model->getId()]);
        $this->db->delete('object_relations_' . $this->model->getClassId(), ['src_id' => $this->model->getId()]);

        // delete fields wich have their own delete algorithm
        foreach ($this->model->getClass()->getFieldDefinitions() as $fd) {
            if (method_exists($fd, 'delete')) {
                $fd->delete($this->model);
            }
        }

        parent::delete();
    }

    /**
     * get versions from database, and assign it to object
     *
     * @return array
     */
    public function getVersions()
    {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? AND ctype='object' ORDER BY `id` DESC", $this->model->getId());

        $versions = [];
        foreach ($versionIds as $versionId) {
            $versions[] = Model\Version::getById($versionId);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     *
     * @param bool $force
     *
     * @return Model\Version|null
     *
     * @todo: should return null or false explicit
     */
    public function getLatestVersion($force = false)
    {
        $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='object' ORDER BY `id` DESC LIMIT 1", $this->model->getId());

        if ($versionData && $versionData['id'] && ($versionData['date'] > $this->model->getModificationDate() || $force)) {
            $version = Model\Version::getById($versionData['id']);

            return $version;
        }

        return;
    }

    public function deleteAllTasks()
    {
        $this->db->delete('schedule_tasks', ['cid' => $this->model->getId(), 'ctype' => 'object']);
    }
}
