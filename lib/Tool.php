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
use Symfony\Component\HttpFoundation\Request;

class Tool
{
    /**
     * Sets the current request to use when resolving request at early
     * stages (before container is loaded)
     *
     * @var Request
     */
    private static $currentRequest;

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
     * Sets the current request to operate on
     *
     * @param Request|null $request
     */
    public static function setCurrentRequest(Request $request = null)
    {
        self::$currentRequest = $request;
    }

    /**
     * returns a valid cache key/tag string
     *
     * @param string $key
     *
     * @return string
     */
    public static function getValidCacheKey($key)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', $key);
    }

    /**
     * @static
     *
     * @param string $path
     *
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
     *
     * @param  string $language
     *
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
     *
     * @return string[]
     */
    public static function getValidLanguages()
    {
        if (empty(self::$validLanguages)) {
            $config = Config::getSystemConfiguration('general');

            if (empty($config['valid_languages'])) {
                return [];
            }

            $validLanguages = str_replace(' ', '', strval($config['valid_languages']));
            $languages = explode(',', $validLanguages);

            if (!is_array($languages)) {
                $languages = [];
            }

            self::$validLanguages = $languages;
        }

        return self::$validLanguages;
    }

    /**
     * @param string $language
     *
     * @return array
     */
    public static function getFallbackLanguagesFor($language)
    {
        $languages = [];

        $config = Config::getSystemConfiguration('general');
        if (!empty($config['fallback_languages'][$language])) {
            $fallbackLanguages = explode(',', $config['fallback_languages'][$language]);
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
        $config = Config::getSystemConfiguration('general');
        $defaultLanguage = $config['default_language'] ?? null;
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
     *
     * @throws \Exception
     */
    public static function getSupportedLocales()
    {
        $localeService = \Pimcore::getContainer()->get('pimcore.locale');
        $locale = $localeService->findLocale();

        $cacheKey = 'system_supported_locales_' . strtolower((string) $locale);
        if (!$languageOptions = Cache::load($cacheKey)) {
            $languages = $localeService->getLocaleList();

            $languageOptions = [];
            foreach ($languages as $code) {
                $translation = \Locale::getDisplayLanguage($code, $locale);
                $displayRegion = \Locale::getDisplayRegion($code, $locale);

                if ($displayRegion) {
                    $translation .= ' (' . $displayRegion . ')';
                }

                if (!$translation) {
                    $translation = $code;
                }

                $languageOptions[$code] = $translation;
            }

            asort($languageOptions);

            Cache::save($languageOptions, $cacheKey, ['system']);
        }

        return $languageOptions;
    }

    /**
     * @param string $language
     * @param bool $absolutePath
     *
     * @return string
     */
    public static function getLanguageFlagFile($language, $absolutePath = true)
    {
        $basePath = '/bundles/pimcoreadmin/img/flags';
        $iconFsBasePath = PIMCORE_WEB_ROOT . $basePath;

        if ($absolutePath === true) {
            $basePath = PIMCORE_WEB_ROOT . $basePath;
        }

        $code = strtolower($language);
        $code = str_replace('_', '-', $code);
        $countryCode = null;
        $fallbackLanguageCode = null;

        $parts = explode('-', $code);
        if (count($parts) > 1) {
            $countryCode = array_pop($parts);
            $fallbackLanguageCode = $parts[0];
        }

        $languageFsPath = $iconFsBasePath . '/languages/' . $code . '.svg';
        $countryFsPath = $iconFsBasePath . '/countries/' . $countryCode . '.svg';
        $fallbackFsLanguagePath = $iconFsBasePath . '/languages/' . $fallbackLanguageCode . '.svg';

        $iconPath = ($absolutePath === true ? $iconFsBasePath : $basePath) . '/countries/_unknown.svg';

        $languageCountryMapping = [
            'aa' => 'er', 'af' => 'za', 'am' => 'et', 'as' => 'in', 'ast' => 'es', 'asa' => 'tz',
            'az' => 'az', 'bas' => 'cm', 'eu' => 'es', 'be' => 'by', 'bem' => 'zm', 'bez' => 'tz', 'bg' => 'bg',
            'bm' => 'ml', 'bn' => 'bd', 'br' => 'fr', 'brx' => 'in', 'bs' => 'ba', 'cs' => 'cz', 'da' => 'dk',
            'de' => 'de', 'dz' => 'bt', 'el' => 'gr', 'en' => 'gb', 'es' => 'es', 'et' => 'ee', 'fi' => 'fi',
            'fo' => 'fo', 'fr' => 'fr', 'ga' => 'ie', 'gv' => 'im', 'he' => 'il', 'hi' => 'in', 'hr' => 'hr',
            'hu' => 'hu', 'hy' => 'am', 'id' => 'id', 'ig' => 'ng', 'is' => 'is', 'it' => 'it', 'ja' => 'jp',
            'ka' => 'ge', 'os' => 'ge', 'kea' => 'cv', 'kk' => 'kz', 'kl' => 'gl', 'km' => 'kh', 'ko' => 'kr',
            'lg' => 'ug', 'lo' => 'la', 'lt' => 'lt', 'mg' => 'mg', 'mk' => 'mk', 'mn' => 'mn', 'ms' => 'my',
            'mt' => 'mt', 'my' => 'mm', 'nb' => 'no', 'ne' => 'np', 'nl' => 'nl', 'nn' => 'no', 'pl' => 'pl',
            'pt' => 'pt', 'ro' => 'ro', 'ru' => 'ru', 'sg' => 'cf', 'sk' => 'sk', 'sl' => 'si', 'sq' => 'al',
            'sr' => 'rs', 'sv' => 'se', 'swc' => 'cd', 'th' => 'th', 'to' => 'to', 'tr' => 'tr', 'tzm' => 'ma',
            'uk' => 'ua', 'uz' => 'uz', 'vi' => 'vn', 'zh' => 'cn', 'gd' => 'gb-sct', 'gd-gb' => 'gb-sct',
            'cy' => 'gb-wls', 'cy-gb' => 'gb-wls', 'fy' => 'nl', 'xh' => 'za', 'yo' => 'bj', 'zu' => 'za',
            'ta' => 'lk', 'te' => 'in', 'ss' => 'za', 'sw' => 'ke', 'so' => 'so', 'si' => 'lk', 'ii' => 'cn',
            'zh-hans' => 'cn', 'sn' => 'zw', 'rm' => 'ch', 'pa' => 'in', 'fa' => 'ir', 'lv' => 'lv', 'gl' => 'es',
            'fil' => 'ph',
        ];

        if (array_key_exists($code, $languageCountryMapping)) {
            $iconPath = $basePath . '/countries/' . $languageCountryMapping[$code] . '.svg';
        } elseif (file_exists($languageFsPath)) {
            $iconPath = $basePath . '/languages/' . $code . '.svg';
        } elseif ($countryCode && file_exists($countryFsPath)) {
            $iconPath = $basePath . '/countries/' . $countryCode . '.svg';
        } elseif ($fallbackLanguageCode && file_exists($fallbackFsLanguagePath)) {
            $iconPath = $basePath . '/languages/' . $fallbackLanguageCode . '.svg';
        }

        return $iconPath;
    }

    /**
     * @static
     *
     * @return array
     */
    public static function getRoutingDefaults()
    {
        $container = \Pimcore::getContainer();
        $routingDefaults = $container->getParameter('pimcore.routing.defaults');
        $routingDefaults['module'] = $routingDefaults['bundle'];

        return $routingDefaults;
    }

    /**
     * @param Request|null $request
     *
     * @return null|Request
     */
    public static function resolveRequest(Request $request = null)
    {
        if (null === $request) {
            // do an extra check for the container as we might be in a state where no container is set yet
            if (\Pimcore::hasContainer()) {
                $request = \Pimcore::getContainer()->get('request_stack')->getMasterRequest();
            } else {
                if (null !== self::$currentRequest) {
                    return self::$currentRequest;
                }
            }
        }

        return $request;
    }

    /**
     * @static
     *
     * @param Request|null $request
     *
     * @return bool
     */
    public static function isFrontend(Request $request = null): bool
    {
        if (null === $request) {
            $request = \Pimcore::getContainer()->get('request_stack')->getMasterRequest();
        }

        if (null === $request) {
            return false;
        }

        return \Pimcore::getContainer()
            ->get('pimcore.http.request_helper')
            ->isFrontendRequest($request);
    }

    /**
     * eg. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     *
     * @param Request|null $request
     *
     * @return bool
     */
    public static function isFrontendRequestByAdmin(Request $request = null)
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return false;
        }

        return \Pimcore::getContainer()
            ->get('pimcore.http.request_helper')
            ->isFrontendRequestByAdmin($request);
    }

    /**
     * @static
     *
     * @param Request|null $request
     *
     * @return bool
     */
    public static function useFrontendOutputFilters(Request $request = null)
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return false;
        }

        if (!self::isFrontend($request)) {
            return false;
        }

        if (self::isFrontendRequestByAdmin($request)) {
            return false;
        }

        $requestKeys = array_merge(
            array_keys($request->query->all()),
            array_keys($request->request->all())
        );

        // check for manually disabled ?pimcore_outputfilters_disabled=true
        if (array_key_exists('pimcore_outputfilters_disabled', $requestKeys) && \Pimcore::inDebugMode()) {
            return false;
        }

        return true;
    }

    /**
     * @static
     *
     * @param Request|null $request
     *
     * @return null|string
     */
    public static function getHostname(Request $request = null)
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return null;
        }

        return $request->getHost();
    }

    /**
     * @return string
     */
    public static function getRequestScheme(Request $request = null)
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return 'http';
        }

        return $request->getScheme();
    }

    /**
     * Returns the host URL
     *
     * @param string $useProtocol use a specific protocol
     * @param Request|null $request
     *
     * @return string
     */
    public static function getHostUrl($useProtocol = null, Request $request = null)
    {
        $request = self::resolveRequest($request);

        $protocol = 'http';
        $hostname = '';
        $port = '';

        if (null !== $request) {
            $protocol = $request->getScheme();
            $hostname = $request->getHost();

            if (!in_array($request->getPort(), [443, 80])) {
                $port = ':' . $request->getPort();
            }
        }

        // get it from System settings
        if (!$hostname || $hostname == 'localhost') {
            $systemConfig = Config::getSystemConfiguration('general');
            $hostname = $systemConfig['domain'] ?? null;

            if (!$hostname) {
                Logger::warn('Couldn\'t determine HTTP Host. No Domain set in "Settings" -> "System" -> "Website" -> "Domain"');

                return '';
            }
        }

        if ($useProtocol) {
            $protocol = $useProtocol;
        }

        return $protocol . '://' . $hostname . $port;
    }

    /**
     * @static
     *
     * @param Request|null $request
     *
     * @return string|null
     */
    public static function getClientIp(Request $request = null)
    {
        $request = self::resolveRequest($request);
        if ($request) {
            return $request->getClientIp();
        }

        // fallback to $_SERVER variables
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            return null;
        }

        $ips = explode(',', $ip);
        $ip = trim(array_shift($ips));

        return $ip;
    }

    /**
     * @param Request|null $request
     *
     * @return null|string
     */
    public static function getAnonymizedClientIp(Request $request = null)
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return null;
        }

        return \Pimcore::getContainer()
            ->get('pimcore.http.request_helper')
            ->getAnonymizedClientIp($request);
    }

    /**
     * @static
     *
     * @return array|bool
     */
    public static function getCustomViewConfig()
    {
        $configFile = \Pimcore\Config::locateConfigFile('customviews.php');

        if (!is_file($configFile)) {
            $cvData = false;
        } else {
            $confArray = include($configFile);
            $cvData = [];

            foreach ($confArray['views'] as $tmp) {
                if (isset($tmp['name'])) {
                    $tmp['showroot'] = !empty($tmp['showroot']);

                    if (!empty($tmp['hidden'])) {
                        continue;
                    }

                    $cvData[] = $tmp;
                }
            }
        }

        return $cvData;
    }

    /**
     * @param array|string|null $recipients
     * @param string|null $subject
     * @param string|null $charset
     *
     * @return Mail
     *
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
     *
     * @param string $url
     * @param array $paramsGet
     * @param array $paramsPost
     * @param array $options
     *
     * @return bool|string
     */
    public static function getHttpData($url, $paramsGet = [], $paramsPost = [], $options = [])
    {
        $client = \Pimcore::getContainer()->get('pimcore.http_client');
        $requestType = 'GET';

        if (!isset($options['timeout'])) {
            $options['timeout'] = 5;
        }

        if (is_array($paramsGet) && count($paramsGet) > 0) {

            //need to insert get params from url to $paramsGet because otherwise the would be ignored
            $urlParts = parse_url($url);
            $urlParams = [];
            parse_str($urlParts['query'], $urlParams);

            if ($urlParams) {
                $paramsGet = array_merge($urlParams, $paramsGet);
            }

            $options[RequestOptions::QUERY] = $paramsGet;
        }

        if (is_array($paramsPost) && count($paramsPost) > 0) {
            $options[RequestOptions::FORM_PARAMS] = $paramsPost;
            $requestType = 'POST';
        }

        try {
            $response = $client->request($requestType, $url, $options);

            if ($response->getStatusCode() < 300) {
                return (string)$response->getBody();
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public static function classExists($class)
    {
        return self::classInterfaceExists($class, 'class');
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public static function interfaceExists($class)
    {
        return self::classInterfaceExists($class, 'interface');
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public static function traitExists($class)
    {
        return self::classInterfaceExists($class, 'trait');
    }

    /**
     * @param string $class
     * @param string $type (e.g. 'class', 'interface', 'trait')
     *
     * @return bool
     */
    protected static function classInterfaceExists($class, $type)
    {
        $functionName = $type . '_exists';

        // if the class is already loaded we can skip right here
        if ($functionName($class, false)) {
            return true;
        }

        $class = '\\' . ltrim($class, '\\');

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
     * @return array
     */
    public static function getCachedSymfonyEnvironments(): array
    {
        $dirs = glob(PIMCORE_SYMFONY_CACHE_DIRECTORY . '/*', GLOB_ONLYDIR);
        if (($key = array_search(PIMCORE_CACHE_DIRECTORY, $dirs)) !== false) {
            unset($dirs[$key]);
        }
        $dirs = array_map('basename', $dirs);

        return array_values($dirs);
    }

    /**
     * @param string $message
     */
    public static function exitWithError($message)
    {
        while (@ob_end_flush());

        if (php_sapi_name() != 'cli') {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
        }

        die($message);
    }
}
