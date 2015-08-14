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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Log;

class Helper {

    const ERROR_LOG_TABLE_NAME = "application_logs";
    const ERROR_LOG_ARCHIVE_TABLE_NAME = "application_logs_archive";

    protected static $_pluginConfig;

    protected static $_logLevels = array(0 => 'Emergency: system is unusable',
                                        1 => 'Alert: action must be taken immediately',
                                        2 => 'Critical: critical conditions',
                                        3 => 'Error: error conditions',
                                        4 => 'Warning: warning conditions',
                                        5 => 'Notice: normal but significant condition',
                                        6 => 'Informational: informational messages',
                                        7 => 'Debug: debug messages',
                                );

    public static function getConfigFilePath()
    {
        return PIMCORE_WEBSITE_PATH . '/var/plugins/Elements_Logging/config.xml';
    }

    public static function getLogLevels(){
        return self::$_logLevels;
    }

    public static function getPluginConfig()
    {
        $pluginFile = self::getConfigFilePath();
        if (is_null(self::$_pluginConfig)) {
            self::$_pluginConfig = new \Zend_Config_Xml($pluginFile, 'configData');
        }
        return self::$_pluginConfig;
    }

}