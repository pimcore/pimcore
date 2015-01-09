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

use Pimcore\Config;
use Pimcore\Tool;
use Pimcore\Model;

class Admin_IndexController extends \Pimcore\Controller\Action\Admin {

    public function indexAction() {

        // IE compatibility
        //$this->getResponse()->setHeader("X-UA-Compatible", "IE=8; IE=9", true);

        // check maintenance
        $maintenance_enabled = false;

        $manager = Model\Schedule\Manager\Factory::getManager("maintenance.pid");

        $lastExecution = $manager->getLastExecution(); 
        if ($lastExecution) {
            if ((time() - $lastExecution) < 610) { // maintenance script should run at least every 10 minutes + a little tolerance
                $maintenance_enabled = true;
            }                                    
        }

        $this->view->maintenance_enabled = \Zend_Json::encode($maintenance_enabled);

        // configuration
        $sysConfig = Config::getSystemConfig();
        $this->view->config = $sysConfig;

        //mail settings
        $mailIncomplete = false;
        if($sysConfig->email) {
            if(!$sysConfig->email->debug->emailaddresses) {
                $mailIncomplete = true;
            }
            if(!$sysConfig->email->sender->email){
                $mailIncomplete = true;
            }
             if($sysConfig->email->method == "smtp" && !$sysConfig->email->smtp->host){
                 $mailIncomplete = true;
             }
        }
        $this->view->mail_settings_complete =  \Zend_Json::encode(!$mailIncomplete);




        // report configuration
        $this->view->report_config = Config::getReportConfig();

        // customviews config
        $cvConfig = Tool::getCustomViewConfig();
        $cvData = array();

        if ($cvConfig) {
            foreach ($cvConfig as $node) {
                $tmpData = $node;
                $rootNode = Model\Object::getByPath($tmpData["rootfolder"]);

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
        $upload_mb = min($max_upload, $max_post);
        
        $this->view->upload_max_filesize = $upload_mb;


        // csrf token
        $user = $this->getUser();
        $this->view->csrfToken = Tool\Session::useSession(function($adminSession) use ($user) {
            if(!isset($adminSession->csrfToken) && !$adminSession->csrfToken) {
                $adminSession->csrfToken = sha1(microtime() . $user->getName() . uniqid());
            }
            return $adminSession->csrfToken;
        });
    }
}
