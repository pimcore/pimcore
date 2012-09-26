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

class Schedule_Task extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var integer
     */
    public $date;

    /**
     * @var string
     */
    public $action;

    /**
     * @var version
     */
    public $version;

    /**
     * @var version
     */
    public $active;

    /**
     * @param integer $id
     * @return Schedule_Task
     */
    public static function getById($id) {

        $cacheKey = "scheduled_task_" . $id;

        try {
            $task = Zend_Registry::get($cacheKey);
            if(!$task) {
                throw new Exception("Scheduled Task in Registry is not valid");
            }
        }
        catch (Exception $e) {
            $task = new self();
            $task->getResource()->getById(intval($id));

            Zend_Registry::set($cacheKey, $task);
        }

        return $task;
    }

    /**
     * @param array $data
     * @return Schedule_Task
     */
    public static function create($data) {

        $task = new self();
        $task->setValues($data);
        return $task;
    }

    /**
     * @param array $data
     */
    public function __construct($data = array()) {
        $this->setValues($data);
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
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @return integer
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @return integer
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param integer $id
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param integer $cid
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
    }

    /**
     * @param string $ctype
     */
    public function setCtype($ctype) {
        $this->ctype = $ctype;
    }

    /**
     * @param intger $date
     */
    public function setDate($date) {
        $this->date = (int) $date;
    }

    /**
     * @param string $action
     */
    public function setAction($action) {
        $this->action = $action;
    }

    /**
     * @param integer $version
     */
    public function setVersion($version) {
        $this->version = $version;
    }

    /**
     * @return boolean
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active) {
        if (empty($active)) {
            $active = false;
        }
        $this->active = (bool) $active;
    }

}
