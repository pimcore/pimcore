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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Update; 

class Update_IndexController extends \Pimcore\Controller\Action\Admin {


    public function init() {
        parent::init();

        // clear the opcache (as of PHP 5.5)
        if(function_exists("opcache_reset")) {
            opcache_reset();
        }

        // clear the APC opcode cache (<= PHP 5.4)
        if(function_exists("apc_clear_cache")) {
            apc_clear_cache();
        }

        // clear the Zend Optimizer cache (Zend Server <= PHP 5.4)
        if (function_exists('accelerator_reset')) {
            return accelerator_reset();
        }

        $this->checkPermission("update");
    }

    public function checkFilePermissionsAction () {
        
        $success = false;
        if(Update::isWriteable()) {
            $success = true;
        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }
    
    public function getAvailableUpdatesAction () {

        $availableUpdates = Update::getAvailableUpdates();
        $this->_helper->json($availableUpdates);
    }
    
    public function getJobsAction () {

        $jobs = Update::getJobs($this->getParam("toRevision"));
        
        $this->_helper->json($jobs);
    }
    
    public function jobParallelAction () {
        if($this->getParam("type") == "download") {
            Update::downloadData($this->getParam("revision"), $this->getParam("url"));
        }
        
        $this->_helper->json(array("success" => true));
    }
    
    public function jobProceduralAction () {
        
        $status = array("success" => true);
        
        if($this->getParam("type") == "files") {
            Update::installData($this->getParam("revision"));
        } else if ($this->getParam("type") == "clearcache") {
            \Pimcore\Model\Cache::clearAll();
        } else if ($this->getParam("type") == "preupdate") {
            $status = Update::executeScript($this->getParam("revision"), "preupdate");
        } else if ($this->getParam("type") == "postupdate") {
            $status = Update::executeScript($this->getParam("revision"), "postupdate");
        } else if ($this->getParam("type") == "cleanup") {
            Update::cleanup();
        }

        $this->_helper->json($status);
    }
}
