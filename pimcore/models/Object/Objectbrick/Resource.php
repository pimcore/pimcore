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

class Object_Objectbrick_Resource extends Object_Fieldcollection_Resource {

    /**
     * @param Object_Concrete $object
     * @return array
     */
    public function load(Object_Concrete $object) {
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        $values = array();
        
        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = Object_Objectbrick_Definition::getByKey($type);
            } catch (Exception $e) {
                continue;
            }
             
            $tableName = $definition->getTableName($object->getClass(), false);
            
            try {
                $results = $this->db->fetchAll("SELECT * FROM ".$tableName." WHERE o_id = ? AND fieldname = ?", array($object->getId(), $this->model->getFieldname()));
            } catch (Exception $e) {
                $results = array();
            }

            //$allRelations = $this->db->fetchAll("SELECT * FROM object_relations_" . $object->getO_classId() . " WHERE src_id = ? AND ownertype = 'objectbrick' AND ownername = ?", array($object->getO_id(), $this->model->getFieldname()));
            $fieldDefinitions = $definition->getFieldDefinitions();
            $brickClass = "Object_Objectbrick_Data_" . ucfirst($type);

            foreach ($results as $result) {
                $brick = new $brickClass($object);
                $brick->setFieldname($result["fieldname"]);
                $brick->setObject($object);

                foreach ($fieldDefinitions as $key => $fd) {

                    /*if ($fd->isRelationType()) {

                        $relations = array();
                        foreach ($allRelations as $relation) {
                            if ($relation["fieldname"] == $key) {
                                $relations[] = $relation;
                            }
                        }

                        $brick->setValue( $key, $fd->getDataFromResource($relations));
                    }*/
                    if (method_exists($fd, "load")) {
                        // datafield has it's own loader
                        $value = $fd->load($brick);
                        if($value === 0 || !empty($value)) {
                            $brick->setValue($key, $value);
                        }
                    } else {
                        if (is_array($fd->getColumnType())) {
                            $multidata = array();
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . "__" . $fkey] = $result[$key . "__" . $fkey];
                            }
                            $brick->setValue(
                                $key,
                                $fd->getDataFromResource($multidata));

                        } else {
                            $brick->setValue(
                                $key,
                                $fd->getDataFromResource($result[$key]));
                        }
                    }

                }

                $setter = "set" . ucfirst($type);
                $this->model->$setter($brick);

                $values[] = $brick;
            }
        }
        return $values;
    }

    /**
     * @throws Exception
     * @param Object_Concrete $object
     * @return void
     */
    public function delete (Object_Concrete $object) {
        throw new Exception("Not implemented yet");
    }
}
