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
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Schedule\Maintenance;

use Pimcore\Model;
use Pimcore\Model\Tool;

class Job {

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

    public function __construct($id, $object, $method, $arguments=null) {

        $this->setId($id);
        $this->setObject($object);
        $this->setMethod($method);
        $this->setArguments($arguments);
    }

    /**
     * execute job
     * @return mixed
     */
    public function execute() {
        if (method_exists($this->getObject(), $this->getMethod())) {
            $arguments = $this->getArguments();
            if(!is_array($arguments)){
                $arguments = array();
            }
            return call_user_func_array(array($this->getObject(), $this->getMethod()), $arguments);
        }
    }

    public function getLockKey() {
        return "maintenance-job-" . $this->getId();
    }

    /**
     * create lock file
     * @return void
     */
    public function lock() {
        Tool\Lock::lock($this->getLockKey());
    }

    /**
     * delete lock file
     * @return void
     */
    public function unlock() {
        Tool\Lock::release($this->getLockKey());
    }

    /**
     * @return bool
     */
    public function isLocked() {
        return Tool\Lock::isLocked($this->getLockKey(), 86400); // 24h expire
    }

    /**
     * @param  string $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param  object $object
     * @return void
     */
    public function setObject($object) {
        $this->object = $object;
        return $this;
    }

    /**
     * @return object
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * @param  string $method
     * @return void
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArguments(){
        return $this->arguments;
    }

    /**
     * @param  array $args
     * @return void
     */
    public function setArguments($args){
        $this->arguments = $args;
        return $this;
    }

}
