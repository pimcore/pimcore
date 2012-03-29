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
 * @package    Object_Objectbrick
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick_Data_Resource extends Pimcore_Model_Resource_Abstract {


    /**
     * @var Object_Concrete_Resource_InheritanceHelper
     */
    protected $inheritanceHelper = null;    

    
    /**
     * create data rows for query table and for the store table
     *
     * @return void
     */
    protected function createDataRows($object) {
        try {
            $tableName = $this->model->getDefinition()->getTableName($object->getClass(), false);
            $this->db->insert($tableName, array("o_id" => $object->getId(), "fieldname" => $this->model->getFieldname()));
        }
        catch (Exception $e) {
        }

        try {
            $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
            $this->db->insert($tableName, array("o_id" => $object->getId(), "fieldname" => $this->model->getFieldname()));
        }
        catch (Exception $e) {
        }
    }

    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function save (Object_Concrete $object) {

        // HACK: set the pimcore admin mode to false to get the inherited values from parent if this source one is empty
        $inheritedValues = Object_Abstract::doGetInheritedValues();

        $storetable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $querytable = $this->model->getDefinition()->getTableName($object->getClass(), true);


        $this->inheritanceHelper = new Object_Concrete_Resource_InheritanceHelper($object->getClassId(), "o_id", $storetable, $querytable);

        $this->createDataRows($object);


        Object_Abstract::setGetInheritedValues(false);

        $fd = $this->model->getDefinition()->getFieldDefinitions();

        $data = array();
        $data["o_id"] = $object->getId();
        $data["fieldname"] = $this->model->getFieldname();

        // remove all relations
        try {
            $this->db->delete("object_relations_" . $object->getClassId(), "src_id = " . $object->getId() . " AND ownertype = 'objectbrick' AND ownername = '" . $this->model->getFieldname() . "' AND (position = '" . $this->model->getType() . "' OR position IS NULL OR position = '')");
        } catch(Exception $e) {
            Logger::warning("Error during removing old relations: " . $e);
        }

        foreach ($fd as $key => $value) {
            $getter = "get" . ucfirst($value->getName());
            
                if (method_exists($fd, "save")) {
                    // for fieldtypes which have their own save algorithm eg. objects, multihref, ...
                    $value->save($this->model);

                } else {
                if ($value->getColumnType()) {
                    if (is_array($value->getColumnType())) {
                        $insertDataArray = $value->getDataForResource($this->model->$getter(), $object);
                        $data = array_merge($data, $insertDataArray);
                    } else {
                        $insertData = $value->getDataForResource($this->model->$getter(), $object);
                        $data[$key] = $insertData;
                    }
                } else if (method_exists($value, "save")) {
                    // for fieldtypes which have their own save algorithm eg. fieldcollections
                    $value->save($this->model);
                }
            }
        }

        $this->db->update($storetable, $data, $this->db->quoteInto("o_id = ?", $object->getId()));


        // get data for query table 
        // $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        // this is special because we have to call each getter to get the inherited values from a possible parent object


        Object_Abstract::setGetInheritedValues(true);

        $objectVars = get_object_vars($this->model);

        $data = array();
        $data["o_id"] = $object->getId();
        $data["fieldname"] = $this->model->getFieldname();
        $this->inheritanceHelper->resetFieldsToCheck();
        $oldData = $this->db->fetchRow("SELECT * FROM " . $querytable . " WHERE o_id = ?", $object->getId());

        foreach ($objectVars as $key => $value) {
            $fd = $this->model->getDefinition()->getFieldDefinition($key);

            if ($fd) {
                if ($fd->getQueryColumnType()) {
                    //exclude untouchables if value is not an array - this means data has not been loaded

                    $method = "get" . $key;
                    $insertData = $fd->getDataForQueryResource($this->model->$method(), $object);

                    if (is_array($insertData)) {
                        $data = array_merge($data, $insertData);
                    }
                    else {
                        $data[$key] = $insertData;
                    }


                    //get changed fields for inheritance
                    if($fd->isRelationType()) {
                        if (is_array($insertData)) {
                            $doInsert = false;
                            foreach($insertData as $insertDataKey => $insertDataValue) {
                                if($oldData[$insertDataKey] != $insertDataValue) {
                                    $doInsert = true;
                                }
                            }

                            if($doInsert) {
                                $this->inheritanceHelper->addRelationToCheck($key, array_keys($insertData));
                            }
                        } else {
                            if($oldData[$key] != $insertData) {
                                $this->inheritanceHelper->addRelationToCheck($key);
                            }
                        }

                    } else {
                        if (is_array($insertData)) {
                            foreach($insertData as $insertDataKey => $insertDataValue) {
                                if($oldData[$insertDataKey] != $insertDataValue) {
                                    $this->inheritanceHelper->addFieldToCheck($insertDataKey);
                                }
                            }
                        } else {
                            if($oldData[$key] != $insertData) {
                                $this->inheritanceHelper->addFieldToCheck($key);
                            }
                        }
                    }

                }
            }
        }
        $this->db->update($querytable, $data, "o_id = " . $object->getId());

        $this->inheritanceHelper->doUpdate($object->getId());
        $this->inheritanceHelper->resetFieldsToCheck();

        // HACK: see a few lines above!
        Object_Abstract::setGetInheritedValues($inheritedValues);

    }

    /**
     * @param Object_Concrete $object
     * @return void
     */
    public function delete(Object_Concrete $object) {
        // update data for store table
        $tableName = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $this->db->delete($tableName, $this->db->quoteInto("o_id = ?", $object->getId()));

        // update data for query table
        $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        $this->db->delete($tableName, $this->db->quoteInto("o_id = ?", $object->getId()));

        //update data for relations table
        $this->db->delete("object_relations_" . $object->getO_classId(), "src_id = " . $object->getId() . " AND ownertype = 'objectbrick' AND ownername = '" . $this->model->getFieldname() . "' AND position = '" . $this->model->getType() . "'");
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
