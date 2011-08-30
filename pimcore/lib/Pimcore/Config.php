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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Config {

    /**
     * @static
     * @return Zend_Config
     */
    public static function getSystemConfig () {

        $config = null;

        try {
            $config = Zend_Registry::get("pimcore_config_system");
        } catch (Exception $e) {

            try {
                $config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_SYSTEM);
                self::setSystemConfig($config);
            } catch (Exception $e) {
                Logger::emergency("Cannot find system configuration, should be located at: " . PIMCORE_CONFIGURATION_SYSTEM);
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
        try {
            $config = Zend_Registry::get("pimcore_config_website");
        } catch (Exception $e) {
            $cacheKey = "website_config";
            if (!$config = Pimcore_Model_Cache::load($cacheKey)) {

                $websiteSettingFile = PIMCORE_CONFIGURATION_DIRECTORY . "/website.xml";
                $settingsArray = array();

                if(is_file($websiteSettingFile)) {
                    $rawConfig = new Zend_Config_Xml($websiteSettingFile);
                    $arrayData = $rawConfig->toArray();

                    foreach ($arrayData as $key => $value) {
                        $s = null;
                        if($value["type"] == "document") {
                            $s = Document::getByPath($value["data"]);
                        }
                        else if($value["type"] == "asset") {
                            $s = Asset::getByPath($value["data"]);
                        }
                        else if($value["type"] == "object") {
                            $s = Object_Abstract::getByPath($value["data"]);
                        }
                        else if($value["type"] == "bool") {
                            $s = (bool) $value["data"];
                        }
                        else if($value["type"] == "text") {
                            $s = (string) $value["data"];
                        }


                        if($s) {
                            $settingsArray[$key] = $s;
                        }
                    }
                }
                $config = new Zend_Config($settingsArray, true);

                Pimcore_Model_Cache::save($config, $cacheKey, array("websiteconfig","system","config"), null, 998);
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
        try {
            $config = Zend_Registry::get("pimcore_config_report");
        } catch (Exception $e) {
            try {
                $config = new Zend_Config_Xml(PIMCORE_CONFIGURATION_DIRECTORY . "/reports.xml");
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
        
        try {
            $config = Zend_Registry::get("pimcore_config_model_classmapping");
        } catch (Exception $e) {
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
