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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Google;

use Pimcore\Config;
use Pimcore\Model;

class Analytics {

    /**
     * @var array
     */
    public static $stack = array();

    /**
     * @var null
     */
    public static $defaultPath = null;

    /**
     * @var array
     */
    protected static $additionalCodes = [
        "beforeInit" => [],
        "beforePageview" => [],
        "beforeEnd" => []
    ];

    /**
     * @param Model\Site $site
     * @return bool
     */
    public static function isConfigured (Model\Site $site = null) {
        if(self::getSiteConfig($site) && self::getSiteConfig($site)->profile) {
            return true;
        }
        return false;
    }

    /**
     * @param null $site
     * @return bool
     */
    public static function getSiteConfig ($site = null) {
        
        $siteKey = \Pimcore\Tool\Frontend::getSiteKey($site);
        
        $config = Config::getReportConfig();
        if (!$config->analytics) {
            return false;
        }

        if($config->analytics->sites->$siteKey) {
            return Config::getReportConfig()->analytics->sites->$siteKey;
        }
        return false;
    }

    /**
     * @param null $config
     * @return string
     */
    public static function getCode ($config = null) {
                
        if(is_null($config)){
            $config = self::getSiteConfig();
        }
        
        // do nothing if not configured
        if(!$config || !$config->trackid) {
            return "";
        }

        $codeBeforeInit = $config->additionalcodebeforeinit;
        $codeBeforePageview = $config->additionalcodebeforepageview;
        $codeBeforeEnd = $config->additionalcode;

        if(!empty(self::$additionalCodes["beforeInit"])) {
            $codeBeforeInit .= "\n" . implode("\n", self::$additionalCodes["beforeInit"]);
        }

        if(!empty(self::$additionalCodes["beforePageview"])) {
            $codeBeforePageview .= "\n" . implode("\n", self::$additionalCodes["beforePageview"]);
        }

        if(!empty(self::$additionalCodes["beforeEnd"])) {
            $codeBeforeEnd .= "\n" . implode("\n", self::$additionalCodes["beforeEnd"]);
        }


        $code = "";

        if($config->asynchronouscode || $config->retargetingcode) {
            $typeSrc = "ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';";
            if($config->retargetingcode) {
                $typeSrc = "ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';";
            }

            $code .= "
            <script type=\"text/javascript\">

              " . $codeBeforeInit . "
              var _gaq = _gaq || [];
              _gaq.push(['_setAccount', '" . $config->trackid . "']);
              _gaq.push (['_gat._anonymizeIp']);
              " . $codeBeforePageview . "
              if (typeof _gaqPageView != \"undefined\"){
                _gaq.push(['_trackPageview',_gaqPageView]);
              } else {
                _gaq.push(['_trackPageview'" . (self::$defaultPath ? (",'" . self::$defaultPath . "'") : "") . "]);
              }

              " . $codeBeforeEnd . "

              (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                " . $typeSrc . "
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
              })();
            </script>";
        } else {
            $code .= "
            <script>
              (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
              m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
              })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

              " . $codeBeforeInit . "

              ga('create', '" . $config->trackid . "'" . ($config->universal_configuration ? ("," . $config->universal_configuration) : "") . ");
              " . $codeBeforePageview . "
              if (typeof _gaqPageView != \"undefined\"){
                ga('send', 'pageview', _gaqPageView);
              } else {
                ga('send', 'pageview'" . (self::$defaultPath ? (",'" . self::$defaultPath . "'") : "") . ");
              }

              " . $codeBeforeEnd . "
            </script>";
        }

        
        return $code;  
    }

    /**
     * @param string $code
     * @param string $where
     */
    public static function addAdditionalCode($code, $where = "beforeEnd") {
        self::$additionalCodes[$where][] = $code;
    }

    /**
     * @param Model\Element\ElementInterface $element
     */
    public static function trackElement (Model\Element\ElementInterface $element) {
        \Logger::error("Pimcore_Google_Analytics::trackPageView() is unsupported as of version 2.0.1");
    }

    /**
     * @param $path
     */
    public static function trackPageView ($path) {
        \Logger::error("Pimcore_Google_Analytics::trackPageView() is unsupported as of version 2.0.1");
    }

    /**
     * @param $defaultPath
     */
    public static function setDefaultPath($defaultPath)
    {
        self::$defaultPath = $defaultPath;
    }

    /**
     * @return null
     */
    public static function getDefaultPath()
    {
        return self::$defaultPath;
    }
}
