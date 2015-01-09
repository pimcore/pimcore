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

namespace Pimcore;

class ExtensionManager {

    /**
     * @var \Zend_Config
     */
    private static $config;

    /**
     * @static
     * @return \Zend_Config
     */
    public static function getConfig () {
        if(!self::$config) {
            try {
                self::$config = new \Zend_Config_Xml(PIMCORE_CONFIGURATION_DIRECTORY . "/extensions.xml", null, array("allowModifications" => true));
            }
            catch (\Exception $e) {
                self::$config = new \Zend_Config(array(), true);
            }
        }
        return self::$config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     * @return void
     */
    public static function setConfig (\Zend_Config $config) {

        self::$config = $config;

        $writer = new \Zend_Config_Writer_Xml(array(
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
        if($config->$type) {
            return (bool) $config->$type->$id;
        }
        return false;
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
            $config->$type = new \Zend_Config(array(), true);
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
            $config->$type = new \Zend_Config(array(), true);
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
                                $pluginConf = new \Zend_Config_Xml(PIMCORE_PLUGINS_PATH . "/" . $d . "/plugin.xml");
                                if ($pluginConf != null) {
                                    $pluginConfigs[] = $pluginConf->toArray();
                                }
                            } catch (\Exception $e) {
                                \Logger::error("Unable to initialize plugin with ID: " . $d);
                                \Logger::error($e);
                            }
                        }
                    }
                }
            }
        }
        return $pluginConfigs;
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public static function getPluginConfig ($id) {

        $pluginConfigs = self::getPluginConfigs();

        foreach ($pluginConfigs as $config) {
            if($config["plugin"]["pluginName"] == $id) {
                return $config;
            }
        }

        throw new \Exception("Plugin with id: " . $id . " does not exists");
    }

    /**
     * @param null $customPath
     * @return array|mixed
     */
    public static function getBrickDirectories ($customPath = null) {

        $cacheKey = "brick_directories";
        if($customPath) {
            $cacheKey .= "_" . crc32($customPath);
        }

        $areas = array();
        try
        {
            $areas = \Zend_Registry::get($cacheKey);
        }
        catch  (\Exception $e) {
            if($customPath) {
                $areaRepositories = array($customPath);
            } else {
                $areaRepositories = array(
                    PIMCORE_WEBSITE_PATH . "/views/areas",
                    PIMCORE_WEBSITE_VAR . "/areas"
                );
            }

            // include area repositories from active plugins
            $configs = ExtensionManager::getPluginConfigs();
            foreach ($configs as $config) {
                $className = $config["plugin"]["pluginClassName"];

                if (!empty($className)) {
                    $isEnabled = ExtensionManager::isEnabled("plugin", $config["plugin"]["pluginName"]);
                    $areaDir = PIMCORE_PLUGINS_PATH . "/" . $config["plugin"]["pluginName"] . "/views/areas";

                    if ($isEnabled && file_exists($areaDir)) {
                        $areaRepositories[] = $areaDir;
                    }
                }
            }

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
            \Zend_Registry::set($cacheKey, $areas);
        }

        return $areas;
    }

    /**
     * @param null $customPath
     * @return array|mixed
     */
    public static function getBrickConfigs($customPath = null) {

        $cacheKey = "brick_configs";
        if($customPath) {
            $cacheKey .= "_" . crc32($customPath);
        }

        try {
            $configs = \Zend_Registry::get($cacheKey);
        } catch (\Exception $e) {
            $configs = array();

            foreach (self::getBrickDirectories($customPath) as $areaName => $path) {
                try {
                    $configs[$areaName] = new \Zend_Config_Xml($path . "/area.xml");
                } catch (\Exception $e) {
                    \Logger::error("Unable to initalize brick with id: " . $areaName);
                    \Logger::error($e);
                }
            }

            \Zend_Registry::set($cacheKey, $configs);
        }

        return $configs;
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public static function getBrickConfig ($id) {

        $brickConfigs = self::getBrickConfigs();

        foreach ($brickConfigs as $brickId => $config) {
            if($brickId == $id) {
                return $config;
            }
        }

        throw new \Exception("Areabrick with id: " . $id . " does not exists");
    }

    /**
     * @param $id
     * @param $type
     */
    public static function delete ($id, $type) {
        if($type == "plugin") {
            $pluginDir = PIMCORE_PLUGINS_PATH . "/" . $id;
            if(is_writeable($pluginDir)) {
                recursiveDelete($pluginDir,true);
            }
        } else if ($type == "brick") {
            $brickDirs = self::getBrickDirectories();
            $brickDir = $brickDirs[$id];

            if(is_writeable($brickDir)) {
                recursiveDelete($brickDir,true);
            }
        }
    }

    /**
     * @param $id
     * @param $type
     * @return string
     */
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
