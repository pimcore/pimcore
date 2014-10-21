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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Concrete;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Object\AbstractObject\Resource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumnsObjectConcrete = array();

    /**
     * @var Object\Concrete\Resource\InheritanceHelper
     */
    protected $inheritanceHelper = null;

    /**
     *
     */
    public function init() {  
        parent::init();
        $this->inheritanceHelper = new Object\Concrete\Resource\InheritanceHelper($this->model->getClassId());
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        try {
            $data = $this->db->fetchRow("SELECT objects.*, tree_locks.locked as o_locked FROM objects
                LEFT JOIN tree_locks ON objects.o_id = tree_locks.id AND tree_locks.type = 'object'
                    WHERE o_id = ?", $id);

            if ($data["o_id"]) {
                $this->assignVariablesToModel($data);
                $this->getData();
            }
            else {
                throw new \Exception("Object with the ID " . $id . " doesn't exists");
            }

        }
        catch (\Exception $e) {
            \Logger::warning($e);
        }
    }

    /**
     * @param  string $fieldName
     * @return array
     */
    public function getRelationIds($fieldName) {
        $relations = array();
        $allRelations = $this->db->fetchAll("SELECT * FROM object_relations_" . $this->model->getClassId() . " WHERE fieldname = ? AND src_id = ? AND ownertype = 'object' ORDER BY `index` ASC", array($fieldName, $this->model->getId()));
        foreach ($allRelations as $relation) {
            $relations[] = $relation["dest_id"];
        }
        return $relations;
    }

    /**
     * @param  string $field
     * @return array
     */
    public function getRelationData($field, $forOwner, $remoteClassId) {

        $id = $this->model->getId();
        if ($remoteClassId) {
            $classId = $remoteClassId;
        } else {
            $classId = $this->model->getClassId();
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
            AND r.ownertype = 'object'
            AND r." . $src . " = ?
            AND o.o_id = r." . $dest . "
            AND r.type='object'

            UNION SELECT r." . $dest . " as dest_id, r." . $dest . " as id, r.type,  a.type as subtype,  concat(a.path,a.filename) as path, r.index
            FROM assets a, object_relations_" . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . " = ?
            AND a.id = r." . $dest . "
            AND r.type='asset'

            UNION SELECT r." . $dest . " as dest_id, r." . $dest . " as id, r.type, d.type as subtype, concat(d.path,d.key) as path, r.index
            FROM documents d, object_relations_" . $classId . " r
            WHERE r.fieldname= ?
            AND r.ownertype = 'object'
            AND r." . $src . " = ?
            AND d.id = r." . $dest . "
            AND r.type='document'

            ORDER BY `index` ASC", $params);

        if (is_array($relations) and count($relations) > 0) {
            return $relations;
        } else return array();
    }


    /**
     * Get the data-elements for the object from database for the given path
     *
     * @return void
     */
    public function getData() {

        $data = $this->db->fetchRow('SELECT * FROM object_store_' . $this->model->getClassId() . ' WHERE oo_id = ?', $this->model->getId());

        foreach ($this->model->getClass()->getFieldDefinitions() as $key => $value) {
            if (method_exists($value, "load")) {
                // datafield has it's own loader
                $value = $value->load($this->model);
                if($value === 0 || !empty($value)) {
                    $this->model->setValue($key, $value);
                }
            } else {
                // if a datafield requires more than one field
                if (is_array($value->getColumnType())) {
                    $multidata = array();
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $multidata[$key . "__" . $fkey] = $data[$key . "__" . $fkey];
                    }
                    $this->model->setValue( $key, $this->model->getClass()->getFieldDefinition($key)->getDataFromResource($multidata));

                } else {
                    $this->model->setValue( $key, $value->getDataFromResource($data[$key]));
                }
            }
        }
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {

        parent::update();

        // get fields which shouldn't be updated
        $fd = $this->model->getClass()->getFieldDefinitions();
        $untouchable = array();
        foreach ($fd as $key => $value) {
            if (method_exists($value, "getLazyLoading") && $value->getLazyLoading()) {
                if (!in_array($key, $this->model->getLazyLoadedFields())) {
                    //this is a relation subject to lazy loading - it has not been loaded
                    $untouchable[] = $key;
                }
            }
        }
        
        // empty relation table except the untouchable fields (eg. lazy loading fields)
        if (count($untouchable) > 0) {
            $untouchables = "'" . implode("','", $untouchable) . "'";
            $this->db->delete("object_relations_" . $this->model->getClassId(), $this->db->quoteInto("src_id = ? AND fieldname not in (" . $untouchables . ") AND ownertype = 'object'", $this->model->getId()));
        } else {
            $this->db->delete("object_relations_" . $this->model->getClassId(), $this->db->quoteInto("src_id = ? AND ownertype = 'object'",  $this->model->getId()));
        }

        
        $inheritedValues = Object\AbstractObject::doGetInheritedValues();
        Object\AbstractObject::setGetInheritedValues(false);

        $data = array();
        $data["oo_id"] = $this->model->getId();
        foreach ($fd as $key => $value) {

            $getter = "get" . ucfirst($key);

            if (method_exists($value, "save")) {
                // for fieldtypes which have their own save algorithm eg. fieldcollections, objects, multihref, ...
                $value->save($this->model);
            } else if ($value->getColumnType()) {
                // pimcore saves the values with getDataForResource
                if (is_array($value->getColumnType())) {
                    $insertDataArray = $value->getDataForResource($this->model->$getter(), $this->model);
                    if(is_array($insertDataArray)) {
                        $data = array_merge($data, $insertDataArray);
                    }
                } else {
                    $insertData = $value->getDataForResource($this->model->$getter(), $this->model);
                    $data[$key] = $insertData;
                }
            }
        }

        $this->db->insertOrUpdate("object_store_" . $this->model->getClassId(), $data);

        // get data for query table
        // this is special because we have to call each getter to get the inherited values from a possible parent object
        Object\AbstractObject::setGetInheritedValues(true);

        $object = get_object_vars($this->model);

        $data = array();
        $this->inheritanceHelper->resetFieldsToCheck();
        $oldData = $this->db->fetchRow("SELECT * FROM object_query_" . $this->model->getClassId() . " WHERE oo_id = ?", $this->model->getId());

        foreach ($object as $key => $value) {
            $fd = $this->model->getClass()->getFieldDefinition($key);

            if ($fd) {
                if ($fd->getQueryColumnType()) {
                    //exclude untouchables if value is not an array - this means data has not been loaded
                    if (!(in_array($key, $untouchable) and !is_array($this->model->$key))) {
                        $method = "get" . $key;
                        $insertData = $fd->getDataForQueryResource($this->model->$method(), $this->model);
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

                    } else {
                        \Logger::debug("Excluding untouchable query value for object [ " . $this->model->getId() . " ]  key [ $key ] because it has not been loaded");
                    }
                }
            }
        }
        $data["oo_id"] = $this->model->getId();

        $this->db->insertOrUpdate("object_query_" . $this->model->getClassId(), $data);

        Object\AbstractObject::setGetInheritedValues($inheritedValues);
    }

    /**
     *
     */
    public function saveChilds() {
        $this->inheritanceHelper->doUpdate($this->model->getId());
        $this->inheritanceHelper->resetFieldsToCheck();
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("object_query_" . $this->model->getClassId(), $this->db->quoteInto("oo_id = ?", $this->model->getId()));
        $this->db->delete("object_store_" . $this->model->getClassId(), $this->db->quoteInto("oo_id = ?", $this->model->getId()));
        $this->db->delete("object_relations_" . $this->model->getClassId(), $this->db->quoteInto("src_id = ?", $this->model->getId()));

        // delete fields wich have their own delete algorithm
        foreach ($this->model->getClass()->getFieldDefinitions() as $fd) {
            if (method_exists($fd, "delete")) {
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
    public function getVersions() {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? AND ctype='object' ORDER BY `id` DESC", $this->model->getId());

        $versions = array();
        foreach ($versionIds as $versionId) {
            $versions[] = Model\Version::getById($versionId);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     * @param bool $force
     * @return array
     */
    public function getLatestVersion($force = false) {
        $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='object' ORDER BY `id` DESC LIMIT 1", $this->model->getId());

        if(($versionData["id"] && $versionData["date"] > $this->model->getModificationDate()) || $force) {
            $version = Model\Version::getById($versionData["id"]);
            return $version;
        }
        return;
    }

    /**
     * @return void
     */
    public function deleteAllTasks() {
        $this->db->delete("schedule_tasks", "cid='" . $this->model->getId() . "' AND ctype='object'");
        $this->db->delete("schedule_tasks", $this->db->quoteInto("cid = ? AND ctype='object'", $this->model->getId()));
    }
}
