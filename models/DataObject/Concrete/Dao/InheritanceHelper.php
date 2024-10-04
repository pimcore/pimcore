<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Concrete\Dao;

use Doctrine\DBAL\Connection;
use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
class InheritanceHelper
{
    const STORE_TABLE = 'object_store_';

    const QUERY_TABLE = 'object_query_';

    const RELATION_TABLE = 'object_relations_';

    const ID_FIELD = 'oo_id';

    const DEFAULT_QUERY_ID_COLUMN = 'ooo_id';

    protected Connection $db;

    protected array $fields = [];

    protected array $relations = [];

    protected array $fieldIds = [];

    protected array $deletionFieldIds = [];

    protected array $fieldDefinitions = [];

    protected string $classId;

    protected static bool $useRuntimeCache = false;

    protected bool $childFound = false;

    protected static array $runtimeCache = [];

    protected ?string $storetable = null;

    protected ?string $querytable = null;

    protected ?string $relationtable = null;

    protected ?string $idField = null;

    protected ?string $queryIdField = null;

    public function __construct(string $classId, string $idField = null, string $storetable = null, string $querytable = null, string $relationtable = null, string $queryIdField = null)
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
     */
    public static function setUseRuntimeCache(bool $value): void
    {
        self::$useRuntimeCache = $value;
    }

    /**
     * clear the runtime cache
     */
    public static function clearRuntimeCache(): void
    {
        self::$runtimeCache = [];
    }

    public function resetFieldsToCheck(): void
    {
        $this->fields = [];
        $this->relations = [];
        $this->fieldIds = [];
        $this->deletionFieldIds = [];
        $this->fieldDefinitions = [];
        $this->childFound = false;
    }

    public function addFieldToCheck(string $fieldname, DataObject\ClassDefinition\Data $fieldDefinition): void
    {
        $this->fields[$fieldname] = $fieldname;
        $this->fieldIds[$fieldname] = [];
        $this->fieldDefinitions[$fieldname] = $fieldDefinition;
    }

    public function addRelationToCheck(string $fieldname, DataObject\ClassDefinition\Data $fieldDefinition, array $queryfields = null): void
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
     *
     * @throws Exception
     */
    public function doUpdate(int $oo_id, bool $createMissingChildrenRows = false, array $params = []): void
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

            $result = $this->db->fetchAssociative('SELECT ' . $this->idField . ' AS id' . $fields . ' FROM ' . $this->storetable . ' WHERE ' . $this->idField . ' = ?', [$oo_id]);
            $o = [
                'id' => $result['id'],
                'values' => $result,
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

                $query = "
                    WITH RECURSIVE cte(id, classId) as (
                        SELECT c.id AS id, c.classId AS classId
                        FROM objects c
                        WHERE c.parentid = {$object->getId()}
                        UNION ALL
                        SELECT p.id AS id, p.classId AS classId
                        FROM objects p
                        INNER JOIN cte on (p.parentid = cte.id)
                    ) select x.id
                    FROM cte x
                    LEFT JOIN {$this->querytable} l on (x.id = l.{$this->idField})
                    where x.classId = {$this->db->quote($classId)}
                    AND l.{$this->queryIdField} is null;
                ";

                $missingIds = $this->db->fetchFirstColumn($query);

                // create entries for children that don't have an entry yet
                $originalEntry = Helper::quoteDataIdentifiers($this->db, $this->db->fetchAssociative('SELECT * FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' = ?', [$oo_id]));

                foreach ($missingIds as $id) {
                    $originalEntry[$this->db->quoteIdentifier($this->idField)] = $id;
                    $this->db->insert($this->db->quoteIdentifier($this->querytable), $originalEntry);
                }
            }
        }
    }

    /**
     * Currently solely used for object bricks. If a brick is removed, this info must be propagated to all
     * child elements.
     *
     */
    public function doDelete(int $objectId, array $params = []): void
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

        $systemFields = ['id', 'fieldname'];

        $toBeRemovedItemIds = [];

        // now iterate over all affected elements and check if the object even has a brick. If it doesn't, then
        // remove the query row entirely ...
        if ($affectedIds) {
            $objectsWithBrickIds = [];
            $objectsWithBricks = $this->db->fetchAllAssociative('SELECT ' . $this->idField . ' FROM ' . $this->storetable . ' WHERE ' . $this->idField . ' IN (' . implode(',', $affectedIds) . ')');
            foreach ($objectsWithBricks as $item) {
                $objectsWithBrickIds[] = $item[$this->idField];
            }

            $currentQueryItems = $this->db->fetchAllAssociative('SELECT * FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' IN (' . implode(',', $affectedIds) . ')');

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
                    if (!in_array($queryItem['id'], $objectsWithBrickIds)) {
                        $toBeRemovedItemIds[] = $queryItem['id'];
                    }
                }
            }
        }

        if ($toBeRemovedItemIds) {
            $this->db->executeStatement('DELETE FROM ' . $this->querytable . ' WHERE ' . $this->idField . ' IN (' . implode(',', $toBeRemovedItemIds) . ')');
        }
    }

    protected function filterResultByLanguage(array $result, string $language, string $column): array
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

    protected function buildTree(int $currentParentId, string $fields = '', array $parentIdGroups = null, array $params = []): array
    {
        $objects = [];
        $storeTable = $this->storetable;
        $idfield = $this->idField;

        if (!$parentIdGroups) {
            $object = DataObject::getById($currentParentId);
            if (isset($params['language'])) {
                $language = $params['language'];

                $query = "
                WITH RECURSIVE cte(id, classId, parentId, path) as (
                    SELECT c.id AS id, c.classId AS classId, c.parentid AS parentId, c.path as `path`
                    FROM objects c
                    WHERE c.parentid = $currentParentId
                    UNION ALL
                    SELECT p.id AS id, p.classId AS classId, p.parentid AS parentId, p.path as `path`
                    FROM objects p
                    INNER JOIN cte on (p.parentid = cte.id)
                ) SELECT l.language AS `language`,
                         x.id AS id,
                         x.classId AS classId,
                         x.parentId AS parentId
                         $fields
                    FROM cte x
                    LEFT JOIN $storeTable l ON x.id = l.$idfield
                   WHERE COALESCE(`language`, " . $this->db->quote($language) . ') = ' . $this->db->quote($language) .
                   ' ORDER BY x.path ASC';
            } else {
                $query = "
                    WITH RECURSIVE cte(id, classId, parentId, path) as (
                        SELECT c.id AS id, c.classId AS classId, c.parentid AS parentId, c.path as `path`
                        FROM objects c
                        WHERE c.parentid = $currentParentId
                        UNION ALL
                        SELECT p.id AS id, p.classId AS classId, p.parentid AS parentId, p.path as `path`
                        FROM objects p
                        INNER JOIN cte on (p.parentid = cte.id)
                    )	SELECT x.id AS id,
                               x.classId AS classId,
                               x.parentId AS parentId
                               $fields
                        FROM cte x
                        LEFT JOIN $storeTable a ON x.id = a.$idfield
                        GROUP BY x.id
                        ORDER BY x.path ASC";
            }
            $queryCacheKey = 'tree_'.md5($query);

            if (self::$useRuntimeCache) {
                $parentIdGroups = self::$runtimeCache[$queryCacheKey] ?? null;
            }

            if (!$parentIdGroups) {
                $result = $this->db->fetchAllAssociative($query);

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

    protected function getRelationCondition(array $params = []): string
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

    protected function getRelationsForNode(array &$node, array $params = []): array
    {
        // if the relations are already set, skip here
        if (isset($node['relations'])) {
            return $node;
        }

        $relationCondition = $this->getRelationCondition($params);

        if (isset($params['language'])) {
            $objectRelationsResult = $this->db->fetchAllAssociative('SELECT src_id as id, fieldname, position, count(*) as COUNT FROM ' . $this->relationtable . ' WHERE ' . $relationCondition . " src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') "
                . ' GROUP BY position, fieldname'
                . ' HAVING `position` = "' . $params['language'] . '" OR ISNULL(`position`)', [$node['id']]);
            $objectRelationsResult = $this->filterResultByLanguage($objectRelationsResult, $params['language'], 'position');
        } else {
            $objectRelationsResult = $this->db->fetchAllAssociative('SELECT fieldname, count(*) as COUNT FROM ' . $this->relationtable . ' WHERE ' . $relationCondition . " src_id = ? AND fieldname IN('" . implode("','", array_keys($this->relations)) . "') GROUP BY fieldname;", [$node['id']]);
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

    protected function getIdsToCheckForDeletionForValuefields(array $currentNode, string $fieldname, array $params = []): void
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

    protected function getIdsToUpdateForValuefields(array $currentNode, string $fieldname): void
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

    protected function getIdsToCheckForDeletionForRelationfields(array $currentNode, string $fieldname): void
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

    protected function getIdsToUpdateForRelationfields(array $currentNode, string $fieldname, array $params = []): void
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
     *
     * @throws Exception
     */
    protected function updateQueryTable(int $oo_id, array $ids, string $fieldname): void
    {
        if (!empty($ids)) {
            $value = $this->db->fetchOne("SELECT `$fieldname` FROM " . $this->querytable . ' WHERE ' . $this->idField . ' = ?', [$oo_id]);
            $this->db->executeStatement('UPDATE ' . $this->querytable .' SET ' . $this->db->quoteIdentifier($fieldname) . '=? WHERE ' . $this->db->quoteIdentifier($this->idField) . ' IN (' . implode(',', $ids) . ')', [$value]);
        }
    }

    protected function updateQueryTableOnDelete(int $oo_id, array $ids, string $fieldname): void
    {
        if (!empty($ids)) {
            $value = null;
            $this->db->executeStatement('UPDATE ' . $this->querytable .' SET ' . $this->db->quoteIdentifier($fieldname) . '=? WHERE ' . $this->db->quoteIdentifier($this->idField) . ' IN (' . implode(',', $ids) . ')', [$value]);
        }
    }
}
