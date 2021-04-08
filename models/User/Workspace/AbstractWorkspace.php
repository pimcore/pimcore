<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Workspace;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\Workspace\Dao getDao()
 * @method void save()
 */
class AbstractWorkspace extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var int
     */
    protected $cid;

    /**
     * @var string
     */
    protected $cpath;

    /**
     * @var bool
     */
    protected $list = false;

    /**
     * @var bool
     */
    protected $view = false;

    /**
     * @var bool
     */
    protected $publish = false;

    /**
     * @var bool
     */
    protected $delete = false;

    /**
     * @var bool
     */
    protected $rename = false;

    /**
     * @var bool
     */
    protected $create = false;

    /**
     * @var bool
     */
    protected $settings = false;

    /**
     * @var bool
     */
    protected $versions = false;

    /**
     * @var bool
     */
    protected $properties = false;

    /**
     * @param bool $create
     *
     * @return $this
     */
    public function setCreate($create)
    {
        $this->create = $create;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCreate()
    {
        return $this->create;
    }

    /**
     * @param bool $delete
     *
     * @return $this
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDelete()
    {
        return $this->delete;
    }

    /**
     * @param bool $list
     *
     * @return $this
     */
    public function setList($list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return bool
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param bool $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return bool
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param bool $publish
     *
     * @return $this
     */
    public function setPublish($publish)
    {
        $this->publish = $publish;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPublish()
    {
        return $this->publish;
    }

    /**
     * @param bool $rename
     *
     * @return $this
     */
    public function setRename($rename)
    {
        $this->rename = $rename;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRename()
    {
        return $this->rename;
    }

    /**
     * @param bool $settings
     *
     * @return $this
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param bool $versions
     *
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param bool $view
     *
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return bool
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param int $cid
     *
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
     * @param int $userId
     *
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
     * @param string $cpath
     *
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
