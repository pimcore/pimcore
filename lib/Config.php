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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Cache\Runtime;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Model\User\UserRole;
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
     * @see    ArrayAccess::offsetExists()
     *
     * @param  mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return self::getSystemConfiguration($offset) !== null;
    }

    /**
     * @see    ArrayAccess::offsetSet()
     *
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception("modifying the config isn't allowed");
    }

    /**
     * @see    ArrayAccess::offsetUnset()
     *
     * @param  mixed $offset
     *
     * @throws \Exception
     */
    public function offsetUnset($offset)
    {
        throw new \Exception("modifying the config isn't allowed");
    }

    /**
     * @param string $offset
     *
     * @return array|null
     */
    public function offsetGet($offset)
    {
        return self::getSystemConfiguration($offset);
    }

    /**
     * @internal
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
     * @return mixed|\Pimcore\Config\Config
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
                $originDocument = \Pimcore::getContainer()->get('request_stack')->getMasterRequest()->get(DynamicRouter::CONTENT_KEY);
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

            /** @var \Pimcore\Config\Config|null $config */
            $config = Cache::load($cacheKey);
            if (!$config) {
                $settingsArray = [];
                $cacheTags = ['website_config', 'system', 'config', 'output'];

                $list = new Model\WebsiteSetting\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    $itemSiteId = $item->getSiteId();

                    if ($itemSiteId !== 0 && $itemSiteId !== $siteId) {
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
                        $cacheTags = $s->getCacheTags($cacheTags);
                    }

                    if (isset($s)) {
                        $settingsArray[$key] = $s;
                    }
                }

                //TODO resolve for all langs, current lang first, then no lang
                $config = new \Pimcore\Config\Config($settingsArray, true);

                Cache::save($config, $cacheKey, $cacheTags, null, 998);
            } else {
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
     * @param Config\Config $config
     * @param string|null $language
     */
    public static function setWebsiteConfig(\Pimcore\Config\Config $config, $language = null)
    {
        \Pimcore\Cache\Runtime::set(self::getWebsiteConfigRuntimeCacheKey($language), $config);
    }

    /**
     * Returns whole website config or only a given setting for the current site
     *
     * @param null|mixed $key       Config key to directly load. If null, the whole config will be returned
     * @param null|mixed $default   Default value to use if the key is not set
     * @param null|string $language   Language
     *
     * @return Config\Config|mixed
     */
    public static function getWebsiteConfigValue($key = null, $default = null, $language = null)
    {
        $config = self::getWebsiteConfig($language);
        if (null !== $key) {
            return $config->get($key, $default);
        }

        return $config;
    }

    private static function getArrayValue($keys, $array)
    {
        $len = count($keys);
        $pointer = $array;
        for ($i = 0; $i < $len; $i++) {
            $key = $keys[$i];
            if (array_key_exists($key, $pointer)) {
                $pointer = $pointer[$key];
            } else {
                return null;
            }
        }

        return $pointer;
    }

    /**
     * @internal
     * @static
     *
     * @return \Pimcore\Config\Config
     */
    public static function getReportConfig()
    {
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_report')) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_report');
        } else {
            try {
                $file = self::locateConfigFile('reports.php');
                $config = static::getConfigInstance($file);
            } catch (\Exception $e) {
                $config = new \Pimcore\Config\Config([]);
            }

            self::setReportConfig($config);
        }

        return $config;
    }

    /**
     * @internal
     * @static
     *
     * @param \Pimcore\Config\Config $config
     */
    public static function setReportConfig(\Pimcore\Config\Config $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_report', $config);
    }

    /**
     * @static
     *
     * @return \Pimcore\Config\Config
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

                $config = new \Pimcore\Config\Config($configData);
            } catch (\Exception $e) {
                $config = new \Pimcore\Config\Config([]);
            }

            self::setRobotsConfig($config);
        }

        return $config;
    }

    /**
     * @static
     *
     * @param \Pimcore\Config\Config $config
     *
     * @internal
     */
    public static function setRobotsConfig(\Pimcore\Config\Config $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_robots', $config);
    }

    /**
     * @internal
     * @static
     *
     * @return \Pimcore\Config\Config
     */
    public static function getWeb2PrintConfig()
    {
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_web2print')) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_web2print');
        } else {
            try {
                $file = self::locateConfigFile('web2print.php');
                $config = static::getConfigInstance($file);
            } catch (\Exception $e) {
                $config = new \Pimcore\Config\Config([]);
            }

            self::setWeb2PrintConfig($config);
        }

        return $config;
    }

    /**
     * @internal
     * @static
     *
     * @param \Pimcore\Config\Config $config
     */
    public static function setWeb2PrintConfig(\Pimcore\Config\Config $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_web2print', $config);
    }

    /**
     * @internal
     * @static
     *
     * @param \Pimcore\Config\Config $config
     */
    public static function setModelClassMappingConfig($config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_model_classmapping', $config);
    }

    /**
     * @internal
     * @static
     *
     * @return mixed|\Pimcore\Config\Config
     */
    public static function getPerspectivesConfig()
    {
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_perspectives')) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_perspectives');
        } else {
            $file = self::locateConfigFile('perspectives.php');
            try {
                $config = static::getConfigInstance($file);
                self::setPerspectivesConfig($config);
            } catch (\Exception $e) {
                Logger::info('Cannot find perspectives configuration, should be located at: ' . $file);
                if (is_file($file)) {
                    $m = 'Your perspectives.php located at ' . $file . ' is invalid, please check and correct it manually!';
                    Tool::exitWithError($m);
                }
                $config = new \Pimcore\Config\Config(self::getStandardPerspective());
                self::setPerspectivesConfig($config);
            }
        }

        return $config;
    }

    /**
     * @internal
     *
     * @return array
     */
    public static function getStandardPerspective()
    {
        $elementTree = [
            [
                'type' => 'documents',
                'position' => 'left',
                'expanded' => false,
                'hidden' => false,
                'sort' => -3,
                'treeContextMenu' => [
                    'document' => [
                        'items' => [
                            'addPrintPage' => self::getWeb2PrintConfig()->get('enableInDefaultView') ? true : false, // hide add print documents by default
                        ],
                    ],
                ],
            ],
            [
                'type' => 'assets',
                'position' => 'left',
                'expanded' => false,
                'hidden' => false,
                'sort' => -2,
            ],
            [
                'type' => 'objects',
                'position' => 'left',
                'expanded' => false,
                'hidden' => false,
                'sort' => -1,
            ],
        ];

        $cvConfigs = Tool::getCustomViewConfig();
        if ($cvConfigs) {
            foreach ($cvConfigs as $cvConfig) {
                $cvConfig['type'] = 'customview';
                $elementTree[] = $cvConfig;
            }
        }

        return [
            'default' => [
                'iconCls' => 'pimcore_nav_icon_perspective',
                'elementTree' => $elementTree,
                'dashboards' => [
                    'predefined' => [
                        'welcome' => [
                            'positions' => [
                                [
                                    [
                                        'id' => 1,
                                        'type' => 'pimcore.layout.portlets.modificationStatistic',
                                        'config' => null,
                                    ],
                                    [
                                        'id' => 2,
                                        'type' => 'pimcore.layout.portlets.modifiedAssets',
                                        'config' => null,
                                    ],
                                ],
                                [
                                    [
                                        'id' => 3,
                                        'type' => 'pimcore.layout.portlets.modifiedObjects',
                                        'config' => null,
                                    ],
                                    [
                                        'id' => 4,
                                        'type' => 'pimcore.layout.portlets.modifiedDocuments',
                                        'config' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @internal
     *
     * @param Model\User $currentUser
     *
     * @return array
     */
    public static function getRuntimePerspective(Model\User $currentUser = null)
    {
        if (null === $currentUser) {
            $currentUser = Tool\Admin::getCurrentUser();
        }

        $currentConfigName = $currentUser->getActivePerspective() ? $currentUser->getActivePerspective() : $currentUser->getFirstAllowedPerspective();

        $config = self::getPerspectivesConfig()->toArray();
        $result = [];

        if (isset($config[$currentConfigName])) {
            $result = $config[$currentConfigName];
        } else {
            $availablePerspectives = self::getAvailablePerspectives($currentUser);
            if ($availablePerspectives) {
                $currentPerspective = reset($availablePerspectives);
                $currentConfigName = $currentPerspective['name'];
                if ($currentConfigName && $config[$currentConfigName]) {
                    $result = $config[$currentConfigName];
                }
            }
        }

        if ($result && $currentConfigName != $currentUser->getActivePerspective()) {
            $currentUser->setActivePerspective($currentConfigName);
            $currentUser->save();
        }

        $result['elementTree'] = self::getRuntimeElementTreeConfig($currentConfigName);

        return $result;
    }

    /**
     * @internal
     *
     * @param string $name
     *
     * @return array
     */
    protected static function getRuntimeElementTreeConfig($name)
    {
        $masterConfig = self::getPerspectivesConfig()->toArray();

        $config = $masterConfig[$name];
        if (!$config) {
            $config = [];
        }

        $tmpResult = $config['elementTree'];
        if (is_null($tmpResult)) {
            $tmpResult = [];
        }
        $result = [];

        $cfConfigMapping = [];

        $cvConfigs = Tool::getCustomViewConfig();

        if ($cvConfigs) {
            foreach ($cvConfigs as $node) {
                $tmpData = $node;
                if (!isset($tmpData['id'])) {
                    Logger::error('custom view ID is missing ' . var_export($tmpData, true));
                    continue;
                }

                if (!empty($tmpData['hidden'])) {
                    continue;
                }

                // backwards compatibility
                $treeType = $tmpData['treetype'] ? $tmpData['treetype'] : 'object';
                $rootNode = Model\Element\Service::getElementByPath($treeType, $tmpData['rootfolder']);

                if ($rootNode) {
                    $tmpData['type'] = 'customview';
                    $tmpData['rootId'] = $rootNode->getId();
                    $tmpData['allowedClasses'] = $tmpData['classes'] ?? null;
                    $tmpData['showroot'] = (bool)$tmpData['showroot'];
                    $customViewId = $tmpData['id'];
                    $cfConfigMapping[$customViewId] = $tmpData;
                }
            }
        }

        foreach ($tmpResult as $resultItem) {
            if (!empty($resultItem['hidden'])) {
                continue;
            }

            if ($resultItem['type'] == 'customview') {
                $customViewId = $resultItem['id'];
                if (!$customViewId) {
                    Logger::error('custom view id missing ' . var_export($resultItem, true));
                    continue;
                }
                $customViewCfg = isset($cfConfigMapping[$customViewId]) ? $cfConfigMapping[$customViewId] : null;
                if (!$customViewCfg) {
                    Logger::error('no custom view config for id  ' . $customViewId);
                    continue;
                }

                foreach ($resultItem as $specificConfigKey => $specificConfigValue) {
                    $customViewCfg[$specificConfigKey] = $specificConfigValue;
                }
                $result[] = $customViewCfg;
            } else {
                $result[] = $resultItem;
            }
        }

        usort($result, static function ($treeA, $treeB) {
            $a = $treeA['sort'] ?: 0;
            $b = $treeB['sort'] ?: 0;

            return $a <=> $b;
        });

        return $result;
    }

    /**
     * @internal
     * @static
     *
     * @param \Pimcore\Config\Config $config
     */
    public static function setPerspectivesConfig(\Pimcore\Config\Config $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_perspectives', $config);
    }

    /**
     * @internal
     *
     * @param Model\User|null $user
     *
     * @return array
     */
    public static function getAvailablePerspectives($user)
    {
        $currentConfigName = null;
        $masterConfig = self::getPerspectivesConfig()->toArray();

        if ($user instanceof  Model\User) {
            if ($user->isAdmin()) {
                $config = self::getPerspectivesConfig()->toArray();
            } else {
                $config = [];
                $roleIds = $user->getRoles();
                $userIds = [$user->getId()];
                $userIds = array_merge($userIds, $roleIds);

                foreach ($userIds as $userId) {
                    if (in_array($userId, $roleIds)) {
                        $userOrRoleToCheck = Model\User\Role::getById($userId);
                    } else {
                        $userOrRoleToCheck = Model\User::getById($userId);
                    }
                    if ($userOrRoleToCheck instanceof UserRole) {
                        $perspectives = $userOrRoleToCheck->getPerspectives();
                        if ($perspectives) {
                            foreach ($perspectives as $perspectiveName) {
                                $masterDef = $masterConfig[$perspectiveName] ?? null;
                                if ($masterDef) {
                                    $config[$perspectiveName] = $masterDef;
                                }
                            }
                        }
                    }
                }
                if (!$config) {
                    $config = self::getPerspectivesConfig()->toArray();
                }
            }

            if ($config) {
                $tmpConfig = [];
                $validPerspectiveNames = array_keys($config);

                // sort the stuff
                foreach ($masterConfig as $masterConfigName => $masterConfiguration) {
                    if (in_array($masterConfigName, $validPerspectiveNames)) {
                        $tmpConfig[$masterConfigName] = $masterConfiguration;
                    }
                }
                $config = $tmpConfig;
            }

            $currentConfigName = $user->getActivePerspective();
            if ($config && !in_array($currentConfigName, array_keys($config))) {
                $configNames = array_keys($config);
                $currentConfigName = reset($configNames);
            }
        } else {
            $config = self::getPerspectivesConfig()->toArray();
        }

        $result = [];

        foreach ($config as $configName => $configItem) {
            $item = [
                'name' => $configName,
                'icon' => isset($configItem['icon']) ? $configItem['icon'] : null,
                'iconCls' => isset($configItem['iconCls']) ? $configItem['iconCls'] : null,
            ];
            if ($user) {
                $item['active'] = $configName == $currentConfigName;
            }

            $result[] = $item;
        }

        return $result;
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
     * @return null|Config\Config|array
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

                return new \Pimcore\Config\Config($content);
            }
        } else {
            throw new \Exception($file . " doesn't exist");
        }

        throw new \Exception($file . ' is invalid');
    }
}
