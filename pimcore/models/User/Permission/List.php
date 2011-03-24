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

class User_Permission_List extends Pimcore_Model_List_Abstract {

    private $permissions;

    protected static $permissionNames = array();

    public function isValidOrderKey($key) {
        return true;
    }

    /**
     * removes all permissions
     */
    public function removeAll() {
        $this->permissions = array();
    }

    /**
     * adds a permission
     * @param User_Permission $permission new permission name
     */
    public function add($permission) {
        $this->permissions[] = $permission;
        return $this->permissions;
    }


    /**
     *
     * @return User_Permission_List $permissionList
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     *
     * @param String $permissionName
     * @return boolean $hasPermission
     */
    public function hasPermission($permissionName) {
        if (count($this->permissions) > 0) {
            foreach ($this->permissions as $permission) {
                if ($permission->getName() === $permissionName) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return Array array of permission names
     */
    public function getPermissionNames() {
        $permissionNames = array();
        if (count($this->permissions) > 0) {
            foreach ($this->permissions as $permission) {
                $permissionNames[] = $permission->getName();
            }
        }
        return $permissionNames;
    }


    /**
     *
     * @param Array $permissions
     */
    public function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    /**
     *
     * @return Array $permissionNames
     */
    public static function getAllPermissionDefinitions() {

        if (empty(self::$permissionNames)) {
            $list = new User_Permission_Definition_List();
            self::$permissionNames = $list->load();
        }

        return self::$permissionNames;
    }
}
