<?php

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

namespace Pimcore\Model\Document;

use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\User;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document $model
 */
class Dao extends Model\Element\Dao
{
    use Model\Element\Traits\ScheduledTasksDaoTrait;

    /**
     * Fetch a row by an id from the database and assign variables to the document model.
     *
     * @param int $id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById($id)
    {
        $data = $this->db->fetchAssociative("SELECT documents.*, tree_locks.locked FROM documents
            LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                WHERE documents.id = ?", [$id]);

        if (!empty($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new  Model\Exception\NotFoundException('document with id ' . $id . ' not found');
        }
    }

    /**
     * Fetch a row by a path from the database and assign variables to the model.
     *
     * @param string $path
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByPath($path)
    {
        $params = $this->extractKeyAndPath($path);
        $data = $this->db->fetchAssociative('SELECT id FROM documents WHERE path = BINARY :path AND `key` = BINARY :key', $params);

        if (!empty($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            // try to find a page with a pretty URL (use the original $path)
            $data = $this->db->fetchAssociative('SELECT id FROM documents_page WHERE prettyUrl = :prettyUrl', [
                'prettyUrl' => $path,
            ]);

            if (!empty($data['id'])) {
                $this->assignVariablesToModel($data);
            } else {
                throw new Model\Exception\NotFoundException("document with path $path doesn't exist");
            }
        }
    }

    public function create()
    {
        $this->db->insert('documents', Helper::quoteDataIdentifiers($this->db, [
            'key' => $this->model->getKey(),
            'type' => $this->model->getType(),
            'path' => $this->model->getRealPath(),
            'parentId' => $this->model->getParentId(),
            'index' => 0,
        ]));

        $this->model->setId((int) $this->db->lastInsertId());

        if (!$this->model->getKey()) {
            $this->model->setKey((string) $this->model->getId());
        }
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        $typeSpecificTable = null;
        $validColumnsTypeSpecific = [];
        $documentsConfig = \Pimcore\Config::getSystemConfiguration('documents');
        $validTables = $documentsConfig['valid_tables'];
        if (in_array($this->model->getType(), $validTables)) {
            $typeSpecificTable = 'documents_' . $this->model->getType();
            $validColumnsTypeSpecific = $this->getValidTableColumns($typeSpecificTable);
        }

        $document = $this->model->getObjectVars();

        $dataDocument = [];
        $dataTypeSpecific = [];

        foreach ($document as $key => $value) {
            // check if the getter exists
            $getter = 'get' . ucfirst($key);
            if (!method_exists($this->model, $getter)) {
                continue;
            }

            // get the value from the getter
            if (in_array($key, $this->getValidTableColumns('documents')) || in_array($key, $validColumnsTypeSpecific)) {
                $value = $this->model->$getter();
            } else {
                continue;
            }

            if (is_bool($value)) {
                $value = (int)$value;
            }
            if (is_array($value)) {
                $value = Serialize::serialize($value);
            }

            if (in_array($key, $this->getValidTableColumns('documents'))) {
                $dataDocument[$key] = $value;
            }
            if (in_array($key, $validColumnsTypeSpecific)) {
                $dataTypeSpecific[$key] = $value;
            }
        }

        // use the real document path, just for the case that a documents gets saved in the frontend
        // and the page is within a site. see also: PIMCORE-2684
        $dataDocument['path'] = $this->model->getRealPath();

        // update the values in the database
        Helper::insertOrUpdate($this->db, 'documents', $dataDocument);

        if ($typeSpecificTable) {
            Helper::insertOrUpdate($this->db, $typeSpecificTable, $dataTypeSpecific);
        }

        $this->updateLocks();
    }

    /**
     * Delete the row from the database. (based on the model id)
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->db->delete('documents', ['id' => $this->model->getId()]);
    }

    /**
     * Update document workspaces..
     *
     * @throws \Exception
     */
    public function updateWorkspaces()
    {
        $this->db->update('users_workspaces_document', [
            'cpath' => $this->model->getRealFullPath(),
        ], [
            'cid' => $this->model->getId(),
        ]);
    }

    /**
     * Updates children path in order to the old document path specified in the $oldPath parameter.
     *
     * @internal
     *
     * @param string $oldPath
     *
     * @return array
     */
    public function updateChildPaths($oldPath)
    {
        //get documents to empty their cache
        $documents = $this->db->fetchAllAssociative('SELECT id, CONCAT(path,`key`) AS path FROM documents WHERE path LIKE ?', [Helper::escapeLike($oldPath) . '%']);

        $userId = '0';
        if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
            $userId = $user->getId();
        }

        //update documents child paths
        // we don't update the modification date here, as this can have side-effects when there's an unpublished version for an element
        $this->db->executeQuery('update documents set path = replace(path,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . "), userModification = '" . $userId . "' where path like " . $this->db->quote(Helper::escapeLike($oldPath) . '/%') . ';');

        //update documents child permission paths
        $this->db->executeQuery('update users_workspaces_document set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote(Helper::escapeLike($oldPath) . '/%') . ';');

        //update documents child properties paths
        $this->db->executeQuery('update properties set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote(Helper::escapeLike($oldPath) . '/%') . ';');

        return $documents;
    }

    /**
     * Returns the current full document path from the database.
     *
     * @return string
     */
    public function getCurrentFullPath()
    {
        $path = null;

        try {
            $path = $this->db->fetchOne('SELECT CONCAT(path,`key`) as path FROM documents WHERE id = ?', [$this->model->getId()]);
        } catch (\Exception $e) {
            Logger::error('could not  get current document path from DB');
        }

        return $path;
    }

    /**
     * @return int
     */
    public function getVersionCountForUpdate(): int
    {
        if (!$this->model->getId()) {
            return 0;
        }

        $versionCount = (int) $this->db->fetchOne('SELECT versionCount FROM documents WHERE id = ? FOR UPDATE', [$this->model->getId()]);

        if ($this->model instanceof PageSnippet) {
            $versionCount2 = (int) $this->db->fetchOne("SELECT MAX(versionCount) FROM versions WHERE cid = ? AND ctype = 'document'", [$this->model->getId()]);
            $versionCount = max($versionCount, $versionCount2);
        }

        return (int) $versionCount;
    }

    /**
     * Returns properties for the object from the database and assigns these.
     *
     * @param bool $onlyInherited
     * @param bool $onlyDirect
     *
     * @return array
     */
    public function getProperties($onlyInherited = false, $onlyDirect = false)
    {
        $properties = [];

        if ($onlyDirect) {
            $propertiesRaw = $this->db->fetchAllAssociative("SELECT * FROM properties WHERE cid = ? AND ctype='document'", [$this->model->getId()]);
        } else {
            $parentIds = $this->getParentIds();
            $propertiesRaw = $this->db->fetchAllAssociative('SELECT * FROM properties WHERE ((cid IN (' . implode(',', $parentIds) . ") AND inheritable = 1) OR cid = ? )  AND ctype='document'", [$this->model->getId()]);
        }

        // because this should be faster than mysql
        usort($propertiesRaw, function ($left, $right) {
            return strcmp($left['cpath'], $right['cpath']);
        });

        foreach ($propertiesRaw as $propertyRaw) {
            try {
                $property = new Model\Property();
                $property->setType($propertyRaw['type']);
                $property->setCid($this->model->getId());
                $property->setName($propertyRaw['name']);
                $property->setCtype('document');
                $property->setDataFromResource($propertyRaw['data']);
                $property->setInherited(true);
                if ($propertyRaw['cid'] == $this->model->getId()) {
                    $property->setInherited(false);
                }
                $property->setInheritable(false);
                if ($propertyRaw['inheritable']) {
                    $property->setInheritable(true);
                }

                if ($onlyInherited && !$property->getInherited()) {
                    continue;
                }

                $properties[$propertyRaw['name']] = $property;
            } catch (\Exception $e) {
                Logger::error("can't add property " . $propertyRaw['name'] . ' to document ' . $this->model->getRealFullPath());
            }
        }

        // if only inherited then only return it and dont call the setter in the model
        if ($onlyInherited || $onlyDirect) {
            return $properties;
        }

        $this->model->setProperties($properties);

        return $properties;
    }

    /**
     * Deletes all object properties from the database.
     */
    public function deleteAllProperties()
    {
        $this->db->delete('properties', ['cid' => $this->model->getId(), 'ctype' => 'document']);
    }

    /**
     * Quick check if there are children.
     *
     * @param bool|null $includingUnpublished
     * @param Model\User $user
     *
     * @return bool
     */
    public function hasChildren($includingUnpublished = null, $user = null)
    {
        if (!$this->model->getId()) {
            return false;
        }

        $sql = 'SELECT id FROM documents d WHERE parentId = ? ';

        if ($user && !$user->isAdmin()) {
            $userIds = $user->getRoles();
            $currentUserId = $user->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $this->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_document uwd WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(d.path,d.`key`),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_document WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwd.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = id AND list=0)';

            $sql .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        if ((isset($includingUnpublished) && !$includingUnpublished) || (!isset($includingUnpublished) && Model\Document::doHideUnpublished())) {
            $sql .= ' AND published = 1';
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, [$this->model->getId()]);

        return (bool)$c;
    }

    /**
     * Returns the amount of children (not recursively),
     *
     * @param Model\User $user
     *
     * @return int
     */
    public function getChildAmount($user = null)
    {
        if (!$this->model->getId()) {
            return 0;
        }
        $sql = 'SELECT count(*) FROM documents d WHERE parentId = ? ';
        if ($user && !$user->isAdmin()) {
            $userIds = $user->getRoles();
            $currentUserId = $user->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $this->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_document uwd WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(d.path,d.`key`),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_document WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwd.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = id AND list=0)';

            $sql .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        return (int) $this->db->fetchOne($sql, [$this->model->getId()]);
    }

    /**
     * Checks if the document has siblings
     *
     * @param bool|null $includingUnpublished
     *
     * @return bool
     */
    public function hasSiblings($includingUnpublished = null)
    {
        if (!$this->model->getParentId()) {
            return false;
        }

        $sql = 'SELECT id FROM documents WHERE parentId = ?';
        $params = [$this->model->getParentId()];

        if ($this->model->getId()) {
            $sql .= ' AND id != ?';
            $params[] = $this->model->getId();
        }

        if ((isset($includingUnpublished) && !$includingUnpublished) || (!isset($includingUnpublished) && Model\Document::doHideUnpublished())) {
            $sql .= ' AND published = 1';
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, $params);

        return (bool)$c;
    }

    /**
     * Checks if the document is locked.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isLocked()
    {
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks
            INNER JOIN documents ON tree_locks.id = documents.id
                WHERE documents.path LIKE ? AND tree_locks.type = 'document' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", [Helper::escapeLike($this->model->getRealFullPath()). '/%']);

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne('SELECT id FROM tree_locks WHERE id IN (' . implode(',', $parentIds) . ") AND type='document' AND locked = 'propagate' LIMIT 1");

        if ($inhertitedLocks > 0) {
            return true;
        }

        return false;
    }

    /**
     * Update the lock value for the document.
     *
     * @throws \Exception
     */
    public function updateLocks()
    {
        $this->db->delete('tree_locks', ['id' => $this->model->getId(), 'type' => 'document']);
        if ($this->model->getLocked()) {
            $this->db->insert('tree_locks', [
                'id' => $this->model->getId(),
                'type' => 'document',
                'locked' => $this->model->getLocked(),
            ]);
        }
    }

    /**
     * Deletes locks from the document and its children.
     *
     * @return array
     */
    public function unlockPropagate()
    {
        $lockIds = $this->db->fetchFirstColumn('SELECT id from documents WHERE path LIKE ' . $this->db->quote(Helper::escapeLike($this->model->getRealFullPath()) . '/%') . ' OR id = ' . $this->model->getId());
        $this->db->executeStatement("DELETE FROM tree_locks WHERE type = 'document' AND id IN (" . implode(',', $lockIds) . ')');

        return $lockIds;
    }

    /**
     * @param string $type
     * @param array $userIds
     *
     * @return int
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function isInheritingPermission(string $type, array $userIds)
    {
        return $this->InheritingPermission($type, $userIds, 'document');
    }

    /**
     * Checks if the action is allowed.
     *
     * @param string $type
     * @param Model\User $user
     *
     * @return bool
     */
    public function isAllowed($type, $user)
    {
        // collect properties via parent - ids
        $parentIds = [1];

        $obj = $this->model->getParent();
        if ($obj) {
            while ($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        if ($id = $this->model->getId()) {
            $parentIds[] = $id;
        }

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne('SELECT ' . $this->db->quoteIdentifier($type) . ' FROM users_workspaces_document WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC, ' . $this->db->quoteIdentifier($type) . ' DESC  LIMIT 1');

            if ($permissionsParent) {
                return true;
            }

            // exception for list permission
            if (empty($permissionsParent) && $type == 'list') {
                // check for children with permissions
                $path = $this->model->getRealFullPath() . '/';
                if ($this->model->getId() == 1) {
                    $path = '/';
                }

                $permissionsChildren = $this->db->fetchOne('SELECT list FROM users_workspaces_document WHERE cpath LIKE ? AND userId IN (' . implode(',', $userIds) . ') AND list = 1 LIMIT 1', [Helper::escapeLike($path) . '%']);
                if ($permissionsChildren) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for document ' . $this->model->getId());
        }

        return false;
    }

    /**
     * @param array $columns
     * @param User $user
     *
     * @return array<string, int>
     *
     */
    public function areAllowed(array $columns, User $user)
    {
        return $this->permissionByTypes($columns, $user, 'document');
    }

    /**
     * Save the document index.
     *
     * @param int $index
     */
    public function saveIndex($index)
    {
        $this->db->update('documents', [
            'index' => $index,
        ], [
            'id' => $this->model->getId(),
        ]);
    }

    /**
     * Fetches the maximum index value from siblings.
     *
     * @return int
     */
    public function getNextIndex()
    {
        $index = $this->db->fetchOne('SELECT MAX(`index`) FROM documents WHERE parentId = ?', [$this->model->getParentId()]);
        $index++;

        return $index;
    }

    /**
     * @return bool
     */
    public function __isBasedOnLatestData()
    {
        $data = $this->db->fetchAssociative('SELECT modificationDate,versionCount from documents WHERE id = ?', [$this->model->getId()]);
        if ($data['modificationDate'] == $this->model->__getDataVersionTimestamp() && $data['versionCount'] == $this->model->getVersionCount()) {
            return true;
        }

        return false;
    }
}
