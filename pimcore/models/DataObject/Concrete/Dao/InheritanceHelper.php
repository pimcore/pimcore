<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Concrete\Dao;

use Pimcore\Db\Connection;
use Pimcore\Model\DataObject;

class InheritanceHelper
{
    const STORE_TABLE = 'object_store_';

    const QUERY_TABLE = 'object_query_';

    const RELATION_TABLE = 'object_relations_';

    const OBJECTS_TABLE = 'objects';

    const ID_FIELD = 'oo_id';

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var array
     */
    protected $fieldIds = [];

    /**
     * @var array
     */
    protected $deletionFieldIds = [];

    /**
     * @var array
     */
    protected $fieldDefinitions = [];

    /**
     * @var
     */
    protected $classId;

    /**
     * @var bool
     */
    protected static $useRuntimeCache = false;

    /**
     * @var array
     */
    protected $treeIds = [];

    /**
     * @var array
     */
    protected static $runtimeCache = [];

    /**
     * @param $classId
     * @param null $idField
     * @param null $storetable
     * @param null $querytable
     * @param null $relationtable
     */
    public function __construct($classId, $idField = null, $storetable = null, $querytable = null, $relationtable = null)
    {
        $this->db = \Pimcore\Db::get();
        $this->classId = $classId;

        if ($storetable == null) {
            $this->storetable = self::STORE_TABLE . $classId;
        } else {
            $this->storetable = $storetable;
        }

        if ($querytable == null) {
            $this->querytable = self::QUERY_TABLE . $classId;
        } else {
            $this->querytable = $querytable;
        }

        if ($relationtable == null) {
            $this->relationtable = self::RELATION_TABLE . $classId;
        } else {
            $this->relationtable = $relationtable;
        }

        if ($idField == null) {
            $this->idField = self::ID_FIELD;
        } else {
            $this->idField = $idField;
        }
    }

    /**
     * Enable or disable the runtime cache
     *
     * @param bool $value
     */
    public static function setUseRuntimeCache($value)
    {
        self::$useRuntimeCache = $value;
    }

    /**
     * clear the runtime cache
     */
    public static function clearRuntimeCache()
    {
        self::$runtimeCache = [];
    }

    public function resetFieldsToCheck()
    {
        $this->fields = [];
        $this->relations = [];
        $this->fieldIds = [];
        $this->deletionFieldIds = [];
        $this->fieldDefinitions = [];
    }

    /**
     * @param $fieldname
     * @param $fieldDefinition
     */
    public function addFieldToCheck($fieldname, $fieldDefinition)
    {
        $this->fields[$fieldname] = $fieldname;
        $this->fieldIds[$fieldname] = [];
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    /**
     * @param $fieldname
     * @param $fieldDefinition
     * @param null $queryfields
     */
    public function addRelationToCheck($fieldname, $fieldDefinition, $queryfields = null)
    {
        if ($queryfields == null) {
            $this->relations[$fieldname] = $fieldname;
        } else {
            $this->relations[$fieldname] = $queryfields;
        }

        $this->fieldIds[$fieldname] = [];
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    /**
     * @param $oo_id
     * @param bool $createMissingChildrenRows
     *
     * @throws \Exception
     */
    public function doUpdate($oo_id, $createMissingChildrenRows = false)
    {
        if (empty($this->fields) && empty($this->relations) && !$createMissingChildrenRows) {
            return;
        }

        $fields = implode('`,`', $this->fields);
        if (!empty($fields)) {
            $fields = ', `' . $fields . '`';
        }

        $result = $this->db->fetchRow('SELECT ' . $this->idField . ' AS id' . $fields . ' FROM ' . $this->storetable . ' WHERE ' . $this->idField . ' = ?', $oo_id);
        $o = new \stdClass();
        $o->id = $result['id'];
        $o->values = $result;

        $this->treeIds = [];
        $o->childs = $this->buildTree($result['id'], $fields);

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldname) {
                foreach ($o->childs as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }

                $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
            }
        }

        if (!empty($this->relations)) {
            foreach ($this->relations as $fieldname => $fields) {
                foreach ($o->childs as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname);
                }

                if (is_array($fields)) {
                    foreach ($fields as $f) {
                        $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $f);
                    }
                } else {
                    $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
                }
            }
        }

        // check for missing entries which can occur in object bricks and localized fields
        // this happens especially in the following case:
        // parent object has no brick, add child to parent, add brick to parent & click save
        // without this code there will not be an entry in the query table for the child object
        if ($createMissingChildrenRows) {
            if (!empty($this->treeIds)) {
                $idsInTable = $this->db->fetchCol('SELECT ' . $this->idField . ' FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' IN (' . implode(',', $this->treeIds) . ')');

                $diff = array_diff($this->treeIds, $idsInTable);

                // create entries for children that don't have an entry yet
                $originalEntry = $this->db->fetchRow('SELECT * FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' = ?', $oo_id);
                foreach ($diff as $id) {
                    $originalEntry[$this->idField] = $id;
                    $this->db->insert($this->querytable, $originalEntry);
                }
            }
        }
    }

    /** Currently solely used for object bricks. If a brick is removed, this info must be propagated to all
     * child elements.
     *
     * @param $objectId
     */
    public function doDelete($objectId)
    {
        // NOT FINISHED - NEEDS TO BE COMPLETED !!!

        // as a first step, build an ID list of all child elements that are affected. Stop at the level
        // which has a non-empty value.

        $fields = implode('`,`', $this->fields);
        if (!empty($fields)) {
            $fields = ', `' . $fields . '`';
        }

        $o = new \stdClass();
        $o->id = $objectId;
        $o->values = [];
        $o->childs = $this->buildTree($objectId, $fields);

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldname) {
                foreach ($o->childs as $c) {
                    $this->getIdsToCheckForDeletionForValuefields($c, $fieldname);
                }
                $this->updateQueryTableOnDelete($objectId, $this->deletionFieldIds[$fieldname], $fieldname);
            }
        }

        if (!empty($this->relations)) {
            foreach ($this->relations as $fieldname => $fields) {
                foreach ($o->childs as $c) {
                    $this->getIdsToCheckForDeletionForRelationfields($c, $fieldname);
                }
                $this->updateQueryTableOnDelete($objectId, $this->deletionFieldIds[$fieldname], $fieldname);
            }
        }

        $affectedIds = [];

        foreach ($this->deletionFieldIds as $fieldname => $ids) {
            foreach ($ids as $id) {
                $affectedIds[$id] = $id;
            }
        }

        $systemFields = ['o_id', 'fieldname'];

        $toBeRemovedItemIds = [];

        // now iterate over all affected elements and check if the object even has a brick. If it doesn't, then
        // remove the query row entirely ...
        if ($affectedIds) {
            $objectsWithBrickIds = [];
            $objectsWithBricks = $this->db->fetchAll('SELECT ' . $this->idField . ' FROM ' . $this->storetable . ' WHERE ' . $this->idField . ' IN (' . implode(',', $affectedIds) . ')');
            foreach ($objectsWithBricks as $item) {
                $objectsWithBrickIds[] = $item['id'];
            }

            $currentQueryItems = $this->db->fetchAll('SELECT * FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' IN (' . implode(',', $affectedIds) . ')');

            foreach ($currentQueryItems as $queryItem) {
                $toBeRemoved = true;
                foreach ($queryItem as $fieldname => $value) {
                    if (!in_array($fieldname, $systemFields)) {
                        if (!is_null($value)) {
                            $toBeRemoved = false;
                            break;
                        }
                    }
                }
                if ($toBeRemoved) {
                    if (!in_array($queryItem['o_id'], $objectsWithBrickIds)) {
                        $toBeRemovedItemIds[] = $queryItem['o_id'];
                    }
                }
            }
        }

        if ($toBeRemovedItemIds) {
            $this->db->deleteWhere($this->querytable, $this->idField . ' IN (' . implode(',', $toBeRemovedItemIds) . ')');
        }
    }

    /**
     * @param $currentParentId
     * @param string $fields
     * @param null $parentIdGroups
     *
     * @return array
     */
    protected function buildTree($currentParentId, $fields = '', $parentIdGroups = null)
    {
        $objects = [];

        if (!$parentIdGroups) {
            $object = DataObject::getById($currentParentId);
            $query = "SELECT b.o_id AS id $fields, b.o_type AS type, b.o_classId AS classId, b.o_parentId AS parentId, o_path, o_key FROM objects b LEFT JOIN " . $this->storetable . ' a ON b.o_id = a.' . $this->idField . ' WHERE o_path LIKE '.\Pimcore\Db::get()->quote($object->getRealFullPath().'/%') . ' GROUP BY b.o_id ORDER BY LENGTH(o_path) ASC';

            if (self::$useRuntimeCache) {
                $queryCacheKey = 'tree_'.md5($query);
                $parentIdGroups = self::$runtimeCache[$queryCacheKey];
            }

            if (!$parentIdGroups) {
                $result = $this->db->fetchAll($query);

                // group the results together based on the parent id's
                $parentIdGroups = [];
                foreach ($result as $r) {
                    $r['fullpath'] = $r['o_path'].$r['o_key'];
                    if (!isset($parentIdGroups[$r['parentId']])) {
                        $parentIdGroups[$r['parentId']] = [];
                    }

                    $parentIdGroups[$r['parentId']][] = $r;
                }
                if (self::$useRuntimeCache) {
                    self::$runtimeCache[$queryCacheKey] = $parentIdGroups;
                }
            }
        }

        if (isset($parentIdGroups[$currentParentId])) {
            foreach ($parentIdGroups[$currentParentId] as $r) {
                $o = new \stdClass();
                $o->id = $r['id'];
                $o->values = $r;
                $o->type = $r['type'];
                $o->classId = $r['classId'];
                $o->childs = $this->buildTree($r['id'], $fields, $parentIdGroups);

                if ($o->classId == $this->classId) {
                    $this->treeIds[] = $o->id;
                }

                $objects[] = $o;
            }
        }

        return $objects;
    }

    /**
     * @param $node
     *
     * @return mixed
     */
    protected function getRelationsForNode($node)
    {

        // if the relations are already set, skip here
        if (isset($node->relations)) {
            return $node;
        }

        $objectRelationsResult =  $this->db->fetchAll('SELECT fieldname, count(*) as COUNT FROM ' . $this->relationtable . " WHERE src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') GROUP BY fieldname;", [$node->id]);

        $objectRelations = [];
        if (!empty($objectRelationsResult)) {
            foreach ($objectRelationsResult as $orr) {
                if ($orr['COUNT'] > 0) {
                    $objectRelations[$orr['fieldname']] = $orr['fieldname'];
                }
            }
            $node->relations = $objectRelations;
        } else {
            $node->relations = [];
        }

        return $node;
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToCheckForDeletionForValuefields($currentNode, $fieldname)
    {
        $value = $currentNode->values[$fieldname];

        if (!$this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            return;
        }

        $this->deletionFieldIds[$fieldname][] = $currentNode->id;

        if (!empty($currentNode->childs)) {
            foreach ($currentNode->childs as $c) {
                $this->getIdsToCheckForDeletionForValuefields($c, $fieldname);
            }
        }
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToUpdateForValuefields($currentNode, $fieldname)
    {
        $value = $currentNode->values[$fieldname];
        if ($this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            $this->fieldIds[$fieldname][] = $currentNode->id;
            if (!empty($currentNode->childs)) {
                foreach ($currentNode->childs as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToCheckForDeletionForRelationfields($currentNode, $fieldname)
    {
        $this->getRelationsForNode($currentNode);
        if (isset($currentNode->relations[$fieldname])) {
            $value = $currentNode->relations[$fieldname];
        } else {
            $value = null;
        }
        if (!$this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            return;
        }
        $this->deletionFieldIds[$fieldname][] = $currentNode->id;

        if (!empty($currentNode->childs)) {
            foreach ($currentNode->childs as $c) {
                $this->getIdsToCheckForDeletionForRelationfields($c, $fieldname);
            }
        }
    }

    /**
     * @param $currentNode
     * @param $fieldname
     */
    protected function getIdsToUpdateForRelationfields($currentNode, $fieldname)
    {
        $this->getRelationsForNode($currentNode);
        if (isset($currentNode->relations[$fieldname])) {
            $value = $currentNode->relations[$fieldname];
        } else {
            $value = null;
        }
        if ($this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            $this->fieldIds[$fieldname][] = $currentNode->id;
            if (!empty($currentNode->childs)) {
                foreach ($currentNode->childs as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param $oo_id
     * @param $ids
     * @param $fieldname
     *
     * @throws \Exception
     */
    protected function updateQueryTable($oo_id, $ids, $fieldname)
    {
        if (!empty($ids)) {
            $value = $this->db->fetchOne("SELECT `$fieldname` FROM " . $this->querytable . ' WHERE ' . $this->idField . ' = ?', $oo_id);
            $this->db->updateWhere($this->querytable, [$fieldname => $value], $this->idField . ' IN (' . implode(',', $ids) . ')');
        }
    }

    /**
     * @param $oo_id
     * @param $ids
     * @param $fieldname
     */
    protected function updateQueryTableOnDelete($oo_id, $ids, $fieldname)
    {
        if (!empty($ids)) {
            $value = null;
            $this->db->updateWhere($this->querytable, [$fieldname => $value], $this->idField . ' IN (' . implode(',', $ids) . ')');
        }
    }
}
