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
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_UserRole extends User_Abstract {

    /**
     * @var array
     */
    public $permissions = array();

    /**
     * @var array
     */
    public $workspacesAsset = array();

    /**
     * @var array
     */
    public $workspacesObject = array();

    /**
     * @var array
     */
    public $workspacesDocument = array();


    /**
     *
     */
    public function update () {
        $this->getResource()->update();

        // save all workspaces
        $this->getResource()->emptyWorkspaces();

        foreach ($this->getWorkspacesAsset() as $workspace) {
            $workspace->save();
        }
        foreach ($this->getWorkspacesDocument() as $workspace) {
            $workspace->save();
        }
        foreach ($this->getWorkspacesObject() as $workspace) {
            $workspace->save();
        }
    }

    /**
     *
     */
    public function setAllAclToFalse() {
        $this->permissions = array();
    }

    /**
     * @param string $permissionName
     * @param bool $value
     */
    public function setPermission($permissionName, $value = null) {

        if(!in_array($permissionName, $this->permissions) && $value) {
            $this->permissions[] = $permissionName;
        } else if (in_array($permissionName, $this->permissions) && !$value) {
            $position = array_search($permissionName, $this->permissions);
            array_splice($this->permissions, $position, 1);
        }
    }

    /**
     * @return array
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     *
     * @param String $permissionName
     * @return User_Permission $userPermission
     */
    public function getPermission($permissionName) {

        if(in_array($permissionName, $this->permissions)) {
            return true;
        }

        return false;
    }

    /**
     * Generates the permission list required for frontend display
     *
     * @return void
     */
    public function generatePermissionList() {
        $permissionInfo = null;

        $list = new User_Permission_Definition_List();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $permissionInfo[$definition->getKey()] = $this->getPermission($definition->getKey());
        }

        return $permissionInfo;
    }

    /**
     * @param array $permissions
     */
    public function setPermissions($permissions)
    {
        if(is_string($permissions)) {
            $this->permissions = explode(",", $permissions);
        } else if (is_array($permissions)) {
            $this->permissions = $permissions;
        }
    }

    /**
     * @param array $workspacesAsset
     */
    public function setWorkspacesAsset($workspacesAsset)
    {
        $this->workspacesAsset = $workspacesAsset;
    }

    /**
     * @return array
     */
    public function getWorkspacesAsset()
    {
        return $this->workspacesAsset;
    }

    /**
     * @param array $workspacesDocument
     */
    public function setWorkspacesDocument($workspacesDocument)
    {
        $this->workspacesDocument = $workspacesDocument;
    }

    /**
     * @return array
     */
    public function getWorkspacesDocument()
    {
        return $this->workspacesDocument;
    }

    /**
     * @param array $workspacesObject
     */
    public function setWorkspacesObject($workspacesObject)
    {
        $this->workspacesObject = $workspacesObject;
    }

    /**
     * @return array
     */
    public function getWorkspacesObject()
    {
        return $this->workspacesObject;
    }
}
