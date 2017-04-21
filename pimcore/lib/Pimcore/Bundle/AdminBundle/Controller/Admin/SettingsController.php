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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Glossary;
use Pimcore\Model\Metadata;
use Pimcore\Model\Property;
use Pimcore\Model\Redirect;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Tag;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 */
class SettingsController extends AdminController
{
    /**
     * @Route("/metadata")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function metadataAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('asset_metadata');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $metadata = Metadata\Predefined::getById($id);
                $metadata->delete();

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $metadata = Metadata\Predefined::getById($data['id']);

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem && $existingItem->getId() != $metadata->getId()) {
                    return $this->json(['message' => 'rule_violation', 'success' => false]);
                }

                $metadata->minimize();
                $metadata->save();
                $metadata->expand();

                return $this->json(['data' => $metadata, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $metadata = Metadata\Predefined::create();

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem) {
                    return $this->json(['message' => 'rule_violation', 'success' => false]);
                }

                $metadata->save();

                return $this->json(['data' => $metadata, 'success' => true]);
            }
        } else {
            // get list of types

            $list = new Metadata\Predefined\Listing();

            if ($request->get('filter')) {
                $filter = $request->get('filter');
                $list->setFilter(function ($row) use ($filter) {
                    foreach ($row as $value) {
                        if (strpos($value, $filter) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $list->load();

            $properties = [];
            if (is_array($list->getDefinitions())) {
                foreach ($list->getDefinitions() as $metadata) {
                    $metadata->expand();
                    $properties[] = $metadata;
                }
            }

            return $this->json(['data' => $properties, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }

    /**
     * @Route("/get-predefined-metadata")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getPredefinedMetadataAction(Request $request)
    {
        $type = $request->get('type');
        $subType = $request->get('subType');
        $list = Metadata\Predefined\Listing::getByTargetType($type, [$subType]);
        $result = [];
        foreach ($list as $item) {
            /** @var $item Metadata\Predefined */
            $item->expand();
            $result[] = $item;
        }

        return $this->json(['data' => $result, 'success' => true]);
    }

    /**
     * @Route("/properties")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function propertiesAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('predefined_properties');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $property = Property\Predefined::getById($id);
                $property->delete();

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $property = Property\Predefined::getById($data['id']);
                $property->setValues($data);

                $property->save();

                return $this->json(['data' => $property, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $property = Property\Predefined::create();
                $property->setValues($data);

                $property->save();

                return $this->json(['data' => $property, 'success' => true]);
            }
        } else {
            // get list of types
            $list = new Property\Predefined\Listing();

            if ($request->get('filter')) {
                $filter = $request->get('filter');
                $list->setFilter(function ($row) use ($filter) {
                    foreach ($row as $value) {
                        if ($value) {
                            $cellValues = is_array($value) ? $value : [$value];

                            foreach ($cellValues as $cellValue) {
                                if (strpos($cellValue, $filter) !== false) {
                                    return true;
                                }
                            }
                        }
                    }

                    return false;
                });
            }

            $list->load();

            $properties = [];
            if (is_array($list->getProperties())) {
                foreach ($list->getProperties() as $property) {
                    $properties[] = $property;
                }
            }

            return $this->json(['data' => $properties, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }

    /**
     * @param $root
     * @param $thumbnailName
     */
    private function deleteThumbnailFolders($root, $thumbnailName)
    {
        // delete all thumbnails which are using this config

        /**
         * @param $dir
         * @param $thumbnail
         * @param array $matches
         *
         * @return array
         */
        function delete($dir, $thumbnail, &$matches = [])
        {
            $dirs = glob($dir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                if (
                    preg_match('@/thumb__' . $thumbnail . '$@', $dir) ||
                    preg_match('@/thumb__' . $thumbnail . '_auto@', $dir) ||
                    preg_match('@/thumb__document_' . $thumbnail . '\-[\d]+$@', $dir) ||
                    preg_match('@/thumb__document_' . $thumbnail . '\-[\d]+_auto@', $dir)
                ) {
                    recursiveDelete($dir);
                }
                delete($dir, $thumbnail, $matches);
            }

            return $matches;
        }

        delete($root, $thumbnailName);
    }

    /**
     * @param Asset\Image\Thumbnail\Config $thumbnail
     */
    private function deleteThumbnailTmpFiles(Asset\Image\Thumbnail\Config $thumbnail)
    {
        $this->deleteThumbnailFolders(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails', $thumbnail->getName());
    }

    /**
     * @param Asset\Video\Thumbnail\Config $thumbnail
     */
    private function deleteVideoThumbnailTmpFiles(Asset\Video\Thumbnail\Config $thumbnail)
    {
        $this->deleteThumbnailFolders(PIMCORE_TEMPORARY_DIRECTORY . '/video-thumbnails', $thumbnail->getName());
    }

    /**
     * @Route("/get-system")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSystemAction(Request $request)
    {
        $this->checkPermission('system_settings');

        $values = Config::getSystemConfig();

        $timezones = \DateTimeZone::listIdentifiers();

        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        foreach ($locales as $short => $translation) {
            if (!empty($short)) {
                $languageOptions[] = [
                    'language' => $short,
                    'display' => $translation . " ($short)"
                ];
                $validLanguages[] = $short;
            }
        }

        $valueArray = $values->toArray();
        $valueArray['general']['validLanguage'] = explode(',', $valueArray['general']['validLanguages']);

        //for "wrong" legacy values
        if (is_array($valueArray['general']['validLanguage'])) {
            foreach ($valueArray['general']['validLanguage'] as $existingValue) {
                if (!in_array($existingValue, $validLanguages)) {
                    $languageOptions[] = [
                        'language' => $existingValue,
                        'display' => $existingValue
                    ];
                }
            }
        }

        //cache exclude patterns - add as array
        if (!empty($valueArray['cache']['excludePatterns'])) {
            $patterns = explode(',', $valueArray['cache']['excludePatterns']);
            if (is_array($patterns)) {
                foreach ($patterns as $pattern) {
                    $valueArray['cache']['excludePatternsArray'][] = ['value' => $pattern];
                }
            }
        }

        //remove password from values sent to frontend
        $valueArray['database']['params']['password'] = '##SECRET_PASS##';

        // inject debug mode

        $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';
        $debugMode = [];
        if (file_exists($debugModeFile)) {
            $debugMode = include $debugModeFile;
        }
        $valueArray['general']['debug'] = $debugMode['active'];
        $valueArray['general']['debug_ip'] = $debugMode['ip'];

        $response = [
            'values' => $valueArray,
            'config' => [
                'timezones' => $timezones,
                'languages' => $languageOptions,
                'client_ip' => $request->getClientIp(),
                'google_private_key_exists' => file_exists(\Pimcore\Google\Api::getPrivateKeyPath()),
                'google_private_key_path' => \Pimcore\Google\Api::getPrivateKeyPath(),
                'path_separator' => PATH_SEPARATOR
            ]
        ];

        return $this->json($response);
    }

    /**
     * @Route("/set-system")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function setSystemAction(Request $request)
    {
        $this->checkPermission('system_settings');

        $values = $this->decodeJson($request->get('data'));

        // email settings
        $existingConfig = Config::getSystemConfig();
        $existingValues = $existingConfig->toArray();

        // fallback languages
        $fallbackLanguages = [];
        $languages = explode(',', $values['general.validLanguages']);
        $filteredLanguages = [];
        foreach ($languages as $language) {
            if (isset($values['general.fallbackLanguages.' . $language])) {
                $fallbackLanguages[$language] = str_replace(' ', '', $values['general.fallbackLanguages.' . $language]);
            }

            if (\Pimcore::getContainer()->get('pimcore.locale')->isLocale($language)) {
                $filteredLanguages[] = $language;
            }
        }

        // check if there's a fallback language endless loop
        foreach ($fallbackLanguages as $sourceLang => $targetLang) {
            $this->checkFallbackLanguageLoop($sourceLang, $fallbackLanguages);
        }

        // delete views if fallback languages has changed or the language is no more available
        if (isset($existingValues['general']['fallbackLanguages']) && is_array($existingValues['general']['fallbackLanguages'])) {
            $fallbackLanguagesChanged = array_diff_assoc($existingValues['general']['fallbackLanguages'],
                $fallbackLanguages);
            $dbName = $existingValues['database']['params']['dbname'];
            foreach ($fallbackLanguagesChanged as $language => $dummy) {
                $this->deleteViews($language, $dbName);
            }
        }

        $cacheExcludePatterns = $values['cache.excludePatterns'];
        if (is_array($cacheExcludePatterns)) {
            $cacheExcludePatterns = implode(',', $cacheExcludePatterns);
        }

        $settings = [
            'general' => [
                'timezone' => $values['general.timezone'],
                'path_variable' => $values['general.path_variable'],
                'domain' => $values['general.domain'],
                'redirect_to_maindomain' => $values['general.redirect_to_maindomain'],
                'language' => $values['general.language'],
                'validLanguages' => implode(',', $filteredLanguages),
                'fallbackLanguages' => $fallbackLanguages,
                'defaultLanguage' => $values['general.defaultLanguage'],
                'loginscreencustomimage' => $values['general.loginscreencustomimage'],
                'disableusagestatistics' => $values['general.disableusagestatistics'],
                'http_auth' => [
                    'username' => $values['general.http_auth.username'],
                    'password' => $values['general.http_auth.password']
                ],
                'debug_admin_translations' => $values['general.debug_admin_translations'],
                'devmode' => $values['general.devmode'],
                'instanceIdentifier' => $values['general.instanceIdentifier'],
                'show_cookie_notice' => $values['general.show_cookie_notice'],
            ],
            'documents' => [
                'versions' => [
                    'days' => $values['documents.versions.days'],
                    'steps' => $values['documents.versions.steps']
                ],
                'default_controller' => $values['documents.default_controller'],
                'default_action' => $values['documents.default_action'],
                'error_pages' => [
                    'default' => $values['documents.error_pages.default']
                ],
                'createredirectwhenmoved' => $values['documents.createredirectwhenmoved'],
                'allowtrailingslash' => $values['documents.allowtrailingslash'],
                'generatepreview' => $values['documents.generatepreview']
            ],
            'objects' => [
                'versions' => [
                    'days' => $values['objects.versions.days'],
                    'steps' => $values['objects.versions.steps']
                ]
            ],
            'assets' => [
                'versions' => [
                    'days' => $values['assets.versions.days'],
                    'steps' => $values['assets.versions.steps']
                ],
                'icc_rgb_profile' => $values['assets.icc_rgb_profile'],
                'icc_cmyk_profile' => $values['assets.icc_cmyk_profile'],
                'hide_edit_image' => $values['assets.hide_edit_image'],
                'disable_tree_preview' => $values['assets.disable_tree_preview']
            ],
            'services' => [
                'google' => [
                    'client_id' => $values['services.google.client_id'],
                    'email' => $values['services.google.email'],
                    'simpleapikey' => $values['services.google.simpleapikey'],
                    'browserapikey' => $values['services.google.browserapikey']
                ]
            ],
            'cache' => [
                'enabled' => $values['cache.enabled'],
                'lifetime' => $values['cache.lifetime'],
                'excludePatterns' => $cacheExcludePatterns,
                'excludeCookie' => $values['cache.excludeCookie']
            ],
            'webservice' => [
                'enabled' => $values['webservice.enabled']
            ],
            'httpclient' => [
                'adapter' => $values['httpclient.adapter'],
                'proxy_host' => $values['httpclient.proxy_host'],
                'proxy_port' => $values['httpclient.proxy_port'],
                'proxy_user' => $values['httpclient.proxy_user'],
                'proxy_pass' => $values['httpclient.proxy_pass'],
            ],
            'applicationlog' => [
                'mail_notification' => [
                    'send_log_summary' => $values['applicationlog.mail_notification.send_log_summary'],
                    'filter_priority' => $values['applicationlog.mail_notification.filter_priority'],
                    'mail_receiver' => $values['applicationlog.mail_notification.mail_receiver'],
                ],
                'archive_treshold' => $values['applicationlog.archive_treshold'],
                'archive_alternative_database' => $values['applicationlog.archive_alternative_database'],
            ]
        ];

        // email & newsletter
        foreach (['email', 'newsletter'] as $type) {
            $settings[$type] = [
                'sender' => [
                    'name' => $values[$type . '.sender.name'],
                    'email' => $values[$type . '.sender.email']],
                'return' => [
                    'name' => $values[$type . '.return.name'],
                    'email' => $values[$type . '.return.email']],
                'method' => $values[$type . '.method'],
                'smtp' => [
                    'host' => $values[$type . '.smtp.host'],
                    'port' => $values[$type . '.smtp.port'],
                    'ssl' => $values[$type . '.smtp.ssl'] ? $values[$type . '.smtp.ssl'] : null,
                    'name' => $values[$type . '.smtp.name'],
                    'auth' => [
                        'method' => $values[$type . '.smtp.auth.method'] ? $values[$type . '.smtp.auth.method'] : null,
                        'username' => $values[$type . '.smtp.auth.username'],
                    ]
                ]
            ];

            $smtpPassword = $values[$type . '.smtp.auth.password'];
            if (!empty($smtpPassword)) {
                $settings[$type]['smtp']['auth']['password'] = $smtpPassword;
            } else {
                $settings[$type]['smtp']['auth']['password'] = null;
            }

            if (array_key_exists($type . '.debug.emailAddresses', $values)) {
                $settings[$type]['debug'] = ['emailaddresses' => $values[$type . '.debug.emailAddresses']];
            } else {
                $settings[$type]['debug'] = null;
            }
        }
        $settings['newsletter']['usespecific'] = $values['newsletter.usespecific'];

        $settings = array_merge($existingValues, $settings);

        $configFile = \Pimcore\Config::locateConfigFile('system.php');
        File::putPhpFile($configFile, to_php_data_file_format($settings));

        $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';
        File::putPhpFile($debugModeFile, to_php_data_file_format([
            'active' => $values['general.debug'],
            'ip' => $values['general.debug_ip'],
        ]));

        return $this->json(['success' => true]);
    }

    /**
     * @param $source
     * @param $definitions
     * @param array $fallbacks
     *
     * @throws \Exception
     */
    protected function checkFallbackLanguageLoop($source, $definitions, $fallbacks = [])
    {
        if (isset($definitions[$source])) {
            $targets = explode(',', $definitions[$source]);
            foreach ($targets as $l) {
                $target = trim($l);
                if ($target) {
                    if (in_array($target, $fallbacks)) {
                        throw new \Exception("Language `$source` | `$target` causes an infinte loop.");
                    }
                    $fallbacks[] = $target;

                    $this->checkFallbackLanguageLoop($target, $definitions, $fallbacks);
                }
            }
        } else {
            throw new \Exception("Language `$source` doesn't exist");
        }
    }

    /**
     * @Route("/get-web2print")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWeb2printAction(Request $request)
    {
        $this->checkPermission('web2print_settings');

        $values = Config::getWeb2PrintConfig();
        $valueArray = $values->toArray();

        $optionsString = [];
        if ($valueArray['wkhtml2pdfOptions']) {
            foreach ($valueArray['wkhtml2pdfOptions'] as $key => $value) {
                $tmpStr = '--'.$key;
                if ($value !== null && $value !== '') {
                    $tmpStr .= ' '.$value;
                }
                $optionsString[] = $tmpStr;
            }
        }
        $valueArray['wkhtml2pdfOptions'] = implode("\n", $optionsString);

        $response = [
            'values' => $valueArray
        ];

        return $this->json($response);
    }

    /**
     * @Route("/set-web2print")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function setWeb2printAction(Request $request)
    {
        $this->checkPermission('web2print_settings');

        $values = $this->decodeJson($request->get('data'));

        if ($values['wkhtml2pdfOptions']) {
            $optionArray = [];
            $lines = explode("\n", $values['wkhtml2pdfOptions']);
            foreach ($lines as $line) {
                $parts = explode(' ', substr($line, 2));
                $key = trim($parts[0]);
                if ($key) {
                    $value = trim($parts[1]);
                    $optionArray[$key] = $value;
                }
            }
            $values['wkhtml2pdfOptions'] = $optionArray;
        }

        $configFile = \Pimcore\Config::locateConfigFile('web2print.php');
        File::putPhpFile($configFile, to_php_data_file_format($values));

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/clear-cache")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearCacheAction(Request $request)
    {
        $this->checkPermission('clear_cache');

        $onlyPimcoreCache = (bool)$request->get('only_pimcore_cache');
        $onlySymfonyCache = (bool)$request->get('only_symfony_cache');

        if (!$onlySymfonyCache) {
            // empty document cache
            Cache::clearAll();

            $db = \Pimcore\Db::get();
            $db->query('truncate table cache_tags');
            $db->query('truncate table cache');
        }

        if (!$onlyPimcoreCache) {
            \Pimcore\Tool::clearSymfonyCache($this->container);
        }

        if (!$onlySymfonyCache) {
            $this->get('filesystem')->remove(PIMCORE_CACHE_DIRECTORY);
            // PIMCORE-1854 - recreate .dummy file => should remain
            \Pimcore\File::put(PIMCORE_CACHE_DIRECTORY . '/.gitkeep', '');

            \Pimcore::getEventDispatcher()->dispatch(\Pimcore\Event\SystemEvents::CACHE_CLEAR);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/clear-output-cache")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearOutputCacheAction(Request $request)
    {
        $this->checkPermission('clear_cache');

        // remove "output" out of the ignored tags, if a cache lifetime is specified
        Cache::removeIgnoredTagOnClear('output');

        // empty document cache
        Cache::clearTags(['output', 'output_lifetime']);

        \Pimcore::getEventDispatcher()->dispatch(\Pimcore\Event\SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/clear-temporary-files")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearTemporaryFilesAction(Request $request)
    {
        $this->checkPermission('clear_temp_files');

        // public files
        recursiveDelete(PIMCORE_TEMPORARY_DIRECTORY, false);

        // system files
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);

        // recreate .dummy files # PIMCORE-2629
        \Pimcore\File::put(PIMCORE_TEMPORARY_DIRECTORY . '/.dummy', '');
        \Pimcore\File::put(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/.dummy', '');

        \Pimcore::getEventDispatcher()->dispatch(\Pimcore\Event\SystemEvents::CACHE_CLEAR_TEMPORARY_FILES);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/staticroutes")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function staticroutesAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('routes');

            $data = $this->decodeJson($request->get('data'));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
            }

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $route = Staticroute::getById($id);
                $route->delete();

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                // save routes
                $route = Staticroute::getById($data['id']);
                $route->setValues($data);

                $route->save();

                return $this->json(['data' => $route, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                unset($data['id']);

                // save route
                $route = new Staticroute();
                $route->setValues($data);

                $route->save();

                return $this->json(['data' => $route, 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new Staticroute\Listing();

            if ($request->get('filter')) {
                $filter = $request->get('filter');
                $list->setFilter(function ($row) use ($filter) {
                    foreach ($row as $value) {
                        if (! is_scalar($value)) {
                            continue;
                        }
                        if (strpos((string)$value, $filter) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $list->load();

            $routes = [];
            /** @var $route Staticroute */
            foreach ($list->getRoutes() as $route) {
                if (is_array($route->getSiteId())) {
                    $route = json_encode($route);
                    $route = json_decode($route, true);
                    $route['siteId'] = implode(',', $route['siteId']);
                }
                $routes[] = $route;
            }

            return $this->json(['data' => $routes, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->json(false);
    }

    /**
     * @Route("/get-available-languages")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableLanguagesAction(Request $request)
    {
        if ($languages = Tool::getValidLanguages()) {
            return $this->json($languages);
        }

        $t = new Model\Translation\Website();

        return $this->json($t->getAvailableLanguages());
    }

    /**
     * @Route("/get-available-admin-languages")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableAdminLanguagesAction(Request $request)
    {
        $langs = [];
        $availableLanguages = Tool\Admin::getLanguages();
        $locales = Tool::getSupportedLocales();

        foreach ($availableLanguages as $lang) {
            if (array_key_exists($lang, $locales)) {
                $langs[] = [
                    'language' => $lang,
                    'display' => $locales[$lang]
                ];
            }
        }

        return $this->json($langs);
    }

    /**
     * @Route("/redirects")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function redirectsAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('redirects');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $redirect = Redirect::getById($id);
                $redirect->delete();

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save redirect
                $redirect = Redirect::getById($data['id']);

                if ($data['target']) {
                    if ($doc = Document::getByPath($data['target'])) {
                        $data['target'] = $doc->getId();
                    }
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget)) {
                    if ($doc = Document::getById(intval($redirectTarget))) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }

                return $this->json(['data' => $redirect, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save route
                $redirect = new Redirect();

                if ($data['target']) {
                    if ($doc = Document::getByPath($data['target'])) {
                        $data['target'] = $doc->getId();
                    }
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget)) {
                    if ($doc = Document::getById(intval($redirectTarget))) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }

                return $this->json(['data' => $redirect, 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new Redirect\Listing();
            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($request->get('filter')) {
                $list->setCondition('`source` LIKE ' . $list->quote('%'.$request->get('filter').'%') . ' OR `target` LIKE ' . $list->quote('%'.$request->get('filter').'%'));
            }

            $list->load();

            $redirects = [];
            foreach ($list->getRedirects() as $redirect) {
                if ($link = $redirect->getTarget()) {
                    if (is_numeric($link)) {
                        if ($doc = Document::getById(intval($link))) {
                            $redirect->setTarget($doc->getRealFullPath());
                        }
                    }
                }

                $redirects[] = $redirect;
            }

            return $this->json(['data' => $redirects, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->json(false);
    }

    /**
     * @Route("/glossary")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function glossaryAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('glossary');

            Cache::clearTag('glossary');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $glossary = Glossary::getById($id);
                $glossary->delete();

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save glossary
                $glossary = Glossary::getById($data['id']);

                if ($data['link']) {
                    if ($doc = Document::getByPath($data['link'])) {
                        $data['link'] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                $glossary->save();

                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                return $this->json(['data' => $glossary, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save glossary
                $glossary = new Glossary();

                if ($data['link']) {
                    if ($doc = Document::getByPath($data['link'])) {
                        $data['link'] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                $glossary->save();

                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                return $this->json(['data' => $glossary, 'success' => true]);
            }
        } else {
            // get list of glossaries

            $list = new Glossary\Listing();
            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($request->get('filter')) {
                $list->setCondition('`text` LIKE ' . $list->quote('%'.$request->get('filter').'%'));
            }

            $list->load();

            $glossaries = [];
            foreach ($list->getGlossary() as $glossary) {
                if ($link = $glossary->getLink()) {
                    if (intval($link) > 0) {
                        if ($doc = Document::getById(intval($link))) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                $glossaries[] = $glossary;
            }

            return $this->json(['data' => $glossaries, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->json(false);
    }

    /**
     * @Route("/get-available-sites")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableSitesAction(Request $request)
    {
        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [[
            'id' => 'default',
            'rootId' => 1,
            'domains' => '',
            'rootPath' => '/',
            'domain' => $this->trans('main_site')
        ]];

        foreach ($sitesObjects as $site) {
            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        'id' => $site->getId(),
                        'rootId' => $site->getRootId(),
                        'domains' => implode(',', $site->getDomains()),
                        'rootPath' => $site->getRootPath(),
                        'domain' => $site->getMainDomain()
                    ];
                }
            } else {
                // site is useless, parent doesn't exist anymore
                $site->delete();
            }
        }

        return $this->json($sites);
    }

    /**
     * @Route("/get-available-countries")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableCountriesAction(Request $request)
    {
        $countries = \Pimcore::getContainer()->get('pimcore.locale')->getDisplayRegions();
        asort($countries);

        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'key' => $translation . ' (' . $short . ')',
                    'value' => $short
                ];
            }
        }

        $result = ['data' => $options, 'success' => true, 'total' => count($options)];

        return $this->json($result);
    }

    /**
     * @Route("/thumbnail-adapter-check")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function thumbnailAdapterCheckAction(Request $request)
    {
        $content = '';

        $instance = \Pimcore\Image::getInstance();
        if ($instance instanceof \Pimcore\Image\Adapter\GD) {
            $content = '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->trans('important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality') .
                '</span>';
        }

        return new Response($content);
    }

    /**
     * @Route("/thumbnail-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailTreeAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new Asset\Image\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName()
            ];
        }

        return $this->json($thumbnails);
    }

    /**
     * @Route("/thumbnail-add")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailAddAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $success = false;

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe) {
            $pipe = new Asset\Image\Thumbnail\Config();
            $pipe->setName($request->get('name'));
            $pipe->save();

            $success = true;
        }

        return $this->json(['success' => $success, 'id' => $pipe->getName()]);
    }

    /**
     * @Route("/thumbnail-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailDeleteAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));
        $pipe->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/thumbnail-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailGetAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));

        return $this->json($pipe);
    }

    /**
     * @Route("/thumbnail-update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailUpdateAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));
        $settingsData = $this->decodeJson($request->get('settings'));
        $mediaData = $this->decodeJson($request->get('medias'));

        foreach ($settingsData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        foreach ($mediaData as $mediaName => $items) {
            foreach ($items as $item) {
                $type = $item['type'];
                unset($item['type']);

                $pipe->addItem($type, $item, $mediaName);
            }
        }

        $pipe->save();

        $this->deleteThumbnailTmpFiles($pipe);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/video-thumbnail-adapter-check")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function videoThumbnailAdapterCheckAction(Request $request)
    {
        $content = '';

        if (!\Pimcore\Video::isAvailable()) {
            $content = '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->trans('php_cli_binary_and_or_ffmpeg_binary_setting_is_missing') .
                '</span>';
        }

        return new Response($content);
    }

    /**
     * @Route("/video-thumbnail-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailTreeAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new Asset\Video\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName()
            ];
        }

        return $this->json($thumbnails);
    }

    /**
     * @Route("/video-thumbnail-add")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailAddAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $success = false;

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe) {
            $pipe = new Asset\Video\Thumbnail\Config();
            $pipe->setName($request->get('name'));
            $pipe->save();

            $success = true;
        }

        return $this->json(['success' => $success, 'id' => $pipe->getName()]);
    }

    /**
     * @Route("/video-thumbnail-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailDeleteAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));
        $pipe->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/video-thumbnail-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailGetAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        return $this->json($pipe);
    }

    /**
     * @Route("/video-thumbnail-update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailUpdateAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));
        $data = $this->decodeJson($request->get('configuration'));

        $items = [];
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }

            if (strpos($key, 'item.') === 0) {
                $cleanKeyParts = explode('.', $key);
                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $pipe->resetItems();
        foreach ($items as $item) {
            $type = $item['type'];
            unset($item['type']);

            $pipe->addItem($type, $item);
        }

        $pipe->save();

        $this->deleteVideoThumbnailTmpFiles($pipe);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/robots-txt")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function robotsTxtAction(Request $request)
    {
        $this->checkPermission('robots.txt');

        $siteSuffix = '';
        if ($request->get('site')) {
            $siteSuffix = '-' . $request->get('site');
        }

        $robotsPath = PIMCORE_CONFIGURATION_DIRECTORY . '/robots' . $siteSuffix . '.txt';

        if ($request->get('data') !== null) {
            // save data
            \Pimcore\File::put($robotsPath, $request->get('data'));

            return $this->json([
                'success' => true
            ]);
        } else {
            // get data
            $data = '';
            if (is_file($robotsPath)) {
                $data = file_get_contents($robotsPath);
            }

            return $this->json([
                'success' => true,
                'data' => $data,
                'onFileSystem' => file_exists(PIMCORE_WEB_ROOT . '/robots.txt')
            ]);
        }
    }

    /**
     * @Route("/tag-management-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function tagManagementTreeAction(Request $request)
    {
        $this->checkPermission('tag_snippet_management');

        $tags = [];

        $list = new Tag\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $tags[] = [
                'id' => $item->getName(),
                'text' => $item->getName()
            ];
        }

        return $this->json($tags);
    }

    /**
     * @Route("/tag-management-add")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function tagManagementAddAction(Request $request)
    {
        $this->checkPermission('tag_snippet_management');

        $success = false;

        $tag = Model\Tool\Tag\Config::getByName($request->get('name'));

        if (!$tag) {
            $tag = new Model\Tool\Tag\Config();
            $tag->setName($request->get('name'));
            $tag->save();

            $success = true;
        }

        return $this->json(['success' => $success, 'id' => $tag->getName()]);
    }

    /**
     * @Route("/tag-management-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function tagManagementDeleteAction(Request $request)
    {
        $this->checkPermission('tag_snippet_management');

        $tag = Model\Tool\Tag\Config::getByName($request->get('name'));
        $tag->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/tag-management-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function tagManagementGetAction(Request $request)
    {
        $this->checkPermission('tag_snippet_management');

        $tag = Model\Tool\Tag\Config::getByName($request->get('name'));

        return $this->json($tag);
    }

    /**
     * @Route("/tag-management-update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function tagManagementUpdateAction(Request $request)
    {
        $this->checkPermission('tag_snippet_management');

        $tag = Model\Tool\Tag\Config::getByName($request->get('name'));
        $data = $this->decodeJson($request->get('configuration'));

        $items = [];
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($tag, $setter)) {
                $tag->$setter($value);
            }

            if (strpos($key, 'item.') === 0) {
                $cleanKeyParts = explode('.', $key);
                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $tag->resetItems();
        foreach ($items as $item) {
            $tag->addItem($item);
        }

        // parameters get/post
        $params = [];
        for ($i=0; $i < 5; $i++) {
            $params[] = [
                'name' => $data['params.name' . $i],
                'value' => $data['params.value' . $i]
            ];
        }
        $tag->setParams($params);

        if ($request->get('name') != $data['name']) {
            $tag->setName($request->get('name')); // set the old name again, so that the old file get's deleted
            $tag->delete(); // delete the old config / file
            $tag->setName($data['name']);
        }

        $tag->save();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/website-settings")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function websiteSettingsAction(Request $request)
    {
        try {
            if ($request->get('data')) {
                $this->checkPermission('website_settings');

                $data = $this->decodeJson($request->get('data'));

                if (is_array($data)) {
                    foreach ($data as &$value) {
                        $value = trim($value);
                    }
                }

                if ($request->get('xaction') == 'destroy') {
                    $id = $data['id'];
                    $setting = WebsiteSetting::getById($id);
                    $setting->delete();

                    return $this->json(['success' => true, 'data' => []]);
                } elseif ($request->get('xaction') == 'update') {
                    // save routes
                    $setting = WebsiteSetting::getById($data['id']);

                    switch ($setting->getType()) {
                        case 'document':
                        case 'asset':
                        case 'object':
                            if (isset($data['data'])) {
                                $path = $data['data'];
                                $element = Element\Service::getElementByPath($setting->getType(), $path);
                                $data['data'] = $element ? $element->getId() : null;
                            }
                            break;
                    }

                    $setting->setValues($data);

                    $setting->save();

                    $data = $this->getWebsiteSettingForEditMode($setting);

                    return $this->json(['data' => $data, 'success' => true]);
                } elseif ($request->get('xaction') == 'create') {
                    unset($data['id']);

                    // save route
                    $setting = new WebsiteSetting();
                    $setting->setValues($data);

                    $setting->save();

                    return $this->json(['data' => $setting, 'success' => true]);
                }
            } else {
                // get list of routes

                $list = new WebsiteSetting\Listing();

                $list->setLimit($request->get('limit'));
                $list->setOffset($request->get('start'));

                $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
                if ($sortingSettings['orderKey']) {
                    $list->setOrderKey($sortingSettings['orderKey']);
                    $list->setOrder($sortingSettings['order']);
                } else {
                    $list->setOrderKey('name');
                    $list->setOrder('asc');
                }

                if ($request->get('filter')) {
                    $list->setCondition('`name` LIKE ' . $list->quote('%'.$request->get('filter').'%'));
                }

                $totalCount = $list->getTotalCount();
                $list = $list->load();

                $settings = [];
                foreach ($list as $item) {
                    $resultItem = $this->getWebsiteSettingForEditMode($item);
                    $settings[] = $resultItem;
                }

                return $this->json(['data' => $settings, 'success' => true, 'total' => $totalCount]);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->json(false);
    }

    /**
     * @param $item
     *
     * @return array
     */
    private function getWebsiteSettingForEditMode($item)
    {
        $resultItem = [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'type' => $item->getType(),
            'data' => null,
            'siteId' => $item->getSiteId(),
            'creationDate' => $item->getCreationDate(),
            'modificationDate' => $item->getModificationDate()
        ];

        switch ($item->getType()) {
            case 'document':
            case 'asset':
            case 'object':
                $element = Element\Service::getElementById($item->getType(), $item->getData());
                if ($element) {
                    $resultItem['data'] = $element->getRealFullPath();
                }
                break;
            default:
                $resultItem['data'] = $item->getData('data');
                break;
        }

        return $resultItem;
    }

    /**
     * @Route("/get-available-algorithms")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableAlgorithmsAction(Request $request)
    {
        $options = [
            [
                'key'   => 'password_hash',
                'value' => 'password_hash',
            ]
        ];

        $algorithms = hash_algos();
        foreach ($algorithms as $algorithm) {
            $options[] = [
                'key' => $algorithm,
                'value' => $algorithm
            ];
        }

        $result = ['data' => $options, 'success' => true, 'total' => count($options)];

        return $this->json($result);
    }

    /**
     * deleteViews
     * delete views for localized fields when languages are removed to
     * prevent mysql errors
     *
     * @param $language
     * @param $dbName
     */
    protected function deleteViews($language, $dbName)
    {
        $db = \Pimcore\Db::get();
        $views = $db->fetchAll('SHOW FULL TABLES IN ' . $db->quoteIdentifier($dbName) . " WHERE TABLE_TYPE LIKE 'VIEW'");

        foreach ($views as $view) {
            if (preg_match('/^object_localized_[0-9]+_' . $language . '$/', $view['Tables_in_' . $dbName])) {
                $sql = 'DROP VIEW ' . $db->quoteIdentifier($view['Tables_in_' . $dbName]);
                $db->query($sql);
            }
        }
    }
}
