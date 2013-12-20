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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Schedule_Manager_Procedural {


    public $jobs = array();

    protected $validJobs = array();

    protected $_pidFileName;

    protected $force = false;

    public function __construct($pidFileName){
        $this->_pidFileName = $pidFileName;
    }


    public function setValidJobs ($validJobs) {
        if(is_array($validJobs)) {
            $this->validJobs = $validJobs;
        }
        return $this;
    }

    public function registerJob(Schedule_Maintenance_Job $job, $force = false) {

        if(!empty($this->validJobs) and !in_array($job->getId(),$this->validJobs)) {
            Logger::info("Skipped job with ID: " . $job->getId() . " because it is not in the valid jobs.");
            return false;
        }

        if (!$job->isLocked() || $force || $this->getForce()) {
            $this->jobs[] = $job;
            $job->lock();

            Logger::info("Registered job with ID: " . $job->getId());

            return true;
        } else {
            Logger::info("Skipped job with ID: " . $job->getId() . " because it is still locked.");
        }
        
        return false;
    }

    public function run() {
        $this->setLastExecution();

        foreach ($this->jobs as $job) {
            Logger::info("Executing job with ID: " . $job->getId());
            try {
                $job->execute();
                Logger::info("Finished job with ID: " . $job->getId());
            }
            catch (Exception $e) {
                Logger::error("Failed to execute job with id: " . $job->getId());
                Logger::error($e);
            }
            $job->unlock();
        }
    }

    public function setLastExecution() {
        Tool_Lock::lock($this->_pidFileName);
    }

    public function getLastExecution() {
        $lock = Tool_Lock::get($this->_pidFileName);
        if($date = $lock->getDate()) {
            return $date;
        }
        return;
    }

    /**
     * @param boolean $force
     */
    public function setForce($force)
    {
        $this->force = $force;
    }

    /**
     * @return boolean
     */
    public function getForce()
    {
        return $this->force;
    }
}
