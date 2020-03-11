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
 * @package    Schedule
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Schedule\Task\Dao getDao()
 * @method void save()
 */
class Task extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var int
     */
    public $date;

    /**
     * @var string
     */
    public $action;

    /**
     * @var int
     */
    public $version;

    /**
     * @var bool
     */
    public $active;

    /**
     * @var null|int
     */
    protected $userId;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        $cacheKey = 'scheduled_task_' . $id;

        try {
            $task = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$task) {
                throw new \Exception('Scheduled Task in Registry is not valid');
            }
        } catch (\Exception $e) {
            try {
                $task = new self();
                $task->getDao()->getById(intval($id));
                \Pimcore\Cache\Runtime::set($cacheKey, $task);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $task;
    }

    /**
     * @param array $data
     *
     * @return Task
     */
    public static function create($data)
    {
        $task = new self();
        $task->setValues($data);

        return $task;
    }

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->setValues($data);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param int $cid
     *
     * @return $this
     */
    public function setCid($cid)
    {
        $this->cid = (int) $cid;

        return $this;
    }

    /**
     * @param string $ctype
     *
     * @return $this
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = (int) $date;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param int $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        if (empty($active)) {
            $active = false;
        }
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     *
     * @return $this
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
