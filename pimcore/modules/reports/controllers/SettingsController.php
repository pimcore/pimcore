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

use Pimcore\File;

class Reports_SettingsController extends \Pimcore\Controller\Action\Admin\Reports
{

    public function getAction()
    {
        $this->checkPermission("system_settings");

        $conf = $this->getConfig();

        $response = array(
            "values" => $conf->toArray(),
            "config" => array()
        );

        $this->_helper->json($response);
    }

    public function saveAction()
    {
        $this->checkPermission("system_settings");

        $values = \Zend_Json::decode($this->getParam("data"));

        $configFile = \Pimcore\Config::locateConfigFile("reports.php");
        File::putPhpFile($configFile, to_php_data_file_format($values));

        $this->_helper->json(array("success" => true));
    }
}
