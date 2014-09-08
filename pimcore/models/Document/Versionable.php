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
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Document_Versionable extends Document {

    /**
     * Contains all versions of the document
     *
     * @var array
     */
    public $versions = null;

    /**
     * Contains all scheduled tasks
     *
     * @var array
     */
    public $scheduledTasks = null;

    /**
     * Save the current object as version
     *
     * @return void
     */
    public function saveVersion($setModificationDate = true, $callPluginHook = true) {
        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            Pimcore::getEventManager()->trigger("document.preUpdate", $this);
        }

        // set date
        if ($setModificationDate) {
            $this->setModificationDate(time());
        }
        
        // scheduled tasks are saved always, they are not versioned!
        $this->saveScheduledTasks();
        
        // create version
        $version = null;

        // only create a new version if there is at least 1 allowed
        if(Pimcore_Config::getSystemConfig()->documents->versions->steps
            || Pimcore_Config::getSystemConfig()->documents->versions->days) {
            $version = new Version();
            $version->setCid($this->getId());
            $version->setCtype("document");
            $version->setDate($this->getModificationDate());
            $version->setUserId($this->getUserModification());
            $version->setData($this);
            $version->save();
        }

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            Pimcore::getEventManager()->trigger("document.postUpdate", $this);
        }

        return $version;
    }

    /**
     * @see Document::delete
     * @return void
     */
    public function delete() {
        $versions = $this->getVersions();
        foreach ($versions as $version) {
            $version->delete();
        }

        // remove all tasks
        $this->getResource()->deleteAllTasks();

        parent::delete();
    }

    /**
     * @return array
     */
    public function getVersions() {
        if ($this->versions === null) {
            $this->setVersions($this->getResource()->getVersions());
        }
        return $this->versions;
    }

    /**
     * @param array $versions
     * @return void
     */
    public function setVersions($versions) {
        $this->versions = $versions;
        return $this;
    }

    /**
     * @return the $scheduledTasks
     */
    public function getScheduledTasks() {
        if ($this->scheduledTasks == null) {
            $taskList = new Schedule_Task_List();
            $taskList->setCondition("cid = ? AND ctype='document'", $this->getId());
            $this->setScheduledTasks($taskList->load());
        }
        return $this->scheduledTasks;
    }

    /**
     * @param $scheduledTasks the $scheduledTasks to set
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
        return $this;
    }
    
    
    public function saveScheduledTasks() {
        $this->getScheduledTasks();
        $this->getResource()->deleteAllTasks();
        
        if (is_array($this->getScheduledTasks()) && count($this->getScheduledTasks()) > 0) {
            foreach ($this->getScheduledTasks() as $task) {
                $task->setId(null);
                $task->setResource(null);
                $task->setCid($this->getId());
                $task->setCtype("document");
                $task->save();
            }
        }
    }
}
