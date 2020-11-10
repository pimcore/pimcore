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

use Pimcore\Db\ConnectionInterface;
use Pimcore\Model\DataObject;

class InheritanceHelper
{
    const STORE_TABLE = 'object_store_';

    const QUERY_TABLE = 'object_query_';

    const RELATION_TABLE = 'object_relations_';

    const ID_FIELD = 'oo_id';

    const DEFAULT_QUERY_ID_COLUMN = 'ooo_id';

    /**
     * @var ConnectionInterface
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
     * @var string
     */
    protected $classId;

    /**
     * @var bool
     */
    protected static $useRuntimeCache = false;

    /**
     * @var bool
     */
    protected $childFound;

    /**
     * @var array
     */
    protected static $runtimeCache = [];

    /**
     * @var string|null
     */
    protected $storetable;

    /**
     * @var string|null
     */
    protected $querytable;

    /**
     * @var null|string
     */
    protected $relationtable;

    /**
     * @var null|string
     */
    protected $idField;

    /**
     * @var null|string
     */
    protected $queryIdField;

    /**
     * @param string $classId
     * @param string|null $idField
     * @param string|null $storetable
     * @param string|null $querytable
     * @param string|null $relationtable
     * @param string|null $queryIdField
     */
    public function __construct($classId, $idField = null, $storetable = null, $querytable = null, $relationtable = null, $queryIdField = null)
    {
        $this->db = \Pimcore\Db::get();
        $this->classId = $classId;

        if ($storetable === null) {
            $this->storetable = self::STORE_TABLE . $classId;
        } else {
            $this->storetable = $storetable;
        }

        if ($querytable === null) {
            $this->querytable = self::QUERY_TABLE . $classId;
        } else {
            $this->querytable = $querytable;
        }

        if ($relationtable === null) {
            $this->relationtable = self::RELATION_TABLE . $classId;
        } else {
            $this->relationtable = $relationtable;
        }

        if ($idField === null) {
            $this->idField = self::ID_FIELD;
        } else {
            $this->idField = $idField;
        }

        if ($queryIdField === null) {
            $this->queryIdField = self::DEFAULT_QUERY_ID_COLUMN;
        } else {
            $this->queryIdField = $queryIdField;
        }
    }

    /**
     * Enable or disable the runtime cache. Default value is off.
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
        $this->childFound = false;
    }

    /**
     * @param string $fieldname
     * @param DataObject\ClassDefinition\Data $fieldDefinition
     */
    public function addFieldToCheck($fieldname, $fieldDefinition)
    {
        $this->fields[$fieldname] = $fieldname;
        $this->fieldIds[$fieldname] = [];
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    /**
     * @param string $fieldname
     * @param DataObject\ClassDefinition\Data $fieldDefinition
     * @param array|null $queryfields
     */
    public function addRelationToCheck($fieldname, $fieldDefinition, $queryfields = null)
    {
        if ($queryfields === null) {
            $this->relations[$fieldname] = $fieldname;
        } else {
            $this->relations[$fieldname] = $queryfields;
        }

        $this->fieldIds[$fieldname] = [];
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    /**
     * @param int $oo_id
     * @param bool $createMissingChildrenRows
     * @param array $params
     *
     * @throws \Exception
     */
    public function doUpdate($oo_id, $createMissingChildrenRows = false, $params = [])
    {
        if (empty($this->fields) && empty($this->relations) && !$createMissingChildrenRows) {
            return;
        }

        // only build the tree if there are fields to check
        if (!empty($this->fields) || !empty($this->relations)) {
            $fields = implode('`,`', $this->fields);
            if (!empty($fields)) {
                $fields = ', `' . $fields . '`';
            }

            $result = $this->db->fetchRow('SELECT ' . $this->idField . ' AS id' . $fields . ' FROM ' . $this->storetable . ' WHERE ' . $this->idField . ' = ?', $oo_id);
            $o = [
                'id' => $result['id'],
                'values' => $result ?? null,
            ];

            $o['children'] = $this->buildTree($result['id'], $fields, null, $params);

            if (!empty($this->fields)) {
                foreach ($this->fields as $fieldname) {
                    foreach ($o['children'] as $c) {
                        $this->getIdsToUpdateForValuefields($c, $fieldname);
                    }

                    $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
                    // not needed anymore
                    unset($this->fieldIds[$fieldname]);
                }
            }

            if (!empty($this->relations)) {
                foreach ($this->relations as $fieldname => $fields) {
                    foreach ($o['children'] as $c) {
                        $this->getIdsToUpdateForRelationfields($c, $fieldname, $params);
                    }

                    if (is_array($fields)) {
                        foreach ($fields as $f) {
                            $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $f);
                        }
                    } else {
                        $this->updateQueryTable($oo_id, $this->fieldIds[$fieldname], $fieldname);
                    }
                    // not needed anymore
                    unset($this->fieldIds[$fieldname]);
                }
            }
        }

        // check for missing entries which can occur in object bricks and localized fields
        // this happens especially in the following case:
        // parent object has no brick, add child to parent, add brick to parent & click save
        // without this code there will not be an entry in the query table for the child object
        if ($createMissingChildrenRows) {
            // if we have a tree (which is the case if either fields or relations is configured) then
            // rely on the childFound flag
            // without a tree we have to do the select anyway
            if ($this->childFound || (empty($this->fields) && empty($this->relations))) {
                $object = DataObject\Concrete::getById($oo_id);
                $classId = $object->getClassId();

                $query = 'SELECT b.o_id AS id '
                    . ' FROM objects b LEFT JOIN ' . $this->querytable . ' a ON b.o_id = a.' . $this->idField
                    . ' WHERE b.o_classId = ' . $this->db->quote($classId)
                    . ' AND o_path LIKE '. $this->db->quote($this->db->escapeLike($object->getRealFullPath()).'/%')
                    . ' AND ISNULL(a.' . $this->queryIdField . ')';
                $missingIds = $this->db->fetchCol($query);

                // create entries for children that don't have an entry yet
                $originalEntry = $this->db->fetchRow('SELECT * FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' = ?', $oo_id);
                foreach ($missingIds as $id) {
                    $originalEntry[$this->idField] = $id;
                    $this->db->insert($this->querytable, $originalEntry);
                }
            }
        }
    }

    /**
     * Currently solely used for object bricks. If a brick is removed, this info must be propagated to all
     * child elements.
     *
     * @param int $objectId
     * @param array $params
     */
    public function doDelete($objectId, $params = [])
    {
        // NOT FINISHED - NEEDS TO BE COMPLETED !!!

        // as a first step, build an ID list of all child elements that are affected. Stop at the level
        // which has a non-empty value.

        $fields = implode('`,`', $this->fields);
        if (!empty($fields)) {
            $fields = ', `' . $fields . '`';
        }

        $o = [
            'id' => $objectId,
            'children' => $this->buildTree($objectId, $fields, null, $params),
        ];

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldname) {
                foreach ($o['children'] as $c) {
                    $this->getIdsToCheckForDeletionForValuefields($c, $fieldname);
                }
                if (isset($this->deletionFieldIds[$fieldname])) {
                    $this->updateQueryTableOnDelete($objectId, $this->deletionFieldIds[$fieldname], $fieldname);
                }
            }
        }

        if (!empty($this->relations)) {
            foreach ($this->relations as $fieldname => $fields) {
                foreach ($o['children'] as $c) {
                    $this->getIdsToCheckForDeletionForRelationfields($c, $fieldname);
                }
                if (isset($this->deletionFieldIds[$fieldname])) {
                    $this->updateQueryTableOnDelete($objectId, $this->deletionFieldIds[$fieldname], $fieldname);
                }
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
                $objectsWithBrickIds[] = $item[$this->idField];
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
     * @param array $result
     * @param string $language
     * @param string $column
     *
     * @return array
     */
    protected function filterResultByLanguage($result, $language, $column)
    {
        $filteredResult = [];
        foreach ($result as $row) {
            $rowId = $row['id'];
            if ((!isset($filteredResult[$rowId]) && $row[$column] === null) || $row[$column] === $language) {
                $filteredResult[$rowId] = $row;
            }
        }

        return array_values($filteredResult);
    }

    /**
     * @param int $currentParentId
     * @param string $fields
     * @param array|null $parentIdGroups
     * @param array $params
     *
     * @return array
     */
    protected function buildTree($currentParentId, $fields = '', $parentIdGroups = null, $params = [])
    {
        $objects = [];

        if (!$parentIdGroups) {
            $object = DataObject::getById($currentParentId);
            if (isset($params['language'])) {
                $query = "SELECT a.language as language, b.o_id AS id $fields, b.o_classId AS classId, b.o_parentId AS parentId FROM objects b LEFT JOIN " . $this->storetable . ' a ON b.o_id = a.' . $this->idField . ' WHERE o_path LIKE ' . $this->db->quote($this->db->escapeLike($object->getRealFullPath()) . '/%')
                    . ' HAVING `language` = "' . $params['language'] . '" OR ISNULL(`language`)'
                    . ' ORDER BY LENGTH(o_path) ASC';
            } else {
                $query = "SELECT b.o_id AS id $fields, b.o_classId AS classId, b.o_parentId AS parentId FROM objects b LEFT JOIN " . $this->storetable . ' a ON b.o_id = a.' . $this->idField . ' WHERE o_path LIKE ' . $this->db->quote($this->db->escapeLike($object->getRealFullPath()).'/%') . ' GROUP BY b.o_id ORDER BY LENGTH(o_path) ASC';
            }
            $queryCacheKey = 'tree_'.md5($query);

            if (self::$useRuntimeCache) {
                $parentIdGroups = self::$runtimeCache[$queryCacheKey] ?? null;
            }

            if (!$parentIdGroups) {
                $result = $this->db->fetchAll($query);

                if (isset($params['language'])) {
                    $result = $this->filterResultByLanguage($result, $params['language'], 'language');
                }

                // group the results together based on the parent id's
                $parentIdGroups = [];
                $rowCount = count($result);
                for ($rowIdx = 0; $rowIdx < $rowCount; ++$rowIdx) {
                    // assign the reference
                    $rowData = &$result[$rowIdx];

                    if (!isset($parentIdGroups[$rowData['parentId']])) {
                        $parentIdGroups[$rowData['parentId']] = [];
                    }

                    $parentIdGroups[$rowData['parentId']][] = &$rowData;
                }
                if (self::$useRuntimeCache) {
                    self::$runtimeCache[$queryCacheKey] = $parentIdGroups;
                }
            }
        }

        if (isset($parentIdGroups[$currentParentId])) {
            $childData = $parentIdGroups[$currentParentId];
            $childCount = count($childData);
            for ($childIdx = 0; $childIdx < $childCount; ++$childIdx) {
                $rowData = &$childData[$childIdx];

                if ($rowData['classId'] == $this->classId) {
                    $this->childFound = true;
                }

                $id = $rowData['id'];

                $o = [
                    'id' => $id,
                    'children' => $this->buildTree($id, $fields, $parentIdGroups, $params),
                    'values' => $rowData,
                ];

                $objects[] = $o;
            }
        }

        return $objects;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    protected function getRelationCondition($params = [])
    {
        $condition = '';
        $parts = [];

        if (isset($params['inheritanceRelationContext'])) {
            foreach ($params['inheritanceRelationContext'] as $key => $value) {
                $parts[] = $this->db->quoteIdentifier($key) . ' = ' . $this->db->quote($value);
            }
            $condition = implode(' AND ', $parts);
        }
        if (count($parts) > 0) {
            $condition = $condition . ' AND ';
        }

        return $condition;
    }

    /**
     * @param array $node
     * @param array $params
     *
     * @return mixed
     */
    protected function getRelationsForNode(&$node, $params = [])
    {
        // if the relations are already set, skip here
        if (isset($node['relations'])) {
            return $node;
        }

        $relationCondition = $this->getRelationCondition($params);

        if (isset($params['language'])) {
            $objectRelationsResult = $this->db->fetchAll('SELECT src_id as id, fieldname, position, count(*) as COUNT FROM ' . $this->relationtable . ' WHERE ' . $relationCondition . " src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') "
                . ' GROUP BY position, fieldname'
                . ' HAVING `position` = "' . $params['language'] . '" OR ISNULL(`position`)', [$node['id']]);
            $objectRelationsResult = $this->filterResultByLanguage($objectRelationsResult, $params['language'], 'position');
        } else {
            $objectRelationsResult = $this->db->fetchAll('SELECT fieldname, count(*) as COUNT FROM ' . $this->relationtable . ' WHERE ' . $relationCondition . " src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') GROUP BY fieldname;", [$node['id']]);
        }

        $objectRelations = [];
        if (!empty($objectRelationsResult)) {
            foreach ($objectRelationsResult as $orr) {
                if ($orr['COUNT'] > 0) {
                    $objectRelations[$orr['fieldname']] = $orr['fieldname'];
                }
            }
            $node['relations'] = $objectRelations;
        } else {
            $node['relations'] = [];
        }

        return $node;
    }

    /**
     * @param array $currentNode
     * @param string $fieldname
     * @param array $params
     */
    protected function getIdsToCheckForDeletionForValuefields($currentNode, $fieldname, $params = [])
    {
        $value = $currentNode['values'][$fieldname] ?? null;

        if (!$this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            return;
        }

        $this->deletionFieldIds[$fieldname][] = $currentNode['id'];

        if (!empty($currentNode['children'])) {
            foreach ($currentNode['children'] as $c) {
                $this->getIdsToCheckForDeletionForValuefields($c, $fieldname, $params);
            }
        }
    }

    /**
     * @param array $currentNode
     * @param string $fieldname
     */
    protected function getIdsToUpdateForValuefields($currentNode, $fieldname)
    {
        $value = $currentNode['values'][$fieldname] ?? null;
        if ($this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            $this->fieldIds[$fieldname][] = $currentNode['id'];
            if (!empty($currentNode['children'])) {
                foreach ($currentNode['children'] as $c) {
                    $this->getIdsToUpdateForValuefields($c, $fieldname);
                }
            }
        }
    }

    /**
     * @param array $currentNode
     * @param string $fieldname
     */
    protected function getIdsToCheckForDeletionForRelationfields($currentNode, $fieldname)
    {
        $this->getRelationsForNode($currentNode);
        if (isset($currentNode['relations'][$fieldname])) {
            $value = $currentNode['relations'][$fieldname];
        } else {
            $value = null;
        }
        if (!$this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            return;
        }
        $this->deletionFieldIds[$fieldname][] = $currentNode['id'];

        if (!empty($currentNode['children'])) {
            foreach ($currentNode['children'] as $c) {
                $this->getIdsToCheckForDeletionForRelationfields($c, $fieldname);
            }
        }
    }

    /**
     * @param array $currentNode
     * @param string $fieldname
     * @param array $params
     */
    protected function getIdsToUpdateForRelationfields($currentNode, $fieldname, $params = [])
    {
        $this->getRelationsForNode($currentNode, $params);
        if (isset($currentNode['relations'][$fieldname])) {
            $value = $currentNode['relations'][$fieldname];
        } else {
            $value = null;
        }
        if ($this->fieldDefinitions[$fieldname]->isEmpty($value)) {
            $this->fieldIds[$fieldname][] = $currentNode['id'];
            if (!empty($currentNode['children'])) {
                foreach ($currentNode['children'] as $c) {
                    $this->getIdsToUpdateForRelationfields($c, $fieldname, $params);
                }
            }
        }
    }

    /**
     * @param int $oo_id
     * @param array $ids
     * @param string $fieldname
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
     * @param int $oo_id
     * @param array $ids
     * @param string $fieldname
     */
    protected function updateQueryTableOnDelete($oo_id, $ids, $fieldname)
    {
        if (!empty($ids)) {
            $value = null;
            $this->db->updateWhere($this->querytable, [$fieldname => $value], $this->idField . ' IN (' . implode(',', $ids) . ')');
        }
    }
}
