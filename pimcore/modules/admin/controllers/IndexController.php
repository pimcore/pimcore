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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_IndexController extends Pimcore_Controller_Action_Admin {

    public function indexAction() {

        // IE compatibility
        $this->getResponse()->setHeader("X-UA-Compatible", "IE=8; IE=9", true);

        // check maintenance
        $maintenance_enabled = false;

        $manager = Schedule_Manager_Factory::getManager("maintenance.pid");

        $lastExecution = $manager->getLastExecution(); 
        if ($lastExecution) {
            if ((time() - $lastExecution) < 610) { // maintenance script should run at least every 10 minutes + a little tolerance
                $maintenance_enabled = true;
            }                                    
        }

        $this->view->maintenance_enabled = Zend_Json::encode($maintenance_enabled);

        // configuration
        $this->view->config = Pimcore_Config::getSystemConfig();

        //mail settings
        $mailIncomplete = false;
        if($this->view->config->email) {
            $emailSettings = $this->view->config->email->toArray();
            if($emailSettings['method']=="sendmail" and !empty($emailSettings['sender']['email'])){
                $mailIncomplete=true;
            }
             if($emailSettings['method']=="smtp" and !empty($emailSettings['sender']['email']) and !empty($emailSettings['smtp']['host'])){
                 $mailIncomplete=true;
             }
        }
        $this->view->mail_settings_incomplete =  Zend_Json::encode($mailIncomplete);




        // report configuration
        $this->view->report_config = Pimcore_Config::getReportConfig();

        // customviews config
        $cvConfig = Pimcore_Tool::getCustomViewConfig();
        $cvData = array();

        if ($cvConfig) {
            foreach ($cvConfig as $node) {
                $tmpData = $node;
                $rootNode = Object_Abstract::getByPath($tmpData["rootfolder"]);

                if ($rootNode) {
                    $tmpData["rootId"] = $rootNode->getId();
                    $tmpData["allowedClasses"] = explode(",", $tmpData["classes"]);
                    $tmpData["showroot"] = (bool) $tmpData["showroot"];

                    $cvData[] = $tmpData;
                }
            }
        }

        $this->view->customview_config = $cvData;
        
        
        // upload limit
        $max_upload = filesize2bytes(ini_get("upload_max_filesize") . "B");
        $max_post = filesize2bytes(ini_get("post_max_size") . "B");
        $memory_limit = filesize2bytes(ini_get("memory_limit") . "B");
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        
        $this->view->upload_max_filesize = $upload_mb;

        // live connect
        $liveconnectToken = Pimcore_Liveconnect::getToken();
        $this->view->liveconnectToken = $liveconnectToken;

        // adding css minify filter because of IE issues with CkEditor and more than 31 stylesheets
        if(!PIMCORE_DEVMODE) {
            $front = Zend_Controller_Front::getInstance();
            $front->registerPlugin(new Pimcore_Controller_Plugin_CssMinify(), 800);
        }
    }
}
