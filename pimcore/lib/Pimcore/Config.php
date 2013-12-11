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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Config {

    /**
     * @static
     * @return Zend_Config
     */
    public static function getSystemConfig () {

        $config = null;

        if(Zend_Registry::isRegistered("pimcore_config_system")) {
            $config = Zend_Registry::get("pimcore_config_system");
        } else  {
            try {
                $config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_SYSTEM);
                self::setSystemConfig($config);
            } catch (Exception $e) {
                Logger::emergency("Cannot find system configuration, should be located at: " . PIMCORE_CONFIGURATION_SYSTEM);
                if(is_file(PIMCORE_CONFIGURATION_SYSTEM)) {
                    $m = "Your system.xml located at " . PIMCORE_CONFIGURATION_SYSTEM . " is invalid, please check and correct it manually!";
                    Pimcore_Tool::exitWithError($m);
                }
            }
        }

        return $config;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return void
     */
    public static function setSystemConfig (Zend_Config $config) {
        Zend_Registry::set("pimcore_config_system", $config);
    }

    /**
     * @static
     * @return mixed|Zend_Config
     */
    public static function getWebsiteConfig () {
        if(Zend_Registry::isRegistered("pimcore_config_website")) {
            $config = Zend_Registry::get("pimcore_config_website");
        } else {
            $cacheKey = "website_config";

            if(Site::isSiteRequest()){
                $siteId = Site::getCurrentSite()->getId();
                $cacheKey = $cacheKey . "_site_" . $siteId;
            }

            if (!$config = Pimcore_Model_Cache::load($cacheKey)) {
                $settingsArray = array();
                $cacheTags = array("website_config","system","config","output");

                $list = new WebsiteSetting_List();
                $list = $list->load();



                foreach ($list as $item) {
                    $key = $item->getName();
                    $siteId = $item->getSiteId();

                    if(!$siteId && $siteId > 0){
                        continue;
                    }

                    if($siteId && $siteId > 0 && $siteId != $siteId){
                        continue;
                    }

                    $s = null;

                    switch ($item->getType()) {
                        case "document":
                        case "asset":
                        case "object":
                            $s = Element_Service::getElementById($item->getType(), $item->getData());
                            break;
                        case "bool":
                            $s = (bool) $item->getData();
                            break;
                        case "text":
                            $s = (string) $item->getData();
                            break;

                    }

                    if($s instanceof Element_Interface) {
                        $cacheTags = $s->getCacheTags($cacheTags);
                    }

                    if(isset($s)) {
                        $settingsArray[$key] = $s;
                    }
                }

                $config = new Zend_Config($settingsArray, true);

                Pimcore_Model_Cache::save($config, $cacheKey, $cacheTags, null, 998);
            }

            self::setWebsiteConfig($config);
        }

        return $config;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return void
     */
    public static function setWebsiteConfig (Zend_Config $config) {
        Zend_Registry::set("pimcore_config_website", $config);
    }


    /**
     * @static
     * @return Zend_Config
     */
    public static function getReportConfig () {

        if(Zend_Registry::isRegistered("pimcore_config_report")) {
            $config = Zend_Registry::get("pimcore_config_report");
        } else {
            try {
                $configFile = PIMCORE_CONFIGURATION_DIRECTORY . "/reports.xml";
                if(file_exists($configFile)) {
                    $config = new Zend_Config_Xml($configFile);
                } else {
                    throw new \Exception("Config-file " . $configFile . " doesn't exist.");
                }
            }
            catch (Exception $e) {
                $config = new Zend_Config(array());
            }

            self::setReportConfig($config);
        }
        return $config;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return void
     */
    public static function setReportConfig (Zend_Config $config) {
        Zend_Registry::set("pimcore_config_report", $config);
    }


    /**
     * @static
     * @return Zend_Config_Xml
     */
    public static function getModelClassMappingConfig () {

        $config = null;

        if(Zend_Registry::isRegistered("pimcore_config_model_classmapping")) {
            $config = Zend_Registry::get("pimcore_config_model_classmapping");
        } else {
            $mappingFile = PIMCORE_CONFIGURATION_DIRECTORY . "/classmap.xml";

            if(is_file($mappingFile) && is_readable($mappingFile)) {
                try {
                    $config = new Zend_Config_Xml($mappingFile);
                    self::setModelClassMappingConfig($config);
                } catch (Exception $e) {
                    Logger::error("classmap.xml exists but it is not a valid Zend_Config_Xml configuration. Maybe there is a syntaxerror in the XML.");
                }
            }
        }
        return $config;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return void
     */
    public static function setModelClassMappingConfig (Zend_Config $config) {
        Zend_Registry::set("pimcore_config_model_classmapping", $config);
    }
}
