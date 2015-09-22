<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Controller\Plugin;

class Maintenance extends \Zend_Controller_Plugin_Abstract {

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request) {
        $maintenance = false;
        $file = \Pimcore\Tool\Admin::getMaintenanceModeFile();

        if(is_file($file)) {
            $conf = new \Zend_Config_Xml($file);
            if($conf->sessionId) {
                if($conf->sessionId != $_COOKIE["pimcore_admin_sid"]) {
                    $maintenance = true;
                }
            } else {
                @unlink($file);
            }
        }

        // do not activate the maintenance for the server itself
        // this is to avoid problems with monitoring agents
        $serverIps = array("127.0.0.1");

        if($maintenance && !in_array(\Pimcore\Tool::getClientIp(), $serverIps)) {
            header("HTTP/1.1 503 Service Temporarily Unavailable",503);
            echo file_get_contents(PIMCORE_PATH . "/static/html/maintenance.html");
            exit;
        }
    }
}
