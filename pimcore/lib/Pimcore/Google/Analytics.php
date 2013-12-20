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

class Pimcore_Google_Analytics {
    
    public static $stack = array();

    public static $defaultPath = null;
    
    public static function isConfigured (Site $site = null) {
        if(self::getSiteConfig($site) && self::getSiteConfig($site)->profile) {
            return true;
        }
        return false;
    }

    public static function getSiteConfig ($site = null) {
        
        $siteKey = Pimcore_Tool_Frontend::getSiteKey($site);
        
        $config = Pimcore_Config::getReportConfig();
        if (!$config->analytics) {
            return false;
        }

        if($config->analytics->sites->$siteKey) {
            return Pimcore_Config::getReportConfig()->analytics->sites->$siteKey;
        }
        return false;
    }
    
    public static function getCode ($config = null) {
                
        if(is_null($config)){
            $config = self::getSiteConfig();
        }
        
        // do nothing if not configured
        if(!$config || !$config->trackid) {
            return "";
        }

        $code = "";

        if($config->universalcode) {
            $code .= "
            <script>
              (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
              m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
              })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

              " . $config->additionalcodebeforepageview . "

              ga('create', '" . $config->trackid . "'" . ($config->universal_configuration ? ("," . $config->universal_configuration) : "") . ");
              if (typeof _gaqPageView != \"undefined\"){
                ga('send', 'pageview', _gaqPageView);
              } else {
                ga('send', 'pageview');
              }

              " . $config->additionalcode . "
            </script>";
        } else {
            $typeSrc = "ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';";
            if($config->retargetingcode) {
                $typeSrc = "ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';";
            }

            $code .= "
            <script type=\"text/javascript\">

              var _gaq = _gaq || [];
              _gaq.push(['_setAccount', '" . $config->trackid . "']);
              _gaq.push (['_gat._anonymizeIp']);
              " . $config->additionalcodebeforepageview . "
              if (typeof _gaqPageView != \"undefined\"){
                _gaq.push(['_trackPageview',_gaqPageView]);
              } else {
                _gaq.push(['_trackPageview'" . (self::$defaultPath ? (",'" . self::$defaultPath . "'") : "") . "]);
              }

              " . $config->additionalcode . "

              (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                " . $typeSrc . "
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
              })();
            </script>";
        }

        
        return $code;  
    }
    
    public static function trackElement (Element_Interface $element) {
        Logger::error("Pimcore_Google_Analytics::trackPageView() is unsupported as of version 2.0.1");
    }
    
    public static function trackPageView ($path) {
        Logger::error("Pimcore_Google_Analytics::trackPageView() is unsupported as of version 2.0.1");
    }

    public static function setDefaultPath($defaultPath)
    {
        self::$defaultPath = $defaultPath;
    }

    public static function getDefaultPath()
    {
        return self::$defaultPath;
    }
}
