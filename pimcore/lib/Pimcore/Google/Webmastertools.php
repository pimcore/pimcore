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

class Pimcore_Google_Webmastertools {
    
    public static $stack = array();
    
    public static function isConfigured (Site $site = null) {
        if(self::getSiteConfig($site)) {
            return true;
        }
        return false;
    }
    
    public static function getSiteKey (Site $site = null) {
        // check for site
        if(!$site) {
            try {
                $site = Zend_Registry::get("pimcore_site");
            }
            catch (Exception $e) {
                $site = false;
            }
        }
        
        
        if($site) {
            $siteKey = "site_" . $site->getId();
        }
        else {
            $siteKey = "default";
        }
        
        return $siteKey;
    }
    
    public static function getSiteConfig ($site = null) {
        
        $siteKey = self::getSiteKey($site);
        
        if(Pimcore_Config::getReportConfig()->webmastertools->sites->$siteKey->profile) {
            return Pimcore_Config::getReportConfig()->webmastertools->sites->$siteKey;
        }
        return false;
    }
}
