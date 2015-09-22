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

class Reports_SettingsController extends \Pimcore\Controller\Action\Admin\Reports {
    
    public function getAction () {

        $this->checkPermission("system_settings");

        $conf = $this->getConfig();

        $response = array(
            "values" => $conf->toArray(),
            "config" => array()
        );

        $this->_helper->json($response);
    }
    
    public function saveAction () {

        $this->checkPermission("system_settings");

        $values = \Zend_Json::decode($this->getParam("data"));

        $config = new \Zend_Config($values, true);
        $writer = new \Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/reports.xml"
        ));
        $writer->write();

        $this->_helper->json(array("success" => true));
    }
}
