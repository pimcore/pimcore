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

use Pimcore\Cache\Runtime;
use Pimcore\Config\EnvironmentConfig;
use Pimcore\Config\EnvironmentConfigInterface;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\User\UserRole;
use Pimcore\Model\WebsiteSetting;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\Yaml\Yaml;

class Config implements \ArrayAccess
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
     * @var EnvironmentConfigInterface
     */
    private static $environmentConfig;

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
     * @param string $name - name of configuration file. slash is allowed for subdirectories.
     *
     * @return mixed
     */
    public static function locateConfigFile($name)
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

            //add email settings
            foreach (['email' => 'pimcore_mailer', 'newsletter' => 'newsletter_mailer'] as $key => $group) {
                if ($container->hasParameter('swiftmailer.mailer.'.$group.'.transport.smtp.host')) {
                    $config[$key]['smtp'] = [
                        'host' => $container->getParameter('swiftmailer.mailer.' . $group . '.transport.smtp.host'),
                        'username' => $container->getParameter('swiftmailer.mailer.' . $group . '.transport.smtp.username'),
                        'password' => $container->getParameter('swiftmailer.mailer.' . $group . '.transport.smtp.password'),
                        'port' => $container->getParameter('swiftmailer.mailer.' . $group . '.transport.smtp.port'),
                        'encryption' => $container->getParameter('swiftmailer.mailer.' . $group . '.transport.smtp.encryption'),
                        'auth_mode' => $container->getParameter('swiftmailer.mailer.' . $group . '.transport.smtp.auth_mode'),
                    ];
                }
            }

            static::$systemConfig = array_merge_recursive($config, $adminConfig);
        }

        if (null !== $offset) {
            return static::$systemConfig[$offset] ?? null;
        }

        return static::$systemConfig;
    }

    /**
     * @internal
     * @static
     *
     * @param \Pimcore\Config\Config $config
     */
    public static function setSystemConfig(\Pimcore\Config\Config $config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_system', $config);
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

            /** @var \Pimcore\Config\Config $config */
            $config = Cache::load($cacheKey);
            if (!$config) {
                $settingsArray = [];
                $cacheTags = ['website_config', 'system', 'config', 'output'];

                $list = new Model\WebsiteSetting\Listing();
                $list = $list->load();

                /** @var WebsiteSetting $item */
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
                    if ($setting instanceof AbstractElement) {
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
     * @param array $config
     *
     * @return array|Config\Config
     */
    private static function mapLegacyConfiguration($config)
    {
        $systemConfig = [];

        if (is_array($config)) {
            //legacy system configuration mapping
            $systemConfig = new \Pimcore\Config\Config([
                'general' => [
                    'timezone' => self::getArrayValue(['general', 'timezone'], $config),
                    'path_variable' => self::getArrayValue(['general', 'path_variable'], $config),
                    'domain' => self::getArrayValue(['general', 'domain'], $config),
                    'redirect_to_maindomain' => self::getArrayValue(['general', 'redirect_to_maindomain'], $config),
                    'language' => self::getArrayValue(['general', 'language'], $config),
                    'validLanguages' => self::getArrayValue(['general', 'valid_languages'], $config),
                    'fallbackLanguages' => self::getArrayValue(['general', 'fallback_languages'], $config),
                    'defaultLanguage' => self::getArrayValue(['general', 'default_language'], $config),
                    'loginscreencustomimage' => self::getArrayValue(['branding', 'login_screen_custom_image'], $config),
                    'disableusagestatistics' => self::getArrayValue(['general', 'disable_usage_statistics'], $config),
                    'debug_admin_translations' => self::getArrayValue(['general', 'debug_admin_translations'], $config),
                    'instanceIdentifier' => self::getArrayValue(['general', 'instance_identifier'], $config),
                    'show_cookie_notice' => self::getArrayValue(['general', 'show_cookie_notice'], $config),
                ],
                'documents' => [
                    'versions' => [
                        'days' => self::getArrayValue(['documents', 'versions', 'days'], $config),
                        'steps' => self::getArrayValue(['documents', 'versions', 'steps'], $config),
                    ],
                    'error_pages' => self::getArrayValue(['documents', 'error_pages'], $config),
                    'allowtrailingslash' => self::getArrayValue(['documents', 'allow_trailing_slash'], $config),
                    'generatepreview' => self::getArrayValue(['documents', 'generate_preview'], $config),
                ],
                'objects' => [
                    'versions' => [
                        'days' => self::getArrayValue(['objects', 'versions', 'days'], $config),
                        'steps' => self::getArrayValue(['objects', 'versions', 'steps'], $config),
                    ],
                ],
                'assets' => [
                    'versions' => [
                        'days' => self::getArrayValue(['assets', 'versions', 'days'], $config),
                        'steps' => self::getArrayValue(['assets', 'versions', 'steps'], $config),
                    ],
                    'icc_rgb_profile' => self::getArrayValue(['assets', 'icc_rgb_profile'], $config),
                    'icc_cmyk_profile' => self::getArrayValue(['assets', 'icc_cmyk_profile'], $config),
                    'hide_edit_image' => self::getArrayValue(['assets', 'hide_edit_image'], $config),
                    'disable_tree_preview' => self::getArrayValue(['assets', 'disable_tree_preview'], $config),
                ],
                'services' => [
                    'google' => [
                        'client_id' => self::getArrayValue(['services', 'google', 'client_id'], $config),
                        'email' => self::getArrayValue(['services', 'google', 'email'], $config),
                        'simpleapikey' => self::getArrayValue(['services', 'google', 'simple_api_key'], $config),
                        'browserapikey' => self::getArrayValue(['services', 'google', 'browser_api_key'], $config),
                    ],
                ],
                'full_page_cache' => [
                    'enabled' => self::getArrayValue(['full_page_cache', 'enabled'], $config),
                    'lifetime' => self::getArrayValue(['full_page_cache', 'lifetime'], $config),
                    'excludePatterns' => self::getArrayValue(['full_page_cache', 'exclude_patterns'], $config),
                    'excludeCookie' => self::getArrayValue(['full_page_cache', 'exclude_cookie'], $config),
                ],
                'webservice' => [
                    'enabled' => self::getArrayValue(['webservice', 'enabled'], $config),
                ],
                'httpclient' => [
                    'adapter' => self::getArrayValue(['httpclient', 'adapter'], $config),
                    'proxy_host' => self::getArrayValue(['httpclient', 'proxy_host'], $config),
                    'proxy_port' => self::getArrayValue(['httpclient', 'proxy_port'], $config),
                    'proxy_user' => self::getArrayValue(['httpclient', 'proxy_user'], $config),
                    'proxy_pass' => self::getArrayValue(['httpclient', 'proxy_pass'], $config),
                ],
                'email' => [
                    'sender' => [
                        'name' => self::getArrayValue(['email', 'sender', 'name'], $config),
                        'email' => self::getArrayValue(['email', 'sender', 'email'], $config),
                    ],
                    'return' => [
                        'name' => self::getArrayValue(['email', 'return', 'name'], $config),
                        'email' => self::getArrayValue(['email', 'return', 'email'], $config),
                    ],
                    'method' => self::getArrayValue(['email', 'method'], $config),
                    'smtp' => [
                        'host' => self::getArrayValue(['email', 'smtp', 'host'], $config),
                        'port' => self::getArrayValue(['email', 'smtp', 'port'], $config),
                        'ssl' => self::getArrayValue(['email', 'smtp', 'encryption'], $config),
                        'name' => 'smtp',
                        'auth' => [
                            'method' => self::getArrayValue(['email', 'smtp', 'auth_mode'], $config),
                            'username' => self::getArrayValue(['email', 'smtp', 'username'], $config),
                            'password' => self::getArrayValue(['email', 'smtp', 'password'], $config),
                        ],
                    ],
                    'debug' => [
                        'emailaddresses' => self::getArrayValue(['email', 'debug', 'email_addresses'], $config),
                    ],
                ],
                'newsletter' => [
                    'sender' => [
                        'name' => self::getArrayValue(['newsletter', 'sender', 'name'], $config),
                        'email' => self::getArrayValue(['newsletter', 'sender', 'email'], $config),
                    ],
                    'return' => [
                        'name' => self::getArrayValue(['newsletter', 'return', 'name'], $config),
                        'email' => self::getArrayValue(['newsletter', 'return', 'name'], $config),
                    ],
                    'method' => self::getArrayValue(['newsletter', 'method'], $config),
                    'smtp' => [
                        'host' => self::getArrayValue(['newsletter', 'smtp', 'host'], $config),
                        'port' => self::getArrayValue(['newsletter', 'smtp', 'port'], $config),
                        'ssl' => self::getArrayValue(['newsletter', 'smtp', 'encryption'], $config),
                        'name' => 'smtp',
                        'auth' => [
                            'method' => self::getArrayValue(['newsletter', 'smtp', 'auth_mode'], $config),
                            'username' => self::getArrayValue(['newsletter', 'smtp', 'username'], $config),
                            'password' => self::getArrayValue(['newsletter', 'smtp', 'password'], $config),
                        ],
                    ],
                    'debug' => self::getArrayValue(['newsletter', 'debug', 'email_addresses'], $config),
                    'usespecific' => self::getArrayValue(['newsletter', 'use_specific'], $config),
                ],
                'branding' => [
                    'login_screen_invert_colors' => self::getArrayValue(['branding', 'login_screen_invert_colors'], $config),
                    'color_login_screen' => self::getArrayValue(['branding', 'color_login_screen'], $config),
                    'color_admin_interface' => self::getArrayValue(['branding', 'color_admin_interface'], $config),
                ],
                'applicationlog' => [
                    'mail_notification' => [
                        'send_log_summary' => self::getArrayValue(['applicationlog', 'mail_notification', 'send_log_summary'], $config),
                        'filter_priority' => self::getArrayValue(['applicationlog', 'mail_notification', 'filter_priority'], $config),
                        'mail_receiver' => self::getArrayValue(['applicationlog', 'mail_notification', 'mail_receiver'], $config),
                    ],
                    'archive_treshold' => self::getArrayValue(['applicationlog', 'archive_treshold'], $config),
                    'archive_alternative_database' => self::getArrayValue(['applicationlog', 'archive_alternative_database'], $config),
                ],
            ]);
        }

        return $systemConfig;
    }

    /**
     * @deprecated use getSystemConfiguration()/Pimcore\Config service instead
     * to be removed in v7.0
     *
     * @return mixed|null|\Pimcore\Config\Config
     *
     * @throws \Exception
     */
    public static function getSystemConfig()
    {
        $systemConfig = null;

        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_system')) {
            $systemConfig = \Pimcore\Cache\Runtime::get('pimcore_config_system');
        } elseif ($config = self::getSystemConfiguration()) {
            $systemConfig = self::mapLegacyConfiguration($config);
            self::setSystemConfig($systemConfig);
        }

        return $systemConfig;
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
     */
    public static function getRobotsConfig()
    {
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_robots')) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_robots');
        } else {
            try {
                $file = self::locateConfigFile('robots.php');
                $config = static::getConfigInstance($file);
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
                    $tmpData['allowedClasses'] = isset($tmpData['classes']) && $tmpData['classes'] ? explode(',', $tmpData['classes']) : null;
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

        usort($result, function ($treeA, $treeB) {
            $a = $treeA['sort'] ? $treeA['sort'] : 0;
            $b = $treeB['sort'] ? $treeB['sort'] : 0;

            if ($a > $b) {
                return 1;
            } elseif ($a < $b) {
                return -1;
            } else {
                return 0;
            }
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
     * @param Model\User $user
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
     * @param bool $reset
     * @param string|null $default
     *
     * @return string
     */
    public static function getEnvironment(bool $reset = false, string $default = null)
    {
        if (null === static::$environment || $reset) {
            $environment = false;

            if (php_sapi_name() === 'cli') {
                $input = new ArgvInput();
                $environment = $input->getParameterOption(['--env', '-e'], null, true);
            }

            // check env vars - fall back to default (prod)
            if (!$environment) {
                foreach (['PIMCORE_ENVIRONMENT', 'SYMFONY_ENV', 'APP_ENV'] as $envVarName) {
                    $environment = self::resolveEnvVarValue($envVarName);
                    if ($environment) {
                        break;
                    }
                }
            }

            if (!$environment) {
                if (null !== $default) {
                    $environment = $default;
                } else {
                    $environment = static::getEnvironmentConfig()->getDefaultEnvironment();
                }
            }

            static::$environment = $environment;
        }

        return static::$environment;
    }

    /**
     * @internal
     *
     * @param string $environment
     */
    public static function setEnvironment($environment)
    {
        static::$environment = $environment;
    }

    /**
     * @internal
     *
     * @return EnvironmentConfigInterface
     */
    public static function getEnvironmentConfig(): EnvironmentConfigInterface
    {
        if (null === static::$environmentConfig) {
            static::$environmentConfig = new EnvironmentConfig();
        }

        return static::$environmentConfig;
    }

    /**
     * @internal
     *
     * @param EnvironmentConfigInterface $environmentConfig
     */
    public static function setEnvironmentConfig(EnvironmentConfigInterface $environmentConfig)
    {
        self::$environmentConfig = $environmentConfig;
    }

    /**
     * @return mixed
     */
    public static function getFlag($key)
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');
        if (isset($config['flags'])) {
            if (isset($config['flags'][$key])) {
                return $config['flags'][$key];
            }
        }

        return null;
    }

    /**
     * @internal
     *
     * @param string $file
     * @param bool $asArray
     *
     * @return null|Config\Config
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

    /**
     * @internal
     *
     * @param string $varName
     * @param mixed $default
     *
     * @return string|null
     */
    public static function resolveEnvVarValue(string $varName, $default = null): ?string
    {
        $value = $_SERVER[$varName] ?? $_ENV[$varName] ?? $_SERVER['REDIRECT_' . $varName]
            ?? $_ENV['REDIRECT_' . $varName] ?? $default;

        return $value;
    }

    /**
     * @internal
     */
    public static function initDebugDevMode()
    {
        if (defined('PIMCORE_CONFIGURATION_DIRECTORY')) {
            $configDir = PIMCORE_CONFIGURATION_DIRECTORY;
        } else {
            // this is called via Pimcore::inDebugMode() before the constants get initialized, so we try to get the
            // path from the environment variables (if customized) or we use the default structure
            $privateVar = self::resolveEnvVarValue('PIMCORE_PRIVATE_VAR', PIMCORE_PROJECT_ROOT . '/var');
            $configDir = self::resolveEnvVarValue('PIMCORE_CONFIGURATION_DIRECTORY', $privateVar . '/config');
        }

        $debug = false;
        $devMode = false;

        $debugModeFile = $configDir . '/debug-mode.php';
        if (file_exists($debugModeFile)) {
            $confTemp = include $debugModeFile;
            if (is_array($confTemp)) {
                $conf = $confTemp;

                // init debug mode
                if (isset($conf['active'])) {
                    $debug = $conf['active'];
                    // enable debug mode only for a comma-separated list of IP addresses/ranges
                    if ($debug && $conf['ip']) {
                        $debug = false;
                        $clientIp = Tool::getClientIp();
                        if (null !== $clientIp) {
                            $debugIpAddresses = explode_and_trim(',', $conf['ip']);
                            if (IpUtils::checkIp($clientIp, $debugIpAddresses)) {
                                $debug = true;
                            }
                        }
                    }
                }

                // init dev mode
                if ($debug && isset($conf['devmode'])) {
                    $devMode = $conf['devmode'];
                }
            }
        }

        if (\Pimcore::getDebugMode() === null) {
            \Pimcore::setDebugMode($debug);

            /**
             * @deprecated
             */
            define('PIMCORE_DEBUG', $debug);
        }

        if (\Pimcore::getDevMode() === null) {
            \Pimcore::setDevMode($devMode);

            /**
             * @deprecated
             */
            define('PIMCORE_DEVMODE', $devMode);
        }
    }
}
