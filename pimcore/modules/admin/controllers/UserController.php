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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Tool; 
use Pimcore\Model\User;
use Pimcore\Model\Element;
use Pimcore\Model\Object;

class Admin_UserController extends \Pimcore\Controller\Action\Admin {

    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array("get-current-user", "update-current-user", "get-available-permissions", "get-minimal", "get-image", "upload-current-user-image");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("users");
        }
    }

    public function treeGetChildsByIdAction() {

        $list = new User\Listing();
        $list->setCondition("parentId = ?", intval($this->getParam("node")));
        $list->setOrder("ASC");
        $list->setOrderKey("name");
        $list->load();

        $users = array();
        if(is_array($list->getUsers())){
            foreach ($list->getUsers() as $user) {
                if($user->getId() && $user->getName() != "system") {
                    $users[] = $this->getTreeNodeConfig($user);
                }
            }
        }
        $this->_helper->json($users);
    }

    protected function getTreeNodeConfig($user) {
        $tmpUser = array(
            "id" => $user->getId(),
            "text" => $user->getName(),
            "elementType" => "user",
            "type" => $user->getType(),
            "qtipCfg" => array(
                "title" => "ID: " . $user->getId()
            )
        );

        // set type specific settings
        if ($user instanceof User\Folder) {
            $tmpUser["leaf"] = false;
            $tmpUser["iconCls"] = "pimcore_icon_folder";
            $tmpUser["expanded"] = true;
            $tmpUser["allowChildren"] = true;

            if($user->hasChilds()) {
                $tmpUser["expanded"] = false;
            }
        }
        else {
            $tmpUser["leaf"] = true;
            $tmpUser["iconCls"] = "pimcore_icon_user";
            $tmpUser["allowChildren"] = false;
            $tmpUser["admin"] = $user->isAdmin();

        }

        return $tmpUser;
    }

    public function addAction() {

        $this->protectCSRF();

        try {
            $type = $this->getParam("type");;
            $className = User\Service::getClassNameForType($type);
            $user = $className::create(array(
                "parentId" => intval($this->getParam("parentId")),
                "name" => trim($this->getParam("name")),
                "password" => "",
                "active" => $this->getParam("active")
            ));

            if ($this->getParam("rid")) {

                $rid = $this->getParam("rid");
                $rObject = $className::getById($rid);
                if ($rObject) {
                    if ($type == "user" || $type == "role") {
                        $user->setParentId($rObject->getParentId());
                        if ($rObject->getClasses()) {
                            $user->setClasses(implode(',', $rObject->getClasses()));
                        }
                        if ($rObject->getDocTypes()) {
                            $user->setDocTypes(implode(',', $rObject->getDocTypes()));
                        }

                        $keys = array("asset", "document", "object");
                        foreach ($keys as $key) {
                            $getter = "getWorkspaces" . ucfirst($key);
                            $setter = "setWorkspaces" . ucfirst($key);
                            $workspaces = $rObject->$getter();
                            $clonedWorkspaces = array();
                            if (is_array($workspaces)) {
                                foreach($workspaces as $workspace) {
                                    $vars = get_object_vars($workspace);
                                    $workspaceClass = "\\Pimcore\\Model\\User\\Workspace\\" . ucfirst($key);
                                    $newWorkspace = new $workspaceClass();
                                    foreach ($vars as $varKey => $varValue) {
                                        $newWorkspace->$varKey = $varValue;
                                    }
                                    $newWorkspace->setUserId($user->getId());
                                    $clonedWorkspaces[] = $newWorkspace;
                                }
                            }

                            $user->$setter($clonedWorkspaces);
                        }

                        $user->setPermissions($rObject->getPermissions());

                        if ($type == "user") {
                            $user->setAdmin(false);
                            if($this->getUser()->isAdmin()) {
                                $user->setAdmin($rObject->getAdmin());
                            }
                            $user->setActive($rObject->getActive());
                            $user->setRoles($rObject->getRoles());
                            $user->setWelcomeScreen($rObject->getWelcomescreen());
                            $user->setMemorizeTabs($rObject->getMemorizeTabs());
                            $user->setCloseWarning($rObject->getCloseWarning());
                        }

                        $user->save();
                    }
                }
            }
            $this->_helper->json(array(
                "success" => true,
                "id" => $user->getId()
            ));
        } catch (\Exception $e) {
            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
        }

        $this->_helper->json(false);
    }


    protected function populateChildNodes($node, &$currentList) {
        $currentUser = \Pimcore\Tool\Admin::getCurrentUser();
        $list = new User\Listing();
        $list->setCondition("parentId = ?", $node->getId());
        $list->setOrder("ASC");
        $list->setOrderKey("name");
        $list->load();

        $childList = $list->getUsers();
        if(is_array($childList)) {
            foreach ($childList as $user) {
                if ($user->getId() == $currentUser->getId()) {
                    throw new Exception("Cannot delete current user");
                }
                if ($user->getId() && $currentUser->getId() && $user->getName() != "system") {
                    $currentList[] = $user;
                    $this->populateChildNodes($user, $currentList);
                }
            }
        }
        return $currentList;
    }

    public function deleteAction() {
        $user = User\AbstractUser::getById(intval($this->getParam("id")));

        // only admins are allowed to delete admins and folders
        // because a folder might contain an admin user, so it is simply not allowed for users with the "users" permission
        if(($user instanceof User\Folder && !$this->getUser()->isAdmin()) || ($user instanceof User && $user->isAdmin() && !$this->getUser()->isAdmin())) {
            throw new \Exception("You are not allowed to delete this user");
        } else {
            if ($user instanceof User\Folder) {

                $list = array($user);
                $this->populateChildNodes($user, $list);
                $listCount = count($list);
                for ($i = $listCount - 1; $i >= 0; $i--) {
                    // iterate over the list from the so that nothing can get "lost"
                    $user = $list[$i];
                    $user->delete();
                }
            } else {
                $user->delete();
            }
        }

        $this->_helper->json(array("success" => true));
    }

    public function updateAction() {

        $this->protectCSRF();

        $user = User\AbstractUser::getById(intval($this->getParam("id")));

        if($user instanceof User && $user->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception("Only admin users are allowed to modify admin users");
        }

        if($this->getParam("data")) {
            $values = \Zend_Json::decode($this->getParam("data"));

            if (!empty($values["password"])) {
                $values["password"] = Tool\Authentication::getPasswordHash($user->getName(),$values["password"]);
            }

            // check if there are permissions transmitted, if so reset them all to false (they will be set later)
            foreach($values as $key => $value) {
                if(strpos($key, "permission_") === 0) {
                    if(method_exists($user, "setAllAclToFalse")) {
                        $user->setAllAclToFalse();
                    }
                    break;
                }
            }

            $user->setValues($values);

            // only admins are allowed to create admin users
            // if the logged in user isn't an admin, set admin always to false
            if(!$this->getUser()->isAdmin()) {
                $user->setAdmin(false);
            }

            // check for permissions
            $availableUserPermissionsList = new User\Permission\Definition\Listing();
            $availableUserPermissions = $availableUserPermissionsList->load();

            foreach($availableUserPermissions as $permission) {
                if(isset($values["permission_" . $permission->getKey()])) {
                    $user->setPermission($permission->getKey(), (bool) $values["permission_" . $permission->getKey()]);
                }
            }

            // check for workspaces
            if($this->getParam("workspaces")) {
                $workspaces = \Zend_Json::decode($this->getParam("workspaces"));
                foreach ($workspaces as $type => $spaces) {

                    $newWorkspaces = array();
                    foreach ($spaces as $space) {

                        $element = Element\Service::getElementByPath($type, $space["path"]);
                        if($element) {
                            $className = "\\Pimcore\\Model\\User\\Workspace\\" . ucfirst($type);
                            $workspace = new $className();
                            $workspace->setValues($space);

                            $workspace->setCid($element->getId());
                            $workspace->setCpath($element->getFullPath());
                            $workspace->setUserId($user->getId());

                            $newWorkspaces[] = $workspace;
                        }
                    }
                    $user->{"setWorkspaces" . ucfirst($type)}($newWorkspaces);
                }
            }
        }

        $user->save();

        $this->_helper->json(array("success" => true));
    }

    public function getAction() {

        if(intval($this->getParam("id")) < 1) {
            $this->_helper->json(array("success" => false));
        }

        $user = User::getById(intval($this->getParam("id")));

        if($user->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception("Only admin users are allowed to modify admin users");
        }

        // workspaces
        $types = array("asset","document","object");
        foreach ($types as $type) {
            $workspaces = $user->{"getWorkspaces" . ucfirst($type)}();
            foreach ($workspaces as $workspace) {
                $el = Element\Service::getElementById($type, $workspace->getCid());
                if($el) {
                    // direct injection => not nice but in this case ok ;-)
                    $workspace->path = $el->getFullPath();
                }
            }
        }

        // object <=> user dependencies
        $userObjects = Object\Service::getObjectsReferencingUser($user->getId());
        $userObjectData = array();

        foreach ($userObjects as $o) {
            $hasHidden = false;
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
        $availableUserPermissionsList = new User\Permission\Definition\Listing();
        $availableUserPermissions = $availableUserPermissionsList->load();

        // get available roles
        $roles = array();
        $list = new User\Role\Listing();
        $list->setCondition("`type` = ?", array("role"));
        $list->load();

        $roles = array();
        if(is_array($list->getItems())){
            foreach ($list->getItems() as $role) {
                $roles[] = array($role->getId(), $role->getName());
            }
        }

        // unset confidential informations
        $userData = object2array($user);
        unset($userData["password"]);

        $conf = \Pimcore\Config::getSystemConfig();
        $this->_helper->json(array(
            "success" => true,
            "wsenabled" => $conf->webservice->enabled,
            "user" => $userData,
            "roles" => $roles,
            "permissions" => $user->generatePermissionList(),
            "availablePermissions" => $availableUserPermissions,
            "objectDependencies" => array(
                "hasHidden" => $hasHidden,
                "dependencies" => $userObjectData
            )
        ));
    }

    public function getMinimalAction() {
        $user = User::getById(intval($this->getParam("id")));
        $user->setPassword(null);

        $minimalUserData['id'] = $user->getId();
        $minimalUserData['admin'] = $user->isAdmin();
        $minimalUserData['active'] = $user->isActive();
        $minimalUserData['permissionInfo']['assets'] = $user->isAllowed("assets");
        $minimalUserData['permissionInfo']['documents'] = $user->isAllowed("documents");
        $minimalUserData['permissionInfo']['objects'] = $user->isAllowed("objects");

        $this->_helper->json($minimalUserData);
    }

    public function uploadCurrentUserImageAction() {

        $user = $this->getUser();
        if ($user != null) {
            if ($user->getId() == $this->getParam("id")) {
                $this->uploadImageAction();
            } else {
                \Logger::warn("prevented save current user, because ids do not match. ");
                $this->_helper->json(false);
            }
        } else {
            $this->_helper->json(false);
        }
    }


    public function updateCurrentUserAction() {

        $this->protectCSRF();

        $user = $this->getUser();
        if ($user != null) {
            if ($user->getId() == $this->getParam("id")) {
                $values = \Zend_Json::decode($this->getParam("data"));

                unset($values["name"]);
                unset($values["id"]);
                unset($values["admin"]);
                unset($values["permissions"]);
                unset($values["roles"]);
                unset($values["active"]);

                if (!empty($values["new_password"])) {
                    $oldPasswordCheck = false;


                    if(empty($values["old_password"])) {
                        // if the user want to reset the password, the old password isn't required
                        $oldPasswordCheck = Tool\Session::useSession(function($adminSession) use ($oldPasswordCheck) {
                            if($adminSession->password_reset) {
                                return true;
                            }
                            return false;
                        });

                    } else {
                        // the password has to match
                        $checkUser = Tool\Authentication::authenticatePlaintext($user->getName(), $values["old_password"]);
                        if($checkUser) {
                            $oldPasswordCheck = true;
                        }
                    }

                    if($oldPasswordCheck && $values["new_password"] == $values["retype_password"]) {
                        $values["password"] = Tool\Authentication::getPasswordHash($user->getName(),$values["new_password"]);
                    } else {
                        $this->_helper->json(array("success" => false, "message" => "password_cannot_be_changed"));
                    }
                }

                $user->setValues($values);
                $user->save();
                $this->_helper->json(array("success" => true));
            } else {
                \Logger::warn("prevented save current user, because ids do not match. ");
                $this->_helper->json(false);
            }
        } else {
            $this->_helper->json(false);
        }
    }

    public function getCurrentUserAction() {

        header("Content-Type: text/javascript");

        $user = $this->getUser();

        $list = new User\Permission\Definition\Listing();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $user->setPermission($definition->getKey(), $user->isAllowed($definition->getKey()));
        }

        // unset confidential informations
        $userData = object2array($user);
        unset($userData["password"]);

        echo "pimcore.currentuser = " . \Zend_Json::encode($userData);
        exit;
    }


    /* ROLES */

    public function roleTreeGetChildsByIdAction() {

        $list = new User\Role\Listing();
        $list->setCondition("parentId = ?", intval($this->getParam("node")));
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
        if ($role instanceof User\Role\Folder) {
            $tmpUser["leaf"] = false;
            $tmpUser["iconCls"] = "pimcore_icon_folder";
            $tmpUser["expanded"] = true;
            $tmpUser["allowChildren"] = true;

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

    public function roleGetAction() {
        $role = User\Role::getById(intval($this->getParam("id")));

        // workspaces
        $types = array("asset","document","object");
        foreach ($types as $type) {
            $workspaces = $role->{"getWorkspaces" . ucfirst($type)}();
            foreach ($workspaces as $workspace) {
                $el = Element\Service::getElementById($type, $workspace->getCid());
                if($el) {
                    // direct injection => not nice but in this case ok ;-)
                    $workspace->path = $el->getFullPath();
                }
            }
        }

        // get available permissions
        $availableUserPermissionsList = new User\Permission\Definition\Listing();
        $availableUserPermissions = $availableUserPermissionsList->load();

        $this->_helper->json(array(
            "success" => true,
            "role" => $role,
            "permissions" => $role->generatePermissionList(),
            "classes" => $role->getClasses(),
            "docTypes" => $role->getDocTypes(),
            "availablePermissions" => $availableUserPermissions
        ));
    }

    public function uploadImageAction() {

        if($this->getParam("id")) {
            if($this->getUser()->getId() != $this->getParam("id")) {
                $this->checkPermission("users");
            }
            $id = $this->getParam("id");
        } else {
            $id = $this->getUser()->getId();
        }

        $userObj = User::getById($id);

        if($userObj->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception("Only admin users are allowed to modify admin users");
        }

        $userObj->setImage($_FILES["Filedata"]["tmp_name"]);


        $this->_helper->json(array(
            "success" => true
        ), false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html");
    }

    public function getImageAction() {

        if($this->getParam("id")) {
            if($this->getUser()->getId() != $this->getParam("id")) {
                $this->checkPermission("users");
            }
            $id = $this->getParam("id");
        } else {
            $id = $this->getUser()->getId();
        }

        $userObj = User::getById($id);
        $thumb = $userObj->getImage();

        header("Content-type: image/png", true);

        while(@ob_end_flush());
        flush();
        readfile($thumb);
        exit;
    }

    public function getTokenLoginLinkAction() {

        $user = User::getById($this->getParam("id"));

        if($user->isAdmin() && !$this->getUser()->isAdmin()) {
            throw new \Exception("Only admin users are allowed to login as an admin user");
        }

        if($user) {
            $token = Tool\Authentication::generateToken($user->getName(), $user->getPassword());
            $r = $this->getRequest();
            $link = $r->getScheme() . "://" . $r->getHttpHost() . "/admin/login/login/?username=" . $user->getName() . "&token=" . $token;

            $this->_helper->json(array(
                "link" => $link
            ));
        }
    }

    public function searchAction() {

        $q = "%" . $this->getParam("query") . "%";

        $list = new User\Listing();
        $list->setCondition("name LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR id = ?", [$q, $q, $q, $q, intval($this->getParam("query"))]);
        $list->setOrder("ASC");
        $list->setOrderKey("name");
        $list->load();

        $users = array();
        if(is_array($list->getUsers())){
            foreach ($list->getUsers() as $user) {
                if($user instanceof User && $user->getId() && $user->getName() != "system") {
                    $users[] = [
                        "id" => $user->getId(),
                        "name" => $user->getName(),
                        "email" => $user->getEmail(),
                        "firstname" => $user->getFirstname(),
                        "lastname" => $user->getLastname(),
                    ];
                }
            }
        }
        $this->_helper->json([
            "success" => true,
            "users" => $users
        ]);
    }
}
