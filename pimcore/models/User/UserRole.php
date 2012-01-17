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
     *
     */
    public function setAllAclToFalse() {
        $list = new User_Permission_Definition_List();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $this->permissions[$definition->getKey()] = false;
        }
    }

    /**
     * @param string $permissionName
     * @param bool $value
     */
    public function setPermission($permissionName, $value = null) {

        $availableUserPermissionsList = new User_Permission_Definition_List();
        $availableUserPermissions = $availableUserPermissionsList->load();

        $availableUserPermissionKeys = array();
        foreach($availableUserPermissions as $permission){
            if($permission instanceof User_Permission_Definition){
                $availableUserPermissionKeys[]=$permission->getKey();
            }
        }

        if(in_array($permissionName,$availableUserPermissionKeys)){
            $this->permissions[$permissionName] = (bool) $value;
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

        if(array_key_exists($permissionName, $this->permissions)) {
            return $this->permissions[$permissionName];
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
}
