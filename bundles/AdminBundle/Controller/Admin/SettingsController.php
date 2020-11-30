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
use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Cache\Symfony\CacheClearer;
use Pimcore\Config;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Event\SystemEvents;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Glossary;
use Pimcore\Model\Metadata;
use Pimcore\Model\Property;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Tag;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/settings")
 */
class SettingsController extends AdminController
{
    /**
     * @Route("/display-custom-logo", name="pimcore_settings_display_custom_logo", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function displayCustomLogoAction(Request $request)
    {
        // default logo
        $logo = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/logo-claim-gray.svg';
        if ($request->get('white')) {
            $logo = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/logo-claim-white.svg';
        }

        $mime = 'image/svg+xml';
        $customLogoPath = PIMCORE_CONFIGURATION_DIRECTORY . '/custom-logo.';

        foreach (['svg', 'png', 'jpg'] as $format) {
            $customLogoFile = $customLogoPath . $format;
            if (file_exists($customLogoFile)) {
                try {
                    $mime = Tool\Mime::detect($customLogoFile);
                    $logo = $customLogoFile;
                    break;
                } catch (\Exception $e) {
                    // do nothing
                }
            }
        }

        return new BinaryFileResponse($logo, 200, ['Content-Type' => $mime]);
    }

    /**
     * @Route("/upload-custom-logo", name="pimcore_admin_settings_uploadcustomlogo", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function uploadCustomLogoAction(Request $request)
    {
        $fileExt = File::getFileExtension($_FILES['Filedata']['name']);
        if (!in_array($fileExt, ['svg', 'png', 'jpg'])) {
            throw new \Exception('Unsupported file format');
        }
        $customLogoPath = PIMCORE_CONFIGURATION_DIRECTORY . '/custom-logo.' . $fileExt;

        copy($_FILES['Filedata']['tmp_name'], $customLogoPath);
        @chmod($customLogoPath, File::getDefaultMode());

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed

        $response = $this->adminJson(['success' => true]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/delete-custom-logo", name="pimcore_admin_settings_deletecustomlogo", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteCustomLogoAction(Request $request)
    {
        $customLogoPath = PIMCORE_CONFIGURATION_DIRECTORY . '/custom-logo.*';

        $files = glob($customLogoPath);
        foreach ($files as $file) {
            unlink($file);
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * Used by the predefined metadata grid
     *
     * @Route("/predefined-metadata", name="pimcore_admin_settings_metadata", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function metadataAction(Request $request)
    {
        $this->checkPermission('asset_metadata');

        if ($request->get('data')) {
            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $metadata = Metadata\Predefined::getById($id);
                $metadata->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $metadata = Metadata\Predefined::getById($data['id']);

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem && $existingItem->getId() != $metadata->getId()) {
                    return $this->adminJson(['message' => 'rule_violation', 'success' => false]);
                }

                $metadata->minimize();
                $metadata->save();
                $metadata->expand();

                return $this->adminJson(['data' => $metadata, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $metadata = Metadata\Predefined::create();

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem) {
                    return $this->adminJson(['message' => 'rule_violation', 'success' => false]);
                }

                $metadata->save();

                return $this->adminJson(['data' => $metadata, 'success' => true]);
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

            return $this->adminJson(['data' => $properties, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-predefined-metadata", name="pimcore_admin_settings_getpredefinedmetadata", methods={"GET"})
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
        /** @var Metadata\Predefined $item */
        foreach ($list as $item) {
            $item->expand();
            $result[] = $item;
        }

        return $this->adminJson(['data' => $result, 'success' => true]);
    }

    /**
     * @Route("/properties", name="pimcore_admin_settings_properties", methods={"POST"})
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

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $property = Property\Predefined::getById($data['id']);
                if (is_array($data['ctype'])) {
                    $data['ctype'] = implode(',', $data['ctype']);
                }
                $property->setValues($data);

                $property->save();

                return $this->adminJson(['data' => $property, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $property = Property\Predefined::create();
                $property->setValues($data);

                $property->save();

                return $this->adminJson(['data' => $property, 'success' => true]);
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

            return $this->adminJson(['data' => $properties, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-system", name="pimcore_admin_settings_getsystem", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSystemAction(Request $request)
    {
        $this->checkPermission('system_settings');

        //TODO use Pimcore\Config service when legacy mapping is removed
        $values = Config::getSystemConfig();

        $timezones = \DateTimeZone::listIdentifiers();

        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        $validLanguages = [];
        foreach ($locales as $short => $translation) {
            if (!empty($short)) {
                $languageOptions[] = [
                    'language' => $short,
                    'display' => $translation . " ($short)",
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
                        'display' => $existingValue,
                    ];
                }
            }
        }

        //cache exclude patterns - add as array
        if (!empty($valueArray['full_page_cache']['excludePatterns'])) {
            $patterns = explode(',', $valueArray['full_page_cache']['excludePatterns']);
            if (is_array($patterns)) {
                foreach ($patterns as $pattern) {
                    $valueArray['full_page_cache']['excludePatternsArray'][] = ['value' => $pattern];
                }
            }
        }

        //remove password from values sent to frontend
        unset($valueArray['database']);
        foreach (['email', 'newsletter'] as $type) {
            $valueArray[$type]['smtp']['auth']['password'] = '#####SUPER-SECRET-VALUE-PLACEHOLDER######';
        }

        // inject debug mode
        $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';
        $debugMode = [];
        if (file_exists($debugModeFile)) {
            $debugMode = include $debugModeFile;
        }
        $valueArray['general']['debug'] = $debugMode['active'] ?? false;
        $valueArray['general']['debug_ip'] = $debugMode['ip'] ?? '';
        $valueArray['general']['devmode'] = $debugMode['devmode'] ?? false;

        $response = [
            'values' => $valueArray,
            'config' => [
                'timezones' => $timezones,
                'languages' => $languageOptions,
                'client_ip' => $request->getClientIp(),
                'google_private_key_exists' => file_exists(\Pimcore\Google\Api::getPrivateKeyPath()),
                'google_private_key_path' => \Pimcore\Google\Api::getPrivateKeyPath(),
                'path_separator' => PATH_SEPARATOR,
            ],
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/set-system", name="pimcore_admin_settings_setsystem", methods={"PUT"})
     *
     * @param Request $request
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function setSystemAction(Request $request, LocaleServiceInterface $localeService)
    {
        $this->checkPermission('system_settings');

        $values = $this->decodeJson($request->get('data'));

        $existingValues = [];
        try {
            $file = Config::locateConfigFile('system.yml');
            $existingValues = Config::getConfigInstance($file, true);
        } catch (\Exception $e) {
            // nothing to do
        }

        // fallback languages
        $fallbackLanguages = [];
        $existingValues['pimcore']['general']['fallback_languages'] = [];
        $languages = explode(',', $values['general.validLanguages']);
        $filteredLanguages = [];
        foreach ($languages as $language) {
            if (isset($values['general.fallbackLanguages.' . $language])) {
                $fallbackLanguages[$language] = str_replace(' ', '', $values['general.fallbackLanguages.' . $language]);
            }

            if ($localeService->isLocale($language)) {
                $filteredLanguages[] = $language;
            }
        }

        // check if there's a fallback language endless loop
        foreach ($fallbackLanguages as $sourceLang => $targetLang) {
            $this->checkFallbackLanguageLoop($sourceLang, $fallbackLanguages);
        }

        $cacheExcludePatterns = $values['full_page_cache.excludePatterns'];
        if (is_array($cacheExcludePatterns)) {
            $cacheExcludePatterns = implode(',', $cacheExcludePatterns);
        }

        $settings['pimcore'] = [
            'general' => [
                'timezone' => $values['general.timezone'],
                'path_variable' => $values['general.path_variable'],
                'domain' => $values['general.domain'],
                'redirect_to_maindomain' => $values['general.redirect_to_maindomain'],
                'language' => $values['general.language'],
                'valid_languages' => implode(',', $filteredLanguages),
                'fallback_languages' => $fallbackLanguages,
                'default_language' => $values['general.defaultLanguage'],
                'disable_usage_statistics' => $values['general.disableusagestatistics'],
                'debug_admin_translations' => $values['general.debug_admin_translations'],
                'instance_identifier' => $values['general.instanceIdentifier'],
                'show_cookie_notice' => $values['general.show_cookie_notice'],
            ],
            'documents' => [
                'versions' => [
                    'days' => $values['documents.versions.days'] ?? null,
                    'steps' => $values['documents.versions.steps'] ?? null,
                ],
                'error_pages' => [
                    'default' => $values['documents.error_pages.default'],
                ],
                'allow_trailing_slash' => $values['documents.allowtrailingslash'],
                'generate_preview' => $values['documents.generatepreview'],
            ],
            'objects' => [
                'versions' => [
                    'days' => $values['objects.versions.days'] ?? null,
                    'steps' => $values['objects.versions.steps'] ?? null,
                ],
            ],
            'assets' => [
                'versions' => [
                    'days' => $values['assets.versions.days'] ?? null,
                    'steps' => $values['assets.versions.steps'] ?? null,
                ],
                'icc_rgb_profile' => $values['assets.icc_rgb_profile'],
                'icc_cmyk_profile' => $values['assets.icc_cmyk_profile'],
                'hide_edit_image' => $values['assets.hide_edit_image'],
                'disable_tree_preview' => $values['assets.disable_tree_preview'],
            ],
            'services' => [
                'google' => [
                    'client_id' => $values['services.google.client_id'],
                    'email' => $values['services.google.email'],
                    'simple_api_key' => $values['services.google.simpleapikey'],
                    'browser_api_key' => $values['services.google.browserapikey'],
                ],
            ],
            'full_page_cache' => [
                'enabled' => $values['full_page_cache.enabled'],
                'lifetime' => $values['full_page_cache.lifetime'],
                'exclude_patterns' => $cacheExcludePatterns,
                'exclude_cookie' => $values['full_page_cache.excludeCookie'],
            ],
            'webservice' => [
                'enabled' => $values['webservice.enabled'],
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
            ],
        ];

        //branding
        $settings['pimcore_admin'] = [
            'branding' =>
                [
                    'login_screen_invert_colors' => $values['branding.login_screen_invert_colors'],
                    'color_login_screen' => $values['branding.color_login_screen'],
                    'color_admin_interface' => $values['branding.color_admin_interface'],
                    'login_screen_custom_image' => $values['general.loginscreencustomimage'],
                ],
        ];

        // email & newsletter (swiftmailer settings)
        foreach (['email' => 'pimcore_mailer', 'newsletter' => 'newsletter_mailer'] as $type => $group) {
            $settings['pimcore'][$type] = [
                'sender' => [
                    'name' => $values[$type . '.sender.name'],
                    'email' => $values[$type . '.sender.email'], ],
                'return' => [
                    'name' => $values[$type . '.return.name'],
                    'email' => $values[$type . '.return.email'], ],
                'method' => $values[$type . '.method'],
            ];

            $settings['swiftmailer']['mailers'][$group] = [
                'transport' => $values[$type . '.method'],
                'host' => $values[$type . '.smtp.host'],
                'username' => $values[$type . '.smtp.auth.username'],
                'port' => $values[$type . '.smtp.port'],
                'encryption' => $values[$type . '.smtp.ssl'] ? $values[$type . '.smtp.ssl'] : null,
                'auth_mode' => $values[$type . '.smtp.auth.method'] ? $values[$type . '.smtp.auth.method'] : null,
            ];

            $smtpPassword = $values[$type . '.smtp.auth.password'];
            if ($smtpPassword !== '#####SUPER-SECRET-VALUE-PLACEHOLDER######') {
                if (!empty($smtpPassword)) {
                    $settings['swiftmailer']['mailers'][$group]['password'] = $smtpPassword;
                } else {
                    $settings['swiftmailer']['mailers'][$group]['password'] = null;
                }
            }
            if (array_key_exists('email.debug.emailAddresses', $values) && $values['email.debug.emailAddresses']) {
                $settings['swiftmailer']['mailers'][$group]['delivery_addresses'] = [$values['email.debug.emailAddresses']];
                $settings['pimcore'][$type]['debug']['email_addresses'] = $values['email.debug.emailAddresses'];
            } else {
                $settings['swiftmailer']['mailers'][$group]['delivery_addresses'] = [];
                $settings['pimcore'][$type]['debug']['email_addresses'] = null;
            }
        }
        $settings['pimcore']['newsletter']['use_specific'] = $values['newsletter.usespecific'];

        $settings = array_replace_recursive($existingValues, $settings);

        $settingsYml = Yaml::dump($settings, 5);
        $configFile = Config::locateConfigFile('system.yml');
        File::put($configFile, $settingsYml);

        $debugModeFile = PIMCORE_CONFIGURATION_DIRECTORY . '/debug-mode.php';
        File::putPhpFile($debugModeFile, to_php_data_file_format([
            'active' => $values['general.debug'],
            'ip' => $values['general.debug_ip'],
            'devmode' => $values['general.devmode'],
        ]));

        // clear all caches
        $this->forward(self::class . '::clearCacheAction', [
            'only_symfony_cache' => false,
            'only_pimcore_cache' => false,
            'env' => [\Pimcore::getKernel()->getEnvironment()],
        ]);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param string $source
     * @param array $definitions
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
     * @Route("/get-web2print", name="pimcore_admin_settings_getweb2print", methods={"GET"})
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
            'values' => $valueArray,
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/set-web2print", name="pimcore_admin_settings_setweb2print", methods={"PUT"})
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
                    $value = trim($parts[1] ?? '');
                    $optionArray[$key] = $value;
                }
            }
            $values['wkhtml2pdfOptions'] = $optionArray;
        }

        $configFile = \Pimcore\Config::locateConfigFile('web2print.php');
        File::putPhpFile($configFile, to_php_data_file_format($values));

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/clear-cache", name="pimcore_admin_settings_clearcache", methods={"DELETE"})
     *
     * @param Request $request
     * @param KernelInterface $kernel
     * @param EventDispatcherInterface $eventDispatcher
     * @param CoreHandlerInterface $cache
     * @param ConnectionInterface $db
     * @param Filesystem $filesystem
     * @param CacheClearer $symfonyCacheClearer
     *
     * @return JsonResponse
     */
    public function clearCacheAction(
        Request $request,
        KernelInterface $kernel,
        EventDispatcherInterface $eventDispatcher,
        CoreHandlerInterface $cache,
        ConnectionInterface $db,
        Filesystem $filesystem,
        CacheClearer $symfonyCacheClearer
    ) {
        $this->checkPermissionsHasOneOf(['clear_cache', 'system_settings']);

        $result = [
            'success' => true,
        ];

        $clearPimcoreCache = !(bool)$request->get('only_symfony_cache');
        $clearSymfonyCache = !(bool)$request->get('only_pimcore_cache');

        if ($clearPimcoreCache) {
            // empty document cache
            $cache->clearAll();

            $db->query('truncate table cache_tags');
            $db->query('truncate table cache');

            if ($filesystem->exists(PIMCORE_CACHE_DIRECTORY)) {
                $filesystem->remove(PIMCORE_CACHE_DIRECTORY);
            }

            // PIMCORE-1854 - recreate .dummy file => should remain
            File::put(PIMCORE_CACHE_DIRECTORY . '/.gitkeep', '');

            $eventDispatcher->dispatch(SystemEvents::CACHE_CLEAR);
        }

        if ($clearSymfonyCache) {
            // pass one or move env parameters to clear multiple envs
            // if no env is passed it will use the current one
            $environments = $request->get('env', $kernel->getEnvironment());

            if (!is_array($environments)) {
                $environments = trim((string)$environments);

                if (empty($environments)) {
                    $environments = [];
                } else {
                    $environments = [$environments];
                }
            }

            if (empty($environments)) {
                $environments = [$kernel->getEnvironment()];
            }

            $result['environments'] = $environments;

            if (in_array($kernel->getEnvironment(), $environments)) {
                // remove terminate and exception event listeners for the current env as they break with a
                // cleared container - see #2434
                foreach ($eventDispatcher->getListeners(KernelEvents::TERMINATE) as $listener) {
                    $eventDispatcher->removeListener(KernelEvents::TERMINATE, $listener);
                }

                foreach ($eventDispatcher->getListeners(KernelEvents::EXCEPTION) as $listener) {
                    $eventDispatcher->removeListener(KernelEvents::EXCEPTION, $listener);
                }
            }

            foreach ($environments as $environment) {
                try {
                    $symfonyCacheClearer->clear($environment);
                } catch (\Throwable $e) {
                    $errors = $result['errors'] ?? [];
                    $errors[] = $e->getMessage();

                    $result = array_merge($result, [
                        'success' => false,
                        'errors' => $errors,
                    ]);
                }
            }
        }

        $response = new JsonResponse($result);

        if ($clearSymfonyCache) {
            // we send the response directly here and exit to make sure no code depending on the stale container
            // is running after this
            $response->sendHeaders();
            $response->sendContent();
            exit;
        }

        return $response;
    }

    /**
     * @Route("/clear-output-cache", name="pimcore_admin_settings_clearoutputcache", methods={"DELETE"})
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function clearOutputCacheAction(EventDispatcherInterface $eventDispatcher)
    {
        $this->checkPermission('clear_fullpage_cache');

        // remove "output" out of the ignored tags, if a cache lifetime is specified
        Cache::removeIgnoredTagOnClear('output');

        // empty document cache
        Cache::clearTags(['output', 'output_lifetime']);

        $eventDispatcher->dispatch(SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/clear-temporary-files", name="pimcore_admin_settings_cleartemporaryfiles", methods={"DELETE"})
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function clearTemporaryFilesAction(EventDispatcherInterface $eventDispatcher)
    {
        $this->checkPermission('clear_temp_files');

        // public files
        recursiveDelete(PIMCORE_TEMPORARY_DIRECTORY, false);

        // system files
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);

        // recreate .dummy files # PIMCORE-2629
        File::put(PIMCORE_TEMPORARY_DIRECTORY . '/.dummy', '');
        File::put(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/.dummy', '');

        $eventDispatcher->dispatch(SystemEvents::CACHE_CLEAR_TEMPORARY_FILES);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/staticroutes", name="pimcore_admin_settings_staticroutes", methods={"POST"})
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

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                // save routes
                $route = Staticroute::getById($data['id']);
                $route->setValues($data);

                $route->save();

                return $this->adminJson(['data' => $route, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                unset($data['id']);

                // save route
                $route = new Staticroute();
                $route->setValues($data);

                $route->save();

                return $this->adminJson(['data' => $route, 'success' => true]);
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
            /** @var Staticroute $route */
            foreach ($list->getRoutes() as $route) {
                if (is_array($route->getSiteId())) {
                    $route = json_encode($route);
                    $route = json_decode($route, true);
                    $route['siteId'] = implode(',', $route['siteId']);
                }
                $routes[] = $route;
            }

            return $this->adminJson(['data' => $routes, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-available-admin-languages", name="pimcore_admin_settings_getavailableadminlanguages", methods={"GET"})
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
                    'display' => $locales[$lang],
                ];
            }
        }

        usort($langs, function ($a, $b) {
            return strcmp($a['display'], $b['display']);
        });

        return $this->adminJson($langs);
    }

    /**
     * @Route("/glossary", name="pimcore_admin_settings_glossary", methods={"POST"})
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

                return $this->adminJson(['success' => true, 'data' => []]);
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

                return $this->adminJson(['data' => $glossary, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save glossary
                $glossary = new Glossary();

                if (!empty($data['link'])) {
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

                return $this->adminJson(['data' => $glossary, 'success' => true]);
            }
        } else {
            // get list of glossaries

            $list = new Glossary\Listing();
            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
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

            return $this->adminJson(['data' => $glossaries, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-available-sites", name="pimcore_admin_settings_getavailablesites", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableSitesAction(Request $request)
    {
        $excludeMainSite = $request->get('excludeMainSite');

        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [];
        if (!$excludeMainSite) {
            $sites[] = [
                'id' => 'default',
                'rootId' => 1,
                'domains' => '',
                'rootPath' => '/',
                'domain' => $this->trans('main_site'),
            ];
        }

        foreach ($sitesObjects as $site) {
            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        'id' => $site->getId(),
                        'rootId' => $site->getRootId(),
                        'domains' => implode(',', $site->getDomains()),
                        'rootPath' => $site->getRootPath(),
                        'domain' => $site->getMainDomain(),
                    ];
                }
            } else {
                // site is useless, parent doesn't exist anymore
                $site->delete();
            }
        }

        return $this->adminJson($sites);
    }

    /**
     * @Route("/get-available-countries", name="pimcore_admin_settings_getavailablecountries", methods={"GET"})
     *
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function getAvailableCountriesAction(LocaleServiceInterface $localeService)
    {
        $countries = $localeService->getDisplayRegions();
        asort($countries);

        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'key' => $translation . ' (' . $short . ')',
                    'value' => $short,
                ];
            }
        }

        $result = ['data' => $options, 'success' => true, 'total' => count($options)];

        return $this->adminJson($result);
    }

    /**
     * @Route("/thumbnail-adapter-check", name="pimcore_admin_settings_thumbnailadaptercheck", methods={"GET"})
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
     * @Route("/thumbnail-tree", name="pimcore_admin_settings_thumbnailtree", methods={"GET", "POST"})
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
        $items = $list->getThumbnails();

        $groups = [];
        /** @var Asset\Image\Thumbnail\Config $item */
        foreach ($items as $item) {
            if ($item->getGroup()) {
                if (empty($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getName(),
                        'text' => $item->getGroup(),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                        ];
                }
                $groups[$item->getGroup()]['children'][] =
                    [
                        'id' => $item->getName(),
                        'text' => $item->getName(),
                        'leaf' => true,
                        'iconCls' => 'pimcore_icon_thumbnails',
                    ];
            } else {
                $thumbnails[] = [
                    'id' => $item->getName(),
                    'text' => $item->getName(),
                    'leaf' => true,
                    'iconCls' => 'pimcore_icon_thumbnails',
                ];
            }
        }

        foreach ($groups as $group) {
            $thumbnails[] = $group;
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/thumbnail-downloadable", name="pimcore_admin_settings_thumbnaildownloadable", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailDownloadableAction(Request $request)
    {
        $thumbnails = [];

        $list = new Asset\Image\Thumbnail\Config\Listing();
        $list->setFilter(function (array $config) {
            return array_key_exists('downloadable', $config) ? $config['downloadable'] : false;
        });
        $items = $list->getThumbnails();

        /** @var Asset\Image\Thumbnail\Config $item */
        foreach ($items as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
            ];
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/thumbnail-add", name="pimcore_admin_settings_thumbnailadd", methods={"POST"})
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

        return $this->adminJson(['success' => $success, 'id' => $pipe->getName()]);
    }

    /**
     * @Route("/thumbnail-delete", name="pimcore_admin_settings_thumbnaildelete", methods={"DELETE"})
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

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/thumbnail-get", name="pimcore_admin_settings_thumbnailget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailGetAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));

        return $this->adminJson($pipe);
    }

    /**
     * @Route("/thumbnail-update", name="pimcore_admin_settings_thumbnailupdate", methods={"PUT"})
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
        $mediaOrder = $this->decodeJson($request->get('mediaOrder'));

        foreach ($settingsData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        uksort($mediaData, function ($a, $b) use ($mediaOrder) {
            if ($a === 'default') {
                return -1;
            }

            return ($mediaOrder[$a] < $mediaOrder[$b]) ? -1 : 1;
        });

        foreach ($mediaData as $mediaName => $items) {
            foreach ($items as $item) {
                $type = $item['type'];
                unset($item['type']);

                $pipe->addItem($type, $item, $mediaName);
            }
        }

        $pipe->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/video-thumbnail-adapter-check", name="pimcore_admin_settings_videothumbnailadaptercheck", methods={"GET"})
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
     * @Route("/video-thumbnail-tree", name="pimcore_admin_settings_videothumbnailtree", methods={"GET", "POST"})
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
        $items = $list->getThumbnails();

        $groups = [];
        /** @var Asset\Image\Thumbnail\Config $item */
        foreach ($items as $item) {
            if ($item->getGroup()) {
                if (!$groups[$item->getGroup()]) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getName(),
                        'text' => $item->getGroup(),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                $groups[$item->getGroup()]['children'][] =
                    [
                        'id' => $item->getName(),
                        'text' => $item->getName(),
                        'leaf' => true,
                        'iconCls' => 'pimcore_icon_videothumbnails',
                    ];
            } else {
                $thumbnails[] = [
                    'id' => $item->getName(),
                    'text' => $item->getName(),
                    'leaf' => true,
                    'iconCls' => 'pimcore_icon_videothumbnails',
                ];
            }
        }

        foreach ($groups as $group) {
            $thumbnails[] = $group;
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/video-thumbnail-add", name="pimcore_admin_settings_videothumbnailadd", methods={"POST"})
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

        return $this->adminJson(['success' => $success, 'id' => $pipe->getName()]);
    }

    /**
     * @Route("/video-thumbnail-delete", name="pimcore_admin_settings_videothumbnaildelete", methods={"DELETE"})
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

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/video-thumbnail-get", name="pimcore_admin_settings_videothumbnailget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailGetAction(Request $request)
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        return $this->adminJson($pipe);
    }

    /**
     * @Route("/video-thumbnail-update", name="pimcore_admin_settings_videothumbnailupdate", methods={"PUT"})
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

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/robots-txt", name="pimcore_admin_settings_robotstxtget", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function robotsTxtGetAction()
    {
        $this->checkPermission('robots.txt');

        $config = Config::getRobotsConfig();
        $config = $config->toArray();

        return $this->adminJson([
            'success' => true,
            'data' => $config,
            'onFileSystem' => file_exists(PIMCORE_WEB_ROOT . '/robots.txt'),
        ]);
    }

    /**
     * @Route("/robots-txt", name="pimcore_admin_settings_robotstxtput", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function robotsTxtPutAction(Request $request)
    {
        $this->checkPermission('robots.txt');

        $values = $request->get('data');
        if (!is_array($values)) {
            $values = [];
        }

        File::putPhpFile(
            Config::locateConfigFile('robots.php'),
            to_php_data_file_format($values)
        );

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/tag-management-tree", name="pimcore_admin_settings_tagmanagementtree", methods={"GET", "POST"})
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
                'text' => $item->getName(),
            ];
        }

        return $this->adminJson($tags);
    }

    /**
     * @deprecated
     * @Route("/tag-management-add", name="pimcore_admin_settings_tagmanagementadd", methods={"POST"})
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

        return $this->adminJson(['success' => $success, 'id' => $tag->getName()]);
    }

    /**
     * @deprecated
     * @Route("/tag-management-delete", name="pimcore_admin_settings_tagmanagementdelete", methods={"DELETE"})
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

        return $this->adminJson(['success' => true]);
    }

    /**
     * @deprecated
     * @Route("/tag-management-get", name="pimcore_admin_settings_tagmanagementget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function tagManagementGetAction(Request $request)
    {
        $this->checkPermission('tag_snippet_management');

        $tag = Model\Tool\Tag\Config::getByName($request->get('name'));

        return $this->adminJson($tag);
    }

    /**
     * @deprecated
     * @Route("/tag-management-update", name="pimcore_admin_settings_tagmanagementupdate", methods={"PUT"})
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

                if ($cleanKeyParts[2] == 'date') {
                    $date = $value;
                    $value = null;

                    if (!empty($date) && !empty($data[$cleanKeyParts[0].'.'.$cleanKeyParts[1].'.time'])) {
                        $time = $data[$cleanKeyParts[0].'.'.$cleanKeyParts[1].'.time'];
                        $time = explode('T', $time);
                        $date = explode('T', $date);
                        $value = strtotime($date[0].'T'.$time[1]);
                    }
                } elseif ($cleanKeyParts[2] == 'time') {
                    continue;
                }

                $items[$cleanKeyParts[1]][$cleanKeyParts[2]] = $value;
            }
        }

        $tag->resetItems();
        foreach ($items as $item) {
            $tag->addItem($item);
        }

        // parameters get/post
        $params = [];
        for ($i = 0; $i < 5; $i++) {
            $params[] = [
                'name' => $data['params.name' . $i],
                'value' => $data['params.value' . $i],
            ];
        }
        $tag->setParams($params);

        if ($request->get('name') != $data['name']) {
            $tag->setName($request->get('name')); // set the old name again, so that the old file get's deleted
            $tag->delete(); // delete the old config / file
            $tag->setName($data['name']);
        }

        $tag->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/website-settings", name="pimcore_admin_settings_websitesettings", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function websiteSettingsAction(Request $request)
    {
        $this->checkPermission('website_settings');

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    $value = trim($value);
                }
            }

            if ($request->get('xaction') == 'destroy') {
                $id = $data['id'];
                $setting = WebsiteSetting::getById($id);
                if ($setting instanceof WebsiteSetting) {
                    $setting->delete();

                    return $this->adminJson(['success' => true, 'data' => []]);
                }
            } elseif ($request->get('xaction') == 'update') {
                // save routes
                $setting = WebsiteSetting::getById($data['id']);
                if ($setting instanceof WebsiteSetting) {
                    switch ($setting->getType()) {
                        case 'document':
                        case 'asset':
                        case 'object':
                            if (isset($data['data'])) {
                                $element = Element\Service::getElementByPath($setting->getType(), $data['data']);
                                $data['data'] = $element;
                            }
                            break;
                    }

                    $setting->setValues($data);
                    $setting->save();

                    $data = $this->getWebsiteSettingForEditMode($setting);

                    return $this->adminJson(['data' => $data, 'success' => true]);
                }
            } elseif ($request->get('xaction') == 'create') {
                unset($data['id']);

                // save route
                $setting = new WebsiteSetting();
                $setting->setValues($data);

                $setting->save();

                return $this->adminJson(['data' => $setting->getObjectVars(), 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new WebsiteSetting\Listing();

            $limit = $request->get('limit');
            $start = $request->get('start');

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));

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

            $list->setOrder(static function ($a, $b) use ($sortingSettings) {
                if (!$sortingSettings) {
                    return 0;
                }
                $orderKey = $sortingSettings['orderKey'];
                $aValue = $a[$orderKey] ?? null;
                $bValue = $b[$orderKey] ?? null;
                if ($aValue == $bValue) {
                    return 0;
                }

                $result = $aValue < $bValue ? -1 : 1;
                if ($sortingSettings['order'] === 'DESC') {
                    $result = -1 * $result;
                }

                return $result;
            });

            $totalCount = $list->getTotalCount();
            $list = $list->load();

            $list = array_slice($list, $start, $limit);

            $settings = [];
            foreach ($list as $item) {
                $resultItem = $this->getWebsiteSettingForEditMode($item);
                $settings[] = $resultItem;
            }

            return $this->adminJson(['data' => $settings, 'success' => true, 'total' => $totalCount]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @param WebsiteSetting $item
     *
     * @return array
     */
    private function getWebsiteSettingForEditMode($item)
    {
        $resultItem = [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'language' => $item->getLanguage(),
            'type' => $item->getType(),
            'data' => null,
            'siteId' => $item->getSiteId(),
            'creationDate' => $item->getCreationDate(),
            'modificationDate' => $item->getModificationDate(),
        ];

        switch ($item->getType()) {
            case 'document':
            case 'asset':
            case 'object':
                $element = $item->getData();
                if ($element) {
                    $resultItem['data'] = $element->getRealFullPath();
                }
                break;
            default:
                $resultItem['data'] = $item->getData();
                break;
        }

        return $resultItem;
    }

    /**
     * @Route("/get-available-algorithms", name="pimcore_admin_settings_getavailablealgorithms", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableAlgorithmsAction(Request $request)
    {
        $options = [
            [
                'key' => 'password_hash',
                'value' => 'password_hash',
            ],
        ];

        $algorithms = hash_algos();
        foreach ($algorithms as $algorithm) {
            $options[] = [
                'key' => $algorithm,
                'value' => $algorithm,
            ];
        }

        $result = ['data' => $options, 'success' => true, 'total' => count($options)];

        return $this->adminJson($result);
    }

    /**
     * deleteViews
     * delete views for localized fields when languages are removed to
     * prevent mysql errors
     *
     * @param string $language
     * @param string $dbName
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

    /**
     * @Route("/test-web2print", name="pimcore_admin_settings_testweb2print", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function testWeb2printAction(Request $request)
    {
        $this->checkPermission('web2print_settings');

        $response = $this->render('PimcoreAdminBundle:Admin/Settings:testWeb2print.html.php');
        $html = $response->getContent();

        $adapter = \Pimcore\Web2Print\Processor::getInstance();
        $params = [];

        if ($adapter instanceof \Pimcore\Web2Print\Processor\WkHtmlToPdf) {
            $params['adapterConfig'] = '-O landscape';
        } elseif ($adapter instanceof \Pimcore\Web2Print\Processor\PdfReactor8) {
            $params['adapterConfig'] = [
                'javaScriptMode' => 0,
                'addLinks' => true,
                'appendLog' => true,
                'enableDebugMode' => true,
            ];
        }

        $responseOptions = [
            'Content-Type' => 'application/pdf',
        ];

        $pdfData = $adapter->getPdfFromString($html, $params);

        return new \Symfony\Component\HttpFoundation\Response(
            $pdfData,
            200,
            $responseOptions

        );
    }
}
