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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

 
class Object_Concrete_Resource_Mysql_InheritanceHelper {

    const STORE_TABLE = "object_store_";
    const QUERY_TABLE = "object_query_";
    const RELATION_TABLE = "object_relations_";

    public function __construct($classId) {
        $this->db = Pimcore_Resource_Mysql::get();
        $this->fields = array();
        $this->relations = array();
        $this->fieldIds = array();

        $this->storetable = self::STORE_TABLE . $classId;
        $this->querytable = self::QUERY_TABLE . $classId;
        $this->relationtable = self::RELATION_TABLE . $classId;
    }

    public function resetFieldsToCheck() {  
        $this->fields = array();
        $this->relations = array();
        $this->fieldIds = array();         
    }

    public function addFieldToCheck($fieldname) {
        $this->fields[$fieldname] = $fieldname;
        $this->fieldIds[$fieldname] = array();
    }

    public function addRelationToCheck($fieldname, $queryfields = null) {
        if($queryfields == null) {
            $this->relations[$fieldname] = $fieldname;
        } else {
            $this->relations[$fieldname] = $queryfields;
        }

        $this->fieldIds[$fieldname] = array();
    }

    public function doUpdate($oo_id) {
//        p_r($this->fields);
//        p_r($this->relations);

        if(empty($this->fields) && empty($this->relations)) {
            return;
        }

        $this->idTree = array();


        $fields = implode("`,`", $this->fields);
        if(!empty($fields)) {
            $fields = ", `" . $fields . "`";
        }

        $result = $this->db->fetchRow("SELECT oo_id AS id" . $fields . " FROM " . $this->storetable . " WHERE oo_id = " . $oo_id);
        $o = new stdClass();
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



    private function buildTree($currentParentId, $fields) {
        $result = $this->db->fetchAll("SELECT oo_id AS id $fields FROM " . $this->storetable . " a INNER JOIN objects b ON a.oo_id = b.o_id WHERE o_parentId = " . $currentParentId);

        $objects = array();

        foreach($result as $r) {
            $o = new stdClass();
            $o->id = $r['id'];
            $o->values = $r;
            $o->childs = $this->buildTree($r['id'], $fields);

            $objectRelationsResult =  $this->db->fetchAll("SELECT fieldname, count(*) as COUNT FROM " . $this->relationtable . " WHERE src_id = " . $r['id'] . " AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') GROUP BY fieldname;");

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
        return $objects;
    }

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


    private function updateQueryTable($oo_id, $ids, $fieldname) {
        if(!empty($ids)) {
            $value = $this->db->fetchCol("SELECT `$fieldname` FROM " . $this->querytable . " WHERE oo_id = " . $oo_id);
            $this->db->update($this->querytable, array($fieldname => $value[0]), "oo_id IN (" . implode(",", $ids) . ")");
        }
    }

}
