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

abstract class Document_Versionable_Resource extends Document_Resource {

    /**
     * Get available versions fot the object and return an array of them
     *
     * @return array
     */
    public function getVersions() {        
        $versionList = new Version_List();
        $versionList->setCondition("cid = ? AND ctype='document'", $this->model->getId());
        $versionList->setOrderKey("id");
        $versionList->setOrder("DESC");
        $versions = $versionList->load();

        $this->model->setVersions($versions);

        return $versions;
    }
    
    
    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     * @param bool $force
     * @return array
     */
    public function getLatestVersion($force = false) {
        try {
            $version = Version::getByCidAndCtype($this->model->getId(), 'document');

            if($force || $version->getDate() >= $this->model->getModificationDate()) {
                return $version;  
            }
        } catch(Exception $e) {
            
        }
        return;
    }
    
    public function getScheduledTasks() {
        $taskList = new Schedule_Task_List();
        $taskList->setCondition("cid = ? AND ctype='document'", $this->model->getId());
        $scheduledTasks = $taskList->load();

        $this->model->setScheduledTasks($scheduledTasks);

        return $scheduledTasks;
    }

}
