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

class User extends User_UserRole {

    /**
     * @var string
     */
    public $type = "user";

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
     * @var boolean
     */
    public $active = true;

    /**
     * @var array
     */
    public $roles = array();

    /**
     * @var bool
     */
    public $welcomescreen = true;

    /**
     * @var bool
     */
    public $closeWarning = true;


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

    /**
     * @return bool
     */
    public function isActive(){
        return $this->getActive();
    }

    /**
     * @param String $key
     * @return boolean
     */
    public function isAllowed($key) {

        if(!$this->getPermission($key)) {
            // check roles
            foreach ($this->getRoles() as $roleId) {
                $role = User_Role::getById($roleId);
                if($role->getPermission($key)) {
                    return true;
                }
            }
        }

        return $this->getPermission($key);
    }

    /**
     *
     * @param string $permissionName
     * @return array
     */
    public function getPermission($permissionName) {

        if ($this->isAdmin()) {
            return true;
        }

        return parent::getPermission($permissionName);
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        if(is_string($roles) && !empty($roles) ) {
            $this->roles = explode(",", $roles);
        } else if (is_array($roles)) {
            $this->roles = $roles;
        } else if (empty($roles)) {
            $this->roles = array();
        }
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        if(empty($this->roles)) {
            return array();
        }
        return $this->roles;
    }

    /**
     * @param boolean $welcomescreen
     */
    public function setWelcomescreen($welcomescreen)
    {
        $this->welcomescreen = (bool) $welcomescreen;
    }

    /**
     * @return boolean
     */
    public function getWelcomescreen()
    {
        return $this->welcomescreen;
    }

    /**
     * @param boolean $closeWarning
     */
    public function setCloseWarning($closeWarning)
    {
        $this->closeWarning = (bool) $closeWarning;
    }

    /**
     * @return boolean
     */
    public function getCloseWarning()
    {
        return $this->closeWarning;
    }
}
