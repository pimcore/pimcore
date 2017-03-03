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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule\Maintenance;

use Pimcore\Model\Tool;

class Job
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var bool
     */
    public $locked = false;

    /**
     * @var object
     */
    public $object;

    /**
     * @var string
     */
    public $method;
    /**
     * @var array
     */
    public $arguments;

    /**
     * @param $id
     * @param $object
     * @param $method
     * @param null $arguments
     */
    public function __construct($id, $object, $method, $arguments = null)
    {
        $this->setId($id);
        $this->setObject($object);
        $this->setMethod($method);
        $this->setArguments($arguments);
    }

    /**
     * execute job
     * @return mixed
     */
    public function execute()
    {
        if (method_exists($this->getObject(), $this->getMethod())) {
            $arguments = $this->getArguments();
            if (!is_array($arguments)) {
                $arguments = [];
            }

            return call_user_func_array([$this->getObject(), $this->getMethod()], $arguments);
        }
        //TODO: Shouldn't we return null here?
    }

    /**
     * @return string
     */
    public function getLockKey()
    {
        return "maintenance-job-" . $this->getId();
    }

    /**
     * create lock file
     */
    public function lock()
    {
        Tool\Lock::lock($this->getLockKey());
    }

    /**
     * delete lock file
     */
    public function unlock()
    {
        Tool\Lock::release($this->getLockKey());
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return Tool\Lock::isLocked($this->getLockKey(), 86400); // 24h expire
    }

    /**
     * @param  string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $object
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param $args
     * @return $this
     */
    public function setArguments($args)
    {
        $this->arguments = $args;

        return $this;
    }
}
