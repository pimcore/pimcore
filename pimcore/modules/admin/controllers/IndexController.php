<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Config;
use Pimcore\Tool;
use Pimcore\Model;

class Admin_IndexController extends \Pimcore\Controller\Action\Admin
{
    public function indexAction()
    {
        // clear open edit locks for this session (in the case of a reload, ...)
        \Pimcore\Model\Element\Editlock::clearSession(session_id());

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
        if ($sysConfig->email) {
            if (!$sysConfig->email->debug->emailaddresses) {
                $mailIncomplete = true;
            }
            if (!$sysConfig->email->sender->email) {
                $mailIncomplete = true;
            }
            if ($sysConfig->email->method == "smtp" && !$sysConfig->email->smtp->host) {
                $mailIncomplete = true;
            }
        }
        $this->view->mail_settings_complete =  \Zend_Json::encode(!$mailIncomplete);

        // report configuration
        $this->view->report_config = Config::getReportConfig();

        $cvData = [];

        // still needed when publishing objects
        $cvConfig = Tool::getCustomViewConfig();

        if ($cvConfig) {
            foreach ($cvConfig as $node) {
                $tmpData = $node;
                // backwards compatibility
                $treeType = $tmpData["treetype"] ? $tmpData["treetype"] : "object";
                $rootNode = Model\Element\Service::getElementByPath($treeType, $tmpData["rootfolder"]);

                if ($rootNode) {
                    $tmpData["rootId"] = $rootNode->getId();
                    $tmpData["allowedClasses"] = $tmpData["classes"] ? explode(",", $tmpData["classes"]) : null;
                    $tmpData["showroot"] = (bool)$tmpData["showroot"];

                    // Check if a user has privileges to that node
                    if ($rootNode->isAllowed("list")) {
                        $cvData[] = $tmpData;
                    }
                }
            }
        }

        $this->view->customview_config = $cvData;

        // upload limit
        $max_upload = filesize2bytes(ini_get("upload_max_filesize") . "B");
        $max_post = filesize2bytes(ini_get("post_max_size") . "B");
        $upload_mb = min($max_upload, $max_post);

        $this->view->upload_max_filesize = $upload_mb;

        // session lifetime (gc)
        $session_gc_maxlifetime = ini_get("session.gc_maxlifetime");
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }
        $this->view->session_gc_maxlifetime = $session_gc_maxlifetime;

        // csrf token
        $user = $this->getUser();
        $this->view->csrfToken = Tool\Session::useSession(function ($adminSession) use ($user) {
            if (!isset($adminSession->csrfToken) && !$adminSession->csrfToken) {
                $adminSession->csrfToken = sha1(microtime() . $user->getName() . uniqid());
            }

            return $adminSession->csrfToken;
        });
    }
}
