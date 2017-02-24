<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Logger;

class ExtensionManager
{

    /**
     * @var \Pimcore\Config\Config
     */
    private static $config;

    /**
     * @static
     * @return \Pimcore\Config\Config
     */
    public static function getConfig()
    {
        if (!self::$config) {
            try {
                $file = \Pimcore\Config::locateConfigFile("extensions.php");
                if (file_exists($file)) {
                    self::$config = new \Pimcore\Config\Config(include($file), true);
                } else {
                    throw new \Exception($file . " doesn't exist");
                }
            } catch (\Exception $e) {
                self::$config = new \Pimcore\Config\Config([], true);
            }
        }

        return self::$config;
    }

    /**
     * @static
     * @param \Pimcore\Config\Config $config
     */
    public static function setConfig(\Pimcore\Config\Config $config)
    {
        self::$config = $config;

        $file = \Pimcore\Config::locateConfigFile("extensions.php");
        File::putPhpFile($file, to_php_data_file_format(self::$config->toArray()));
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     * @return bool
     */
    public static function isEnabled($type, $id)
    {
        $config = self::getConfig();

        if ($type == "brick") {
            // bricks are enabled per default
            if (!isset($config->brick->$id)) {
                return true;
            } else {
                return (bool) $config->$type->$id;
            }
        } else {
            // plugins (any maybe others) need to be explicitly enabled
            if ($config->$type) {
                return (bool) $config->$type->$id;
            }
        }

        return false;
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     */
    public static function enable($type, $id)
    {
        $config = self::getConfig();
        if (!isset($config->$type)) {
            $config->$type = new \Pimcore\Config\Config([], true);
        }
        $config->$type->$id = true;
        self::setConfig($config);

        // call enable.php inside the extension
        $extensionDir = self::getPathForExtension($id, $type);
        $enableScript = $extensionDir . "/enable.php";
        if (is_file($enableScript)) {
            include($enableScript);
        }
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     */
    public static function disable($type, $id)
    {
        $config = self::getConfig();
        if (!isset($config->$type)) {
            $config->$type = new \Pimcore\Config\Config([], true);
        }
        $config->$type->$id = false;
        self::setConfig($config);

        // call disable.php inside the extension
        $extensionDir = self::getPathForExtension($id, $type);
        $disableScript = $extensionDir . "/disable.php";
        if (is_file($disableScript)) {
            include($disableScript);
        }
    }


    /**
     * @return Array $pluginConfigs
     */
    public static function getPluginConfigs()
    {
        $pluginConfigs = [];

        if (is_dir(PIMCORE_PLUGINS_PATH) && is_readable(PIMCORE_PLUGINS_PATH)) {
            $pluginDirs = scandir(PIMCORE_PLUGINS_PATH);
            if (is_array($pluginDirs)) {
                foreach ($pluginDirs as $d) {
                    if ($d != "." and $d != ".." and is_dir(PIMCORE_PLUGINS_PATH . "//" . $d)) {
                        if (file_exists(PIMCORE_PLUGINS_PATH . "/" . $d . "/plugin.xml")) {
                            try {
                                $pluginConfArray = xmlToArray(PIMCORE_PLUGINS_PATH . "/" . $d . "/plugin.xml");
                                $pluginConf = new \Pimcore\Config\Config($pluginConfArray);
                                if ($pluginConf != null) {
                                    $pluginConfigs[] = $pluginConf->toArray();
                                }
                            } catch (\Exception $e) {
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
     * @param $id
     * @throws \Exception
     */
    public static function getPluginConfig($id)
    {
        $pluginConfigs = self::getPluginConfigs();

        foreach ($pluginConfigs as $config) {
            if ($config["plugin"]["pluginName"] == $id) {
                return $config;
            }
        }

        throw new \Exception("Plugin with id: " . $id . " does not exists");
    }

    /**
     * @param null $customPath
     * @return array|mixed
     */
    public static function getBrickDirectories($customPath = null)
    {
        $cacheKey = "brick_directories";
        if ($customPath) {
            $cacheKey .= "_" . crc32($customPath);
        }

        $areas = [];
        try {
            $areas = \Pimcore\Cache\Runtime::get($cacheKey);
        } catch (\Exception $e) {
            if ($customPath) {
                $areaRepositories = [$customPath];
            } else {
                $areaRepositories = [
                    PIMCORE_WEBSITE_PATH . "/views/areas",
                    PIMCORE_WEBSITE_VAR . "/areas"
                ];
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
                if (is_dir($respository) && is_readable($respository)) {
                    $blockDirs = scandir($respository);

                    foreach ($blockDirs as $blockDir) {
                        if (is_dir($respository . "/" . $blockDir)) {
                            if (is_file($respository . "/" . $blockDir . "/area.xml")) {
                                $areas[$blockDir] = $respository . "/" . $blockDir;
                            }
                        }
                    }
                }
            }
            \Pimcore\Cache\Runtime::set($cacheKey, $areas);
        }

        return $areas;
    }

    /**
     * @param null $customPath
     * @return array|mixed
     */
    public static function getBrickConfigs($customPath = null)
    {
        $cacheKey = "brick_configs";
        if ($customPath) {
            $cacheKey .= "_" . crc32($customPath);
        }

        try {
            $configs = \Pimcore\Cache\Runtime::get($cacheKey);
        } catch (\Exception $e) {
            $configs = [];

            foreach (self::getBrickDirectories($customPath) as $areaName => $path) {
                try {
                    $configArray = xmlToArray($path . "/area.xml");
                    $configs[$areaName] = new \Pimcore\Config\Config($configArray);
                } catch (\Exception $e) {
                    Logger::error("Unable to initalize brick with id: " . $areaName);
                    Logger::error($e);
                }
            }

            \Pimcore\Cache\Runtime::set($cacheKey, $configs);
        }

        return $configs;
    }

    /**
     * @param $id
     * @param $path
     * @throws \Exception
     *
     * @return mixed|array
     */
    public static function getBrickConfig($id, $path = null)
    {
        $brickConfigs = self::getBrickConfigs($path);

        foreach ($brickConfigs as $brickId => $config) {
            if ($brickId == $id) {
                return $config;
            }
        }

        throw new \Exception("Areabrick with id: " . $id . " does not exists");
    }

    /**
     * @param $id
     * @param $type
     */
    public static function delete($id, $type)
    {
        if ($type == "plugin") {
            $pluginDir = PIMCORE_PLUGINS_PATH . "/" . $id;
            if (is_writeable($pluginDir)) {
                recursiveDelete($pluginDir, true);
            }
        } elseif ($type == "brick") {
            $brickDirs = self::getBrickDirectories();
            $brickDir = $brickDirs[$id];

            if (is_writeable($brickDir)) {
                recursiveDelete($brickDir, true);
            }
        }
    }

    /**
     * @param $id
     * @param $type
     * @return string
     */
    public static function getPathForExtension($id, $type)
    {
        $extensionDir = "";

        if ($type == "plugin") {
            $extensionDir = PIMCORE_PLUGINS_PATH . "/" . $id;
        } elseif ($type == "brick") {
            $brickDirs = self::getBrickDirectories();
            $extensionDir = $brickDirs[$id];
        }

        return $extensionDir;
    }
}
