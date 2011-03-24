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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Permissions extends Pimcore_Model_Abstract {

    /**
     * Unique ID
     *
     * @var integer
     */
    public $id;


    /**
     * ID of the User the permission is assigned
     *
     * @var integer
     */
    public $userId;


    /**
     * User the permission is assigned
     *
     * @var User
     */
    public $user;

    /**
     * Username of the User the permission is assigned
     *
     * @var string
     */
    public $username;

    /**
     * Content-ID, the ID of the asset the permission is assigned
     *
     * @var integer
     */
    public $cid;

    /**
     * Content-path, the path of the asset the permission is assigned
     *
     * @var integer
     */
    public $cpath;


    /**
     * Permission property (list)
     *
     * @var boolean
     */
    public $list = true;

    /**
     * Permission property (view)
     *
     * @var boolean
     */
    public $view = true;

    /**
     * Permission property (publish)
     *
     * @var boolean
     */
    public $publish = true;

    /**
     * Permission property (delete)
     *
     * @var boolean
     */
    public $delete = true;

    /**
     * Permission property (rename)
     *
     * @var boolean
     */
    public $rename = true;

    /**
     * Permission property (create)
     *
     * @var boolean
     */
    public $create = true;

    /**
     * Permission property (permissions)
     *
     * @var boolean
     */
    public $permissions = true;

    /**
     * Permission property (versions)
     *
     * @var boolean
     */
    public $versions = true;

    /**
     * Permission property (properties)
     *
     * @var boolean
     */
    public $properties = true;

    /**
     * Permission property (settings)
     *
     * @var boolean
     */
    public $settings = true;

    /**
     * Is a inherited property or not
     *
     * @var boolean
     */
    public $inherited = false;


    /**
     * @param integer $id
     * @return Asset_Permissions
     */
    public static function getById($id) {
        $permission = new self();
        $permission->setId(intval($id));
        $permission->getResource()->getById();
        $permission->getUser();

        return $permission;
    }

    /**
     * save the current state
     *
     * @return void
     */
    public function save() {

        if (!$this->getUser() instanceof User) {
            if ($this->getUserId()) {
                $this->user = User::getById(intval($this->getUserId()));
            }
            else if ($this->getUsername()) {
                $this->user = User::getByName($this->getUsername());
            }
        }

        $this->setUserId($this->getUser()->getId());

        $this->getResource()->save();
    }


    /**
     * @return integer
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return User
     */
    public function getUser() {
        if (!$this->user && $this->userId) {
            $this->user = User::getById($this->userId);
            $this->setUsername($this->user->getUsername());
        }
        return $this->user;
    }

    /**
     * @return boolean
     */
    public function getList() {
        return $this->list;
    }

    /**
     * @return boolean
     */
    public function getView() {
        return $this->view;
    }

    /**
     * @return boolean
     */
    public function getPublish() {
        return $this->publish;
    }

    /**
     * @return boolean
     */
    public function getDelete() {
        return $this->delete;
    }

    /**
     * @return boolean
     */
    public function getRename() {
        return $this->rename;
    }

    /**
     * @return boolean
     */
    public function getCreate() {
        return $this->create;
    }

    /**
     * @param integer $userId
     * @return void
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * @param User $user
     * @return void
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @param boolean $list
     * @return void
     */
    public function setList($list) {
        $this->list = (bool) $list;
    }

    /**
     * @param boolean $view
     * @return void
     */
    public function setView($view) {
        $this->view = (bool) $view;
    }

    /**
     * @param boolean $publish
     * @return void
     */
    public function setPublish($publish) {
        $this->publish = (bool) $publish;
    }

    /**
     * @param boolean $delete
     * @return void
     */
    public function setDelete($delete) {
        $this->delete = (bool) $delete;
    }

    /**
     * @param boolean $rename
     * @return void
     */
    public function setRename($rename) {
        $this->rename = (bool) $rename;
    }

    /**
     * @param boolean $create
     * @return void
     */
    public function setCreate($create) {
        $this->create = (bool) $create;
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
     * @return boolean
     */
    public function getInherited() {
        return $this->inherited;
    }

    /**
     * @param boolean $inherited
     * @return void
     */
    public function setInherited($inherited) {
        $this->inherited = (bool) $inherited;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCpath() {
        return $this->cpath;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @param integer $cid
     * @return void
     */
    public function setCid($cid) {
        $this->cid = $cid;
    }

    /**
     * @param string $cpath
     * @return void
     */
    public function setCpath($cpath) {
        $this->cpath = $cpath;
    }

    /**
     * @return boolean
     */
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * @return boolean
     */
    public function getVersions() {
        return $this->versions;
    }

    /**
     * @return boolean
     */
    public function getProperties() {
        return $this->properties;
    }

    /**
     * @return boolean
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * @param boolean $permissions
     * @return void
     */
    public function setPermissions($permissions) {
        $this->permissions = (bool) $permissions;
    }

    /**
     * @param boolean $versions
     * @return void
     */
    public function setVersions($versions) {
        $this->versions = (bool) $versions;
    }

    /**
     * @param boolean $properties
     * @return void
     */
    public function setProperties($properties) {
        $this->properties = (bool) $properties;
    }

    /**
     * @param boolean $settings
     * @return void
     */
    public function setSettings($settings) {
        $this->settings = (bool) $settings;
    }

    /**
     * @return string[]
     */
    public function getValidPermissionKeys(){
        return  array("list", "view", "publish",  "create", "delete", "rename", "create", "settings", "properties", "permissions", "versions");
    }
}
