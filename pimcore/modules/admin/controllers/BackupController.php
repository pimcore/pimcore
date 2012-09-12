<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_BackupController extends Pimcore_Controller_Action_Admin {


    public function init() {
        parent::init();

        @ini_set("memory_limit", "-1");

        $this->session = new Zend_Session_Namespace("pimcore_backup");
    }

    public function initAction() {

        $backup = new Pimcore_Backup(PIMCORE_BACKUP_DIRECTORY . "/backup_" . date("m-d-Y_H-i") . ".tar");
        $initInfo = $backup->init();
        
        $this->session->backup = $backup;        

        $this->_helper->json($initInfo);
    }

    public function filesAction() {

        $backup = $this->session->backup;
        $return = $backup->fileStep($this->getParam("step"));
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function mysqlTablesAction() {

        $backup = $this->session->backup;
        $return = $backup->mysqlTables();              
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
        
    }

    public function mysqlAction() {

        $name = $this->getParam("name");
        $type = $this->getParam("type");
        
        $backup = $this->session->backup;
        $return = $backup->mysqlData($name, $type);              
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function mysqlCompleteAction() {

        $backup = $this->session->backup;
        $return = $backup->mysqlComplete();              
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function completeAction() {

        $backup = $this->session->backup;
        $return = $backup->complete();              
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function downloadAction() {
        
        $backup = $this->session->backup;
        
        header("Content-Type: application/tar");
        header('Content-Disposition: attachment; filename="' . basename($backup->getBackupFile()) . '"');
        readfile($backup->getBackupFile());
        
        exit;
    }
}
