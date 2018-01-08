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

use Pimcore\Tool;
use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Logger;

class Config
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
     * @param $name - name of configuration file. slash is allowed for subdirectories.
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
                $pureName = str_replace("." . $fileExt, "", $name);
                foreach ($pathsToCheck as $path) {
                    $tmpFile = $path . "/" . $pureName . "." . $env . "." . $fileExt;
                    if (file_exists($tmpFile)) {
                        $file = $tmpFile;
                        break;
                    }
                }
            }

            //check for config file without environment configuration
            if (!$file) {
                foreach ($pathsToCheck as $path) {
                    $tmpFile = $path . "/" . $name;
                    if (file_exists($tmpFile)) {
                        $file = $tmpFile;
                        break;
                    }
                }
            }

            //get default path in pimcore configuration directory
            if (!$file) {
                $file = PIMCORE_CONFIGURATION_DIRECTORY . "/" . $name;
            }

            self::$configFileCache[$name] = $file;
        }

        return self::$configFileCache[$name];
    }

    /**
     * @param bool $forceReload
     * @return mixed|null|\Zend_Config
     * @throws \Zend_Exception
     */
    public static function getSystemConfig($forceReload = false)
    {
        $config = null;

        if (\Zend_Registry::isRegistered("pimcore_config_system") && !$forceReload) {
            $config = \Zend_Registry::get("pimcore_config_system");
        } else {
            try {
                $file = self::locateConfigFile("system.php");
                if (file_exists($file)) {
                    $config = new \Zend_Config(include($file));
                } else {
                    throw new \Exception($file . " doesn't exist");
                }
                self::setSystemConfig($config);
            } catch (\Exception $e) {
                $file = self::locateConfigFile("system.php");
                Logger::emergency("Cannot find system configuration, should be located at: " . $file);
                if (is_file($file)) {
                    $m = "Your system.php located at " . $file . " is invalid, please check and correct it manually!";
                    Tool::exitWithError($m);
                }
            }
        }

        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     */
    public static function setSystemConfig(\Zend_Config $config)
    {
        \Zend_Registry::set("pimcore_config_system", $config);
    }

    /**
     * @static
     * @return mixed|\Zend_Config
     */
    public static function getWebsiteConfig()
    {
        if (\Zend_Registry::isRegistered("pimcore_config_website")) {
            $config = \Zend_Registry::get("pimcore_config_website");
        } else {
            $cacheKey = "website_config";

            $siteId = null;
            if (Model\Site::isSiteRequest()) {
                $siteId = Model\Site::getCurrentSite()->getId();
            } elseif (Tool::isFrontentRequestByAdmin()) {
                // this is necessary to set the correct settings in editmode/preview (using the main domain)
                $front = \Zend_Controller_Front::getInstance();
                if ($request = $front->getRequest()) {
                    $originDocument = $request->getParam("document");
                    if ($originDocument) {
                        $site = Tool\Frontend::getSiteForDocument($originDocument);
                        if ($site) {
                            $siteId = $site->getId();
                        }
                    }
                }
            }

            if ($siteId) {
                $cacheKey = $cacheKey . "_site_" . $siteId;
            }

            if (!$config = Cache::load($cacheKey)) {
                $settingsArray = [];
                $cacheTags = ["website_config", "system", "config", "output"];

                $list = new Model\WebsiteSetting\Listing();
                $list = $list->load();



                foreach ($list as $item) {
                    $key = $item->getName();
                    $itemSiteId = $item->getSiteId();

                    if ($itemSiteId != 0 && $itemSiteId != $siteId) {
                        continue;
                    }

                    $s = null;

                    switch ($item->getType()) {
                        case "document":
                        case "asset":
                        case "object":
                            $s = Model\Element\Service::getElementById($item->getType(), $item->getData());
                            break;
                        case "bool":
                            $s = (bool) $item->getData();
                            break;
                        case "text":
                            $s = (string) $item->getData();
                            break;

                    }

                    if ($s instanceof Model\Element\ElementInterface) {
                        $cacheTags = $s->getCacheTags($cacheTags);
                    }

                    if (isset($s)) {
                        $settingsArray[$key] = $s;
                    }
                }

                $config = new \Zend_Config($settingsArray, true);

                Cache::save($config, $cacheKey, $cacheTags, null, 998);
            }

            self::setWebsiteConfig($config);
        }

        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     */
    public static function setWebsiteConfig(\Zend_Config $config)
    {
        \Zend_Registry::set("pimcore_config_website", $config);
    }


    /**
     * @static
     * @return \Zend_Config
     */
    public static function getReportConfig()
    {
        if (\Zend_Registry::isRegistered("pimcore_config_report")) {
            $config = \Zend_Registry::get("pimcore_config_report");
        } else {
            try {
                $file = self::locateConfigFile("reports.php");
                if (file_exists($file)) {
                    $config = new \Zend_Config(include($file));
                } else {
                    throw new \Exception("Config-file " . $file . " doesn't exist.");
                }
            } catch (\Exception $e) {
                $config = new \Zend_Config([]);
            }

            self::setReportConfig($config);
        }

        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     */
    public static function setReportConfig(\Zend_Config $config)
    {
        \Zend_Registry::set("pimcore_config_report", $config);
    }

    /**
     * @static
     * @return \Zend_Config
     */
    public static function getWeb2PrintConfig()
    {
        if (\Zend_Registry::isRegistered("pimcore_config_web2print")) {
            $config = \Zend_Registry::get("pimcore_config_web2print");
        } else {
            try {
                $file = self::locateConfigFile("web2print.php");
                if (file_exists($file)) {
                    $config = new \Zend_Config(include($file));
                } else {
                    throw new \Exception("Config-file " . $file . " doesn't exist.");
                }
            } catch (\Exception $e) {
                $config = new \Zend_Config([]);
            }

            self::setWeb2PrintConfig($config);
        }

        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     */
    public static function setWeb2PrintConfig(\Zend_Config $config)
    {
        \Zend_Registry::set("pimcore_config_web2print", $config);
    }

    /**
     * @static
     * @param \Zend_Config $config
     */
    public static function setModelClassMappingConfig($config)
    {
        \Zend_Registry::set("pimcore_config_model_classmapping", $config);
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getFlag($name)
    {
        $settings = self::getSystemConfig()->toArray();

        if (isset($settings["flags"])) {
            if (isset($settings["flags"][$name])) {
                return $settings["flags"][$name];
            }
        }

        return null;
    }

    /**
     * @param $name
     * @param $value
     */
    public static function setFlag($name, $value)
    {
        $settings = self::getSystemConfig()->toArray();

        if (!isset($settings["flags"])) {
            $settings["flags"] = [];
        }

        $settings["flags"][$name] = $value;

        $configFile = \Pimcore\Config::locateConfigFile("system.php");
        File::putPhpFile($configFile, to_php_data_file_format($settings));
    }

    /**
     * @static
     * @return mixed|\Zend_Config
     */
    public static function getPerspectivesConfig()
    {
        if (\Zend_Registry::isRegistered("pimcore_config_perspectives")) {
            $config = \Zend_Registry::get("pimcore_config_perspectives");
        } else {
            try {
                $file = self::locateConfigFile("perspectives.php");
                if (file_exists($file)) {
                    $config = new \Zend_Config(include($file));
                } else {
                    throw new \Exception($file . " doesn't exist");
                }
                self::setPerspectivesConfig($config);
            } catch (\Exception $e) {
                Logger::info("Cannot find perspectives configuration, should be located at: " . $file);
                if (is_file($file)) {
                    $m = "Your perspectives.php located at " . $file . " is invalid, please check and correct it manually!";
                    Tool::exitWithError($m);
                }
                $config = new \Zend_Config(self::getStandardPerspective());
                self::setPerspectivesConfig($config);
            }
        }

        return $config;
    }

    /** Returns the standard perspective settings
     * @return array
     */
    public static function getStandardPerspective()
    {
        $elementTree = [
            [
                "type" => "documents",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -3,
                "treeContextMenu" => [
                    "document" => [
                        "items" => [
                            "addPrintPage" => self::getWeb2PrintConfig()->enableInDefaultView ? true : false // hide add print documents by default
                        ]
                    ]
                ]
            ],
            [
                "type" => "assets",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -2
            ],
            [
                "type" => "objects",
                "position" => "left",
                "expanded" => false,
                "hidden" => false,
                "sort" => -1
            ],
        ];

        $cvConfigs = Tool::getCustomViewConfig();
        if ($cvConfigs) {
            foreach ($cvConfigs as $cvConfig) {
                $cvConfig["type"] = "customview";
                $elementTree[] = $cvConfig;
            }
        }

        return [
            "default" => [
                "iconCls" => "pimcore_icon_perspective",
                "elementTree" => $elementTree,
                "dashboards" => [
                    "predefined" => [
                        "welcome" => [
                            "positions" => [
                                [
                                    [
                                        "id" => 1,
                                        "type" => "pimcore.layout.portlets.modificationStatistic",
                                        "config" => null
                                    ],
                                    [
                                        "id" => 2,
                                        "type" => "pimcore.layout.portlets.modifiedAssets",
                                        "config" => null
                                    ]
                                ],
                                [
                                    [
                                        "id" => 3,
                                        "type" => "pimcore.layout.portlets.modifiedObjects",
                                        "config" => null
                                    ],
                                    [
                                        "id" => 4,
                                        "type" => "pimcore.layout.portlets.modifiedDocuments",
                                        "config" => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /** Gets the active perspective for the current user
     * @return array
     */
    public static function getRuntimePerspective()
    {
        $currentUser = Tool\Admin::getCurrentUser();
        $currentConfigName = $currentUser->getActivePerspective() ? $currentUser->getActivePerspective() : $currentUser->getFirstAllowedPerspective();

        $config = self::getPerspectivesConfig()->toArray();
        $result = [];

        if ($config[$currentConfigName]) {
            $result = $config[$currentConfigName];
        } else {
            $availablePerspectives = self::getAvailablePerspectives($currentUser);
            if ($availablePerspectives) {
                $currentPerspective = reset($availablePerspectives);
                $currentConfigName = $currentPerspective["name"];
                if ($currentConfigName && $config[$currentConfigName]) {
                    $result = $config[$currentConfigName];
                }
            }
        }

        if ($result && $currentConfigName != $currentUser->getActivePerspective()) {
            $currentUser->setActivePerspective($currentConfigName);
            $currentUser->save();
        }


        $result["elementTree"] = self::getRuntimeElementTreeConfig($currentConfigName);

        return $result;
    }


    /** Returns the element tree config for the given config name
     * @param $name
     * @return array
     */
    protected static function getRuntimeElementTreeConfig($name)
    {
        $masterConfig = self::getPerspectivesConfig()->toArray();

        $config = $masterConfig[$name];
        if (!$config) {
            $config = [];
        }

        $tmpResult = $config["elementTree"];
        if (is_null($tmpResult)) {
            $tmpResult = [];
        }
        $result = [];

        $cfConfigMapping = [];

        $cvConfigs = Tool::getCustomViewConfig();

        if ($cvConfigs) {
            foreach ($cvConfigs as $node) {
                $tmpData = $node;
                if (!isset($tmpData["id"])) {
                    Logger::error("custom view ID is missing " . var_export($tmpData, true));
                    continue;
                }

                if ($tmpData["hidden"]) {
                    continue;
                }

                // backwards compatibility
                $treeType = $tmpData["treetype"] ? $tmpData["treetype"] : "object";
                $rootNode = Model\Element\Service::getElementByPath($treeType, $tmpData["rootfolder"]);

                if ($rootNode) {
                    $tmpData["type"] = "customview";
                    $tmpData["rootId"] = $rootNode->getId();
                    $tmpData["allowedClasses"] = $tmpData["classes"] ? explode(",", $tmpData["classes"]) : null;
                    $tmpData["showroot"] = (bool)$tmpData["showroot"];
                    $customViewId = $tmpData["id"];
                    $cfConfigMapping[$customViewId]= $tmpData;
                }
            }
        }


        foreach ($tmpResult as $resultItem) {
            if ($resultItem["hidden"]) {
                continue;
            }

            if ($resultItem["type"] == "customview") {
                $customViewId = $resultItem["id"];
                if (!$customViewId) {
                    Logger::error("custom view id missing " . var_export($resultItem, true));
                    continue;
                }
                $customViewCfg = $cfConfigMapping[$customViewId];
                if (!$customViewId) {
                    Logger::error("no custom view config for id  " . $customViewId);
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
            $a = $treeA["sort"] ? $treeA["sort"] : 0;
            $b = $treeB["sort"] ? $treeB["sort"] : 0;

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
     * @static
     * @param \Zend_Config $config
     */
    public static function setPerspectivesConfig(\Zend_Config $config)
    {
        \Zend_Registry::set("pimcore_config_perspectives", $config);
    }


    /** Returns a list of available perspectives for the given user
     * @param Model\User $user
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
                    $perspectives = $userOrRoleToCheck->getPerspectives();
                    if ($perspectives) {
                        foreach ($perspectives as $perspectiveName) {
                            $masterDef = $masterConfig[$perspectiveName];
                            if ($masterDef) {
                                $config[$perspectiveName] = $masterDef;
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
                $currentConfigName = reset(array_keys($config));
            }
        } else {
            $config = self::getPerspectivesConfig()->toArray();
        }

        $result = [];

        foreach ($config as $configName => $configItem) {
            $item = [
                "name" => $configName,
                "icon" => isset($configItem["icon"]) ? $configItem["icon"] : null,
                "iconCls" => isset($configItem["iconCls"]) ? $configItem["iconCls"] : null
            ];
            if ($user) {
                $item["active"] = $configName == $currentConfigName;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param $runtimeConfig
     * @param $key
     * @return bool
     */
    public static function inPerspective($runtimeConfig, $key)
    {
        if (!isset($runtimeConfig["toolbar"]) || !$runtimeConfig["toolbar"]) {
            return true;
        }
        $parts = explode(".", $key);
        $menuItems = $runtimeConfig["toolbar"];

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            if (!isset($menuItems[$part])) {
                break;
            }

            $menuItem = $menuItems[$part];

            if (is_array($menuItem)) {
                if ($menuItem["hidden"]) {
                    return false;
                }

                if (!$menuItem["items"]) {
                    break;
                }
                $menuItems = $menuItem["items"];
            } else {
                return $menuItem;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public static function getEnvironment()
    {
        // null means that it wasn't checked, false means it was checked already, but not environment available
        if (self::$environment === null) {
            // check environment variables
            self::$environment = getenv("PIMCORE_ENVIRONMENT") ?: (getenv("REDIRECT_PIMCORE_ENVIRONMENT") ?: false);

            if (!self::$environment && isset($_SERVER["argv"]) && is_array($_SERVER["argv"])) {
                // check CLI option: --environment[=ENVIRONMENT]
                foreach ($_SERVER["argv"] as $argument) {
                    if (preg_match("@\-\-environment=(.*)@", $argument, $matches)) {
                        self::$environment = $matches[1];
                        break;
                    }
                }
            }
        }

        return self::$environment;
    }

    /**
     * @param string $environment
     */
    public static function setEnvironment($environment)
    {
        self::$environment = $environment;
    }
}
