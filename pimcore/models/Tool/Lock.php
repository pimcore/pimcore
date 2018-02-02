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
 * @package    Tool
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Lock\Dao getDao()
 */
class Lock extends Model\AbstractModel
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $date;

    /**
     * @var array
     */
    protected static $acquiredLocks = [];

    /**
     * @var Lock
     */
    protected static $instance;

    /**
     * @return Lock
     */
    protected static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $key
     * @param int $expire
     * @param int $refreshInterval
     */
    public static function acquire($key, $expire = 120, $refreshInterval = 1)
    {
        $instance = self::getInstance();
        $instance->getDao()->acquire($key, $expire, $refreshInterval);

        self::$acquiredLocks[$key] = $key;
    }

    /**
     * @param string $key
     */
    public static function release($key)
    {
        $instance = self::getInstance();
        $instance->getDao()->release($key);

        unset(self::$acquiredLocks[$key]);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function lock($key)
    {
        $instance = self::getInstance();

        return $instance->getDao()->lock($key);
    }

    /**
     * @param $key
     * @param int $expire
     *
     * @return mixed
     */
    public static function isLocked($key, $expire = 120)
    {
        $instance = self::getInstance();

        return $instance->getDao()->isLocked($key, $expire);
    }

    /**
     * @param $key
     *
     * @return Lock
     */
    public static function get($key)
    {
        $lock = new self;
        $lock->getById($key);

        return $lock;
    }

    public static function releaseAll()
    {
        $locks = self::$acquiredLocks;

        foreach ($locks as $key) {
            self::release($key);
        }
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
