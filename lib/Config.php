<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore;

use Pimcore\Cache\Runtime;
use Pimcore\Config\Config as PimcoreConfig;
use Pimcore\Config\ReportConfigWriter;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\Yaml\Yaml;

final class Config implements \ArrayAccess
{
    /**
     * @var array
     */
    protected static $configFileCache = [];

    /**
     * @var string
     */
    protected static $environment = null;

    /**
     * @var array|null
     */
    protected static $systemConfig = null;

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return self::getSystemConfiguration($offset) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new \Exception("modifying the config isn't allowed");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new \Exception("modifying the config isn't allowed");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): ?array
    {
        return self::getSystemConfiguration($offset);
    }

    /**
     * @internal
     *
     * @param string $name - name of configuration file. slash is allowed for subdirectories.
     *
     * @return string
     */
    public static function locateConfigFile(string $name): string
    {
        if (!isset(self::$configFileCache[$name])) {
            $pathsToCheck = [
                PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY,
                PIMCORE_CONFIGURATION_DIRECTORY,
            ];
            $file = null;

            // check for environment configuration
            $env = self::getEnvironment();
            if ($env) {
                $fileExt = File::getFileExtension($name);
                $pureName = str_replace('.' . $fileExt, '', $name);
                foreach ($pathsToCheck as $path) {
                    $tmpFile = $path . '/' . $pureName . '_' . $env . '.' . $fileExt;
                    if (file_exists($tmpFile)) {
                        $file = $tmpFile;

                        break;
                    }
                }
            }

            //check for config file without environment configuration
            if (!$file) {
                foreach ($pathsToCheck as $path) {
                    $tmpFile = $path . '/' . $name;
                    if (file_exists($tmpFile)) {
                        $file = $tmpFile;

                        break;
                    }
                }
            }

            //get default path in pimcore configuration directory
            if (!$file) {
                $file = PIMCORE_CONFIGURATION_DIRECTORY . '/' . $name;
            }

            self::$configFileCache[$name] = $file;
        }

        return self::$configFileCache[$name];
    }

    /**
     * @internal ONLY FOR TESTING PURPOSES IF NEEDED FOR SPECIFIC TEST CASES
     *
     * @param null|array $configuration
     * @param null|mixed $offset
     */
    public static function setSystemConfiguration($configuration, $offset = null)
    {
        if (null !== $offset) {
            self::getSystemConfiguration();
            static::$systemConfig[$offset] = $configuration;
        } else {
            static::$systemConfig = $configuration;
        }
    }

    /**
     * @internal
     *
     * @param null|mixed $offset
     *
     * @return null|array
     */
    public static function getSystemConfiguration($offset = null)
    {
        if (null === static::$systemConfig && $container = \Pimcore::getContainer()) {
            $config = $container->getParameter('pimcore.config');
            $adminConfig = $container->getParameter('pimcore_admin.config');

            static::$systemConfig = array_merge_recursive($config, $adminConfig);
        }

        if (null !== $offset) {
            return static::$systemConfig[$offset] ?? null;
        }

        return static::$systemConfig;
    }

    /**
     * @internal
     *
     * @param string|null $languange
     *
     * @return string
     */
    public static function getWebsiteConfigRuntimeCacheKey($languange = null)
    {
        $cacheKey = 'pimcore_config_website';
        if ($languange) {
            $cacheKey .= '_' . $languange;
        }

        return $cacheKey;
    }

    /**
     * @static
     *
     * @param string|null $language
     *
     * @return PimcoreConfig
     */
    public static function getWebsiteConfig($language = null)
    {
        if (\Pimcore\Cache\Runtime::isRegistered(self::getWebsiteConfigRuntimeCacheKey($language))) {
            $config = \Pimcore\Cache\Runtime::get(self::getWebsiteConfigRuntimeCacheKey($language));
        } else {
            $cacheKey = 'website_config';
            if ($language) {
                $cacheKey .= '_' . $language;
            }

            $siteId = null;
            if (Model\Site::isSiteRequest()) {
                $siteId = Model\Site::getCurrentSite()->getId();
            } elseif (Tool::isFrontendRequestByAdmin()) {
                // this is necessary to set the correct settings in editmode/preview (using the main domain)
                // we cannot use the document resolver service here, because we need the document on the master request
                $originDocument = \Pimcore::getContainer()->get('request_stack')->getMainRequest()->get(DynamicRouter::CONTENT_KEY);
                if ($originDocument) {
                    $site = Tool\Frontend::getSiteForDocument($originDocument);
                    if ($site) {
                        $siteId = $site->getId();
                    }
                }
            }

            if ($siteId) {
                $cacheKey = $cacheKey . '_site_' . $siteId;
            }

            /** @var PimcoreConfig|null $config */
            $config = Cache::load($cacheKey);
            if (!$config) {
                $settingsArray = [];
                $cacheTags = ['website_config', 'system', 'config', 'output'];

                $list = new Model\WebsiteSetting\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    $itemSiteId = $item->getSiteId();

                    if ($itemSiteId && $itemSiteId !== $siteId) {
                        continue;
                    }

                    $itemLanguage = $item->getLanguage();

                    if ($itemLanguage && $language !== $itemLanguage) {
                        continue;
                    }

                    $key = $item->getName();

                    if (!$itemLanguage && isset($settingsArray[$key])) {
                        continue;
                    }

                    switch ($item->getType()) {
                        case 'document':
                        case 'asset':
                        case 'object':
                            $s = $item->getData();

                            break;
                        case 'bool':
                            $s = (bool) $item->getData();

                            break;
                        case 'text':
                            $s = (string) $item->getData();

                            break;
                        default:
                            $s = null;

                            break;
                    }

                    if ($s instanceof Model\Element\ElementInterface) {
                        $elementCacheKey = $s->getCacheTag();
                        $cacheTags[$elementCacheKey] = $elementCacheKey;
                    }

                    if (isset($s)) {
                        $settingsArray[$key] = $s;
                    }
                }

                //TODO resolve for all langs, current lang first, then no lang
                $config = new PimcoreConfig($settingsArray, true);

                Cache::save($config, $cacheKey, $cacheTags, null, 998);
            } elseif ($config instanceof PimcoreConfig) {
                $data = $config->toArray();
                foreach ($data as $key => $setting) {
                    if ($setting instanceof ElementInterface) {
                        $elementCacheKey = $setting->getCacheTag();
                        if (!Runtime::isRegistered($elementCacheKey)) {
                            Runtime::set($elementCacheKey, $setting);
                        }
                    }
                }
            }

            self::setWebsiteConfig($config, $language);
        }

        return $config;
    }

    /**
     * @internal
     *
     * @param Config\Config|null $config
     * @param string|null $language
     */
    public static function setWebsiteConfig(?PimcoreConfig $config, $language = null)
    {
        \Pimcore\Cache\Runtime::set(self::getWebsiteConfigRuntimeCacheKey($language), $config);
    }

    /**
     * Returns whole website config or only a given setting for the current site
     *
     * @param string|null $key  Config key to directly load. If null, the whole config will be returned
     * @param mixed $default    Default value to use if the key is not set
     * @param string|null $language
     *
     * @return mixed
     */
    public static function getWebsiteConfigValue($key = null, $default = null, $language = null)
    {
        $config = self::getWebsiteConfig($language);
        if (null !== $key) {
            return $config->get($key, $default);
        }

        return $config;
    }

    /**
     * @return PimcoreConfig
     *
     * @throws \Exception
     *
     * @internal
     * @static
     */
    public static function getReportConfig(): PimcoreConfig
    {
        $config = null;
        if (Runtime::isRegistered('pimcore_config_report')) {
            $config = Runtime::get('pimcore_config_report');
        } else {
            try {
                $configJson = SettingsStore::get(
                    ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE
                );

                if ($configJson) {
                    $configArray = json_decode($configJson->getData(), true);
                    $config = new PimcoreConfig($configArray);
                }
            } catch (\Exception $e) {
                // nothing to do
            }
        }

        if (!$config) {
            $config = new PimcoreConfig([]);
        }

        self::setReportConfig($config);

        return $config;
    }

    /**
     * @static
     *
     * @param PimcoreConfig $config
     *
     * @internal
     */
    public static function setReportConfig(PimcoreConfig $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_report', $config);
    }

    /**
     * @static
     *
     * @return PimcoreConfig
     *
     * @internal
     */
    public static function getRobotsConfig()
    {
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_robots')) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_robots');
        } else {
            try {
                $settingsStoreScope = 'robots.txt';
                $configData = [];
                $robotsSettingsIds = SettingsStore::getIdsByScope($settingsStoreScope);
                foreach ($robotsSettingsIds as $id) {
                    $robots = SettingsStore::get($id, $settingsStoreScope);
                    $siteId = \preg_replace('/^robots\.txt\-/', '', $robots->getId());
                    $configData[$siteId] = $robots->getData();
                }

                $config = new PimcoreConfig($configData);
            } catch (\Exception $e) {
                $config = new PimcoreConfig([]);
            }

            self::setRobotsConfig($config);
        }

        return $config;
    }

    /**
     * @static
     *
     * @param PimcoreConfig $config
     *
     * @internal
     */
    public static function setRobotsConfig(PimcoreConfig $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_robots', $config);
    }

    /**
     * @static
     *
     * @return PimcoreConfig
     *
     * @internal
     */
    public static function getWeb2PrintConfig()
    {
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_web2print')) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_web2print');
        } else {
            $config = \Pimcore\Web2Print\Config::get();
            self::setWeb2PrintConfig($config);
        }

        return $config;
    }

    /**
     * @static
     *
     * @param PimcoreConfig $config
     *
     * @internal
     */
    public static function setWeb2PrintConfig(PimcoreConfig $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_web2print', $config);
    }

    /**
     * @static
     *
     * @param PimcoreConfig $config
     *
     * @internal
     */
    public static function setModelClassMappingConfig($config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_model_classmapping', $config);
    }

    /**
     * @internal
     *
     * @param array $runtimeConfig
     * @param string $key
     *
     * @return bool
     */
    public static function inPerspective($runtimeConfig, $key)
    {
        if (!isset($runtimeConfig['toolbar']) || !$runtimeConfig['toolbar']) {
            return true;
        }
        $parts = explode('.', $key);
        $menuItems = $runtimeConfig['toolbar'];

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            if (!isset($menuItems[$part])) {
                break;
            }

            $menuItem = $menuItems[$part];

            if (is_array($menuItem)) {
                if (isset($menuItem['hidden']) && $menuItem['hidden']) {
                    return false;
                }

                if (!($menuItem['items'] ?? null)) {
                    break;
                }
                $menuItems = $menuItem['items'];
            } else {
                return $menuItem;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public static function getEnvironment(): string
    {
        return $_SERVER['APP_ENV'];
    }

    /**
     * @internal
     *
     * @param string $file
     * @param bool $asArray
     *
     * @return Config\Config|array
     *
     * @throws \Exception
     */
    public static function getConfigInstance($file, bool $asArray = false)
    {
        $fileType = pathinfo($file, PATHINFO_EXTENSION);
        if (file_exists($file)) {
            if ($fileType == 'yml') {
                $content = Yaml::parseFile($file);
            } else {
                $content = include($file);
            }

            if (is_array($content)) {
                if ($asArray) {
                    return $content;
                }

                return new PimcoreConfig($content);
            }
        } else {
            throw new \Exception($file . " doesn't exist");
        }

        throw new \Exception($file . ' is invalid');
    }
}
