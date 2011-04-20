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
 * @package    Object_Fieldcollection
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick_Data_Resource_Mysql extends Pimcore_Model_Resource_Mysql_Abstract {


    /**
     * @var Object_Concrete_Resource_Mysql_InheritanceHelper
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

    public function save (Object_Concrete $object) {

        $storetable = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $querytable = $this->model->getDefinition()->getTableName($object->getClass(), true);


        $this->inheritanceHelper = new Object_Concrete_Resource_Mysql_InheritanceHelper($object->getClassId(), "o_id", $storetable, $querytable);

        $this->createDataRows($object);


        //        $data = array(
        //            "o_id" => $object->getId(),
        //            "fieldname" => $this->model->getFieldname()
        //        );


        //        p_r($this->model); die();

        $fd = $this->model->getDefinition()->getFieldDefinitions();
        $untouchable = array();
        foreach ($fd as $key => $value) {
            if ($value->isRelationType()) {
                if ($value->getLazyLoading()) {
                    if (!in_array($key, $this->model->getLazyLoadedFields())) {
                        //this is a relation subject to lazy loading - it has not been loaded
                        $untouchable[] = $key;
                    }
                }
            }
        }


        // update data for store table
        //        $tableName = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $data = array();
        $data["o_id"] = $object->getId();
        $data["fieldname"] = $this->model->getFieldname();

        foreach ($fd as $key => $value) {
            if ($value->getColumnType()) {
                if (is_array($value->getColumnType())) {
                    $insertDataArray = $value->getDataForResource($this->model->$key);
                    $data = array_merge($data, $insertDataArray);
                } else {
                    $insertData = $value->getDataForResource($this->model->$key);
                    $data[$key] = $insertData;
                }
            } else if (method_exists($value, "save")) {
                // for fieldtypes which have their own save algorithm eg. fieldcollections
                $value->save($this->model);
            }
        }

        $this->db->update($storetable, $data, $this->db->quoteInto("o_id = ?", $object->getId()));


        // get data for query table
        //        $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        // this is special because we have to call each getter to get the inherited values from a possible parent object

        // HACK: set the pimcore admin mode to false to get the inherited values from parent if this source one is empty
        $inheritedValues = Object_Abstract::doGetInheritedValues();
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
                    if (!(in_array($key, $untouchable) and !is_array($this->model->$key))) {
                        $method = "get" . $key;
                        $insertData = $fd->getDataForQueryResource($this->model->$method());
//                        p_R($this->model->$method());
                        if (is_array($insertData)) {
                            $data = array_merge($data, $insertData);
                        }
                        else {
                            $data[$key] = $insertData;
                        }


                        //get changed fields for inheritance
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

                    } else {
                        logger::debug(get_class($this) . ": Excluding untouchable query value for object - objectbrick [ " . $object->getId() . " ]  key [ $key ] because it has not been loaded");
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

    public function delete(Object_Concrete $object) {
        // update data for store table
        $tableName = $this->model->getDefinition()->getTableName($object->getClass(), false);
        $this->db->delete($tableName, $this->db->quoteInto("o_id = ?", $object->getId()));

        // update data for query table
        $tableName = $this->model->getDefinition()->getTableName($object->getClass(), true);
        $this->db->delete($tableName, $this->db->quoteInto("o_id = ?", $object->getId()));
    }


}
