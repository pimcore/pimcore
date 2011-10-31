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

class Pimcore_Controller_Plugin_Maintenance extends Zend_Controller_Plugin_Abstract {

    public function routeStartup(Zend_Controller_Request_Abstract $request) {

        $maintenance = false;
        $file = Pimcore_Tool_Admin::getMaintenanceModeFile();

        if(is_file($file)) {
            $conf = new Zend_Config_Xml($file);
            if($conf->sessionId) {
                if($conf->sessionId != $_COOKIE["pimcore_admin_sid"]) {
                    $maintenance = true;
                }
            } else {
                @unlink($file);
            }
        }

        if($maintenance) {
            header("HTTP/1.1 503 Service Temporarily Unavailable",503);

            echo file_get_contents(PIMCORE_PATH . "/static/html/maintenance.html");
            exit;
        }
    }
}
