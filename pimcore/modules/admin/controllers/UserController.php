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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_UserController extends Pimcore_Controller_Action_Admin {

    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array("get-current-user", "update-current-user", "get-all-users", "get-available-permissions", "tree-get-childs-by-id", "get-minimal");
        if (!in_array($this->_getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAdmin()) {

                $this->_redirect("/admin/login");
                die();
            }
        }
    }

    public function treeGetChildsByIdAction() {

        $list = new User_List();
        $list->setCondition("parentId = ?", intval($this->_getParam("node")));
        $list->load();

        $users = array();
        if(is_array($list->getUsers())){
            foreach ($list->getUsers() as $user) {
                $users[] = $this->getTreeNodeConfig($user);
            }
        }
        $this->_helper->json($users);
    }

    protected function getTreeNodeConfig($user) {
        $tmpUser = array(
            "id" => $user->getId(),
            "text" => $user->getName(),
            "elementType" => "user",
            "qtipCfg" => array(
                "title" => "ID: " . $user->getId()
            )
        );

        // set type specific settings
        if ($user instanceof User_Folder) {
            $tmpUser["leaf"] = false;
            $tmpUser["iconCls"] = "pimcore_icon_folder";
            $tmpUser["expanded"] = true;

            if($user->hasChilds()) {
                $tmpUser["expanded"] = false;
            }
        }
        else {
            $tmpUser["leaf"] = true;
            $tmpUser["iconCls"] = "pimcore_icon_user";
            $tmpUser["allowChildren"] = false;

        }

        return $tmpUser;
    }

    public function addAction() {

        try {
            $className = User_Service::getClassNameForType($this->_getParam("type"));
            $user = $className::create(array(
                "parentId" => intval($this->_getParam("parentId")),
                "name" => $this->_getParam("name"),
                "password" => md5(microtime()),
                "active" => $this->_getParam("active")
            ));

            $this->_helper->json(array(
                "success" => true,
                "id" => $user->getId()
            ));
        } catch (Exception $e) {
            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
        }

        $this->_helper->json(false);
    }

    public function deleteAction() {
        $user = User_Abstract::getById(intval($this->_getParam("id")));
        $user->delete();

        $this->_helper->json(array("success" => true));
    }

    public function updateAction() {

        $user = User_Abstract::getById(intval($this->_getParam("id")));

        if($this->_getParam("data")) {
            $allValues = Zend_Json::decode($this->_getParam("data"));
            $values = array();

            //simulate behaviour from before change form prototype to jquery
            foreach ($allValues as $k => $v) {
                if (!empty($v) and $v!==FALSE) {
                    $values[$k] = $v;
                }
            }

            if (!empty($values["password"])) {
                $values["password"] = Pimcore_Tool_Authentication::getPasswordHash($user->getName(),$values["password"]);
            }


            $user->setAllAclToFalse();
            $user->setValues($values);

            // booleans
            if (isset($allValues["parentId"])) {
                $user->setParentId($allValues["parentId"]);
            }

            if (isset($allValues["active"])) {
                $user->setActive($allValues["active"]);
            }

            if (isset($allValues["admin"])) {
                $user->setAdmin($allValues["admin"]);
            }

            // check for permissions
            $availableUserPermissionsList = new User_Permission_Definition_List();
            $availableUserPermissions = $availableUserPermissionsList->load();

            foreach($availableUserPermissions as $permission) {
                if($values["permission_" . $permission->getKey()]) {
                    $user->setPermission($permission->getKey(), (bool) $values["permission_" . $permission->getKey()]);
                }
            }
        }

        if($this->_getParam("workspaces")) {
            // ...
        }

        $user->save();

        $this->_helper->json(array("success" => true));
    }

    public function getAllUsersAction() {
        $list = new User_List();
        $list->load();

        $users = $list->getUsers();
        if (!empty($users)) {
            foreach ($users as $user) {
                if($user instanceof User) {
                    $userList[] = $user;
                }
            }
        }

        $this->_helper->json(array(
            "users" => $userList
        ));
    }

    public function getAction() {
        $user = User::getById(intval($this->_getParam("id")));
        $userObjects = Object_Service::getObjectsReferencingUser($user->getId());

        $userObjectData = array();
        $currentUser = Zend_Registry::get("pimcore_user");
        foreach ($userObjects as $o) {
            $hasHidden = false;
            $o->getUserPermissions($currentUser);
            if ($o->isAllowed("list")) {
                $userObjectData[] = array(
                    "path" => $o->getFullPath(),
                    "id" => $o->getId(),
                    "subtype" => $o->getClass()->getName()
                );
            } else {
                $hasHidden = true;
            }
        }

        // get available permissions
        $availableUserPermissionsList = new User_Permission_Definition_List();
        $availableUserPermissions = $availableUserPermissionsList->load();


        $user->setPassword(null);
        $conf = Pimcore_Config::getSystemConfig();
        $this->_helper->json(array(
            "success" => true,
            "wsenabled"=>$conf->webservice->enabled,
            "user" => $user,
            "permissions" => $user->generatePermissionList(),
            "availablePermissions" => $availableUserPermissions,
            "objectDependencies" => array(
                "hasHidden" => $hasHidden,
                "dependencies" => $userObjectData
            )
        ));
    }

    public function getMinimalAction() {
        $user = User::getById(intval($this->_getParam("id")));
        $user->setPassword(null);

        $minimalUserData['id'] = $user->getId();
        $minimalUserData['admin'] = $user->isAdmin();
        $minimalUserData['active'] = $user->isActive();
        $minimalUserData['permissionInfo']['assets'] = $user->isAllowed("assets");
        $minimalUserData['permissionInfo']['documents'] = $user->isAllowed("documents");
        $minimalUserData['permissionInfo']['objects'] = $user->isAllowed("objects");

        $this->_helper->json($minimalUserData);
    }

    public function updateCurrentUserAction() {

        $user = $this->getUser();
        if ($user != null) {
            if ($user->getId() == $this->_getParam("id")) {
                $values = Zend_Json::decode($this->_getParam("data"));

                unset($values["admin"]);

                if (!empty($values["password"])) {
                    $values["password"] = Pimcore_Tool_Authentication::getPasswordHash($user->getName(),$values["password"]);
                }
                $user->setValues($values);
                $user->save();
                $this->_helper->json(array("success" => true));
            } else {
                Logger::warn("prevented save current user, because ids do not match. ");
                $this->_helper->json(false);
            }
        } else {
            $this->_helper->json(false);
        }

    }

    public function getCurrentUserAction() {

        header("Content-Type: text/javascript");

        $user = $this->getUser();

        echo "pimcore.currentuser = " . Zend_Json::encode($user);
        exit;
    }





    /* ROLES */

    public function roleTreeGetChildsByIdAction() {

        $list = new User_Role_List();
        $list->setCondition("parentId = ?", intval($this->_getParam("node")));
        $list->load();

        $roles = array();
        if(is_array($list->getItems())){
            foreach ($list->getItems() as $role) {
                $roles[] = $this->getRoleTreeNodeConfig($role);
            }
        }
        $this->_helper->json($roles);
    }

    protected function getRoleTreeNodeConfig($role) {
        $tmpUser = array(
            "id" => $role->getId(),
            "text" => $role->getName(),
            "elementType" => "role",
            "qtipCfg" => array(
                "title" => "ID: " . $role->getId()
            )
        );

        // set type specific settings
        if ($role instanceof User_Role_Folder) {
            $tmpUser["leaf"] = false;
            $tmpUser["iconCls"] = "pimcore_icon_folder";
            $tmpUser["expanded"] = true;

            if($role->hasChilds()) {
                $tmpUser["expanded"] = false;
            }
        }
        else {
            $tmpUser["leaf"] = true;
            $tmpUser["iconCls"] = "pimcore_icon_roles";
            $tmpUser["allowChildren"] = false;

        }

        return $tmpUser;
    }
}
