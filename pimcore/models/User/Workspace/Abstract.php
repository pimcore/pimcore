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

class User_Workspace_Abstract extends Pimcore_Model_Abstract {

    /**
     * @var int
     */
    public $userId;

    /**
     * @var int
     */
    public $cid;

    /**
     * @var string
     */
    public $cpath;

    /**
     * @var bool
     */
    public $list = false;

    /**
     * @var bool
     */
    public $view = false;

    /**
     * @var bool
     */
    public $publish = false;

    /**
     * @var bool
     */
    public $delete = false;

    /**
     * @var bool
     */
    public $rename = false;

    /**
     * @var bool
     */
    public $create = false;

    /**
     * @var bool
     */
    public $settings = false;

    /**
     * @var bool
     */
    public $versions = false;

    /**
     * @var bool
     */
    public $properties = false;


    /**
     * @param boolean $create
     */
    public function setCreate($create)
    {
        $this->create = $create;
    }

    /**
     * @return boolean
     */
    public function getCreate()
    {
        return $this->create;
    }

    /**
     * @param boolean $delete
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;
    }

    /**
     * @return boolean
     */
    public function getDelete()
    {
        return $this->delete;
    }

    /**
     * @param boolean $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }

    /**
     * @return boolean
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param boolean $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return boolean
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param boolean $publish
     */
    public function setPublish($publish)
    {
        $this->publish = $publish;
    }

    /**
     * @return boolean
     */
    public function getPublish()
    {
        return $this->publish;
    }

    /**
     * @param boolean $rename
     */
    public function setRename($rename)
    {
        $this->rename = $rename;
    }

    /**
     * @return boolean
     */
    public function getRename()
    {
        return $this->rename;
    }

    /**
     * @param boolean $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return boolean
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param boolean $versions
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;
    }

    /**
     * @return boolean
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param boolean $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * @return boolean
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param int $cid
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $cpath
     */
    public function setCpath($cpath)
    {
        $this->cpath = $cpath;
    }

    /**
     * @return string
     */
    public function getCpath()
    {
        return $this->cpath;
    }
}
