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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Model;

class AbstractWorkspace extends Model\AbstractModel {

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
     * @param $create
     * @return $this
     */
    public function setCreate($create)
    {
        $this->create = $create;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCreate()
    {
        return $this->create;
    }

    /**
     * @param $delete
     * @return $this
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDelete()
    {
        return $this->delete;
    }

    /**
     * @param $list
     * @return $this
     */
    public function setList($list)
    {
        $this->list = $list;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param $publish
     * @return $this
     */
    public function setPublish($publish)
    {
        $this->publish = $publish;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getPublish()
    {
        return $this->publish;
    }

    /**
     * @param $rename
     * @return $this
     */
    public function setRename($rename)
    {
        $this->rename = $rename;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getRename()
    {
        return $this->rename;
    }

    /**
     * @param $settings
     * @return $this
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param $versions
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param $view
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param $cid
     * @return $this
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
        return $this;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @param $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param $cpath
     * @return $this
     */
    public function setCpath($cpath)
    {
        $this->cpath = $cpath;
        return $this;
    }

    /**
     * @return string
     */
    public function getCpath()
    {
        return $this->cpath;
    }
}
