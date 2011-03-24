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
 
class Pimcore_Report {

    public static $config;
    
    public static function getConfig() {
        if(!self::$config) {
            try {
                self::$config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_DIRECTORY . "/reports.xml");
            }
            catch (Exception $e) {
                self::$config = new Zend_Config(array());
            }
        }
        return self::$config;
    }  
}
