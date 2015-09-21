<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Objectbrick\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Resource\AbstractResource {


    /**
     * @var Object\Concrete\Resource\InheritanceHelper
     */
    protected $inheritanceHelper = null;


    /**
     * @param Object\Concrete $object
     * @throws \Exception
     */
    public function save (Object\Concrete $object) {

        // HACK: set the pimcore admin mode to false to get the inherited values from parent if this source one is empty
        $inheritedValues = Object\AbstractObject::doGetInheritedValues();

        $storetable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $querytable = $this->model->getDefinition()->getTableName($object->getClass(), true);


        $this->inheritanceHelper = new Object\Concrete\Resource\InheritanceHelper($object->getClassId(), "o_id", $storetable, $querytable);

        Object\AbstractObject::setGetInheritedValues(false);

        $fieldDefinitions = $this->model->getDefinition()->getFieldDefinitions();

        $data = array();
        $data["o_id"] = $object->getId();
        $data["fieldname"] = $this->model->getFieldname();

        // remove all relations
        try {
            $this->db->delete("object_relations_" . $object->getClassId(), "src_id = " . $object->getId() . " AND ownertype = 'objectbrick' AND ownername = '" . $this->model->getFieldname() . "' AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')");
        } catch(\Exception $e) {
            \Logger::warning("Error during removing old relations: " . $e);
        }

        foreach ($fieldDefinitions as $key => $fd) {
            $getter = "get" . ucfirst($fd->getName());
            
            if (method_exists($fd, "save")) {
                // for fieldtypes which have their own save algorithm eg. objects, multihref, ...
                $fd->save($this->model);
            } else if ($fd->getColumnType()) {
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object);
                    $data = array_merge($data, $insertDataArray);
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $object);
                    $data[$key] = $insertData;
                }
            }
        }

        $this->db->insertOrUpdate($storetable, $data);


        // get data for query table 
        // $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        // this is special because we have to call each getter to get the inherited values from a possible parent object

        $data = array();
        $data["o_id"] = $object->getId();
        $data["fieldname"] = $this->model->getFieldname();

        $this->inheritanceHelper->resetFieldsToCheck();
        $oldData = $this->db->fetchRow("SELECT * FROM " . $querytable . " WHERE o_id = ?", $object->getId());

        $inheritanceEnabled = $object->getClass()->getAllowInherit();
        $parentData = null;
        if($inheritanceEnabled) {
            // get the next suitable parent for inheritance
            $parentForInheritance = $object->getNextParentForInheritance();
            if($parentForInheritance) {
                // we don't use the getter (built in functionality to get inherited values) because we need to avoid race conditions
                // we cannot Object\AbstractObject::setGetInheritedValues(true); and then $this->model->$method();
                // so we select the data from the parent object using FOR UPDATE, which causes a lock on this row
                // so the data of the parent cannot be changed while this transaction is on progress
                $parentData = $this->db->fetchRow("SELECT * FROM " . $querytable . " WHERE o_id = ? FOR UPDATE", $parentForInheritance->getId());
            }
        }

        foreach ($fieldDefinitions as $key => $fd) {
            if ($fd->getQueryColumnType()) {
                //exclude untouchables if value is not an array - this means data has not been loaded

                $method = "get" . $key;
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
                if($isEmpty && $parentData) {
                    foreach($columnNames as $columnName) {
                        if(array_key_exists($columnName, $parentData)) {
                            $data[$columnName] = $parentData[$columnName];
                        }
                    }
                }

                if($inheritanceEnabled) {
                    //get changed fields for inheritance
                    if ($fd->isRelationType()) {
                        if (is_array($insertData)) {
                            $doInsert = false;
                            foreach ($insertData as $insertDataKey => $insertDataValue) {
                                if ($isEmpty && $oldData[$insertDataKey] == $parentData[$insertDataKey]) {
                                    // do nothing, ... value is still empty and parent data is equal to current data in query table
                                } else if ($oldData[$insertDataKey] != $insertDataValue) {
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
                            } else if ($oldData[$key] != $insertData) {
                                $this->inheritanceHelper->addRelationToCheck($key, $fd);
                            }
                        }

                    } else {
                        if (is_array($insertData)) {
                            foreach ($insertData as $insertDataKey => $insertDataValue) {
                                if ($isEmpty && $oldData[$insertDataKey] == $parentData[$insertDataKey]) {
                                    // do nothing, ... value is still empty and parent data is equal to current data in query table
                                } else if ($oldData[$insertDataKey] != $insertDataValue) {
                                    $this->inheritanceHelper->addFieldToCheck($insertDataKey, $fd);
                                }
                            }
                        } else {
                            if ($isEmpty && $oldData[$key] == $parentData[$key]) {
                                // do nothing, ... value is still empty and parent data is equal to current data in query table
                            } else if ($oldData[$key] != $insertData) {
                                // data changed, do check and update
                                $this->inheritanceHelper->addFieldToCheck($key, $fd);
                            }
                        }
                    }
                }
            }
        }

        $this->db->insertOrUpdate($querytable, $data);

        $this->inheritanceHelper->doUpdate($object->getId(), true);
        $this->inheritanceHelper->resetFieldsToCheck();

        // HACK: see a few lines above!
        Object\AbstractObject::setGetInheritedValues($inheritedValues);

    }

    /**
     * @param Object\Concrete $object
     * @return void
     */
    public function delete(Object\Concrete $object) {
        // update data for store table
        $storeTable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $this->db->delete($storeTable, $this->db->quoteInto("o_id = ?", $object->getId()));

        // update data for query table
        $queryTable = $this->model->getDefinition()->getTableName($object->getClass(), true);

        $oldData = $this->db->fetchRow("SELECT * FROM " . $queryTable . " WHERE o_id = ?", $object->getId());
        $this->db->delete($queryTable, $this->db->quoteInto("o_id = ?", $object->getId()));

        //update data for relations table
        $this->db->delete("object_relations_" . $object->getClassId(), "src_id = " . $object->getId() . " AND ownertype = 'objectbrick' AND ownername = '" . $this->model->getFieldname() . "' AND position = '" . $this->model->getType() . "'");

        $this->inheritanceHelper = new Object\Concrete\Resource\InheritanceHelper($object->getClassId(), "o_id", $storeTable, $queryTable);
        $this->inheritanceHelper->resetFieldsToCheck();

        $objectVars = get_object_vars($this->model);

        foreach ($objectVars as $key => $value) {
            $fd = $this->model->getDefinition()->getFieldDefinition($key);

            if ($fd) {
                if ($fd->getQueryColumnType()) {
                    //exclude untouchables if value is not an array - this means data has not been loaded
                    //get changed fields for inheritance
                    if($fd->isRelationType()) {
                        if($oldData[$key] != null) {
                            $this->inheritanceHelper->addRelationToCheck($key, $fd);
                        }
                    } else {
                        if($oldData[$key] != null) {
                            $this->inheritanceHelper->addFieldToCheck($key, $fd);
                        }
                    }
                }
            }
        }


        $this->inheritanceHelper->doDelete($object->getId());

        $this->inheritanceHelper->resetFieldsToCheck();
    }


    /**
     * @param  string $field
     * @return array
     */
    public function getRelationData($field, $forOwner, $remoteClassId) {

        $id = $this->model->getObject()->getId();
        if ($remoteClassId) {
            $classId = $remoteClassId;
        } else {
            $classId = $this->model->getObject()->getClassId();
        }


        $params = array($field, $id, $field, $id, $field, $id);

        $dest = "dest_id";
        $src = "src_id";
        if (!$forOwner) {
            $dest = "src_id";
            $src = "dest_id";
        }

        $relations = $this->db->fetchAll("SELECT r." . $dest . " as dest_id, r." . $dest . " as id, r.type, o.o_className as subtype, concat(o.o_path ,o.o_key) as path , r.index
            FROM objects o, object_relations_" . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . " = ?
            AND o.o_id = r." . $dest . "
            AND r.type='object'

            UNION SELECT r." . $dest . " as dest_id, r." . $dest . " as id, r.type,  a.type as subtype,  concat(a.path,a.filename) as path, r.index
            FROM assets a, object_relations_" . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . " = ?
            AND a.id = r." . $dest . "
            AND r.type='asset'

            UNION SELECT r." . $dest . " as dest_id, r." . $dest . " as id, r.type, d.type as subtype, concat(d.path,d.key) as path, r.index
            FROM documents d, object_relations_" . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'objectbrick'
            AND r." . $src . " = ?
            AND d.id = r." . $dest . "
            AND r.type='document'

            ORDER BY `index` ASC", $params);

        if (is_array($relations) and count($relations) > 0) {
            return $relations;
        } else return array();
    }
}
