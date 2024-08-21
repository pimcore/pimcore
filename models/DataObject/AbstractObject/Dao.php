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

namespace Pimcore\Model\DataObject\AbstractObject;

use Doctrine\DBAL\Exception;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\User;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\AbstractObject $model
 */
class Dao extends Model\Element\Dao
{
    /**
     * Get the data for the object from database for the given id
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative("SELECT objects.*, tree_locks.locked as locked FROM objects
            LEFT JOIN tree_locks ON objects.id = tree_locks.id AND tree_locks.type = 'object'
                WHERE objects.id = ?", [$id]);

        if ($data) {
            $data['published'] = (bool)$data['published'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('Object with the ID ' . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the object from database for the given path
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByPath(string $path): void
    {
        $params = $this->extractKeyAndPath($path);
        $data = $this->db->fetchAssociative('SELECT id FROM objects WHERE `path` = BINARY :path AND `key` = BINARY :key', $params);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException("object doesn't exist");
        }
    }

    /**
     * Create a new record for the object in database
     */
    public function create(): void
    {
        $this->db->insert('objects', Helper::quoteDataIdentifiers($this->db, [
            'key' => $this->model->getKey(),
            'path' => $this->model->getRealPath(),
        ]));
        $this->model->setId((int) $this->db->lastInsertId());

        if (!$this->model->getKey() && !is_numeric($this->model->getKey())) {
            $this->model->setKey($this->db->lastInsertId());
        }
    }

    /**
     *
     * @throws \Exception
     */
    public function update(bool $isUpdate = null): void
    {
        $object = $this->model->getObjectVars();

        $data = [];
        $validTableColumns = $this->getValidTableColumns('objects');

        foreach ($object as $key => $value) {
            if (in_array($key, $validTableColumns)) {
                if (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        // check the type before updating, changing the type or class of an object is not possible
        $checkColumns = ['type', 'classId', 'className'];
        $existingData = $this->db->fetchAssociative('SELECT ' . implode(',', $checkColumns) . ' FROM objects WHERE id = ?', [$this->model->getId()]);
        foreach ($checkColumns as $column) {
            if ($column == 'type' && in_array($data[$column], [DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT]) && (isset($existingData[$column]) && in_array($existingData[$column], [DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_OBJECT]))) {
                // type conversion variant <=> object should be possible
                continue;
            }

            if (!empty($existingData[$column]) && $data[$column] != $existingData[$column]) {
                throw new \Exception('Unable to save object: type, classId or className mismatch');
            }
        }

        Helper::upsert($this->db, 'objects', $data, $this->getPrimaryKey('objects'));

        // tree_locks
        $this->db->delete('tree_locks', ['id' => $this->model->getId(), 'type' => 'object']);
        if ($this->model->getLocked()) {
            $this->db->insert('tree_locks', [
                'id' => $this->model->getId(),
                'type' => 'object',
                'locked' => $this->model->getLocked(),
            ]);
        }
    }

    /**
     * Deletes object from database
     *
     */
    public function delete(): void
    {
        $this->db->delete('objects', ['id' => $this->model->getId()]);
    }

    public function updateWorkspaces(): void
    {
        $this->db->update('users_workspaces_object', [
            'cpath' => $this->model->getRealFullPath(),
        ], [
            'cid' => $this->model->getId(),
        ]);
    }

    /**
     * Updates the paths for children, children's properties and children's permissions in the database
     *
     *
     *
     * @internal
     */
    public function updateChildPaths(string $oldPath): ?array
    {
        if ($this->hasChildren(DataObject::$types, true)) {
            //get objects to empty their cache
            $objects = $this->db->fetchFirstColumn('SELECT id FROM objects WHERE `path` like ?', [Helper::escapeLike($oldPath) . '%']);

            $userId = '0';
            if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                $userId = $user->getId();
            }

            //update object child paths
            // we don't update the modification date here, as this can have side-effects when there's an unpublished version for an element
            $this->db->executeQuery('update objects set `path` = replace(`path`,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . "), userModification = '" . $userId . "' where `path` like " . $this->db->quote(Helper::escapeLike($oldPath) . '/%') . ';');

            //update object child permission paths
            $this->db->executeQuery('update users_workspaces_object set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote(Helper::escapeLike($oldPath) . '/%') . ';');

            //update object child properties paths
            $this->db->executeQuery('update properties set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote(Helper::escapeLike($oldPath) . '/%') . ';');

            return $objects;
        }

        return null;
    }

    /**
     * deletes all properties for the object from database
     *
     */
    public function deleteAllProperties(): void
    {
        $this->db->delete('properties', ['cid' => $this->model->getId(), 'ctype' => 'object']);
    }

    /**
     * @return string|null retrieves the current full object path from DB
     */
    public function getCurrentFullPath(): ?string
    {
        if ($path = $this->db->fetchOne('SELECT CONCAT(`path`,`key`) as `path` FROM objects WHERE id = ?', [$this->model->getId()])) {
            return $path;
        }

        return null;
    }

    public function getVersionCountForUpdate(): int
    {
        if (!$this->model->getId()) {
            return 0;
        }

        $versionCount = (int) $this->db->fetchOne('SELECT versionCount FROM objects WHERE id = ? FOR UPDATE', [$this->model->getId()]);

        if ($this->model instanceof DataObject\Concrete) {
            $versionCount2 = (int) $this->db->fetchOne("SELECT MAX(versionCount) FROM versions WHERE cid = ? AND ctype = 'object'", [$this->model->getId()]);
            $versionCount = max($versionCount, $versionCount2);
        }

        return (int) $versionCount;
    }

    /**
     * Get the properties for the object from database and assign it
     *
     * @throws \Exception
     */
    public function getProperties(bool $onlyInherited = false): array
    {
        $properties = [];

        // collect properties via parent - ids
        $parentIds = $this->getParentIds();
        $propertiesRaw = $this->db->fetchAllAssociative(
            'SELECT name, type, data, cid, inheritable, cpath FROM properties WHERE
                     (
                         (cid IN (' . implode(',', $parentIds) . ") AND inheritable = 1)
                         OR cid = ? ) AND ctype='object'",
            [$this->model->getId()]
        );

        // because this should be faster than mysql
        usort($propertiesRaw, function ($left, $right) {
            return strcmp((string)$left['cpath'], (string)$right['cpath']);
        });

        foreach ($propertiesRaw as $propertyRaw) {
            try {
                $id = $this->model->getId();
                $property = new Model\Property();
                $property->setType($propertyRaw['type']);
                if ($id !== null) {
                    $property->setCid($id);
                }
                $property->setName($propertyRaw['name']);
                $property->setCtype('object');
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
            } catch (\Exception) {
                Logger::error(
                    "can't add property " . $propertyRaw['name'] . ' to object ' . $this->model->getRealFullPath()
                );
            }
        }

        return $properties;
    }

    /**
     * Quick test if there are children
     *
     * @throws Exception
     */
    public function hasChildren(
        array $objectTypes = [
            DataObject::OBJECT_TYPE_OBJECT,
            DataObject::OBJECT_TYPE_VARIANT,
            DataObject::OBJECT_TYPE_FOLDER],
        ?bool $includingUnpublished = null,
        ?User $user = null
    ): bool {
        if (!$this->model->getId()) {
            return false;
        }

        $sql = 'SELECT 1 FROM objects o WHERE parentId = ? ';
        if ($user && !$user->isAdmin()) {
            $roleIds = $user->getRoles();
            $currentUserId = $user->getId();
            $permissionIds = array_merge($roleIds, [$currentUserId]);

            //gets the permission of the ancestors, since it would be the same for each row with same parentId, it is done once outside the query to avoid extra subquery.
            $inheritedPermission = $this->isInheritingPermission('list', $permissionIds);

            // $anyAllowedRowOrChildren checks for nested elements that are `list`=1. This is to allow the folders in between from current parent to any nested elements and due the "additive" permission on the element itself, we can simply ignore list=0 children
            // unless for the same rule found is list=0 on user specific level, in that case it nullifies that entry.
            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_object uwo WHERE userId IN (' . implode(',', $permissionIds) . ') AND list=1 AND LOCATE(CONCAT(o.path,o.key),cpath)=1 AND
            NOT EXISTS(SELECT list FROM users_workspaces_object WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwo.cpath))';

            // $allowedCurrentRow checks if the current row is blocked, if found a match it "removes/ignores" the entry from object table, doesn't need to check if is list=1 on user level, since it is done in $anyAllowedRowOrChildren (NB: equal or longer cpath) so we are safe to deduce that there are no valid list=1 rules
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_object uworow WHERE userId IN (' . implode(',', $permissionIds) . ')  AND cid = id AND list=0)';

            //If no children with list=1 (with no user-level list=0) is found, we consider the inherited permission rule
            //if $inheritedPermission=0 then everything is disallowed (or doesn't specify any rule) for that row, we can skip $isDisallowedCurrentRow
            //if $inheritedPermission=1, then we are allowed unless the current row is specifically disabled, already knowing from $anyAllowedRowOrChildren that there are no list=1(without user permission list=0),so this "blocker" is the highest cpath available for this row if found

            $sql .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        if ((isset($includingUnpublished) && !$includingUnpublished) || (!isset($includingUnpublished) && Model\Document::doHideUnpublished())) {
            $sql .= ' AND published = 1';
        }

        if (!empty($objectTypes)) {
            $sql .= " AND `type` IN ('" . implode("','", $objectTypes) . "')";
        }

        $sql .= ' LIMIT 1';
        $c = $this->db->fetchOne($sql, [$this->model->getId()]);

        return (bool)$c;
    }

    /**
     * Quick test if there are siblings
     *
     * @throws Exception
     */
    public function hasSiblings(
        array $objectTypes = [
            DataObject::OBJECT_TYPE_OBJECT,
            DataObject::OBJECT_TYPE_VARIANT,
            DataObject::OBJECT_TYPE_FOLDER,
        ],
        ?bool $includingUnpublished = null
    ): bool {
        if (!$this->model->getParentId()) {
            return false;
        }

        $sql = 'SELECT 1 FROM objects WHERE parentId = ?';
        $params = [$this->model->getParentId()];

        if ($this->model->getId()) {
            $sql .= ' AND id != ?';
            $params[] = $this->model->getId();
        }

        if ((isset($includingUnpublished) && !$includingUnpublished) || (!isset($includingUnpublished) && Model\Document::doHideUnpublished())) {
            $sql .= ' AND published = 1';
        }

        if (!empty($objectTypes)) {
            $sql .= " AND `type` IN ('" . implode("','", $objectTypes) . "')";
        }

        $sql .= ' LIMIT 1';

        $c = $this->db->fetchOne($sql, $params);

        return (bool)$c;
    }

    /**
     * returns the amount of directly children (not recursivly)
     *
     * @param Model\User|null $user
     *
     */
    public function getChildAmount(?array $objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_FOLDER], User $user = null): int
    {
        if (!$this->model->getId()) {
            return 0;
        }

        $query = 'SELECT COUNT(*) AS count FROM objects o WHERE parentId = ?';

        if (!empty($objectTypes)) {
            $query .= sprintf(' AND `type` IN (\'%s\')', implode("','", $objectTypes));
        }

        if ($user && !$user->isAdmin()) {
            $roleIds = $user->getRoles();
            $currentUserId = $user->getId();
            $permissionIds = array_merge($roleIds, [$currentUserId]);

            $inheritedPermission = $this->isInheritingPermission('list', $permissionIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_object uwo WHERE userId IN (' . implode(',', $permissionIds) . ') AND list=1 AND LOCATE(CONCAT(o.path,o.key),cpath)=1 AND
            NOT EXISTS(SELECT list FROM users_workspaces_object WHERE userId ='.$currentUserId.'  AND list=0 AND cpath = uwo.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_object uworow WHERE userId IN (' . implode(',', $permissionIds) . ')  AND cid = id AND list=0)';

            $query .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        return (int) $this->db->fetchOne($query, [$this->model->getId()]);
    }

    /**
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getTypeById(int $id): array
    {
        $t = $this->db->fetchAssociative('SELECT `type`,`className`,`classId` FROM objects WHERE `id` = ?', [$id]);

        if (!$t) {
            throw new Model\Exception\NotFoundException('object with ID ' . $id . ' not found');
        }

        return $t;
    }

    public function isLocked(): bool
    {
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks INNER JOIN objects ON tree_locks.id = objects.id WHERE objects.path LIKE ? AND tree_locks.type = 'object' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", [Helper::escapeLike($this->model->getRealFullPath()) . '/%']);

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne('SELECT id FROM tree_locks WHERE id IN (' . implode(',', $parentIds) . ") AND `type`='object' AND locked = 'propagate' LIMIT 1");

        if ($inhertitedLocks > 0) {
            return true;
        }

        return false;
    }

    public function unlockPropagate(): array
    {
        $lockIds = $this->db->fetchFirstColumn('SELECT id from objects WHERE `path` like ' . $this->db->quote(Helper::escapeLike($this->model->getRealFullPath()) . '/%') . ' OR id = ' . $this->model->getId());
        $this->db->executeStatement("DELETE FROM tree_locks WHERE `type` = 'object' AND id IN (" . implode(',', $lockIds) . ')');

        return $lockIds;
    }

    /**
     * @return DataObject\ClassDefinition[]
     */
    public function getClasses(): array
    {
        $path = $this->model->getRealFullPath();
        if (!$this->model->getId() || $this->model->getId() == 1) {
            $path = '';
        }

        $classIds = [];
        do {
            $classId = $this->db->fetchOne(
                "SELECT classId FROM objects WHERE `path` like ? AND `type` = 'object'".($classIds ? ' AND classId NOT IN ('.rtrim(str_repeat('?,', count($classIds)), ',').')' : '').' LIMIT 1',
                array_merge([Helper::escapeLike($path).'/%'], $classIds));
            if ($classId) {
                $classIds[] = $classId;
            }
        } while ($classId);

        $classes = [];
        foreach ($classIds as $classId) {
            if ($class = DataObject\ClassDefinition::getById($classId)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * @return int[]
     */
    protected function collectParentIds(): array
    {
        $parentIds = $this->getParentIds();
        if ($id = $this->model->getId()) {
            $parentIds[] = $id;
        }

        return $parentIds;
    }

    /**
     *
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function isInheritingPermission(string $type, array $userIds): int
    {
        return $this->InheritingPermission($type, $userIds, 'object');
    }

    public function isAllowed(string $type, User $user): bool
    {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne('SELECT ' . $this->db->quoteIdentifier($type) . ' FROM users_workspaces_object WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC, ' . $this->db->quoteIdentifier($type) . ' DESC LIMIT 1');

            if ($permissionsParent) {
                return true;
            }

            // exception for list permission
            if (empty($permissionsParent) && $type === 'list') {
                // check for children with permissions
                $path = $this->model->getRealFullPath() . '/';
                if ($this->model->getId() == 1) {
                    $path = '/';
                }

                $permissionsChildren = $this->db->fetchOne('SELECT list FROM users_workspaces_object WHERE cpath LIKE ? AND userId IN (' . implode(',', $userIds) . ') AND list = 1 LIMIT 1', [Helper::escapeLike($path) . '%']);
                if ($permissionsChildren) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return false;
    }

    /**
     * @param string[] $columns
     *
     * @return array<string, int>
     */
    public function areAllowed(array $columns, User $user): array
    {
        return $this->permissionByTypes($columns, $user, 'object');
    }

    public function getPermissions(?string $type, User $user, bool $quote = true): ?array
    {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            if ($type && $quote) {
                $queryType = '`' . $type . '`';
            } else {
                $queryType = '*';
            }

            $commaSeparated = in_array($type, ['lView', 'lEdit', 'layouts']);

            if ($commaSeparated) {
                $allPermissions = $this->db->fetchAllAssociative('SELECT ' . $queryType . ',cid,cpath FROM users_workspaces_object WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC, `' . $type . '` DESC');
                if (!$allPermissions) {
                    return null;
                }

                if (count($allPermissions) == 1) {
                    return $allPermissions[0];
                }

                $firstPermission = $allPermissions[0];
                $firstPermissionCid = $firstPermission['cid'];
                $mergedPermissions = [];

                foreach ($allPermissions as $permission) {
                    $cid = $permission['cid'];
                    if ($cid != $firstPermissionCid) {
                        break;
                    }

                    $permissionValues = $permission[$type];
                    if (!$permissionValues) {
                        $firstPermission[$type] = null;

                        return $firstPermission;
                    }

                    $permissionValues = explode(',', $permissionValues);
                    foreach ($permissionValues as $permissionValue) {
                        $mergedPermissions[$permissionValue] = $permissionValue;
                    }
                }

                $firstPermission[$type] = implode(',', $mergedPermissions);

                return $firstPermission;
            }

            $orderByType = $type ? ', `' . $type . '` DESC' : '';
            $permissions = $this->db->fetchAssociative('SELECT ' . $queryType . ' FROM users_workspaces_object WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC' . $orderByType . ' LIMIT 1');

            return $permissions ?: null;
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return null;
    }

    public function getChildPermissions(?string $type, User $user, bool $quote = true): array
    {
        $userIds = $user->getRoles();
        $userIds[] = $user->getId();
        $permissions = [];

        try {
            if ($type && $quote) {
                $type = '`' . $type . '`';
            } else {
                $type = '*';
            }

            $cid = $this->model->getId();
            $sql = 'SELECT ' . $type . ' FROM users_workspaces_object WHERE cid != ' . $cid . ' AND cpath LIKE ' . $this->db->quote(Helper::escapeLike($this->model->getRealFullPath()) . '%') . ' AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC';
            $permissions = $this->db->fetchAllAssociative($sql);
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return $permissions;
    }

    public function saveIndex(int $index): void
    {
        $this->db->update('objects', [
            $this->db->quoteIdentifier('index') => $index,
        ], [
            'id' => $this->model->getId(),
        ]);
    }

    public function __isBasedOnLatestData(): bool
    {
        $data = $this->db->fetchAssociative('SELECT modificationDate, versionCount  from objects WHERE id = ?', [$this->model->getId()]);

        return $data
            && $data['modificationDate'] == $this->model->__getDataVersionTimestamp()
            && $data['versionCount'] == $this->model->getVersionCount();
    }
}
