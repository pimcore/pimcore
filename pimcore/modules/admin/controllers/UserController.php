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
            if (!$this->getUser()->isAllowed("users")) {

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

    public function addAction() {

        if ($this->getUser()->isAllowed("users")) {

            try {
                $user = User::create(array(
                    "parentId" => intval($this->_getParam("parentId")),
                    "username" => $this->_getParam("username"),
                    "password" => md5(time()),
                    "hasCredentials" => $this->_getParam("hasCredentials"),
                    "active" => $this->_getParam("active")
                ));

                $this->_helper->json(array(
                    "success" => true,
                    "username" => $user->getUsername(),
                    "id" => $user->getId(),
                    "parentId" => $user->getParentId(),
                    "hasCredentials" => $user->getHasCredentials()
                ));
            } catch (Exception $e) {

                if (($e instanceof PDOException or $e instanceof Zend_Db_Statement_Exception) and $e->getCode() == 23000) {
                    $this->_helper->json(array("success" => false, "message" => "duplicate_username"));
                } else {
                    $this->_helper->json(array("success" => false));
                }

            }
        }

        $this->_helper->json(false);
    }

    public function deleteAction() {
        if ($this->getUser()->isAllowed("users")) {
            $user = User::getById(intval($this->_getParam("id")));
            $user->delete();
        }
        $this->removeViewRenderer();
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
        $user->setPassword(null);
        $conf = Pimcore_Config::getSystemConfig();
        $this->_helper->json(array("wsenabled"=>$conf->webservice->enabled,"user" => $user->getIterator(), "objectDependencies" => array("hasHidden" => $hasHidden, "dependencies" => $userObjectData)));
    }

    public function getMinimalAction() {
        $user = User::getById(intval($this->_getParam("id")));
        $user->setPassword(null);

        $minimalUserData['id'] = $user->getId();
        $minimalUserData['hasCredentials'] = $user->getHasCredentials();
        $minimalUserData['admin'] = $user->isAdmin();
        $minimalUserData['active'] = $user->isActive();
        $minimalUserData['permissionInfo']['assets']['granted'] = $user->isAllowed("assets");
        $minimalUserData['permissionInfo']['documents']['granted'] = $user->isAllowed("documents");
        $minimalUserData['permissionInfo']['objects']['granted'] = $user->isAllowed("objects");

        $this->_helper->json($minimalUserData);
    }

    public function updateAction() {
        if ($this->getUser()->isAllowed("users")) {
            $allValues = Zend_Json::decode($this->_getParam("data"));
            $values = array();
            //simulate behaviour from before change form prototype to jquery
            foreach ($allValues as $k => $v) {
                if (!empty($v) and $v!==FALSE) {
                    $values[$k] = $v;
                }
            }

            $user = User::getById(intval($this->_getParam("id")));

            if (!empty($values["password"])) {
                $values["password"] = Pimcore_Tool_Authentication::getPasswordHash($user->getUsername(),$values["password"]);
            }


            $user->setAllAclToFalse();
            $user->setValues($values);

            if (isset($allValues["parentId"])) {
                $user->setParentId($allValues["parentId"]);
            }

            if (isset($allValues["active"])) {
                $user->setActive($allValues["active"]);
            }

            if (isset($allValues["admin"])) {
                $user->setAdmin($allValues["admin"]);
            }

            $user->save();

            $this->_helper->json(array("success" => true));
        }

        $this->_helper->json(false);
    }

    public function updateCurrentUserAction() {

        $user = $this->getUser();
        if ($user != null) {
            if ($user->getId() == $this->_getParam("id")) {
                $values = Zend_Json::decode($this->_getParam("data"));
                if (!empty($values["password"])) {
                    $values["password"] = Pimcore_Tool_Authentication::getPasswordHash($user->getUsername(),$values["password"]);
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


    public function getAllUsersAction() {
        $list = new User_List();
        $list->load();

        $users = $list->getUsers();
        if (!empty($users)) {
            foreach ($users as $user) {
                $userList[] = $user->getIterator();
            }
        }

        $this->_helper->json(array(
            "users" => $userList
        ));
    }

    public function getCurrentUserAction() {

        header("Content-Type: text/javascript");

        $user = $this->getUser();
        if ($user != null) {
            $user = $user->getIterator();
        }

        echo "pimcore.currentuser = " . Zend_Json::encode($user);
        exit;
    }

    public function getAvailablePermissionsAction() {

        $list = User_Permission_List::getAllPermissionDefinitions();
        $this->_helper->json($list);
    }

    protected function getTreeNodeConfig($user) {
        $tmpUser = array(
            "id" => $user->getId(),
            "text" => $user->getUsername(),
            "elementType" => "user"
        );

        // set type specific settings
        if (!$user->getHasCredentials()) {
            $tmpUser["leaf"] = false;
            $tmpUser["iconCls"] = "pimcore_icon_usergroup";
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

}
