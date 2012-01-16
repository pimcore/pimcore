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
     * @param String $permissionName
     */
    public function setPermission($permissionName) {
        $availableUserPermissionsList = new User_Permission_Definition_List();
        $availableUserPermissions = $availableUserPermissionsList->load();

        $availableUserPermissionKeys = array();
        foreach($availableUserPermissions as $permission){
            if($permission instanceof User_Permission_Definition){
                $availableUserPermissionKeys[]=$permission->getKey();
            }
        }
        if(in_array($permissionName,$availableUserPermissionKeys)){

            // @TODO PERMISSIONS_REFACTORE must be replaced with new permissions list (in an array $this->permissions)
            /*if (empty($this->permissions) or !in_array($permissionName, $this->permissions->getPermissionNames())) {
                $permission = new User_Permission($permissionName, false);
                $this->permissions->add($permission);
            }*/

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

        $thisHasPermission = false;

        // @TODO PERMISSIONS_REFACTORE must be replaced with new permissions list (in an array $this->permissions)
        /*if ($this->permissions != null) {
            $thisHasPermission = $this->permissions->hasPermission($permissionName);
        }
        */

        /*
        // this was for inheritance! @TODO: PERMISSIONS_REFACTORE Must be replaced with groups
        $parentHasPermission = false;

        if ($this->getParent() != null and $this->getParent()->getUserPermissionList() != null) {
           $parentHasPermission = $this->getParent()->getPermission($permissionName);
        }
        if (!$thisHasPermission && $parentHasPermission) {
            return true;
        } else {

            return $thisHasPermission;
        }*/

        return $thisHasPermission;

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

        if (!$this->isAdmin()) {
            foreach ($definitions as $definition) {
                $permissionInfo[$definition->getKey()] = $this->getPermission($definition->getKey());
            }

        } else {
            foreach ($definitions as $definition) {
                $permissionInfo[$definition->getKey()] = true;
            }
        }
        return $permissionInfo;
    }
}
