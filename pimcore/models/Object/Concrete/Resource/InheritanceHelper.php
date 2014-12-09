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

namespace Pimcore\Model\Object\Concrete\Resource;

use Pimcore\Model;
 
class InheritanceHelper {

    /**
     *
     */
    const STORE_TABLE = "object_store_";

    /**
     *
     */
    const QUERY_TABLE = "object_query_";

    /**
     *
     */
    const RELATION_TABLE = "object_relations_";

    /**
     *
     */
    const OBJECTS_TABLE = 'objects';

    /**
     *
     */
    const ID_FIELD = "oo_id";

    /**
     * @param $classId
     * @param null $idField
     * @param null $storetable
     * @param null $querytable
     * @param null $relationtable
     */
    public function __construct($classId, $idField = null, $storetable = null, $querytable = null, $relationtable = null) {
        $this->db = \Pimcore\Resource::get();
        $this->fields = array();
        $this->relations = array();
        $this->fieldIds = array();

        if($storetable == null) {
            $this->storetable = self::STORE_TABLE . $classId;
        } else {
            $this->storetable = $storetable;
        }

        if($querytable == null) {
            $this->querytable = self::QUERY_TABLE . $classId;
        } else {
            $this->querytable = $querytable;
        }

        if($relationtable == null) {
            $this->relationtable = self::RELATION_TABLE . $classId;
        } else {
            $this->relationtable = $relationtable;
        }

        if($idField == null) {
            $this->idField = self::ID_FIELD;
        } else {
            $this->idField = $idField;
        }
    }

    /**
     *
     */
    public function resetFieldsToCheck() {  
        $this->fields = array();
        $this->relations = array();
        $this->fieldIds = array();         
    }

    /**
     * @param $fieldname
     */
    public function addFieldToCheck($fieldname) {
        $this->fields[$fieldname] = $fieldname;
        $this->fieldIds[$fieldname] = array();
    }

    /**
     * @param $fieldname
     * @param null $queryfields
     */
    public function addRelationToCheck($fieldname, $queryfields = null) {
        if($queryfields == null) {
            $this->relations[$fieldname] = $fieldname;
        } else {
            $this->relations[$fieldname] = $queryfields;
        }

        $this->fieldIds[$fieldname] = array();
    }

    /**
     * @param $oo_id
     */
    public function doUpdate($oo_id) {

        if(empty($this->fields) && empty($this->relations)) {
            return;
        }

        $this->idTree = array();


        $fields = implode("`,`", $this->fields);
        if(!empty($fields)) {
            $fields = ", `" . $fields . "`";
        }

        $result = $this->db->fetchRow("SELECT " . $this->idField . " AS id" . $fields . " FROM " . $this->storetable . " WHERE " . $this->idField . " = ?", $oo_id);
        $o = new \stdClass();
        $o->id = $result['id'];
        $o->values = $result;
        $o->childs = $this->buildTree($result['id'], $fields);

        if(!empty($this->fields)) {
            foreach($this->fields as $fieldname) {
                foreach($o->childs as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }

                $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
            }
        }

        if(!empty($this->relations)) {
            foreach($this->relations as $fieldname => $fields) {
                foreach($o->childs as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname);
                }

                if(is_array($fields)) {
                    foreach($fields as $f) {
                        $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $f);
                    }
                } else {
                    $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
                }
            }
        }

    }

    /**
     * @param $currentParentId
     * @param $fields
     * @return array
     */
    private function buildTree($currentParentId, $fields) {
        $result = $this->db->fetchAll("SELECT a." . $this->idField . " AS id $fields FROM " . $this->storetable . " a INNER JOIN objects b ON a." . $this->idField . " = b.o_id WHERE o_parentId = ? GROUP BY a." . $this->idField, $currentParentId);

        $objects = array();

        foreach($result as $r) {
            $o = new \stdClass();
            $o->id = $r['id'];
            $o->values = $r;
            $o->childs = $this->buildTree($r['id'], $fields);

            $objectRelationsResult =  $this->db->fetchAll("SELECT fieldname, count(*) as COUNT FROM " . $this->relationtable . " WHERE src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') GROUP BY fieldname;", $r['id']);

            $objectRelations = array();
            if(!empty($objectRelationsResult)) {
                foreach($objectRelationsResult as $orr) {
                    if($orr['COUNT'] > 0) {
                        $objectRelations[$orr['fieldname']] = $orr['fieldname'];
                    }
                }
                $o->relations = $objectRelations;
            }

            $objects[] = $o;
        }

        //the inheritance shouldn't stop here, when a folder is between two inherited objects
        $folderIds = $this->db->fetchAll("SELECT distinct o_id as id FROM " . self::OBJECTS_TABLE . " where o_parentId = ? and o_type='folder'", $currentParentId);

        if(!empty($folderIds)) {
            foreach($folderIds as $r) {
                $o = new \stdClass();
                $o->id = $r['id'];
                $o->values = $r;
                $o->childs = $this->buildTree($r['id'], $fields);

                $objects[] = $o;
            }
        }

        return $objects;
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    private function getIdsToUpdateForValuefields($currentNode, $fieldname) {
        $value = $currentNode->values[$fieldname];
        if($value == null) {
            $this->fieldIds[$fieldname][] = $currentNode->id;
            if(!empty($currentNode->childs)) {
                foreach($currentNode->childs as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    private function getIdsToUpdateForRelationfields($currentNode, $fieldname) {
        $value = $currentNode->relations[$fieldname];
        if($value == null) {
            $this->fieldIds[$fieldname][] = $currentNode->id;
            if(!empty($currentNode->childs)) {
                foreach($currentNode->childs as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param $oo_id
     * @param $ids
     * @param $fieldname
     * @throws \Zend_Db_Adapter_Exception
     */
    private function updateQueryTable($oo_id, $ids, $fieldname) {
        if(!empty($ids)) {
            $value = $this->db->fetchCol("SELECT `$fieldname` FROM " . $this->querytable . " WHERE " . $this->idField . " = ?", $oo_id);
            $this->db->update($this->querytable, array($fieldname => $value[0]), $this->idField . " IN (" . implode(",", $ids) . ")");
        }
    }
}
