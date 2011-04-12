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

class Pimcore_ExtensionManager {

    private static $config;

    public static function getConfig () {
        if(!self::$config) {
            try {
                self::$config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_DIRECTORY . "/extensions.xml", null, array("allowModifications" => true));
            }
            catch (Exception $e) {
                self::$config = new Zend_Config(array(), null, array("allowModifications" => true));
            }
        }
        return self::$config;
    }

    public static function setConfig (Zend_Config $config) {

        self::$config = $config;

        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/extensions.xml"
        ));
        $writer->write();
    }

    public static function isEnabled ($type, $id) {
        $config = self::getConfig();
        return (bool) $config->$type->$id;
    }

    public static function enable ($type, $id) {
        $config = self::getConfig();
        if(!isset($config->$type)) {
            $config->$type = new Zend_Config(array(), true);
        }
        $config->$type->$id = true;
        self::setConfig($config);
    }

    public static function disable ($type, $id) {
        $config = self::getConfig();
        if(!isset($config->$type)) {
            $config->$type = new Zend_Config(array(), true);
        }
        $config->$type->$id = false;
        self::setConfig($config);
    }


    /**
     * @return Array $pluginConfigs
     */
    public static function getPluginConfigs() {

        $pluginConfigs = array();

        if (is_dir(PIMCORE_PLUGINS_PATH) && is_readable(PIMCORE_PLUGINS_PATH)) {
            $pluginDirs = scandir(PIMCORE_PLUGINS_PATH);
            if (is_array($pluginDirs)) {
                foreach ($pluginDirs as $d) {
                    if ($d != "." and $d != ".." and is_dir(PIMCORE_PLUGINS_PATH . "//" . $d)) {
                        if (file_exists(PIMCORE_PLUGINS_PATH . "/" . $d . "/plugin.xml")) {
                            $pluginConf = new Zend_Config_Xml(PIMCORE_PLUGINS_PATH . "/" . $d . "/plugin.xml");
                            if ($pluginConf != null) {
                                $pluginConfigs[] = $pluginConf->toArray();
                            }
                        }
                    }
                }
            }
        }
        return $pluginConfigs;
    }
}