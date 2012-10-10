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


class Pimcore_Tool {

    /**
     * @static
     * @param string $key
     * @return void
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
        if (preg_match("/[a-zA-Z0-9_~\.\-\/]+/", $path, $matches)) {
            if ($matches[0] == $path) {
                return true;
            }
        }
        return false;
    }

    /**
     * @static
     * @param  $element
     * @return string
     */
    public static function getIdPathForElement($element) {

        $path = "";

        if ($element instanceof Document) {
            $nid = $element->getParentId();
            $ne = Document::getById($nid);
        }
        else if ($element instanceof Asset) {
            $nid = $element->getParentId();
            $ne = Asset::getById($nid);
        }
        else if ($element instanceof Object_Abstract) {
            $nid = $element->getO_parentId();
            $ne = Object_Abstract::getById($nid);
        }

        if ($ne) {
            $path = self::getIdPathForElement($ne, $path);
        }

        if ($element) {
            $path = $path . "/" . $element->getId();
        }

        return $path;
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

        $config = Pimcore_Config::getSystemConfig();
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
     * @static
     */
    public static function getSupportedLocales() {

        // we use the locale here, because Zend_Translate only supports locales not "languages"
        $languages = Zend_Locale::getLocaleList();
        $languageOptions = array();
        foreach ($languages as $code => $active) {
            if($active) {
                $translation = Zend_Locale::getTranslation($code, "language");
                if(!$translation) {
                    $tmpLocale = new Zend_Locale($code);
                    $lt = Zend_Locale::getTranslation($tmpLocale->getLanguage(), "language");
                    $tt = Zend_Locale::getTranslation($tmpLocale->getRegion(), "territory");

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

        return $languageOptions;
    }

    /**
     * @static
     * @return array
     */
    public static function getRoutingDefaults() {

        $config = Pimcore_Config::getSystemConfig();

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
            || array_key_exists("pimcore_version", $_REQUEST)) {

            return true;
        }

        return false;
    }

    /**
     * @static
     * @param Zend_Controller_Request_Abstract $request
     * @return bool
     */
    public static function useFrontendOutputFilters(Zend_Controller_Request_Abstract $request) {

        // check for module
        if (!self::isFrontend()) {
            return false;
        }

        if(Pimcore_Tool::isFrontentRequestByAdmin()) {
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
            $protocol = strtolower($_SERVER["SERVER_PROTOCOL"]);
            $protocol = substr($protocol, 0, strpos($protocol, "/"));
            $protocol .= ($_SERVER["HTTPS"] == "on") ? "s" : "";

            if(!in_array($_SERVER["SERVER_PORT"],array(443,80))){
                $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
            }


            $hostname = self::getHostname();

            //get it from System settings
            if (!$hostname) {
                $systemConfig = Pimcore_Config::getSystemConfig()->toArray();
                $hostname = $systemConfig['general']['domain'];
                if (!$hostname) {
                    Logger::warn('Couldn\'t determine HTTP Host. No Domain set in "Settings" -> "System" -> "Website" -> "Domain"');
                } else {
                    $protocol   = 'http';
                    $port       = '';
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
            $config = new Zend_Config_Xml($cvConfigFile);
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
     * @static
     * @param  $sender
     * @param  $recipients
     * @param  $subject
     * @return Pimcore_Mail
     */
    public static function getMail($recipients = null, $subject = null, $charset = null) {

        $mail = new Pimcore_Mail($charset);

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
     * @param Zend_Controller_Response_Abstract $response
     * @return bool
     */
    public static function isHtmlResponse (Zend_Controller_Response_Abstract $response) {
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
     * @static
     * @throws Exception
     * @param string $type
     * @return Zend_Http_Client
     */
    public static function getHttpClient ($type = "Zend_Http_Client") {

        $config = Pimcore_Config::getSystemConfig();
        $clientConfig = $config->httpclient->toArray();
        $clientConfig["maxredirects"] = 0;
        $clientConfig["timeout"] = 3600;
        $type = empty($type) ? "Zend_Http_Client" : $type;

        if(Pimcore_Tool::classExists($type)) {
            $client = new $type(null, $clientConfig);
        } else {
            throw new Exception("Pimcore_Tool::getHttpClient: Unable to create an instance of $type");
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
        $requestType = Zend_Http_Client::GET;

        if(is_array($paramsGet) && count($paramsGet) > 0) {
            foreach ($paramsGet as $key => $value) {
                $client->setParameterGet($key, $value);
            }
        }

        if(is_array($paramsPost) && count($paramsPost) > 0) {
            foreach ($paramsPost as $key => $value) {
                $client->setParameterPost($key, $value);
            }

            $requestType = Zend_Http_Client::POST;
        }

        $response = $client->request($requestType);

        if ($response->isSuccessful()) {
            return $response->getBody();
        }

        return false;
    }


    /*
     * Class Mapping Tools
     * They are used to map all instances of Element_Interface to an defined class (type)
     */

    /**
     * @static
     * @param  $sourceClassName
     * @return string
     */
    public static function getModelClassMapping($sourceClassName) {

        $targetClassName = $sourceClassName;

        if($map = Pimcore_Config::getModelClassMappingConfig()) {
            $tmpClassName = $map->{$sourceClassName};
            if($tmpClassName) {
                if(Pimcore_Tool::classExists($tmpClassName)) {
                    if(is_subclass_of($tmpClassName, $sourceClassName)) {
                        $targetClassName = $tmpClassName;
                    }
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

        $autoloader = Zend_Loader_Autoloader::getInstance();
        if($map = Pimcore_Config::getModelClassMappingConfig()) {
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

        return $ip;
    }

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
        Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
        $exists = class_exists($class);
        Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);

        return $exists;
    }
}
