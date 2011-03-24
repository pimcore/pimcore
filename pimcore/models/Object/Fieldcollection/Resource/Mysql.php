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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Fieldcollection_Resource_Mysql extends Pimcore_Model_Resource_Mysql_Abstract {
    
    public function save (Object_Concrete $object) {
        $this->delete($object);
    }
    
    public function load (Object_Concrete $object) {
        
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        $values = array();

        
        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = Object_Fieldcollection_Definition::getByKey($type);
            } catch (Exception $e) {
                continue;
            }
            
            $tableName = $definition->getTableName($object->getClass());
            
            try {
                $results = $this->db->fetchAll("SELECT * FROM ".$tableName." WHERE o_id = '".$object->getId()."' AND fieldname = '".$this->model->getFieldname()."' ORDER BY `index` ASC");
            } catch (Exception $e) {
                $results = array();
            }            
            
            $fieldDefinitions = $definition->getFieldDefinitions();
            $collectionClass = "Object_Fieldcollection_Data_" . ucfirst($type);
            
            
            foreach ($results as $result) {
                
                $collection = new $collectionClass();
                $collection->setIndex($result["index"]);
                $collection->setFieldname($result["fieldname"]);
                
                foreach ($fieldDefinitions as $key => $fd) {
                    if (is_array($fd->getColumnType())) {
                        $multidata = array();
                        foreach ($fd->getColumnType() as $fkey => $fvalue) {
                            $multidata[$key . "__" . $fkey] = $result[$key . "__" . $fkey];
                        }
                        $collection->setValue(
                            $key,
                            $fd->getDataFromResource($multidata));
    
                    } else {
                        $collection->setValue(
                            $key,
                            $fd->getDataFromResource($result[$key]));
                    }
                }
                
                $values[] = $collection;
            }
        }
        
        $orderedValues = array();
        foreach ($values as $value) {
            $orderedValues[$value->getIndex()] = $value;
        }
        
        ksort($orderedValues);
        
        $this->model->setItems($orderedValues);
                
        return $orderedValues;
    }
    
    public function delete (Object_Concrete $object) {
        // empty or create all relevant tables 
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        
        foreach ($fieldDef->getAllowedTypes() as $type) {
            
            try {
                $definition = Object_Fieldcollection_Definition::getByKey($type);
            } catch (Exception $e) {
                continue;
            }
              
            $tableName = $definition->getTableName($object->getClass());
            
            try {
                $this->db->delete($tableName, "o_id = '".$object->getId()."' AND fieldname = '".$this->model->getFieldname()."'");
            } catch (Exception $e) {
                // create definition if it does not exist
                $definition->createUpdateTable($object->getClass());
            }
        }
    }
}
