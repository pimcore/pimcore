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

        // call enable.php inside the extension
        $extensionDir = self::getPathForExtension($id, $type);
        $enableScript = $extensionDir . "/enable.php";
        if(is_file($enableScript)) {
            include($enableScript);
        }
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

        // call disable.php inside the extension
        $extensionDir = self::getPathForExtension($id, $type);
        $disableScript = $extensionDir . "/disable.php";
        if(is_file($disableScript)) {
            include($disableScript);
        }
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
                            try {
                                $pluginConf = new Zend_Config_Xml(PIMCORE_PLUGINS_PATH . "/" . $d . "/plugin.xml");
                                if ($pluginConf != null) {
                                    $pluginConfigs[] = $pluginConf->toArray();
                                }
                            } catch (Exception $e) {
                                Logger::error("Unable to initialize plugin with ID: " . $d);
                                Logger::error($e);
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


    public static function getBrickDirectories () {
        $areas = array();
        try
        {
            $areas = Zend_Registry::get('brick_directories');
        }
        catch  (Exception $e)
        {
            $areaRepositories = array(
                PIMCORE_WEBSITE_PATH . "/views/areas",
            PIMCORE_WEBSITE_PATH . "/var/areas"
        );

        // get directories
        foreach ($areaRepositories as $respository) {

            if(is_dir($respository) && is_readable($respository)) {
                $blockDirs = scandir($respository);

                    foreach ($blockDirs as $blockDir) {
                        if(is_dir($respository . "/" . $blockDir)) {
                            if(is_file($respository . "/" . $blockDir . "/area.xml")) {
                                $areas[$blockDir] = $respository . "/" . $blockDir;
                            }
                        }
                    }
                }
            }
            Zend_Registry::set('brick_directories', $areas);
        }

        return $areas;
    }

    public static function getBrickConfigs() {

        try {
            $configs = Zend_Registry::get('brick_configs');
        } catch (Exception $e) {
            $configs = array();

            foreach (self::getBrickDirectories() as $areaName => $path) {
                try {
                    $configs[$areaName] = new Zend_Config_Xml($path . "/area.xml");
                } catch (Exception $e) {
                    Logger::error("Unable to initalize brick with id: " . $areaName);
                    Logger::error($e);
                }
            }

            Zend_Registry::set('brick_configs', $configs);
        }

        return $configs;
    }

    public static function getBrickConfig ($id) {

        $brickConfigs = self::getBrickConfigs();

        foreach ($brickConfigs as $brickId => $config) {
            if($brickId == $id) {
                return $config;
            }
        }

        throw new Exception("Areabrick with id: " . $id . " does not exists");
    }

    public static function delete ($id, $type) {
        if($type == "plugin") {
            $pluginDir = PIMCORE_PLUGINS_PATH . "/" . $id;
            if(is_writeable($pluginDir)) {
                recursiveDelete($pluginDir,true);
            }
        } else if ($type == "brick") {
            $brickDirs = Pimcore_ExtensionManager::getBrickDirectories();
            $brickDir = $brickDirs[$id];

            if(is_writeable($brickDir)) {
                recursiveDelete($brickDir,true);
            }
        }
    }

    public static function getPathForExtension($id, $type) {

        $extensionDir = "";

        if($type == "plugin") {
            $extensionDir = PIMCORE_PLUGINS_PATH . "/" . $id;
        } else if ($type == "brick") {
            $brickDirs = self::getBrickDirectories();
            $extensionDir = $brickDirs[$id];
        }

        return $extensionDir;
    }
}