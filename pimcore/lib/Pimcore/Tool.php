<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\Container;

class Tool
{

    /**
     * @var array
     */
    protected static $notFoundClassNames = [];

    /**
     * @var array
     */
    protected static $validLanguages = [];

    /**
     * @var null
     */
    protected static $isFrontend = null;

    /**
     * returns a valid cache key/tag string
     *
     * @param string $key
     * @return string
     */
    public static function getValidCacheKey($key)
    {
        return preg_replace("/[^a-zA-Z0-9]/", "_", $key);
    }

    /**
     * @static
     * @param  $path
     * @return bool
     */
    public static function isValidPath($path)
    {
        return (bool) preg_match("/^[a-zA-Z0-9_~\.\-\/ ]+$/", $path, $matches);
    }

    /**
     * Checks, if the given language is configured in pimcore's system
     * settings at "Localization & Internationalization (i18n/l10n)".
     * Returns true, if the language is valid or no language is
     * configured at all, false otherwise.
     *
     * @static
     * @param  string $language
     * @return bool
     */
    public static function isValidLanguage($language)
    {
        $language = (string) $language; // cast to string
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
     * Returns an array of language codes that configured for this system
     * in pimcore's system settings at "Localization & Internationalization (i18n/l10n)".
     * An empty array is returned if no languages are configured.
     *
     * @static
     * @return string[]
     */
    public static function getValidLanguages()
    {
        if (empty(self::$validLanguages)) {
            $config = Config::getSystemConfig();
            $validLanguages = strval($config->general->validLanguages);

            if (empty($validLanguages)) {
                return [];
            }

            $validLanguages = str_replace(" ", "", $validLanguages);
            $languages = explode(",", $validLanguages);

            if (!is_array($languages)) {
                $languages = [];
            }

            self::$validLanguages = $languages;
        }

        return self::$validLanguages;
    }

    /**
     * @param $language
     * @return array
     */
    public static function getFallbackLanguagesFor($language)
    {
        $languages = [];

        $conf = Config::getSystemConfig();
        if ($conf->general->fallbackLanguages && $conf->general->fallbackLanguages->$language) {
            $fallbackLanguages = explode(",", $conf->general->fallbackLanguages->$language);
            foreach ($fallbackLanguages as $l) {
                if (self::isValidLanguage($l)) {
                    $languages[] = trim($l);
                }
            }
        }

        return $languages;
    }

    /**
     * Returns the default language for this system. If no default is set,
     * returns the first language, or null, if no languages are configured
     * at all.
     *
     * @return null|string
     */
    public static function getDefaultLanguage()
    {
        $config = Config::getSystemConfig();
        $defaultLanguage = $config->general->defaultLanguage;
        $languages = self::getValidLanguages();

        if (!empty($languages) && in_array($defaultLanguage, $languages)) {
            return $defaultLanguage;
        } elseif (!empty($languages)) {
            return $languages[0];
        }

        return null;
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public static function getSupportedLocales()
    {
        $localeService = \Pimcore::getContainer()->get("pimcore.locale");
        $locale = $localeService->findLocale();

        $cacheKey = "system_supported_locales_" . strtolower((string) $locale);
        if (!$languageOptions = Cache::load($cacheKey)) {
            $languages = $localeService->getLocaleList();

            $languageOptions = [];
            foreach ($languages as $code) {
                $translation = \Locale::getDisplayLanguage($code, $locale);
                $displayRegion = \Locale::getDisplayRegion($code, $locale);

                if ($displayRegion) {
                    $translation .= " (" . $displayRegion . ")";
                }

                if (!$translation) {
                    $translation = $code;
                }

                $languageOptions[$code] = $translation;
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
    public static function getLanguageFlagFile($language)
    {
        $relativePath = '/pimcore/static6/img/flags';
        $iconWebBasePath = PIMCORE_PROJECT_ROOT . $relativePath;
        $iconFsBasePath = PIMCORE_WEB_ROOT . $relativePath;

        $code = strtolower($language);
        $code = str_replace("_", "-", $code);
        $countryCode = null;
        $fallbackLanguageCode = null;

        $parts = explode("-", $code);
        if (count($parts) > 1) {
            $countryCode = array_pop($parts);
            $fallbackLanguageCode = $parts[0];
        }

        $languageFsPath = $iconFsBasePath . "/languages/" . $code . ".svg";
        $countryFsPath = $iconFsBasePath . "/countries/" . $countryCode . ".svg";
        $fallbackFsLanguagePath = $iconFsBasePath . "/languages/" . $fallbackLanguageCode . ".svg";

        $iconPath = $iconFsBasePath . "/countries/_unknown.svg";

        $languageCountryMapping = [
            "aa" => "er", "af" => "za", "am" => "et", "as" => "in", "ast" => "es", "asa" => "tz",
            "az" => "az", "bas" => "cm", "eu" => "es", "be" => "by", "bem" => "zm", "bez" => "tz", "bg" => "bg",
            "bm" => "ml", "bn" => "bd", "br" => "fr", "brx" => "in", "bs" => "ba", "cs" => "cz", "da" => "dk",
            "de" => "de", "dz" => "bt", "el" => "gr", "en" => "gb", "es" => "es", "et" => "ee", "fi" => "fi",
            "fo" => "fo", "fr" => "fr", "ga" => "ie", "gv" => "im", "he" => "il", "hi" => "in", "hr" => "hr",
            "hu" => "hu", "hy" => "am", "id" => "id", "ig" => "ng", "is" => "is", "it" => "it", "ja" => "jp",
            "ka" => "ge", "os" => "ge", "kea" => "cv", "kk" => "kz", "kl" => "gl", "km" => "kh", "ko" => "kr",
            "lg" => "ug", "lo" => "la", "lt" => "lv", "mg" => "mg", "mk" => "mk", "mn" => "mn", "ms" => "my",
            "mt" => "mt", "my" => "mm", "nb" => "no", "ne" => "np", "nl" => "nl", "nn" => "no", "pl" => "pl",
            "pt" => "pt", "ro" => "ro", "ru" => "ru", "sg" => "cf", "sk" => "sk", "sl" => "si", "sq" => "al",
            "sr" => "rs", "sv" => "se", "swc" => "cd", "th" => "th", "to" => "to", "tr" => "tr", "tzm" => "ma",
            "uk" => "ua", "uz" => "uz", "vi" => "vn", "zh" => "cn", "gd" => "gb-sct", "gd-gb" => "gb-sct",
            "cy" => "gb-wls", "cy-gb" => "gb-wls", "fy" => "nl", "xh" => "za", "yo" => "bj", "zu" => "za",
            "ta" => "lk", "te" => "in", "ss" => "za", "sw" => "ke", "so" => "so", "si" => "lk", "ii" => "cn",
            "zh-hans" => "cn", "sn" => "zw", "rm" => "ch", "pa" => "in", "fa" => "ir", "lv" => "lv", "gl" => "es",
            "fil" => "ph"
        ];

        if (array_key_exists($code, $languageCountryMapping)) {
            $iconPath = $iconFsBasePath . "/countries/" . $languageCountryMapping[$code] . ".svg";
        } elseif (file_exists($languageFsPath)) {
            $iconPath = $languageFsPath;
        } elseif ($countryCode && file_exists($countryFsPath)) {
            $iconPath = $iconFsBasePath . "/countries/" . $countryCode . ".svg";
        } elseif ($fallbackLanguageCode && file_exists($fallbackFsLanguagePath)) {
            $iconPath = $iconFsBasePath . "/languages/" . $fallbackLanguageCode . ".svg";
        }

        return $iconPath;
    }

    /**
     * @static
     * @return array
     */
    public static function getRoutingDefaults()
    {
        $config = Config::getSystemConfig();

        if ($config) {
            // system default
            $routeingDefaults = [
                "controller" => "default",
                "action" => "default",
                "module" => PIMCORE_FRONTEND_MODULE
            ];

            // get configured settings for defaults
            $systemRoutingDefaults = $config->documents->toArray();

            foreach ($routeingDefaults as $key => $value) {
                if (isset($systemRoutingDefaults["default_" . $key]) && $systemRoutingDefaults["default_" . $key]) {
                    $routeingDefaults[$key] = $systemRoutingDefaults["default_" . $key];
                }
            }

            return $routeingDefaults;
        } else {
            return [];
        }
    }


    /**
     * @static
     * @return bool
     */
    public static function isFrontend()
    {
        if (self::$isFrontend !== null) {
            return self::$isFrontend;
        }

        $isFrontend = true;

        if ($isFrontend && php_sapi_name() == "cli") {
            $isFrontend = false;
        }

        if ($isFrontend && \Pimcore::inAdmin()) {
            $isFrontend = false;
        }

        if ($isFrontend) {
            $excludePatterns = [
                "/^\/admin.*/",
                "/^\/install.*/",
                "/^\/plugin.*/",
                "/^\/webservice.*/"
            ];

            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $_SERVER["REQUEST_URI"])) {
                    $isFrontend = false;
                    break;
                }
            }
        }

        self::$isFrontend = $isFrontend;

        return $isFrontend;
    }

    /**
     * @return bool
     */
    public static function isInstaller()
    {
        if (preg_match("@^/install@", $_SERVER["REQUEST_URI"])) {
            return true;
        }

        return false;
    }

    /**
     * eg. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     */
    public static function isFrontentRequestByAdmin()
    {
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
     * @return bool
     */
    public static function useFrontendOutputFilters()
    {

        // check for module
        if (!self::isFrontend()) {
            return false;
        }

        if (self::isFrontentRequestByAdmin()) {
            return false;
        }

        // check for manually disabled ?pimcore_outputfilters_disabled=true
        if (array_key_exists("pimcore_outputfilters_disabled", $_REQUEST) && PIMCORE_DEBUG) {
            return false;
        }


        return true;
    }

    /**
     * @static
     * @return string
     */
    public static function getHostname()
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_HOST"]) && !empty($_SERVER["HTTP_X_FORWARDED_HOST"])) {
            $hostParts = explode(",", $_SERVER["HTTP_X_FORWARDED_HOST"]);
            $hostname = trim(end($hostParts));
        } else {
            $hostname = $_SERVER["HTTP_HOST"];
        }

        // remove port if set
        if (strpos($hostname, ":") !== false) {
            $hostname = preg_replace("@:[0-9]+@", "", $hostname);
        }

        return $hostname;
    }

    /**
     * @return string
     */
    public static function getRequestScheme()
    {
        $requestScheme = "http";
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $requestScheme = "https";
        }

        return $requestScheme;
    }

    /**
     * Returns the host URL
     *
     * @param string $useProtocol use a specific protocol
     *
     * @return string
     */
    public static function getHostUrl($useProtocol = null)
    {
        $protocol = self::getRequestScheme();
        $port = '';

        if (isset($_SERVER["SERVER_PORT"])) {
            if (!in_array((int) $_SERVER["SERVER_PORT"], [443, 80])) {
                $port = ":" . $_SERVER["SERVER_PORT"];
            }
        }

        $hostname = self::getHostname();

        //get it from System settings
        if (!$hostname) {
            $systemConfig = Config::getSystemConfig()->toArray();
            $hostname = $systemConfig['general']['domain'];
            if (!$hostname) {
                Logger::warn('Couldn\'t determine HTTP Host. No Domain set in "Settings" -> "System" -> "Website" -> "Domain"');

                return "";
            }
        }

        if ($useProtocol) {
            $protocol = $useProtocol;
        }

        return $protocol . "://" . $hostname . $port;
    }


    /**
     * @static
     * @return array|bool
     */
    public static function getCustomViewConfig()
    {
        $configFile = \Pimcore\Config::locateConfigFile("customviews.php");

        if (!is_file($configFile)) {
            $cvData = false;
        } else {
            $confArray = include($configFile);
            $cvData = [];

            foreach ($confArray["views"] as $tmp) {
                if (isset($tmp["name"])) {
                    $tmp["showroot"] = (bool) $tmp["showroot"];

                    if ((bool) $tmp["hidden"]) {
                        continue;
                    }

                    $cvData[] = $tmp;
                }
            }
        }

        return $cvData;
    }

    /**
     * @param null $recipients
     * @param null $subject
     * @param null $charset
     * @return Mail
     * @throws \Exception
     */
    public static function getMail($recipients = null, $subject = null, $charset = null)
    {
        $mail = new Mail();
        $mail->setCharset($charset);

        if ($recipients) {
            if (is_string($recipients)) {
                $mail->addTo($recipients);
            } elseif (is_array($recipients)) {
                foreach ($recipients as $recipient) {
                    $mail->addTo($recipient);
                }
            }
        }

        if ($subject) {
            $mail->setSubject($subject);
        }

        return $mail;
    }



    /**
     * @static
     * @param $url
     * @param array $paramsGet
     * @param array $paramsPost
     * @return bool|string
     */
    public static function getHttpData($url, $paramsGet = [], $paramsPost = [])
    {
        $client = \Pimcore::getContainer()->get("pimcore.http_client");
        $requestType = 'GET';

        $config = [];

        if (is_array($paramsGet) && count($paramsGet) > 0) {

            //need to insert get params from url to $paramsGet because otherwise the would be ignored
            $urlParts = parse_url($url);
            $urlParams = [];
            parse_str($urlParts['query'], $urlParams);

            if ($urlParams) {
                $paramsGet = array_merge($urlParams, $paramsGet);
            }

            $config[RequestOptions::QUERY] = $paramsGet;
        }

        if (is_array($paramsPost) && count($paramsPost) > 0) {
            $config[RequestOptions::FORM_PARAMS] = $paramsPost;
            $requestType = 'POST';
        }

        try {
            $response = $client->request($requestType, $url, $config);

            if ($response->getStatusCode() < 300) {
                return (string)$response->getBody();
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @static
     * @param  $sourceClassName
     * @return string
     *
     * @deprecated
     */
    public static function getModelClassMapping($sourceClassName)
    {
        try {
            return get_class(\Pimcore::getDiContainer()->get($sourceClassName));
        } catch (\Exception $ex) {
        }

        return $sourceClassName;
    }

    /**
     * @deprecated Use Request::getClientIp() instead
     *
     * @static
     * @return mixed
     */
    public static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $ips = explode(",", $ip);
        $ip = trim(array_shift($ips));

        return $ip;
    }

    /**
     * @deprecated Use RequestHelper::getAnonymizedClientIp() instead
     *
     * @return string
     */
    public static function getAnonymizedClientIp()
    {
        $ip = self::getClientIp();
        $aip = substr($ip, 0, strrpos($ip, ".")+1);
        $aip .= "255";

        return $aip;
    }

    /**
     * @param Container|null $container
     */
    public static function clearSymfonyCache(Container $container = null)
    {
        if (!$container) {
            $container = \Pimcore::getContainer();
        }

        $realCacheDir = $container->getParameter('kernel.cache_dir');
        // the old cache dir name must not be longer than the real one to avoid exceeding
        // the maximum length of a directory or file path within it (esp. Windows MAX_PATH)
        $oldCacheDir = substr($realCacheDir, 0, -1).('~' === substr($realCacheDir, -1) ? '+' : '~');
        $filesystem = $container->get('filesystem');
        if ($filesystem->exists($oldCacheDir)) {
            $filesystem->remove($oldCacheDir);
        }

        $container->get('cache_clearer')->clear($realCacheDir);
        $filesystem->rename($realCacheDir, $oldCacheDir);
        $filesystem->remove($oldCacheDir);
    }

    /**
     * @static
     * @param $class
     * @return bool
     */
    public static function classExists($class)
    {
        return self::classInterfaceExists($class, "class");
    }

    /**
     * @static
     * @param $class
     * @return bool
     */
    public static function interfaceExists($class)
    {
        return self::classInterfaceExists($class, "interface");
    }

    /**
     * @param $class
     * @param $type
     * @return bool
     */
    protected static function classInterfaceExists($class, $type)
    {
        $functionName = $type . "_exists";

        // if the class is already loaded we can skip right here
        if ($functionName($class, false)) {
            return true;
        }

        $class = "\\" . ltrim($class, "\\");

        // let's test if we have seens this class already before
        if (isset(self::$notFoundClassNames[$class])) {
            return false;
        }

        // we need to set a custom error handler here for the time being
        // unfortunately suppressNotFoundWarnings() doesn't work all the time, it has something to do with the calls in
        // Pimcore\Tool::ClassMapAutoloader(), but don't know what actual conditions causes this problem.
        // but to be save we log the errors into the debug.log, so if anything else happens we can see it there
        // the normal warning is e.g. Warning: include_once(Path/To/Class.php): failed to open stream: No such file or directory in ...
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            //Logger::debug(implode(" ", [$errno, $errstr, $errfile, $errline]));
        });

        $exists = $functionName($class);

        restore_error_handler();

        if (!$exists) {
            self::$notFoundClassNames[$class] = true; // value doesn't matter, key lookups are faster ;-)
        }

        return $exists;
    }

    /**
     * @param $message
     */
    public static function exitWithError($message)
    {
        while (@ob_end_flush());

        if (php_sapi_name() != "cli") {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
        }

        die($message);
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        if (class_exists("Pimcore\\Tool\\Legacy")) {
            return forward_static_call_array("Pimcore\\Tool\\Legacy::" . $name, $arguments);
        }

        throw new \Exception("Call to undefined static method " . $name . " on class Pimcore\\Tool");
    }
}
