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

    protected $_pidFileName;

    public function __construct($pidFileName){
        $this->_pidFileName = $pidFileName;
    }

    public function registerJob(Schedule_Maintenance_Job $job, $force = false) {
        if (!$job->isLocked() || $force) {
            $this->jobs[] = $job;
            $job->lock();

            return true;
        }
        return false;
    }

    public function run() {
        $this->setLastExecution();

        foreach ($this->jobs as $job) {
            Logger::info("Executing job with ID: " . $job->getId());
            try {
                $job->execute();     
            }
            catch (Exception $e) {
                Logger::error(get_class($this).": Failed to execute job with id: " . $job->getId());
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
