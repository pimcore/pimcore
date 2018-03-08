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
use Pimcore\Logger;
use Pimcore\Tool;
use Pimcore\Cache;

/**
 * @method \Pimcore\Model\User\Workspace\Dao getDao()
 */
class AbstractWorkspace extends Model\AbstractModel
{
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
     * @param $delete
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
     * @param $list
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
     * @param $properties
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
     * @param $publish
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
     * @param $rename
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
     * @param $settings
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
     * @param $versions
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
     * @param $view
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
     * @param $cid
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
     * @param $userId
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
     * @param $cpath
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

    /**
     * save workspace and remove cache entries
     */
    public function save()
    {
        parent::save();
        // remove possible cache entry to have refreshed workspaces on next request
        Cache::remove(static::getType() . '_' . $this->getCid());
    }

    /**
     * get workspace by path
     *
     * @param   string  $path   workspace c-path
     * @param   bool    $force  force refresh cached entry
     *
     * @return self
     */
    public static function getByPath($path, $force = false)
    {
        $path = Model\Element\Service::correctPath($path);

        try {
            $object = new static();

            if (Tool::isValidPath($path)) {
                $object->getDao()->getByPath($path);

                return static::getById($object->getCid(), $force);
            }
        } catch (\Exception $e) {
            Logger::warning($e->getMessage());
        }

        return null;
    }

    /**
     * Static helper to get an workspace by the passed ID
     *
     * @param   int     $id     workspace id
     * @param   bool    $force  force refresh cached entry
     *
     * @return  self
     */
    public static function getById($id, $force = false)
    {
        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = static::getType() . '_' . $id;

        if (!$force && \Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $object = \Pimcore\Cache\Runtime::get($cacheKey);
            if ($object && static::typeMatch($object)) {
                return $object;
            }
        }

        try {
            /** @var static $object */
            if ($force || !($object = Cache::load($cacheKey))) {
                // no model factory, because of unsupported model override
                $object = new static();
                // set to runtime
                \Pimcore\Cache\Runtime::set($cacheKey, $object);
                $object->getDao()->getById($id);

                Cache::save($object, $cacheKey);
            } else {
                \Pimcore\Cache\Runtime::set($cacheKey, $object);
            }
        } catch (\Exception $e) {
            Logger::warning($e->getMessage());

            return null;
        }

        if (!$object || !static::typeMatch($object)) {
            return null;
        }

        return $object;
    }

    /**
     * get workspace type
     *
     * @return  string  workspace type
     */
    public static function getType()
    {
        foreach (['Asset', 'Document', 'DataObject'] as $specific) {
            $class = 'Pimcore\\Model\\User\\Workspace\\' . $specific;
            if (static::class == $class || is_subclass_of(static::class, $class)) {
                return 'user_workspace_' . strtolower($specific);
            }
        }
        return '';
    }

    /**
     * validate generated object type
     *
     * @param   \Pimcore\Model\User\Workspace\AbstractWorkspace $object workspace to validate
     *
     * @return  bool                                                    true if valid
     */
    protected static function typeMatch(AbstractWorkspace $object)
    {
        $staticType = get_called_class();
        if (!$object instanceof $staticType) {
            return false;
        }
        return true;
    }
}
