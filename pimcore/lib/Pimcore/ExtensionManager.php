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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Config;

/**
 * @deprecated Superseded by PimcoreBundleManager and AreabrickManager in Pimcore 5
 */
class ExtensionManager
{
    /**
     * @var array
     */
    private static $assetPaths;

    /**
     * @var array
     */
    private static $validTypes = ['plugin', 'brick'];

    /**
     * @static
     * @return Config\Config
     */
    public static function getConfig()
    {
        return \Pimcore::getContainer()->get('pimcore.extension.config')->loadConfig();
    }

    /**
     * @static
     * @param Config\Config $config
     */
    public static function setConfig(Config\Config $config)
    {
        return \Pimcore::getContainer()->get('pimcore.extension.config')->saveConfig($config);
    }

    /**
     * @param string $type
     */
    private static function validateType($type)
    {
        if (!in_array($type, static::$validTypes)) {
            throw new \InvalidArgumentException(sprintf('"%s" is no valid ExtensionManager type', $type));
        }
    }

    /**
     * @static
     * @param  $type
     * @param  $id
     * @return bool
     */
    public static function isEnabled($type, $id)
    {
        static::validateType($type);

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
        static::validateType($type);

        $config = self::getConfig();
        if (!isset($config->$type)) {
            $config->$type = new Config\Config([], true);
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
        static::validateType($type);

        $config = self::getConfig();
        if (!isset($config->$type)) {
            $config->$type = new Config\Config([], true);
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
     * @return array $pluginConfigs
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
                                $pluginConf = new Config\Config($pluginConfArray);
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
     *
     * @return array
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
     * @param string $type
     * @param bool $editmode
     *
     * @return array
     */
    public static function getAssetPaths($type, $editmode = false, $pluginName = null)
    {
        if (!in_array($type, ['js', 'css'])) {
            throw new \InvalidArgumentException('Invalid type');
        }

        $paths = static::loadAssetPaths();

        $key = $type;
        if ($editmode) {
            $key = 'editmode_' . $type;
        }

        if (null !== $pluginName) {
            if (isset($paths['plugin'][$pluginName]) && isset($paths['plugin'][$pluginName][$key])) {
                return $paths['plugin'][$pluginName][$key];
            }
        } else {
            if (isset($paths['merged'][$key])) {
                return $paths['merged'][$key];
            }
        }

        return [];
    }

    /**
     * @return array
     */
    protected static function loadAssetPaths()
    {
        if (null !== static::$assetPaths) {
            return static::$assetPaths;
        }

        $paths = [
            'plugin' => [],
            'merged' => []
        ];

        // don't try to load paths if no legacy mode is available
        if (!\Pimcore::isLegacyModeAvailable()) {
            return $paths;
        }

        foreach (static::getPluginConfigs() as $pluginConfig) {
            $pluginName = $pluginConfig['plugin']['pluginName'];
            if (!static::isEnabled('plugin', $pluginName)) {
                // continue;
            }

            $paths['plugin'][$pluginName] = [
                'js'           => static::resolveAssetPaths($pluginConfig['plugin'], 'js'),
                'css'          => static::resolveAssetPaths($pluginConfig['plugin'], 'css'),
                'editmode_js'  => static::resolveAssetPaths($pluginConfig['plugin'], 'js', 'editmode'),
                'editmode_css' => static::resolveAssetPaths($pluginConfig['plugin'], 'css', 'editmode'),
            ];
        }

        $merged = [];
        foreach ($paths['plugin'] as $pluginName => $pluginPaths) {
            $merged = array_merge($merged, $pluginPaths);
        }

        $paths['merged'] = $merged;

        static::$assetPaths = $paths;

        return $paths;
    }

    /**
     * @param array       $pluginConfig
     * @param string      $type
     * @param null|string $mode
     *
     * @return array
     */
    protected static function resolveAssetPaths(array $pluginConfig, $type, $mode = null)
    {
        $paths = [];

        $modePrefix = '';
        if ('editmode' === $mode) {
            $modePrefix = 'DocumentEditmode';
        }

        $versions = ['-extjs6', ''];
        foreach ($versions as $versionSuffix) {
            // pluginCssPaths-extjs6
            // pluginDocumentEditmodeJsPaths
            $key = sprintf('plugin%s%sPaths%s', $modePrefix, ucfirst($type), $versionSuffix);

            if (array_key_exists($key, $pluginConfig) && is_array($pluginConfig[$key]) && isset($pluginConfig[$key]['path'])) {
                if (is_array($pluginConfig[$key]['path'])) {
                    $paths = $pluginConfig[$key]['path'];
                    break;
                } elseif (null !== $pluginConfig[$key]['path']) {
                    $paths[]  = $pluginConfig[$key]['path'];
                    break;
                }
            }
        }

        $result = [];
        if (count($paths) > 0) {
            for ($i = 0; $i < count($paths); $i++) {
                if (is_file(PIMCORE_PLUGINS_PATH . $paths[$i])) {
                    $result[] = '/plugins' . $paths[$i];
                }
            }
        }

        return $result;
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
                    $configs[$areaName] = new Config\Config($configArray);
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
        static::validateType($type);

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
        static::validateType($type);

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
