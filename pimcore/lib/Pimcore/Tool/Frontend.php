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

class Pimcore_Tool_Frontend {
    
    /**
     * Returns the Website-Config
     * @return Zend_Config
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
                
                Pimcore_Model_Cache::save($config, $cacheKey, array("websiteconfig","system","config"));
            }
            
            Zend_Registry::set("pimcore_config_website",$config);
        }
        
        return $config;
    }
}
