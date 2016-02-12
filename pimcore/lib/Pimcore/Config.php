<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore;

use Pimcore\Tool;
use Pimcore\Cache;
use Pimcore\Model;

class Config {

    /**
     * @var array
     */
    protected static $configFileCache = [];

    /**
     * returns null if no env is set
     * returns false if env is set but file not found
     * @return bool|null
     */
    public static function locateEnvConfigFile() {

        $env = getenv("PIMCORE_ENVIRONMENT");

        if($env === FALSE) {
            return NULL;
        }

        $envName = "system." . $env . ".php";

        if(!isset(self::$configFileCache[$envName])) {

            $pathsToCheck = [
                PIMCORE_WEBSITE_PATH . "/config",
                PIMCORE_CONFIGURATION_DIRECTORY,
            ];

            $file = NULL;

            foreach ($pathsToCheck as $path) {
                $tmpFile = $path . "/" . $envName;

                if (file_exists($tmpFile)) {
                    $file = $tmpFile;
                    break;
                }
            }

            if(is_null($file)) {
                return FALSE;
            }

            self::$configFileCache[$envName] = $file;
        }

        return self::$configFileCache[$envName];
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function locateConfigFile($name) {

        if(!isset(self::$configFileCache[$name])) {

            $pathsToCheck = [
                PIMCORE_WEBSITE_PATH . "/config",
                PIMCORE_CONFIGURATION_DIRECTORY,
            ];
            $file = PIMCORE_CONFIGURATION_DIRECTORY . "/" . $name;

            foreach($pathsToCheck as $path) {
                $tmpFile = $path . "/" . $name;
                if(file_exists($tmpFile)) {
                    $file = $tmpFile;
                    break;
                }
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
    public static function getSystemConfig ($forceReload = false) {

        $config = null;

        if(\Zend_Registry::isRegistered("pimcore_config_system") && !$forceReload) {
            $config = \Zend_Registry::get("pimcore_config_system");
        } else  {
            try {
                $file = self::locateConfigFile("system.php");
                if(file_exists($file)) {
                    $config = new \Zend_Config(include($file), true);
                } else {
                    throw new \Exception($file . " doesn't exist");
                }

                $envFile = self::locateEnvConfigFile();
                if($envFile !== NULL ) {
                    if( $envFile !== FALSE) {
                        $envConfig = new \Zend_Config(include($envFile));
                        $config = $config->merge( $envConfig );
                        self::setSystemConfig($config);
                    } else {
                        Tool::exitWithError("Environment system config doesn't exist!");
                    }
                } else {
                    self::setSystemConfig($config);
                }


            } catch (\Exception $e) {
                $file = self::locateConfigFile("system.php");
                \Logger::emergency("Cannot find system configuration, should be located at: " . $file);
                if(is_file($file)) {
                    $m = "Your system.php located at " . $file . " is invalid, please check and correct it manually!" . $e->getMessage();
                    Tool::exitWithError($m);
                }
            }

        }

        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     * @return void
     */
    public static function setSystemConfig (\Zend_Config $config) {
        \Zend_Registry::set("pimcore_config_system", $config);
    }

    /**
     * @static
     * @return mixed|\Zend_Config
     */
    public static function getWebsiteConfig () {
        if(\Zend_Registry::isRegistered("pimcore_config_website")) {
            $config = \Zend_Registry::get("pimcore_config_website");
        } else {
            $cacheKey = "website_config";

            $siteId = null;
            if(Model\Site::isSiteRequest()){
                $siteId = Model\Site::getCurrentSite()->getId();
                $cacheKey = $cacheKey . "_site_" . $siteId;
            }

            if (!$config = Cache::load($cacheKey)) {
                $settingsArray = array();
                $cacheTags = array("website_config","system","config","output");

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

                    if($s instanceof Model\Element\ElementInterface) {
                        $cacheTags = $s->getCacheTags($cacheTags);
                    }

                    if(isset($s)) {
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
     * @return void
     */
    public static function setWebsiteConfig (\Zend_Config $config) {
        \Zend_Registry::set("pimcore_config_website", $config);
    }


    /**
     * @static
     * @return \Zend_Config
     */
    public static function getReportConfig () {

        if(\Zend_Registry::isRegistered("pimcore_config_report")) {
            $config = \Zend_Registry::get("pimcore_config_report");
        } else {
            try {
                $file = self::locateConfigFile("reports.php");
                if(file_exists($file)) {
                    $config = new \Zend_Config(include($file));
                } else {
                    throw new \Exception("Config-file " . $file . " doesn't exist.");
                }
            }
            catch (\Exception $e) {
                $config = new \Zend_Config(array());
            }

            self::setReportConfig($config);
        }
        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     * @return void
     */
    public static function setReportConfig (\Zend_Config $config) {
        \Zend_Registry::set("pimcore_config_report", $config);
    }


    /**
     * @static
     * @return \Zend_Config_Xml
     */
    public static function getModelClassMappingConfig () {

        $config = null;

        if(\Zend_Registry::isRegistered("pimcore_config_model_classmapping")) {
            $config = \Zend_Registry::get("pimcore_config_model_classmapping");
        } else {
            $mappingFile = \Pimcore\Config::locateConfigFile("classmap.php");

            if(is_file($mappingFile)) {
                $config = include($mappingFile);

                if(is_array($config)) {
                    self::setModelClassMappingConfig($config);
                } else {
                    \Logger::error("classmap.json exists but it is not a valid JSON configuration. Maybe there is a syntax error in the JSON.");
                }
            }
        }
        return $config;
    }

    /**
     * @static
     * @param \Zend_Config $config
     * @return void
     */
    public static function setModelClassMappingConfig ($config) {
        \Zend_Registry::set("pimcore_config_model_classmapping", $config);
    }
}