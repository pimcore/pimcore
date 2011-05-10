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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Schedule_Maintenance_Job {

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

    /**
     * @return string
     */
    public function getLockFile() {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/maintenance_" . $this->getId() . ".pid";
    }

    /**
     * create lock file
     * @return void
     */
    public function lock() {
        file_put_contents($this->getLockFile(), time());
        chmod($this->getLockFile(), 0766);
    }

    /**
     * delete lock file
     * @return void
     */
    public function unlock() {
        @unlink($this->getLockFile());
    }

    /**
     * @return bool
     */
    public function isLocked() {
        if (file_exists($this->getLockFile())) {
            return true;
        }
        return false;
    }

    /**
     * @param  string $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
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
    }

}
