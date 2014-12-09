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
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Fieldcollection;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Resource\AbstractResource {

    /**
     * @param Object\Concrete $object
     */
    public function save (Object\Concrete $object) {
        $this->delete($object);
    }

    /**
     * @param Object\Concrete $object
     * @return array
     */
    public function load (Object\Concrete $object) {
        
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        $values = array();

        
        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = Object\Fieldcollection\Definition::getByKey($type);
            } catch (\Exception $e) {
                continue;
            }
            
            $tableName = $definition->getTableName($object->getClass());
            
            try {
                $results = $this->db->fetchAll("SELECT * FROM " . $tableName . " WHERE o_id = ? AND fieldname = ? ORDER BY `index` ASC", array($object->getId(), $this->model->getFieldname()));
            } catch (\Exception $e) {
                $results = array();
            }

            //$allRelations = $this->db->fetchAll("SELECT * FROM object_relations_" . $object->getO_classId() . " WHERE src_id = ? AND ownertype = 'fieldcollection' AND ownername = ? ORDER BY `index` ASC", array($object->getO_id(), $this->model->getFieldname()));
            
            $fieldDefinitions = $definition->getFieldDefinitions();
            $collectionClass = "\\Pimcore\\Model\\Object\\Fieldcollection\\Data\\" . ucfirst($type);
            
            
            foreach ($results as $result) {
                
                $collection = new $collectionClass();
                $collection->setIndex($result["index"]);
                $collection->setFieldname($result["fieldname"]);
                $collection->setObject($object);
                
                foreach ($fieldDefinitions as $key => $fd) {
                    if (method_exists($fd, "load")) {
                        // datafield has it's own loader
                        $value = $fd->load($collection);
                        if($value === 0 || !empty($value)) {
                            $collection->setValue($key, $value);
                        }
                    } else {
                        if (is_array($fd->getColumnType())) {
                            $multidata = array();
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . "__" . $fkey] = $result[$key . "__" . $fkey];
                            }
                            $collection->setValue($key, $fd->getDataFromResource($multidata));

                        } else {
                            $collection->setValue( $key, $fd->getDataFromResource($result[$key]));
                        }
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

    /**
     * @param Object\Concrete $object
     */
    public function delete (Object\Concrete $object) {
        // empty or create all relevant tables 
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        
        foreach ($fieldDef->getAllowedTypes() as $type) {
            
            try {
                $definition = Object\Fieldcollection\Definition::getByKey($type);
            } catch (\Exception $e) {
                continue;
            }
              
            $tableName = $definition->getTableName($object->getClass());
            
            try {
                $this->db->delete($tableName, $this->db->quoteInto("o_id = ?", $object->getId()) . " AND " . $this->db->quoteInto("fieldname = ?", $this->model->getFieldname()));
            } catch (\Exception $e) {
                // create definition if it does not exist
                $definition->createUpdateTable($object->getClass());
            }
        }

        // empty relation table
        $this->db->delete("object_relations_" . $object->getClassId(), "ownertype = 'fieldcollection' AND " . $this->db->quoteInto("ownername = ?", $this->model->getFieldname()) . " AND " . $this->db->quoteInto("src_id = ?", $object->getId()));
    }
}
