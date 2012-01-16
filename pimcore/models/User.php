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
     * @param array $values
     * @return User
     */
    public static function create($values = array()) {
        $user = new self();
        $user->setValues($values);
        $user->save();
        return $user;
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
     *
     */
    public function setAllAclToFalse() {
        // @TODO PERMISSIONS_REFACTORE must be replaced with new permissions list (in an array $this->permissions)
        //$this->permissions->removeAll();
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
     * @param string $permissionName
     * @return array
     */
    public function getPermission($permissionName) {

        if ($this->isAdmin()) {
            return true;
        }

        return parent::getPermission($permissionName);
    }

    public function getParent() {
        $test = "asd";
    }
}
