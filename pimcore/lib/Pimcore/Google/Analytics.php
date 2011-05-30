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

class Pimcore_Google_Analytics {
    
    public static $stack = array();
    
    public static function isConfigured (Site $site = null) {
        if(self::getSiteConfig($site)) {
            return true;
        }
        return false;
    }
    
    public static function getSiteKey (Site $site = null) {

        if($site) {
            $siteKey = "site_" . $site->getId();
        }
        else {
            $siteKey = "default";
        }
        
        return $siteKey;
    }
    
    public static function getSiteConfig ($site = null) {
        
        // check for site
        if(!$site) {
            try {
                $site = Zend_Registry::get("pimcore_site");
            }
            catch (Exception $e) {
                $site = null;
            }
        }
        
        $siteKey = self::getSiteKey($site);
        
        if(Pimcore_Config::getReportConfig()->analytics->sites->$siteKey->profile) {
            return Pimcore_Config::getReportConfig()->analytics->sites->$siteKey;
        }
        return false;
    }
    
    public static function getCode () {
                
        $config = self::getSiteConfig();
        
        // do nothing if not configured
        if(!$config || !$config->trackid) {
            return "";
        }
        
        
        
        $stack = array();
        
        if($config->advanced) {
            foreach (self::$stack as $s) {
            $stack[] = "_gaq.push(" . Zend_Json::encode($s) . ");";
            }
            
            // remove dublicates
            $stack = array_unique($stack);
        }
        
        
        $code = "";
        $code .= "
            <script type=\"text/javascript\">

              var _gaq = _gaq || [];
              _gaq.push(['_setAccount', '" . $config->trackid . "']);
              if (typeof _gaqPageView != \"undefined\"){
                _gaq.push(['_trackPageview',_gaqPageView]);
              } else {
                _gaq.push(['_trackPageview']);
              }
            
              (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
              })();
              
              " . implode("\n",$stack) . "
              
            </script>
        ";
        
        return $code;  
    }
    
    public static function trackElement (Element_Interface $element) {
        
        $type = "";
        if($element instanceof Document) {
            $type = "document";
        }
        else if($element instanceof Asset) {
            $type = "asset";
        }
        else if($element instanceof Object_Abstract) {
            $type = "object";
        }
        else {
            return;
        }
        
        self::trackPageView('/pimcoreanalytics/' . $type . '/' . $element->getId());
        
        return true;
    }
    
    public static function trackPageView ($path) {
        self::$stack[] = array("_trackPageview",$path);
    }
    
    
    
    
    
    /*
        GOOGLE WEBSITE OPTIMIZER 
    */
    
    public static function getOptimizerTop ($doc) {
        
        if($doc->getProperty("google_website_optimizer_test_id") && $doc->getProperty("google_website_optimizer_original_page")) {
            $code = <<<XML
            
            
            <!-- Google Website Optimizer Control Script -->
            <script>
            function utmx_section(){}function utmx(){}
            (function(){var k='{#__TESTID__#}',d=document,l=d.location,c=d.cookie;function f(n){
            if(c){var i=c.indexOf(n+'=');if(i>-1){var j=c.indexOf(';',i);return c.substring(i+n.
            length+1,j<0?c.length:j)}}}var x=f('__utmx'),xx=f('__utmxx'),h=l.hash;
            d.write('<sc'+'ript src="'+
            'http'+(l.protocol=='https:'?'s://ssl':'://www')+'.google-analytics.com'
            +'/siteopt.js?v=1&utmxkey='+k+'&utmx='+(x?x:'')+'&utmxx='+(xx?xx:'')+'&utmxtime='
            +new Date().valueOf()+(h?'&utmxhash='+escape(h.substr(1)):'')+
            '" type="text/javascript" charset="utf-8"></sc'+'ript>')})();
            </script><script>utmx("url",'A/B');</script>
            <!-- End of Google Website Optimizer Control Script -->
            
            
XML;
            
            return str_replace("{#__TESTID__#}",$doc->getProperty("google_website_optimizer_test_id"),$code);
        }
        return false;
    }
    
    public static function getOptimizerBottom ($doc) {
        
        if($doc->getProperty("google_website_optimizer_test_id") && $doc->getProperty("google_website_optimizer_track_id")) {
            $code = <<<XML
            
            
            <!-- Google Website Optimizer Tracking Script -->
            <script type="text/javascript">
            if(typeof(_gat)!='object')document.write('<sc'+'ript src="http'+
            (document.location.protocol=='https:'?'s://ssl':'://www')+
            '.google-analytics.com/ga.js"></sc'+'ript>')</script>
            <script type="text/javascript">
            try {
            var gwoTracker=_gat._getTracker("{#__TRACKID__#}");
            gwoTracker._trackPageview("/{#__TESTID__#}/test");
            }catch(err){}</script>
            <!-- End of Google Website Optimizer Tracking Script -->
            
            
XML;

            $code = str_replace("{#__TESTID__#}",$doc->getProperty("google_website_optimizer_test_id"),$code);
            $code = str_replace("{#__TRACKID__#}",$doc->getProperty("google_website_optimizer_track_id"),$code);
            
            return $code;
        }
        return false;
    }
    
    public static function getOptimizerConversion ($doc) {
        
        if($doc->getProperty("google_website_optimizer_conversion_page") && $doc->getProperty("google_website_optimizer_test_id") && $doc->getProperty("google_website_optimizer_track_id")) {
            $code = <<<XML
            
            
            <!-- Google Website Optimizer Conversion Script -->
            <script type="text/javascript">
            if(typeof(_gat)!='object')document.write('<sc'+'ript src="http'+
            (document.location.protocol=='https:'?'s://ssl':'://www')+
            '.google-analytics.com/ga.js"></sc'+'ript>')</script>
            <script type="text/javascript"> 
            try {
            var gwoTracker=_gat._getTracker("{#__TRACKID__#}");
            gwoTracker._trackPageview("/{#__TESTID__#}/goal");
            }catch(err){}</script>
            <!-- End of Google Website Optimizer Conversion Script -->
            
            
XML;

            $code = str_replace("{#__TESTID__#}",$doc->getProperty("google_website_optimizer_test_id"),$code);
            $code = str_replace("{#__TRACKID__#}",$doc->getProperty("google_website_optimizer_track_id"),$code);
            
            return $code;
        }
        return false;
    }
}
