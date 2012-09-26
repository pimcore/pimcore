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

class Object_Abstract_Resource extends Element_Resource {

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
    public function init() {
        $this->validColumnsBase = $this->getValidTableColumns("objects");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT objects.*, tree_locks.locked as o_locked FROM objects
            LEFT JOIN tree_locks ON objects.o_id = tree_locks.id AND tree_locks.type = 'object'
                WHERE o_id = ?", $id);

        if ($data["o_id"]) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("Object with the ID " . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the object from database for the given path
     *
     * @param string $path
     * @return void
     */
    public function getByPath($path) {

        // check for root node
        $_path = $path != "/" ? dirname($path) : $path;
        $_path = str_replace("\\", "/", $_path); // windows patch
        $_key = basename($path);
        $_path .= $_path != "/" ? "/" : "";

        $data = $this->db->fetchRow("SELECT o_id FROM objects WHERE o_path = " . $this->db->quote($_path) . " and `o_key` = " . $this->db->quote($_key));

        if ($data["o_id"]) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("object doesn't exist");
        }
    }


    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {


        $this->db->insert("objects", array());
        $this->model->setO_id($this->db->lastInsertId());

        if (!$this->model->geto_key()) {
            $this->model->setO_key($this->db->lastInsertId());
        }
        //$this->model->save();
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {

        $object = get_object_vars($this->model);

        $data = array();
        foreach ($object as $key => $value) {
            if (in_array($key, $this->validColumnsBase)) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        // first try to insert a new record, this is because of the recyclebin restore
        if($this->insertOrUpdate && $this->insertOrUpdate["object"]) {
            // this is for object_concrete which exist already
            $this->db->update("objects", $data, $this->db->quoteInto("o_id = ?", $this->model->getO_id() ));
        } else {
            // insert and fallback for folders etc. where the $this->insertOrUpdate is not set
            try {
                $this->db->insert("objects", $data);
            } catch (Exception $e) {
                $this->db->update("objects", $data, $this->db->quoteInto("o_id = ?", $this->model->getO_id() ));
            }
        }

        // tree_locks
        $this->db->delete("tree_locks", "id = " . $this->model->getId() . " AND type = 'object'");
        if($this->model->getLocked()) {
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
    public function delete() {
        $this->db->delete("objects", $this->db->quoteInto("o_id = ?", $this->model->getO_id() ));
    }

    /**
     * Updates the paths for children, children's properties and children's permissions in the database
     *
     * @param string $oldPath
     * @return void
     */
    public function updateChildsPaths($oldPath) {

        if($this->hasChilds()) {
            //get objects to empty their cache
            $objects = $this->db->fetchAll("SELECT o_id,o_path FROM objects WHERE o_path LIKE ?", $oldPath . "%");

            //update object child paths
            $this->db->query("update objects set o_path = replace(o_path," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where o_path like " . $this->db->quote($oldPath . "/%") .";");

            //update object child permission paths
            $this->db->query("update users_workspaces_object set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");

            //update object child properties paths
            $this->db->query("update properties set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");


            foreach ($objects as $object) {
                // empty object cache
                try {
                    Pimcore_Model_Cache::clearTag("object_" . $object["o_id"]);
                }
                catch (Exception $e) {
                }
            }
        }
    }


    /**
     * deletes all properties for the object from database
     *
     * @return void
     */
    public function deleteAllProperties() {
        $this->db->delete("properties", $this->db->quoteInto("cid = ? AND ctype = 'object'", $this->model->getId()));
    }

    /**
     * @return string retrieves the current full object path from DB
     */
    public function getCurrentFullPath() {
        try {
            $path = $this->db->fetchOne("SELECT CONCAT(o_path,`o_key`) as o_path FROM objects WHERE o_id = ?", $this->model->getId());
        }
        catch (Exception $e) {
            Logger::error("could not get current object path from DB");
        }
        return $path;
    }


    /**
     * Get the properties for the object from database and assign it
     *
     * @return void
     */
    public function getProperties($onlyInherited = false) {

        $properties = array();

        // collect properties via parent - ids
        $parentIds = $this->getParentIds();
        $propertiesRaw = $this->db->fetchAll("SELECT * FROM properties WHERE ((cid IN (".implode(",",$parentIds).") AND inheritable = 1) OR cid = ? )  AND ctype='object'", $this->model->getId());

        // because this should be faster than mysql
        usort($propertiesRaw, function($left,$right) {
           return strcmp($left["cpath"],$right["cpath"]);
        });

        foreach ($propertiesRaw as $propertyRaw) {

            try {
                $property = new Property();
                $property->setType($propertyRaw["type"]);
                $property->setCid($this->model->getO_Id());
                $property->setName($propertyRaw["name"]);
                $property->setCtype("object");
                $property->setDataFromResource($propertyRaw["data"]);
                $property->setInherited(true);
                if ($propertyRaw["cid"] == $this->model->getO_Id()) {
                    $property->setInherited(false);
                }
                $property->setInheritable(false);
                if ($propertyRaw["inheritable"]) {
                    $property->setInheritable(true);
                }
                
                if($onlyInherited && !$property->getInherited()) {
                    continue;
                }
                
                $properties[$propertyRaw["name"]] = $property;
            }
            catch (Exception $e) {
                Logger::error("can't add property " . $propertyRaw["name"] . " to object " . $this->model->getFullPath());
            }
        }
        
        // if only inherited then only return it and dont call the setter in the model
        if($onlyInherited) {
            return $properties;
        }
        
        $this->model->setO_Properties($properties);

        return $properties;
    }

    /**
     *
     * @return void
     */
    public function deleteAllPermissions() {
        $this->db->delete("users_workspaces_object", $this->db->quoteInto("cid = ?", $this->model->getO_Id()));
    }

    /**
     * Quick test if there are childs
     *
     * @return boolean
     */
    public function hasChilds($objectTypes = array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER)) {
        $c = $this->db->fetchOne("SELECT o_id FROM objects WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')", $this->model->getO_id());
        return (bool) $c;
    }

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @return integer
     */
    public function getChildAmount($objectTypes = array(Object_Abstract::OBJECT_TYPE_OBJECT, Object_Abstract::OBJECT_TYPE_FOLDER)) {
        $c = $this->db->fetchOne("SELECT COUNT(*) AS count FROM objects WHERE o_parentId = ? AND o_type IN ('" . implode("','", $objectTypes) . "')", $this->model->getO_id());
        return $c;
    }


    public function getTypeById($id) {

        $t = $this->db->fetchRow("SELECT o_type,o_className,o_classId FROM objects WHERE o_id = ?", $id);
        return $t;
    }
    
    
    public function isLocked () {

        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks INNER JOIN objects ON tree_locks.id = objects.o_id WHERE objects.o_path LIKE ? AND tree_locks.type = 'object' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", $this->model->getFullpath() . "/%");

        if($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne("SELECT id FROM tree_locks WHERE id IN (".implode(",",$parentIds).") AND type='object' AND locked = 'propagate' LIMIT 1");

        if($inhertitedLocks > 0) {
            return true;
        }


        return false;
    }

    public function getClasses() {
        if($this->getChildAmount()) {
            $classIds = $this->db->fetchCol("SELECT o_classId FROM objects WHERE o_path LIKE ? AND o_type = 'object' GROUP BY o_classId", $this->model->getFullPath() . "%");

            $classes = array();
            foreach ($classIds as $classId) {
                $classes[] = Object_Class::getById($classId);
            }

            return $classes;

        } else {
            return array();
        }

    }

    public function isAllowed($type, $user) {

        // collect properties via parent - ids
        $parentIds = array(1);

        $obj = $this->model->getParent();
        if($obj) {
            while($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }
        $parentIds[] = $this->model->getId();

        $userIds = $user->getRoles();
        $userIds[] = $user->getId();

        try {
            $permissionsParent = $this->db->fetchOne("SELECT `" . $type . "` FROM users_workspaces_object WHERE cid IN (".implode(",",$parentIds).") AND userId IN (" . implode(",",$userIds) . ") ORDER BY LENGTH(cpath) DESC LIMIT 1");

            if($permissionsParent) {
                return true;
            }

            // exception for list permission
            if(empty($permissionsParent) && $type == "list") {
                // check for childs with permissions
                $path = $this->model->getFullPath() . "/";
                if($this->model->getId() == 1) {
                    $path = "/";
                }

                $permissionsChilds = $this->db->fetchOne("SELECT list FROM users_workspaces_object WHERE cpath LIKE ? AND userId IN (" . implode(",",$userIds) . ") LIMIT 1", $path."%");
                if($permissionsChilds) {
                    return true;
                }
            }
        } catch (Exception $e) {
            Logger::warn("Unable to get permission " . $type . " for object " . $this->model->getId());
        }

        return false;
    }

}
