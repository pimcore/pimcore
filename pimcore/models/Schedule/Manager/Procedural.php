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

class Schedule_Manager_Procedural {


    public $jobs = array();

    protected $validJobs = array();

    protected $_pidFileName;

    public function __construct($pidFileName){
        $this->_pidFileName = $pidFileName;
    }


    public function setValidJobs ($validJobs) {
        if(is_array($validJobs)) {
            $this->validJobs = $validJobs;
        }
    }

    public function registerJob(Schedule_Maintenance_Job $job, $force = false) {

        if(!empty($this->validJobs) and !in_array($job->getId(),$this->validJobs)) {
            Logger::info("Skipped job with ID: " . $job->getId() . " because it is not in the valid jobs.");
            return false;
        }

        if (!$job->isLocked() || $force) {
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

    public  function getPidFile() {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/".$this->_pidFileName;
    }

    public  function setLastExecution() {
        file_put_contents($this->getPidFile(), time());
        chmod($this->getPidFile(), 0766);
    }

    public  function getLastExecution() {
        if (is_file($this->getPidFile())) {
            return file_get_contents($this->getPidFile());
        }
        return;
    }
}
