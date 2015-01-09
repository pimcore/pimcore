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

namespace Pimcore\Model\Object\AbstractObject;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Element\Resource
{

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumnsBase = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init()
    {
        $this->validColumnsBase = $this->getValidTableColumns("objects");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT objects.*, tree_locks.locked as o_locked FROM objects
            LEFT JOIN tree_locks ON objects.o_id = tree_locks.id AND tree_locks.type = 'object'
                WHERE o_id = ?", $id);

        if ($data["o_id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Object with the ID " . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the object from database for the given path
     *
     * @param string $path
     * @throws \Exception
     */
    public function getByPath($path)
    {

        // check for root node
        $_path = $path != "/" ? dirname($path) : $path;
        $_path = str_replace("\\", "/", $_path); // windows patch
        $_key = basename($path);
        $_path .= $_path != "/" ? "/" : "";

        $data = $this->db->fetchRow("SELECT o_id FROM objects WHERE o_path = " . $this->db->quote($_path) . " and `o_key` = " . $this->db->quote($_key));

        if ($data["o_id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("object doesn't exist");
        }
    }


    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create()
    {
        $this->db->insert("objects", array(
            "o_key" => $this->model->getKey(),
            "o_path" => $this->model->getPath()
        ));
        $this->model->setId($this->db->lastInsertId());

        if (!$this->model->getKey()) {
            $this->model->setKey($this->db->lastInsertId());
        }
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update()
    {

        $object = get_object_vars($this->model);

        $data = array();
        foreach ($object as $key => $value) {
            if (in_array($key, $this->validColumnsBase)) {
                if (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("objects", $data);

        // tree_locks
        $this->db->delete("tree_locks", "id = " . $this->model->getId() . " AND type = 'object'");
        if ($this->model->getLocked()) {
            $this->db->insert("tree_locks", array(
                "id" => $this->model->getId(),
                "type" => "object",
                "locked" => $this->model->getLocked()
            ));
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->delete("objects", $this->db->quoteInto("o_id = ?", $this->model->getId()));
    }


    public function updateWorkspaces() {
        $this->db->update("users_workspaces_object", array(
            "cpath" => $this->model->getFullPath()
        ), "cid = " . $this->model->getId());
    }

    /**
     * Updates the paths for children, children's properties and children's permissions in the database
     *
     * @param string $oldPath
     * @return void
     */
    public function updateChildsPaths($oldPath)
    {

        if ($this->hasChilds()) {
            //get objects to empty their cache
            $objects = $this->db->fetchCol("SELECT o_id FROM objects WHERE o_path LIKE ?", $oldPath . "%");

            $userId = "0";
            if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
                $userId = $user->getId();
            }

            //update object child paths
            $this->db->query("update objects set o_path = replace(o_path," . $this->db->quote($oldPath . "/") . "," . $this->db->quote($this->model->getFullPath() . "/") . "), o_modificationDate = '" . time() . "', o_userModification = '" . $userId . "' where o_path like " . $this->db->quote($oldPath . "/%") . ";");

            //update object child permission paths
            $this->db->query("update users_workspaces_object set cpath = replace(cpath," . $this->db->quote($oldPath . "/") . "," . $this->db->quote($this->model->getFullPath() . "/") . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");

            //update object child properties paths
            $this->db->query("update properties set cpath = replace(cpath," . $this->db->quote($oldPath . "/") . "," . $this->db->quote($this->model->getFullPath() . "/") . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");


            return $objects;
        }
    }


    /**
     * deletes all properties for the object from database
     *
     * @return void
     */
    public function deleteAllProperties()
    {
        $this->db->delete("properties", $this->db->quoteInto("cid = ? AND ctype = 'object'", $this->model->getId()));
    }

    /**
     * @return string retrieves the current full object path from DB
     */
    public function getCurrentFullPath()
    {

        $path = null;
        try {
            $path = $this->db->fetchOne("SELECT CONCAT(o_path,`o_key`) as o_path FROM objects WHERE o_id = ?", $this->model->getId());
        } catch (\Exception $e) {
            \Logger::error("could not get current object path from DB");
        }
        return $path;
    }


    /**
     * Get the properties for the object from database and assign it
     *
     * @return void
     */
    public function getProperties($onlyInherited = false)
    {

        $properties = array();

        // collect properties via parent - ids
        $parentIds = $this->getParentIds();
        $propertiesRaw = $this->db->fetchAll("SELECT * FROM properties WHERE ((cid IN (" . implode(",", $parentIds) . ") AND inheritable = 1) OR cid = ? )  AND ctype='object'", $this->model->getId());

        // because this should be faster than mysql
        usort($propertiesRaw, function ($left, $right) {
            return strcmp($left["cpath"], $right["cpath"]);
        });

        foreach ($propertiesRaw as $propertyRaw) {

            try {
                $property = new Model\Property();
                $property->setType($propertyRaw["type"]);
                $property->setCid($this->model->getId());
                $property->setName($propertyRaw["name"]);
                $property->setCtype("object");
                $property->setDataFromResource($propertyRaw["data"]);
                $property->setInherited(true);
                if ($propertyRaw["cid"] == $this->model->getId()) {
                    $property->setInherited(false);
                }
                $property->setInheritable(false);
                if ($propertyRaw["inheritable"]) {
                    $property->setInheritable(true);
                }

                if ($onlyInherited && !$property->getInherited()) {
                    continue;
                }

                $properties[$propertyRaw["name"]] = $property;
            } catch (\Exception $e) {
                \Logger::error("can't add property " . $propertyRaw["name"] . " to object " . $this->model->getFullPath());
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
     *
     * @return void
     */
    public function deleteAllPermissions()
    {
        $this->db->delete("users_workspaces_object", $this->db->quoteInto("cid = ?", $this->model->getId()));
    }

    /**
     * Quick test if there are childs
     *
     * @return boolean
     */
    public function hasChilds($objectTypes = array(Object::OBJECT_TYPE_OBJECT, Object::OBJECT_TYPE_FOLDER))
    {
        $c = $this->db->fetchOne("SELECT o_id FROM objects WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')", $this->model->getId());
        return (bool)$c;
    }

	/**
	 * Quick test if there are siblings
	 *
	 * @param array $objectTypes
	 * @return boolean
	 */
	public function hasSiblings($objectTypes = array(Object::OBJECT_TYPE_OBJECT, Object::OBJECT_TYPE_FOLDER)) {
		$c = $this->db->fetchOne("SELECT o_id FROM objects WHERE o_parentId = ? and o_id != ? AND o_type IN ('" . implode("','", $objectTypes) . "')", [$this->model->getParentId(), $this->model->getId()]);
		return (bool)$c;
	}

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @param User $user
     * @return integer
     */
    public function getChildAmount($objectTypes = array(Object::OBJECT_TYPE_OBJECT, Object::OBJECT_TYPE_FOLDER), $user = null)
    {
        if ($user and !$user->isAdmin()) {
            $userIds = $user->getRoles();
            $userIds[] = $user->getId();

            $query = "SELECT COUNT(*) AS count FROM objects o WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')
                              AND (select list as locate from users_workspaces_object where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(o.o_path,o.o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1;";


        } else {
            $query = "SELECT COUNT(*) AS count FROM objects WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')";
        }

        $c = $this->db->fetchOne($query, $this->model->getId());
        return $c;
    }


    public function getTypeById($id)
    {

        $t = $this->db->fetchRow("SELECT o_type,o_className,o_classId FROM objects WHERE o_id = ?", $id);
        return $t;
    }


    public function isLocked()
    {

        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks INNER JOIN objects ON tree_locks.id = objects.o_id WHERE objects.o_path LIKE ? AND tree_locks.type = 'object' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", $this->model->getFullpath() . "/%");

        if ($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne("SELECT id FROM tree_locks WHERE id IN (" . implode(",", $parentIds) . ") AND type='object' AND locked = 'propagate' LIMIT 1");

        if ($inhertitedLocks > 0) {
            return true;
        }


        return false;
    }

    /**
     *
     */
    public function unlockPropagate() {
        $lockIds = $this->db->fetchCol("SELECT o_id from objects WHERE o_path LIKE " . $this->db->quote($this->model->getFullPath() . "/%") . " OR o_id = " . $this->model->getId());
        $this->db->delete("tree_locks", "type = 'object' AND id IN (" . implode(",", $lockIds) . ")");
        return $lockIds;
    }

    public function getClasses()
    {
        if ($this->getChildAmount()) {
            $path = $this->model->getFullPath();
            if (!$this->model->getId() || $this->model->getId() == 1) {
                $path = "";
            }
            $classIds = $this->db->fetchCol("SELECT o_classId FROM objects WHERE o_path LIKE ? AND o_type = 'object' GROUP BY o_classId", $path . "/%");

            $classes = array();
            foreach ($classIds as $classId) {
                $classes[] = Object\ClassDefinition::getById($classId);
            }

            return $classes;

        } else {
            return array();
        }

    }

    protected function collectParentIds() {
        // collect properties via parent - ids
        $parentIds = array(1);

        $obj = $this->model->getParent();
        if ($obj) {
            while ($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        $parentIds[] = $this->model->getId();
        return $parentIds;
    }

    public function isAllowed($type, $user)
    {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne("SELECT `" . $type . "` FROM users_workspaces_object WHERE cid IN (" . implode(",", $parentIds) . ") AND userId IN (" . implode(",", $userIds) . ") ORDER BY LENGTH(cpath) DESC, ABS(userId-" . $user->getId() . ") ASC LIMIT 1");

            if ($permissionsParent) {
                return true;
            }

            // exception for list permission
            if (empty($permissionsParent) && $type == "list") {
                // check for childs with permissions
                $path = $this->model->getFullPath() . "/";
                if ($this->model->getId() == 1) {
                    $path = "/";
                }

                $permissionsChilds = $this->db->fetchOne("SELECT list FROM users_workspaces_object WHERE cpath LIKE ? AND userId IN (" . implode(",", $userIds) . ") LIMIT 1", $path . "%");
                if ($permissionsChilds) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            \Logger::warn("Unable to get permission " . $type . " for object " . $this->model->getId());
        }

        return false;
    }

    public function getPermissions($type, $user, $quote = true) {
        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            if ($type && $quote) {
                $type = "`" . $type . "`";
            } else {
                $type = "*";
            }

            $permissions = $this->db->fetchRow("SELECT " . $type . " FROM users_workspaces_object WHERE cid IN (" . implode(",", $parentIds) . ") AND userId IN (" . implode(",", $userIds) . ") ORDER BY LENGTH(cpath) DESC LIMIT 1");
        } catch (\Exception $e) {
            \Logger::warn("Unable to get permission " . $type . " for object " . $this->model->getId());
        }

        return $permissions;
    }

    public function getChildPermissions($type, $user, $quote = true) {
//        $parentIds = $this->collectParentIds();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            if ($type && $quote) {
                $type = "`" . $type . "`";
            } else {
                $type = "*";
            }

            $cid = $this->model->getId();
            $sql = "SELECT " . $type . " FROM users_workspaces_object WHERE cid != " . $cid . " AND cpath LIKE " . $this->db->quote($this->model->getFullPath() . "%") . " AND userId IN (" . implode(",", $userIds) . ") ORDER BY LENGTH(cpath) DESC";
            $permissions = $this->db->fetchAll($sql);
        } catch (\Exception $e) {
            \Logger::warn("Unable to get permission " . $type . " for object " . $this->model->getId());
        }

        return $permissions;
    }

}
