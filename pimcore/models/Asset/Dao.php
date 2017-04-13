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
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Logger;
use Pimcore\Model;

/**
 * @property \Pimcore\Model\Asset $model
 */
class Dao extends Model\Element\Dao
{
    use Model\Element\ChildsCompatibilityTrait;

    /**
     * Get the data for the object by id from database and assign it to the object (model)
     *
     * @param $id
     *
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT assets.*, tree_locks.locked FROM assets
            LEFT JOIN tree_locks ON assets.id = tree_locks.id AND tree_locks.type = 'asset'
                WHERE assets.id = ?", $id);

        if ($data['id'] > 0) {
            $this->assignVariablesToModel($data);

            if ($data['hasMetaData']) {
                $metadataRaw = $this->db->fetchAll('SELECT * FROM assets_metadata WHERE cid = ?', [$data['id']]);
                $metadata = [];
                foreach ($metadataRaw as $md) {
                    unset($md['cid']);
                    $metadata[] = $md;
                }
                $this->model->setMetadata($metadata);
            }
        } else {
            throw new \Exception('Asset with ID ' . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the asset from database for the given path
     *
     * @param string $path
     *
     * @throws \Exception
     */
    public function getByPath($path)
    {

        // check for root node
        $_path = $path != '/' ? dirname($path) : $path;
        $_path = str_replace('\\', '/', $_path); // windows patch
        $_key = basename($path);
        $_path .= $_path != '/' ? '/' : '';

        $data = $this->db->fetchRow('SELECT id FROM assets WHERE path = ' . $this->db->quote($_path) . ' and `filename` = ' . $this->db->quote($_key));

        if ($data['id']) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('asset with path: ' . $path . " doesn't exist");
        }
    }

    /**
     * Create a the new object in database, an get the new assigned ID
     *
     * @throws \Exception
     */
    public function create()
    {
        try {
            $this->db->insert('assets', [
                'filename' => $this->model->getFilename(),
                'path' => $this->model->getRealPath(),
                'parentId' => $this->model->getParentId()
            ]);

            $date = time();
            $this->model->setId($this->db->lastInsertId());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update data from object to the database
     *
     * @throws \Exception
     */
    public function update()
    {
        try {
            $this->model->setModificationDate(time());

            $asset = get_object_vars($this->model);

            foreach ($asset as $key => $value) {
                if (in_array($key, $this->getValidTableColumns('assets'))) {
                    if (is_array($value)) {
                        $value = \Pimcore\Tool\Serialize::serialize($value);
                    }
                    $data[$key] = $value;
                }
            }

            // metadata
            $this->db->delete('assets_metadata', ['cid' => $this->model->getId()]);
            $metadata = $this->model->getMetadata();
            $data['hasMetaData'] = 0;
            if (!empty($metadata)) {
                foreach ($metadata as $metadataItem) {
                    $metadataItem['cid'] = $this->model->getId();
                    unset($metadataItem['config']);

                    if ($metadataItem['data'] instanceof Model\Element\ElementInterface) {
                        $metadataItem['data'] = $metadataItem['data']->getId();
                    }

                    if (is_scalar($metadataItem['data']) && strlen($metadataItem['data']) > 0) {
                        $this->db->insert('assets_metadata', $metadataItem);
                        $data['hasMetaData'] = 1;
                    }
                }
            }

            $this->db->insertOrUpdate('assets', $data);

            // tree_locks
            $this->db->delete('tree_locks', ['id' => $this->model->getId(), 'type' => 'asset']);
            if ($this->model->getLocked()) {
                $this->db->insert('tree_locks', [
                    'id' => $this->model->getId(),
                    'type' => 'asset',
                    'locked' => $this->model->getLocked()
                ]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove the object from database
     *
     * @throws \Exception
     */
    public function delete()
    {
        try {
            $this->db->delete('assets', ['id' => $this->model->getId()]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function updateWorkspaces()
    {
        $this->db->update('users_workspaces_asset', [
            'cpath' => $this->model->getRealFullPath()
        ], [
            'cid' => $this->model->getId()
        ]);
    }

    /**
     * @param $oldPath
     *
     * @return array
     */
    public function updateChildsPaths($oldPath)
    {
        //get assets to empty their cache
        $assets = $this->db->fetchCol('SELECT id FROM assets WHERE path LIKE ' . $this->db->quote($oldPath . '%'));

        $userId = '0';
        if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
            $userId = $user->getId();
        }

        //update assets child paths
        $this->db->query('update assets set path = replace(path,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . "), modificationDate = '" . time() . "', userModification = '" . $userId . "' where path like " . $this->db->quote($oldPath . '/%') . ';');

        //update assets child permission paths
        $this->db->query('update users_workspaces_asset set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote($oldPath . '/%') . ';');

        //update assets child properties paths
        $this->db->query('update properties set cpath = replace(cpath,' . $this->db->quote($oldPath . '/') . ',' . $this->db->quote($this->model->getRealFullPath() . '/') . ') where cpath like ' . $this->db->quote($oldPath . '/%') . ';');

        return $assets;
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
        $propertiesRaw = $this->db->fetchAll('SELECT * FROM properties WHERE ((cid IN (' . implode(',', $parentIds) . ") AND inheritable = 1) OR cid = ? )  AND ctype='asset'", [$this->model->getId()]);

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
                $property->setCtype('asset');
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
                Logger::error("can't add property " . $propertyRaw['name'] . ' to asset ' . $this->model->getRealFullPath());
            }
        }

        // if only inherited then only return it and dont call the setter in the model
        if ($onlyInherited) {
            return $properties;
        }

        $this->model->setProperties($properties);

        return $properties;
    }

    /**
     * deletes all properties for the object from database
     */
    public function deleteAllProperties()
    {
        $this->db->delete('properties', ['cid' => $this->model->getId(), 'ctype' => 'asset']);
    }

    /**
     * deletes all metadata for the object from database
     */
    public function deleteAllMetadata()
    {
        $this->db->delete('assets_metadata', ['cid' => $this->model->getId()]);
    }

    /**
     * get versions from database, and assign it to object
     *
     * @return array
     */
    public function getVersions()
    {
        $versionIds = $this->db->fetchAll("SELECT id FROM versions WHERE cid = ? AND ctype='asset' ORDER BY `id` DESC", [$this->model->getId()]);

        $versions = [];
        foreach ($versionIds as $versionId) {
            $versions[] = Model\Version::getById($versionId['id']);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    public function deleteAllPermissions()
    {
        $this->db->delete('users_workspaces_asset', ['cid' => $this->model->getId()]);
    }

    public function deleteAllTasks()
    {
        $this->db->delete('schedule_tasks', ['cid' => $this->model->getId(), 'ctype' => 'asset']);
    }

    /**
     * @return string retrieves the current full set path from DB
     */
    public function getCurrentFullPath()
    {
        $path = null;

        try {
            $path = $this->db->fetchOne('SELECT CONCAT(path,filename) as path FROM assets WHERE id = ?', $this->model->getId());
        } catch (\Exception $e) {
            Logger::error('could not get  current asset path from DB');
        }

        return $path;
    }

    /**
     * quick test if there are childs
     *
     * @return bool
     */
    public function hasChildren()
    {
        $c = $this->db->fetchOne('SELECT id FROM assets WHERE parentId = ?', $this->model->getId());

        return (bool)$c;
    }

    /**
     * Quick test if there are siblings
     *
     * @return bool
     */
    public function hasSiblings()
    {
        $c = $this->db->fetchOne('SELECT id FROM assets WHERE parentId = ? and id != ? LIMIT 1', [$this->model->getParentId(), $this->model->getId()]);

        return (bool)$c;
    }

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @param Model\User $user
     *
     * @return int
     */
    public function getChildAmount($user = null)
    {
        if ($user and !$user->isAdmin()) {
            $userIds = $user->getRoles();
            $userIds[] = $user->getId();

            $query = 'select count(*) from assets a where parentId = ?
                            and (select list as locate from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(a.path,a.filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1;';
        } else {
            $query = 'SELECT COUNT(*) AS count FROM assets WHERE parentId = ?';
        }

        $c = $this->db->fetchOne($query, $this->model->getId());

        return $c;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks INNER JOIN assets ON tree_locks.id = assets.id WHERE assets.path LIKE ? AND tree_locks.type = 'asset' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", $this->model->getRealFullPath() . '/%');

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne('SELECT id FROM tree_locks WHERE id IN (' . implode(',', $parentIds) . ") AND type='asset' AND locked = 'propagate' LIMIT 1");

        if ($inhertitedLocks > 0) {
            return true;
        }

        return false;
    }

    public function unlockPropagate()
    {
        $lockIds = $this->db->fetchCol('SELECT id from assets WHERE path LIKE ' . $this->db->quote($this->model->getRealFullPath() . '/%') . ' OR id = ' . $this->model->getId());
        $this->db->deleteWhere('tree_locks', "type = 'asset' AND id IN (" . implode(',', $lockIds) . ')');

        return $lockIds;
    }

    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     *
     * @param bool $force
     *
     * @return array
     */
    public function getLatestVersion($force = false)
    {
        if ($this->model->getType() != 'folder') {
            $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='asset' ORDER BY `id` DESC LIMIT 1", $this->model->getId());

            if ($versionData && $versionData['id'] && ($versionData['date'] > $this->model->getModificationDate() || $force)) {
                $version = Model\Version::getById($versionData['id']);

                return $version;
            }
        }

        return;
    }

    /**
     * @param $type
     * @param $user
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
        $parentIds[] = $this->model->getId();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne('SELECT `' . $type . '` FROM users_workspaces_asset WHERE cid IN (' . implode(',', $parentIds) . ') AND userId IN (' . implode(',', $userIds) . ') ORDER BY LENGTH(cpath) DESC, ABS(userId-' . $user->getId() . ') ASC LIMIT 1');

            if ($permissionsParent) {
                return true;
            }

            // exception for list permission
            if (empty($permissionsParent) && $type == 'list') {
                // check for childs with permissions
                $path = $this->model->getRealFullPath() . '/';
                if ($this->model->getId() == 1) {
                    $path = '/';
                }

                $permissionsChilds = $this->db->fetchOne('SELECT list FROM users_workspaces_asset WHERE cpath LIKE ? AND userId IN (' . implode(',', $userIds) . ') AND list = 1 LIMIT 1', $path . '%');
                if ($permissionsChilds) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Logger::warn('Unable to get permission ' . $type . ' for asset ' . $this->model->getId());
        }

        return false;
    }

    /**
     * @return bool
     */
    public function __isBasedOnLatestData()
    {
        $currentDataTimestamp = $this->db->fetchOne('SELECT modificationDate from assets WHERE id = ?', $this->model->getId());
        if ($currentDataTimestamp == $this->model->__getDataVersionTimestamp()) {
            return true;
        }

        return false;
    }
}
