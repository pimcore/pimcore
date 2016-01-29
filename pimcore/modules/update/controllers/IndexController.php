<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use Pimcore\Update;

class Update_IndexController extends \Pimcore\Controller\Action\Admin
{


    public function init()
    {
        parent::init();

        Update::clearOPCaches();

        $this->checkPermission("update");
    }

    public function checkFilePermissionsAction()
    {
        $success = false;
        if (Update::isWriteable()) {
            $success = true;
        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }
    
    public function getAvailableUpdatesAction()
    {
        $availableUpdates = Update::getAvailableUpdates();
        $this->_helper->json($availableUpdates);
    }
    
    public function getJobsAction()
    {
        $jobs = Update::getJobs($this->getParam("toRevision"));
        
        $this->_helper->json($jobs);
    }
    
    public function jobParallelAction()
    {
        if ($this->getParam("type") == "download") {
            Update::downloadData($this->getParam("revision"), $this->getParam("url"));
        }
        
        $this->_helper->json(array("success" => true));
    }
    
    public function jobProceduralAction()
    {
        $status = array("success" => true);
        
        if ($this->getParam("type") == "files") {
            Update::installData($this->getParam("revision"));
        } elseif ($this->getParam("type") == "clearcache") {
            \Pimcore\Cache::clearAll();
        } elseif ($this->getParam("type") == "preupdate") {
            $status = Update::executeScript($this->getParam("revision"), "preupdate");
        } elseif ($this->getParam("type") == "postupdate") {
            $status = Update::executeScript($this->getParam("revision"), "postupdate");
        } elseif ($this->getParam("type") == "cleanup") {
            Update::cleanup();
        }

        // we use pure PHP here, otherwise this can cause issues with dependencies that changed during the update
        header("Content-type: application/json");
        echo json_encode($status);
        exit;
    }
}
