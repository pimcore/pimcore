<?php
declare(strict_types=1);

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

use ArrayAccess;
use Exception;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config\ReportConfigWriter;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Tool\SettingsStore;
use function preg_replace;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\Yaml\Yaml;

final class Config implements ArrayAccess
{
    protected static array $configFileCache = [];

    protected static ?string $environment = null;

    protected static ?array $systemConfig = null;

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
        throw new Exception("modifying the config isn't allowed");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new Exception("modifying the config isn't allowed");
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
     * @param array|null $configuration
     * @param string|null $offset
     *
     * @internal ONLY FOR TESTING PURPOSES IF NEEDED FOR SPECIFIC TEST CASES
     */
    public static function setSystemConfiguration(?array $configuration, string $offset = null): void
    {
        if (null !== $offset) {
            self::getSystemConfiguration();
            static::$systemConfig[$offset] = $configuration;
        } else {
            static::$systemConfig = $configuration;
        }
    }

    /**
     * @param string|null $offset
     *
     * @return null|array
     *
     * @internal
     */
    public static function getSystemConfiguration(string $offset = null): ?array
    {
        if (null === static::$systemConfig && $container = Pimcore::getContainer()) {
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
     * @param string|null $languange
     *
     * @return string
     *
     * @internal
     */
    public static function getWebsiteConfigRuntimeCacheKey(string $languange = null): string
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
     * @return array
     */
    public static function getWebsiteConfig(string $language = null): array
    {
        if (RuntimeCache::isRegistered(self::getWebsiteConfigRuntimeCacheKey($language))) {
            $config = RuntimeCache::get(self::getWebsiteConfigRuntimeCacheKey($language));
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
                $originDocument = Pimcore::getContainer()->get('request_stack')->getMainRequest()->get(DynamicRouter::CONTENT_KEY);
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

            $config = Cache::load($cacheKey);
            if (!$config) {
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

                    if (!$itemLanguage && isset($config[$key])) {
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
                        $config[$key] = $s;
                    }
                }

                //TODO resolve for all langs, current lang first, then no lang
                Cache::save($config, $cacheKey, $cacheTags, null, 998);
            } elseif (is_array($config)) {
                foreach ($config as $key => $setting) {
                    if ($setting instanceof ElementInterface) {
                        $elementCacheKey = $setting->getCacheTag();
                        if (!RuntimeCache::isRegistered($elementCacheKey)) {
                            RuntimeCache::set($elementCacheKey, $setting);
                        }
                    }
                }
            }

            self::setWebsiteConfig($config, $language);
        }

        return $config;
    }

    /**
     * @param array|null $config
     * @param string|null $language
     *
     * @internal
     */
    public static function setWebsiteConfig(?array $config, string $language = null): void
    {
        RuntimeCache::set(self::getWebsiteConfigRuntimeCacheKey($language), $config);
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
    public static function getWebsiteConfigValue(string $key = null, mixed $default = null, string $language = null): mixed
    {
        $config = self::getWebsiteConfig($language);
        if (null !== $key) {
            return $config[$key] ?? $default;
        }

        return $config;
    }

    /**
     * @return array
     *
     * @throws Exception
     *
     * @internal
     *
     * @static
     */
    public static function getReportConfig(): array
    {
        $config = [];
        if (RuntimeCache::isRegistered('pimcore_config_report')) {
            $config = RuntimeCache::get('pimcore_config_report');
        } else {
            try {
                $configJson = SettingsStore::get(
                    ReportConfigWriter::REPORT_SETTING_ID, ReportConfigWriter::REPORT_SETTING_SCOPE
                );

                if ($configJson) {
                    $config = json_decode($configJson->getData(), true);
                }
            } catch (Exception $e) {
                // nothing to do
            }
        }

        self::setReportConfig($config);

        return $config;
    }

    /**
     * @static
     *
     * @param array $config
     *
     * @internal
     */
    public static function setReportConfig(array $config): void
    {
        RuntimeCache::set('pimcore_config_report', $config);
    }

    /**
     * @static
     *
     * @return array
     *
     * @internal
     */
    public static function getWeb2PrintConfig(): array
    {
        if (RuntimeCache::isRegistered('pimcore_config_web2print')) {
            $config = RuntimeCache::get('pimcore_config_web2print');
        } else {
            $config = Web2Print\Config::get();
            self::setWeb2PrintConfig($config);
        }

        return $config;
    }

    /**
     * @static
     *
     * @param array $config
     *
     * @internal
     */
    public static function setWeb2PrintConfig(array $config): void
    {
        RuntimeCache::set('pimcore_config_web2print', $config);
    }

    /**
     * @static
     *
     * @param array $config
     *
     * @internal
     */
    public static function setModelClassMappingConfig(array $config): void
    {
        RuntimeCache::set('pimcore_config_model_classmapping', $config);
    }

    /**
     * @param array $runtimeConfig
     * @param string $key
     *
     * @return bool
     *
     * @internal
     */
    public static function inPerspective(array $runtimeConfig, string $key): bool
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

    public static function getEnvironment(): string
    {
        return $_SERVER['APP_ENV'];
    }

    /**
     * @param string $file
     *
     * @return array
     *
     * @throws Exception
     *
     * @internal
     */
    public static function getConfigInstance(string $file): array
    {
        $fileType = pathinfo($file, PATHINFO_EXTENSION);
        if (file_exists($file)) {
            if ($fileType == 'yaml') {
                $content = Yaml::parseFile($file);
            } else {
                $content = include($file);
            }

            if (is_array($content)) {
                return $content;
            }
        } else {
            throw new Exception($file . " doesn't exist");
        }

        throw new Exception($file . ' is invalid');
    }
}
