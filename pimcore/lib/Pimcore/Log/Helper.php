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