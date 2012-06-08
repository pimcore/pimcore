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
    

    public static function getSiteConfig ($site = null) {
        
        $siteKey = Pimcore_Tool_Frontend::getSiteKey($site);
        
        if(Pimcore_Config::getReportConfig()->webmastertools->sites->$siteKey->verification) {
            return Pimcore_Config::getReportConfig()->webmastertools->sites->$siteKey;
        }
        return false;
    }
}
