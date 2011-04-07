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

class User extends Pimcore_Model_Abstract implements IteratorAggregate {

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
    public $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $firstname;

    /**
     * @var string
     */
    private $lastname;

    /**
     * @var string
     */
    private $email;


    /**
     * @var string
     */
    private $language = "en";

    /**
     * @var boolean
     */
    private $admin = false;

    /**
     * @var boolean
     */
    private $hasCredentials;

    /**
     * @var User
     */
    private $parent;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var boolean
     */
    private $hasChilds;

    /**
     * @var boolean
     */
    private $active = true;


    public function __construct() {
        $this->init();
    }

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
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $username
     * @return void
     */
    public function setUsername($username) {
        $this->username = $username;
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
     * @return boolean
     */
    function getHasCredentials() {
        return $this->hasCredentials;
    }

    /**
     * @param boolean $hasCredentials
     * @return void
     */
    function setHasCredentials($hasCredentials) {
        $this->hasCredentials = (bool) $hasCredentials;
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
     * initializes user object
     */
    public function init() {

        //set parent
        if ($this->getParentId() > 0) {
            $parent = new self();
            $parent->getResource()->getById($this->getParentId());
            $parent->init();
            $this->parent = $parent;
        } else $this->parent = null;

        $this->permissions = new User_Permission_List();
        $this->permissions->load($this);

    }

    /**
     * @param string $username
     * @return User
     */
    public static function getByName($username) {

        try {
            $user = new self();
            $user->getResource()->getByName($username);
            $user->init();
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
            $user->init();
            return $user;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * returns an ArrayIterator which should be used for json_encode of user to make private properties accessible
     *
     * @return ArrayIterator $arrayIterator
     */
    public function getIterator() {

        $iArray['id'] = $this->id;
        if ($this->parent != null) {
            $iArray['parentId'] = $this->parent->getId();
        } else {
            $iArray['parentId'] = 0;
        }
        $iArray['username'] = $this->username;
        $iArray['password'] = $this->password;
        $iArray['language'] = $this->language;
        $iArray['firstname'] = $this->firstname;
        $iArray['lastname'] = $this->lastname;
        $iArray['email'] = $this->email;
        $iArray['admin'] = $this->admin;
        $iArray['active'] = $this->active;
        $iArray['hasCredentials'] = $this->hasCredentials;
        $iArray['hasChilds'] = $this->hasChilds;
        $iArray['permissionInfo'] = $this->generatePermissionList();
        return new ArrayIterator($iArray);
    }


    /**
     * Generates the permission list required for frontend display
     *
     * @return void
     */
    private function generatePermissionList() {
        $permissionInfo = null;
        $definitions = User_Permission_List::getAllPermissionDefinitions();

        if (!$this->isAdmin()) {
            if ($this->parent != null) {
                foreach ($definitions as $definition) {
                    $permissionInfo[$definition->getKey()]["inherited"] = $this->getParent()->getPermission($definition->getKey());
                }
            }
            foreach ($definitions as $definition) {
                $permissionInfo[$definition->getKey()]["granted"] = $this->getPermission($definition->getKey());
            }

        } else {
            foreach ($definitions as $definition) {
                $permissionInfo[$definition->getKey()]["granted"] = true;
                $permissionInfo[$definition->getKey()]["inherited"] = true;
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
        $this->permissions->removeAll();
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
            if ($this->permissions != null) {
                $thisHasPermission = $this->permissions->hasPermission($permissionName);
            }
            $parentHasPermission = false;
            
            if ($this->getParent() != null and $this->getParent()->getUserPermissionList() != null) {
               $parentHasPermission = $this->getParent()->getPermission($permissionName);
            }
            if (!$thisHasPermission && $parentHasPermission) {
                return true;
            } else {

                return $thisHasPermission;
            }
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
        $availableUserPermissions = User_Permission_List::getAllPermissionDefinitions();
        $availableUserPermissionKeys = array();
        foreach($availableUserPermissions as $permission){
            if($permission instanceof User_Permission_Definition){
                $availableUserPermissionKeys[]=$permission->getKey();
            }
        }
        if(in_array($permissionName,$availableUserPermissionKeys)){
            if (empty($this->permissions) or !in_array($permissionName, $this->permissions->getPermissionNames())) {
                $permission = new User_Permission($permissionName, false);
                $this->permissions->add($permission);
            }
        }


    }

    /**
     * returns a freezable user object i.e. without database resources
     * @return User $user
     */
    private function getFreezable() {
        $freezable = clone $this;
        $freezable->setResource(null);
        $freezable->getUserPermissionList()->setResource(null);
        $ancestor = $this->getParent();
        while ($ancestor != null) {
            $ancestor->setResource(null);
            if ($ancestor->getUserPermissionList() != null) {
                $ancestor->getUserPermissionList()->setResource(null);
            }
            $ancestor = $ancestor->getParent();
        }
        return $freezable;
    }

    /**
     * freezes the user and returns it frozen
     * @return Array $frozenUser
     */
    public function getAsFrozen() {
        $freezer = new Object_Freezer();
        return $freezer->freeze($this->getFreezable());
    }


    /**
     * thaws a frozen user
     * @param Array $frozenUser
     * @return User $user returns User Object or null if user could not be thawed
     */
    public static function thaw($frozenUser) {
        $thawedUser = null;

        if (is_array($frozenUser)) {
            $freezer = new Object_Freezer();
            $thawedUser = $freezer->thaw($frozenUser);
            if ($thawedUser instanceof User) {
                $thawedUser->getResource()->getById($thawedUser->getId());
                //TODO: do we need to reinitialize parent's resources?
            } else {
                $thawedUser = null;
                Logger::error("could not thaw user");
            }
        }

        return $thawedUser;
    }


    /**
     * delete user
     */
    public function delete() {

        $this->getResource()->delete();
        Pimcore_Model_Cache::clearAll();
    }
}
