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
 
class Pimcore_Controller_Action_Admin_Reports extends Pimcore_Controller_Action_Admin {
    
    public $conf;
    
    public function init () {
        
        parent::init();
        
        // add include paths
        $includePaths = array(
            PIMCORE_PATH . "/modules/reports/lib",
            get_include_path()
        );
        set_include_path(implode(PATH_SEPARATOR, $includePaths));
        
    }
    
    public function getConfig () {
        return Pimcore_Config::getReportConfig();
    }
    
    protected function getAnalyticsCredentials () {
        
        $conf = $this->getConfig()->analytics;
        
        if($conf->username && $conf->password) {
            return array(
                "username" => $conf->username,
                "password" => $conf->password
            ); 
        }

        $conf = Pimcore_Config::getSystemConfig()->services->google;
        if($conf->username && $conf->password) {
            return array(
                "username" => $conf->username,
                "password" => $conf->password
            ); 
        }
        
        return false;
    }
    
    protected function getWebmastertoolsCredentials () {
        
        $conf = $this->getConfig()->webmastertools;
        
        if($conf->username && $conf->password) {
            return array(
                "username" => $conf->username,
                "password" => $conf->password
            ); 
        }

        $conf = Pimcore_Config::getSystemConfig()->services->google;
        if($conf->username && $conf->password) {
            return array(
                "username" => $conf->username,
                "password" => $conf->password
            ); 
        }
        
        return false;
    }
}