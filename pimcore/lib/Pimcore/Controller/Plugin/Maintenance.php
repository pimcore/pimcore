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

namespace Pimcore\Controller\Plugin;

class Maintenance extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(\Zend_Controller_Request_Abstract $request)
    {
        $maintenance = false;
        $file = \Pimcore\Tool\Admin::getMaintenanceModeFile();

        if (is_file($file)) {
            $conf = include($file);
            if (isset($conf["sessionId"])) {
                if ($conf["sessionId"] != $_COOKIE["pimcore_admin_sid"]) {
                    $maintenance = true;
                }
            } else {
                @unlink($file);
            }
        }

        // do not activate the maintenance for the server itself
        // this is to avoid problems with monitoring agents
        $serverIps = ["127.0.0.1"];

        if ($maintenance && !in_array(\Pimcore\Tool::getClientIp(), $serverIps)) {
            header("HTTP/1.1 503 Service Temporarily Unavailable", 503);

            $file = PIMCORE_PATH . "/static6/html/maintenance.html";

            $customFile = PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/maintenance.html";
            if (file_exists($customFile)) {
                $file = $customFile;
            }

            echo file_get_contents($file);
            exit;
        }
    }
}
