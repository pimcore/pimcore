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

class User_Permission_List_Resource extends Pimcore_Model_Resource_Abstract {


    /**
     * loads all permissions for the given user
     * @param User $user
     */
    public function load($user) {

        $permissionNames = $this->db->fetchAll("SELECT name FROM users_permissions WHERE userId = ?", $user->getId());

        $permissions = array();
        if (count($permissionNames) > 0) {
            foreach ($permissionNames as $permissionName) {
                $permissions[] = new User_Permission($permissionName["name"], false);
            }
            $this->model->setPermissions($permissions);
        }

        return $permissions;
    }

    /**
     * Deletes all permissions for a user
     * @param User $user
     */
    public function deleteForUser($user) {
        try {
            $this->db->delete("users_permissions", $this->db->quoteInto("userId = ?", $user->getId()));
            Logger::info("dropped all permissions for user " . $user->getId());
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     * @param User $user
     * @param User_Permission_List $permissionList
     */
    public function update($user) {



        if ($user->getUserPermissionList() == null or count($user->getUserPermissionList()->getPermissionNames()) < 1) {
            $this->deleteForUser($user);
        } else {
            foreach ($user->getUserPermissionList()->getPermissionNames() as $permissionName) {
                try {
                    $oldPermissions = $this->db->fetchAll("SELECT * FROM users_permissions WHERE userId=?", $user->getId());
                    $oldPermissionNames = array();
                    if (count($oldPermissions) > 0) {
                        foreach ($oldPermissions as $oldPermission) {
                            $oldPermissionNames[] = $oldPermission["name"];
                        }
                    }
                    if (!in_array($permissionName, $oldPermissionNames)) {

                        Logger::debug($permissionName);

                        $this->db->insert("users_permissions", array(
                            "userId" => $user->getId(),
                            "name" => $permissionName
                        ));
                        Logger::debug("set new permission " . $permissionName . " for user " . $user->getId());
                    } else {
                        Logger::debug("permission " . $permissionName . " already set for user " . $user->getId());
                    }
                    foreach ($oldPermissionNames as $oldpermissionName) {
                        if (!in_array($oldpermissionName, $user->getUserPermissionList()->getPermissionNames())) {
                            $this->db->delete("users_permissions", $this->db->quoteInto("userId= ?", $user->getId()) . " AND " . $this->db->quoteInto("name = ?", $oldpermissionName ));
                            Logger::debug("dropped permission " . $permissionName . " for user " . $user->getId());
                        }
                    }

                }
                catch (Exception $e) {
                    throw $e;
                }
            }
        }

    }
}
