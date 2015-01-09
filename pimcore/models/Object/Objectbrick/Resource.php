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

namespace Pimcore\Model\Object\Objectbrick;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Object\Fieldcollection\Resource {

    /**
     * @param Object\Concrete $object
     * @return array
     */
    public function load(Object\Concrete $object) {
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        $values = array();
        
        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = Object\Objectbrick\Definition::getByKey($type);
            } catch (\Exception $e) {
                continue;
            }
             
            $tableName = $definition->getTableName($object->getClass(), false);
            
            try {
                $results = $this->db->fetchAll("SELECT * FROM ".$tableName." WHERE o_id = ? AND fieldname = ?", array($object->getId(), $this->model->getFieldname()));
            } catch (\Exception $e) {
                $results = array();
            }

            //$allRelations = $this->db->fetchAll("SELECT * FROM object_relations_" . $object->getO_classId() . " WHERE src_id = ? AND ownertype = 'objectbrick' AND ownername = ?", array($object->getO_id(), $this->model->getFieldname()));
            $fieldDefinitions = $definition->getFieldDefinitions();
            $brickClass = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($type);

            foreach ($results as $result) {
                $brick = new $brickClass($object);
                $brick->setFieldname($result["fieldname"]);
                $brick->setObject($object);

                foreach ($fieldDefinitions as $key => $fd) {

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
     * @throws \Exception
     * @param Object\Concrete $object
     * @return void
     */
    public function delete (Object\Concrete $object) {
        throw new \Exception("Not implemented yet");
    }
}
