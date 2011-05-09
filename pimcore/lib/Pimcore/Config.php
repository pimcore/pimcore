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

class Pimcore_Config {

    /**
     * @static
     * @return Zend_Config
     */
    public static function getSystemConfig () {

        $config = null;

        try {
            $config = Zend_Registry::get("pimcore_config_system");
        } catch (Exception $e) {

            try {
                $config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_SYSTEM);
                self::setSystemConfig($config);
            } catch (Exception $e) {
                Logger::emergency("Cannot find system configuration, should be located at: " . PIMCORE_CONFIGURATION_SYSTEM);
            }
        }

        return $config;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return void
     */
    public static function setSystemConfig (Zend_Config $config) {
        Zend_Registry::set("pimcore_config_system", $config);
    }


}
