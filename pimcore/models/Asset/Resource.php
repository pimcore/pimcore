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
        $data = $this->db->fetchRow("SELECT * FROM assets WHERE id = ?", $id);
        if ($data["id"] > 0) {
            $this->assignVariablesToModel($data);
        }
        else {
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
        $_path = $path != "/" ? $_path = dirname($path) : $path;
        $_path = str_replace("\\", "/", $_path); // windows patch
        $_key = basename($path);
        $_path .= $_path != "/" ? "/" : "";

        $data = $this->db->fetchRow("SELECT id FROM assets WHERE path = " . $this->db->quote($_path) . " and `filename` = " . $this->db->quote($_key));

        if ($data["id"]) {
            $this->assignVariablesToModel($data);
        }
        else {
            throw new Exception("asset doesn't exist");
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
                        $value = serialize($value);
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
        $this->db->query("update assets_permissions set cpath = replace(cpath," . $this->db->quote($oldPath) . "," . $this->db->quote($this->model->getFullPath()) . ") where cpath like " . $this->db->quote($oldPath . "/%") . ";");

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
        $parentIds = array(1);
        $obj = $this->model->getParent();

        if($obj) {
            while($obj) {
                $parentIds[] = $obj->getId();
                $obj = $obj->getParent();
            }
        }

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
     * get recursivly the permissions for the passed user
     *
     * @param User $user
     * @return Asset_Permission
     */
    public function getPermissionsForUser(User $user) {
        $pathParts = explode("/", $this->model->getPath() . $this->model->getFilename());
        unset($pathParts[0]);
        $tmpPathes = array();
        $pathConditionParts[] = "cpath = '/'";
        foreach ($pathParts as $pathPart) {
            $tmpPathes[] = $pathPart;
            $pathConditionParts[] = $this->db->quoteInto("cpath = ?", "/" . implode("/", $tmpPathes));
        }

        $pathCondition = implode(" OR ", $pathConditionParts);
        $permissionRaw = $this->db->fetchRow("SELECT id FROM assets_permissions WHERE (" . $pathCondition . ") AND userId = ? ORDER BY cpath DESC LIMIT 1", $user->getId());

        //path condition for parent asset
        $parentAssetPathParts = array_slice($pathParts, 0, -1);
        $parentAssetPathConditionParts[] = "cpath = '/'";
        foreach ($parentAssetPathParts as $parentAssetPathPart) {
            $parentAssetTmpPaths[] = $parentAssetPathPart;
            $parentAssetPathConditionParts[] = $this->db->quoteInto("cpath = ?", "/" . implode("/", $parentAssetTmpPaths));
        }
        $parentAssetPathCondition = implode(" OR ", $parentAssetPathConditionParts);
        $parentAssetPermissionRaw = $this->db->fetchRow("SELECT id FROM assets_permissions WHERE (" . $parentAssetPathCondition . ") AND userId = ? ORDER BY cpath DESC LIMIT 1", $user->getId());
        $parentAssetPermissions = new Asset_Permissions();
        if ($parentAssetPermissionRaw["id"]) {

            $parentAssetPermissions = Asset_Permissions::getById($parentAssetPermissionRaw["id"]);
        }


        $parentUser = $user->getParent();
        if ($parentUser instanceof User and $parentUser->isAllowed("assets")) {
            $parentPermission = $this->getPermissionsForUser($parentUser);
        } else $parentPermission = null;

        $permission = new Asset_Permissions();

        if ($permissionRaw["id"] and $parentPermission instanceof Asset_Permissions ) {

            //consider user group permissions
            $permission = Asset_Permissions::getById($permissionRaw["id"]);
            $permissionKeys = $permission->getValidPermissionKeys();

            foreach ($permissionKeys as $key) {
                $getter = "get" . ucfirst($key);
                $setter = "set" . ucfirst($key);

                if ((!$permission->getList() and !$parentPermission->getList())  or !$parentAssetPermissions->getList()) {
                    //no list - return false for all
                    $permission->$setter(false);
                } else if ($parentPermission->$getter()) {
                    //if user group allows -> return true, it overrides the user permission!
                    $permission->$setter(true);
                }
            }


        } else if ($permissionRaw["id"]) {
            //use user permissions, no user group to override anything
            $permission = Asset_Permissions::getById($permissionRaw["id"]);

            //check parent asset's list permission and current object's list permission
            if (!$parentAssetPermissions->getList() or !$permission->getList()) {
                $permissionKeys = $permission->getValidPermissionKeys();
                foreach ($permissionKeys as $key) {
                    $setter = "set" . ucfirst($key);
                    $permission->$setter(false);
                }
            }

        } else if ($parentPermission instanceof Asset_Permissions and $parentPermission->getId() > 0) {
            //use user group permissions - no permission found for user at all
            $permission = $parentPermission;
            //check parent asset's list permission and current object's list permission
            if (!$parentAssetPermissions->getList() or !$permission->getList()) {
                $permissionKeys = $permission->getValidPermissionKeys();
                foreach ($permissionKeys as $key) {
                    $setter = "set" . ucfirst($key);
                    $permission->$setter(false);
                }
            }

        } else {
            //neither user group nor user has permissions set -> use default all allowed
            $permission->setUser($user);
            $permission->setUserId($user->getId());
            $permission->setUsername($user->getUsername());
            $permission->setCid($this->model->getId());
            $permission->setCpath($this->model->getFullPath());

        }

        $this->model->setUserPermissions($permission);
        return $permission;
    }


    /**
     * all user permissions for this document
     * @return void
     */

    public function getPermissions() {

        $permissions = array();

        $permissionsRaw = $this->db->fetchAll("SELECT id FROM assets_permissions WHERE cid = ? ORDER BY cpath ASC", $this->model->getId());

        $userIdMappings = array();
        foreach ($permissionsRaw as $permissionRaw) {
            $permissions[] = Asset_Permissions::getById($permissionRaw["id"]);
        }


        $this->model->setPermissions($permissions);

        return $permissions;
    }


    /**
     * @return void
     */
    public function deleteAllPermissions() {
        $this->db->delete("assets_permissions", $this->db->quoteInto("cid = ?", $this->model->getId()));
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
            $data = $this->db->fetchRow("SELECT CONCAT(path,filename) as path FROM assets WHERE id = ?", $this->model->getId());
        }
        catch (Exception $e) {
            Logger::error("could not get  current asset path from DB");
        }

        return $data['path'];

    }


    /**
     * quick test if there are childs
     *
     * @return boolean
     */
    public function hasChilds() {
        $c = $this->db->fetchRow("SELECT id FROM assets WHERE parentId = ?", $this->model->getId());

        $state = false;
        if ($c["id"]) {
            $state = true;
        }

        $this->model->hasChilds = $state;

        return $state;
    }

    /**
     * returns the amount of directly childs (not recursivly)
     *
     * @return integer
     */
    public function getChildAmount() {
        $c = $this->db->fetchRow("SELECT COUNT(*) AS count FROM assets WHERE parentId = ?", $this->model->getId());
        return $c["count"];
    }
    
    
    public function isLocked () {
        
        // check for an locked element below this element
        $belowLocks = $this->db->fetchRow("SELECT id FROM assets WHERE path LIKE ? AND locked IS NOT NULL AND locked != '';", $this->model->getFullpath() . "%");
        
        if(is_array($belowLocks) && count($belowLocks) > 0) {
            return true;
        }
        
        // check for an inherited lock
        $pathParts = explode("/", $this->model->getFullPath());
        unset($pathParts[0]);
        $tmpPathes = array();
        $pathConditionParts[] = "CONCAT(path,`filename`) = '/'";
        foreach ($pathParts as $pathPart) {
            $tmpPathes[] = $pathPart;
            $pathConditionParts[] = $this->db->quoteInto("CONCAT(path,`filename`) = ?", "/" . implode("/", $tmpPathes));
        }

        $pathCondition = implode(" OR ", $pathConditionParts);
        $inhertitedLocks = $this->db->fetchAll("SELECT id FROM assets WHERE (" . $pathCondition . ") AND locked = 'propagate';");
        
        if(is_array($inhertitedLocks) && count($inhertitedLocks) > 0) {
            return true;
        }
        
        
        return false;
    }
}  
