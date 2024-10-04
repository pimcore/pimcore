<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore;

use Exception;
use GuzzleHttp\RequestOptions;
use Locale;
use Pimcore;
use Pimcore\Http\RequestHelper;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\Request;

final class Tool
{
    /**
     * Sets the current request to use when resolving request at early
     * stages (before container is loaded)
     */
    private static ?Request $currentRequest = null;

    protected static array $notFoundClassNames = [];

    protected static array $validLanguages = [];

    protected static array $requiredLanguages = [];

    /**
     * Sets the current request to operate on
     *
     * @internal
     */
    public static function setCurrentRequest(?Request $request = null): void
    {
        self::$currentRequest = $request;
    }

    /**
     * @internal
     */
    public static function hasCurrentRequest(): bool
    {
        return self::$currentRequest !== null;
    }

    /**
     * Checks, if the given language is configured in pimcore's system
     * settings at "Localization & Internationalization (i18n/l10n)".
     * Returns true, if the language is valid or no language is
     * configured at all, false otherwise.
     */
    public static function isValidLanguage(?string $language): bool
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
     * @return string[]
     */
    public static function getValidLanguages(): array
    {
        if (empty(self::$validLanguages)) {
            $config = SystemSettingsConfig::get()['general'];
            if (empty($config['valid_languages'])) {
                return [];
            }

            $validLanguages = $config['valid_languages'];

            if (!is_array($validLanguages)) {
                $validLanguages = [];
            }

            self::$validLanguages = $validLanguages;
        }

        return self::$validLanguages;
    }

    public static function getRequiredLanguages(): array
    {
        if (empty(self::$requiredLanguages) === true) {
            $config = SystemSettingsConfig::get()['general'];
            if (empty($config['required_languages'])) {
                return Tool::getValidLanguages();
            }

            $requiredLanguages = $config['required_languages'];

            if (!is_array($requiredLanguages)) {
                $requiredLanguages = Tool::getValidLanguages();
            }

            self::$requiredLanguages = $requiredLanguages;
        }

        return self::$requiredLanguages;
    }

    /**
     * @return string[]
     *
     * @internal
     */
    public static function getFallbackLanguagesFor(string $language): array
    {
        $languages = [];

        $config = SystemSettingsConfig::get()['general'];
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
     */
    public static function getDefaultLanguage(): ?string
    {
        $config = SystemSettingsConfig::get()['general'];
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
     * @return array<string, string>
     *
     * @throws Exception
     */
    public static function getSupportedLocales(): array
    {
        $localeService = Pimcore::getContainer()->get(LocaleServiceInterface::class);
        $locale = $localeService->findLocale();

        $cacheKey = 'system_supported_locales_' . strtolower((string) $locale);
        if (!$languageOptions = Cache::load($cacheKey)) {
            $languages = $localeService->getLocaleList();

            $languageOptions = [];
            foreach ($languages as $code) {
                $translation = Locale::getDisplayLanguage($code, $locale);
                $displayRegion = Locale::getDisplayRegion($code, $locale);

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
     * Trying to get BCP 47 format
     *
     * @return array<string, string>
     *
     * @throws Exception
     */
    public static function getSupportedJSLocales(): array
    {
        $localeService = Pimcore::getContainer()->get(LocaleServiceInterface::class);
        $locale = $localeService->findLocale();

        $cacheKey = 'system_supported_js_locales_' . strtolower((string)$locale);
        if (!$languageOptions = Cache::load($cacheKey)) {
            $languages = $localeService->getLocaleList();

            $languageOptions = [];
            foreach ($languages as $code) {
                if (substr_count($code, '_') > 1) {
                    continue;
                }
                $codeBCP = str_replace('_', '-', $code);

                $displayName = Locale::getDisplayName($code, $locale);
                $displayRegion = Locale::getDisplayRegion($code, $locale);

                if ($displayRegion) {
                    $translation = $displayRegion . ' [' . $codeBCP . ']';
                } else {
                    $translation = $displayName . ' [' . $codeBCP . ']';
                }

                $languageOptions[$codeBCP] = $translation;
            }

            asort($languageOptions);

            Cache::save($languageOptions, $cacheKey, ['system']);
        }

        return $languageOptions;
    }

    private static function resolveRequest(Request $request = null): ?Request
    {
        if (null === $request) {
            // do an extra check for the container as we might be in a state where no container is set yet
            if (Pimcore::hasContainer()) {
                $request = Pimcore::getContainer()->get('request_stack')->getMainRequest();
            } else {
                if (null !== self::$currentRequest) {
                    return self::$currentRequest;
                }
            }
        }

        return $request;
    }

    public static function isFrontend(Request $request = null): bool
    {
        if (null === $request) {
            $request = Pimcore::getContainer()->get('request_stack')->getMainRequest();
        }

        if (null === $request) {
            return false;
        }

        return Pimcore::getContainer()
            ->get(RequestHelper::class)
            ->isFrontendRequest($request);
    }

    /**
     * eg. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     */
    public static function isFrontendRequestByAdmin(Request $request = null): bool
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return false;
        }

        return Pimcore::getContainer()
            ->get(RequestHelper::class)
            ->isFrontendRequestByAdmin($request);
    }

    /**
     * Verify element request (eg. editmode, preview, version preview) called within admin, with permissions.
     */
    public static function isElementRequestByAdmin(Request $request, Element\ElementInterface $element): bool
    {
        if (!self::isFrontendRequestByAdmin($request)) {
            return false;
        }

        $user = Tool\Authentication::authenticateSession($request);

        return $user && $element->isAllowed('view', $user);
    }

    /**
     * @internal
     */
    public static function useFrontendOutputFilters(Request $request = null): bool
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
        if (in_array('pimcore_outputfilters_disabled', $requestKeys) && Pimcore::inDebugMode()) {
            return false;
        }

        return true;
    }

    /**
     * @internal
     */
    public static function getHostname(Request $request = null): ?string
    {
        $request = self::resolveRequest($request);

        if (null === $request || !$request->getHost()) {
            $config = SystemSettingsConfig::get()['general'];
            $domain = $config['domain'];

            return $domain ?: null;
        }

        return $request->getHost();
    }

    /**
     * @internal
     */
    public static function getRequestScheme(Request $request = null): string
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
     * @param string|null $useProtocol use a specific protocol
     */
    public static function getHostUrl(string $useProtocol = null, Request $request = null): string
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
        if (!$hostname || $hostname === 'localhost') {
            $systemConfig = SystemSettingsConfig::get()['general'];
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
     * @internal
     */
    public static function getClientIp(Request $request = null): ?string
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
     * @internal
     */
    public static function getAnonymizedClientIp(Request $request = null): ?string
    {
        $request = self::resolveRequest($request);

        if (null === $request) {
            return null;
        }

        return Pimcore::getContainer()
            ->get(RequestHelper::class)
            ->getAnonymizedClientIp($request);
    }

    /**
     * @throws Exception
     */
    public static function getMail(array|string $recipients = null, string $subject = null): Mail
    {
        $mail = new Mail();

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
            $mail->subject($subject);
        }

        return $mail;
    }

    public static function getHttpData(string $url, array $paramsGet = [], array $paramsPost = [], array $options = []): false|string
    {
        $client = Pimcore::getContainer()->get('pimcore.http_client');
        $requestType = 'GET';

        if (!isset($options['timeout'])) {
            $options['timeout'] = 5;
        }

        if (count($paramsGet) > 0) {
            //need to insert get params from url to $paramsGet because otherwise they would be ignored
            $urlParts = parse_url($url);

            if (isset($urlParts['query'])) {
                $urlParams = [];

                parse_str($urlParts['query'], $urlParams);

                if ($urlParams) {
                    $paramsGet = array_merge($urlParams, $paramsGet);
                }
            }

            $options[RequestOptions::QUERY] = $paramsGet;
        }

        if (count($paramsPost) > 0) {
            $options[RequestOptions::FORM_PARAMS] = $paramsPost;
            $requestType = 'POST';
        }

        try {
            $response = $client->request($requestType, $url, $options);

            if ($response->getStatusCode() < 300) {
                return (string)$response->getBody();
            }
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @internal
     */
    public static function classExists(string $class): bool
    {
        return self::classInterfaceExists($class, 'class');
    }

    /**
     * @internal
     */
    public static function interfaceExists(string $class): bool
    {
        return self::classInterfaceExists($class, 'interface');
    }

    /**
     * @internal
     */
    public static function traitExists(string $class): bool
    {
        return self::classInterfaceExists($class, 'trait');
    }

    /**
     * @param string $type (e.g. 'class', 'interface', 'trait')
     */
    private static function classInterfaceExists(string $class, string $type): bool
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
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            //Logger::debug(implode(" ", [$errno, $errstr, $errfile, $errline]));
            return true;
        });

        $exists = $functionName($class);

        restore_error_handler();

        if (!$exists) {
            self::$notFoundClassNames[$class] = true; // value doesn't matter, key lookups are faster ;-)
        }

        return $exists;
    }

    /**
     * @internal
     *
     * @return string[]
     *
     * @deprecated. Remove in Pimcore 12
     */
    public static function getCachedSymfonyEnvironments(): array
    {
        $dirs = glob(PIMCORE_SYMFONY_CACHE_DIRECTORY . '/*', GLOB_ONLYDIR);
        if (($key = array_search(PIMCORE_CACHE_DIRECTORY, $dirs)) !== false) {
            unset($dirs[$key]);
        }
        $dirs = array_map('basename', $dirs);
        $dirs = array_filter($dirs, function ($value) {
            // this filters out "old" build directories, which end with a ~
            return !(bool) preg_match('/~$/', $value);
        });

        return array_values($dirs);
    }
}
