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

namespace Pimcore\Model\DataObject\AbstractObject;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\AbstractObject $model
 */
class Dao extends Model\Element\Dao
{
    /**
     * Get the data for the object from database for the given id
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT objects.*, tree_locks.locked as o_locked FROM objects
            LEFT JOIN tree_locks ON objects.o_id = tree_locks.id AND tree_locks.type = 'object'
                WHERE o_id = ?", $id);

        if (!empty($data['o_id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('Object with the ID ' . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the object from database for the given path
     *
     * @param string $path
     *
     * @throws \Exception
     */
    public function getByPath($path)
    {
        $params = $this->extractKeyAndPath($path);
        $data = $this->db->fetchRow('SELECT o_id FROM objects WHERE o_path = :path AND `o_key` = :key', $params);

        if (!empty($data['o_id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("object doesn't exist");
        }
    }

    /**
     * Create a new record for the object in database
     */
    public function create()
    {
        $this->db->insert('objects', [
            'o_key' => $this->model->getKey(),
            'o_path' => $this->model->getRealPath(),
        ]);
        $this->model->setId($this->db->lastInsertId());

        if (!$this->model->getKey() && !is_numeric($this->model->getKey())) {
            $this->model->setKey($this->db->lastInsertId());
        }
    }

    /**
     * @param bool|null $isUpdate
     *
     * @throws \Exception
     */
    public function update($isUpdate = null)
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
        $checkColumns = ['o_type', 'o_classId', 'o_className'];
        $existingData = $this->db->fetchRow('SELECT ' . implode(',', $checkColumns) . ' FROM objects WHERE o_id = ?', [$this->model->getId()]);
        foreach ($checkColumns as $column) {
            if ($column == 'o_type' && in_array($data[$column], ['variant', 'object']) && (isset($existingData[$column]) && in_array($existingData[$column], ['variant', 'object']))) {
                // type conversion variant <=> object should be possible
                continue;
            }

            if (!empty($existingData[$column]) && $data[$column] != $existingData[$column]) {
                throw new \Exception('Unable to save object: type, classId or className mismatch');
            }
        }

        $this->db->insertOrUpdate('objects', $data);

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
     * @return void
     */
    public function delete()
    {
        $this->db->delete('objects', ['o_id' => $this->model->getId()]);
    }

    public function updateWorkspaces()
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
     * @internal
     *
     * @param string $oldPath
     *
     * @return null|array
     */
    public function updateChildPaths($oldPath)
    {
        if ($this->hasChildren([DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_VARIANT])) {
            //get objects to empty their cache
            $objects = $this->db->fetchCol('SELECT o_id FROM objects WHERE o_path LIKE ?', $this->db->escapeLike($oldPath) . '%');

            $userId = '0';
            if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                $userId = $user->getId();
            }

            //update object child paths
            // we don't update the modification date here, as this can have side-effects when there's an unpublished version for an element
            $this->db->query('update objects set o_path = replace(o_path,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . "), o_userModification = '" . $userId . "' where o_path like " . $this->db->quote($this->db->escapeLike($oldPath) . '/%') . ';');

            //update object child permission paths
            $this->db->query('update users_workspaces_object set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote($this->db->escapeLike($oldPath) . '/%') . ';');

            //update object child properties paths
            $this->db->query('update properties set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote($this->db->escapeLike($oldPath) . '/%') . ';');

            return $objects;
        }

        return null;
    }

    /**
     * deletes all properties for the object from database
     *
     * @return void
     */
    public function deleteAllProperties()
    {
        $this->db->delete('properties', ['cid' => $this->model->getId(), 'ctype' => 'object']);
    }

    /**
     * @return string retrieves the current full object path from DB
     */
    public function getCurrentFullPath()
    {
        $path = null;
        try {
            $path = $this->db->fetchOne('SELECT CONCAT(o_path,`o_key`) as o_path FROM objects WHERE o_id = ?', $this->model->getId());
        } catch (\Exception $e) {
            Logger::error('could not get current object path from DB');
        }

        return $path;
    }

    /**
     * @return int
     */
    public function getVersionCountForUpdate(): int
    {
        $versionCount = (int) $this->db->fetchOne('SELECT o_versionCount FROM objects WHERE o_id = ? FOR UPDATE', $this->model->getId());

        if ($this->model instanceof DataObject\Concrete) {
            $versionCount2 = (int) $this->db->fetchOne("SELECT MAX(versionCount) FROM versions WHERE cid = ? AND ctype = 'object'", $this->model->getId());
            $versionCount = max($versionCount, $versionCount2);
        }

        return (int) $versionCount;
    }

    /**
     * Get the properties for the object from database and assign it
     *
     * @param bool $onlyInherited
     *
     * @return array
     */
    public function getProperties($onlyInherited = false)
    {
        $properties = [];

        // collect properties via parent - ids
        $parentIds = $this->getParentIds();
        $propertiesRaw = $this->db->fetchAll('SELECT name, type, data, cid, inheritable, cpath FROM properties WHERE ((cid IN (' . implode(',', $parentIds) . ") AND inheritable = 1) OR cid = ? ) AND ctype='object'", [$this->model->getId()]);

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
            } catch (\Exception $e) {
                Logger::error("can't add property " . $propertyRaw['name'] . ' to object ' . $this->model->getRealFullPath());
            }
        }

        // if only inherited then only return it and dont call the setter in the model
        if ($onlyInherited) {
            return $properties;
        }

        $this->model->setProperties($properties);

        return $properties;
    }

    public function deleteAllPermissions()
    {
        $this->db->delete('users_workspaces_object', ['cid' => $this->model->getId()]);
    }

    /**
     * Quick test if there are children
     *
     * @param array $objectTypes
     * @param bool|null $includingUnpublished
     *
     * @return bool
     */
    public function hasChildren($objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], $includingUnpublished = null)
    {
        $sql = 'SELECT 1 FROM objects WHERE o_parentId = ?';

        if ((isset($includingUnpublished) && !$includingUnpublished) || (!isset($includingUnpublished) && Model\Document::doHideUnpublished())) {
            $sql .= ' AND o_published = 1';
        }

        $sql .= " AND o_type IN ('" . implode("','", $objectTypes) . "') LIMIT 1";

        $c = $this->db->fetchOne($sql, $this->model->getId());

        return (bool)$c;
    }

    /**
     * Quick test if there are siblings
     *
     * @param array $objectTypes
     * @param bool|null $includingUnpublished
     *
     * @return bool
     */
    public function hasSiblings($objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], $includingUnpublished = null)
    {
        $sql = 'SELECT 1 FROM objects WHERE o_parentId = ? and o_id != ?';

        if ((isset($includingUnpublished) && !$includingUnpublished) || (!isset($includingUnpublished) && Model\Document::doHideUnpublished())) {
            $sql .= ' AND o_published = 1';
        }

        $sql .= " AND o_type IN ('" . implode("','", $objectTypes) . "') LIMIT 1";

        $c = $this->db->fetchOne($sql, [$this->model->getParentId(), $this->model->getId()]);

        return (bool)$c;
    }

    /**
     * returns the amount of directly children (not recursivly)
     *
     * @param array|null $objectTypes
     * @param Model\User $user
     *
     * @return int
     */
    public function getChildAmount($objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], $user = null)
    {
        $query = 'SELECT COUNT(*) AS count FROM objects o WHERE o_parentId = ?';

        if (!empty($objectTypes)) {
            $query .= sprintf(' AND o_type IN (\'%s\')', implode("','", $objectTypes));
        }

        if ($user and !$user->isAdmin()) {
            $userIds = $user->getRoles();
            $userIds[] = $user->getId();

            $query .= ' AND (select list as locate from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(o.o_path,o.o_key))=1 ORDER BY LENGTH(cpath) DESC LIMIT 1)=1;';
        }

        $c = $this->db->fetchOne($query, $this->model->getId());

        return $c;
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getTypeById($id)
    {
        $t = $this->db->fetchRow('SELECT o_type,o_className,o_classId FROM objects WHERE o_id = ?', $id);

        return $t;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks INNER JOIN objects ON tree_locks.id = objects.o_id WHERE objects.o_path LIKE ? AND tree_locks.type = 'object' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", $this->db->escapeLike($this->model->getRealFullPath()) . '/%');

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne('SELECT id FROM tree_locks WHERE id IN (' . implode(',', $parentIds) . ") AND type='object' AND locked = 'propagate' LIMIT 1");

        if ($inhertitedLocks > 0) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function unlockPropagate()
    {
        $lockIds = $this->db->fetchCol('SELECT o_id from objects WHERE o_path LIKE ' . $this->db->quote($this->db->escapeLike($this->model->getRealFullPath()) . '/%') . ' OR o_id = ' . $this->model->getId());
        $this->db->deleteWhere('tree_locks', "type = 'object' AND id IN (" . implode(',', $lockIds) . ')');

        return $lockIds;
    }

    /**
     * @return array
     */
    public function getClasses()
    {
        if ($this->getChildAmount()) {
            $path = $this->model->getRealFullPath();
            if (!$this->model->getId() || $this->model->getId() == 1) {
                $path = '';
            }
            $classIds = $this->db->fetchCol("SELECT o_classId FROM objects WHERE o_path LIKE ? AND o_type = 'object' GROUP BY o_classId", $this->db->escapeLike($path) . '/%');

            $classes = [];
            foreach ($classIds as $classId) {
                $classes[] = DataObject\ClassDefinition::getById($classId);
            }

            return $classes;
        }

        return [];
    }

    /**
     * @return int[]
     */
    protected function collectParentIds()
    {
        $parentIds = $this->getParentIds();
        $parentIds[] = $this->model->getId();

        return $parentIds;
    }

    /**
     * @param string $type
     * @param Model\User $user
     *
     * @return bool
     */
    public function isAllowed($type, $user)
    {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne('SELECT `' . $type . '` FROM users_workspaces_object WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC, `' . $type . '` DESC LIMIT 1');

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

                $permissionsChildren = $this->db->fetchOne('SELECT list FROM users_workspaces_object WHERE cpath LIKE ? AND userId IN (' . implode(',', $userIds) . ') AND list = 1 LIMIT 1', $this->db->escapeLike($path) . '%');
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
     * @param string $type
     * @param Model\User $user
     * @param bool $quote
     *
     * @return array|null
     */
    public function getPermissions($type, $user, $quote = true)
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
                $allPermissions = $this->db->fetchAll('SELECT ' . $queryType . ',cid,cpath FROM users_workspaces_object WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC, `' . $type . '` DESC');
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

            $permissions = $this->db->fetchRow('SELECT ' . $queryType . ' FROM users_workspaces_object WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, FIELD(userId, ' . $user->getId() . ') DESC, `' . $type . '` DESC  LIMIT 1');

            return $permissions;
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return null;
    }

    /**
     * @param string $type
     * @param Model\User $user
     * @param bool $quote
     *
     * @return array
     */
    public function getChildPermissions($type, $user, $quote = true)
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
            $sql = 'SELECT ' . $type . ' FROM users_workspaces_object WHERE cid != ' . $cid . ' AND cpath LIKE ' . $this->db->quote($this->db->escapeLike($this->model->getRealFullPath()) . '%') . ' AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC';
            $permissions = $this->db->fetchAll($sql);
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for object ' . $this->model->getId());
        }

        return $permissions;
    }

    /**
     * @param int $index
     */
    public function saveIndex($index)
    {
        $this->db->update('objects', [
            'o_index' => $index,
        ], [
            'o_id' => $this->model->getId(),
        ]);
    }

    /**
     * @return bool
     */
    public function __isBasedOnLatestData()
    {
        $data = $this->db->fetchRow('SELECT o_modificationDate, o_versionCount  from objects WHERE o_id = ?', $this->model->getId());

        return $data
            && $data['o_modificationDate'] == $this->model->__getDataVersionTimestamp()
            && $data['o_versionCount'] == $this->model->getVersionCount();
    }
}
