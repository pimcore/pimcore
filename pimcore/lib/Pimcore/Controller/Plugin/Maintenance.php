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
        $serverIps = array("127.0.0.1");

        if ($maintenance && !in_array(\Pimcore\Tool::getClientIp(), $serverIps)) {
            header("HTTP/1.1 503 Service Temporarily Unavailable", 503);

            $pathToMaintenanceFile = $this->getPathToMaintenanceFile();
            echo file_get_contents($pathToMaintenanceFile);
            exit;
        }
    }

    /**
     * Checks if there is a maintenance file under the website folder, fallback on the file in the pimcore folder.
     *
     * @return string
     */
    protected function getPathToMaintenanceFile()
    {
        $maintenancePageFilePath = '/static/html/maintenance.html';
        $maintenancePageFileFullPath = PIMCORE_PATH . $maintenancePageFilePath;

        // check if there is a maintenance.html file under the website folder
        if (is_readable(PIMCORE_WEBSITE_PATH . $maintenancePageFilePath)) {
            $maintenancePageFileFullPath = PIMCORE_WEBSITE_PATH . $maintenancePageFilePath;
        }

        return $maintenancePageFileFullPath;
    }
}
