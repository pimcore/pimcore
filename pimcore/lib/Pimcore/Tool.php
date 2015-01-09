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

namespace Pimcore;

use Pimcore\Model\Cache;

class Tool {

    /**
     * @static
     * @param string $key
     * @return bool
     */
    public static function isValidKey($key){
        return (bool) preg_match("/^[a-z0-9_~\.\-]+$/", $key);
    }

    /**
     * @static
     * @param  $path
     * @return bool
     */
    public static function isValidPath($path) {
        return (bool) preg_match("/^[a-zA-Z0-9_~\.\-\/]+$/", $path, $matches);
    }

    /**
     * @static
     * @param  $language
     * @return bool
     */
    public static function isValidLanguage($language) {

        $languages = self::getValidLanguages();

        // if not configured, every language is valid
        if (!$languages) {
            return true;
        }

        if (in_array($language, $languages)) {
            return true;
        }

        return false;
    }

    /**
     * @static
     * @return array
     */
    public static function getValidLanguages() {

        $config = Config::getSystemConfig();
        $validLanguages = strval($config->general->validLanguages);

        if (empty($validLanguages)) {
            return array();
        }

        $validLanguages = str_replace(" ", "", $validLanguages);
        $languages = explode(",", $validLanguages);

        if (!is_array($languages)) {
            $languages = array();
        }

        return $languages;
    }

    /**
     * @param $language
     * @return array
     */
    public static function getFallbackLanguagesFor($language) {

        $languages = array();

        $conf = Config::getSystemConfig();
        if($conf->general->fallbackLanguages && $conf->general->fallbackLanguages->$language) {
            $languages = explode(",", $conf->general->fallbackLanguages->$language);
            foreach ($languages as $l) {
                if(self::isValidLanguage($l)) {
                    $languages[] = trim($l);
                }
            }
        }

        return $languages;
    }

    /**
     * @return null
     */
    public static function getDefaultLanguage() {
        $languages = self::getValidLanguages();
        if(!empty($languages)) {
            return $languages[0];
        }
        return null;
    }

    /**
     * @static
     */
    public static function getSupportedLocales() {

        $locale = \Zend_Locale::findLocale();
        $cacheKey = "system_supported_locales_" . strtolower((string) $locale);
        if(!$languageOptions = Cache::load($cacheKey)) {
            // we use the locale here, because \Zend_Translate only supports locales not "languages"
            $languages = \Zend_Locale::getLocaleList();
            $languageOptions = array();
            foreach ($languages as $code => $active) {
                if($active) {
                    $translation = \Zend_Locale::getTranslation($code, "language");
                    if(!$translation) {
                        $tmpLocale = new \Zend_Locale($code);
                        $lt = \Zend_Locale::getTranslation($tmpLocale->getLanguage(), "language");
                        $tt = \Zend_Locale::getTranslation($tmpLocale->getRegion(), "territory");

                        if($lt && $tt) {
                            $translation = $lt ." (" . $tt . ")";
                        }
                    }

                    if(!$translation) {
                        $translation = $code;
                    }

                    $languageOptions[$code] = $translation;
                }
            }

            asort($languageOptions);

            Cache::save($languageOptions, $cacheKey, ["system"]);
        }

        return $languageOptions;
    }

    /**
     * @param $language
     * @return string
     */
    public static function getLanguageFlagFile($language) {
        $iconBasePath = PIMCORE_PATH . '/static/img/flags';

        $code = strtolower($language);
        $code = str_replace("_","-", $code);
        $countryCode = null;
        $fallbackLanguageCode = null;

        $parts = explode("-", $code);
        if(count($parts) > 1) {
            $countryCode = array_pop($parts);
            $fallbackLanguageCode = $parts[0];
        }

        $languagePath = $iconBasePath . "/languages/" . $code . ".png";
        $countryPath = $iconBasePath . "/countries/" . $countryCode . ".png";
        $fallbackLanguagePath = $iconBasePath . "/languages/" . $fallbackLanguageCode . ".png";

        $iconPath = $iconBasePath . "/countries/_unknown.png";
        if(file_exists($languagePath)) {
            $iconPath = $languagePath;
        } else if($countryCode && file_exists($countryPath)) {
            $iconPath = $countryPath;
        } else if ($fallbackLanguageCode && file_exists($fallbackLanguagePath)) {
            $iconPath = $fallbackLanguagePath;
        }

        return $iconPath;
    }

    /**
     * @static
     * @return array
     */
    public static function getRoutingDefaults() {

        $config = Config::getSystemConfig();

        if($config) {
            // system default
            $routeingDefaults = array(
                "controller" => "default",
                "action" => "default",
                "module" => PIMCORE_FRONTEND_MODULE
            );

            // get configured settings for defaults
            $systemRoutingDefaults = $config->documents->toArray();

            foreach ($routeingDefaults as $key => $value) {
                if ($systemRoutingDefaults["default_" . $key]) {
                    $routeingDefaults[$key] = $systemRoutingDefaults["default_" . $key];
                }
            }

            return $routeingDefaults;
        } else {
            return array();
        }
    }


    /**
     * @static
     * @return bool
     */
    public static function isFrontend() {
        $excludePatterns = array(
            "/^\/admin.*/",
            "/^\/install.*/",
            "/^\/plugin.*/",
            "/^\/webservice.*/"
        );
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $_SERVER["REQUEST_URI"])) {
                return false;
            }
        }

        return true;
    }

    /**
     * eg. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     */
    public static function isFrontentRequestByAdmin () {
        if (array_key_exists("pimcore_editmode", $_REQUEST)
            || array_key_exists("pimcore_preview", $_REQUEST)
            || array_key_exists("pimcore_admin", $_REQUEST)
            || array_key_exists("pimcore_object_preview", $_REQUEST)
            || array_key_exists("pimcore_version", $_REQUEST)
            || preg_match("@^/pimcore_document_tag_renderlet@", $_SERVER["REQUEST_URI"])) {

            return true;
        }

        return false;
    }

    /**
     * @static
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool
     */
    public static function useFrontendOutputFilters(\Zend_Controller_Request_Abstract $request) {

        // check for module
        if (!self::isFrontend()) {
            return false;
        }

        if(self::isFrontentRequestByAdmin()) {
            return false;
        }

        // check for manually disabled ?pimcore_outputfilters_disabled=true
        if ($request->getParam("pimcore_outputfilters_disabled")) {
            return false;
        }


        return true;
    }

    /**
     * @static
     * @return string
     */
    public static function getHostname() {
        if(isset($_SERVER["HTTP_X_FORWARDED_HOST"]) && !empty($_SERVER["HTTP_X_FORWARDED_HOST"])) {
            return $_SERVER["HTTP_X_FORWARDED_HOST"];
        }
        return $_SERVER["HTTP_HOST"];
    }


    /**
     * Returns the host URL
     *
     * @static
     * @return string
     */
    public static function getHostUrl()
        {
            $protocol = "http";
            $port = '';

            if(isset($_SERVER["SERVER_PROTOCOL"])) {
                $protocol = strtolower($_SERVER["SERVER_PROTOCOL"]);
                $protocol = substr($protocol, 0, strpos($protocol, "/"));
                $protocol .= (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "s" : "";
            }

            if(isset($_SERVER["SERVER_PORT"])) {
                if(!in_array((int) $_SERVER["SERVER_PORT"],array(443,80))){
                    $port = ":" . $_SERVER["SERVER_PORT"];
                }
            }

            $hostname = self::getHostname();

            //get it from System settings
            if (!$hostname) {
                $systemConfig = Config::getSystemConfig()->toArray();
                $hostname = $systemConfig['general']['domain'];
                if (!$hostname) {
                    \Logger::warn('Couldn\'t determine HTTP Host. No Domain set in "Settings" -> "System" -> "Website" -> "Domain"');
                    return "";
                }
            }

            return $protocol . "://" . $hostname . $port;
        }


    /**
     * @static
     * @return array|bool
     */
    public static function getCustomViewConfig() {
        $cvConfigFile = PIMCORE_CONFIGURATION_DIRECTORY . "/customviews.xml";
        $cvData = array();

        if (!is_file($cvConfigFile)) {
            $cvData = false;
        }
        else {
            $config = new \Zend_Config_Xml($cvConfigFile);
            $confArray = $config->toArray();

            if (empty($confArray["views"]["view"])) {
                return array();
            }
            else if ($confArray["views"]["view"][0]) {
                $cvData = $confArray["views"]["view"];
            }
            else {
                $cvData[] = $confArray["views"]["view"];
            }

            foreach ($cvData as &$tmp) {
                $tmp["showroot"] = (bool) $tmp["showroot"];
            }
        }
        return $cvData;
    }

    /**
     * @param null $recipients
     * @param null $subject
     * @param null $charset
     * @return Mail
     * @throws \Zend_Mail_Exception
     */
    public static function getMail($recipients = null, $subject = null, $charset = null) {

        $mail = new Mail($charset);

        if($recipients) {
            if(is_string($recipients)) {
                $mail->addTo($recipients);
            } else if(is_array($recipients)){
                foreach($recipients as $recipient){
                    $mail->addTo($recipient);
                }
            }
        }

        if($subject) {
            $mail->setSubject($subject);
        }

        return $mail;
    }


    /**
     * @static
     * @param \Zend_Controller_Response_Abstract $response
     * @return bool
     */
    public static function isHtmlResponse (\Zend_Controller_Response_Abstract $response) {
        // check if response is html
        $headers = $response->getHeaders();
        foreach ($headers as $header) {
            if($header["name"] == "Content-Type") {
                if(strpos($header["value"],"html") === false) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * @param string $type
     * @param array $options
     * @return mixed
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public static function getHttpClient ($type = "Zend_Http_Client",$options = array()) {

        $config = Config::getSystemConfig();
        $clientConfig = $config->httpclient->toArray();
        $clientConfig["adapter"] = $clientConfig["adapter"] ? $clientConfig["adapter"] : "Zend_Http_Client_Adapter_Socket";
        $clientConfig["maxredirects"] = $options["maxredirects"] ? $options["maxredirects"] : 2;
        $clientConfig["timeout"] = $options["timeout"] ? $options["timeout"] : 3600;
        $type = empty($type) ? "Zend_Http_Client" : $type;

        $type = "\\" . ltrim($type, "\\");

        if(self::classExists($type)) {
            $client = new $type(null, $clientConfig);

            // workaround/for ZF (Proxy-authorization isn't added by ZF)
            if ($clientConfig['proxy_user']) {
                $client->setHeaders('Proxy-authorization',  \Zend_Http_Client::encodeAuthHeader(
                    $clientConfig['proxy_user'], $clientConfig['proxy_pass'], \Zend_Http_Client::AUTH_BASIC
                    ));
            }
        } else {
            throw new \Exception("Pimcore_Tool::getHttpClient: Unable to create an instance of $type");
        }

        return $client;
    }

    /**
     * @static
     * @param $url
     * @param array $paramsGet
     * @param array $paramsPost
     * @return bool|string
     */
    public static function getHttpData($url, $paramsGet = array(), $paramsPost = array()) {
        
        $client = self::getHttpClient();
        $client->setUri($url);
        $requestType = \Zend_Http_Client::GET;

        if(is_array($paramsGet) && count($paramsGet) > 0) {
            foreach ($paramsGet as $key => $value) {
                $client->setParameterGet($key, $value);
            }
        }

        if(is_array($paramsPost) && count($paramsPost) > 0) {
            foreach ($paramsPost as $key => $value) {
                $client->setParameterPost($key, $value);
            }

            $requestType = \Zend_Http_Client::POST;
        }

        try {
            $response = $client->request($requestType);

            if ($response->isSuccessful()) {
                return $response->getBody();
            }
        } catch (\Exception $e) {

        }

        return false;
    }


    /*
     * Class Mapping Tools
     * They are used to map all instances of \Element_Interface to an defined class (type)
     */

    /**
     * @static
     * @param  $sourceClassName
     * @return string
     */
    public static function getModelClassMapping($sourceClassName) {

        $targetClassName = $sourceClassName;
        $lookupName = str_replace(["\\Pimcore\\Model\\", "\\"], ["", "_"], $sourceClassName);
        $lookupName = ltrim($lookupName, "\\_");

        if($map = Config::getModelClassMappingConfig()) {
            $tmpClassName = $map->{$lookupName};
            if($tmpClassName) {
                $tmpClassName = "\\" . ltrim($tmpClassName, "\\");
                if(self::classExists($tmpClassName)) {
                    if(is_subclass_of($tmpClassName, $sourceClassName)) {
                        $targetClassName = "\\" . ltrim($tmpClassName, "\\"); // ensure class is in global namespace
                    } else {
                        \Logger::error("Classmapping for " . $sourceClassName . " failed. '" . $tmpClassName . " is not a subclass of '" . $sourceClassName . "'. " . $tmpClassName . " has to extend " . $sourceClassName);
                    }
                } else {
                    \Logger::error("Classmapping for " . $sourceClassName . " failed. Cannot find class '" . $tmpClassName . "'");
                }
            }
        }

        return $targetClassName;
    }

    /**
     * @static
     * @return void
     */
    public static function registerClassModelMappingNamespaces () {

        $autoloader = \Zend_Loader_Autoloader::getInstance();
        if($map = Config::getModelClassMappingConfig()) {
            $map = $map->toArray();

            foreach ($map as $targetClass) {
                $classParts = explode("_", $targetClass);
                $autoloader->registerNamespace($classParts[0]);
            }
        }
    }

    /**
     * @static
     * @return mixed
     */
    public static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $ips = explode(",", $ip);
        $ip = trim(array_shift($ips));

        return $ip;
    }

    /**
     * @return string
     */
    public static function getAnonymizedClientIp() {
        $ip = self::getClientIp();
        $aip = substr($ip, 0, strrpos($ip, ".")+1);
        $aip .= "255";
        return $aip;
    }

    /**
     * @static
     * @param $class
     * @return bool
     */
    public static function classExists ($class) {

        // we need to set a custom error handler here for the time being
        // unfortunately suppressNotFoundWarnings() doesn't work all the time, it has something to do with the calls in
        // Pimcore\Tool::ClassMapAutoloader(), but don't know what actual conditions causes this problem.
        // but to be save we log the errors into the debug.log, so if anything else happens we can see it there
        // the normal warning is e.g. Warning: include_once(Path/To/Class.php): failed to open stream: No such file or directory in ...
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            \Logger::debug(implode(" ", func_get_args()));
        });

        \Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
        $class = "\\" . ltrim($class, "\\");
        $exists = class_exists($class);
        \Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);

        restore_error_handler();

        return $exists;
    }

    /**
     * @static
     * @param $class
     * @return bool
     */
    public static function interfaceExists ($class) {

        // we need to set a custom error handler here for the time being
        // unfortunately suppressNotFoundWarnings() doesn't work all the time, it has something to do with the calls in
        // Pimcore\Tool::ClassMapAutoloader(), but don't know what actual conditions causes this problem.
        // but to be save we log the errors into the debug.log, so if anything else happens we can see it there
        // the normal warning is e.g. Warning: include_once(Path/To/Class.php): failed to open stream: No such file or directory in ...
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            \Logger::debug(implode(" ", func_get_args()));
        });

        \Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
        $class = "\\" . ltrim($class, "\\");
        $exists = interface_exists($class);
        \Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);

        restore_error_handler();

        return $exists;
    }

    /**
     * @param $message
     */
    public static function exitWithError($message) {

        while (@ob_end_flush());

        header('HTTP/1.1 503 Service Temporarily Unavailable');
        die($message);
    }

}
