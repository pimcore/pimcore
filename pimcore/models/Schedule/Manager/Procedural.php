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

namespace Pimcore\Model\Schedule\Manager;

use Pimcore\Model;

class Procedural {

    /**
     * @var array
     */
    public $jobs = array();

    /**
     * @var array
     */
    protected $validJobs = array();

    /**
     * @var
     */
    protected $_pidFileName;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param $pidFileName
     */
    public function __construct($pidFileName){
        $this->_pidFileName = $pidFileName;
    }

    /**
     * @param $validJobs
     * @return $this
     */
    public function setValidJobs ($validJobs) {
        if(is_array($validJobs)) {
            $this->validJobs = $validJobs;
        }
        return $this;
    }

    /**
     * @param Model\Schedule\Maintenance\Job $job
     * @param bool $force
     * @return bool
     */
    public function registerJob(Model\Schedule\Maintenance\Job $job, $force = false) {

        if(!empty($this->validJobs) and !in_array($job->getId(),$this->validJobs)) {
            \Logger::info("Skipped job with ID: " . $job->getId() . " because it is not in the valid jobs.");
            return false;
        }

        if (!$job->isLocked() || $force || $this->getForce()) {
            $this->jobs[] = $job;
            $job->lock();

            \Logger::info("Registered job with ID: " . $job->getId());

            return true;
        } else {
            \Logger::info("Skipped job with ID: " . $job->getId() . " because it is still locked.");
        }
        
        return false;
    }

    /**
     *
     */
    public function run() {
        $this->setLastExecution();

        foreach ($this->jobs as $job) {
            \Logger::info("Executing job with ID: " . $job->getId());
            try {
                $job->execute();
                \Logger::info("Finished job with ID: " . $job->getId());
            }
            catch (\Exception $e) {
                \Logger::error("Failed to execute job with id: " . $job->getId());
                \Logger::error($e);
            }
            $job->unlock();
        }
    }

    /**
     *
     */
    public function setLastExecution() {
        Model\Tool\Lock::lock($this->_pidFileName);
    }

    /**
     * @return mixed
     */
    public function getLastExecution() {
        $lock = Model\Tool\Lock::get($this->_pidFileName);
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
