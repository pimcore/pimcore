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

class User extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    private $parentId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $firstname;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $email;


    /**
     * @var string
     */
    public $language = "en";

    /**
     * @var boolean
     */
    public $admin = false;

    /**
     * @var User
     */
    public $parent;

    /**
     * @var array
     */
    public $permissions = array();

    /**
     * @var boolean
     */
    public $hasChilds;

    /**
     * @var boolean
     */
    public $active = true;

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     * @return void
     */
    public function setPassword($password) {
        if (strlen($password) > 4) {
            $this->password = $password;
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Alias for getName()
     * @deprecated
     * @return string
     */
    public function getUsername () {
        return $this->getName();
    }

    /**
     * Alias for setName()
     * @deprecated
     * @param $username
     */
    public function setUsername ($username) {
        $this->setName($username);
    }

    /**
     *
     * @return string
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     *
     * @param string $firstname
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;
    }

    /**
     *
     * @return string
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     *
     * @param string $lastname
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;
    }

    /**
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     *
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }



    /**
     * @return integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * @param integer $parentId
     * @return void
     */
    public function setParentId($parentId) {
        $this->parentId = $parentId;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $language
     * @return void
     */
    public function setLanguage($language) {
        if ($language) {
            $this->language = $language;
        }
    }

    /**
     * @see getAdmin()
     * @return boolean
     */
    public function isAdmin() {
        return $this->getAdmin();
    }

    /**
     * @return boolean
     */
    public function getAdmin() {
        return $this->admin;
    }

    /**
     * @param boolean $admin
     * @return void
     */
    public function setAdmin($admin) {
        $this->admin = (bool) $admin;
    }

    /**
     * @return boolean
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return void
     */
    public function setActive($active) {
        $this->active = (bool) $active;
    }

    public function isActive(){
        return $this->getActive();
    }


    /**
     * @param boolean $state
     */
    function setHasChilds($state){
        $this->hasChilds= $state;

    }

    /**
     * Returns true if the document has at least one child
     *
     * @return boolean
     */
    public function hasChilds() {
        if ($this->hasChilds !== null) {
            return $this->hasChilds;
        }
        return $this->getResource()->hasChilds();
    }



    /**
     * @param string $name
     * @return User
     */
    public static function getByName($name) {

        try {
            $user = new self();
            $user->getResource()->getByName($name);
            return $user;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param integer $id
     * @return User
     */
    public static function getById($id) {

        try {
            $user = new self();
            $user->getResource()->getById($id);
            return $user;
        }
        catch (Exception $e) {
            return false;
        }
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


    /**
     * @param array $values
     * @return User
     */
    public static function create($values = array()) {
        $user = new self();
        $user->setValues($values);
        $user->save();
        return $user;
    }


    public function setAllAclToFalse() {
        // @TODO must be replaced with new permissions list (in an array $this->permissions)
        //$this->permissions->removeAll();
    }


    /**
     * @return User_Permission_List $permissionList
     */
    public function getUserPermissionList() {
        return $this->permissions;
    }

    /**
     * @return User returns parent
     */
    public function getParent() {
        return $this->parent;
    }


    /**
     *
     * @param String $permissionName
     * @return User_Permission $userPermission
     */
    public function getPermission($permissionName) {

        if ($this->isAdmin()) {
            return true;
        } else {
            $thisHasPermission = false;

            // @TODO must be replaced with new permissions list (in an array $this->permissions)
            /*if ($this->permissions != null) {
                $thisHasPermission = $this->permissions->hasPermission($permissionName);
            }
            */

            /*
            // this was for inheritance! @TODO: Must be replaced with groups
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

    }

    /**
     * @param String $key
     * @return boolean
     */
    public function isAllowed($key) {
        return $this->getPermission($key);
    }

    /**
     *
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

            // @TODO must be replaced with new permissions list (in an array $this->permissions)
            /*if (empty($this->permissions) or !in_array($permissionName, $this->permissions->getPermissionNames())) {
                $permission = new User_Permission($permissionName, false);
                $this->permissions->add($permission);
            }*/

        }


    }

    /**
     * delete user
     */
    public function delete() {

        $this->getResource()->delete();
        Pimcore_Model_Cache::clearAll();
    }

}
