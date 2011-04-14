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

    /**
     * @var Zend_Config
     */
    private static $config;

    /**
     * @static
     * @return Zend_Config
     */
    public static function getConfig () {
        if(!self::$config) {
            try {
                self::$config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_DIRECTORY . "/extensions.xml", null, array("allowModifications" => true));
            }
            catch (Exception $e) {
                self::$config = new Zend_Config(array(), true);
            }
        }
        return self::$config;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return void
     */
    public static function setConfig (Zend_Config $config) {

        self::$config = $config;

        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $config,
            "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/extensions.xml"
        ));
        $writer->write();
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     * @return bool
     */
    public static function isEnabled ($type, $id) {
        $config = self::getConfig();
        return (bool) $config->$type->$id;
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     * @return void
     */
    public static function enable ($type, $id) {
        $config = self::getConfig();
        if(!isset($config->$type)) {
            $config->$type = new Zend_Config(array(), true);
        }
        $config->$type->$id = true;
        self::setConfig($config);
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     * @return void
     */
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

    /**
     * @static
     * @throws Exception
     * @param  $id
     * @return array
     */
    public static function getPluginConfig ($id) {

        $pluginConfigs = self::getPluginConfigs();

        foreach ($pluginConfigs as $config) {
            if($config["plugin"]["pluginName"] == $id) {
                return $config;
            }
        }

        throw new Exception("Plugin with id: " . $id . " does not exists");
    }


    public static function delete ($id, $type) {
        if($type == "plugin") {
            $pluginDir = PIMCORE_PLUGINS_PATH . "/" . $id;
            if(is_writeable($pluginDir)) {
                recursiveDelete($pluginDir,true);
            }
        } else if ($type == "brick") {
            
        }
    }
}