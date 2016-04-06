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

class Admin_BackupController extends \Pimcore\Controller\Action\Admin
{


    public function init()
    {
        parent::init();

        $this->checkPermission("backup");

        @ini_set("memory_limit", "-1");

        $this->session = \Pimcore\Tool\Session::get("pimcore_backup");
    }

    public function initAction()
    {
        $backup = new \Pimcore\Backup(PIMCORE_BACKUP_DIRECTORY . "/backup_" . date("m-d-Y_H-i") . ".zip");
        $initInfo = $backup->init();
        
        $this->session->backup = $backup;

        $this->_helper->json($initInfo);
    }

    public function filesAction()
    {
        $backup = $this->session->backup;
        $return = $backup->fileStep($this->getParam("step"));
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function mysqlTablesAction()
    {
        $backup = $this->session->backup;
        $return = $backup->mysqlTables();
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function mysqlAction()
    {
        $name = $this->getParam("name");
        $type = $this->getParam("type");
        
        $backup = $this->session->backup;
        $return = $backup->mysqlData($name, $type);
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function mysqlCompleteAction()
    {
        $backup = $this->session->backup;
        $return = $backup->mysqlComplete();
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function completeAction()
    {
        $backup = $this->session->backup;
        $return = $backup->complete();
        $this->session->backup = $backup;
                                
        $this->_helper->json($return);
    }

    public function downloadAction()
    {
        $backup = $this->session->backup;
        
        header("Content-Type: application/zip");
        header('Content-Disposition: attachment; filename="' . basename($backup->getBackupFile()) . '"');

        while (@ob_end_flush());
        flush();

        readfile($backup->getBackupFile());
        
        exit;
    }
}
