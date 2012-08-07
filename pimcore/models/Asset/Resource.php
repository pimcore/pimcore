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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Resource extends Element_Resource {

    /**
     * List of valid columns in database table
     * This is used for automatic matching the objects properties to the database
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("assets");
    }

    /**
     * Get the data for the object by id from database and assign it to the object (model)
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT assets.*, tree_locks.locked FROM assets
            LEFT JOIN tree_locks ON assets.id = tree_locks.id AND tree_locks.type = 'asset'
                WHERE assets.id = ?", $id);

        if ($data["id"] > 0) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Exception("Asset with ID " . $id . " doesn't exists");
        }
    }

    /**
     * Get the data for the asset from database for the given path
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

        $data = $this->db->fetchRow("SELECT id FROM assets WHERE path = " . $this->db->quote($_path) . " and `filename` = " . $this->db->quote($_key));

        if ($data["id"]) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("asset with path: " . $path . " doesn't exist");
        }
    }

    /**
     * Create a the new object in database, an get the new assigned ID
     *
     * @return void
     */
    public function create() {
        try {


            $this->db->insert("assets", array(
                "path" => $this->model->getPath(),
                "parentId" => $this->model->getParentId()
            ));

            $date = time();
            $this->model->setId($this->db->lastInsertId());
            $this->model->setCreationDate($date);
            $this->model->setModificationDate($date);

        }
        catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Update data from object to the database
     *
     * @return void
     */
    public function update() {

        try {
            $this->model->setModificationDate(time());

            $asset = get_object_vars($this->model);

            foreach ($asset as $key => $value) {
                if (in_array($key, $this->validColumns)) {

                    if (is_array($value)) {
                        $value = Pimcore_Tool_Serialize::serialize($value);
                    }
                    $data[$key] = $value;
                }
            }

            // first try to insert a new record, this is because of the recyclebin restore
            try {
                $this->db->insert("assets", $data);
            }
            catch (Exception $e) {
                $this->db->update("assets", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
            }

            // tree_locks
            $this->db->delete("tree_locks", "id = " . $this->model->getId() . " AND type = 'asset'");
            if($this->model->getLocked()) {
                $this->db->insert("tree_locks", array(
                    "id" => $this->model->getId(),
                    "type" => "asset",
                    "locked" => $this->model->getLocked()
                ));
            }
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove the object from database
     *
     * @return void
     */
    public function delete() {
        try {
            $this->db->delete("assets", $this->db->quoteInto("id = ?", $this->model->getId()));
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    public function updateChildsPaths($oldPath) {
        //get assets to empty their cache
        $assets = $this->db->fetchAll("SELECT id,path FROM assets WHERE path LIKE " . $this->db->quote($oldPath . "%"));

        //update assets child paths
        $this->db->query("update assets set path = replace(path," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where path like " . $this->db->quote($oldPath . "/%")  . ";");

        //update assets child permission paths
        $this->db->query("update users_workspaces_asset set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");

        //update assets child properties paths
        $this->db->query("update properties set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");


        foreach ($assets as $asset) {
            // empty assets cache
            try {
                Pimcore_Model_Cache::clearTag("asset_" . $asset["id"]);
            }
            catch (Exception $e) {
            }
        }

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
        $propertiesRaw = $this->db->fetchAll("SELECT * FROM properties WHERE ((cid IN (".implode(",",$parentIds).") AND inheritable = 1) OR cid = ? )  AND ctype='asset'", $this->model->getId());

        // because this should be faster than mysql
        usort($propertiesRaw, function($left,$right) {
           return strcmp($left["cpath"],$right["cpath"]);
        });

        foreach ($propertiesRaw as $propertyRaw) {
            
            try {
                $property = new Property();
                $property->setType($propertyRaw["type"]);
                $property->setCid($this->model->getId());
                $property->setName($propertyRaw["name"]);
                $property->setCtype("asset");
                $property->setDataFromResource($propertyRaw["data"]);
                $property->setInherited(true);
                if ($propertyRaw["cid"] == $this->model->getId()) {
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
                Logger::error("can't add property " . $propertyRaw["name"] . " to asset " . $this->model->getFullPath());
            }
        }
        
        // if only inherited then only return it and dont call the setter in the model
        if($onlyInherited) {
            return $properties;
        }
        
        $this->model->setProperties($properties);

        return $properties;
    }

    /**
     * deletes all properties for the object from database
     *
     * @return void
     */
    public function deleteAllProperties() {
        $this->db->delete("properties", $this->db->quoteInto("cid = ? AND ctype = 'asset'", $this->model->getId()));
    }

    /**
     * get versions from database, and assign it to object
     *
     * @return array
     */
    public function getVersions() {
        $versionIds = $this->db->fetchAll("SELECT id FROM versions WHERE cid = ? AND ctype='asset' ORDER BY `id` DESC", $this->model->getId());

        $versions = array();
        foreach ($versionIds as $versionId) {
            $versions[] = Version::getById($versionId["id"]);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return void
     */
    public function deleteAllPermissions() {
        $this->db->delete("users_workspaces_asset", $this->db->quoteInto("cid = ?", $this->model->getId()));
    }


    /**
     * @return void
     */
    public function deleteAllTasks() {
        $this->db->delete("schedule_tasks", $this->db->quoteInto("cid = ? AND ctype='asset'", $this->model->getId()));
    }

    /**
     * @return string retrieves the current full sset path from DB
     */
    public function getCurrentFullPath() {
        try {
            $path = $this->db->fetchOne("SELECT CONCAT(path,filename) as path FROM assets WHERE id = ?", $this->model->getId());
        }
        catch (Exception $e) {
            Logger::error("could not get  current asset path from DB");
        }

        return $path;
    }


    /**
     * quick test if there are childs
     *
     * @return boolean
     */
    public function hasChilds() {
        $c = $this->db->fetchOne("SELECT id FROM assets WHERE parentId = ?", $this->model->getId());
        return (bool) $c;
    }

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @return integer
     */
    public function getChildAmount() {
        $c = $this->db->fetchOne("SELECT COUNT(*) AS count FROM assets WHERE parentId = ?", $this->model->getId());
        return $c;
    }
    
    
    public function isLocked () {
        
        // check for an locked element below this element
        $belowLocks = $this->db->fetchOne("SELECT tree_locks.id FROM tree_locks INNER JOIN assets ON tree_locks.id = assets.id WHERE assets.path LIKE ? AND tree_locks.type = 'asset' AND tree_locks.locked IS NOT NULL AND tree_locks.locked != '' LIMIT 1", $this->model->getFullpath() . "/%");

        if($belowLocks > 0) {
            return true;
        }

        $parentIds = $this->getParentIds();
        $inhertitedLocks = $this->db->fetchOne("SELECT id FROM tree_locks WHERE id IN (".implode(",",$parentIds).") AND type='asset' AND locked = 'propagate' LIMIT 1");
        
        if($inhertitedLocks > 0) {
            return true;
        }
        
        
        return false;
    }

    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     * @param bool $force
     * @return array
     */
    public function getLatestVersion($force = false) {

        if($this->model->getType() != "folder") {
            $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='asset' ORDER BY `id` DESC LIMIT 1", $this->model->getId());

            if(($versionData["id"] && $versionData["date"] > $this->model->getModificationDate()) || $force) {
                $version = Version::getById($versionData["id"]);
                return $version;
            }
        }
        return;
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
            $permissionsParent = $this->db->fetchOne("SELECT `" . $type . "` FROM users_workspaces_asset WHERE cid IN (".implode(",",$parentIds).") AND userId IN (" . implode(",",$userIds) . ") ORDER BY LENGTH(cpath) DESC LIMIT 1");

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

                $permissionsChilds = $this->db->fetchOne("SELECT list FROM users_workspaces_asset WHERE cpath LIKE ? AND userId IN (" . implode(",",$userIds) . ") LIMIT 1", $path."%");
                if($permissionsChilds) {
                    return true;
                }
            }
        } catch (Exception $e) {
            Logger::warn("Unable to get permission " . $type . " for asset " . $this->model->getId());
        }

        return false;
    }
}  
